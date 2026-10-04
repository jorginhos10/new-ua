// Landing único de "Ver" en Peticiones (peticiones-tipo-detalle): mecánica portada literalmente
// del prototipo "Dev > Tabla" (vista/dev/pruebas/tabla.php) — buscar, filtrar por columna, ordenar,
// ocultar/redimensionar columnas, vista de gráfica — generalizada para cualquier origen/estado, con
// Editar/Eliminar reales (POST al mismo controlador) en vez del demo en memoria del prototipo.
document.addEventListener('DOMContentLoaded', function () {
    var tabla = document.querySelector('.tabla-dev-datos[data-origen]');

    if (!tabla) {
        return;
    }

    var namespace = (typeof tdtNamespace !== 'undefined' && tdtNamespace) ? tdtNamespace : 'default';
    var claveAnchos = 'peticiones_tabla_anchos_' + namespace;
    var claveOcultas = 'peticiones_tabla_ocultas_' + namespace;

    var cuerpo = tabla.querySelector('tbody');
    var filasOriginales = Array.prototype.slice.call(cuerpo.querySelectorAll('tr'));

    // --- Desplazarse hasta la fila resaltada (el ítem sobre el que se pulsó "Ver") ---
    var filaResaltada = cuerpo.querySelector('tr.fila-resaltada');
    if (filaResaltada) {
        filaResaltada.scrollIntoView({ block: 'center' });
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
            if (desplegable) {
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

        if (desplegable) {
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
        (tdtColumnas || []).forEach(function (c, i) { valoresUnicosColumna(i).forEach(function (v) { valoresTodos[v] = true; }); });
        crearCombobox(comboBuscar, Object.keys(valoresTodos), false);
    }

    // --- Redimensionar columnas (ancho persistido por tabla/origen/estado) ---
    function leerAnchosGuardados() {
        try { return JSON.parse(localStorage.getItem(claveAnchos) || '{}'); } catch (e) { return {}; }
    }
    function guardarAncho(indice, ancho) {
        try {
            var anchos = leerAnchosGuardados();
            anchos[indice] = ancho;
            localStorage.setItem(claveAnchos, JSON.stringify(anchos));
        } catch (e) { /* localStorage no disponible */ }
    }

    function celdasDeColumna(indice) {
        var celdas = [];
        var encabezado = tabla.querySelector('.encabezado-columna[data-indice="' + indice + '"]');
        var filtro = tabla.querySelector('.combo-filtro-columna[data-indice="' + indice + '"]');
        if (encabezado) { celdas.push(encabezado.closest('th')); }
        if (filtro) { celdas.push(filtro.closest('th')); }
        cuerpo.querySelectorAll('tr td:nth-child(' + (parseInt(indice, 10) + 2) + ')').forEach(function (c) { celdas.push(c); });
        return celdas;
    }

    function fijarAnchoColumna(indice, anchoPx) {
        var col = document.getElementById('tdt-col-' + indice);
        if (col) { col.style.width = anchoPx + 'px'; }
        celdasDeColumna(indice).forEach(function (celda) {
            celda.style.width = anchoPx + 'px';
            celda.style.maxWidth = anchoPx + 'px';
        });
    }

    var anchosGuardados = leerAnchosGuardados();
    Object.keys(anchosGuardados).forEach(function (indice) {
        fijarAnchoColumna(indice, anchosGuardados[indice]);
    });

    // --- Ancho de la tabla: si las columnas visibles no llenan el contenedor, reparte el espacio
    // sobrante entre ellas (proporcional a su ancho) para no dejar zona muerta a la derecha; si no
    // caben, no toca nada y aparece el scroll horizontal normal de .tabla-scroll. "anchosBase"
    // guarda el ancho de cada columna ANTES de repartir (el guardado/por defecto), para no ir
    // agrandándolas de más cada vez que se recalcula (el CSS 'width: 100%' no sirve para esto:
    // con table-layout fixed los navegadores no liberan el espacio de una columna oculta con
    // visibility: collapse). ---
    var anchosBase = {};
    tabla.querySelectorAll('colgroup col[id^="tdt-col-"]').forEach(function (col) {
        var indice = col.id.replace('tdt-col-', '');
        anchosBase[indice] = parseInt(anchosGuardados[indice], 10) || parseInt(col.style.width, 10) || 90;
    });
    var contenedorScroll = tabla.closest('.tabla-scroll');
    var anchoColSeleccion = 34;

    function ajustarAnchoTabla() {
        if (!contenedorScroll) { return; }
        var indicesVisibles = Object.keys(anchosBase).filter(function (indice) {
            var col = document.getElementById('tdt-col-' + indice);
            return col && col.style.visibility !== 'collapse';
        });
        var sumaBase = indicesVisibles.reduce(function (total, indice) { return total + anchosBase[indice]; }, 0);
        if (sumaBase === 0) { return; }
        var disponible = contenedorScroll.clientWidth - anchoColSeleccion;
        var factor = disponible > sumaBase ? disponible / sumaBase : 1;
        indicesVisibles.forEach(function (indice) {
            fijarAnchoColumna(indice, Math.round(anchosBase[indice] * factor));
        });
    }
    window.addEventListener('resize', ajustarAnchoTabla);

    tabla.querySelectorAll('.redimensionador-columna').forEach(function (manija) {
        var col = document.getElementById('tdt-col-' + manija.dataset.indice);
        if (!col) { return; }

        manija.addEventListener('mousedown', function (evento) {
            evento.preventDefault();
            var xInicial = evento.clientX;
            var anchoInicial = col.getBoundingClientRect().width;
            manija.classList.add('redimensionando');
            document.body.classList.add('tabla-redimensionando-cursor');
            var anchoFinal = anchoInicial;

            function mover(eventoMover) {
                anchoFinal = Math.max(40, Math.round(anchoInicial + (eventoMover.clientX - xInicial)));
                fijarAnchoColumna(manija.dataset.indice, anchoFinal);
            }
            function soltar() {
                manija.classList.remove('redimensionando');
                document.body.classList.remove('tabla-redimensionando-cursor');
                document.removeEventListener('mousemove', mover);
                document.removeEventListener('mouseup', soltar);
                guardarAncho(manija.dataset.indice, anchoFinal);
                anchosBase[manija.dataset.indice] = anchoFinal;
                ajustarAnchoTabla();
            }
            document.addEventListener('mousemove', mover);
            document.addEventListener('mouseup', soltar);
        });
    });

    // --- Ocultar/mostrar columnas ---
    function leerOcultasGuardadas() {
        try { return JSON.parse(localStorage.getItem(claveOcultas) || '[]'); } catch (e) { return []; }
    }
    function guardarOcultas(indices) {
        try { localStorage.setItem(claveOcultas, JSON.stringify(indices)); } catch (e) { /* no disponible */ }
    }

    var botonColumnas = document.getElementById('tdt-boton-columnas');
    var tarjetaColumnas = document.getElementById('tdt-columnas-tarjeta');

    if (botonColumnas && tarjetaColumnas) {
        var casillasColumnas = tarjetaColumnas.querySelectorAll('.columnas-checkbox');

        function aplicarVisibilidadColumna(indice, visible) {
            var col = document.getElementById('tdt-col-' + indice);
            if (col) { col.style.visibility = visible ? '' : 'collapse'; }
        }
        function indicesOcultosActuales() {
            return Array.prototype.filter.call(casillasColumnas, function (c) { return !c.checked; }).map(function (c) { return c.dataset.indice; });
        }

        leerOcultasGuardadas().forEach(function (indice) {
            aplicarVisibilidadColumna(indice, false);
            var casilla = tarjetaColumnas.querySelector('.columnas-checkbox[data-indice="' + indice + '"]');
            if (casilla) { casilla.checked = false; }
        });

        casillasColumnas.forEach(function (casilla) {
            casilla.addEventListener('change', function () {
                aplicarVisibilidadColumna(casilla.dataset.indice, casilla.checked);
                guardarOcultas(indicesOcultosActuales());
                ajustarAnchoTabla();
                actualizarGrafica();
            });
        });

        botonColumnas.addEventListener('click', function (evento) {
            evento.stopPropagation();
            tarjetaColumnas.classList.toggle('abierta');
        });
        document.addEventListener('click', function (evento) {
            if (!botonColumnas.contains(evento.target) && !tarjetaColumnas.contains(evento.target)) {
                tarjetaColumnas.classList.remove('abierta');
            }
        });
    }

    // Recién aquí quedó aplicado el estado inicial completo de columnas ocultas (por defecto desde
    // el servidor y las guardadas en localStorage), así que el primer reparto de ancho se hace acá.
    ajustarAnchoTabla();

    // --- Filtros por columna (ocultos hasta pulsar "Filtrar") ---
    var filaEncabezados = tabla.querySelector('tr.fila-encabezados');
    var filaFiltros = tabla.querySelector('tr.fila-filtros');
    if (filaEncabezados && filaFiltros) {
        var altura = filaEncabezados.getBoundingClientRect().height;
        Array.prototype.forEach.call(filaFiltros.querySelectorAll('th'), function (celda) { celda.style.top = altura + 'px'; });
    }

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

    function aplicarFiltros() {
        var activos = Array.prototype.map.call(filtros, function (campo) {
            return { indice: parseInt(campo.dataset.indice, 10), valor: campo.value.trim().toLowerCase() };
        }).filter(function (f) { return f.valor !== ''; });

        var busqueda = campoBuscar ? campoBuscar.value.trim().toLowerCase() : '';

        filasOriginales.forEach(function (fila) {
            var cumpleColumnas = activos.every(function (filtro) {
                var texto = fila.children[filtro.indice + 1].textContent.toLowerCase();
                return texto.indexOf(filtro.valor) !== -1;
            });
            var cumpleBusqueda = busqueda === '' || fila.textContent.toLowerCase().indexOf(busqueda) !== -1;
            var cumpleGrafica = !filtroGrafica || filtroGrafica.cumple(fila);
            fila.classList.toggle('fila-oculta-filtro', !(cumpleColumnas && cumpleBusqueda && cumpleGrafica));
        });

        actualizarGrafica();
        construirFilaTotales();
    }

    filtros.forEach(function (campo) { campo.addEventListener('input', aplicarFiltros); });

    // --- Orden ---
    tabla.querySelectorAll('.encabezado-columna').forEach(function (encabezado) {
        encabezado.addEventListener('click', function () {
            var indice = parseInt(encabezado.dataset.indice, 10);
            var ascendente = !encabezado.classList.contains('orden-asc');

            tabla.querySelectorAll('.encabezado-columna').forEach(function (otro) { otro.classList.remove('orden-asc', 'orden-desc'); });
            encabezado.classList.add(ascendente ? 'orden-asc' : 'orden-desc');

            var filas = Array.prototype.slice.call(cuerpo.querySelectorAll('tr'));
            filas.sort(function (filaA, filaB) {
                var textoA = filaA.children[indice + 1].textContent.trim();
                var textoB = filaB.children[indice + 1].textContent.trim();
                var numeroA = parseFloat(textoA.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.'));
                var numeroB = parseFloat(textoB.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.'));
                var comparacion;
                if (!isNaN(numeroA) && !isNaN(numeroB) && textoA !== '' && textoB !== '') {
                    comparacion = numeroA - numeroB;
                } else {
                    comparacion = textoA.localeCompare(textoB, 'es');
                }
                return ascendente ? comparacion : -comparacion;
            });
            filas.forEach(function (fila) { cuerpo.appendChild(fila); });
        });
    });

    // --- Vista de gráfica: mismo dashboard de decisión del prototipo Dev (agrupar por cualquier
    // columna de texto y sumar la columna de valor), pero genérico — no asume las columnas de
    // Gasto: la columna de valor se detecta por nombre ("valor"/"total") y las dimensiones son
    // cualquier columna cuyo contenido no sea numérico (detectado por el propio dato, no por el
    // nombre de la columna), así sirve igual para ARL, Monitores, OPS, Otros o Necesidad. ---
    var vistaGraficaActiva = false;
    // Filtro por clic en una barra (ver aplicarFiltroGrafica()): null o
    // { etiqueta, cumple(fila) -> bool, vistaAnterior: { grafica, panel, pagina } }.
    var filtroGrafica = null;
    var PALETA_SERIES_TDT = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
    // Pareto 80/20 solo en estos paneles (pedido explícito) — Dependencia/Sede/Categoría/Riesgo y
    // cualquier otra dimensión auto-detectada quedan igual que hoy, sin este tratamiento.
    var DIMENSIONES_CON_PARETO = ['Rubro', 'Actividad', 'Proyecto PDI'];
    var claveTipoGrafico = 'peticiones_tabla_grafica_tipo_' + namespace;

    // --- Ancho de las descripciones en las gráficas de barras: flexible como las columnas de la
    // tabla, independiente por gráfica (dimensión). Se guarda como {dimensión: px} por namespace y
    // se aplica como variable CSS en el envoltorio de filas de esa gráfica (normal y ampliada). ---
    var claveAnchoEtiquetaGrafica = 'peticiones_tabla_grafica_etiquetas_' + namespace;
    var contenedorGraficaEtiquetas = document.getElementById('tdt-grafica');

    function leerAnchosEtiquetaGrafica() {
        try { return JSON.parse(localStorage.getItem(claveAnchoEtiquetaGrafica) || '{}'); } catch (e) { return {}; }
    }

    function guardarAnchoEtiquetaGrafica(nombreDimension, ancho) {
        var anchos = leerAnchosEtiquetaGrafica();
        if (ancho) {
            anchos[nombreDimension] = ancho;
        } else {
            delete anchos[nombreDimension];
        }
        try { localStorage.setItem(claveAnchoEtiquetaGrafica, JSON.stringify(anchos)); } catch (e) { /* sin localStorage */ }
    }

    function fijarAnchoEtiquetaGrafica(envoltorio, ancho) {
        if (ancho) {
            envoltorio.style.setProperty('--grafica-ancho-etiqueta', ancho + 'px');
        } else {
            envoltorio.style.removeProperty('--grafica-ancho-etiqueta');
        }
    }

    if (contenedorGraficaEtiquetas) {
        contenedorGraficaEtiquetas.addEventListener('mousedown', function (evento) {
            var manija = evento.target.closest('.grafica-redimensionador-etiqueta');
            if (!manija) { return; }
            var envoltorio = manija.parentNode;
            var etiquetaReferencia = envoltorio.querySelector('.grafica-barra-etiqueta');
            if (!etiquetaReferencia) { return; }
            evento.preventDefault();
            evento.stopPropagation();
            var xInicial = evento.clientX;
            var anchoInicial = etiquetaReferencia.getBoundingClientRect().width;
            // Tope: que siempre quede espacio para la barra y la cifra.
            var anchoMaximo = Math.max(60, envoltorio.clientWidth - 220);
            var anchoFinal = anchoInicial;
            manija.classList.add('redimensionando');
            document.body.classList.add('tabla-redimensionando-cursor');

            function mover(eventoMover) {
                anchoFinal = Math.max(60, Math.min(anchoMaximo, Math.round(anchoInicial + (eventoMover.clientX - xInicial))));
                fijarAnchoEtiquetaGrafica(envoltorio, anchoFinal);
            }
            function soltar() {
                manija.classList.remove('redimensionando');
                document.body.classList.remove('tabla-redimensionando-cursor');
                document.removeEventListener('mousemove', mover);
                document.removeEventListener('mouseup', soltar);
                guardarAnchoEtiquetaGrafica(envoltorio.dataset.dimension, anchoFinal);
            }
            document.addEventListener('mousemove', mover);
            document.addEventListener('mouseup', soltar);
        });

        // Doble clic en la manija: restablece el ancho de esa gráfica (y no amplía el panel).
        contenedorGraficaEtiquetas.addEventListener('dblclick', function (evento) {
            var manija = evento.target.closest('.grafica-redimensionador-etiqueta');
            if (!manija) { return; }
            evento.stopPropagation();
            fijarAnchoEtiquetaGrafica(manija.parentNode, 0);
            guardarAnchoEtiquetaGrafica(manija.parentNode.dataset.dimension, 0);
        }, true);
    }

    function colorSerieTdt(indice) {
        return PALETA_SERIES_TDT[indice % PALETA_SERIES_TDT.length];
    }

    var tipoGraficoPorDimension = (function () {
        try { return JSON.parse(localStorage.getItem(claveTipoGrafico) || '{}'); } catch (e) { return {}; }
    }());

    function leerTipoGrafico(nombreDimension) {
        return tipoGraficoPorDimension[nombreDimension] === 'torta' ? 'torta' : 'barras';
    }

    function fijarTipoGrafico(nombreDimension, tipo) {
        tipoGraficoPorDimension[nombreDimension] = tipo;
        try { localStorage.setItem(claveTipoGrafico, JSON.stringify(tipoGraficoPorDimension)); } catch (e) { /* no disponible */ }
    }

    function parseNumeroCeldaTdt(texto) {
        var limpio = String(texto).replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.');
        var numero = parseFloat(limpio);
        return isNaN(numero) ? 0 : numero;
    }

    function formatoMonedaTdt(valor) {
        return '$' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function indiceColumnaTdt(nombre) {
        return tdtColumnas.findIndex(function (c) { return c.toLowerCase() === nombre.toLowerCase(); });
    }

    // Una columna oculta con el visor de columnas tiene su <col> en visibility: collapse; sus
    // gráficas también se ocultan (y no cuentan para la paginación).
    function columnaVisibleTdt(indice) {
        var col = document.getElementById('tdt-col-' + indice);
        return !col || col.style.visibility !== 'collapse';
    }

    function obtenerIndiceValorTdt() {
        var candidatos = tdtColumnas.map(function (c, i) { return { c: c.toLowerCase(), i: i }; });
        var exacto = candidatos.filter(function (x) { return x.c === 'valor total' || x.c === 'total' || x.c === 'total valor'; });
        if (exacto.length) { return exacto[exacto.length - 1].i; }
        var conAmbos = candidatos.filter(function (x) { return x.c.indexOf('valor') !== -1 && x.c.indexOf('total') !== -1; });
        if (conAmbos.length) { return conAmbos[conAmbos.length - 1].i; }
        var conTotal = candidatos.filter(function (x) { return x.c.indexOf('total') !== -1; });
        if (conTotal.length) { return conTotal[conTotal.length - 1].i; }
        var conValor = candidatos.filter(function (x) { return x.c.indexOf('valor') !== -1; });
        return conValor.length ? conValor[0].i : -1;
    }

    function filasVisiblesParaGrafica() {
        return filasOriginales
            .filter(function (fila) { return !fila.classList.contains('fila-oculta-filtro'); })
            .map(function (fila) {
                return Array.prototype.slice.call(fila.children, 1).map(function (td) { return td.textContent.trim(); });
            });
    }

    // Una columna sirve como dimensión de agrupación si sus valores NO son todos numéricos — se
    // decide mirando el contenido real de las filas visibles, nunca el nombre de la columna, para
    // que funcione igual sin importar qué origen esté cargado.
    function esDimensionCandidata(indice, filasDatos) {
        var textos = filasDatos.map(function (fila) { return (fila[indice] || '').trim(); }).filter(function (t) { return t !== '' && t !== '—'; });
        if (textos.length === 0) { return false; }
        return !textos.every(function (t) { return /^[\d.,\s$%-]+$/.test(t); });
    }

    // --- Fila fija de totales (pie de la tabla, no de la gráfica): suma cada columna numérica
    // (valores Y cantidades, cualquiera que tenga solo texto numérico en sus filas visibles —
    // mismo criterio que esDimensionCandidata, invertido) sobre las filas que el filtro deje
    // visibles. Una columna se muestra en pesos solo si sus celdas ya vienen formateadas como tal
    // (terminan en ",NN" — así las guarda PHP con number_format() para cualquier valor float);
    // una columna de enteros simples (cantidades) se suma y se muestra sin símbolo de moneda, para
    // no confundir "2 monitores" con "$2".
    function construirFilaTotales() {
        var filaTotales = tabla.querySelector('tfoot .fila-totales-tdt');
        if (!filaTotales) {
            return;
        }

        var filasDatos = filasVisiblesParaGrafica();

        for (var indice = 0; indice < tdtColumnas.length; indice++) {
            var celda = filaTotales.children[indice + 1];
            if (!celda) {
                continue;
            }

            var textos = filasDatos.map(function (fila) { return (fila[indice] || '').trim(); }).filter(function (t) { return t !== '' && t !== '—'; });
            var esNumerica = textos.length > 0 && textos.every(function (t) { return /^[\d.,\s$%-]+$/.test(t); });

            if (!esNumerica) {
                celda.textContent = '';
                continue;
            }

            var suma = textos.reduce(function (acumulado, t) { return acumulado + parseNumeroCeldaTdt(t); }, 0);
            var esMoneda = textos.some(function (t) { return /,\d{2}$/.test(t); });
            celda.textContent = esMoneda ? formatoMonedaTdt(suma) : Math.round(suma).toLocaleString('es-CO');
        }
    }

    function agruparYSumar(filasDatos, indiceDimension, indiceValor) {
        var totalesPorCategoria = {};
        var orden = [];

        filasDatos.forEach(function (fila) {
            var categoria = fila[indiceDimension] || '(Sin dato)';
            var valor = parseNumeroCeldaTdt(fila[indiceValor]);

            if (!Object.prototype.hasOwnProperty.call(totalesPorCategoria, categoria)) {
                totalesPorCategoria[categoria] = 0;
                orden.push(categoria);
            }
            totalesPorCategoria[categoria] += valor;
        });

        return orden.map(function (categoria) { return { categoria: categoria, valor: totalesPorCategoria[categoria] }; })
            .sort(function (a, b) { return b.valor - a.valor; });
    }

    // --- Paneles "melt": cuando una tabla trae varias columnas monetarias independientes en vez de
    // una sola dimensión por fila (ej. ARL: "Riesgo I (valor)".."Riesgo V (valor)"), cada columna es
    // una categoría propia — no se puede agrupar por FILA como agruparYSumar(). Se detecta por el
    // nombre de columna (termina en "(valor)"), nunca por el origen, para que sirva igual con
    // cualquier tabla futura que use la misma convención de nombres. ---
    function detectarColumnasMelt() {
        var sufijoValor = /\(valor\)\s*$/i;
        var numeralFinal = /\s+([ivxlcdm]+|\d+)$/i;
        var gruposPorPrefijo = {};
        var orden = [];

        tdtColumnas.forEach(function (nombre, indice) {
            if (!sufijoValor.test(nombre)) { return; }

            var etiqueta = nombre.replace(sufijoValor, '').trim();
            var prefijo = etiqueta.replace(numeralFinal, '').trim() || etiqueta;

            if (!Object.prototype.hasOwnProperty.call(gruposPorPrefijo, prefijo)) {
                gruposPorPrefijo[prefijo] = [];
                orden.push(prefijo);
            }
            gruposPorPrefijo[prefijo].push({ etiqueta: etiqueta, indice: indice });
        });

        return orden
            .map(function (prefijo) { return { prefijo: prefijo, columnas: gruposPorPrefijo[prefijo] }; })
            .filter(function (grupo) { return grupo.columnas.length >= 2; });
    }

    function agruparPorColumnasValor(filasDatos, columnas) {
        return columnas
            .map(function (columna) {
                var total = filasDatos.reduce(function (acc, fila) { return acc + parseNumeroCeldaTdt(fila[columna.indice]); }, 0);
                return { categoria: columna.etiqueta, valor: total };
            })
            .sort(function (a, b) { return b.valor - a.valor; });
    }

    var elementoTooltipGrafica = document.getElementById('tdt-grafica-tooltip');

    function mostrarTooltipGrafica(elementoReferencia, categoria, valor, porcentaje, pista) {
        if (!elementoTooltipGrafica) { return; }
        elementoTooltipGrafica.innerHTML = '';
        elementoTooltipGrafica.appendChild(document.createTextNode(categoria + ': '));
        var fuerte = document.createElement('strong');
        fuerte.textContent = formatoMonedaTdt(valor);
        elementoTooltipGrafica.appendChild(fuerte);
        var linea2 = document.createElement('div');
        linea2.textContent = porcentaje.toFixed(1) + '% del total de este panel';
        elementoTooltipGrafica.appendChild(linea2);
        if (pista) {
            var linea3 = document.createElement('div');
            linea3.style.opacity = '0.75';
            linea3.textContent = pista;
            elementoTooltipGrafica.appendChild(linea3);
        }

        var rect = elementoReferencia.getBoundingClientRect();
        elementoTooltipGrafica.style.display = 'block';
        elementoTooltipGrafica.style.left = Math.max(4, Math.min(rect.left, window.innerWidth - 270)) + 'px';
        var topPos = rect.top - elementoTooltipGrafica.offsetHeight - 8;
        elementoTooltipGrafica.style.top = (topPos < 4 ? rect.bottom + 8 : topPos) + 'px';
    }

    function ocultarTooltipGrafica() {
        if (elementoTooltipGrafica) { elementoTooltipGrafica.style.display = 'none'; }
    }

    // --- Pareto 80/20 (Rubro, Actividad, Proyecto PDI, Meses/PAC — ver DIMENSIONES_CON_PARETO):
    // las categorías cuyo % acumulado (de mayor a menor valor) todavía no cruza el 80% se marcan
    // "vitales" — el color de la barra/porción no cambia (ni engorda el texto), solo baja de
    // opacidad la cola larga, y el texto usa un tono distinto en cada grupo para que ambos sigan
    // siendo legibles (nunca color solo-atenuado, que era difícil de leer). ---
    function construirCuerpoBarras(agregados, totalGeneral, aplicarPareto, dimension) {
        var nombreDimension = dimension.nombre;
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo';
        var maxValor = agregados.reduce(function (acc, a) { return Math.max(acc, a.valor); }, 0) || 1;
        var acumuladoPct = 0;

        // Las filas van en un envoltorio propio: el cuerpo hace scroll, y así la manija (una sola,
        // como el borde de una columna) cubre el alto de TODAS las filas, no solo lo visible.
        var envoltorioFilas = document.createElement('div');
        envoltorioFilas.className = 'grafica-barras-filas';
        envoltorioFilas.dataset.dimension = nombreDimension;
        fijarAnchoEtiquetaGrafica(envoltorioFilas, leerAnchosEtiquetaGrafica()[nombreDimension] || 0);
        var manijaEtiqueta = document.createElement('span');
        manijaEtiqueta.className = 'redimensionador-columna grafica-redimensionador-etiqueta';
        manijaEtiqueta.title = 'Arrastra para cambiar el ancho de las descripciones (doble clic: restablecer)';
        envoltorioFilas.appendChild(manijaEtiqueta);
        contenedor.appendChild(envoltorioFilas);

        agregados.forEach(function (item, indice) {
            var fila = document.createElement('div');
            fila.className = 'grafica-fila-barra';
            fila.tabIndex = 0;

            var porcentaje = totalGeneral > 0 ? (item.valor / totalGeneral) * 100 : 0;
            var esVital = true;
            if (aplicarPareto) {
                esVital = acumuladoPct < 80;
                acumuladoPct += porcentaje;
            }

            var etiqueta = document.createElement('span');
            etiqueta.className = 'grafica-barra-etiqueta';
            etiqueta.textContent = item.categoria;
            etiqueta.title = item.categoria;
            if (aplicarPareto) { etiqueta.style.color = esVital ? 'var(--color-pareto-destacado)' : 'var(--color-texto)'; }
            fila.appendChild(etiqueta);

            var pista = document.createElement('div');
            pista.className = 'grafica-barra-pista';
            var relleno = document.createElement('div');
            relleno.className = 'grafica-barra-relleno';
            relleno.style.background = colorSerieTdt(indice);
            relleno.style.width = Math.max(1, (item.valor / maxValor) * 100) + '%';
            if (aplicarPareto && !esVital) { relleno.style.opacity = '0.35'; }
            pista.appendChild(relleno);
            fila.appendChild(pista);

            var valorSpan = document.createElement('span');
            valorSpan.className = 'grafica-barra-valor';
            valorSpan.textContent = formatoMonedaTdt(item.valor) + ' (' + porcentaje.toFixed(1) + '%)';
            if (aplicarPareto) { valorSpan.style.color = esVital ? 'var(--color-pareto-destacado)' : 'var(--color-texto)'; }
            fila.appendChild(valorSpan);

            fila.addEventListener('mouseenter', function () { mostrarTooltipGrafica(fila, item.categoria, item.valor, porcentaje, 'Clic para ver estas filas en la tabla'); });
            fila.addEventListener('mouseleave', ocultarTooltipGrafica);
            fila.addEventListener('focus', function () { mostrarTooltipGrafica(fila, item.categoria, item.valor, porcentaje, 'Enter para ver estas filas en la tabla'); });
            fila.addEventListener('blur', ocultarTooltipGrafica);

            fila.addEventListener('click', function () { aplicarFiltroGrafica(dimension, item.categoria); });
            fila.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter' || evento.key === ' ') {
                    evento.preventDefault();
                    aplicarFiltroGrafica(dimension, item.categoria);
                }
            });

            envoltorioFilas.appendChild(fila);
        });

        return contenedor;
    }

    function puntoEnCirculoTdt(cx, cy, r, anguloGrados) {
        var rad = (Math.PI / 180) * anguloGrados;
        return { x: cx + r * Math.sin(rad), y: cy - r * Math.cos(rad) };
    }

    function trazoArcoTortaTdt(cx, cy, r, anguloInicio, anguloFin) {
        var p1 = puntoEnCirculoTdt(cx, cy, r, anguloInicio);
        var p2 = puntoEnCirculoTdt(cx, cy, r, anguloFin);
        var grande = (anguloFin - anguloInicio) > 180 ? 1 : 0;
        return ['M', cx, cy, 'L', p1.x, p1.y, 'A', r, r, 0, grande, 1, p2.x, p2.y, 'Z'].join(' ');
    }

    function construirCuerpoTorta(agregados, totalGeneral, tamanoGrande, aplicarPareto, dimension) {
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo grafica-torta-cuerpo';

        // Mismo cálculo de Pareto que construirCuerpoBarras(), una sola vez, para que la porción y
        // su fila de leyenda (dos bucles separados) queden de acuerdo en qué es "vital".
        var esVitalPorIndice = [];
        if (aplicarPareto) {
            var acumuladoPct = 0;
            agregados.forEach(function (item) {
                var pct = totalGeneral > 0 ? (item.valor / totalGeneral) * 100 : 0;
                esVitalPorIndice.push(acumuladoPct < 80);
                acumuladoPct += pct;
            });
        }
        function colorTextoPareto(indice) {
            return esVitalPorIndice[indice] ? 'var(--color-pareto-destacado)' : 'var(--color-texto)';
        }

        var svgNS = 'http://www.w3.org/2000/svg';
        var lado = tamanoGrande ? 240 : 100;
        var radio = lado / 2;
        var svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 ' + lado + ' ' + lado);
        svg.setAttribute('class', 'grafica-torta-svg');
        if (!tamanoGrande) {
            svg.setAttribute('width', lado);
            svg.setAttribute('height', lado);
        }

        if (agregados.length === 1 || totalGeneral <= 0) {
            var circulo = document.createElementNS(svgNS, 'circle');
            circulo.setAttribute('cx', radio);
            circulo.setAttribute('cy', radio);
            circulo.setAttribute('r', radio - 1);
            circulo.setAttribute('fill', colorSerieTdt(0));
            circulo.setAttribute('class', 'grafica-torta-porcion');
            circulo.dataset.indice = '0';
            svg.appendChild(circulo);
        } else {
            var acumulado = 0;
            agregados.forEach(function (item, indice) {
                var anguloInicio = acumulado * 360;
                acumulado += item.valor / totalGeneral;
                var anguloFin = acumulado * 360;
                var porcion = document.createElementNS(svgNS, 'path');
                porcion.setAttribute('d', trazoArcoTortaTdt(radio, radio, radio - 1, anguloInicio, anguloFin));
                porcion.setAttribute('fill', colorSerieTdt(indice));
                porcion.setAttribute('class', 'grafica-torta-porcion');
                porcion.dataset.indice = String(indice);
                if (aplicarPareto && !esVitalPorIndice[indice]) { porcion.setAttribute('opacity', '0.35'); }
                svg.appendChild(porcion);
            });
        }

        contenedor.appendChild(svg);

        var leyenda = document.createElement('div');
        leyenda.className = 'grafica-torta-leyenda';

        agregados.forEach(function (item, indice) {
            var porcentaje = totalGeneral > 0 ? (item.valor / totalGeneral) * 100 : 0;
            var filaLeyenda = document.createElement('div');
            filaLeyenda.className = 'grafica-torta-leyenda-fila';
            filaLeyenda.tabIndex = 0;

            var punto = document.createElement('span');
            punto.className = 'grafica-torta-leyenda-punto';
            punto.style.background = colorSerieTdt(indice);
            if (aplicarPareto && !esVitalPorIndice[indice]) { punto.style.opacity = '0.35'; }
            filaLeyenda.appendChild(punto);

            var etiqueta = document.createElement('span');
            etiqueta.className = 'grafica-torta-leyenda-etiqueta';
            etiqueta.textContent = item.categoria;
            etiqueta.title = item.categoria;
            if (aplicarPareto) { etiqueta.style.color = colorTextoPareto(indice); }
            filaLeyenda.appendChild(etiqueta);

            var valorSpan = document.createElement('span');
            valorSpan.className = 'grafica-torta-leyenda-valor';
            valorSpan.textContent = porcentaje.toFixed(1) + '%';
            if (aplicarPareto) { valorSpan.style.color = colorTextoPareto(indice); }
            filaLeyenda.appendChild(valorSpan);

            var porcionSvg = svg.querySelector('[data-indice="' + indice + '"]');

            function resaltar() {
                filaLeyenda.classList.add('resaltada');
                if (porcionSvg) { porcionSvg.classList.add('resaltada'); }
                mostrarTooltipGrafica(filaLeyenda, item.categoria, item.valor, porcentaje, 'Clic para ver estas filas en la tabla');
            }
            function quitarResaltado() {
                filaLeyenda.classList.remove('resaltada');
                if (porcionSvg) { porcionSvg.classList.remove('resaltada'); }
                ocultarTooltipGrafica();
            }

            filaLeyenda.addEventListener('mouseenter', resaltar);
            filaLeyenda.addEventListener('mouseleave', quitarResaltado);
            filaLeyenda.addEventListener('focus', resaltar);
            filaLeyenda.addEventListener('blur', quitarResaltado);
            if (porcionSvg) {
                porcionSvg.addEventListener('mouseenter', resaltar);
                porcionSvg.addEventListener('mouseleave', quitarResaltado);
            }

            // Igual que las barras: la porción y su fila de leyenda filtran la tabla.
            function filtrarPorEsta() { aplicarFiltroGrafica(dimension, item.categoria); }
            filaLeyenda.addEventListener('click', filtrarPorEsta);
            filaLeyenda.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter' || evento.key === ' ') {
                    evento.preventDefault();
                    filtrarPorEsta();
                }
            });
            // Torta de una sola porción (o total en 0): el único círculo pertenece a la primera
            // categoría, no a todas.
            if (porcionSvg && (agregados.length === 1 || totalGeneral > 0)) {
                porcionSvg.addEventListener('click', filtrarPorEsta);
            }

            leyenda.appendChild(filaLeyenda);
        });

        contenedor.appendChild(leyenda);
        return contenedor;
    }

    var NOMBRES_MESES_TDT = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    function calcularPacPorMes(filasDatos, indiceMeses, indiceValor) {
        var totalesPorMes = NOMBRES_MESES_TDT.map(function () { return 0; });

        filasDatos.forEach(function (fila) {
            var textoMeses = (fila[indiceMeses] || '').trim();
            if (textoMeses === '') { return; }
            var listaMeses = textoMeses.split(',').map(function (m) { return m.trim(); }).filter(function (m) { return m !== ''; });
            if (listaMeses.length === 0) { return; }
            var valorPorMes = parseNumeroCeldaTdt(fila[indiceValor]) / listaMeses.length;
            listaMeses.forEach(function (nombreMes) {
                var indiceMes = NOMBRES_MESES_TDT.indexOf(nombreMes);
                if (indiceMes !== -1) { totalesPorMes[indiceMes] += valorPorMes; }
            });
        });

        return totalesPorMes;
    }

    // Igual que en barras/torta: cuáles categorías (aquí, meses) todavía no cruzan el 80%
    // acumulado, calculado de mayor a menor valor — devuelto en el ORDEN ORIGINAL del array
    // recibido (cronológico para el PAC), para no reordenar el eje.
    function calcularVitalesPareto(valores) {
        var total = valores.reduce(function (a, b) { return a + b; }, 0);
        var orden = valores.map(function (valor, indice) { return indice; })
            .sort(function (a, b) { return valores[b] - valores[a]; });
        var acumuladoPct = 0;
        var vital = valores.map(function () { return false; });
        orden.forEach(function (indice) {
            var pct = total > 0 ? (valores[indice] / total) * 100 : 0;
            vital[indice] = acumuladoPct < 80;
            acumuladoPct += pct;
        });
        return vital;
    }

    function construirCuerpoLinea(valoresPorMes, etiquetasMeses) {
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo grafica-linea-cuerpo';

        var svgNS = 'http://www.w3.org/2000/svg';
        var anchoBase = 600;
        var altoBase = 200;
        var padX = 14;
        var padSuperior = 14;
        var padInferior = 14;
        var anchoUtil = anchoBase - (padX * 2);
        var altoUtil = altoBase - padSuperior - padInferior;
        var maxValor = Math.max.apply(null, valoresPorMes.concat([0])) || 1;
        var totalGeneral = valoresPorMes.reduce(function (a, b) { return a + b; }, 0);
        var esVitalPorMes = calcularVitalesPareto(valoresPorMes);

        var svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 ' + anchoBase + ' ' + altoBase);
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.setAttribute('class', 'grafica-linea-svg');

        var puntos = valoresPorMes.map(function (valor, indice) {
            var x = padX + (anchoUtil * indice / (valoresPorMes.length - 1));
            var y = padSuperior + altoUtil - (altoUtil * (valor / maxValor));
            return { x: x, y: y, valor: valor };
        });

        var lineaBase = document.createElementNS(svgNS, 'line');
        lineaBase.setAttribute('x1', padX);
        lineaBase.setAttribute('x2', anchoBase - padX);
        lineaBase.setAttribute('y1', padSuperior + altoUtil);
        lineaBase.setAttribute('y2', padSuperior + altoUtil);
        lineaBase.setAttribute('class', 'grafica-linea-base');
        svg.appendChild(lineaBase);

        var trazo = document.createElementNS(svgNS, 'path');
        trazo.setAttribute('d', puntos.map(function (p, i) { return (i === 0 ? 'M' : 'L') + p.x + ' ' + p.y; }).join(' '));
        trazo.setAttribute('class', 'grafica-linea-trazo');
        trazo.setAttribute('fill', 'none');
        svg.appendChild(trazo);

        puntos.forEach(function (p, indice) {
            var circulo = document.createElementNS(svgNS, 'circle');
            circulo.setAttribute('cx', p.x);
            circulo.setAttribute('cy', p.y);
            circulo.setAttribute('r', 4);
            circulo.setAttribute('class', 'grafica-linea-punto');
            circulo.setAttribute('tabindex', '0');
            if (!esVitalPorMes[indice]) { circulo.setAttribute('opacity', '0.35'); }

            var porcentaje = totalGeneral > 0 ? (p.valor / totalGeneral) * 100 : 0;
            function mostrar() { mostrarTooltipGrafica(circulo, etiquetasMeses[indice], p.valor, porcentaje); }
            circulo.addEventListener('mouseenter', mostrar);
            circulo.addEventListener('mouseleave', ocultarTooltipGrafica);
            circulo.addEventListener('focus', mostrar);
            circulo.addEventListener('blur', ocultarTooltipGrafica);

            svg.appendChild(circulo);
        });

        contenedor.appendChild(svg);

        var filaEtiquetas = document.createElement('div');
        filaEtiquetas.className = 'grafica-linea-meses-etiquetas';
        etiquetasMeses.forEach(function (etiqueta, indice) {
            var span = document.createElement('span');
            span.className = 'grafica-linea-mes-etiqueta';
            span.textContent = etiqueta;
            span.style.color = esVitalPorMes[indice] ? 'var(--color-pareto-destacado)' : 'var(--color-texto)';
            filaEtiquetas.appendChild(span);
        });
        contenedor.appendChild(filaEtiquetas);

        return contenedor;
    }

    function crearBotonTipoGrafico(tipo, tipoActivo, alElegir) {
        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'grafica-tipo-boton' + (tipo === tipoActivo ? ' activo' : '');
        boton.title = tipo === 'barras' ? 'Barras' : 'Torta';
        boton.innerHTML = tipo === 'barras'
            ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>'
            : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>';
        boton.addEventListener('click', function (evento) {
            evento.stopPropagation();
            alElegir(tipo);
        });
        return boton;
    }

    function construirToggleTipo(nombreDimension, contenedorToggle, alCambiar) {
        contenedorToggle.innerHTML = '';
        var tipoActual = leerTipoGrafico(nombreDimension);
        ['barras', 'torta'].forEach(function (tipo) {
            contenedorToggle.appendChild(crearBotonTipoGrafico(tipo, tipoActual, function (tipoElegido) {
                fijarTipoGrafico(nombreDimension, tipoElegido);
                alCambiar();
            }));
        });
    }

    var panelExpandidoNombre = null;
    var indiceTarjetaGrafica = 0;
    var TAMANO_TARJETA_GRAFICA = 6;

    function expandirPanel(nombreDimension) {
        panelExpandidoNombre = (panelExpandidoNombre === nombreDimension) ? null : nombreDimension;
        actualizarGrafica();
    }

    function renderizarPanel(contenedorPadre, dimension, agregados, totalGeneral, esExpandido) {
        var panel = document.createElement('div');
        panel.className = 'grafica-panel' + (esExpandido ? ' expandido' : '');
        panel.title = esExpandido ? 'Doble clic para volver a ver todas las gráficas' : 'Doble clic para ampliar';

        var cabecera = document.createElement('div');
        cabecera.className = 'grafica-panel-cabecera';

        var textos = document.createElement('div');
        var h3 = document.createElement('h3');
        h3.className = 'grafica-panel-titulo';
        h3.textContent = dimension.titulo;
        var subt = document.createElement('p');
        subt.className = 'grafica-panel-subtitulo';
        subt.textContent = dimension.subtitulo;
        textos.appendChild(h3);
        textos.appendChild(subt);
        cabecera.appendChild(textos);

        var acciones = document.createElement('div');
        acciones.style.display = 'flex';
        acciones.style.alignItems = 'center';
        acciones.style.gap = '0.4rem';
        acciones.style.flexShrink = '0';

        var contenedorToggle;
        if (!dimension.esLinea) {
            contenedorToggle = document.createElement('div');
            contenedorToggle.className = 'grafica-tipo-toggle';
            acciones.appendChild(contenedorToggle);
        }

        var botonAmpliar = document.createElement('button');
        botonAmpliar.type = 'button';
        botonAmpliar.className = 'icono-boton';
        botonAmpliar.title = esExpandido ? 'Restaurar' : 'Ampliar';
        botonAmpliar.innerHTML = esExpandido
            ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>'
            : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>';
        botonAmpliar.addEventListener('click', function (evento) {
            evento.stopPropagation();
            expandirPanel(dimension.nombre);
        });
        acciones.appendChild(botonAmpliar);

        cabecera.appendChild(acciones);
        panel.appendChild(cabecera);

        function actualizarPanelCompleto() {
            if (contenedorToggle) {
                construirToggleTipo(dimension.nombre, contenedorToggle, actualizarPanelCompleto);
            }

            var cuerpoAnterior = panel.querySelector('.grafica-panel-cuerpo');
            if (cuerpoAnterior) { cuerpoAnterior.remove(); }

            if (dimension.esLinea) {
                panel.appendChild(construirCuerpoLinea(dimension.valoresPorMes, dimension.etiquetasMeses));
                return;
            }

            if (agregados.length === 0) {
                var vacio = document.createElement('p');
                vacio.className = 'grafica-panel-vacio grafica-panel-cuerpo';
                vacio.textContent = 'Sin datos para los filtros actuales.';
                panel.appendChild(vacio);
                return;
            }

            var tipo = leerTipoGrafico(dimension.nombre);
            var aplicarPareto = DIMENSIONES_CON_PARETO.indexOf(dimension.nombre) !== -1;
            var cuerpo2 = tipo === 'torta'
                ? construirCuerpoTorta(agregados, totalGeneral, esExpandido, aplicarPareto, dimension)
                : construirCuerpoBarras(agregados, totalGeneral, aplicarPareto, dimension);
            panel.appendChild(cuerpo2);
        }

        actualizarPanelCompleto();

        panel.addEventListener('dblclick', function () {
            expandirPanel(dimension.nombre);
        });

        contenedorPadre.appendChild(panel);
    }

    // --- Paginador de tarjetas: la cuadrícula de paneles (.tabla-grafica-paneles) tiene capacidad
    // para 6 (3×2) — con más de 6 gráficas relevantes para un origen, se reparten en "tarjetas" de
    // hasta 6, con un botón chico para pasar de una a otra. Se reconstruye en cada llamada a
    // actualizarGrafica(), igual que el resto de esta vista — nunca depende de app.js (que solo
    // engancha los `[data-mini-slider]` que ya existen al cargar la página, no los que se crean
    // después dinámicamente). ---
    function construirBarraPaginacionGrafica(totalPaneles) {
        var contenedorKpis = document.getElementById('tdt-grafica-kpis');
        var barra = document.getElementById('tdt-grafica-paginador');
        var totalPaginas = Math.max(1, Math.ceil(totalPaneles / TAMANO_TARJETA_GRAFICA));

        if (totalPaginas <= 1 || panelExpandidoNombre) {
            if (barra) { barra.remove(); }
            return totalPaginas;
        }

        if (indiceTarjetaGrafica >= totalPaginas) { indiceTarjetaGrafica = totalPaginas - 1; }
        if (indiceTarjetaGrafica < 0) { indiceTarjetaGrafica = 0; }

        // Es una celda más de la fila de indicadores (ver tabla-real.js: tarjetasKpi), junto a las
        // cifras, para que botones y número de página ocupen la misma fila.
        if (!barra) {
            barra = document.createElement('div');
            barra.id = 'tdt-grafica-paginador';
            barra.className = 'grafica-stat grafica-paginador';
            contenedorKpis.appendChild(barra);
        }

        barra.innerHTML = '';

        var titulo = document.createElement('span');
        titulo.className = 'mini-slider-titulo';
        titulo.textContent = 'Página ' + (indiceTarjetaGrafica + 1) + ' de ' + totalPaginas;
        barra.appendChild(titulo);

        var nav = document.createElement('div');
        nav.className = 'mini-slider-nav';

        var botonPrev = document.createElement('button');
        botonPrev.type = 'button';
        botonPrev.className = 'mini-slider-flecha';
        botonPrev.setAttribute('aria-label', 'Anterior');
        botonPrev.innerHTML = '&#8249;';
        botonPrev.addEventListener('click', function () {
            indiceTarjetaGrafica -= 1;
            actualizarGrafica();
        });

        var botonNext = document.createElement('button');
        botonNext.type = 'button';
        botonNext.className = 'mini-slider-flecha';
        botonNext.setAttribute('aria-label', 'Siguiente');
        botonNext.innerHTML = '&#8250;';
        botonNext.addEventListener('click', function () {
            indiceTarjetaGrafica += 1;
            actualizarGrafica();
        });

        nav.appendChild(botonPrev);
        nav.appendChild(botonNext);
        barra.appendChild(nav);

        return totalPaginas;
    }

    window.addEventListener('resize', function () {
        if (vistaGraficaActiva) { ajustarAlturaGrafica(); }
    });

    function ajustarAlturaGrafica() {
        var envoltorioScroll = tabla.closest('.tabla-scroll');
        var grafica = document.getElementById('tdt-grafica');
        if (!envoltorioScroll || !grafica) { return; }
        var alturaTabla = tabla.getBoundingClientRect().height;
        var alturaDisponible = envoltorioScroll.clientHeight - alturaTabla;
        grafica.style.height = Math.max(240, alturaDisponible) + 'px';
    }

    function actualizarGrafica() {
        if (!vistaGraficaActiva) { return; }

        var contenedorKpis = document.getElementById('tdt-grafica-kpis');
        var contenedorPaneles = document.getElementById('tdt-grafica-paneles');
        if (!contenedorKpis || !contenedorPaneles) { return; }

        var filasDatos = filasVisiblesParaGrafica();
        var indiceValor = obtenerIndiceValorTdt();

        // Estas son las dimensiones reales para tomar decisiones (Dependencia, Actividad, Rubro,
        // Proyecto PDI, Sede, y Categoría cuando existe — Autogestión) — cuando existen en esta
        // tabla (Gasto/Ingreso), son siempre las que se usan, en este orden. Para el resto de
        // orígenes (ARL, Monitores, OPS, Otros, Necesidad, ingresos) se SUMAN además las demás
        // columnas de texto candidatas — antes, en cuanto una sola dimensión preferida coincidía
        // (ej. "Dependencia"), nunca se revisaban las demás columnas reales de la tabla (ej. "Tipo"
        // en Monitores, "Perfil" en OPS), aunque existieran.
        var ORIGENES_ESTRUCTURA_FIJA = ['gasto_principal', 'gasto_extension', 'gasto_postgrado', 'gasto_unisalud', 'gasto_sin_excedentes'];
        var DIMENSIONES_PREFERIDAS = ['Dependencia', 'Categoría de gasto', 'Rubro', 'Proyecto PDI', 'Sede', 'Actividad'];
        if (indiceColumnaTdt('Categoría') !== -1) { DIMENSIONES_PREFERIDAS = DIMENSIONES_PREFERIDAS.concat('Categoría'); }

        var dimensiones = DIMENSIONES_PREFERIDAS
            .map(function (nombre) { return { nombre: nombre, indice: indiceColumnaTdt(nombre) }; })
            .filter(function (d) { return d.indice !== -1 && d.indice !== indiceValor; });

        var esEstructuraFija = ORIGENES_ESTRUCTURA_FIJA.indexOf(tabla.dataset.origen || '') !== -1;

        if (!esEstructuraFija) {
            var indicesYaIncluidos = dimensiones.map(function (d) { return d.indice; });
            var extras = tdtColumnas
                .map(function (nombre, indice) { return { nombre: nombre, indice: indice }; })
                .filter(function (d) { return d.indice !== indiceValor && indicesYaIncluidos.indexOf(d.indice) === -1 && esDimensionCandidata(d.indice, filasDatos); });
            dimensiones = dimensiones.concat(extras);
        }

        dimensiones = dimensiones.filter(function (d) { return columnaVisibleTdt(d.indice); });

        contenedorKpis.innerHTML = '';

        var totalValor = indiceValor !== -1
            ? filasDatos.reduce(function (acc, fila) { return acc + parseNumeroCeldaTdt(fila[indiceValor]); }, 0)
            : 0;

        var tarjetasKpi = [
            { etiqueta: indiceValor !== -1 ? tdtColumnas[indiceValor] + ' (suma)' : 'Filas', valor: indiceValor !== -1 ? formatoMonedaTdt(totalValor) : String(filasDatos.length) },
            { etiqueta: 'Filas visibles', valor: String(filasDatos.length) }
        ];

        dimensiones.slice(0, 2).forEach(function (dimension) {
            var valoresUnicos = {};
            filasDatos.forEach(function (fila) { valoresUnicos[fila[dimension.indice] || '(Sin dato)'] = true; });
            tarjetasKpi.push({ etiqueta: dimension.nombre + ' distintos', valor: String(Object.keys(valoresUnicos).length) });
        });

        tarjetasKpi.forEach(function (kpi) {
            var tarjeta = document.createElement('div');
            tarjeta.className = 'grafica-stat';
            var etiqueta = document.createElement('p');
            etiqueta.className = 'grafica-stat-etiqueta';
            etiqueta.textContent = kpi.etiqueta;
            var valor = document.createElement('p');
            valor.className = 'grafica-stat-valor';
            valor.textContent = kpi.valor;
            tarjeta.appendChild(etiqueta);
            tarjeta.appendChild(valor);
            contenedorKpis.appendChild(tarjeta);
        });

        contenedorPaneles.innerHTML = '';

        if (indiceValor === -1) {
            var avisoSinValor = document.createElement('p');
            avisoSinValor.className = 'grafica-panel-vacio';
            avisoSinValor.textContent = 'Esta tabla no tiene una columna de valor/total para agregar.';
            contenedorPaneles.appendChild(avisoSinValor);
            construirBarraPaginacionGrafica(0);
        } else {
            // Actividad va después del PAC: así queda en la segunda página de la gráfica.
            var DIMENSIONES_AL_FINAL = ['Actividad'];
            var paneles = dimensiones.filter(function (d) { return DIMENSIONES_AL_FINAL.indexOf(d.nombre) === -1; });
            var dimensionesAlFinal = dimensiones.filter(function (d) { return DIMENSIONES_AL_FINAL.indexOf(d.nombre) !== -1; });

            detectarColumnasMelt().forEach(function (grupo) {
                var columnasVisibles = grupo.columnas.filter(function (c) { return columnaVisibleTdt(c.indice); });
                if (columnasVisibles.length >= 2) {
                    paneles.push({ nombre: grupo.prefijo, esMelt: true, columnasMelt: columnasVisibles });
                }
            });

            var indiceMeses = indiceColumnaTdt('Meses');
            if (indiceMeses !== -1 && columnaVisibleTdt(indiceMeses)) {
                var valoresPorMes = calcularPacPorMes(filasDatos, indiceMeses, indiceValor);
                var filasConMeses = filasDatos.filter(function (fila) { return (fila[indiceMeses] || '').trim() !== ''; }).length;
                paneles.push({
                    nombre: 'Meses',
                    esLinea: true,
                    valoresPorMes: valoresPorMes,
                    etiquetasMeses: NOMBRES_MESES_TDT,
                    titulo: 'PAC',
                    subtitulo: filasConMeses + ' ítem' + (filasConMeses === 1 ? '' : 's') + ' con meses de ejecución asignados'
                });
            }

            dimensionesAlFinal.forEach(function (dimension) { paneles.push(dimension); });

            var panelesAMostrar;

            if (panelExpandidoNombre) {
                panelesAMostrar = paneles.filter(function (p) { return p.nombre === panelExpandidoNombre; });

                if (panelesAMostrar.length === 0) {
                    panelExpandidoNombre = null;
                    panelesAMostrar = paneles;
                }
            }

            if (!panelExpandidoNombre) {
                var totalPaginas = construirBarraPaginacionGrafica(paneles.length);
                var inicioPagina = indiceTarjetaGrafica * TAMANO_TARJETA_GRAFICA;
                panelesAMostrar = totalPaginas > 1 ? paneles.slice(inicioPagina, inicioPagina + TAMANO_TARJETA_GRAFICA) : paneles;
            }

            contenedorPaneles.classList.toggle('un-panel', !!panelExpandidoNombre);

            panelesAMostrar.forEach(function (panelDato) {
                var esExpandido = panelDato.nombre === panelExpandidoNombre;

                if (panelDato.esLinea) {
                    renderizarPanel(contenedorPaneles, panelDato, null, 0, esExpandido);
                    return;
                }

                var agregados = panelDato.esMelt
                    ? agruparPorColumnasValor(filasDatos, panelDato.columnasMelt)
                    : agruparYSumar(filasDatos, panelDato.indice, indiceValor);
                var totalGeneral = agregados.reduce(function (acc, a) { return acc + a.valor; }, 0);
                panelDato.titulo = panelDato.nombre;
                panelDato.subtitulo = agregados.length + ' ' + panelDato.nombre.toLowerCase() + (agregados.length === 1 ? '' : 's') + ' con datos en el filtro actual';
                renderizarPanel(contenedorPaneles, panelDato, agregados, totalGeneral, esExpandido);
            });
        }

        ajustarAlturaGrafica();
    }

    var botonVistaTabla = document.getElementById('tdt-boton-vista-tabla');
    var botonVistaGrafica = document.getElementById('tdt-boton-vista-grafica');

    function activarVistaTabla() {
        vistaGraficaActiva = false;
        tabla.classList.remove('modo-grafica');
        panelExpandidoNombre = null;
        if (botonVistaTabla) { botonVistaTabla.classList.add('activo'); botonVistaTabla.setAttribute('aria-pressed', 'true'); }
        if (botonVistaGrafica) { botonVistaGrafica.classList.remove('activo'); botonVistaGrafica.setAttribute('aria-pressed', 'false'); }
    }

    function activarVistaGrafica() {
        vistaGraficaActiva = true;
        tabla.classList.add('modo-grafica');
        if (botonVistaGrafica) { botonVistaGrafica.classList.add('activo'); botonVistaGrafica.setAttribute('aria-pressed', 'true'); }
        if (botonVistaTabla) { botonVistaTabla.classList.remove('activo'); botonVistaTabla.setAttribute('aria-pressed', 'false'); }

        // La gráfica se filtra con los mismos combos por columna que la tabla (aplicarFiltros()
        // ya llama a actualizarGrafica()); acá solo forzamos que la fila arranque visible al
        // entrar a este modo — el botón "Filtrar" sigue habilitado para poder ocultarla igual
        // que en modo tabla.
        if (filaFiltros) { filaFiltros.classList.add('visible'); }
        if (botonFiltrar) { botonFiltrar.classList.add('activo'); }

        actualizarGrafica();
    }

    // --- Barras como filtro: clic en una barra → tabla con solo esas filas, y junto al nombre de
    // la tabla un botón para volver a la vista de donde se vino (gráfica, o gráfica ampliada en
    // el mismo panel y página). Dimensión normal: filas cuya celda de esa columna es exactamente
    // la categoría ('(Sin dato)' = vacía). Panel "melt" (ej. Riesgos de ARL): la categoría es una
    // columna, así que se filtran las filas con valor distinto de 0 en ella. ---
    var nombreTabla = document.querySelector('.tabla-topbar .tabla-nombre');
    var envoltorioFiltroGrafica = null;

    function aplicarFiltroGrafica(dimension, categoria) {
        var cumple;
        if (dimension.esMelt) {
            var columnaMelt = (dimension.columnasMelt || []).filter(function (c) { return c.etiqueta === categoria; })[0];
            if (!columnaMelt) { return; }
            cumple = function (fila) {
                var celda = fila.children[columnaMelt.indice + 1];
                return !!celda && parseNumeroCeldaTdt(celda.textContent) !== 0;
            };
        } else {
            var valorBuscado = categoria === '(Sin dato)' ? '' : categoria;
            cumple = function (fila) {
                var celda = fila.children[dimension.indice + 1];
                return !!celda && celda.textContent.trim() === valorBuscado;
            };
        }

        ocultarTooltipGrafica();
        filtroGrafica = {
            etiqueta: (dimension.titulo || dimension.nombre) + ': ' + categoria,
            cumple: cumple,
            vistaAnterior: { grafica: vistaGraficaActiva, panel: panelExpandidoNombre, pagina: indiceTarjetaGrafica },
        };

        activarVistaTabla();
        aplicarFiltros();
        mostrarBotonVolverFiltro();
    }

    function limpiarFiltroGrafica() {
        filtroGrafica = null;
        if (envoltorioFiltroGrafica) {
            envoltorioFiltroGrafica.remove();
            envoltorioFiltroGrafica = null;
        }
    }

    function volverDesdeFiltroGrafica() {
        if (!filtroGrafica) { return; }
        var anterior = filtroGrafica.vistaAnterior;
        limpiarFiltroGrafica();
        aplicarFiltros();
        if (anterior.grafica) {
            panelExpandidoNombre = anterior.panel;
            indiceTarjetaGrafica = anterior.pagina;
            activarVistaGrafica();
        } else {
            activarVistaTabla();
        }
    }

    function mostrarBotonVolverFiltro() {
        if (!nombreTabla) { return; }
        if (envoltorioFiltroGrafica) { envoltorioFiltroGrafica.remove(); }

        var anterior = filtroGrafica.vistaAnterior;
        var destino = !anterior.grafica ? 'la tabla' : (anterior.panel ? 'la gráfica ampliada' : 'las gráficas');

        envoltorioFiltroGrafica = document.createElement('span');
        envoltorioFiltroGrafica.className = 'tabla-filtro-grafica';

        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'tabla-boton-volver';
        boton.title = 'Volver a ' + destino;
        boton.setAttribute('aria-label', 'Volver a ' + destino);
        boton.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>';
        boton.addEventListener('click', volverDesdeFiltroGrafica);

        var chip = document.createElement('span');
        chip.className = 'tabla-filtro-grafica-chip';
        chip.textContent = filtroGrafica.etiqueta;
        chip.title = filtroGrafica.etiqueta;

        envoltorioFiltroGrafica.appendChild(boton);
        envoltorioFiltroGrafica.appendChild(chip);
        nombreTabla.insertAdjacentElement('afterend', envoltorioFiltroGrafica);
    }

    // Cambiar de vista a mano descarta el filtro de la barra (si no, la gráfica saldría recortada
    // a una sola categoría sin aviso).
    if (botonVistaTabla) {
        botonVistaTabla.addEventListener('click', function () {
            if (filtroGrafica) { limpiarFiltroGrafica(); aplicarFiltros(); }
            activarVistaTabla();
        });
    }
    if (botonVistaGrafica) {
        botonVistaGrafica.addEventListener('click', function () {
            if (filtroGrafica) { limpiarFiltroGrafica(); aplicarFiltros(); }
            activarVistaGrafica();
        });
    }

    // --- Selección de fila + Editar (navega al formulario real) / Eliminar (real) ---
    var casillaSeleccionarTodo = document.getElementById('tdt-seleccionar-todo');
    var botonEditar = document.getElementById('tdt-boton-editar');
    var botonEliminar = document.getElementById('tdt-boton-eliminar');
    var formAccion = document.getElementById('tdt-form-accion');
    var formAccionValor = document.getElementById('tdt-form-accion-valor');
    var formOrigenId = document.getElementById('tdt-form-origen-id');

    function filaSeleccionadaUnica() {
        var seleccionadas = cuerpo.querySelectorAll('.tabla-seleccion-fila:checked');
        return seleccionadas.length === 1 ? seleccionadas[0].closest('tr') : null;
    }

    function actualizarBotonesSeleccion() {
        var fila = filaSeleccionadaUnica();
        var puedeEditar = fila && fila.dataset.puedeEditar === '1' && fila.dataset.rutaEditar;
        if (botonEditar) { botonEditar.disabled = !puedeEditar; }
        if (botonEliminar) { botonEliminar.disabled = !fila; }
        cuerpo.querySelectorAll('tr').forEach(function (tr) {
            tr.classList.toggle('fila-seleccionada', tr.querySelector('.tabla-seleccion-fila:checked') !== null);
        });
    }

    cuerpo.querySelectorAll('.tabla-seleccion-fila').forEach(function (casilla) {
        casilla.addEventListener('change', function () {
            if (casilla.checked) {
                cuerpo.querySelectorAll('.tabla-seleccion-fila').forEach(function (otra) {
                    if (otra !== casilla) { otra.checked = false; }
                });
            }
            actualizarBotonesSeleccion();
        });
    });

    if (casillaSeleccionarTodo) {
        casillaSeleccionarTodo.addEventListener('change', function () {
            // Editar/Eliminar exigen exactamente 1 fila: "seleccionar todo" solo sirve para acciones
            // masivas que no existen en este landing, así que se deja sin marcar todas.
            casillaSeleccionarTodo.checked = false;
        });
    }

    if (botonEditar) {
        botonEditar.addEventListener('click', function () {
            var fila = filaSeleccionadaUnica();
            if (!fila || botonEditar.disabled) { return; }

            window.location.href = fila.dataset.rutaEditar;
        });
    }

    if (botonEliminar) {
        botonEliminar.addEventListener('click', function () {
            var fila = filaSeleccionadaUnica();
            if (!fila || botonEliminar.disabled) { return; }

            if (!window.confirm('¿Eliminar este ítem? No se puede deshacer.')) {
                return;
            }

            formAccionValor.value = 'eliminar_celda';
            formOrigenId.value = fila.dataset.origenId;
            formAccion.submit();
        });
    }

    actualizarBotonesSeleccion();
    construirFilaTotales();
});
