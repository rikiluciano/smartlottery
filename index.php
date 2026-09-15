<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calculadora Estratégica RLabs Premium</title>
  
  <!-- Tipografía Premium -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Configuración personalizada de Tailwind -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            display: ['Plus Jakarta Sans', 'sans-serif'],
          },
          colors: {
            darkbg: '#020617',
            gold: {
              300: '#FDE047',
              400: '#FACC15',
              500: '#EAB308',
            },
            neon: {
              teal: '#2DD4BF',
              purple: '#A855F7',
              cyan: '#22D3EE'
            }
          },
          animation: {
            'blob': 'blob 7s infinite',
            'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          },
          keyframes: {
            blob: {
              '0%': { transform: 'translate(0px, 0px) scale(1)' },
              '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
              '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
              '100%': { transform: 'translate(0px, 0px) scale(1)' },
            },
            fadeInUp: {
              '0%': { opacity: '0', transform: 'translateY(20px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' },
            }
          }
        }
      }
    }
  </script>

  <link rel="stylesheet" href="resultado/RLabs/rlabs-footer.css?v=2">
  
  <style>
    body::before {
      content: "";
      position: fixed;
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%;
      background-image: url('finance_bg.jpg');
      background-size: cover;
      background-position: center;
      opacity: 0.05;
      pointer-events: none;
      z-index: -1;
      mix-blend-mode: screen;
    }

    /* Efecto Glassmorphism Premium */
    .glass-panel {
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.05);
    }
    
    .glass-input {
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
      color: #F8FAFC;
    }
    
    .glass-input:focus {
      background: rgba(0, 0, 0, 0.5);
      border-color: #2DD4BF;
      box-shadow: 0 0 15px rgba(45, 212, 191, 0.3);
      outline: none;
    }

    /* Scrollbar invisible pero funcional */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #020617; }
    ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #475569; }
    
    /* Animación del Loader de IA */
    .ai-loader {
      width: 48px;
      height: 48px;
      border: 3px solid rgba(45, 212, 191, 0.2);
      border-radius: 50%;
      border-top-color: #2DD4BF;
      animation: spin 1s ease-in-out infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>

<body class="bg-darkbg text-gray-200 min-h-screen flex flex-col relative overflow-x-hidden selection:bg-neon-teal selection:text-darkbg">
  
  <!-- Background Animated Blobs (Elementos de diseño de lujo) -->
  <div class="fixed inset-0 w-full h-full pointer-events-none z-0 overflow-hidden">
    <div class="absolute top-0 -left-4 w-72 h-72 bg-neon-purple rounded-full mix-blend-screen filter blur-[100px] opacity-20 animation-blob"></div>
    <div class="absolute top-0 -right-4 w-72 h-72 bg-neon-cyan rounded-full mix-blend-screen filter blur-[100px] opacity-20 animation-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-neon-teal rounded-full mix-blend-screen filter blur-[100px] opacity-20 animation-blob animation-delay-4000"></div>
  </div>

  <header class="relative z-10 pt-10 pb-6 text-center">
    <div class="inline-block">
      <h1 class="text-4xl md:text-5xl font-display font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-neon-teal via-white to-neon-purple mb-2 pb-2">
        Calculadora Estratégica
      </h1>
      <p class="text-sm md:text-base text-gray-400 uppercase tracking-[0.3em] font-semibold">Motor Predictivo RLabs</p>
    </div>
  </header>

  <main class="flex-grow container mx-auto p-4 relative z-10">
    <!-- Contenedor Principal de la Calculadora -->
    <form id="formulario" onsubmit="return false;" class="glass-panel p-8 rounded-3xl max-w-2xl mx-auto space-y-6 relative overflow-hidden">
      <!-- Decoración sutil en el borde -->
      <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-neon-teal via-neon-cyan to-neon-purple"></div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Tipo de Juego -->
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider font-bold text-gray-400">Tipo de Juego</label>
          <div class="relative">
            <select id="tipoJuego" name="tipoJuego" class="glass-input w-full p-3 pl-10 rounded-xl appearance-none cursor-pointer">
              <option value="72" data-nombre="Quiniela">Quiniela (72x)</option>
              <option value="1500" data-nombre="Palé">Palé (1,500x)</option>
              <option value="3000" data-nombre="Súper Palé">Súper Palé (3,000x)</option>
              <option value="20000" data-nombre="Tripleta">Tripleta (20,000x)</option>
            </select>
            <!-- Icono SVG -->
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-neon-teal">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
          </div>
        </div>

        <!-- Días a jugar -->
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider font-bold text-gray-400">Días a Proyectar</label>
          <div class="relative">
            <input type="number" id="dias" name="dias" min="1" required class="glass-input w-full p-3 pl-10 rounded-xl" placeholder="Ej. 30">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-neon-cyan">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
          </div>
        </div>

        <!-- Monto Inicial -->
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider font-bold text-gray-400">Monto Inicial a Jugar ($)</label>
          <div class="relative">
            <input type="number" id="montoInicial" name="montoInicial" min="0.01" step="0.01" value="1" required class="glass-input w-full p-3 pl-10 rounded-xl">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gold-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
          </div>
        </div>

        <!-- Modo de Cálculo -->
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider font-bold text-gray-400">Estrategia de Capital</label>
          <div class="relative">
            <select id="modoCalculo" name="modoCalculo" class="glass-input w-full p-3 pl-10 rounded-xl appearance-none cursor-pointer">
              <option value="gananciaDiaria">Progresión Inteligente (Recomendado)</option>
              <option value="martingala">Martingala Clásica</option>
            </select>
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-neon-purple">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
          </div>
        </div>

        <!-- Campo Martingala (Oculto por defecto) -->
        <div id="campoMartingala" class="space-y-2 hidden md:col-span-2">
          <label class="text-xs uppercase tracking-wider font-bold text-gray-400">Multiplicador Martingala</label>
          <input type="number" id="martingala" name="martingala" min="1" step="0.1" class="glass-input w-full p-3 rounded-xl" placeholder="Ej. 2">
        </div>

        <!-- Cantidad de Números -->
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider font-bold text-gray-400" id="labelCantidadNumeros">Cantidad de Quiniela</label>
          <div class="relative">
            <input type="number" id="cantidadNumeros" name="cantidadNumeros" min="1" value="1" required class="glass-input w-full p-3 pl-10 rounded-xl">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
            </div>
          </div>
        </div>

        <!-- Cantidad de Loterías -->
        <div class="space-y-2">
          <label class="text-xs uppercase tracking-wider font-bold text-gray-400">Cantidad de Loterías</label>
          <div class="relative">
            <input type="number" id="cantidadLoterias" name="cantidadLoterias" min="1" value="42" required class="glass-input w-full p-3 pl-10 rounded-xl">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
          </div>
        </div>

      </div>

      <!-- Botones de Acción -->
      <div class="flex flex-col sm:flex-row gap-4 pt-4">
        <button type="submit" class="relative group flex-1 bg-neon-teal text-darkbg font-display font-bold text-lg py-3 px-6 rounded-xl overflow-hidden transition-all hover:scale-[1.02] active:scale-95 shadow-[0_0_20px_rgba(45,212,191,0.4)] hover:shadow-[0_0_30px_rgba(45,212,191,0.6)]">
          <span class="relative z-10 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Ejecutar cálculo
          </span>
          <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity"></div>
        </button>
        
        <button type="button" id="btnNuevoCalculo" class="group flex-1 glass-input hover:bg-white/10 text-gray-300 font-display font-bold text-lg py-3 px-6 rounded-xl transition-all hover:text-white flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          Limpiar Datos
        </button>
      </div>
    </form>

    <!-- SECCIÓN DE RESULTADOS (Oculta hasta el cálculo) -->
    <section id="resultado" class="mt-12 hidden opacity-0 transition-opacity duration-500">
      
      <!-- Panel de Análisis IA Quirúrgico -->
      <div class="glass-panel p-1 rounded-2xl mb-8 max-w-4xl mx-auto shadow-[0_0_40px_rgba(168,85,247,0.15)] overflow-hidden relative">
        <div class="absolute inset-0 bg-gradient-to-r from-neon-purple/20 to-neon-cyan/20 animate-pulse-slow"></div>
        <div class="relative bg-darkbg/90 backdrop-blur-xl rounded-xl p-6 md:p-8">
          
          <div class="flex items-center space-x-3 mb-6 relative z-10">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-neon-purple/20 to-neon-teal/20 flex items-center justify-center border border-white/10 overflow-hidden shadow-[0_0_15px_rgba(45,212,191,0.3)]">
              <img src="ai_avatar.jpg" alt="AI Bot" class="w-full h-full object-cover mix-blend-lighten opacity-90">
            </div>
            <h3 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-neon-purple to-neon-teal">Análisis Estratégico de IA</h3>
          </div>

          <!-- Estado de Carga IA -->
            <a href="ia_prediccion_pale.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded shadow flex items-center justify-center gap-2">
                <span class="text-xl">🔮</span> IA Palés
            </a>
            <a href="ia_prediccion_super_pale.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow flex items-center justify-center gap-2">
                <span class="text-xl">✨</span> IA Súper Palés
            </a>
            <a href="ia_prediccion_super_pale.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow flex items-center justify-center gap-2">
                <span class="text-xl">✨</span> IA Súper Palés
            </a>
        </div><p id="ai-loading" class="text-neon-cyan font-mono text-sm animate-pulse">Analizando la rentabilidad y el riesgo de la estrategia...</p>
          </div>

          <!-- Resultado IA -->
          <div id="ai-content" class="hidden text-gray-300 leading-relaxed text-lg font-light">
            <!-- Aquí se inyecta la respuesta "aplatanada" -->
          </div>

        </div>
      </div>

      <!-- Resumen General -->
      <div class="max-w-4xl mx-auto mb-8 text-center">
        <p id="resumenCapital" class="text-xl md:text-2xl font-light text-gray-300 bg-gray-900/50 inline-block p-4 rounded-2xl border border-white/5 shadow-lg"></p>
      </div>

      <!-- Tabla de Proyección Diaria -->
      <div id="tablaResultados" class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Tarjetas inyectadas por JS -->
      </div>
      
    </section>
  </main>

  <!-- RLabs Premium Footer -->
  <div id="rlabs-footer-container" class="mt-20"></div>
  <script src="resultado/RLabs/rlabs-footer.js" defer></script>
  <script>
    fetch('resultado/RLabs/rlabs-footer.html')
      .then(response => response.text())
      .then(html => {
        document.getElementById('rlabs-footer-container').innerHTML = html;
        if (typeof updateRLabsYear === 'function') updateRLabsYear();
      });
  </script>

  <!-- Lógica Principal -->
  <script>
    const selectJuego = document.getElementById('tipoJuego');
    const labelCantidad = document.getElementById('labelCantidadNumeros');
    const cantidadNumerosInput = document.getElementById('cantidadNumeros');
    const modoCalculo = document.getElementById('modoCalculo');
    const campoMartingala = document.getElementById('campoMartingala');
    const btnNuevoCalculo = document.getElementById('btnNuevoCalculo');
    const sectionResultado = document.getElementById('resultado');

    function actualizarEtiqueta() {
      let selected = selectJuego.options[selectJuego.selectedIndex];
      if (!selected) selected = selectJuego.options[0];
      
      let nombre = selected.getAttribute('data-nombre') || 'Quiniela';
      
      // Reglas de pluralización fijas
      if (nombre === 'Quiniela') nombre = 'Quinielas';
      else if (nombre === 'Palé') nombre = 'Palés';
      else if (nombre === 'Súper Palé') nombre = 'Súper Palés';
      else if (nombre === 'Tripleta') nombre = 'Tripletas';
      
      labelCantidad.textContent = `Cantidad de ${nombre}`;
    }

    selectJuego.addEventListener('change', actualizarEtiqueta);
    cantidadNumerosInput.addEventListener('input', actualizarEtiqueta);

    modoCalculo.addEventListener('change', () => {
      if(modoCalculo.value === 'martingala') {
        campoMartingala.classList.remove('hidden');
        document.getElementById('martingala').required = true;
      } else {
        campoMartingala.classList.add('hidden');
        document.getElementById('martingala').required = false;
      }
    });

    const formulario = document.getElementById('formulario');
    const formato = num => num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    const formatoConPeso = num => `$${formato(num)}`;

    // Manejo de Persistencia en Caché
    function guardarDatos() {
      try {
        const datos = {
          dias: document.getElementById('dias').value,
          tipoJuego: selectJuego.value,
          martingala: document.getElementById('martingala').value,
          cantidadNumeros: cantidadNumerosInput.value,
          cantidadLoterias: document.getElementById('cantidadLoterias').value,
          montoInicial: document.getElementById('montoInicial').value,
          modo: modoCalculo.value
        };
        localStorage.setItem('rlabs_lottery_cache', JSON.stringify(datos));
      } catch (e) {
        console.warn('LocalStorage falló:', e);
      }
    }

    function cargarDatos() {
      try {
        const cache = localStorage.getItem('rlabs_lottery_cache');
        if (cache) {
          const datos = JSON.parse(cache);
          if (datos.dias) document.getElementById('dias').value = datos.dias;
          if (datos.tipoJuego) selectJuego.value = datos.tipoJuego;
          if (datos.martingala) document.getElementById('martingala').value = datos.martingala;
          if (datos.cantidadNumeros) cantidadNumerosInput.value = datos.cantidadNumeros;
          if (datos.cantidadLoterias) document.getElementById('cantidadLoterias').value = datos.cantidadLoterias;
          if (datos.montoInicial) document.getElementById('montoInicial').value = datos.montoInicial;
          if (datos.modo) modoCalculo.value = datos.modo;
          
          if(datos.modo === 'martingala') {
            campoMartingala.classList.remove('hidden');
            document.getElementById('martingala').required = true;
          }
        }
      } catch (e) {
        console.warn('LocalStorage falló al cargar:', e);
      }
      actualizarEtiqueta();
    }

    // Escuchar eventos para guardar
    ['dias', 'tipoJuego', 'martingala', 'cantidadNumeros', 'cantidadLoterias', 'montoInicial', 'modoCalculo'].forEach(id => {
      document.getElementById(id).addEventListener('input', guardarDatos);
      document.getElementById(id).addEventListener('change', guardarDatos);
    });

    // La IA devuelve HTML por diseño (el prompt se lo pide). Se eliminan los
    // vectores ejecutables antes de insertarlo con innerHTML.
    function sanitizarHTML(html) {
      const plantilla = document.createElement('template');
      plantilla.innerHTML = html;
      plantilla.content.querySelectorAll('script, style, iframe, object, embed, link, form').forEach(n => n.remove());
      plantilla.content.querySelectorAll('*').forEach(el => {
        [...el.attributes].forEach(attr => {
          const nombre = attr.name.toLowerCase();
          const valor = attr.value.replace(/\s+/g, '').toLowerCase();
          if (nombre.startsWith('on') || valor.startsWith('javascript:') || valor.startsWith('data:text/html')) {
            el.removeAttribute(attr.name);
          }
        });
      });
      return plantilla.innerHTML;
    }

    // Función para invocar la IA en el frontend
    async function solicitarAnalisisIA(inversionFinalTotal, dias, juegoNombre, cantidadLoterias, resultadosCalculados) {
      const aiLoading = document.getElementById('ai-loading');
      const aiContent = document.getElementById('ai-content');
      
      aiLoading.classList.remove('hidden');
      aiContent.classList.add('hidden');
      let invTotalMax = formatoConPeso(inversionFinalTotal);
      
      try {
        // Crear un resumen de todos los días para que la IA tenga el panorama completo
        const analisisDatos = resultadosCalculados.map(d => `Día ${d.dia}: Inversión Acumulada ${formatoConPeso(d.inversionTotal)}, Ganancia Neta ${formatoConPeso(d.gananciaNeta)}`).join('\n');

        const systemPrompt = `Eres un estratega experto y amigable en inversiones. Tu objetivo es dar un resumen MUY BREVE, estructurado y directo.
Habla en Español de Latinoamérica, de forma natural, cercana y clara (sin sonar robótico ni usar dialectos).
INSTRUCCIONES CLAVES:
1. SÉ EXTREMADAMENTE BREVE. Ve directo al punto sin explicaciones largas.
2. Analiza los datos de todos los días que recibirás. Recuerda que el usuario solo necesita ganar UNA VEZ en cualquier día para que el ciclo sea un éxito y se reinicie.
3. SI LA TABLA MUESTRA PÉRDIDAS (números negativos) en casi todos los días: Advierte al usuario amigablemente que la configuración no es rentable y que ajuste los parámetros.
4. SI LA TABLA ES RENTABLE: Valida la estrategia. Muestra una sección llamada "Análisis Inteligente" eligiendo UN DÍA ALEATORIO de los datos para ilustrar cuánto invertiría y cuánto ganaría neto si acierta ese día (destaca que recupera su inversión y gana).
5. Incluye una lista con "Los 3 Mejores Momentos para Ganar" (los días con la mejor ganancia neta o mejor retorno).
6. Cierra con una breve "Conclusión" motivadora pero realista.
7. REGLA DE ESCRITURA: NUNCA uses la letra "x" para multiplicadores (di "14 veces").
8. REGLA DE ESCRITURA: Si hablas de retorno, usa porcentajes (ej. "299%").
9. REGLA DE ESCRITURA: NO uses decimales en los montos de dinero.
10. Devuelve tu respuesta en HTML básico (usa <b>, <i>, <br>, y <span> con clases Tailwind 'text-gold-400 font-bold', 'text-teal-400 font-bold' o 'text-red-500'). Solo el HTML, sin markdown extra.`;

        const userPrompt = `Plan de ${dias} días. Juego: ${juegoNombre}. Loterías: ${cantidadLoterias}. Inversión máxima del plan: ${invTotalMax}.
Escenarios exactos por día (Inversión acumulada vs Ganancia Neta si se gana ese día):
${analisisDatos}

Por favor, genera tu análisis siguiendo las instrucciones. Si es rentable, dame el ejemplo aleatorio, los 3 mejores momentos y la conclusión. Si es pura pérdida, adviérteme.`;

        // La clave de OpenRouter vive en el servidor (api_ia.php), no aquí.
        const response = await fetch("api_ia.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            "model": "nvidia/nemotron-3-ultra-550b-a55b:free",
            "models": ["nvidia/nemotron-3-ultra-550b-a55b:free", "minimax/minimax-m3:free", "nvidia/llama-3.1-nemotron-70b-instruct:free"],
            "messages": [
              { "role": "system", "content": systemPrompt },
              { "role": "user", "content": userPrompt }
            ]
          })
        });

        const data = await response.json();
        
        aiLoading.classList.add('hidden');
        if (data && data.content) {
          let aiMessage = data.content;
          aiMessage = aiMessage.replace(/\`\`\`html/g, '').replace(/\`\`\`/g, '');
          aiContent.innerHTML = sanitizarHTML(aiMessage);
          aiContent.classList.remove('hidden');
          aiContent.classList.add('animate-fade-in-up');
        } else {
          throw new Error("Respuesta inválida de la API");
        }
      } catch (e) {
        console.error('Error al generar predicción:', e);
        aiLoading.classList.add('hidden');
        aiContent.innerHTML = `<span class="text-red-400">Error de conexión con la IA. La estrategia requiere ${invTotalMax} de inversión máxima. Sigue la tabla y ten paciencia.</span>`;
        aiContent.classList.remove('hidden');
      }
    }

    function nuevoCalculo() {
      formulario.reset();
      localStorage.removeItem('rlabs_lottery_cache');
      actualizarEtiqueta();
      campoMartingala.classList.add('hidden');
      sectionResultado.classList.add('hidden');
      sectionResultado.classList.remove('opacity-100');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    btnNuevoCalculo.addEventListener('click', nuevoCalculo);

    formulario.addEventListener('submit', e => {
      e.preventDefault();

      // Forzamos guardar los datos justo al ejecutar
      guardarDatos();

      const datos = {
        dias: +document.getElementById('dias').value,
        tipoJuego: +selectJuego.value,
        juegoNombre: selectJuego.options[selectJuego.selectedIndex].getAttribute('data-nombre'),
        martingala: +document.getElementById('martingala').value,
        montoInicial: +document.getElementById('montoInicial').value,
        cantidadNumeros: +cantidadNumerosInput.value,
        cantidadLoterias: +document.getElementById('cantidadLoterias').value,
        modo: modoCalculo.value
      };

      let inversionTotal = 0;
      let gananciaInicialNeta = (datos.tipoJuego * datos.montoInicial) - (datos.montoInicial * datos.cantidadNumeros * datos.cantidadLoterias);
      let gananciaNetaFinal = 0;

      let tablaHTML = '';
      let resultadosCalculados = [];

      for (let i = 1; i <= datos.dias; i++) {
        let montoJugada = datos.montoInicial;
        let inversionDia, gananciaBruta, gananciaNeta, gananciaEsperada, diferencia;

        if (datos.modo === 'martingala') {
          montoJugada = datos.montoInicial * Math.pow(datos.martingala, i - 1);
          inversionDia = montoJugada * datos.cantidadNumeros * datos.cantidadLoterias;
          inversionTotal += inversionDia;
          gananciaBruta = datos.tipoJuego * montoJugada;
          gananciaNeta = gananciaBruta - inversionTotal;
          gananciaEsperada = '-';
          diferencia = '-';
          gananciaNetaFinal = gananciaNeta;
        } else {
          gananciaEsperada = gananciaInicialNeta * i;
          let mejorAjuste = { monto: 0, diferencia: Infinity, inversion: 0, gananciaNeta: 0 };
          const incremento = (datos.montoInicial % 1 === 0) ? 1 : 0.01;
          
          while (true) {
            let inversionTemp = montoJugada * datos.cantidadNumeros * datos.cantidadLoterias;
            let gananciaBrutaTemp = datos.tipoJuego * montoJugada;
            let gananciaNetaTemp = gananciaBrutaTemp - (inversionTotal + inversionTemp);
            let diferenciaTemp = Math.abs(gananciaNetaTemp - gananciaEsperada);
            
            if (diferenciaTemp < mejorAjuste.diferencia) {
              mejorAjuste = { monto: montoJugada, diferencia: diferenciaTemp, inversion: inversionTemp, gananciaNeta: gananciaNetaTemp };
            } else {
              break;
            }
            montoJugada = +(montoJugada + incremento).toFixed(2);
          }
          
          montoJugada = mejorAjuste.monto;
          inversionDia = mejorAjuste.inversion;
          inversionTotal += inversionDia;
          gananciaBruta = datos.tipoJuego * montoJugada;
          gananciaNeta = mejorAjuste.gananciaNeta;
          gananciaNetaFinal = gananciaNeta;

          const difVal = gananciaNeta - gananciaEsperada;
          if (Math.abs(difVal) < 0.01) {
            diferencia = "Exactamente lo esperado";
          } else {
            let textoDif = difVal > 0 ? "por encima de lo esperado" : "por debajo de lo esperado";
            diferencia = `Diferencia: <strong class="${difVal >= 0 ? 'text-green-400' : 'text-red-400'}">${formatoConPeso(Math.abs(difVal))}</strong> ${textoDif}`;
          }
        }

        // Guardar resultado de este día en el array para que la IA elija un ejemplo random
        resultadosCalculados.push({
          dia: i,
          inversionTotal: inversionTotal,
          gananciaNeta: gananciaNeta
        });

        // Card por día
        tablaHTML += `
          <div class="glass-panel p-6 rounded-2xl relative group hover:-translate-y-1 transition-all duration-300 hover:shadow-[0_10px_40px_rgba(45,212,191,0.15)] animate-fade-in-up" style="animation-delay: ${i * 50}ms">
            
            <!-- Glow Effect on Hover -->
            <div class="absolute inset-0 bg-gradient-to-br from-neon-teal/5 to-neon-purple/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl pointer-events-none"></div>
            
            <div class="flex justify-between items-center mb-4 border-b border-white/10 pb-3">
              <h3 class="text-2xl font-display font-bold text-white">Día <span class="text-neon-teal">${i}</span></h3>
              <span class="px-3 py-1 text-xs font-bold uppercase tracking-widest bg-white/5 border border-white/10 rounded-full text-gold-400">
                Apostar ${formatoConPeso(montoJugada)}
              </span>
            </div>
            
            <ul class="space-y-3 text-sm text-gray-400">
              <li class="flex justify-between items-center"><span class="font-medium">Inversión Hoy:</span> <span class="text-gray-200">${formatoConPeso(inversionDia)}</span></li>
              <li class="flex justify-between items-center"><span class="font-medium">Inversión Total:</span> <span class="text-gray-200">${formatoConPeso(inversionTotal)}</span></li>
              <li class="flex justify-between items-center pt-2 border-t border-white/5"><span class="font-medium text-neon-teal">Ganancia Bruta:</span> <span class="text-white font-bold">${formatoConPeso(gananciaBruta)}</span></li>
              <li class="flex justify-between items-center"><span class="font-medium text-neon-purple">Ganancia Neta:</span> <span class="text-white font-bold">${formatoConPeso(gananciaNeta)}</span></li>
              ${datos.modo !== 'martingala' ? `
                <li class="flex justify-between items-center pt-2 border-t border-white/5 text-xs"><span class="font-medium opacity-70">Esperada:</span> <span>${formatoConPeso(gananciaEsperada)}</span></li>
                <li class="text-center text-xs pt-1 opacity-80">${diferencia}</li>
              ` : ''}
            </ul>
          </div>
        `;
      }

      const resumenHTML = `Capital mínimo requerido: <strong class="text-gold-400 font-display">${formatoConPeso(inversionTotal)}</strong><br><span class="text-sm text-gray-400">Proyección para ${datos.dias} días de juego continuo.</span>`;

      document.getElementById('resumenCapital').innerHTML = resumenHTML;
      document.getElementById('tablaResultados').innerHTML = tablaHTML;
      
      // Mostrar sección
      sectionResultado.classList.remove('hidden');
      setTimeout(() => {
        sectionResultado.classList.add('opacity-100');
        sectionResultado.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 50);

      // Llamar a la IA con los datos calculados
      solicitarAnalisisIA(inversionTotal, datos.dias, datos.juegoNombre, datos.cantidadLoterias, resultadosCalculados);
    });

    // Cargar datos cacheados al iniciar
    document.addEventListener('DOMContentLoaded', cargarDatos);
  </script>
</body>
</html>
