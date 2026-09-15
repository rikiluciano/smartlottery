<?php

function limpiarString($cadena) {
    $no_permitidas = array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú");
    $permitidas = array ("a","e","i","o","u","A","E","I","O","U");
    return str_replace($no_permitidas, $permitidas, $cadena);
}

function getOrdenProgramado($nombre) {
    $nombre = strtolower(trim(limpiarString($nombre)));
    $horarios = [
        'anguilla 8am' => 8 * 60,
        'anguilla 9am' => 9 * 60,
        'anguilla 10am' => 10 * 60,
        'anguilla 11am' => 11 * 60,
        'anguilla 12pm' => 12 * 60,
        'anguilla 1pm' => 13 * 60,
        'anguilla 2pm' => 14 * 60,
        'anguilla 3pm' => 15 * 60,
        'anguilla 4pm' => 16 * 60,
        'anguilla 5pm' => 17 * 60,
        'anguilla 6pm' => 18 * 60,
        'anguilla 7pm' => 19 * 60,
        'anguilla 8pm' => 20 * 60,
        'anguilla 9pm' => 21 * 60,
        'anguilla 10pm' => 22 * 60,
        
        'la primera noche' => 20 * 60,
        'la primera' => 12 * 60,
        
        'lotedom' => 13 * 60 + 55, // 1:55 PM
        
        'georgia noche' => 20 * 60, // 8:00 PM
        'georgia tarde' => 15 * 60, // 3:00 PM
        'georgia dia' => 12 * 60 + 20, // 12:20 PM
        
        'florida noche' => 21 * 60 + 45, // 9:45 PM
        'florida tarde' => 13 * 60 + 30, // 1:30 PM
        
        'new york noche' => 22 * 60 + 30, // 10:30 PM
        'new york tarde' => 14 * 60 + 30, // 2:30 PM
        
        'new jersey noche' => 22 * 60 + 55, // 10:55 PM
        'new jersey tarde' => 12 * 60 + 55, // 12:55 PM
        
        'la suerte dominicana' => 12 * 60 + 30,
        'la suerte 6pm' => 18 * 60,
        'la suerte' => 12 * 60 + 30,
        
        'king lottery noche' => 19 * 60 + 30,
        'king lottery dia' => 12 * 60 + 30,
        
        'real noche' => 20 * 60,
        'loto real' => 13 * 60,
        'real' => 13 * 60,
        
        'loteka' => 19 * 60 + 55, // 7:55 PM
        
        'nacional noche' => 21 * 60, // 9:00 PM
        'nacional gana más' => 15 * 60, // 3:00 PM
        'nacional gana mas' => 15 * 60, // 3:00 PM
        'gana mas' => 15 * 60, // 3:00 PM
        'lotería nacional' => 21 * 60, // 9:00 PM
        
        'haiti bolet 9:30 am' => 9 * 60 + 30,
        'haiti bolet 10:30 am' => 10 * 60 + 30,
        'haiti bolet 11:30 am' => 11 * 60 + 30,
        'haiti bolet 5:30 pm' => 17 * 60 + 30,
        'haiti bolet 6:30 pm' => 18 * 60 + 30,
        'haiti bolet 7:30 pm' => 19 * 60 + 30,
        
        'leidsa' => 20 * 60 + 55 // 8:55 PM
    ];
    
    foreach ($horarios as $key => $minutos) {
        if (strpos($nombre, $key) !== false || $nombre === $key) {
            return $minutos;
        }
    }
    
    // Fallback if not found
    return 9999;
}
