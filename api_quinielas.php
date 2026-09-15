<?php
header('Content-Type: application/json');
if (file_exists('prediccion_quinielas.json')) {
    echo file_get_contents('prediccion_quinielas.json');
} else {
    echo json_encode(['error' => 'Archivo no encontrado']);
}
?>
