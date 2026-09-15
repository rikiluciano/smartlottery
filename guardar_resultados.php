<?php
header('Content-Type: application/json');

// Permitir peticiones solo desde nuestro mismo dominio o local
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'config_db.php';

// Token de seguridad ultra secreto
$SECRET_TOKEN = "Rlabs_Scraper_V1_2026";

// Leer el JSON recibido
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !is_array($data) || !isset($data['token']) || $data['token'] !== $SECRET_TOKEN) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos o no autorizado"]);
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
    echo json_encode(["status" => "error", "message" => "Error de BD: " . $e->getMessage()]);
}
?>
