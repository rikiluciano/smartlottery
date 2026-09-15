<?php
declare(strict_types=1);

/**
 * Arranque común. Toda página y endpoint empieza con:
 *
 *     require_once __DIR__ . '/app/bootstrap.php';
 */

if (defined('APP_INICIADA')) {
    return;
}
define('APP_INICIADA', true);

define('APP_RAIZ', dirname(__DIR__));

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Lotteries.php';
require_once __DIR__ . '/Ingest.php';
require_once __DIR__ . '/Results.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/ResultsPage.php';

/*
 * Versión de los assets para romper la caché del navegador. Se deriva de la
 * fecha del CSS compilado, así que cambia sola en cada build.
 */
$css = APP_RAIZ . '/assets/css/app.css';
define('ASSET_VERSION', is_file($css) ? (string) filemtime($css) : '1');

date_default_timezone_set((string) Config::get('timezone', 'America/Santo_Domingo'));

mb_internal_encoding('UTF-8');

/*
 * En producción los errores van al log, nunca a la pantalla: un aviso de PHP
 * mostrado al visitante filtra rutas del servidor y, en el caso de PDO, las
 * credenciales de conexión.
 */
if (Config::esProduccion()) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
