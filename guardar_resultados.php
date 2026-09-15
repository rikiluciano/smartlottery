<?php
declare(strict_types=1);

/**
 * Ingesta de resultados desde el scraper del VPS.
 *
 * Autenticación por token compartido (secrets.php / variable de entorno).
 * No es un endpoint de navegador: sin CORS y solo POST.
 */

require_once __DIR__ . '/app/bootstrap.php';

Http::exigirMetodo('POST');

if (!Http::dentroDeCuota('ingesta', 60, 300)) {
    Http::error(429, 'Demasiadas peticiones.');
}

$tokenEsperado = (string) Config::get('ingest_token', '');
if ($tokenEsperado === '') {
    error_log('guardar_resultados: falta ingest_token en la configuración.');
    Http::error(503, 'Servicio no configurado.');
}

$cuerpo = json_decode((string) file_get_contents('php://input'), true);
$tokenRecibido = is_array($cuerpo) ? (string) ($cuerpo['token'] ?? '') : '';

// hash_equals compara en tiempo constante: sin él, el tiempo de respuesta
// filtra cuántos caracteres del token son correctos.
if ($tokenRecibido === '' || !hash_equals($tokenEsperado, $tokenRecibido)) {
    Http::error(401, 'No autorizado.');
}

if (!isset($cuerpo['resultados']) || !is_array($cuerpo['resultados'])) {
    Http::error(400, 'Falta la lista de resultados.');
}

try {
    $resumen = Ingest::guardar(Database::conexion(), $cuerpo['resultados']);
} catch (Throwable $e) {
    // El detalle va al log; al cliente no se le filtra el esquema ni las credenciales.
    error_log('guardar_resultados: ' . $e->getMessage());
    Http::error(500, 'Error al guardar.');
}

Http::json(['status' => 'ok'] + $resumen);
