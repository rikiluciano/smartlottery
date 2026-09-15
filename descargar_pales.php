<?php
declare(strict_types=1);

/** Descarga de los listados de palés generados por el VPS. */

require_once __DIR__ . '/app/bootstrap.php';

/** Lista blanca: el parámetro nunca toca el sistema de archivos. */
const DESCARGAS = [
    'posibles'    => ['pales_posibles.txt',    '4950_pales_posibles.txt'],
    'encontrados' => ['pales_encontrados.txt', 'pales_encontrados_historial.txt'],
];

$tipo = (string) ($_GET['tipo'] ?? '');

if (!isset(DESCARGAS[$tipo])) {
    http_response_code(400);
    exit('Tipo de archivo no válido.');
}

[$archivo, $nombreDescarga] = DESCARGAS[$tipo];
$ruta = APP_RAIZ . '/' . $archivo;

if (!is_readable($ruta)) {
    http_response_code(404);
    exit('El sistema todavía no ha generado ese archivo.');
}

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
readfile($ruta);
