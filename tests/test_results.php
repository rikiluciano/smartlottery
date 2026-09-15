<?php
declare(strict_types=1);

T::grupo('Results — ordenación de tarjetas');

$hoy = '2026-09-10';
$filas = [
    ['fecha' => $hoy,         'nombre_loteria' => 'Anguilla 8AM',  'primera' => '01', 'segunda' => '02', 'tercera' => '03'],
    ['fecha' => $hoy,         'nombre_loteria' => 'Leidsa',        'primera' => '04', 'segunda' => '05', 'tercera' => '06'],
    ['fecha' => '2026-09-09', 'nombre_loteria' => 'Loteka',        'primera' => '07', 'segunda' => '08', 'tercera' => '09'],
    ['fecha' => $hoy,         'nombre_loteria' => 'Anguilla 10AM', 'primera' => '10', 'segunda' => '11', 'tercera' => '12'],
];

$t = Results::aTarjetas($filas, $hoy);

T::es(4, count($t), 'devuelve una tarjeta por lotería');
T::es('Leidsa',        $t[0]['nombre'], 'el sorteo más tardío de hoy va primero (20:55)');
T::es('Anguilla 10AM', $t[1]['nombre'], 'luego el de las 10:00');
T::es('Anguilla 8AM',  $t[2]['nombre'], 'luego el de las 08:00');
T::es('Loteka',        $t[3]['nombre'], 'los de días previos van al final');

T::cierto($t[3]['es_antiguo'],  'marca como antiguo el que no es de la fecha elegida');
T::cierto(!$t[0]['es_antiguo'], 'no marca como antiguo el de la fecha elegida');
T::es('assets/logos/leidsa.svg', $t[0]['logo'], 'adjunta el logo correcto');

// Deduplicación: la primera fila de cada lotería gana (la consulta viene ordenada).
$dup = Results::aTarjetas([
    ['fecha' => $hoy,         'nombre_loteria' => 'Leidsa', 'primera' => '11', 'segunda' => '22', 'tercera' => '33'],
    ['fecha' => '2026-01-01', 'nombre_loteria' => 'Leidsa', 'primera' => '99', 'segunda' => '99', 'tercera' => '99'],
], $hoy);
T::es(1,    count($dup),        'colapsa las repetidas de una misma lotería');
T::es('11', $dup[0]['primera'], 'se queda con la más reciente');

T::grupo('Http — escapado');

T::es('&lt;script&gt;', Http::esc('<script>'),        'escapa las etiquetas');
T::es('&quot;x&quot;',  Http::esc('"x"'),             'escapa las comillas dobles');
T::es('&#039;x&#039;',  Http::esc("'x'"),             'escapa las comillas simples');
T::es('Anguilla 8AM',   Http::esc('Anguilla 8AM'),    'deja intacto el texto normal');
