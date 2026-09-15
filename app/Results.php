<?php
declare(strict_types=1);

/**
 * Consulta y ordenación de los resultados que ve el usuario.
 */
final class Results
{
    /**
     * Ventana de búsqueda hacia atrás, en días.
     *
     * La consulta anterior era `WHERE fecha <= :fecha` sin límite: traía el
     * historial completo a memoria en cada carga de página y luego descartaba
     * casi todo en PHP. Con la ventana, el escaneo queda acotado y estable
     * aunque el histórico crezca durante años.
     */
    public const DIAS_DE_BUSQUEDA = 90;

    /**
     * Último resultado de cada lotería hasta la fecha indicada.
     *
     * El trabajo lo hace MySQL con un GROUP BY, no PHP. Se devuelve una fila
     * por lotería (~43) en lugar de decenas de miles.
     *
     * @return array<int,array<string,string>>
     */
    public static function ultimoPorLoteria(PDO $pdo, string $hasta): array
    {
        $desde = date('Y-m-d', strtotime($hasta . ' -' . self::DIAS_DE_BUSQUEDA . ' days'));

        $sql = 'SELECT s.fecha, s.nombre_loteria, s.primera, s.segunda, s.tercera
                  FROM sorteos s
                  JOIN (
                        SELECT nombre_loteria, MAX(fecha) AS ultima
                          FROM sorteos
                         WHERE fecha <= :hasta AND fecha >= :desde
                      GROUP BY nombre_loteria
                       ) u
                    ON u.nombre_loteria = s.nombre_loteria
                   AND u.ultima         = s.fecha
                 WHERE s.fecha <= :hasta2 AND s.fecha >= :desde2';

        $sentencia = $pdo->prepare($sql);
        $sentencia->execute([
            ':hasta'  => $hasta,  ':desde'  => $desde,
            ':hasta2' => $hasta,  ':desde2' => $desde,
        ]);

        return $sentencia->fetchAll();
    }

    /**
     * Convierte filas crudas en las tarjetas que pinta la vista.
     *
     * @param  array<int,array<string,mixed>> $filas
     * @return array<int,array<string,mixed>>
     */
    public static function aTarjetas(array $filas, string $fechaSeleccionada): array
    {
        $tarjetas = [];
        $vistas   = [];

        foreach ($filas as $fila) {
            $nombre = (string) ($fila['nombre_loteria'] ?? '');
            if ($nombre === '' || isset($vistas[$nombre])) {
                continue;
            }
            $vistas[$nombre] = true;

            $fecha = (string) ($fila['fecha'] ?? '');

            $tarjetas[] = [
                'nombre'     => $nombre,
                'primera'    => (string) ($fila['primera'] ?? '00'),
                'segunda'    => (string) ($fila['segunda'] ?? '00'),
                'tercera'    => (string) ($fila['tercera'] ?? '00'),
                'fecha_real' => $fecha,
                'es_antiguo' => $fecha !== $fechaSeleccionada,
                'logo'       => Lotteries::logo($nombre),
                'familia'    => Lotteries::familia($nombre),
                'minutos'    => Lotteries::minutosProgramados($nombre),
            ];
        }

        self::ordenar($tarjetas, $fechaSeleccionada);

        return $tarjetas;
    }

    /**
     * Orden de presentación:
     *   1. Los sorteos de la fecha elegida van arriba.
     *   2. Entre ellos, el más reciente primero (hora descendente).
     *   3. Los pendientes van debajo, en su orden natural del día (ascendente).
     *
     * @param array<int,array<string,mixed>> $tarjetas
     */
    private static function ordenar(array &$tarjetas, string $fechaSeleccionada): void
    {
        usort($tarjetas, static function (array $a, array $b) use ($fechaSeleccionada): int {
            $aEsDeHoy = $a['fecha_real'] === $fechaSeleccionada;
            $bEsDeHoy = $b['fecha_real'] === $fechaSeleccionada;

            if ($aEsDeHoy !== $bEsDeHoy) {
                return $aEsDeHoy ? -1 : 1;
            }

            if ($a['minutos'] !== $b['minutos']) {
                return $aEsDeHoy
                    ? $b['minutos'] <=> $a['minutos']   // ya salieron: el último arriba
                    : $a['minutos'] <=> $b['minutos'];  // por salir: en orden del día
            }

            return strcmp((string) $a['nombre'], (string) $b['nombre']);
        });
    }

    /**
     * Respaldo cuando MySQL no responde (cuota agotada, caída del hosting).
     *
     * @return array<int,array<string,string>>
     */
    public static function desdeRespaldo(string $directorio, string $hasta): array
    {
        $filas = [];

        $respaldo = $directorio . '/db_backup.json';
        if (is_file($respaldo)) {
            $datos = json_decode((string) file_get_contents($respaldo), true);
            foreach (is_array($datos) ? $datos : [] as $fila) {
                if (is_array($fila) && ($fila['fecha'] ?? '') <= $hasta) {
                    $filas[] = $fila;
                }
            }
        }

        // Los resultados de hoy pueden estar solo en el JSON pendiente.
        $pendientes = $directorio . '/resultados_pendientes.json';
        if (is_file($pendientes)) {
            $datos = json_decode((string) file_get_contents($pendientes), true);
            foreach ($datos['resultados'] ?? [] as $r) {
                $sorteo = Ingest::validar($r);
                if ($sorteo === null || $sorteo[':fecha'] > $hasta) {
                    continue;
                }
                $filas[] = [
                    'fecha'          => $sorteo[':fecha'],
                    'nombre_loteria' => $sorteo[':nombre'],
                    'primera'        => $sorteo[':primera'],
                    'segunda'        => $sorteo[':segunda'],
                    'tercera'        => $sorteo[':tercera'],
                ];
            }
        }

        // Más reciente primero: aTarjetas() se queda con la primera de cada lotería.
        usort($filas, static fn(array $a, array $b): int => strcmp((string) $b['fecha'], (string) $a['fecha']));

        return $filas;
    }
}
