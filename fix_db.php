<?php
require_once 'config_db.php';
$stmt = $pdo->prepare("DELETE FROM sorteos WHERE fecha = '2026-09-05'");
$stmt->execute();
echo "Deleted " . $stmt->rowCount() . " rows from 2026-09-05.";
