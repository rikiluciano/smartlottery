<?php
declare(strict_types=1);

/** Ejecuta toda la suite:  php tests/run.php */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/harness.php';

foreach (glob(__DIR__ . '/test_*.php') ?: [] as $archivo) {
    require $archivo;
}

exit(T::resumen());
