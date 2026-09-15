<?php
require_once 'config_db.php';

// Autoprocesador de resultados pendientes (Bypass Firewall)
if (file_exists('resultados_pendientes.json')) {
    try {
        $json = file_get_contents('resultados_pendientes.json');
        $data = json_decode($json, true);
        if ($data && isset($data['token']) && hash_equals((string) cfg('ingest_token'), (string) $data['token'])) {
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
        unlink('resultados_pendientes.json');
    } catch (Exception $e) {
        error_log("Error sincronizando DB: " . $e->getMessage());
    }
}

// Configurar la fecha de hoy con la zona horaria correcta
date_default_timezone_set('America/Santo_Domingo');
$fechaHoy = date('Y-m-d');
$mesActual = date('Y-m');

// Obtener fecha seleccionada
$fechaSeleccionada = $fechaHoy;
if (isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha'])) {
    // Validar que no sea mayor a hoy
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
    // Si no tiene hora explícita, aproximamos por "Día", "Tarde", "Noche"
    if (stripos($nombre, 'día') !== false || stripos($nombre, 'dia') !== false) return 12 * 60;
    if (stripos($nombre, 'tarde') !== false) return 15 * 60;
    if (stripos($nombre, 'noche') !== false) return 19 * 60;
    
    return 9999; // Para que queden al final si no tienen hora
}

$resultadosFinales = [];
$totalLoterias = 0;

try {
    // Obtenemos TODOS los sorteos (para poder buscar el más reciente si no hay de hoy)
    // Utilizamos GROUP BY en PHP para quedarnos con el último por nombre
    $stmt = $pdo->prepare("SELECT fecha, nombre_loteria, primera, segunda, tercera FROM sorteos WHERE fecha <= :fecha ORDER BY fecha DESC");
    $stmt->execute([':fecha' => $fechaSeleccionada]);
    $todos = $stmt->fetchAll();
    
    $vistos = [];
    foreach ($todos as $row) {
        $nombre = $row['nombre_loteria'];
        if (!isset($vistos[$nombre])) {
            $vistos[$nombre] = true;
            $totalLoterias++;
            
            $datos = obtenerDatosFamilia($nombre);
            $fam = $datos['familia'];
            $logo = $datos['logo'];
            
            // Agrupamos
            if (!isset($resultadosFinales[$fam])) {
                $resultadosFinales[$fam] = [];
            }
            
            // Verificamos si es un resultado antiguo
            $esAntiguo = ($row['fecha'] !== $fechaSeleccionada);
            
            $resultadosFinales[$fam][] = [
                'nombre' => $nombre,
                'primera' => $row['primera'],
                'segunda' => $row['segunda'],
                'tercera' => $row['tercera'],
                'fecha_real' => $row['fecha'],
                'es_antiguo' => $esAntiguo,
                'logo' => $logo,
                'minutos' => extraerHora($nombre)
            ];
        }
    }
    
    // Ordenar las loterías dentro de cada familia cronológicamente
    foreach ($resultadosFinales as $fam => &$sorteos) {
        usort($sorteos, function($a, $b) {
            return $a['minutos'] - $b['minutos'];
        });
    }
    // Ordenar familias alfabéticamente
    ksort($resultadosFinales);

} catch (Exception $e) {
    // Si falla
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Lotería - Inteligencia Artificial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0f172a',
                        'neon-teal': '#2dd4bf',
                        'neon-purple': '#c084fc',
                        'card-old': '#1e293b' // Un color ligeramente diferente para los antiguos
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: white; }
        .glass-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-card-old {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.02);
            opacity: 0.85;
        }
        .ball {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.15rem;
            color: #0f172a;
            box-shadow: inset 0 -2px 4px rgba(0,0,0,0.1), 0 3px 5px rgba(0,0,0,0.3);
        }
        .ball-1 { background: #4ade80; } /* Verde suave */
        .ball-2 { background: #facc15; } /* Amarillo suave */
        .ball-3 { background: #f87171; } /* Rojo suave */
        
        .logo-img {
            max-height: 28px;
            max-width: 90px;
            object-fit: contain;
            filter: drop-shadow(0 0 2px rgba(255,255,255,0.2));
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
    </style>
</head>
<body class="min-h-screen relative font-sans">
    
    <!-- Background styling -->
    <div class="fixed inset-0 pointer-events-none z-[-1]" style="background-image: url('finance_ai_bg_1788497081588.jpg'); background-size: cover; background-position: center; opacity: 0.08; mix-blend-mode: screen;"></div>

    <div class="max-w-6xl mx-auto p-6">
        
        <header class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <img src="ai_bot_avatar_1788497063695.jpg" alt="AI Avatar" class="w-16 h-16 rounded-full border-2 border-neon-teal shadow-[0_0_15px_rgba(45,212,191,0.3)] object-cover opacity-80">
                <div>
                    <h1 class="text-3xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-neon-teal to-neon-purple drop-shadow-[0_0_10px_rgba(45,212,191,0.3)]">
                        Lotería IA
                    </h1>
                    <p class="text-gray-400 text-sm">Monitoreando <?php echo $totalLoterias; ?> sorteos en tiempo real</p>
                </div>
            </div>
            
            <form action="" method="GET" class="flex items-center bg-gray-800/50 rounded-lg p-2 border border-gray-700 backdrop-blur-md cursor-pointer" onclick="document.getElementById('fecha').showPicker()">
                <label for="fecha" class="text-gray-300 text-sm mr-2 font-medium cursor-pointer"><i class="fas fa-calendar-alt mr-1"></i> Fecha:</label>
                <input type="date" id="fecha" name="fecha" 
                       max="<?php echo $fechaHoy; ?>" 
                       value="<?php echo $fechaSeleccionada; ?>"
                       onchange="this.form.submit()"
                       class="bg-transparent text-white border-none outline-none text-sm cursor-pointer font-bold pointer-events-none">
            </form>
        </header>

        <?php if (empty($resultadosFinales)): ?>
            <div class="glass-card rounded-2xl p-12 text-center shadow-2xl max-w-2xl mx-auto">
                <i class="fas fa-robot text-5xl text-gray-600 mb-6 block"></i>
                <h3 class="text-xl font-bold text-gray-300 mb-2">Iniciando red neuronal...</h3>
                <p class="text-gray-500">Recopilando datos históricos de las loterías.</p>
            </div>
        <?php else: ?>
            
            <?php foreach ($resultadosFinales as $familia => $sorteos): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-300 border-b border-gray-700/50 pb-2 mb-4 flex items-center">
                        <i class="fas fa-star text-neon-purple/70 mr-2 text-sm"></i>
                        <?php echo htmlspecialchars($familia); ?>
                    </h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach ($sorteos as $sorteo): ?>
                            <?php $cardClass = $sorteo['es_antiguo'] ? 'glass-card-old' : 'glass-card'; ?>
                            
                            <div class="<?php echo $cardClass; ?> rounded-xl p-5 shadow-lg relative overflow-hidden group transition-all duration-300">
                                
                                <?php if (!$sorteo['es_antiguo']): ?>
                                <div class="absolute top-0 right-0 w-20 h-20 bg-neon-teal/5 rounded-full blur-xl -mr-10 -mt-10 pointer-events-none group-hover:bg-neon-teal/10 transition-all duration-500"></div>
                                <?php endif; ?>
                                
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-100 mb-1 leading-tight"><?php echo htmlspecialchars($sorteo['nombre']); ?></h3>
                                        <?php if ($sorteo['fecha_real'] !== $fechaSeleccionada): ?>
                                            <span class="text-[10px] bg-red-900/40 text-red-300 px-2 py-0.5 rounded-full border border-red-800/30">
                                                Esperando... (Anterior: <?php echo date('d/m', strtotime($sorteo['fecha_real'])); ?>)
                                            </span>
                                        <?php elseif ($sorteo['fecha_real'] === $fechaHoy): ?>
                                            <span class="text-[10px] bg-green-900/40 text-green-300 px-2 py-0.5 rounded-full border border-green-800/30">
                                                <i class="fas fa-check-circle mr-1"></i> Hoy
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[10px] bg-blue-900/40 text-blue-300 px-2 py-0.5 rounded-full border border-blue-800/30">
                                                <i class="fas fa-calendar-day mr-1"></i> <?php echo date('d/m/Y', strtotime($sorteo['fecha_real'])); ?>
                                            </span>
                                        <?php endif; ?>
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
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php endif; ?>
        
        <div class="mt-8 text-center pb-8 opacity-70 hover:opacity-100 transition-opacity">
            <a href="index.html" class="text-sm text-gray-500 hover:text-neon-teal transition-colors"><i class="fas fa-calculator mr-1"></i> Ir a Calculadora Estratégica</a>
        </div>
    </div>
</body>
</html>
