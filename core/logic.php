<?php
require_once 'config_db.php';

date_default_timezone_set('America/Santo_Domingo');
$fechaHoy = date('Y-m-d');
$mesActual = date('Y-m');

file_put_contents('debug_early.txt', 'Página cargada a las: ' . date('H:i:s'));

// Mantenimiento automático para arreglar resultados corrompidos del día anterior que fueron subidos después de las 12 AM
// Ejecutar solo si son antes de las 8 AM (ya que los primeros sorteos salen a las 8 AM)
if (date('H') < 8) {
    try {
        $pdo->exec("DELETE FROM sorteos WHERE fecha = '" . $fechaHoy . "'");
    } catch (Exception $e) {
        // Ignorar
    }
}

// Backup automático de la base de datos (una vez al día)
$backup_file = 'db_backup.json';
if (!file_exists($backup_file) || (time() - filemtime($backup_file)) > 86400) {
    try {
        $stmt = $pdo->query("SELECT * FROM sorteos");
        $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents($backup_file, json_encode($all_data));
    } catch (Exception $e) {
        // Ignorar error de backup
    }
}

// Autoprocesador de resultados pendientes y del histórico (Bypass Firewall)
$archivos_json = [];
if (file_exists('resultados_pendientes.json')) {
    $archivos_json[] = 'resultados_pendientes.json';
}
$history_files = glob('history_*.json');
if ($history_files) {
    $archivos_json = array_merge($archivos_json, $history_files);
}
// THROTTLE: Procesar máximo 5 archivos por carga para evitar exceder max_queries_per_hour o timeout de PHP.
if (is_array($archivos_json)) {
    $archivos_json = array_slice($archivos_json, 0, 5);
} else {
    $archivos_json = [];
}

foreach ($archivos_json as $archivo) {
    try {
        $json = file_get_contents($archivo);
        $data = json_decode($json, true);
        if ($data && isset($data['token']) && $data['token'] === 'Rlabs_Scraper_V2_2026') {
            
            // AUTO TRUNCATE TRIGGER - WIPE DB si se detecta un payload de V2 por primera vez
            if (!file_exists('db_wiped.flag')) {
                $pdo->exec("TRUNCATE TABLE sorteos");
                file_put_contents('db_wiped.flag', 'wiped');
            }
            
            $resultados = $data['resultados'];
            $stmt = $pdo->prepare("
                INSERT INTO sorteos (fecha, nombre_loteria, primera, segunda, tercera) 
                VALUES (:fecha, :nombre, :primera, :segunda, :tercera)
                ON DUPLICATE KEY UPDATE 
                    primera = VALUES(primera),
                    segunda = VALUES(segunda),
                    tercera = VALUES(tercera)
            ");
            foreach ($resultados as $sorteo) {
                if (!empty($sorteo['fecha']) && !empty($sorteo['nombre']) && isset($sorteo['numeros'])) {
                    $stmt->execute([
                        ':fecha' => $sorteo['fecha'],
                        ':nombre' => $sorteo['nombre'],
                        ':primera' => $sorteo['numeros'][0] ?? '00',
                        ':segunda' => $sorteo['numeros'][1] ?? '00',
                        ':tercera' => $sorteo['numeros'][2] ?? '00'
                    ]);
                }
            }
        }
        unlink($archivo);
    } catch (PDOException $e) {
        error_log("Error DB sincronizando $archivo: " . $e->getMessage());
        break; // Detener proceso si hay error de DB (ej. max_queries_per_hour)
    } catch (Exception $e) {
        error_log("Error general sincronizando $archivo: " . $e->getMessage());
    }
}

// Obtener fecha seleccionada
$fechaSeleccionada = $fechaHoy;
if (isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha'])) {
    $fechaReq = $_GET['fecha'];
    if ($fechaReq <= $fechaHoy) {
        $fechaSeleccionada = $fechaReq;
    }
}

// Función para obtener la familia y el logo
function obtenerDatosFamilia($nombre) {
    $n = strtolower($nombre);
    if (strpos($n, 'anguilla') !== false || strpos($n, 'anguila') !== false) return ['familia' => 'Anguilla', 'logo' => 'img/logos/anguilla.svg'];
    if (strpos($n, 'haiti') !== false || strpos($n, 'bolet') !== false) return ['familia' => 'Haiti Bolet', 'logo' => 'img/logos/haiti-bolet.svg'];
    if (strpos($n, 'florida') !== false) return ['familia' => 'Florida', 'logo' => 'img/logos/florida.svg'];
    if (strpos($n, 'new york') !== false || strpos($n, 'nueva york') !== false) return ['familia' => 'New York', 'logo' => 'img/logos/new-york.svg'];
    if (strpos($n, 'new jersey') !== false) return ['familia' => 'New Jersey', 'logo' => 'img/logos/new-jersey.svg'];
    if (strpos($n, 'georgia') !== false) return ['familia' => 'Georgia', 'logo' => 'img/logos/georgia.svg'];
    if (strpos($n, 'la primera') !== false) return ['familia' => 'La Primera', 'logo' => 'img/logos/la-primera.svg'];
    if (strpos($n, 'la suerte') !== false) return ['familia' => 'La Suerte', 'logo' => 'img/logos/la-suerte.svg'];
    if (strpos($n, 'lotedom') !== false) return ['familia' => 'LoteDom', 'logo' => 'img/logos/lotedom.svg'];
    if (strpos($n, 'loteka') !== false) return ['familia' => 'Loteka', 'logo' => 'img/logos/loteka.svg'];
    if (strpos($n, 'leidsa') !== false) return ['familia' => 'Leidsa', 'logo' => 'img/logos/leidsa.svg'];
    if (strpos($n, 'real') !== false) return ['familia' => 'Real', 'logo' => 'img/logos/real.svg'];
    if (strpos($n, 'nacional') !== false || strpos($n, 'gana m') !== false) return ['familia' => 'Nacional', 'logo' => 'img/logos/nacional.svg'];
    if (strpos($n, 'king lottery') !== false) return ['familia' => 'King Lottery', 'logo' => 'img/logos/king-lottery.svg'];
    return ['familia' => 'Otras', 'logo' => ''];
}

// Función para extraer hora y ordenar
function extraerHora($nombre) {
    if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(AM|PM)/i', $nombre, $matches)) {
        $hora = (int)$matches[1];
        $min = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : 0;
        $ampm = strtoupper($matches[3]);
        
        if ($hora == 12 && $ampm == 'AM') $hora = 0;
        else if ($hora < 12 && $ampm == 'PM') $hora += 12;
        
        return $hora * 60 + $min; // minutos desde medianoche
    }
    if (stripos($nombre, 'día') !== false || stripos($nombre, 'dia') !== false) return 12 * 60;
    if (stripos($nombre, 'tarde') !== false) return 15 * 60;
    if (stripos($nombre, 'noche') !== false) return 19 * 60;
    return 9999;
}

