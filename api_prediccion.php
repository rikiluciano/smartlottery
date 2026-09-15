<?php
header('Content-Type: application/json');
if (file_exists('prediccion.json')) {
    echo file_get_contents('prediccion.json');
} else {
    echo json_encode(['error' => 'Archivo no encontrado']);
}
?>
