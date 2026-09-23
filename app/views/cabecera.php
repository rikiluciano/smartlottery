<?php
/**
 * Cabecera HTML común.
 *
 * Variables esperadas:
 *   string $titulo       Título de la pestaña.
 *   string $tema         'tema-resultados' | 'tema-calculadora' | ''
 *   bool   $conIconos    Cargar Font Awesome (solo donde se usa).
 *   string $claseBody    Clases extra para <body>.
 *   string $estilosPagina CSS propio de la página, si lo necesita.
 *
 * @var string $titulo
 * @var string $tema
 * @var bool   $conIconos
 * @var string $claseBody
 * @var string $estilosPagina
 */
declare(strict_types=1);

$titulo    = $titulo    ?? 'Lotería RD — Resultados y Análisis';
$tema      = $tema      ?? 'tema-resultados';
$conIconos     = $conIconos     ?? false;
$claseBody     = $claseBody     ?? '';
$estilosPagina = $estilosPagina ?? '';

Http::cabecerasDeSeguridad();
?>
<!DOCTYPE html>
<html lang="es" class="<?= e($tema) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo) ?></title>
<meta name="color-scheme" content="dark">
<meta name="description" content="Resultados de las loterías de República Dominicana y análisis estadístico del historial de sorteos.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap">

<?php /* Hoja compilada con `npm run build:css`; ya no se compila en el navegador. */ ?>
<link rel="stylesheet" href="assets/css/app.css?v=<?= e(ASSET_VERSION) ?>">
<?php if ($conIconos): ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
      integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
      crossorigin="anonymous" referrerpolicy="no-referrer">
<?php endif; ?>
<?php if ($estilosPagina !== ''): ?>
<style><?= $estilosPagina ?></style>
<?php endif; ?>
</head>
<body class="min-h-screen relative font-sans fondo-app <?= e($claseBody) ?>">
