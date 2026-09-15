<?php
require_once 'config_db.php';
$stmt = $pdo->query("SELECT nombre_loteria, fecha, primera, segunda, tercera FROM sorteos WHERE nombre_loteria LIKE '%Anguilla 8AM%' ORDER BY fecha DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('dump.txt', json_encode($rows));
echo "Done";
