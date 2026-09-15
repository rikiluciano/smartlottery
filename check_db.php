<?php
require 'core/config_db.php';
$stmt = $pdo->query("SELECT COUNT(*) as c FROM sorteos");
$row = $stmt->fetch();
$output = "Total rows: " . $row['c'] . "\n";
file_put_contents('db_count.txt', $output);
echo "Written.";
