<?php
require 'config_db.php';
try {
    $stmt = $pdo->query("SELECT 1");
    echo "OK";
} catch (PDOException $e) {
    echo "PDOException: " . $e->getMessage();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
} catch (Error $e) {
    echo "Error: " . $e->getMessage();
}
?>
