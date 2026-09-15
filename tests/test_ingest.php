<?php
declare(strict_types=1);

T::grupo('Ingest — validación de sorteos');

$valido = ['fecha' => '2026-09-10', 'nombre' => 'Leidsa', 'numeros' => ['07', '23', '45']];
$r = Ingest::validar($valido);
T::cierto(is_array($r),                        'acepta un sorteo bien formado');
T::es('2026-09-10', $r[':fecha']  ?? null,     'conserva la fecha');
T::es('Leidsa',     $r[':nombre'] ?? null,     'conserva el nombre');
T::es('07',         $r[':primera'] ?? null,    'conserva la primera posición');

T::es(null, Ingest::validar(['fecha' => '2026-09-10', 'nombre' => 'X', 'numeros' => ['00','00','00']]),
      'descarta el sorteo sin jugar (00-00-00)');
T::es(null, Ingest::validar(['fecha' => '10/09/2026', 'nombre' => 'X', 'numeros' => ['1','2','3']]),
      'descarta fecha con formato no ISO');
T::es(null, Ingest::validar(['fecha' => '2026-02-30', 'nombre' => 'X', 'numeros' => ['1','2','3']]),
      'descarta fecha inexistente (30 de febrero)');
T::es(null, Ingest::validar(['fecha' => '2026-09-10', 'nombre' => '',  'numeros' => ['1','2','3']]),
      'descarta nombre vacío');
T::es(null, Ingest::validar(['fecha' => '2026-09-10', 'nombre' => 'X', 'numeros' => 'no-es-array']),
      'descarta números que no son lista');
T::es(null, Ingest::validar(['fecha' => '2026-09-10', 'nombre' => 'X', 'numeros' => ['7','ab','3']]),
      'descarta números no numéricos');
T::es(null, Ingest::validar('cadena suelta'), 'descarta una entrada que no es array');

$r = Ingest::validar(['fecha' => '2026-09-10', 'nombre' => 'X', 'numeros' => ['7', '3', '0']]);
T::es('07', $r[':primera'] ?? null, 'rellena con cero a la izquierda');
T::es('00', $r[':tercera'] ?? null, '"0" se normaliza a "00"');

$r = Ingest::validar(['fecha' => '2026-09-10', 'nombre' => str_repeat('N', 150), 'numeros' => ['1','2','3']]);
T::es(100, mb_strlen($r[':nombre'] ?? ''), 'recorta el nombre a los 100 del VARCHAR');

$r = Ingest::validar(['fecha' => '2026-09-10', 'nombre' => 'X', 'numeros' => ['12', '34']]);
T::es('00', $r[':tercera'] ?? null, 'la tercera posición ausente queda en 00');
