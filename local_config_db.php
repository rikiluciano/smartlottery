<?php
$host = "sql202.infinityfree.com";
$dbname = "if0_40933868_resultados_db";
$username = "if0_40933868";
$password = "WYDk3sCTGK8s0u";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Configurar PDO para que lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurar fetch mode predeterminado a FETCH_ASSOC
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si hay error en la conexión, detener la ejecución y mostrar el error
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
