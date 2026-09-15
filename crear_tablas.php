<?php
require_once 'config_db.php';

try {
    // Crear tabla de sorteos
    // Guardamos: id, fecha del sorteo, nombre de la lotería, y las posiciones (primera, segunda, tercera)
    $sql = "CREATE TABLE IF NOT EXISTS sorteos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NOT NULL,
        nombre_loteria VARCHAR(100) NOT NULL,
        primera VARCHAR(10) DEFAULT NULL,
        segunda VARCHAR(10) DEFAULT NULL,
        tercera VARCHAR(10) DEFAULT NULL,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_sorteo (fecha, nombre_loteria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Tabla 'sorteos' creada o verificada exitosamente.<br>";

} catch (PDOException $e) {
    echo "Error al crear las tablas: " . $e->getMessage() . "<br>";
}
?>
