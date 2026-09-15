<?php
declare(strict_types=1);

/**
 * Proxy de servidor para OpenRouter.
 *
 * Antes index.php e ia_quinielas.php llamaban a OpenRouter desde JavaScript
 * con la clave incrustada en el HTML, así que la clave llegaba al navegador
 * de cada visitante. Ahora vive solo en secrets.php.
 *
 * Como la clave es de uso gratuito y limitada, el proxy se protege con:
 *   - solo POST y solo desde el propio dominio
 *   - lista blanca de modelos
 *   - tope de tokens y de longitud del prompt
 *   - cuota por IP
 */

require_once __DIR__ . '/app/bootstrap.php';

/** Modelos que este sitio puede usar. Cualquier otro se rechaza. */
const MODELOS_PERMITIDOS = [
    'nvidia/nemotron-3-ultra-550b-a55b:free',
    'nvidia/nemotron-3-super-120b-a12b:free',
    'nvidia/llama-3.1-nemotron-70b-instruct:free',
    'minimax/minimax-m3:free',
];
const MAX_TOKENS        = 3000;
const MAX_CHARS_PROMPT  = 12000;
const CUOTA_PETICIONES  = 20;
const CUOTA_VENTANA_SEG = 600;

Http::exigirMetodo('POST');
Http::exigirMismoOrigen();

if (!Http::dentroDeCuota('ia', CUOTA_PETICIONES, CUOTA_VENTANA_SEG)) {
    Http::error(429, 'Has hecho muchas consultas seguidas. Espera unos minutos.');
}

$clave = (string) Config::get('openrouter_key', '');
if ($clave === '') {
    error_log('api_ia: falta openrouter_key en la configuración.');
    Http::error(503, 'El análisis con IA no está disponible ahora mismo.');
}

$entrada = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($entrada) || !is_array($entrada['messages'] ?? null)) {
    Http::error(400, 'Petición mal formada.');
}

$modelo = (string) ($entrada['model'] ?? MODELOS_PERMITIDOS[0]);
if (!in_array($modelo, MODELOS_PERMITIDOS, true)) {
    Http::error(400, 'Modelo no permitido.');
}

$mensajes = [];
$caracteres = 0;
foreach ($entrada['messages'] as $m) {
    if (!is_array($m) || !isset($m['role'], $m['content'])) {
        continue;
    }
    if (!in_array($m['role'], ['system', 'user', 'assistant'], true)) {
        continue;
    }

    $contenido = (string) $m['content'];
    $caracteres += strlen($contenido);
    if ($caracteres > MAX_CHARS_PROMPT) {
        Http::error(413, 'La consulta es demasiado larga.');
    }

    $mensajes[] = ['role' => $m['role'], 'content' => $contenido];
}

if ($mensajes === []) {
    Http::error(400, 'No hay mensajes que enviar.');
}

$peticion = [
    'model'      => $modelo,
    'messages'   => $mensajes,
    'max_tokens' => max(1, min((int) ($entrada['max_tokens'] ?? 1500), MAX_TOKENS)),
];

// Modelos de respaldo, filtrados también contra la lista blanca.
$respaldos = array_values(array_intersect(
    is_array($entrada['models'] ?? null) ? $entrada['models'] : [],
    MODELOS_PERMITIDOS
));
if ($respaldos !== []) {
    $peticion['models'] = $respaldos;
}

$curl = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($curl, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $clave,
        'Content-Type: application/json',
        'HTTP-Referer: https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
        'X-Title: Loteria RD Analytics',
    ],
    CURLOPT_POSTFIELDS     => json_encode($peticion),
]);

$respuesta = curl_exec($curl);
$estado    = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$errorCurl = curl_error($curl);
curl_close($curl);

if ($respuesta === false) {
    error_log('api_ia: curl — ' . $errorCurl);
    Http::error(502, 'No se pudo contactar con el servicio de IA.');
}

$datos = json_decode((string) $respuesta, true);
$texto = $datos['choices'][0]['message']['content'] ?? null;

if ($estado >= 400 || !is_string($texto)) {
    error_log('api_ia: OpenRouter HTTP ' . $estado . ' — ' . substr((string) $respuesta, 0, 300));
    Http::error(502, 'El servicio de IA no devolvió un análisis.');
}

// Se devuelve solo el texto: el cliente no necesita la respuesta cruda.
Http::json(['content' => $texto]);
