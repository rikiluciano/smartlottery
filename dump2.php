<?php
require_once 'config_db.php';
$stmt = $pdo->query("SELECT nombre_loteria, fecha, primera, segunda, tercera FROM sorteos WHERE fecha >= '2026-09-03' ORDER BY fecha DESC, nombre_loteria ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('db_dump.json', json_encode($rows));
echo "Done";
