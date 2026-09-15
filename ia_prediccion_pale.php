<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pred = [
    'titulo'            => 'Palés pendientes — Análisis por descarte histórico',
    'encabezado'        => 'Predicción de Palés',
    'etiquetaResultado' => 'Único Palé Pendiente',
    'endpoint'          => 'api_prediccion.php',
];

require __DIR__ . '/app/views/pagina_prediccion.php';
