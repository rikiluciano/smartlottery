<?php
function extraerHora($nombre) {
    if (preg_match('/(\d+)(?:\s*:\s*\d+)?\s*(am|pm)/i', $nombre, $matches)) {
        $hora = (int)$matches[1];
        $ampm = strtolower($matches[2]);
        if ($ampm === 'pm' && $hora < 12) {
            $hora += 12;
        }
        if ($ampm === 'am' && $hora == 12) {
            $hora = 0;
        }
        return $hora * 60; // Convertir a minutos
    }
    
    // Asignar horas aproximadas a sorteos conocidos que no tienen hora en el nombre
    $nombre = strtolower($nombre);
    if (stripos($nombre, 'primera') !== false && stripos($nombre, 'noche') === false) return 12 * 60;
    if (stripos($nombre, 'real') !== false && stripos($nombre, 'noche') === false) return 13 * 60; // 1:00 PM
    if (stripos($nombre, 'suerte') !== false && stripos($nombre, '6pm') === false) return 12 * 60 + 30; // 12:30 PM
    if (stripos($nombre, 'lotedom') !== false) return 14 * 60; // 2:00 PM
    if (stripos($nombre, 'gana mas') !== false || stripos($nombre, 'gana más') !== false) return 15 * 60; // 3:00 PM
    if (stripos($nombre, 'loteka') !== false) return 19 * 60 + 55; // 7:55 PM
    if (stripos($nombre, 'leidsa') !== false) return 20 * 60 + 55; // 8:55 PM
    if (stripos($nombre, 'nacional') !== false && stripos($nombre, 'noche') !== false) return 21 * 60; // 9:00 PM
    if (stripos($nombre, 'primera') !== false && stripos($nombre, 'noche') !== false) return 20 * 60; // 8:00 PM
    
    if (stripos($nombre, 'día') !== false || stripos($nombre, 'dia') !== false) return 12 * 60;
    if (stripos($nombre, 'tarde') !== false) return 15 * 60;
    if (stripos($nombre, 'noche') !== false) return 19 * 60;
    return 9999;
}

$nombres = [
    "La Primera",
    "La Primera Noche",
    "Anguilla 10AM",
    "LoteDom",
    "Gana Más"
];

foreach ($nombres as $n) {
    echo "$n: " . extraerHora($n) . "\n";
}
