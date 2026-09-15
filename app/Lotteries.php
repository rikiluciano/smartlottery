<?php
declare(strict_types=1);

/**
 * Catálogo de loterías: familia, logo y horario programado.
 *
 * Todo el conocimiento del dominio vive aquí. Antes estaba duplicado entre
 * core/logic.php, core/mapa_horarios.php y resultados_live.php, con tres
 * copias que se desincronizaban.
 */
final class Lotteries
{
    private const SIN_HORARIO = 9999;

    /**
     * Familia -> patrones que la identifican dentro del nombre del sorteo.
     * El orden importa: gana la primera familia cuyo patrón aparezca.
     *
     * @var array<string,string[]>
     */
    private const FAMILIAS = [
        'Anguilla'     => ['anguilla', 'anguila'],
        'Haiti Bolet'  => ['haiti', 'bolet'],
        'Florida'      => ['florida'],
        'New York'     => ['new york', 'nueva york'],
        'New Jersey'   => ['new jersey'],
        'Georgia'      => ['georgia'],
        'La Primera'   => ['la primera'],
        'La Suerte'    => ['la suerte'],
        'LoteDom'      => ['lotedom'],
        'Loteka'       => ['loteka'],
        'Leidsa'       => ['leidsa'],
        'Real'         => ['real'],
        'Nacional'     => ['nacional', 'gana m'],
        'King Lottery' => ['king lottery'],
    ];

    /** Familia -> nombre del archivo SVG en assets/logos/. */
    private const LOGOS = [
        'Anguilla'     => 'anguilla.svg',
        'Haiti Bolet'  => 'haiti-bolet.svg',
        'Florida'      => 'florida.svg',
        'New York'     => 'new-york.svg',
        'New Jersey'   => 'new-jersey.svg',
        'Georgia'      => 'georgia.svg',
        'La Primera'   => 'la-primera.svg',
        'La Suerte'    => 'la-suerte.svg',
        'LoteDom'      => 'lotedom.svg',
        'Loteka'       => 'loteka.svg',
        'Leidsa'       => 'leidsa.svg',
        'Real'         => 'real.svg',
        'Nacional'     => 'nacional.svg',
        'King Lottery' => 'king-lottery.svg',
    ];

    /**
     * Hora programada de cada sorteo, en minutos desde medianoche.
     *
     * El orden importa: se devuelve la primera clave que aparezca dentro del
     * nombre, así que las variantes más específicas van primero
     * ('la primera noche' antes que 'la primera').
     *
     * @var array<string,int>
     */
    private const HORARIOS = [
        'anguilla 8am' => 480,  'anguilla 9am' => 540,  'anguilla 10am' => 600,
        'anguilla 11am' => 660, 'anguilla 12pm' => 720, 'anguilla 1pm' => 780,
        'anguilla 2pm' => 840,  'anguilla 3pm' => 900,  'anguilla 4pm' => 960,
        'anguilla 5pm' => 1020, 'anguilla 6pm' => 1080, 'anguilla 7pm' => 1140,
        'anguilla 8pm' => 1200, 'anguilla 9pm' => 1260, 'anguilla 10pm' => 1320,

        'la primera noche' => 1200,
        'la primera'       => 720,

        'lotedom' => 835,   // 1:55 PM

        'georgia noche' => 1200,  // 8:00 PM
        'georgia tarde' => 900,   // 3:00 PM
        'georgia dia'   => 740,   // 12:20 PM

        'florida noche' => 1305,  // 9:45 PM
        'florida tarde' => 810,   // 1:30 PM

        'new york noche' => 1350, // 10:30 PM
        'new york tarde' => 870,  // 2:30 PM

        'new jersey noche' => 1375, // 10:55 PM
        'new jersey tarde' => 775,  // 12:55 PM

        'la suerte dominicana' => 750,
        'la suerte 6pm'        => 1080,
        'la suerte'            => 750,

        'king lottery noche' => 1170, // 7:30 PM
        'king lottery dia'   => 750,  // 12:30 PM

        'real noche' => 1200, // 8:00 PM
        'loto real'  => 780,  // 1:00 PM
        'real'       => 780,

        'loteka' => 1195, // 7:55 PM

        'nacional noche'   => 1260, // 9:00 PM
        'nacional gana mas' => 900, // 3:00 PM
        'gana mas'         => 900,
        'loteria nacional' => 1260,

        'haiti bolet 9:30 am'  => 570,
        'haiti bolet 10:30 am' => 630,
        'haiti bolet 11:30 am' => 690,
        'haiti bolet 5:30 pm'  => 1050,
        'haiti bolet 6:30 pm'  => 1110,
        'haiti bolet 7:30 pm'  => 1170,

        'leidsa' => 1255, // 8:55 PM
    ];

    /** Quita acentos y normaliza para comparar nombres de sorteo. */
    public static function normalizar(string $texto): string
    {
        $acentuadas = ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'];
        $planas     = ['a','e','i','o','u','u','n','a','e','i','o','u','u','n'];

        return strtolower(trim(str_replace($acentuadas, $planas, $texto)));
    }

    public static function familia(string $nombreSorteo): string
    {
        $n = self::normalizar($nombreSorteo);

        foreach (self::FAMILIAS as $familia => $patrones) {
            foreach ($patrones as $patron) {
                if (str_contains($n, $patron)) {
                    return $familia;
                }
            }
        }

        return 'Otras';
    }

    /** Ruta del logo relativa a la raíz web, o '' si la familia no tiene. */
    public static function logo(string $nombreSorteo): string
    {
        $archivo = self::LOGOS[self::familia($nombreSorteo)] ?? null;

        return $archivo !== null ? 'assets/logos/' . $archivo : '';
    }

    /**
     * Minutos desde medianoche a los que está programado el sorteo.
     * Devuelve 9999 cuando no se reconoce, para que quede al final al ordenar.
     */
    public static function minutosProgramados(string $nombreSorteo): int
    {
        $n = self::normalizar($nombreSorteo);

        foreach (self::HORARIOS as $clave => $minutos) {
            if (str_contains($n, $clave)) {
                return $minutos;
            }
        }

        return self::horaDesdeNombre($n);
    }

    /** Respaldo: deduce la hora del propio nombre ("Anguilla 8:30 PM"). */
    private static function horaDesdeNombre(string $n): int
    {
        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)/i', $n, $m) === 1) {
            $hora = (int) $m[1];
            $min  = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
            $pm   = strtolower($m[3]) === 'pm';

            if ($hora === 12) {
                $hora = $pm ? 12 : 0;
            } elseif ($pm) {
                $hora += 12;
            }

            return $hora * 60 + $min;
        }

        if (str_contains($n, 'dia'))   { return 720; }
        if (str_contains($n, 'tarde')) { return 900; }
        if (str_contains($n, 'noche')) { return 1140; }

        return self::SIN_HORARIO;
    }
}
