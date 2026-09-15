<?php
declare(strict_types=1);

/**
 * Verifica la consulta de Results contra una base real.
 *
 * Se usa SQLite en memoria: el JOIN + GROUP BY es SQL estándar y se comporta
 * igual que en MySQL. Lo que se está comprobando es la forma de la consulta
 * —que devuelva una sola fila por lotería, la más reciente— no el dialecto.
 */

T::grupo('Results — consulta contra base de datos real');

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('CREATE TABLE sorteos (
    id INTEGER PRIMARY KEY,
    fecha TEXT NOT NULL,
    nombre_loteria TEXT NOT NULL,
    primera TEXT, segunda TEXT, tercera TEXT,
    UNIQUE (fecha, nombre_loteria)
)');

$insertar = $pdo->prepare(
    'INSERT INTO sorteos (fecha, nombre_loteria, primera, segunda, tercera)
     VALUES (?, ?, ?, ?, ?)'
);

// Historial: Leidsa tres días seguidos, Loteka solo un día antiguo,
// y una lotería fuera de la ventana de búsqueda.
$insertar->execute(['2026-09-08', 'Leidsa', '11', '11', '11']);
$insertar->execute(['2026-09-09', 'Leidsa', '22', '22', '22']);
$insertar->execute(['2026-09-10', 'Leidsa', '33', '33', '33']);
$insertar->execute(['2026-09-07', 'Loteka', '44', '44', '44']);
$insertar->execute(['2026-09-11', 'Leidsa', '99', '99', '99']); // futuro respecto al corte
$insertar->execute(['2020-01-01', 'Lotería Fósil', '55', '55', '55']);

$filas = Results::ultimoPorLoteria($pdo, '2026-09-10');
$porNombre = [];
foreach ($filas as $f) { $porNombre[$f['nombre_loteria']] = $f; }

T::es(2, count($filas), 'una fila por lotería dentro de la ventana');
T::es('33', $porNombre['Leidsa']['primera'] ?? null, 'de Leidsa devuelve la del 10, no la del 8 ni la del 9');
T::es('2026-09-10', $porNombre['Leidsa']['fecha'] ?? null, 'con la fecha correcta');
T::es('44', $porNombre['Loteka']['primera'] ?? null, 'incluye la lotería con un solo sorteo antiguo');
T::cierto(!isset($porNombre['Leidsa']) || $porNombre['Leidsa']['primera'] !== '99',
          'excluye los sorteos posteriores a la fecha de corte');
T::cierto(!isset($porNombre['Lotería Fósil']),
          'excluye lo que cae fuera de la ventana de ' . Results::DIAS_DE_BUSQUEDA . ' días');

// Con datos reales, extremo a extremo: consulta -> tarjetas -> orden.
$tarjetas = Results::aTarjetas(Results::ultimoPorLoteria($pdo, '2026-09-10'), '2026-09-10');
T::es(2, count($tarjetas), 'produce dos tarjetas');
T::es('Leidsa', $tarjetas[0]['nombre'], 'la de hoy va antes que la antigua');
T::cierto($tarjetas[1]['es_antiguo'], 'Loteka queda marcada como antigua');

T::grupo('Results — respaldo en disco cuando la base no responde');

$tmp = sys_get_temp_dir() . '/lottery_test_' . getmypid();
@mkdir($tmp);
file_put_contents($tmp . '/db_backup.json', json_encode([
    ['fecha' => '2026-09-09', 'nombre_loteria' => 'Leidsa', 'primera' => '77', 'segunda' => '77', 'tercera' => '77'],
    ['fecha' => '2026-09-10', 'nombre_loteria' => 'Leidsa', 'primera' => '88', 'segunda' => '88', 'tercera' => '88'],
]));
file_put_contents($tmp . '/resultados_pendientes.json', json_encode([
    'token' => 'x',
    'resultados' => [['fecha' => '2026-09-10', 'nombre' => 'Loteka', 'numeros' => ['66', '66', '66']]],
]));

$respaldo = Results::desdeRespaldo($tmp, '2026-09-10');
$t = Results::aTarjetas($respaldo, '2026-09-10');
$nombres = array_column($t, 'nombre');

T::cierto(in_array('Leidsa', $nombres, true), 'recupera del respaldo en disco');
T::cierto(in_array('Loteka', $nombres, true), 'fusiona los pendientes que aún no llegaron a la base');
$leidsa = $t[array_search('Leidsa', $nombres, true)];
T::es('88', $leidsa['primera'], 'del respaldo toma la fila más reciente');

array_map('unlink', glob($tmp . '/*') ?: []);
@rmdir($tmp);
