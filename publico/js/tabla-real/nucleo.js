// Núcleo de la tabla real (landing "Ver" de Peticiones y pestaña de distribución de Análisis): estado compartido
// entre la vista Tabla (tabla.js) y la vista Gráficos (graficos.js) — filas, buscar, filtros por
// columna, conmutador de vistas y utilidades numéricas. Cada vista se registra con
// TablaReal.registrarVista() al cargar su script; en DOMContentLoaded el núcleo arma el contexto
// (ctx) y llama a cada vista en el orden en que se registraron. Orden de carga:
// nucleo.js → tabla.js → graficos.js (ver vista/peticiones/tipo-detalle.php y
// vista/analisis/parciales/tabla-modulo.php).
(function () {
    var vistasRegistradas = [];

    window.TablaReal = {
        registrarVista: function (nombre, init) {
            vistasRegistradas.push({ nombre: nombre, init: init });
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        var tabla = document.querySelector('.tabla-dev-datos[data-origen]');

        if (!tabla) {
            return;
        }

        var namespace = (typeof tdtNamespace !== 'undefined' && tdtNamespace) ? tdtNamespace : 'default';
        var columnas = (typeof tdtColumnas !== 'undefined' && tdtColumnas) ? tdtColumnas : [];
        var contenedorScroll = tabla.closest('.tabla-scroll');

        // --- Bus mínimo entre vistas: 'filtros', 'columnas', 'vista', 'antes-cambio-manual', 'listo'.
        // Los oyentes se llaman en el orden en que se suscribieron (tabla.js antes que graficos.js). ---
        var oyentes = {};

        function on(evento, fn) {
            (oyentes[evento] = oyentes[evento] || []).push(fn);
        }

        function emitir(evento, dato) {
            (oyentes[evento] || []).forEach(function (fn) { fn(dato); });
        }

        // --- Filas: llegan como JSON (#tdt-filas) y cada <tr> se crea fuera del DOM. La vista Tabla
        // adjunta al <tbody> solo las de la ventana visible; filtros, orden, selección, totales y
        // gráficas trabajan con el arreglo completo. ---
        var filasOriginales = construirFilasDesdeDatos();

        function construirFilasDesdeDatos() {
            var islaDatos = document.getElementById('tdt-filas');
            var datos = [];
            if (islaDatos) {
                try { datos = JSON.parse(islaDatos.textContent || '[]'); } catch (e) { datos = []; }
            }

            return datos.map(function (dato) {
                var fila = document.createElement('tr');
                fila.dataset.origenId = dato.id;
                fila.dataset.rutaEditar = dato.editar;
                fila.dataset.puedeEditar = dato.puede ? '1' : '0';
                if (dato.resaltada) { fila.classList.add('fila-resaltada'); }

                var celdaSeleccion = document.createElement('td');
                celdaSeleccion.className = 'col-seleccion';
                var casilla = document.createElement('input');
                casilla.type = 'checkbox';
                casilla.className = 'tabla-seleccion-fila';
                celdaSeleccion.appendChild(casilla);
                fila.appendChild(celdaSeleccion);

                dato.celdas.forEach(function (texto) {
                    var celda = document.createElement('td');
                    celda.textContent = texto;
                    fila.appendChild(celda);
                });

                return fila;
            });
        }

        // --- Utilidades compartidas por ambas vistas ---
        function parseNumeroCeldaTdt(texto) {
            var limpio = String(texto).replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.');
            var numero = parseFloat(limpio);
            return isNaN(numero) ? 0 : numero;
        }

        function formatoMonedaTdt(valor) {
            return '$' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Texto de celda que cuenta como número (valores y cantidades): mismo criterio para decidir
        // qué columnas se totalizan (tabla) y cuáles sirven de dimensión (gráficas, invertido).
        function esTextoNumerico(texto) {
            return /^[\d.,\s$%-]+$/.test(texto);
        }

        function indiceColumnaTdt(nombre) {
            return columnas.findIndex(function (c) { return c.toLowerCase() === nombre.toLowerCase(); });
        }

        // Una columna oculta con el visor de columnas tiene su <col> en visibility: collapse; sus
        // gráficas también se ocultan (y no cuentan para la paginación).
        function columnaVisibleTdt(indice) {
            var col = document.getElementById('tdt-col-' + indice);
            return !col || col.style.visibility !== 'collapse';
        }

        function filasVisiblesParaGrafica() {
            return filasOriginales
                .filter(function (fila) { return !fila.classList.contains('fila-oculta-filtro'); })
                .map(function (fila) {
                    return Array.prototype.slice.call(fila.children, 1).map(function (td) { return td.textContent.trim(); });
                });
        }

        // --- Combobox propio (buscar/filtrar) ---
        function crearCombobox(envoltorio, valores, desplegable) {
            var input = envoltorio.querySelector('.combo-input');
            var fantasma = envoltorio.querySelector('.combo-fantasma');
            var tarjeta = envoltorio.querySelector('.combo-tarjeta');
            var tecleado = document.createElement('span');
            var sugerenciaSpan = document.createElement('span');
            tecleado.className = 'combo-fantasma-tecleado';
            sugerenciaSpan.className = 'combo-fantasma-sugerencia';
            fantasma.appendChild(tecleado);
            fantasma.appendChild(sugerenciaSpan);

            function sugerenciaPara(texto) {
                if (texto === '') {
                    return '';
                }
                var textoMin = texto.toLowerCase();
                var encontrado = valores.find(function (v) {
                    return v.length > texto.length && v.toLowerCase().indexOf(textoMin) === 0;
                });
                return encontrado ? encontrado.slice(texto.length) : '';
            }

            function actualizarFantasma() {
                tecleado.textContent = input.value;
                sugerenciaSpan.textContent = sugerenciaPara(input.value);
            }

            function cerrarTarjeta() {
                if (tarjeta) {
                    tarjeta.classList.remove('abierta');
                }
            }

            function renderTarjeta() {
                if (!tarjeta) {
                    return;
                }

                var textoMin = input.value.toLowerCase();
                var coincidencias = valores.filter(function (v) { return v.toLowerCase().indexOf(textoMin) !== -1; });

                tarjeta.innerHTML = '';

                if (coincidencias.length === 0) {
                    var vacio = document.createElement('div');
                    vacio.className = 'combo-tarjeta-vacio';
                    vacio.textContent = 'Sin coincidencias';
                    tarjeta.appendChild(vacio);
                    return;
                }

                coincidencias.slice(0, 50).forEach(function (valor) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'combo-tarjeta-item';
                    item.textContent = valor;
                    item.addEventListener('mousedown', function (evento) {
                        evento.preventDefault();
                        input.value = valor;
                        actualizarFantasma();
                        cerrarTarjeta();
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    tarjeta.appendChild(item);
                });
            }

            input.addEventListener('input', function () {
                actualizarFantasma();
                if (desplegable && tarjeta) {
                    tarjeta.classList.add('abierta');
                    renderTarjeta();
                }
            });

            input.addEventListener('keydown', function (evento) {
                var sugerencia = sugerenciaSpan.textContent;
                if (sugerencia && (evento.key === 'Tab' || evento.key === 'ArrowRight') && input.selectionStart === input.value.length) {
                    evento.preventDefault();
                    input.value += sugerencia;
                    actualizarFantasma();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                } else if (evento.key === 'Escape') {
                    cerrarTarjeta();
                }
            });

            if (desplegable && tarjeta) {
                input.addEventListener('click', function () { renderTarjeta(); tarjeta.classList.add('abierta'); });
                input.addEventListener('focus', function () { renderTarjeta(); tarjeta.classList.add('abierta'); });
                document.addEventListener('click', function (evento) {
                    if (!envoltorio.contains(evento.target)) {
                        cerrarTarjeta();
                    }
                });
            }

            envoltorio.actualizarFantasmaCombo = actualizarFantasma;
            actualizarFantasma();
        }

        function valoresUnicosColumna(indice) {
            var valores = {};
            filasOriginales.forEach(function (fila) {
                var celda = fila.children[indice + 1];
                if (celda && celda.textContent.trim() !== '') {
                    valores[celda.textContent.trim()] = true;
                }
            });
            return Object.keys(valores).sort(function (a, b) { return a.localeCompare(b, 'es'); });
        }

        tabla.querySelectorAll('.combo-filtro-columna').forEach(function (envoltorio) {
            crearCombobox(envoltorio, valoresUnicosColumna(parseInt(envoltorio.dataset.indice, 10)), true);
        });

        var comboBuscar = document.getElementById('tdt-combo-buscar');
        if (comboBuscar) {
            var valoresTodos = {};
            columnas.forEach(function (c, i) { valoresUnicosColumna(i).forEach(function (v) { valoresTodos[v] = true; }); });
            crearCombobox(comboBuscar, Object.keys(valoresTodos), false);
        }

        // --- Filtros por columna (ocultos hasta pulsar "Filtrar") y búsqueda general ---
        var filaFiltros = tabla.querySelector('tr.fila-filtros');
        var botonFiltrar = document.getElementById('tdt-boton-filtrar');
        if (botonFiltrar && filaFiltros) {
            botonFiltrar.addEventListener('click', function () {
                var mostrar = !filaFiltros.classList.contains('visible');
                filaFiltros.classList.toggle('visible', mostrar);
                botonFiltrar.classList.toggle('activo', mostrar);
                if (!mostrar) {
                    filaFiltros.querySelectorAll('.combo-filtro-columna').forEach(function (envoltorioFiltro) {
                        var campo = envoltorioFiltro.querySelector('.combo-input');
                        campo.value = '';
                        if (envoltorioFiltro.actualizarFantasmaCombo) { envoltorioFiltro.actualizarFantasmaCombo(); }
                    });
                    aplicarFiltros();
                }
            });
        }

        var botonBuscar = document.getElementById('tdt-boton-buscar');
        var envoltorioBuscar = document.querySelector('.buscar-envoltorio');
        var campoBuscar = document.getElementById('tdt-campo-buscar');

        if (botonBuscar && envoltorioBuscar && campoBuscar) {
            botonBuscar.addEventListener('click', function () {
                var desplegar = !envoltorioBuscar.classList.contains('desplegado');
                envoltorioBuscar.classList.toggle('desplegado', desplegar);
                botonBuscar.classList.toggle('activo', desplegar);
                if (desplegar) {
                    campoBuscar.focus();
                } else {
                    campoBuscar.value = '';
                    if (comboBuscar && comboBuscar.actualizarFantasmaCombo) { comboBuscar.actualizarFantasmaCombo(); }
                    aplicarFiltros();
                }
            });
            campoBuscar.addEventListener('input', aplicarFiltros);
        }

        var filtros = tabla.querySelectorAll('.filtro-columna');

        // Predicados que agregan las vistas además de filtros y búsqueda (ej. el clic en una barra de
        // la gráfica, clave 'grafica'): {clave: fn(fila) -> bool}.
        var filtrosExtra = {};

        function fijarFiltroExtra(clave, cumple) {
            if (cumple) {
                filtrosExtra[clave] = cumple;
            } else {
                delete filtrosExtra[clave];
            }
        }

        function aplicarFiltros() {
            var activos = Array.prototype.map.call(filtros, function (campo) {
                return { indice: parseInt(campo.dataset.indice, 10), valor: campo.value.trim().toLowerCase() };
            }).filter(function (f) { return f.valor !== ''; });

            var busqueda = campoBuscar ? campoBuscar.value.trim().toLowerCase() : '';
            var extras = Object.keys(filtrosExtra).map(function (clave) { return filtrosExtra[clave]; });

            filasOriginales.forEach(function (fila) {
                var cumpleColumnas = activos.every(function (filtro) {
                    var texto = fila.children[filtro.indice + 1].textContent.toLowerCase();
                    return texto.indexOf(filtro.valor) !== -1;
                });
                var cumpleBusqueda = busqueda === '' || fila.textContent.toLowerCase().indexOf(busqueda) !== -1;
                var cumpleExtras = extras.every(function (cumple) { return cumple(fila); });
                fila.classList.toggle('fila-oculta-filtro', !(cumpleColumnas && cumpleBusqueda && cumpleExtras));
            });

            emitir('filtros');
        }

        filtros.forEach(function (campo) { campo.addEventListener('input', aplicarFiltros); });

        // --- Conmutador de vistas (tabla / gráfica) ---
        var vistaActual = 'tabla';
        var botonVistaTabla = document.getElementById('tdt-boton-vista-tabla');
        var botonVistaGrafica = document.getElementById('tdt-boton-vista-grafica');

        function marcarBoton(boton, activo) {
            if (!boton) { return; }
            boton.classList.toggle('activo', activo);
            boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
        }

        function activarVista(nombre) {
            vistaActual = nombre === 'grafica' ? 'grafica' : 'tabla';
            var esGrafica = vistaActual === 'grafica';
            tabla.classList.toggle('modo-grafica', esGrafica);
            marcarBoton(botonVistaTabla, !esGrafica);
            marcarBoton(botonVistaGrafica, esGrafica);

            // La gráfica se filtra con los mismos combos por columna que la tabla; acá solo forzamos
            // que la fila arranque visible al entrar a ese modo — el botón "Filtrar" sigue habilitado
            // para poder ocultarla igual que en modo tabla.
            if (esGrafica) {
                if (filaFiltros) { filaFiltros.classList.add('visible'); }
                if (botonFiltrar) { botonFiltrar.classList.add('activo'); }
            }

            emitir('vista', vistaActual);
        }

        // Cambiar de vista a mano avisa antes ('antes-cambio-manual'), para que graficos.js descarte
        // el filtro de la barra (si no, la gráfica saldría recortada a una sola categoría sin aviso).
        if (botonVistaTabla) {
            botonVistaTabla.addEventListener('click', function () {
                emitir('antes-cambio-manual', 'tabla');
                activarVista('tabla');
            });
        }
        if (botonVistaGrafica) {
            botonVistaGrafica.addEventListener('click', function () {
                emitir('antes-cambio-manual', 'grafica');
                activarVista('grafica');
            });
        }

        var ctx = {
            tabla: tabla,
            namespace: namespace,
            columnas: columnas,
            contenedorScroll: contenedorScroll,
            filaFiltros: filaFiltros,
            filasOriginales: filasOriginales,
            on: on,
            emitir: emitir,
            aplicarFiltros: aplicarFiltros,
            fijarFiltroExtra: fijarFiltroExtra,
            activarVista: activarVista,
            vistaActiva: function () { return vistaActual; },
            parseNumeroCeldaTdt: parseNumeroCeldaTdt,
            formatoMonedaTdt: formatoMonedaTdt,
            esTextoNumerico: esTextoNumerico,
            indiceColumnaTdt: indiceColumnaTdt,
            columnaVisibleTdt: columnaVisibleTdt,
            filasVisiblesParaGrafica: filasVisiblesParaGrafica
        };

        vistasRegistradas.forEach(function (vista) { vista.init(ctx); });

        emitir('listo');
    });
}());
