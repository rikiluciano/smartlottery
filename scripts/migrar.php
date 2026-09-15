<?php
declare(strict_types=1);

/**
 * Crea o verifica el esquema. Se ejecuta a mano desde línea de comandos:
 *
 *     php scripts/migrar.php
 *
 * Antes esto era crear_tablas.php, accesible por web sin autenticación.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde línea de comandos.');
}

require_once __DIR__ . '/../app/bootstrap.php';

$esquema = <<<SQL
CREATE TABLE IF NOT EXISTS sorteos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    fecha          DATE         NOT NULL,
    nombre_loteria VARCHAR(100) NOT NULL,
    primera        VARCHAR(10)  DEFAULT NULL,
    segunda        VARCHAR(10)  DEFAULT NULL,
    tercera        VARCHAR(10)  DEFAULT NULL,
    actualizado_en TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_sorteo (fecha, nombre_loteria),
    -- La consulta de la portada filtra por fecha y agrupa por lotería.
    KEY idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

try {
    $pdo = Database::conexion();
    $pdo->exec($esquema);
    echo "Esquema verificado correctamente.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
