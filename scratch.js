
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
    const formato = num => num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

    // Función para invocar la IA en el backend PHP
    async function solicitarAnalisisIA(inversionTotal, dias, gananciaNetaFinal, juegoNombre, cantidadLoterias) {
      const aiLoading = document.getElementById('ai-loading');
      const aiContent = document.getElementById('ai-content');
      
      aiLoading.classList.remove('hidden');
      aiContent.classList.add('hidden');

      try {
        const response = await fetch('ia_prediccion.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            inversionTotal,
            dias,
            gananciaNetaFinal,
            juegoNombre,
            cantidadLoterias
          })
        });

        const data = await response.json();
        
        aiLoading.classList.add('hidden');
        if (data.success) {
          aiContent.innerHTML = data.analisis_html;
          aiContent.classList.remove('hidden');
          aiContent.classList.add('animate-fade-in-up');
        } else {
          aiContent.innerHTML = `<span class="text-red-400">Error en el motor IA: ${data.error}</span>`;
          aiContent.classList.remove('hidden');
        }
      } catch (err) {
        aiLoading.classList.add('hidden');
        aiContent.innerHTML = `<span class="text-red-400">No se pudo conectar con el servidor IA. ${err.message}</span>`;
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

      // Llamar a la IA
      solicitarAnalisisIA(inversionTotal, datos.dias, gananciaNetaFinal, datos.juegoNombre, datos.cantidadLoterias);
    });

    // Cargar datos cacheados al iniciar
    document.addEventListener('DOMContentLoaded', cargarDatos);
  