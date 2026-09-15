<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IA Predicción Quinielas - Lotería</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
        }

        .gradient-text {
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .text-neon-teal { color: #2dd4bf; }
        .text-neon-purple { color: #a855f7; }
        .text-neon-pink { color: #ec4899; }
        .text-neon-yellow { color: #eab308; }

        .matrix-text {
            font-family: monospace;
            color: #2dd4bf;
            text-shadow: 0 0 8px rgba(45, 212, 191, 0.8);
        }
    </style>
</head>
<body class="min-h-screen relative">
    
    <!-- Background Grid -->
    <div class="fixed inset-0 z-0 opacity-20 pointer-events-none" style="background-image: linear-gradient(#334155 1px, transparent 1px), linear-gradient(90deg, #334155 1px, transparent 1px); background-size: 40px 40px;"></div>
    
    <div class="text-center pt-10 pb-6 relative z-10 px-4">
        <h1 class="text-4xl md:text-5xl font-black bg-gradient-to-r from-neon-yellow to-neon-pink text-transparent bg-clip-text drop-shadow-md tracking-wide">IA Quinielas</h1>
        <p class="text-gray-400 mt-2 font-light text-sm md:text-base border-b border-gray-700/50 inline-block pb-2">Análisis de números individuales y dígitos</p>
    </div>

    <!-- Contenedor Principal -->
    <div class="max-w-4xl mx-auto px-4 pb-20 relative z-10">

        <!-- Filtros Panel -->
        <div class="glass-panel rounded-2xl p-6 shadow-2xl mb-8 border-t-4 border-t-neon-yellow">
            <h2 class="text-xl font-bold mb-6 flex items-center text-gray-200">
                <i class="fas fa-sliders-h mr-3 text-neon-yellow"></i> Configuración de Búsqueda
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Tipo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">Objetivo</label>
                    <select id="tipoObjetivo" class="w-full bg-slate-900 border border-slate-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-neon-yellow transition-all appearance-none">
                        <option value="numero">Número Completo (00-99)</option>
                        <option value="digito">Dígito Individual (0-9)</option>
                    </select>
                </div>
                
                <!-- Posición -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">Posición del Sorteo</label>
                    <select id="posicionSorteo" class="w-full bg-slate-900 border border-slate-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-neon-yellow transition-all appearance-none">
                        <option value="todas">Todas (1ra, 2da y 3ra)</option>
                        <option value="primera">Solamente en Primera</option>
                    </select>
                </div>

                <!-- Filtro/Modo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">Modo de Búsqueda</label>
                    <select id="modoBusqueda" class="w-full bg-slate-900 border border-slate-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-neon-yellow transition-all appearance-none">
                        <option value="general">Búsqueda General Libre</option>
                        <option value="inicial">Filtro: Inicial / Decena</option>
                        <option value="terminal">Filtro: Terminal</option>
                    </select>
                </div>
            </div>

            <!-- Selector de Dígito -->
            <div id="digitoContainer" class="mt-8 pt-6 border-t border-slate-700 hidden transition-all duration-300">
                <label class="block text-sm font-semibold text-gray-400 mb-4 text-center">Seleccione el dígito base para el filtro</label>
                <div class="flex flex-wrap justify-center gap-2">
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(0)" id="btn-digito-0">0</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(1)" id="btn-digito-1">1</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(2)" id="btn-digito-2">2</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(3)" id="btn-digito-3">3</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(4)" id="btn-digito-4">4</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(5)" id="btn-digito-5">5</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(6)" id="btn-digito-6">6</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(7)" id="btn-digito-7">7</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(8)" id="btn-digito-8">8</button>
                    <button class="digito-btn w-12 h-12 rounded-xl font-black text-xl transition-all shadow-md bg-slate-700 border border-gray-600 hover:bg-slate-600 text-white" onclick="seleccionarDigito(9)" id="btn-digito-9">9</button>
                </div>
                <input type="hidden" id="digitoSeleccionado" value="0">
            </div>

            <div class="mt-8 text-center">
                <button onclick="iniciarAnalisis()" class="bg-gradient-to-r from-yellow-500 to-pink-600 hover:from-yellow-400 hover:to-pink-500 text-white font-bold text-lg py-4 px-12 rounded-full shadow-[0_0_20px_rgba(234,179,8,0.4)] transition-transform transform hover:scale-105 uppercase tracking-wider">
                    <i class="fas fa-search-location mr-2"></i> Extraer Resultado
                </button>
            </div>
        </div>

        <!-- Pantalla de Análisis (Oculta por defecto) -->
        <div id="analisisPantalla" class="hidden flex-col items-center justify-center py-16">
            <div class="w-24 h-24 border-4 border-slate-700 border-t-neon-yellow rounded-full animate-spin mb-8"></div>
            <div id="txtMatrix" class="matrix-text text-xl md:text-2xl mb-4 h-8">Conectando a la red neuronal...</div>
            <div class="w-full max-w-md bg-slate-800 rounded-full h-2 mt-4 overflow-hidden">
                <div id="barraProgreso" class="bg-neon-yellow h-2 w-0 transition-all duration-300"></div>
            </div>
        </div>

        <!-- Pantalla de Resultado (Oculta por defecto) -->
        <div id="resultadoPantalla" class="hidden">
            <div class="bg-gradient-to-b from-slate-800 to-slate-900 border border-pink-500/30 rounded-3xl p-8 shadow-[0_0_40px_rgba(236,72,153,0.15)] text-center relative overflow-hidden">
                
                <div class="absolute -top-10 -right-10 text-pink-500/5">
                    <i class="fas fa-crosshairs text-9xl"></i>
                </div>

                <h3 class="text-gray-400 font-semibold uppercase tracking-widest text-sm mb-2">ÚNICO AUSENTE PENDIENTE</h3>
                <p id="resContexto" class="text-xs text-gray-500 mb-8 max-w-sm mx-auto">Calculado en base a filtros</p>
                
                <div class="flex justify-center items-center gap-4 mb-8">
                    <div id="resNum1" class="text-8xl md:text-[10rem] font-black bg-gradient-to-b from-white to-gray-400 gradient-text drop-shadow-[0_5px_15px_rgba(0,0,0,1)]">00</div>
                </div>

                <div class="bg-slate-800/80 rounded-xl p-4 border border-gray-600 inline-block text-left shadow-inner">
                    <p class="text-gray-300 text-sm"><i class="fas fa-calendar-alt text-neon-yellow mr-2"></i> <span class="font-bold">Retroceso hasta:</span> <span id="resFecha" class="text-white">...</span></p>
                    <p class="text-gray-300 text-sm mt-2"><i class="fas fa-info-circle text-neon-pink mr-2"></i> <span class="font-bold">Estado:</span> Los demás han salido al menos una vez desde esta fecha.</p>
                </div>
                
                <!-- Chat IA -->
                <div id="aiChatContainer" class="mt-8 bg-slate-900/80 rounded-2xl p-6 border border-neon-purple text-left relative hidden">
                    <div class="absolute -top-4 left-6 bg-slate-900 border border-neon-purple px-4 py-1 rounded-full text-neon-purple text-sm font-bold flex items-center shadow-[0_0_15px_rgba(168,85,247,0.4)]">
                        <i class="fas fa-robot mr-2"></i> Análisis IA
                    </div>
                    <div id="aiChatTyping" class="text-gray-400 text-sm flex items-center mt-2">
                        <i class="fas fa-circle-notch fa-spin mr-2 text-neon-purple"></i> La IA está redactando el análisis matemático detallado...
                    </div>
                    <div id="aiChatResult" class="text-gray-300 text-sm leading-relaxed hidden whitespace-pre-wrap"></div>
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
        const objetivoSelect = document.getElementById('tipoObjetivo');
        const modoSelect = document.getElementById('modoBusqueda');
        const digitoContainer = document.getElementById('digitoContainer');
        const digitoInput = document.getElementById('digitoSeleccionado');

        function actualizarFiltros() {
            // "general" no requiere dígito. Los demás sí.
            if (modoSelect.value === 'general') {
                digitoContainer.classList.add('opacity-0');
                setTimeout(() => digitoContainer.classList.add('hidden'), 300);
            } else {
                digitoContainer.classList.remove('hidden');
                setTimeout(() => digitoContainer.classList.remove('opacity-0'), 10);
            }
        }

        modoSelect.addEventListener('change', actualizarFiltros);
        objetivoSelect.addEventListener('change', actualizarFiltros);

        function seleccionarDigito(d) {
            digitoInput.value = d;
            document.querySelectorAll('.digito-btn').forEach(btn => {
                btn.classList.remove('bg-neon-yellow', 'text-slate-900', 'border-neon-yellow', 'shadow-[0_0_10px_rgba(234,179,8,0.5)]');
                btn.classList.add('bg-slate-700', 'border-gray-600', 'text-white');
            });
            const btn = document.getElementById('btn-digito-' + d);
            btn.classList.remove('bg-slate-700', 'border-gray-600', 'text-white');
            btn.classList.add('bg-neon-yellow', 'text-slate-900', 'border-neon-yellow', 'shadow-[0_0_10px_rgba(234,179,8,0.5)]');
        }
        
        seleccionarDigito(0);

        let dataIA = null;

        async function cargarDatos() {
            try {
                const response = await fetch('api_quinielas.php?t=' + new Date().getTime());
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
            
            let frases = [
                "Analizando frecuencias individuales...",
                "Filtrando posiciones históricas...",
                "Descartando números recientes...",
                "Evaluando decenas y terminales...",
                "Aislando quiniela objetivo..."
            ];
            let f = 0;
            let interval = setInterval(() => {
                document.getElementById('txtMatrix').innerText = frases[f % frases.length] + " [0x" + Math.random().toString(16).substr(2, 6) + "]";
                f++;
            }, 800);

            let progreso = 0;
            let bInterval = setInterval(() => {
                progreso += Math.random() * 5;
                if(progreso > 100) progreso = 100;
                document.getElementById('barraProgreso').style.width = progreso + '%';
            }, 100);

            let exito = await cargarDatos();
            if(!exito) {
                clearInterval(interval);
                clearInterval(bInterval);
                resetear();
                return;
            }

            setTimeout(() => {
                clearInterval(interval);
                clearInterval(bInterval);
                document.getElementById('barraProgreso').style.width = '100%';
                mostrarResultado();
            }, 2500);
        }

        function mostrarResultado() {
            document.getElementById('analisisPantalla').style.display = 'none';
            document.getElementById('resultadoPantalla').classList.remove('hidden');

            const obj = objetivoSelect.value; // "numero" o "digito"
            const pos = document.getElementById('posicionSorteo').value; // "todas" o "primera"
            let modo = modoSelect.value; // "general", "inicial", "terminal"
            const digito = digitoInput.value;
            
            if (obj === 'numero' && modo === 'inicial') {
                modo = 'decena'; // en python le llamamos decena
            }

            let info = null;
            
            if (modo === 'general') {
                info = dataIA[obj][pos][modo]['all'];
            } else {
                info = dataIA[obj][pos][modo][digito];
            }

            if(info && info.status === 'found_one' && info.missing) {
                document.getElementById('resNum1').innerText = info.missing;
                
                let d = new Date(info.fecha);
                d = new Date(d.getTime() + d.getTimezoneOffset() * 60000);
                
                let opt = { year: 'numeric', month: 'long', day: 'numeric' };
                let numOpt = { day: '2-digit', month: '2-digit', year: 'numeric' };
                let strFecha = d.toLocaleDateString('es-ES', opt);
                let strNumFecha = d.toLocaleDateString('es-ES', numOpt);
                document.getElementById('resFecha').innerText = strFecha + " (" + strNumFecha + ")";
                
                let posTxt = (pos === 'primera') ? "en 1ra posición" : "en cualquier posición";
                let ctx = obj === 'numero' ? "Entre todos los números" : "Entre todos los dígitos";
                if (modo === 'decena') ctx = "En la decena inicial del " + digito;
                if (modo === 'terminal') ctx = "En los terminales del " + digito;
                if (obj === 'digito' && modo === 'inicial') ctx = "Buscando dígitos a la izquierda";
                if (obj === 'digito' && modo === 'terminal') ctx = "Buscando dígitos a la derecha";
                
                document.getElementById('resContexto').innerText = ctx + " " + posTxt;
                
                solicitarChatIA([info.missing], obj === 'digito', pos);
            } else if (info && info.status === 'found') {
                // Para búsqueda de un dígito específico, mostrar cuándo fue la última vez que salió
                document.getElementById('resNum1').innerText = info.digit;
                let d = new Date(info.fecha);
                d = new Date(d.getTime() + d.getTimezoneOffset() * 60000);
                let opt = { year: 'numeric', month: 'long', day: 'numeric' };
                let numOpt = { day: '2-digit', month: '2-digit', year: 'numeric' };
                let strFecha = d.toLocaleDateString('es-ES', opt);
                let strNumFecha = d.toLocaleDateString('es-ES', numOpt);
                document.getElementById('resFecha').innerText = strFecha + " (" + strNumFecha + ")";
                
                let posTxt = (pos === 'primera') ? "en 1ra posición" : "en cualquier posición";
                let ctx = "Última aparición del dígito " + digito;
                if (modo === 'inicial') ctx += " (como inicial)";
                if (modo === 'terminal') ctx += " (como terminal)";
                document.getElementById('resContexto').innerText = ctx + " " + posTxt;
                
                solicitarChatIA([info.digit], obj === 'digito', pos);
            } else if (info && info.status === 'found_multiple' && info.missing) {
                let nums = info.missing;
                let textToShow = nums.join(' y ');
                
                let resEl = document.getElementById('resNum1');
                resEl.innerText = textToShow;
                // Ajustar tamaño si son varios
                if (nums.length > 2) {
                    resEl.classList.remove('md:text-[10rem]', 'text-8xl');
                    resEl.classList.add('text-4xl', 'md:text-6xl', 'leading-tight', 'px-4');
                } else {
                    resEl.classList.remove('md:text-[10rem]');
                    resEl.classList.add('md:text-8xl');
                }
                
                let d = new Date(info.fecha);
                d = new Date(d.getTime() + d.getTimezoneOffset() * 60000);
                let opt = { year: 'numeric', month: 'long', day: 'numeric' };
                let numOpt = { day: '2-digit', month: '2-digit', year: 'numeric' };
                let strFecha = d.toLocaleDateString('es-ES', opt);
                let strNumFecha = d.toLocaleDateString('es-ES', numOpt);
                document.getElementById('resFecha').innerText = strFecha + " (" + strNumFecha + ")";
                
                let posTxt = (pos === 'primera') ? "en 1ra posición" : "en cualquier posición";
                let ctx = obj === 'numero' ? "Múltiples números empatados" : "Múltiples dígitos empatados";
                document.getElementById('resContexto').innerText = ctx + " " + posTxt;
                
                solicitarChatIA(info.missing, obj === 'digito', pos);
            } else {
                document.getElementById('resNum1').innerText = "??";
                if(info && info.status === 'history_ended') {
                    document.getElementById('resFecha').innerText = "Límite del historial";
                    document.getElementById('resContexto').innerText = `Aún faltan ${info.rem} por salir en el registro.`;
                } else {
                    document.getElementById('resFecha').innerText = "N/A";
                    document.getElementById('resContexto').innerText = "Múltiples empatados o faltantes.";
                }
            }
        }

        async function solicitarChatIA(target, esDigito, pos) {
            const chatContainer = document.getElementById('aiChatContainer');
            const typing = document.getElementById('aiChatTyping');
            const resultBox = document.getElementById('aiChatResult');
            
            chatContainer.classList.remove('hidden');
            typing.classList.remove('hidden');
            resultBox.classList.add('hidden');
            
            try {
                let targetParam = Array.isArray(target) ? target[0] : target;
                
                // 1. Cargar stats_quinielas.json usando PHP para evitar bloqueos 403 del Anti-Bot
                let statsText = "No se encontraron estadísticas para este objetivo.";
                let statsData = null;
                <?php
                if (file_exists('stats_quinielas.json')) {
                    echo 'statsData = ' . file_get_contents('stats_quinielas.json') . ';';
                }
                ?>
                
                if (!esDigito) {
                    try {
                        let posKey = (pos === 'primera') ? 'primera' : 'todas';
                        if (statsData && statsData[posKey] && statsData[posKey][targetParam]) {
                            let s = statsData[posKey][targetParam];
                            statsText = `Número analizado: ${targetParam}\n`;
                                statsText += `Última vez que salió: ${s.ultima_aparicion.fecha} en la lotería ${s.ultima_aparicion.loteria} (posición: ${s.ultima_aparicion.posicion}).\n`;
                                statsText += `Días ausente: ${s.dias_ausente} días.\n`;
                                statsText += `Lotería donde más repite: ${s.loteria_mas_frecuente}.\n`;
                                statsText += `Posición donde más repite: ${s.posicion_mas_frecuente}.\n`;
                                statsText += `Número que SIEMPRE sale el mismo día que ${targetParam} (compañero frecuente): ${s.numero_companero_frecuente}.\n`;
                                
                                if (Array.isArray(target) && target.length > 1) {
                                    statsText += `NOTA IMPORTANTE: Hubo un empate con otros números faltantes (${target.join(', ')}), pero enfócate solo en el ${targetParam}.\n`;
                                }
                            }
                    } catch (e) {
                        console.log("No se pudo parsear stats_quinielas", e);
                    }
                } else {
                    statsText = `Dígito analizado: ${targetParam}\n`;
                    if (Array.isArray(target) && target.length > 1) {
                        statsText += `NOTA IMPORTANTE: Hubo un empate con otros dígitos faltantes (${target.join(', ')}), pero enfócate solo en el ${targetParam}.\n`;
                    }
                }
                let totalSorteos = 35000;
                if (statsData && statsData.metadata && statsData.metadata.total_sorteos) {
                    totalSorteos = statsData.metadata.total_sorteos;
                }
                
                // 2. Construir Prompt
                let prompt = "Actúa como un experto analista estadístico de loterías.\n";
                prompt += `Se ha detectado que el número (o dígito) más rezagado matemáticamente es el ${targetParam}.\n\n`;
                prompt += `Aquí están las estadísticas ESTRICTAS reales calculadas de una base de datos actualizada al minuto de ${totalSorteos.toLocaleString()} sorteos:\n`;
                prompt += "```\n" + statsText + "\n```\n\n";
                prompt += "Reglas obligatorias:\n";
                prompt += "1. NO ALUCINES. Usa ÚNICAMENTE los datos provistos en el bloque anterior. Si algo no está ahí, no lo inventes.\n";
                prompt += "2. IMPORTANTE: Responde EXCLUSIVAMENTE EN ESPAÑOL, no generes textos de tus procesos internos (No 'thinking process').\n";
                prompt += "3. Sé conciso y ve directo al grano, pero explica tu razonamiento claramente.\n";
                prompt += "4. Responde a estas preguntas en tu texto: cuándo fue la última vez que salió y en cuál sorteo exactamente, cuántos días lleva sin salir, cuál ha sido su comportamiento (en qué lotería y posición repite más).\n";
                prompt += "5. Menciona el compañero frecuente explícitamente y explica que este otro número suele salir el mismo día.\n";
                prompt += "6. Haz una predicción basándote en la ley de probabilidades.\n";
                prompt += "7. Actúa como si tú mismo hubieras hecho el análisis. Sé profesional y directo.\n";
                prompt += "8. REVISA TU ORTOGRAFÍA Y GRAMÁTICA. Asegúrate de usar correctamente el género y número (ej. 'un reaparecimiento' o 'una reaparición', NUNCA 'una reaparecimiento').\n";

                // 3. Llamar a OpenRouter directamente desde el Frontend (NVIDIA bloquea las peticiones desde el navegador por política CORS)
                const openRouterApiKey = "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";
                const orRes = await fetch("https://openrouter.ai/api/v1/chat/completions", {
                    method: "POST",
                    headers: {
                        "Authorization": "Bearer " + openRouterApiKey,
                        "Content-Type": "application/json",
                        "HTTP-Referer": "http://numerosrd.42web.io"
                    },
                    body: JSON.stringify({
                        model: "nvidia/nemotron-3-super-120b-a12b:free",
                        max_tokens: 3000,
                        messages: [
                            { role: "system", content: "Eres el Analista Principal de un algoritmo de predicción de loterías. Eres objetivo, estadístico y claro." },
                            { role: "user", content: prompt }
                        ]
                    })
                });
                
                const orData = await orRes.json();
                
                typing.classList.add('hidden');
                resultBox.classList.remove('hidden');
                
                if (orData.choices && orData.choices.length > 0) {
                    let aiText = orData.choices[0].message.content;
                    
                    // Eliminar bloques de pensamiento si la IA los incluye (ej. <think>...</think>)
                    aiText = aiText.replace(/<think>[\s\S]*?<\/think>/gi, '').trim();
                    // Eliminar "Here's a thinking process" si aparece
                    if (aiText.includes("Here's a thinking process")) {
                        let parts = aiText.split(/---|\*\*\*|___|\n\n\n/); // Intenta encontrar un separador
                        aiText = parts.length > 1 ? parts[parts.length - 1] : aiText; // Si no hay separador, al menos intentamos el prompt estricto
                    }

                    // Simple markdown to HTML
                    aiText = aiText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    aiText = aiText.replace(/\n/g, '<br>');
                    resultBox.innerHTML = aiText;
                } else if (orData.error) {
                    resultBox.innerText = "Error API IA: " + (orData.error.message || JSON.stringify(orData.error));
                } else {
                    resultBox.innerText = "No se pudo obtener el análisis.";
                }
            } catch (e) {
                typing.classList.add('hidden');
                resultBox.classList.remove('hidden');
                resultBox.innerText = "Error de red: " + e.message;
            }
        }

        function resetear() {
            document.getElementById('resultadoPantalla').classList.add('hidden');
            document.getElementById('analisisPantalla').style.display = 'none';
            document.querySelector('.glass-panel').style.display = 'block';
            document.getElementById('barraProgreso').style.width = '0%';
            
            let resEl = document.getElementById('resNum1');
            resEl.className = "text-8xl md:text-[10rem] font-black bg-gradient-to-b from-white to-gray-400 gradient-text drop-shadow-[0_5px_15px_rgba(0,0,0,1)]";
        }
    </script>
</body>
</html>
