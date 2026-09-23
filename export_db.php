<?php
declare(strict_types=1);

/**
 * Exporta toda la base de datos de sorteos para respaldos automatizados.
 * Protegido por el mismo ingest_token usado para subir resultados.
 */

require_once __DIR__ . '/app/bootstrap.php';

$token_esperado = (string) Config::get('ingest_token', '');
$token_recibido = (string) ($_GET['token'] ?? '');

if ($token_esperado === '' || !hash_equals($token_esperado, $token_recibido)) {
    Http::error(401, 'No autorizado.');
}

$pdo = Database::conexion();
$sentencia = $pdo->query('SELECT fecha, nombre_loteria as nombre, primera, segunda, tercera FROM sorteos ORDER BY fecha DESC, nombre_loteria ASC');
$sorteos = $sentencia->fetchAll();

Http::json([
    'status' => 'success',
    'total' => count($sorteos),
    'fecha_exportacion' => date('Y-m-d H:i:s'),
    'data' => $sorteos
]);
