<?php
header('Content-Type: application/json');

$target_nums = $_REQUEST['target'] ?? null;
$es_digito = isset($_REQUEST['es_digito']) && $_REQUEST['es_digito'] === 'true';

if (!$target_nums) {
    http_response_code(400);
    echo json_encode(["error" => "No target provided"]);
    exit;
}

// Check if it's a comma-separated string of numbers (ties)
if (is_string($target_nums)) {
    $target_nums = explode(',', $target_nums);
}
if (!is_array($target_nums)) {
    $target_nums = [$target_nums];
}

// Tomamos solo el primero en caso de empate múltiple para no abrumar a la IA
$target = strval($target_nums[0]);

$stats_text = "No se encontraron estadísticas para este objetivo.";

if (!$es_digito) {
    // Si es un número (00-99), leemos stats_quinielas.json
    if (file_exists('stats_quinielas.json')) {
        $stats = json_decode(file_get_contents('stats_quinielas.json'), true);
        if (isset($stats[$target])) {
            $s = $stats[$target];
            $stats_text = "Número analizado: {$target}\n";
            $stats_text .= "Última vez que salió: {$s['ultima_aparicion']['fecha']} en la lotería {$s['ultima_aparicion']['loteria']} (posición: {$s['ultima_aparicion']['posicion']}).\n";
            $stats_text .= "Días ausente: {$s['dias_ausente']} días.\n";
            $stats_text .= "Lotería donde más repite: {$s['loteria_mas_frecuente']}.\n";
            $stats_text .= "Posición donde más repite: {$s['posicion_mas_frecuente']}.\n";
            $stats_text .= "Número que SIEMPRE sale el mismo día que {$target} (compañero frecuente): {$s['numero_companero_frecuente']}.\n";
            
            if (count($target_nums) > 1) {
                $stats_text .= "NOTA IMPORTANTE: Hubo un empate con otros números faltantes (".implode(", ", $target_nums)."), pero enfócate solo en el {$target}.\n";
            }
        }
    }
} else {
    // Si es un dígito, es más sencillo, podemos omitir algunas métricas o dar algo genérico
    $stats_text = "Dígito analizado: {$target}\n";
    if (count($target_nums) > 1) {
        $stats_text .= "NOTA IMPORTANTE: Hubo un empate con otros dígitos faltantes (".implode(", ", $target_nums)."), pero enfócate solo en el {$target}.\n";
    }
}

// Configurar el LLM
$openRouterApiKey = "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";
$prompt = "Actúa como un experto analista estadístico de loterías.\n";
$prompt .= "Se ha detectado que el número (o dígito) más rezagado matemáticamente es el {$target}.\n\n";
$prompt .= "Aquí están las estadísticas ESTRICTAS reales calculadas de una base de datos de 35,000 sorteos:\n";
$prompt .= "```\n{$stats_text}\n```\n\n";
$prompt .= "Reglas obligatorias:\n";
$prompt .= "1. NO ALUCINES. Usa ÚNICAMENTE los datos provistos en el bloque anterior. Si algo no está ahí, no lo inventes.\n";
$prompt .= "2. Explica de forma clara, breve (máximo 4 párrafos cortos) y profesional este resultado.\n";
$prompt .= "3. Responde a estas preguntas en tu texto: cuándo fue la última vez que salió y en cuál sorteo exactamente, cuántos días lleva sin salir, cuál ha sido su comportamiento (en qué lotería y posición repite más).\n";
$prompt .= "4. Menciona el compañero frecuente explícitamente y explica que este otro número suele salir el mismo día.\n";
$prompt .= "5. Haz una predicción BREVE: basándote en que es el número más rezagado, indica si tiene probabilidad de salir hoy, mañana o en los próximos 3 días (justifica matemáticamente de forma breve basándote en la ley de probabilidades).\n";
$prompt .= "6. No hables de código, ni digas 'según las estadísticas provistas'. Actúa como si tú mismo hubieras hecho el análisis.\n";

$data = [
    "model" => "google/gemini-flash-1.5",
    "messages" => [
        [
            "role" => "system",
            "content" => "Eres el Analista Principal de un algoritmo de predicción de loterías. Eres objetivo, estadístico y claro."
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ]
];

$ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $openRouterApiKey,
    "Content-Type: application/json",
    "HTTP-Referer: http://numerosrd.42web.io"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 25); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(["error" => "Error conectando con la IA: " . curl_error($ch)]);
} else {
    $decoded = json_decode($response, true);
    if (isset($decoded['choices'][0]['message']['content'])) {
        echo json_encode(["respuesta" => $decoded['choices'][0]['message']['content']]);
    } else {
        echo json_encode(["error" => "Respuesta inesperada de la IA", "raw" => $response]);
    }
}
curl_close($ch);
?>
