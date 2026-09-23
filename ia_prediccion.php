<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

// Solo lo consume esta misma web: sin CORS abierto.
Http::exigirMismoOrigen();
if (!Http::dentroDeCuota('ia', 20, 600)) {
    Http::error(429, 'Demasiadas consultas seguidas. Espera unos minutos.');
}

// Recibir datos JSON del POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    Http::error(400, 'No se recibieron datos para analizar.');
}

$inversionTotal = floatval($data['inversionTotal'] ?? 0);
$dias = intval($data['dias'] ?? 0);
$gananciaNetaFinal = floatval($data['gananciaNetaFinal'] ?? 0);
$juego = $data['juegoNombre'] ?? 'juego';
$cantidadLoterias = intval($data['cantidadLoterias'] ?? 1);

// OpenRouter API integration
$openRouterApiKey = (string) Config::get('openrouter_key', '');
if ($openRouterApiKey === '') {
    error_log(basename(__FILE__) . ': falta openrouter_key en la configuración.');
    Http::error(503, 'El análisis con IA no está disponible ahora mismo.');
}

$invF = "$" . number_format($inversionTotal, 2);
$ganF = "$" . number_format($gananciaNetaFinal, 2);

$systemPrompt = "Eres un analista experto en estrategias de apuestas de lotería en República Dominicana. Tu objetivo es explicarle al usuario, de manera muy 'aplatanada' (coloquial, clara, directa y fácil de entender pero profesional), el resultado de su cálculo estratégico de juego. 
INSTRUCCIONES CLAVES:
1. No asumas que el usuario va a ganar el último día de la proyección.
2. EJEMPLIFICA varios escenarios simulando 1 mes de juego. Por ejemplo, cuánto ganaría al mes si en esa semana o mes gana 1 vez, o si gana 2 veces, simulando diferentes aciertos según la cantidad de días que eligió. Que los escenarios sean realistas.
3. Menciona la inversión inicial requerida ($invF), la ganancia neta proyectada en un acierto ($ganF), el tipo de juego ($juego) y la cantidad de loterías ($cantidadLoterias).
4. Dale peso al valor de la estrategia y la paciencia.
5. NO digas 'Revisión quirúrgica completada'. Usa un tono amigable, directo, dominicano coloquial ('aplatanado') pero respetuoso.
6. Devuelve tu respuesta formateada en HTML básico (puedes usar <b>, <i>, <br>, y <span> con clases Tailwind como 'text-gold-400 font-bold' o 'text-teal-400 font-bold' para resaltar números). Solo devuelve el HTML del mensaje, nada más.";

$userPrompt = "Datos del cálculo: Inversión Total para aguantar $dias días: $invF. Ganancia Neta al acertar: $ganF. Juego: $juego. Loterías jugadas al mismo tiempo: $cantidadLoterias. Haz tu análisis aplatanado y simulaciones de 1 mes de juego.";

$postData = [
    "model" => "nvidia/llama-3.1-nemotron-70b-instruct:free", // Using a highly powerful free model
    "messages" => [
        [
            "role" => "system",
            "content" => $systemPrompt
        ],
        [
            "role" => "user",
            "content" => $userPrompt
        ]
    ]
];

$ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $openRouterApiKey,
    "Content-Type: application/json",
    "HTTP-Referer: http://numerosrd.42web.io", // Required by OpenRouter
    "X-Title: Lottery Strategy App" // Optional
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 && $response) {
    $responseData = json_decode($response, true);
    if (isset($responseData['choices'][0]['message']['content'])) {
        $aiMessage = $responseData['choices'][0]['message']['content'];
        
        // Limpiar cualquier markdown block si la IA lo incluyó
        $aiMessage = preg_replace('/```html\s*/', '', $aiMessage);
        $aiMessage = preg_replace('/```\s*/', '', $aiMessage);
        
        Http::json([
            'success' => true,
            'analisis_html' => $aiMessage
        ]);
    }
}

// Fallback si la API de OpenRouter falla o tarda demasiado
$fallbackHtml = "¡Oye mi hermano! La Inteligencia Artificial está un poco congestionada ahora mismo, pero te cuento: La jugada está clara, planeas aguantar $dias días buscando ese $juego. Necesitas <span class='text-gold-400 font-bold'>$invF</span>. Si coronas, te llevas <span class='text-teal-400 font-bold'>$ganF</span> netos. Recuerda que no siempre se gana el último día; si en un mes logras acertar un par de veces, tus números mensuales pueden ser muy buenos gracias a esta estrategia. ¡Mucha paciencia y cero desesperación!";

Http::json([
    'success' => true,
    'analisis_html' => $fallbackHtml
]);
?>
