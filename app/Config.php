<?php
declare(strict_types=1);

/**
 * Acceso a configuración y credenciales.
 *
 * Resolución, en orden:
 *   1. Variable de entorno en MAYÚSCULAS (DB_HOST, INGEST_TOKEN, …)
 *   2. Clave en secrets.php (fuera de git — ver secrets.example.php)
 *   3. Valor por defecto
 *
 * En hosting compartido no siempre se pueden definir variables de entorno,
 * de ahí el respaldo en archivo. Ninguna de las dos fuentes se versiona.
 */
final class Config
{
    /** @var array<string,mixed>|null */
    private static ?array $secretos = null;

    public static function get(string $clave, mixed $porDefecto = null): mixed
    {
        $env = getenv(strtoupper($clave));
        if ($env !== false && $env !== '') {
            return $env;
        }

        if (self::$secretos === null) {
            $ruta = dirname(__DIR__) . '/secrets.php';
            self::$secretos = is_readable($ruta) ? (array) require $ruta : [];
        }

        return self::$secretos[$clave] ?? $porDefecto;
    }

    /** Igual que get(), pero falla ruidosamente si el valor falta. */
    public static function requerido(string $clave): string
    {
        $valor = self::get($clave);
        if (!is_string($valor) || $valor === '') {
            throw new RuntimeException("Falta la configuración obligatoria: {$clave}");
        }
        return $valor;
    }

    public static function esProduccion(): bool
    {
        return (string) self::get('app_env', 'production') === 'production';
    }

    /** Solo para tests: descarta el caché de secrets.php. */
    public static function reiniciar(): void
    {
        self::$secretos = null;
    }
}
