<?php
header('Content-Type: application/json');

// Endpoint de ingesta máquina-a-máquina: no lo consume ningún navegador,
// así que no necesita CORS. Antes tenía `Allow-Origin: *`, que permitía a
// cualquier web invocarlo desde el navegador de un visitante.
header('Access-Control-Allow-Origin: null');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

require_once 'config_db.php';

$SECRET_TOKEN = (string) cfg('ingest_token');

// Leer el JSON recibido
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($SECRET_TOKEN === ''
    || !$data || !is_array($data) || !isset($data['token'])
    || !hash_equals($SECRET_TOKEN, (string) $data['token'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

if (!isset($data['resultados']) || !is_array($data['resultados'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Payload inválido"]);
    exit;
}

$resultados = $data['resultados'];

$insertados = 0;
$actualizados = 0;
$errores = 0;

try {
    // Usamos INSERT ... ON DUPLICATE KEY UPDATE
    // De esta manera, si extraemos resultados que ya estaban en la BD, simplemente se actualizan (UPSERT)
    $stmt = $pdo->prepare("
        INSERT INTO sorteos (fecha, nombre_loteria, primera, segunda, tercera)
        VALUES (:fecha, :nombre, :primera, :segunda, :tercera)
        ON DUPLICATE KEY UPDATE 
            primera = VALUES(primera),
            segunda = VALUES(segunda),
            tercera = VALUES(tercera)
    ");

    foreach ($resultados as $sorteo) {
        // Validar que tengamos los datos mínimos
        if (empty($sorteo['fecha']) || empty($sorteo['nombre']) || !isset($sorteo['numeros']) || !is_array($sorteo['numeros'])) {
            $errores++;
            continue;
        }

        // Si los números son puros ceros "00" "00" "00" o vacío, no es un sorteo jugado aún, saltamos
        $n1 = $sorteo['numeros'][0] ?? null;
        $n2 = $sorteo['numeros'][1] ?? null;
        $n3 = $sorteo['numeros'][2] ?? null;
        
        if ($n1 == '00' && $n2 == '00' && $n3 == '00') {
            continue;
        }

        $stmt->execute([
            ':fecha' => $sorteo['fecha'],
            ':nombre' => $sorteo['nombre'],
            ':primera' => $n1,
            ':segunda' => $n2,
            ':tercera' => $n3
        ]);
        
        if ($stmt->rowCount() == 1) {
            $insertados++;
        } else if ($stmt->rowCount() == 2) {
            $actualizados++;
        }
    }

    echo json_encode([
        "status" => "success", 
        "message" => "Proceso completado", 
        "insertados" => $insertados, 
        "actualizados" => $actualizados,
        "errores" => $errores
    ]);

} catch (Exception $e) {
    error_log('guardar_resultados: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno"]);
}
?>
