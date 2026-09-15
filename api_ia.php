<?php
/**
 * Proxy de servidor para OpenRouter.
 *
 * Motivo: index.php y ia_quinielas.php llamaban a OpenRouter desde JavaScript
 * con la clave incrustada, por lo que la clave se enviaba a cada visitante.
 * Ahora la clave vive solo en secrets.php y nunca sale del servidor.
 *
 * Contra el abuso (la clave sigue siendo gratuita pero limitada):
 *   - solo POST y solo desde el propio dominio
 *   - lista blanca de modelos
 *   - tope de max_tokens
 *   - límite por IP (ventana deslizante en disco)
 */

require_once __DIR__ . '/config_db.php';

header('Content-Type: application/json; charset=utf-8');

const MODELOS_PERMITIDOS = [
    'nvidia/nemotron-3-ultra-550b-a55b:free',
    'nvidia/nemotron-3-super-120b-a12b:free',
    'nvidia/llama-3.1-nemotron-70b-instruct:free',
    'minimax/minimax-m3:free',
];
const MAX_TOKENS_TOPE  = 3000;
const MAX_CHARS_PROMPT = 12000;
const LIMITE_POR_IP    = 20;    // peticiones…
const VENTANA_SEGUNDOS = 600;   // …por cada 10 minutos

function fallo(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    echo json_encode(['error' => ['message' => $mensaje]]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fallo(405, 'Método no permitido');
}

// Solo peticiones originadas en el propio sitio.
$origen = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
$hostPropio = $_SERVER['HTTP_HOST'] ?? '';
if ($origen !== '' && $hostPropio !== '' && stripos($origen, $hostPropio) === false) {
    fallo(403, 'Origen no permitido');
}

$clave = (string) cfg('openrouter_key');
if ($clave === '') {
    error_log('api_ia: falta openrouter_key en secrets.php');
    fallo(503, 'Servicio de IA no configurado');
}

// ---- Límite por IP ----
$ip      = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
$archivo = sys_get_temp_dir() . '/ia_rate_' . sha1($ip) . '.json';
$ahora   = time();
$marcas  = [];
if (is_readable($archivo)) {
    $marcas = json_decode((string) file_get_contents($archivo), true) ?: [];
}
$marcas = array_values(array_filter(
    $marcas,
    static fn($t) => is_int($t) && ($ahora - $t) < VENTANA_SEGUNDOS
));
if (count($marcas) >= LIMITE_POR_IP) {
    fallo(429, 'Demasiadas consultas. Espera unos minutos.');
}
$marcas[] = $ahora;
@file_put_contents($archivo, json_encode($marcas), LOCK_EX);

// ---- Validar el cuerpo ----
$entrada = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($entrada) || !isset($entrada['messages']) || !is_array($entrada['messages'])) {
    fallo(400, 'Cuerpo inválido');
}

$modelo = (string) ($entrada['model'] ?? MODELOS_PERMITIDOS[0]);
if (!in_array($modelo, MODELOS_PERMITIDOS, true)) {
    fallo(400, 'Modelo no permitido');
}

$mensajes = [];
$totalChars = 0;
foreach ($entrada['messages'] as $m) {
    if (!is_array($m) || !isset($m['role'], $m['content'])) {
        continue;
    }
    if (!in_array($m['role'], ['system', 'user', 'assistant'], true)) {
        continue;
    }
    $contenido = (string) $m['content'];
    $totalChars += strlen($contenido);
    if ($totalChars > MAX_CHARS_PROMPT) {
        fallo(413, 'Prompt demasiado largo');
    }
    $mensajes[] = ['role' => $m['role'], 'content' => $contenido];
}
if (!$mensajes) {
    fallo(400, 'Sin mensajes válidos');
}

$maxTokens = (int) ($entrada['max_tokens'] ?? 1500);
$maxTokens = max(1, min($maxTokens, MAX_TOKENS_TOPE));

$cuerpo = ['model' => $modelo, 'messages' => $mensajes, 'max_tokens' => $maxTokens];
if (!empty($entrada['models']) && is_array($entrada['models'])) {
    $alternativos = array_values(array_intersect($entrada['models'], MODELOS_PERMITIDOS));
    if ($alternativos) {
        $cuerpo['models'] = $alternativos;
    }
}

// ---- Reenviar a OpenRouter ----
$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $clave,
        'Content-Type: application/json',
        'HTTP-Referer: https://' . $hostPropio,
        'X-Title: Lottery Analytics',
    ],
    CURLOPT_POSTFIELDS     => json_encode($cuerpo),
]);
$respuesta = curl_exec($ch);
$estado    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errCurl   = curl_error($ch);
curl_close($ch);

if ($respuesta === false) {
    error_log('api_ia: curl — ' . $errCurl);
    fallo(502, 'No se pudo contactar el servicio de IA');
}

// Devolver solo el texto: el cliente no necesita ver la respuesta cruda del proveedor.
$datos = json_decode((string) $respuesta, true);
$texto = $datos['choices'][0]['message']['content'] ?? null;

if ($estado >= 400 || $texto === null) {
    error_log('api_ia: OpenRouter HTTP ' . $estado . ' — ' . substr((string) $respuesta, 0, 500));
    fallo(502, 'El servicio de IA no devolvió un análisis');
}

echo json_encode(['content' => $texto], JSON_UNESCAPED_UNICODE);
