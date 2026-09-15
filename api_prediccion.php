<?php
declare(strict_types=1);

/** Sirve el JSON que el VPS deja por FTP. */

require_once __DIR__ . '/app/bootstrap.php';

Http::servirArchivoJson(APP_RAIZ . '/prediccion.json');
