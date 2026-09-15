<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$vista = ResultsPage::preparar();

$titulo    = 'Resultados de Lotería RD — Hoy en tiempo real';
$tema      = 'tema-resultados';
$conIconos = true;
require __DIR__ . '/app/views/cabecera.php';
?>
<div class="mx-auto max-w-6xl p-6">

    <header class="mb-8 flex flex-col items-center justify-between gap-4 md:flex-row">
        <div class="flex items-center gap-4">
            <img src="assets/img/ai_avatar.jpg" alt=""
                 width="64" height="64" loading="lazy"
                 class="h-16 w-16 rounded-full border-2 border-neon-teal object-cover opacity-80 shadow-[0_0_15px_rgba(45,212,191,0.3)]">
            <div>
                <h1 class="bg-gradient-to-r from-neon-teal to-neon-purple bg-clip-text text-3xl font-extrabold text-transparent drop-shadow-[0_0_10px_rgba(45,212,191,0.3)]">
                    Lotería RD
                </h1>
                <p class="text-sm text-gray-400">
                    <?= e($vista['totalLoterias']) ?> sorteos monitoreados
                </p>
            </div>
        </div>

        <form action="resultados.php" method="GET"
              class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-800/50 p-2 backdrop-blur-md">
            <label for="fecha" class="text-sm font-medium text-gray-300">
                <i class="fas fa-calendar-alt mr-1" aria-hidden="true"></i> Fecha
            </label>
            <input type="date" id="fecha" name="fecha"
                   max="<?= e($vista['fechaHoy']) ?>"
                   value="<?= e($vista['fechaSeleccionada']) ?>"
                   class="cursor-pointer border-none bg-transparent text-sm font-bold text-white outline-none">
            <noscript><button type="submit" class="text-sm text-neon-teal">Ver</button></noscript>
        </form>
    </header>

    <?php if ($vista['modoRespaldo']): ?>
        <p class="mb-6 rounded-lg border border-amber-700/40 bg-amber-900/30 p-3 text-center text-sm text-amber-200">
            Mostrando el último respaldo disponible: la base de datos no responde en este momento.
        </p>
    <?php endif; ?>

    <?php if ($vista['tarjetas'] === []): ?>
        <div class="glass-card mx-auto max-w-2xl rounded-2xl p-12 text-center shadow-2xl">
            <i class="fas fa-robot mb-6 block text-5xl text-gray-600" aria-hidden="true"></i>
            <h2 class="mb-2 text-xl font-bold text-gray-300">Sin resultados para esta fecha</h2>
            <p class="text-gray-500">Prueba con otro día o vuelve en unos minutos.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($vista['tarjetas'] as $sorteo): ?>
                <?php require __DIR__ . '/components/card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
  // Enviar el formulario al elegir fecha. Sin JS, el <noscript> deja un botón.
  document.getElementById('fecha')?.addEventListener('change', function () {
    this.form.submit();
  });
</script>
<?php require __DIR__ . '/app/views/pie.php'; ?>
