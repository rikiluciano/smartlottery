<?php
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

if ($tipo === 'posibles') {
    $file = 'pales_posibles.txt';
    $name = '4950_pales_posibles.txt';
} elseif ($tipo === 'encontrados') {
    $file = 'pales_encontrados.txt';
    $name = '4949_pales_encontrados_historial.txt';
} else {
    die("Archivo invalido.");
}

if (file_exists($file)) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: no-cache');
    readfile($file);
    exit;
} else {
    die("El archivo aun no ha sido generado por el sistema.");
}
?>
