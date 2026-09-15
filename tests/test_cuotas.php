<?php
declare(strict_types=1);

T::grupo('Http — cuotas por IP');

$_SERVER['REMOTE_ADDR'] = '203.0.113.99';
$cubo = 'suite_' . bin2hex(random_bytes(4));

T::cierto(!Http::excedeCuota($cubo, 3, 60), 'un cubo nuevo empieza sin consumo');

// excedeCuota() solo consulta: llamarlo no debe contar como intento.
Http::excedeCuota($cubo, 3, 60);
Http::excedeCuota($cubo, 3, 60);
T::cierto(!Http::excedeCuota($cubo, 3, 60), 'consultar la cuota no la consume');

Http::registrarIntento($cubo, 60);
Http::registrarIntento($cubo, 60);
T::cierto(!Http::excedeCuota($cubo, 3, 60), 'dos intentos de tres aún caben');

Http::registrarIntento($cubo, 60);
T::cierto(Http::excedeCuota($cubo, 3, 60), 'al tercer intento se agota');

Http::limpiarCuota($cubo);
T::cierto(!Http::excedeCuota($cubo, 3, 60), 'limpiar reinicia el contador');

// La ventana es deslizante: lo que quedó fuera ya no cuenta.
$cubo2 = 'suite_' . bin2hex(random_bytes(4));
Http::registrarIntento($cubo2, 60);
T::cierto(!Http::excedeCuota($cubo2, 1, 0), 'con ventana de 0 s nada queda vigente');
Http::limpiarCuota($cubo2);

// Aislamiento entre cubos: agotar uno no afecta a otro.
$a = 'suite_a_' . bin2hex(random_bytes(4));
$b = 'suite_b_' . bin2hex(random_bytes(4));
Http::registrarIntento($a, 60);
T::cierto(Http::excedeCuota($a, 1, 60),  'el cubo A queda agotado');
T::cierto(!Http::excedeCuota($b, 1, 60), 'el cubo B no se ve afectado');
Http::limpiarCuota($a);
Http::limpiarCuota($b);

// dentroDeCuota() sí consume: es para endpoints donde toda petición cuenta.
$c = 'suite_c_' . bin2hex(random_bytes(4));
T::cierto(Http::dentroDeCuota($c, 2, 60),  'primera petición admitida');
T::cierto(Http::dentroDeCuota($c, 2, 60),  'segunda petición admitida');
T::cierto(!Http::dentroDeCuota($c, 2, 60), 'la tercera se rechaza');
Http::limpiarCuota($c);
