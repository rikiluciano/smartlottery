<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pred = [
    'titulo'            => 'Súper Palés pendientes — Análisis por descarte histórico',
    'encabezado'        => 'Predicción de Súper Palés',
    'etiquetaResultado' => 'Único Súper Palé Pendiente',
    'endpoint'          => 'api_super_prediccion.php',
];

require __DIR__ . '/app/views/pagina_prediccion.php';
