<?php
require 'config_db.php';
$stmt = $pdo->query("SHOW CREATE TABLE sorteos");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'];
