<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IA Predicción Palé - Lotería</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark-bg': '#0f172a',
                        'card-bg': '#1e293b',
                        'neon-teal': '#2dd4bf',
                        'neon-purple': '#a855f7',
                        'neon-pink': '#ec4899',
                    },
                    animation: {
                        'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'matrix': 'matrix-anim 2s linear infinite',
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: #f8fafc; overflow-x: hidden; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(45, 212, 191, 0.2); border-radius: 1rem; }
        .neon-border:focus-within { border-color: #2dd4bf; box-shadow: 0 0 10px rgba(45, 212, 191, 0.5); }
        .gradient-text { background-clip: text; -webkit-background-clip: text; color: transparent; }
        @keyframes scanline { 0% { transform: translateY(-100%); } 100% { transform: translateY(100vh); } }
        .scanline { position: fixed; top: 0; left: 0; width: 100%; height: 5px; background: rgba(45, 212, 191, 0.5); opacity: 0.5; animation: scanline 4s linear infinite; pointer-events: none; z-index: 50; }
    </style>
</head>
<body class="min-h-screen relative font-sans">
    <div class="scanline"></div>

    <div class="max-w-4xl mx-auto p-4 py-8 relative z-10">
        <!-- Header -->
        <header class="text-center pt-10 pb-6 relative z-10 px-4">
            <div class="inline-block p-3 rounded-full bg-slate-800 border border-neon-purple shadow-[0_0_15px_rgba(168,85,247,0.4)] mb-4">
                <i class="fas fa-brain text-4xl text-neon-purple animate-pulse"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-black bg-gradient-to-r from-neon-teal to-neon-purple text-transparent bg-clip-text drop-shadow-md tracking-wide">IA Predicción Palé</h1>
            <p class="text-gray-400 mt-2 font-light text-sm md:text-base border-b border-gray-700/50 inline-block pb-2">Búsqueda profunda por descarte histórico</p>
            
            <div class="mt-6 flex flex-col md:flex-row justify-center items-center gap-4">
                <a href="descargar_pales.php?tipo=posibles" class="bg-slate-800 hover:bg-slate-700 text-neon-teal border border-neon-teal/50 px-4 py-2 rounded-lg text-sm transition-all flex items-center shadow-[0_0_10px_rgba(45,212,191,0.2)]">
                    <i class="fas fa-download mr-2"></i> Descargar 4,950 Posibles
                </a>
                <a href="descargar_pales.php?tipo=encontrados" class="bg-slate-800 hover:bg-slate-700 text-neon-pink border border-neon-pink/50 px-4 py-2 rounded-lg text-sm transition-all flex items-center shadow-[0_0_10px_rgba(236,72,153,0.2)]">
                    <i class="fas fa-file-alt mr-2"></i> Descargar Reporte (4,949 hallados)
                </a>
            </div>
        </header>

        <!-- Filtros Panel -->
        <div class="glass-panel p-6 mb-8 shadow-[0_0_20px_rgba(0,0,0,0.5)] transition-all duration-300 hover:shadow-[0_0_25px_rgba(45,212,191,0.2)]">
            <h2 class="text-xl font-bold text-neon-teal mb-6 border-b border-gray-700 pb-2">
                <i class="fas fa-filter mr-2"></i> Configurar Análisis Neuronal
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Modo de Búsqueda -->
                <div>
                    <label class="block text-gray-300 mb-2 font-semibold text-sm">Modo de Búsqueda</label>
                    <div class="relative neon-border rounded-lg bg-slate-800 transition-all duration-300">
                        <select id="modoBusqueda" class="w-full bg-transparent text-white p-3 appearance-none focus:outline-none cursor-pointer">
                            <option value="general" class="bg-slate-800">Búsqueda General (4,950 combinaciones)</option>
                            <option value="iniciales" class="bg-slate-800">Por Iniciales (Misma Decena Inicial)</option>
                            <option value="terminales" class="bg-slate-800">Por Terminales (Mismo Último Dígito)</option>
                            <option value="compartidos" class="bg-slate-800">Por Dígito Compartido</option>
                        </select>
                        <div class="absolute right-3 top-3 pointer-events-none text-neon-teal">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>

                <!-- Selector de Dígito (Oculto en General) -->
                <div id="digitoContainer" class="hidden opacity-0 transition-opacity duration-300">
                    <label class="block text-gray-300 mb-2 font-semibold text-sm">Dígito Objetivo</label>
                    <div class="grid grid-cols-5 gap-2">
                        <!-- Generado por JS -->
                        <script>
                            for(let i=0; i<=9; i++) {
                                document.write(`
                                    <button onclick="seleccionarDigito(${i})" id="btn-digito-${i}" class="digito-btn py-2 bg-slate-700 hover:bg-slate-600 rounded text-center border border-gray-600 transition-colors font-bold">
                                        ${i}
                                    </button>
                                `);
                            }
                        </script>
                    </div>
                    <input type="hidden" id="digitoSeleccionado" value="0">
                </div>
            </div>

            <div class="mt-8 text-center">
                <button id="btnAnalizar" onclick="iniciarAnalisis()" class="bg-gradient-to-r from-neon-teal to-blue-600 text-white font-bold py-3 px-10 rounded-full shadow-[0_0_15px_rgba(45,212,191,0.5)] hover:shadow-[0_0_25px_rgba(45,212,191,0.8)] transform hover:scale-105 transition-all text-lg">
                    <i class="fas fa-search-location mr-2"></i> Iniciar Análisis de Descarte
                </button>
            </div>
        </div>

        <!-- Pantalla de Análisis (Animación) -->
        <div id="analisisPantalla" class="hidden glass-panel p-8 text-center min-h-[300px] flex-col justify-center items-center">
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 border-4 border-t-neon-teal border-r-neon-purple border-b-neon-pink border-l-transparent rounded-full animate-spin"></div>
                <div class="absolute inset-2 border-4 border-l-neon-teal border-b-neon-purple border-t-neon-pink border-r-transparent rounded-full animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
                <i class="fas fa-microchip text-3xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-neon-teal mb-2" id="txtAnalizando">Retrocediendo en el tiempo...</h3>
            <p class="text-gray-400 font-mono text-sm h-6" id="txtMatrix"></p>
            
            <div class="w-full bg-slate-800 rounded-full h-2.5 mt-6 border border-slate-600 overflow-hidden">
                <div id="barraProgreso" class="bg-gradient-to-r from-neon-teal to-neon-purple h-2.5 rounded-full" style="width: 0%"></div>
            </div>
        </div>

        <!-- Resultados -->
        <div id="resultadoPantalla" class="hidden">
            <div class="glass-panel p-8 text-center relative overflow-hidden border-2 border-neon-pink shadow-[0_0_30px_rgba(236,72,153,0.3)]">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-neon-pink opacity-20 blur-3xl rounded-full"></div>
                <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-neon-teal opacity-20 blur-3xl rounded-full"></div>
                
                <h3 class="text-gray-300 text-lg uppercase tracking-widest mb-2">Único Palé Pendiente</h3>
                <p class="text-gray-500 text-sm mb-6" id="resContexto">En la categoría seleccionada</p>
                
                <div class="flex justify-center items-center gap-4 mb-8">
                    <div id="resNum1" class="text-7xl md:text-9xl font-black bg-gradient-to-b from-white to-gray-400 gradient-text drop-shadow-[0_5px_15px_rgba(0,0,0,1)]">00</div>
                    <div class="text-5xl text-neon-pink font-light">-</div>
                    <div id="resNum2" class="text-7xl md:text-9xl font-black bg-gradient-to-b from-white to-gray-400 gradient-text drop-shadow-[0_5px_15px_rgba(0,0,0,1)]">00</div>
                </div>

                <div class="bg-slate-800/80 rounded-xl p-4 border border-gray-600 inline-block text-left shadow-inner">
                    <p class="text-gray-300 text-sm"><i class="fas fa-calendar-alt text-neon-teal mr-2"></i> <span class="font-bold">Retroceso hasta:</span> <span id="resFecha" class="text-white">...</span></p>
                    <p class="text-gray-300 text-sm mt-2"><i class="fas fa-info-circle text-neon-purple mr-2"></i> <span class="font-bold">Estado:</span> Todas las demás combinaciones han salido al menos una vez desde esa fecha.</p>
                </div>
                
                <div class="mt-8">
                    <button onclick="resetear()" class="text-gray-400 hover:text-white transition-colors border-b border-transparent hover:border-white pb-1">
                        <i class="fas fa-redo text-xs mr-1"></i> Realizar nueva búsqueda
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Lógica de UI
        const modoSelect = document.getElementById('modoBusqueda');
        const digitoContainer = document.getElementById('digitoContainer');
        const digitoInput = document.getElementById('digitoSeleccionado');

        modoSelect.addEventListener('change', function() {
            if (this.value === 'general') {
                digitoContainer.classList.add('opacity-0');
                setTimeout(() => digitoContainer.classList.add('hidden'), 300);
            } else {
                digitoContainer.classList.remove('hidden');
                setTimeout(() => digitoContainer.classList.remove('opacity-0'), 10);
            }
        });

        function seleccionarDigito(d) {
            digitoInput.value = d;
            document.querySelectorAll('.digito-btn').forEach(btn => {
                btn.classList.remove('bg-neon-teal', 'text-slate-900', 'border-neon-teal', 'shadow-[0_0_10px_rgba(45,212,191,0.5)]');
                btn.classList.add('bg-slate-700', 'border-gray-600');
            });
            const btn = document.getElementById('btn-digito-' + d);
            btn.classList.remove('bg-slate-700', 'border-gray-600');
            btn.classList.add('bg-neon-teal', 'text-slate-900', 'border-neon-teal', 'shadow-[0_0_10px_rgba(45,212,191,0.5)]');
        }
        
        // Seleccionar el 0 por defecto
        seleccionarDigito(0);

        let dataIA = null;

        async function cargarDatos() {
            try {
                const response = await fetch('api_prediccion.php?t=' + new Date().getTime());
                if (!response.ok) throw new Error("No se pudo cargar");
                dataIA = await response.json();
                return true;
            } catch (e) {
                alert("Error al conectar con la red neuronal. Intente en unos minutos.");
                return false;
            }
        }

        async function iniciarAnalisis() {
            document.querySelector('.glass-panel').style.display = 'none';
            document.getElementById('analisisPantalla').classList.remove('hidden');
            document.getElementById('analisisPantalla').style.display = 'flex';
            
            // Iniciar animacion Matrix
            let frases = [
                "Extrayendo historial de sorteos...",
                "Cruzando datos con pales.txt...",
                "Eliminando combinaciones recientes...",
                "Retrocediendo días anteriores...",
                "Buscando anomalías estadísticas...",
                "Aislando el último eslabón..."
            ];
            let f = 0;
            let interval = setInterval(() => {
                document.getElementById('txtMatrix').innerText = frases[f % frases.length] + " [0x" + Math.random().toString(16).substr(2, 6) + "]";
                f++;
            }, 800);

            // Animar barra
            let progreso = 0;
            let bInterval = setInterval(() => {
                progreso += Math.random() * 5;
                if(progreso > 100) progreso = 100;
                document.getElementById('barraProgreso').style.width = progreso + '%';
            }, 100);

            // Cargar datos reales
            let exito = await cargarDatos();
            if(!exito) {
                clearInterval(interval);
                clearInterval(bInterval);
                resetear();
                return;
            }

            // Simular tiempo de proceso IA para impacto visual
            setTimeout(() => {
                clearInterval(interval);
                clearInterval(bInterval);
                document.getElementById('barraProgreso').style.width = '100%';
                mostrarResultado();
            }, 4000);
        }

        function mostrarResultado() {
            document.getElementById('analisisPantalla').style.display = 'none';
            document.getElementById('resultadoPantalla').classList.remove('hidden');

            const modo = modoSelect.value;
            const digito = digitoInput.value;
            let info = null;
            let contextoStr = "";

            if (modo === 'general') {
                info = dataIA.general.all;
                contextoStr = "De las 4,950 combinaciones posibles";
            } else {
                info = dataIA[modo][digito];
                if(modo === 'iniciales') contextoStr = `De la decena inicial [${digito}]`;
                if(modo === 'terminales') contextoStr = `De los terminales en [${digito}]`;
                if(modo === 'compartidos') contextoStr = `De las combinaciones que comparten el dígito [${digito}]`;
            }

            if(info && info.status === 'found_one' && info.missing.length > 0) {
                let nums = info.missing[0].split('-');
                document.getElementById('resNum1').innerText = nums[0];
                document.getElementById('resNum2').innerText = nums[1];
                
                // Formatear fecha
                let d = new Date(info.fecha_alcanzada + "T12:00:00");
                let opt = { year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('resFecha').innerText = d.toLocaleDateString('es-ES', opt);
                document.getElementById('resContexto').innerText = contextoStr;
            } else {
                // Caso extremo donde hay más de uno o la historia terminó
                document.getElementById('resNum1').innerText = "??";
                document.getElementById('resNum2').innerText = "??";
                if(info.status === 'history_ended') {
                    document.getElementById('resFecha').innerText = "Límite del historial";
                    document.getElementById('resContexto').innerText = `Aún faltan ${info.remaining_count} combinaciones por salir en el registro actual.`;
                } else {
                    document.getElementById('resFecha').innerText = "N/A";
                    document.getElementById('resContexto').innerText = "Múltiples combinaciones empatadas. Seleccione otro modo.";
                }
            }
        }

        function resetear() {
            document.getElementById('resultadoPantalla').classList.add('hidden');
            document.getElementById('analisisPantalla').style.display = 'none';
            document.querySelector('.glass-panel').style.display = 'block';
            document.getElementById('barraProgreso').style.width = '0%';
        }
    </script>
</body>
</html>
