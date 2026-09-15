<?php
/**
 * Tarjeta de un sorteo.
 *
 * @var array{
 *   nombre:string, primera:string, segunda:string, tercera:string,
 *   fecha_real:string, es_antiguo:bool, logo:string, familia:string
 * } $sorteo
 */
declare(strict_types=1);

$etiquetaFecha = match ($sorteo['fecha_real']) {
    date('Y-m-d', strtotime('-1 day'))  => 'Ayer',
    date('Y-m-d', strtotime('-2 days')) => 'Antes de ayer',
    default => date('d/m/Y', (int) strtotime($sorteo['fecha_real'])),
};
?>
<article class="<?= $sorteo['es_antiguo'] ? 'glass-card-old' : 'glass-card' ?> group relative overflow-hidden rounded-xl p-5 shadow-lg transition-all duration-300">

    <?php if (!$sorteo['es_antiguo']): ?>
        <div class="pointer-events-none absolute right-0 top-0 -mr-10 -mt-10 h-20 w-20 rounded-full bg-neon-teal/5 blur-xl transition-all duration-500 group-hover:bg-neon-teal/10"></div>
    <?php endif; ?>

    <div class="mb-4 flex items-start justify-between gap-2">
        <div>
            <h3 class="mb-1 text-sm font-bold leading-tight text-gray-100"><?= e($sorteo['nombre']) ?></h3>

            <?php if ($sorteo['es_antiguo']): ?>
                <span class="rounded-full border border-red-800/30 bg-red-900/40 px-2 py-0.5 text-[10px] font-medium text-red-300">
                    <?= e($etiquetaFecha) ?>
                </span>
            <?php else: ?>
                <span class="flex items-center gap-1 rounded-full border border-emerald-800/30 bg-emerald-900/40 px-2 py-0.5 text-[10px] font-medium text-emerald-300">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Hoy
                </span>
            <?php endif; ?>
        </div>

        <?php if ($sorteo['logo'] !== ''): ?>
            <img src="<?= e($sorteo['logo']) ?>" alt="<?= e($sorteo['familia']) ?>"
                 loading="lazy" class="logo-img opacity-90">
        <?php endif; ?>
    </div>

    <div class="mt-4 flex justify-center gap-3">
        <?php foreach ([['primera', '1ra'], ['segunda', '2da'], ['tercera', '3ra']] as $i => [$clave, $etiqueta]): ?>
            <div class="flex flex-col items-center">
                <div class="ball ball-<?= $i + 1 ?>"><?= e($sorteo[$clave]) ?></div>
                <span class="mt-1 text-[10px] font-semibold uppercase text-gray-400"><?= e($etiqueta) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</article>
