<?php
declare(strict_types=1);

/**
 * Micro-harness de pruebas: sin Composer ni PHPUnit.
 *
 * El proyecto se despliega en hosting compartido sin acceso a línea de
 * comandos, así que la suite tiene que correr con el PHP pelado que haya.
 */
final class T
{
    public static int $ok = 0;
    public static int $fallos = 0;
    /** @var string[] */
    public static array $detalles = [];
    private static string $grupo = '';

    public static function grupo(string $nombre): void
    {
        self::$grupo = $nombre;
        echo "\n\033[1m{$nombre}\033[0m\n";
    }

    public static function es(mixed $esperado, mixed $real, string $caso): void
    {
        if ($esperado === $real) {
            self::$ok++;
            echo "  \033[32m✓\033[0m {$caso}\n";
            return;
        }

        self::$fallos++;
        $e = var_export($esperado, true);
        $r = var_export($real, true);
        echo "  \033[31m✗\033[0m {$caso}\n      esperado: {$e}\n      obtenido: {$r}\n";
        self::$detalles[] = self::$grupo . ' :: ' . $caso;
    }

    public static function cierto(bool $cond, string $caso): void
    {
        self::es(true, $cond, $caso);
    }

    public static function resumen(): int
    {
        $total = self::$ok + self::$fallos;
        echo "\n" . str_repeat('─', 52) . "\n";

        if (self::$fallos === 0) {
            echo "\033[32m{$total} pruebas, todas pasan.\033[0m\n";
            return 0;
        }

        echo "\033[31m" . self::$fallos . " fallo(s) de {$total} pruebas.\033[0m\n";
        foreach (self::$detalles as $d) {
            echo "  · {$d}\n";
        }
        return 1;
    }
}
