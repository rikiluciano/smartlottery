<?php
declare(strict_types=1);

/**
 * Autenticación del panel de administración.
 *
 * HTTP Basic sobre HTTPS con la contraseña guardada como hash bcrypt en
 * secrets.php. Es deliberadamente simple: hay un solo administrador y el
 * hosting compartido no da garantías sobre el almacenamiento de sesiones.
 *
 * Generar el hash con:
 *   php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT), PHP_EOL;"
 */
final class Auth
{
    private const INTENTOS_MAXIMOS = 5;
    private const VENTANA_SEGUNDOS = 900; // 15 minutos
    private const CUBO = 'admin_fallos';

    public static function exigirAdministrador(): void
    {
        $usuarioEsperado = (string) Config::get('admin_user', '');
        $hashEsperado    = (string) Config::get('admin_password_hash', '');

        // Sin credenciales configuradas el panel queda cerrado, no abierto.
        if ($usuarioEsperado === '' || $hashEsperado === '') {
            error_log('Auth: admin_user / admin_password_hash sin configurar.');
            http_response_code(503);
            exit('El panel no está configurado.');
        }

        // Solo se cuentan los intentos FALLIDOS. Contar también los correctos
        // bloquearía al administrador por el mero hecho de recargar el panel.
        if (Http::excedeCuota(self::CUBO, self::INTENTOS_MAXIMOS, self::VENTANA_SEGUNDOS)) {
            http_response_code(429);
            header('Retry-After: ' . self::VENTANA_SEGUNDOS);
            exit('Demasiados intentos fallidos. Vuelve a probar en 15 minutos.');
        }

        $usuario = (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
        $clave   = (string) ($_SERVER['PHP_AUTH_PW']   ?? '');

        // Se evalúan ambas condiciones siempre, sin cortocircuito: cortar antes
        // haría que el tiempo de respuesta revelase cuál de las dos falló.
        $usuarioOk = hash_equals($usuarioEsperado, $usuario);
        $claveOk   = $clave !== '' && password_verify($clave, $hashEsperado);

        if ($usuarioOk && $claveOk) {
            Http::limpiarCuota(self::CUBO);
            return;
        }

        Http::registrarIntento(self::CUBO, self::VENTANA_SEGUNDOS);
        error_log('Auth: intento fallido desde ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));

        header('WWW-Authenticate: Basic realm="Panel de administración", charset="UTF-8"');
        http_response_code(401);
        exit('Acceso restringido.');
    }
}
