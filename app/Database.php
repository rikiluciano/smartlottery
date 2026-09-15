<?php
declare(strict_types=1);

/**
 * Fábrica de conexiones PDO.
 *
 * La conexión es perezosa y se reutiliza dentro de la misma petición: en
 * InfinityFree cada conexión cuenta contra `max_connections_per_hour`.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function conexion(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host   = (string) Config::get('db_host', '');
        $nombre = (string) Config::get('db_name', '');
        $user   = (string) Config::get('db_user', '');
        $pass   = (string) Config::get('db_pass', '');

        if ($host === '' || $nombre === '' || $user === '') {
            throw new RuntimeException('Credenciales de base de datos incompletas.');
        }

        self::$pdo = new PDO(
            "mysql:host={$host};dbname={$nombre};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Sentencias preparadas reales, no emuladas: el driver envía
                // los parámetros por separado del SQL.
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]
        );

        return self::$pdo;
    }

    /** Permite inyectar una conexión en los tests. */
    public static function usar(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function disponible(): bool
    {
        try {
            self::conexion();
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
