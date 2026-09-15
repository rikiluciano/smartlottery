<?php
// Cargar la lógica central (DB, procesamiento, variables globales)
require_once 'security.php';
require_once 'core/logic.php';
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
        // Blindaje Anti-Inspección
        document.addEventListener('contextmenu', event => event.preventDefault()); // Desactiva clic derecho
        document.onkeydown = function(e) {
            // Desactiva F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U, Ctrl+S
            if(e.keyCode == 123) return false;
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false;
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false;
            if(e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
            if(e.ctrlKey && e.keyCode == 'S'.charCodeAt(0)) return false;
        };
        // Obfuscación sencilla para detener a novatos
        setInterval(function() {
            var before = new Date().getTime();
            debugger;
            var after = new Date().getTime();
            if (after - before > 100) {
                document.body.innerHTML = "Acceso denegado.";
            }
        }, 100);
    </script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0f172a',
                        'neon-teal': '#2dd4bf',
                        'neon-purple': '#c084fc',
                        'card-old': '#1e293b'
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
        <!-- Contenedor único de resultados -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($resultadosFinales as $sorteo): ?>
                <?php 
                    $familia = $sorteo['nombre'];
                    include 'components/card.php'; 
                ?>
            <?php endforeach; ?>
        </div>    
        <?php endif; ?>
        
        <div class="mt-8 text-center pb-8 opacity-70 hover:opacity-100 transition-opacity">
            <a href="index.html" class="text-sm text-gray-500 hover:text-neon-teal transition-colors"><i class="fas fa-calculator mr-1"></i> Ir a Calculadora Estratégica</a>
        </div>
    </div>
</body>
</html>
