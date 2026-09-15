<?php
header('Content-Type: application/json');
$cache_file = __DIR__ . '/super_prediccion.json';
if (file_exists($cache_file)) {
    echo file_get_contents($cache_file);
} else {
    echo json_encode(['error' => 'No hay super prediccion disponible']);
}
?>
