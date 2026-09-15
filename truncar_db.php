<?php
require_once 'config_db.php';

try {
    $pdo->exec("TRUNCATE TABLE sorteos");
    echo "<h1>Base de datos reseteada con exito.</h1>";
    echo "<p>La tabla sorteos ha sido truncada y esta vacia.</p>";
    echo "<p><a href='index.html'>Volver al inicio</a></p>";
} catch (Exception $e) {
    echo "<h1>Error reseteando la base de datos</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
