<?php
declare(strict_types=1);

T::grupo('Lotteries — familia y logo');

T::es('Anguilla',     Lotteries::familia('Anguilla 8AM'),          'reconoce Anguilla');
T::es('Anguilla',     Lotteries::familia('Anguila 10PM'),          'tolera la grafía con una sola L');
T::es('Haiti Bolet',  Lotteries::familia('Haiti Bolet 9:30 AM'),   'reconoce Haiti Bolet');
T::es('Nacional',     Lotteries::familia('Lotería Nacional Noche'),'reconoce Nacional con acento');
T::es('Nacional',     Lotteries::familia('Gana Más'),              'Gana Más pertenece a Nacional');
T::es('New York',     Lotteries::familia('Nueva York Tarde'),      'acepta el nombre en español');
T::es('Otras',        Lotteries::familia('Sorteo Desconocido'),    'desconocida cae en Otras');

T::es('assets/logos/leidsa.svg', Lotteries::logo('Leidsa'),        'devuelve la ruta del logo');
T::es('',                        Lotteries::logo('Sorteo Raro'),   'sin logo para familia desconocida');

T::grupo('Lotteries — horario programado');

T::es(480,  Lotteries::minutosProgramados('Anguilla 8AM'),      'Anguilla 8AM -> 08:00');
T::es(1320, Lotteries::minutosProgramados('Anguilla 10PM'),     'Anguilla 10PM -> 22:00');
T::es(1255, Lotteries::minutosProgramados('Leidsa'),            'Leidsa -> 20:55');
T::es(1200, Lotteries::minutosProgramados('La Primera Noche'),  'la variante más específica gana');
T::es(720,  Lotteries::minutosProgramados('La Primera'),        'la variante base sigue funcionando');
T::es(835,  Lotteries::minutosProgramados('LoteDom'),           'LoteDom -> 13:55');
T::es(780,  Lotteries::minutosProgramados('Loto Real'),         'Loto Real -> 13:00');
T::es(1200, Lotteries::minutosProgramados('Real Noche'),        'Real Noche -> 20:00');

// Respaldo: deducir la hora del propio nombre cuando no está en el mapa.
T::es(870,  Lotteries::minutosProgramados('Sorteo Nuevo 2:30 PM'), 'deduce 2:30 PM del nombre');
T::es(0,    Lotteries::minutosProgramados('Sorteo Nuevo 12:00 AM'),'12 AM es medianoche, no mediodía');
T::es(720,  Lotteries::minutosProgramados('Sorteo Nuevo 12:00 PM'),'12 PM es mediodía');
T::es(9999, Lotteries::minutosProgramados('Sorteo Sin Hora'),      'sin hora -> 9999 (al final)');
