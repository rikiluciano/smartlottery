<?php
/**
 * Conexión PDO y carga de credenciales.
 *
 * Orden de resolución:
 *   1. Variables de entorno (DB_HOST, DB_NAME, DB_USER, DB_PASS, ...)
 *   2. secrets.php (fuera de git — ver secrets.example.php)
 *
 * Nunca escribir credenciales en este archivo: está versionado.
 */

function cfg(string $clave, ?string $porDefecto = null): ?string
{
    static $secretos = null;

    $env = getenv(strtoupper($clave));
    if ($env !== false && $env !== '') {
        return $env;
    }

    if ($secretos === null) {
        $ruta = __DIR__ . '/secrets.php';
        $secretos = file_exists($ruta) ? (array) require $ruta : [];
    }

    return $secretos[$clave] ?? $porDefecto;
}

$host   = cfg('db_host');
$dbname = cfg('db_name');
$username = cfg('db_user');
$password = cfg('db_pass');

if (!$host || !$dbname || !$username) {
    error_log('config_db: faltan credenciales (revisar secrets.php o variables de entorno)');
    http_response_code(503);
    die('Servicio no disponible.');
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // El mensaje real va al log; al visitante no se le filtra host ni usuario.
    error_log('config_db: fallo de conexión — ' . $e->getMessage());
    http_response_code(503);
    die('Servicio no disponible temporalmente.');
}
