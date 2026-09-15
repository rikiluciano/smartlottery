<?php
declare(strict_types=1);

/**
 * Prepara el modelo de vista de la página de resultados.
 *
 * Sustituye a core/logic.php, que mezclaba ingesta, mantenimiento de base de
 * datos, consulta, ordenación y efectos secundarios en un solo archivo, y del
 * que resultados_live.php mantenía una copia divergente.
 */
final class ResultsPage
{
    /**
     * @return array{
     *     fechaHoy:string,
     *     fechaSeleccionada:string,
     *     tarjetas:array<int,array<string,mixed>>,
     *     totalLoterias:int,
     *     modoRespaldo:bool
     * }
     */
    public static function preparar(): array
    {
        $fechaHoy          = date('Y-m-d');
        $fechaSeleccionada = self::fechaSolicitada($fechaHoy);

        $filas        = [];
        $modoRespaldo = false;

        try {
            $pdo = Database::conexion();

            // El VPS deja los resultados nuevos como JSON por FTP; se absorben
            // aquí porque el hosting bloquea las peticiones que no son de
            // navegador y el scraper no puede llamar al endpoint directamente.
            Ingest::procesarArchivosPendientes($pdo, APP_RAIZ);

            $filas = Results::ultimoPorLoteria($pdo, $fechaSeleccionada);
        } catch (Throwable $e) {
            // Cuota horaria agotada o base caída: se sirve el último respaldo
            // en disco en lugar de mostrar una página vacía.
            error_log('ResultsPage: ' . $e->getMessage());
            $filas        = Results::desdeRespaldo(APP_RAIZ, $fechaSeleccionada);
            $modoRespaldo = true;
        }

        $tarjetas = Results::aTarjetas($filas, $fechaSeleccionada);

        return [
            'fechaHoy'          => $fechaHoy,
            'fechaSeleccionada' => $fechaSeleccionada,
            'tarjetas'          => $tarjetas,
            'totalLoterias'     => count($tarjetas),
            'modoRespaldo'      => $modoRespaldo,
        ];
    }

    /** Fecha pedida por query string, validada y nunca futura. */
    private static function fechaSolicitada(string $fechaHoy): string
    {
        $pedida = trim((string) ($_GET['fecha'] ?? ''));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pedida) !== 1) {
            return $fechaHoy;
        }

        [$anio, $mes, $dia] = array_map('intval', explode('-', $pedida));
        if (!checkdate($mes, $dia, $anio) || $pedida > $fechaHoy) {
            return $fechaHoy;
        }

        return $pedida;
    }
}
