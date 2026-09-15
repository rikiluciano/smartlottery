<?php
require 'config_db.php';
$stmt = $pdo->query("SELECT nombre_loteria FROM sorteos WHERE fecha='2026-09-05'");
foreach($stmt as $r) {
    echo $r['nombre_loteria'] . "\n";
}
