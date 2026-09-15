<?php
require 'core/config_db.php';
try {
    $stmt = $pdo->query("SELECT 1");
    file_put_contents('db_error.txt', 'DB OK');
} catch (Exception $e) {
    file_put_contents('db_error.txt', 'DB ERROR: ' . $e->getMessage());
}
?>
