<?php
// Backup original
$original = file_get_contents('config_db.php');

// Mock config_db.php to avoid database errors
file_put_contents('config_db.php', '<?php $pdo = new class { public function prepare() { return new class { public function execute() { return true; } public function fetchAll() { return []; } }; } public function query() { return $this->prepare(); } public function exec() { return true; } };');

try {
    // Capture any output
    ob_start();
    require_once 'core/logic.php';
    ob_end_clean();

    $passed = 0;
    $failed = 0;

    function assertEqual($expected, $actual, $testName) {
        global $passed, $failed;
        if ($expected === $actual) {
            echo "PASS: $testName\n";
            $passed++;
        } else {
            echo "FAIL: $testName\n";
            echo "   Expected: " . json_encode($expected) . "\n";
            echo "   Actual:   " . json_encode($actual) . "\n";
            $failed++;
        }
    }

    echo "Running PHP Component Tests...\n";
    echo "------------------------------\n";

    // Test obtenerDatosFamilia
    $fam = obtenerDatosFamilia('Lotedom');
    assertEqual('LoteDom', $fam['familia'], "obtenerDatosFamilia('Lotedom')");
    assertEqual('img/logos/lotedom.svg', $fam['logo'], "obtenerDatosFamilia('Lotedom') logo");

    $fam = obtenerDatosFamilia('New York Noche');
    assertEqual('New York', $fam['familia'], "obtenerDatosFamilia('New York Noche')");

    $fam = obtenerDatosFamilia('Haiti Bolet 10:30 AM');
    assertEqual('Haiti Bolet', $fam['familia'], "obtenerDatosFamilia('Haiti Bolet')");

    $fam = obtenerDatosFamilia('Desconocida');
    assertEqual('Otras', $fam['familia'], "obtenerDatosFamilia('Desconocida')");

    // Test extraerHora
    assertEqual(8 * 60, extraerHora('Anguilla 8:00 AM'), "extraerHora('Anguilla 8:00 AM')");
    assertEqual(20 * 60, extraerHora('Anguilla 8:00 PM'), "extraerHora('Anguilla 8:00 PM')");
    assertEqual(12 * 60, extraerHora('Florida Día'), "extraerHora('Florida Día')");
    assertEqual(15 * 60, extraerHora('Florida Tarde'), "extraerHora('Florida Tarde')");
    assertEqual(19 * 60, extraerHora('New York Noche'), "extraerHora('New York Noche')");
    assertEqual(9999, extraerHora('Sorteo Aleatorio'), "extraerHora('Sorteo Aleatorio')");

    echo "------------------------------\n";
    echo "Total: " . ($passed + $failed) . ", Passed: $passed, Failed: $failed\n";
} finally {
    // Restore original
    file_put_contents('config_db.php', $original);
}
