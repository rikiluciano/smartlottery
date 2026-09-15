<?php
require_once 'config_db.php';
try {
    if (!file_exists('resultados_pendientes.json')) {
        die("No json file");
    }
    $json = file_get_contents('resultados_pendientes.json');
    $data = json_decode($json, true);
    if (!$data || !isset($data['resultados'])) die("invalid json");
    $resultados = $data['resultados'];
    $stmt = $pdo->prepare("
        INSERT INTO sorteos (fecha, nombre_loteria, primera, segunda, tercera) 
        VALUES (:fecha, :nombre, :primera, :segunda, :tercera)
        ON DUPLICATE KEY UPDATE 
            primera = VALUES(primera),
            segunda = VALUES(segunda),
            tercera = VALUES(tercera)
    ");
    $count = 0;
    foreach ($resultados as $sorteo) {
        if (!empty($sorteo['fecha']) && !empty($sorteo['nombre']) && isset($sorteo['numeros'])) {
            $stmt->execute([
                ':fecha' => $sorteo['fecha'],
                ':nombre' => $sorteo['nombre'],
                ':primera' => $sorteo['numeros'][0] ?? '00',
                ':segunda' => $sorteo['numeros'][1] ?? '00',
                ':tercera' => $sorteo['numeros'][2] ?? '00'
            ]);
            $count++;
        }
    }
    echo "Success: $count inserted.";
    unlink('resultados_pendientes.json');
} catch (Exception $e) {
    echo "Error DB: " . $e->getMessage();
}
