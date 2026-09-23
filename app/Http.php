<?php
declare(strict_types=1);

/**
 * Utilidades de petición y respuesta: cabeceras, JSON, escapado y cuotas.
 */
final class Http
{
    /** Cabeceras de seguridad para toda página HTML. */
    public static function cabecerasDeSeguridad(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header_remove('X-Powered-By');

        if (self::esHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function esHttps(): bool
    {
        return (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /** Escapa texto para insertarlo en HTML. Alias corto: e(). */
    public static function esc(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function json(mixed $datos, int $estado = 200): never
    {
        if (!headers_sent()) {
            http_response_code($estado);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }

        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(int $estado, string $mensaje): never
    {
        self::json(['error' => ['message' => $mensaje]], $estado);
    }

    /** Sirve un JSON pregenerado por el VPS, con caché corta. */
    public static function servirArchivoJson(string $ruta, int $segundosCache = 30): never
    {
        if (!is_readable($ruta)) {
            self::error(404, 'Todavía no hay datos disponibles.');
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: public, max-age=' . $segundosCache);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) filemtime($ruta)) . ' GMT');
        }

        readfile($ruta);
        exit;
    }

    public static function exigirMetodo(string $metodo): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($metodo)) {
            self::error(405, 'Método no permitido.');
        }
    }

    /**
     * Configura y envía las cabeceras CORS.
     * Permite peticiones desde el mismo dominio o los especificados.
     */
    public static function cors(array $origenesPermitidos = []): void
    {
        if (headers_sent()) {
            return;
        }

        $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
        $propio = $_SERVER['HTTP_HOST'] ?? '';
        $esquema = self::esHttps() ? 'https://' : 'http://';
        
        if (empty($origenesPermitidos)) {
            $origenesPermitidos = [$esquema . $propio];
        }

        if ($origen !== '' && in_array($origen, $origenesPermitidos, true)) {
            header('Access-Control-Allow-Origin: ' . $origen);
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400'); // Cache options por 24h
        }

        // Manejar las peticiones preflight (OPTIONS)
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Rechaza peticiones originadas fuera del propio sitio.
     *
     * No sustituye a la autenticación —Origin es falsificable fuera del
     * navegador— pero sí impide que otra web use estos endpoints desde el
     * navegador de un visitante.
     */
    public static function exigirMismoOrigen(): void
    {
        self::cors(); // Invocar cors para inyectar cabeceras.
        
        $origen = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        $propio = $_SERVER['HTTP_HOST'] ?? '';

        if ($origen === '' || $propio === '') {
            return; // Sin datos para decidir; la autenticación real va aparte.
        }

        if (stripos((string) parse_url($origen, PHP_URL_HOST) ?: '', $propio) === false) {
            self::error(403, 'Origen no permitido.');
        }
    }

    private static function archivoCuota(string $cubo): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');

        return sys_get_temp_dir() . '/cuota_' . sha1($cubo . '|' . $ip) . '.json';
    }

    /** Marcas de tiempo vigentes dentro de la ventana, para esta IP y cubo. */
    private static function marcasVigentes(string $cubo, int $ventanaSegundos): array
    {
        $archivo = self::archivoCuota($cubo);
        if (!is_readable($archivo)) {
            return [];
        }

        $leido = json_decode((string) file_get_contents($archivo), true);
        $ahora = time();

        return array_values(array_filter(
            is_array($leido) ? $leido : [],
            static fn(mixed $t): bool => is_int($t) && ($ahora - $t) < $ventanaSegundos
        ));
    }

    /**
     * ¿Se superó la cuota? Solo consulta; no cuenta esta petición.
     *
     * Se separa de registrarIntento() para poder contar únicamente los
     * intentos fallidos de autenticación: si contara también los correctos,
     * el administrador se bloquearía a sí mismo al recargar el panel.
     */
    public static function excedeCuota(string $cubo, int $maximo, int $ventanaSegundos): bool
    {
        return count(self::marcasVigentes($cubo, $ventanaSegundos)) >= $maximo;
    }

    /** Anota una petición en el cubo indicado. */
    public static function registrarIntento(string $cubo, int $ventanaSegundos): void
    {
        $marcas   = self::marcasVigentes($cubo, $ventanaSegundos);
        $marcas[] = time();

        @file_put_contents(self::archivoCuota($cubo), json_encode($marcas), LOCK_EX);
    }

    /** Limpia el historial del cubo (p. ej. tras autenticarse con éxito). */
    public static function limpiarCuota(string $cubo): void
    {
        @unlink(self::archivoCuota($cubo));
    }

    /**
     * Consulta y registra en un solo paso. Para endpoints donde toda petición
     * consume cuota, no solo las fallidas.
     *
     * @return bool true si la petición cabe dentro de la cuota.
     */
    public static function dentroDeCuota(string $cubo, int $maximo, int $ventanaSegundos): bool
    {
        if (self::excedeCuota($cubo, $maximo, $ventanaSegundos)) {
            return false;
        }

        self::registrarIntento($cubo, $ventanaSegundos);

        return true;
    }
}

/** Atajo de escapado para las plantillas. */
function e(mixed $valor): string
{
    return Http::esc($valor);
}