$resultadosFinales = [];
$totalLoterias = 0;

try {
    require_once 'mapa_horarios.php';

    $stmt = $pdo->prepare("SELECT fecha, nombre_loteria, primera, segunda, tercera FROM sorteos WHERE fecha <= :fecha ORDER BY fecha DESC");
    $stmt->execute([':fecha' => $fechaSeleccionada]);
    $todos = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Error DB (Throwable): ' . $e->getMessage());
    $todos = [];
    // FALLBACK A JSON SI LA DB ESTA CAIDA O BLOQUEADA
    if (file_exists('db_backup.json')) {
        $backup_data = json_decode(file_get_contents('db_backup.json'), true);
        if ($backup_data) {
            foreach ($backup_data as $row) {
                if ($row['fecha'] <= $fechaSeleccionada) {
                    $todos[] = $row;
                }
            }
        }
    }
    // MERGE con resultados_pendientes.json para tener resultados de HOY incluso si la DB falla
    if (file_exists('resultados_pendientes.json')) {
        $pend_data = json_decode(file_get_contents('resultados_pendientes.json'), true);
        if ($pend_data && isset($pend_data['resultados'])) {
            foreach ($pend_data['resultados'] as $r) {
                if ($r['fecha'] <= $fechaSeleccionada) {
                    // Reemplazar si existe, o agregar
                    $found = false;
                    foreach ($todos as &$t) {
                        if ($t['nombre_loteria'] === $r['nombre'] && $t['fecha'] === $r['fecha']) {
                            $t['primera'] = $r['primera'];
                            $t['segunda'] = $r['segunda'];
                            $t['tercera'] = $r['tercera'];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $todos[] = [
                            'fecha' => $r['fecha'],
                            'nombre_loteria' => $r['nombre'],
                            'primera' => $r['primera'],
                            'segunda' => $r['segunda'],
                            'tercera' => $r['tercera']
                        ];
                    }
                }
            }
        }
    }
}

try {
    $vistos = [];
    foreach ($todos as $row) {
        $nombre = $row['nombre_loteria'];
        if (!isset($vistos[$nombre])) {
            $vistos[$nombre] = true;
            $totalLoterias++;
            
            $datos = obtenerDatosFamilia($nombre);
            $logo = $datos['logo'];
            
            $esAntiguo = ($row['fecha'] !== $fechaSeleccionada);
            
            $resultadosFinales[] = [
                'nombre' => $nombre,
                'primera' => $row['primera'],
                'segunda' => $row['segunda'],
                'tercera' => $row['tercera'],
                'fecha_real' => $row['fecha'],
                'es_antiguo' => $esAntiguo,
                'logo' => $logo,
                'minutos' => getOrdenProgramado($nombre)
            ];
        }
    }
    
    // ORDENAMIENTO EXACTO SOLICITADO POR EL USUARIO:
    usort($resultadosFinales, function($a, $b) use ($fechaSeleccionada) {
        $a_es_hoy = ($a['fecha_real'] === $fechaSeleccionada) ? 1 : 0;
        $b_es_hoy = ($b['fecha_real'] === $fechaSeleccionada) ? 1 : 0;
        
        // 1. Las tarjetas de HOY van siempre arriba de las de AYER
        if ($a_es_hoy !== $b_es_hoy) {
            return $b_es_hoy - $a_es_hoy;
        }
        
        // 2. Si AMBAS son de HOY:
        // "esta sube hasta arriba y se posiciona de primero"
        // Los resultados más recientes del día se posicionan hasta arriba (DESC)
        if ($a_es_hoy === 1) {
            if ($a['minutos'] !== $b['minutos']) {
                return $b['minutos'] - $a['minutos'];
            }
            return strcmp($a['nombre'], $b['nombre']);
        }
        
        // 3. Si AMBAS son ANTIGUAS (esperando salir hoy, o pasó la media noche):
        // "busca los horarios de cada una... y ordénalos en ese orden"
        // Orden natural programado de los sorteos durante el día (ASC)
        if ($a['minutos'] !== $b['minutos']) {
            return $a['minutos'] - $b['minutos'];
        }
        
        return strcmp($a['nombre'], $b['nombre']);
    });

} catch (Exception $e) {
    error_log("Error obteniendo resultados: " . $e->getMessage());
}
?>
