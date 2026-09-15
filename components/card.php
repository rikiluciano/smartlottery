<?php
$cardClass = $sorteo['es_antiguo'] ? 'glass-card-old' : 'glass-card';
?>
<div class="<?php echo $cardClass; ?> rounded-xl p-5 shadow-lg relative overflow-hidden group transition-all duration-300">
    
    <?php if (!$sorteo['es_antiguo']): ?>
    <div class="absolute top-0 right-0 w-20 h-20 bg-neon-teal/5 rounded-full blur-xl -mr-10 -mt-10 pointer-events-none group-hover:bg-neon-teal/10 transition-all duration-500"></div>
    <?php endif; ?>
    
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="text-sm font-bold text-gray-100 mb-1 leading-tight"><?php echo htmlspecialchars($sorteo['nombre']); ?></h3>
            <?php 
                $textoFecha = '';
                if ($sorteo['fecha_real'] === date('Y-m-d', strtotime('-1 day'))) {
                    $textoFecha = 'Ayer';
                } elseif ($sorteo['fecha_real'] === date('Y-m-d', strtotime('-2 days'))) {
                    $textoFecha = 'Antes de ayer';
                } else {
                    $textoFecha = date('d/m/Y', strtotime($sorteo['fecha_real']));
                }
            ?>
            <div class="flex items-center">
                <?php if ($sorteo['fecha_real'] !== $fechaSeleccionada): ?>
                    <span class="text-[10px] bg-red-900/40 text-red-300 px-2 py-0.5 rounded-full border border-red-800/30 font-medium">
                        <?php echo $textoFecha; ?>
                    </span>
                <?php else: ?>
                    <span class="text-[10px] bg-emerald-900/40 text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-800/30 flex items-center gap-1 font-medium">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Hoy
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($sorteo['logo'])): ?>
            <img src="<?php echo htmlspecialchars($sorteo['logo']); ?>" alt="<?php echo htmlspecialchars($familia); ?>" class="logo-img opacity-90">
        <?php endif; ?>
    </div>
    
    <div class="flex justify-center space-x-3 mt-4">
        <div class="flex flex-col items-center">
            <div class="ball ball-1"><?php echo htmlspecialchars($sorteo['primera']); ?></div>
            <span class="text-[10px] text-gray-400 mt-1 uppercase font-semibold">1ra</span>
        </div>
        <div class="flex flex-col items-center">
            <div class="ball ball-2"><?php echo htmlspecialchars($sorteo['segunda']); ?></div>
            <span class="text-[10px] text-gray-400 mt-1 uppercase font-semibold">2da</span>
        </div>
        <div class="flex flex-col items-center">
            <div class="ball ball-3"><?php echo htmlspecialchars($sorteo['tercera']); ?></div>
            <span class="text-[10px] text-gray-400 mt-1 uppercase font-semibold">3ra</span>
        </div>
    </div>
</div>
