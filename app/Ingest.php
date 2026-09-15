<?php
declare(strict_types=1);

/**
 * Ingesta de resultados de sorteo.
 *
 * Una sola implementación para las tres vías que existían por separado:
 *   - POST directo del scraper  (guardar_resultados.php)
 *   - archivo JSON subido por FTP y procesado al cargar la página
 *   - reproceso de históricos   (history_*.json)
 *
 * La deduplicación la garantiza el índice UNIQUE (fecha, nombre_loteria)
 * junto con ON DUPLICATE KEY UPDATE: reejecutar la misma ingesta es seguro.
 */
final class Ingest
{
    /** Tope de archivos por petición, para no agotar max_queries_per_hour. */
    public const MAX_ARCHIVOS_POR_CARGA = 5;

    /** Un sorteo sin jugar llega como 00-00-00; no debe guardarse. */
    private const SIN_JUGAR = ['00', '00', '00'];

    /**
     * @param array<int,mixed> $resultados
     * @return array{insertados:int,actualizados:int,descartados:int}
     */
    public static function guardar(PDO $pdo, array $resultados): array
    {
        $sentencia = $pdo->prepare(
            'INSERT INTO sorteos (fecha, nombre_loteria, primera, segunda, tercera)
             VALUES (:fecha, :nombre, :primera, :segunda, :tercera)
             ON DUPLICATE KEY UPDATE
                 primera = VALUES(primera),
                 segunda = VALUES(segunda),
                 tercera = VALUES(tercera)'
        );

        $insertados = $actualizados = $descartados = 0;

        foreach ($resultados as $crudo) {
            $sorteo = self::validar($crudo);
            if ($sorteo === null) {
                $descartados++;
                continue;
            }

            $sentencia->execute($sorteo);

            // MySQL devuelve 1 en inserción y 2 cuando ON DUPLICATE KEY actualiza.
            match ($sentencia->rowCount()) {
                1       => $insertados++,
                2       => $actualizados++,
                default => null, // 0 = la fila ya tenía esos mismos valores
            };
        }

        return compact('insertados', 'actualizados', 'descartados');
    }

    /**
     * Normaliza y valida un sorteo suelto.
     *
     * @return array<string,string>|null Parámetros listos para execute(), o null si no sirve.
     */
    public static function validar(mixed $crudo): ?array
    {
        if (!is_array($crudo)) {
            return null;
        }

        $fecha  = trim((string) ($crudo['fecha'] ?? ''));
        $nombre = trim((string) ($crudo['nombre'] ?? ''));
        $nums   = $crudo['numeros'] ?? null;

        if ($nombre === '' || !is_array($nums)) {
            return null;
        }

        // Fecha real y con formato ISO: evita filas basura como '0000-00-00'.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return null;
        }
        [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));
        if (!checkdate($mes, $dia, $anio)) {
            return null;
        }

        // El nombre alimenta un VARCHAR(100); recortar evita el truncado silencioso
        // que rompería la unicidad de (fecha, nombre_loteria).
        if (mb_strlen($nombre) > 100) {
            $nombre = mb_substr($nombre, 0, 100);
        }

        $posiciones = [];
        foreach ([0, 1, 2] as $i) {
            $n = str_pad(trim((string) ($nums[$i] ?? '00')), 2, '0', STR_PAD_LEFT);
            if (preg_match('/^\d{2}$/', $n) !== 1) {
                return null;
            }
            $posiciones[] = $n;
        }

        if ($posiciones === self::SIN_JUGAR) {
            return null;
        }

        return [
            ':fecha'   => $fecha,
            ':nombre'  => $nombre,
            ':primera' => $posiciones[0],
            ':segunda' => $posiciones[1],
            ':tercera' => $posiciones[2],
        ];
    }

    /**
     * Procesa los JSON que el VPS deja por FTP en la raíz web.
     *
     * Cada archivo trae su propio token; se compara en tiempo constante y el
     * archivo se borra siempre, válido o no, para que un payload corrupto no
     * se reintente en cada carga de página.
     */
    public static function procesarArchivosPendientes(PDO $pdo, string $directorio): int
    {
        $rutas = [];

        $pendientes = $directorio . '/resultados_pendientes.json';
        if (is_file($pendientes)) {
            $rutas[] = $pendientes;
        }
        foreach (glob($directorio . '/history_*.json') ?: [] as $historico) {
            $rutas[] = $historico;
        }

        $rutas = array_slice($rutas, 0, self::MAX_ARCHIVOS_POR_CARGA);
        $tokenEsperado = (string) Config::get('ingest_token', '');
        $procesados = 0;

        foreach ($rutas as $ruta) {
            try {
                $datos = json_decode((string) file_get_contents($ruta), true);

                $tokenRecibido = is_array($datos) ? (string) ($datos['token'] ?? '') : '';
                $autorizado = $tokenEsperado !== ''
                    && $tokenRecibido !== ''
                    && hash_equals($tokenEsperado, $tokenRecibido);

                if ($autorizado && isset($datos['resultados']) && is_array($datos['resultados'])) {
                    self::guardar($pdo, $datos['resultados']);
                    $procesados++;
                } elseif (!$autorizado) {
                    error_log('Ingest: token inválido en ' . basename($ruta));
                }

                @unlink($ruta);
            } catch (PDOException $e) {
                // Error de base de datos (p. ej. cuota horaria agotada):
                // se conserva el archivo y se corta para reintentar más tarde.
                error_log('Ingest: error de BD en ' . basename($ruta) . ' — ' . $e->getMessage());
                break;
            } catch (Throwable $e) {
                error_log('Ingest: archivo ilegible ' . basename($ruta) . ' — ' . $e->getMessage());
                @unlink($ruta);
            }
        }

        return $procesados;
    }
}
