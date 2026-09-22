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
            fila.classList.toggle('fila-oculta-filtro', !(cumpleColumnas && cumpleBusqueda));
        });

        actualizarGrafica();
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
    var PALETA_SERIES_TDT = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
    var claveTipoGrafico = 'peticiones_tabla_grafica_tipo_' + namespace;

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

    var elementoTooltipGrafica = document.getElementById('tdt-grafica-tooltip');

    function mostrarTooltipGrafica(elementoReferencia, categoria, valor, porcentaje) {
        if (!elementoTooltipGrafica) { return; }
        elementoTooltipGrafica.innerHTML = '';
        elementoTooltipGrafica.appendChild(document.createTextNode(categoria + ': '));
        var fuerte = document.createElement('strong');
        fuerte.textContent = formatoMonedaTdt(valor);
        elementoTooltipGrafica.appendChild(fuerte);
        var linea2 = document.createElement('div');
        linea2.textContent = porcentaje.toFixed(1) + '% del total de este panel';
        elementoTooltipGrafica.appendChild(linea2);

        var rect = elementoReferencia.getBoundingClientRect();
        elementoTooltipGrafica.style.display = 'block';
        elementoTooltipGrafica.style.left = Math.max(4, Math.min(rect.left, window.innerWidth - 270)) + 'px';
        var topPos = rect.top - elementoTooltipGrafica.offsetHeight - 8;
        elementoTooltipGrafica.style.top = (topPos < 4 ? rect.bottom + 8 : topPos) + 'px';
    }

    function ocultarTooltipGrafica() {
        if (elementoTooltipGrafica) { elementoTooltipGrafica.style.display = 'none'; }
    }

    function construirCuerpoBarras(agregados, totalGeneral) {
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo';
        var maxValor = agregados.reduce(function (acc, a) { return Math.max(acc, a.valor); }, 0) || 1;

        agregados.forEach(function (item, indice) {
            var fila = document.createElement('div');
            fila.className = 'grafica-fila-barra';
            fila.tabIndex = 0;

            var etiqueta = document.createElement('span');
            etiqueta.className = 'grafica-barra-etiqueta';
            etiqueta.textContent = item.categoria;
            etiqueta.title = item.categoria;
            fila.appendChild(etiqueta);

            var pista = document.createElement('div');
            pista.className = 'grafica-barra-pista';
            var relleno = document.createElement('div');
            relleno.className = 'grafica-barra-relleno';
            relleno.style.background = colorSerieTdt(indice);
            relleno.style.width = Math.max(1, (item.valor / maxValor) * 100) + '%';
            pista.appendChild(relleno);
            fila.appendChild(pista);

            var valorSpan = document.createElement('span');
            valorSpan.className = 'grafica-barra-valor';
            valorSpan.textContent = formatoMonedaTdt(item.valor);
            fila.appendChild(valorSpan);

            var porcentaje = totalGeneral > 0 ? (item.valor / totalGeneral) * 100 : 0;
            fila.addEventListener('mouseenter', function () { mostrarTooltipGrafica(fila, item.categoria, item.valor, porcentaje); });
            fila.addEventListener('mouseleave', ocultarTooltipGrafica);
            fila.addEventListener('focus', function () { mostrarTooltipGrafica(fila, item.categoria, item.valor, porcentaje); });
            fila.addEventListener('blur', ocultarTooltipGrafica);

            contenedor.appendChild(fila);
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

    function construirCuerpoTorta(agregados, totalGeneral, tamanoGrande) {
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo grafica-torta-cuerpo';

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
            filaLeyenda.appendChild(punto);

            var etiqueta = document.createElement('span');
            etiqueta.className = 'grafica-torta-leyenda-etiqueta';
            etiqueta.textContent = item.categoria;
            etiqueta.title = item.categoria;
            filaLeyenda.appendChild(etiqueta);

            var valorSpan = document.createElement('span');
            valorSpan.className = 'grafica-torta-leyenda-valor';
            valorSpan.textContent = porcentaje.toFixed(1) + '%';
            filaLeyenda.appendChild(valorSpan);

            var porcionSvg = svg.querySelector('[data-indice="' + indice + '"]');

            function resaltar() {
                filaLeyenda.classList.add('resaltada');
                if (porcionSvg) { porcionSvg.classList.add('resaltada'); }
                mostrarTooltipGrafica(filaLeyenda, item.categoria, item.valor, porcentaje);
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
        etiquetasMeses.forEach(function (etiqueta) {
            var span = document.createElement('span');
            span.className = 'grafica-linea-mes-etiqueta';
            span.textContent = etiqueta;
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
            var cuerpo2 = tipo === 'torta'
                ? construirCuerpoTorta(agregados, totalGeneral, esExpandido)
                : construirCuerpoBarras(agregados, totalGeneral);
            panel.appendChild(cuerpo2);
        }

        actualizarPanelCompleto();

        panel.addEventListener('dblclick', function () {
            expandirPanel(dimension.nombre);
        });

        contenedorPadre.appendChild(panel);
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
        // Proyecto PDI, Sede) — cuando existen en esta tabla (Gasto/Ingreso), son siempre las que
        // se usan, en este orden. Si el origen no las tiene (ARL, Monitores, OPS, Otros, Necesidad),
        // se cae al detector genérico (cualquier columna de texto, no numérica).
        var DIMENSIONES_PREFERIDAS = ['Dependencia', 'Actividad', 'Rubro', 'Proyecto PDI', 'Sede'];
        var dimensiones = DIMENSIONES_PREFERIDAS
            .map(function (nombre) { return { nombre: nombre, indice: indiceColumnaTdt(nombre) }; })
            .filter(function (d) { return d.indice !== -1 && d.indice !== indiceValor; });

        if (dimensiones.length === 0) {
            dimensiones = tdtColumnas
                .map(function (nombre, indice) { return { nombre: nombre, indice: indice }; })
                .filter(function (d) { return d.indice !== indiceValor && esDimensionCandidata(d.indice, filasDatos); })
                .slice(0, 5);
        }

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
        } else {
            var paneles = dimensiones.slice();

            var indiceMeses = indiceColumnaTdt('Meses');
            if (indiceMeses !== -1) {
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

            var panelesAMostrar = panelExpandidoNombre
                ? paneles.filter(function (p) { return p.nombre === panelExpandidoNombre; })
                : paneles;

            if (panelExpandidoNombre && panelesAMostrar.length === 0) {
                panelExpandidoNombre = null;
                panelesAMostrar = paneles;
            }

            contenedorPaneles.classList.toggle('un-panel', !!panelExpandidoNombre);

            panelesAMostrar.forEach(function (panelDato) {
                var esExpandido = panelDato.nombre === panelExpandidoNombre;

                if (panelDato.esLinea) {
                    renderizarPanel(contenedorPaneles, panelDato, null, 0, esExpandido);
                    return;
                }

                var agregados = agruparYSumar(filasDatos, panelDato.indice, indiceValor);
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
        actualizarGrafica();
    }

    if (botonVistaTabla) { botonVistaTabla.addEventListener('click', activarVistaTabla); }
    if (botonVistaGrafica) { botonVistaGrafica.addEventListener('click', activarVistaGrafica); }

    // --- Selección de fila + Editar/Eliminar reales (in-place, sin modal) ---
    var casillaSeleccionarTodo = document.getElementById('tdt-seleccionar-todo');
    var botonEditar = document.getElementById('tdt-boton-editar');
    var botonEliminar = document.getElementById('tdt-boton-eliminar');
    var formAccion = document.getElementById('tdt-form-accion');
    var formAccionValor = document.getElementById('tdt-form-accion-valor');
    var formOrigenId = document.getElementById('tdt-form-origen-id');
    var editando = false;

    function filaSeleccionadaUnica() {
        var seleccionadas = cuerpo.querySelectorAll('.tabla-seleccion-fila:checked');
        return seleccionadas.length === 1 ? seleccionadas[0].closest('tr') : null;
    }

    function actualizarBotonesSeleccion() {
        var fila = filaSeleccionadaUnica();
        var tieneEditables = fila && fila.querySelector('td[data-editable]') && (tdtCamposEditables || []).length > 0;
        botonEditar.disabled = editando ? false : !tieneEditables;
        botonEliminar.disabled = editando ? false : !fila;
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

    function infoCampo(clave) {
        return (tdtCamposEditablesInfo || []).find(function (c) { return c.clave === clave; });
    }

    function iniciarEdicion(fila) {
        editando = true;
        actualizarBotonesSeleccion();

        fila.querySelectorAll('td[data-editable]').forEach(function (celda) {
            var clave = celda.dataset.editable;
            var info = infoCampo(clave);
            var valorActual = celda.textContent;
            // Algunos campos se muestran formateados (ej. "meses" como Ene/Feb) pero se editan en
            // su forma cruda real (ej. "1,2") — data-valor-crudo trae ese valor cuando difiere.
            var valorParaEditar = celda.dataset.valorCrudo !== undefined ? celda.dataset.valorCrudo : valorActual;
            celda.dataset.valorOriginal = valorActual;
            celda.classList.add('celda-editando');

            var input = document.createElement(info && info.tipo === 'textarea' ? 'textarea' : 'input');
            if (input.tagName === 'INPUT') {
                input.type = (info && info.tipo === 'number') ? 'number' : 'text';
                if (info && info.tipo === 'number') { input.step = 'any'; }
            }
            input.value = valorParaEditar === '—' ? '' : valorParaEditar;
            celda.textContent = '';
            celda.appendChild(input);
        });

        var primerInput = fila.querySelector('td.celda-editando input, td.celda-editando textarea');
        if (primerInput) { primerInput.focus(); }

        // Los botones Editar/Eliminar de la topbar se reutilizan como Guardar/Cancelar mientras
        // se edita, para no introducir una barra de acciones aparte.
        botonEditar.title = 'Guardar cambios';
        botonEliminar.title = 'Cancelar edición';
    }

    function guardarEdicion(fila) {
        formAccionValor.value = 'guardar_celda';
        formOrigenId.value = fila.dataset.origenId;
        fila.querySelectorAll('td.celda-editando').forEach(function (celda) {
            var clave = celda.dataset.editable;
            var input = celda.querySelector('input, textarea');
            var campoOculto = document.getElementById('tdt-form-campo-' + clave);
            if (campoOculto && input) { campoOculto.value = input.value; }
        });
        formAccion.submit();
    }

    function cancelarEdicion(fila) {
        fila.querySelectorAll('td.celda-editando').forEach(function (celda) {
            celda.classList.remove('celda-editando');
            celda.textContent = celda.dataset.valorOriginal || '';
        });
        editando = false;
        botonEditar.title = 'Editar';
        botonEliminar.title = 'Eliminar';
        actualizarBotonesSeleccion();
    }

    if (botonEditar) {
        botonEditar.addEventListener('click', function () {
            var fila = filaSeleccionadaUnica();
            if (!fila) { return; }

            if (editando) {
                guardarEdicion(fila);
            } else if (!botonEditar.disabled) {
                iniciarEdicion(fila);
            }
        });
    }

    if (botonEliminar) {
        botonEliminar.addEventListener('click', function () {
            var fila = filaSeleccionadaUnica();
            if (!fila) { return; }

            if (editando) {
                cancelarEdicion(fila);
                return;
            }

            if (botonEliminar.disabled || !window.confirm('¿Eliminar este ítem? No se puede deshacer.')) {
                return;
            }

            formAccionValor.value = 'eliminar_celda';
            formOrigenId.value = fila.dataset.origenId;
            formAccion.submit();
        });
    }

    actualizarBotonesSeleccion();
});
