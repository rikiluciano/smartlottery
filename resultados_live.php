<?php
declare(strict_types=1);

/**
 * Alias histórico de resultados.php.
 *
 * Este archivo era una copia de 314 líneas de resultados.php con su propia
 * versión —ya divergente— de la ingesta y del mapeo de loterías. Se conserva
 * la URL para no romper enlaces existentes.
 */

require_once __DIR__ . '/app/bootstrap.php';

$destino = 'resultados.php';
if (isset($_GET['fecha'])) {
    $destino .= '?fecha=' . rawurlencode((string) $_GET['fecha']);
}

header('Location: ' . $destino, true, 301);
exit;
