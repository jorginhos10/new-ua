// Vista Tabla de la tabla real: virtualización de filas,
// anchos y visibilidad de columnas, orden, fila de totales y selección + Editar/Eliminar (POST
// real al mismo controlador). Filas, filtros y conmutador de vistas viven en nucleo.js.
TablaReal.registrarVista('tabla', function (ctx) {
    var tabla = ctx.tabla;
    var tdtColumnas = ctx.columnas;
    var filasOriginales = ctx.filasOriginales;
    var contenedorScroll = ctx.contenedorScroll;
    // Anchos y columnas ocultas se guardan por POSICIÓN de columna: al cambiar el orden estándar
    // de las tablas de gasto se subió la versión de la clave (v2) para no aplicar lo guardado a la
    // columna equivocada — cada quien arranca de nuevo con los valores por defecto.
    var claveAnchos = 'peticiones_tabla_anchos_v2_' + ctx.namespace;
    var claveOcultas = 'peticiones_tabla_ocultas_v2_' + ctx.namespace;

    var cuerpo = tabla.querySelector('tbody');
    var numeroColumnas = tabla.querySelectorAll('thead tr.fila-encabezados th').length || 1;

    // --- Ventana visible: solo se adjuntan al <tbody> las filas de la ventana (renderizarVentana()).
    // Filtros, orden, selección y totales trabajan con el arreglo completo. ---
    var filasEnOrden = filasOriginales.slice();
    var ALTURA_FILA = 36;
    var ALTURA_MEDIDA = false;
    var MARGEN_FILAS = 15;
    var pendienteRender = false;

    function filasVisibles() {
        return filasEnOrden.filter(function (fila) { return !fila.classList.contains('fila-oculta-filtro'); });
    }

    function crearEspaciador(alto) {
        var espaciador = document.createElement('tr');
        espaciador.className = 'fila-espaciadora';
        espaciador.setAttribute('aria-hidden', 'true');
        var celda = document.createElement('td');
        celda.colSpan = numeroColumnas;
        celda.style.height = alto + 'px';
        celda.style.padding = '0';
        celda.style.border = '0';
        espaciador.appendChild(celda);
        return espaciador;
    }

    // Adjunta al <tbody> solo las filas que caen en la ventana (con margen), y espaciadores para que
    // la barra de desplazamiento tenga el alto de todas las filas visibles.
    function renderizarVentana() {
        var visibles = filasVisibles();
        var total = visibles.length;
        var posicionScroll = contenedorScroll ? contenedorScroll.scrollTop : 0;
        var altoVentana = contenedorScroll ? contenedorScroll.clientHeight : 600;
        var inicio = Math.max(0, Math.floor(posicionScroll / ALTURA_FILA) - MARGEN_FILAS);
        var fin = Math.min(total, inicio + Math.ceil(altoVentana / ALTURA_FILA) + 2 * MARGEN_FILAS);

        var nodos = [];
        if (inicio > 0) { nodos.push(crearEspaciador(inicio * ALTURA_FILA)); }
        for (var i = inicio; i < fin; i++) { nodos.push(visibles[i]); }
        if (fin < total) { nodos.push(crearEspaciador((total - fin) * ALTURA_FILA)); }

        cuerpo.replaceChildren.apply(cuerpo, nodos);

        if (!ALTURA_MEDIDA && fin > inicio) {
            var alturaReal = visibles[inicio].getBoundingClientRect().height;
            if (alturaReal > 0) {
                ALTURA_FILA = alturaReal;
                ALTURA_MEDIDA = true;
            }
        }
    }

    function programarRender() {
        if (pendienteRender) { return; }
        pendienteRender = true;
        requestAnimationFrame(function () {
            pendienteRender = false;
            renderizarVentana();
        });
    }

    if (contenedorScroll) {
        contenedorScroll.addEventListener('scroll', programarRender);
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
        filasOriginales.forEach(function (fila) { celdas.push(fila.children[parseInt(indice, 10) + 1]); });
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

    // --- Ancho de la tabla: por defecto las columnas visibles ocupan el 100 % del contenedor — si
    // sobra espacio se agrandan y si falta se encogen, en proporción a su ancho, sin bajar de
    // ANCHO_MINIMO_COLUMNA (si ni así caben, aparece el scroll horizontal normal de .tabla-scroll).
    // Las columnas con ancho guardado en localStorage (las que el usuario ajustó arrastrando la
    // barra) conservan ese ancho: el 100 % se reparte solo entre las demás. "anchosBase" guarda el
    // ancho de cada columna ANTES de repartir (el guardado/por defecto), para no ir agrandándolas
    // de más cada vez que se recalcula (el CSS 'width: 100%' no sirve para esto: con table-layout
    // fixed los navegadores no liberan el espacio de una columna oculta con visibility: collapse). ---
    var anchosBase = {};
    tabla.querySelectorAll('colgroup col[id^="tdt-col-"]').forEach(function (col) {
        var indice = col.id.replace('tdt-col-', '');
        anchosBase[indice] = parseInt(anchosGuardados[indice], 10) || parseInt(col.style.width, 10) || 90;
    });
    var anchoColSeleccion = 34;
    var ANCHO_MINIMO_COLUMNA = 70;

    function ajustarAnchoTabla() {
        if (!contenedorScroll) { return; }
        var indicesVisibles = Object.keys(anchosBase).filter(function (indice) {
            var col = document.getElementById('tdt-col-' + indice);
            return col && col.style.visibility !== 'collapse';
        });
        var fijas = indicesVisibles.filter(function (indice) { return anchosGuardados[indice]; });
        var libres = indicesVisibles.filter(function (indice) { return !anchosGuardados[indice]; });

        fijas.forEach(function (indice) { fijarAnchoColumna(indice, anchosBase[indice]); });

        var sumaFijas = fijas.reduce(function (total, indice) { return total + anchosBase[indice]; }, 0);
        var sumaLibres = libres.reduce(function (total, indice) { return total + anchosBase[indice]; }, 0);
        if (sumaLibres === 0) { return; }
        var disponible = contenedorScroll.clientWidth - anchoColSeleccion - sumaFijas;
        var factor = disponible > 0 ? disponible / sumaLibres : 1;
        libres.forEach(function (indice) {
            fijarAnchoColumna(indice, Math.max(ANCHO_MINIMO_COLUMNA, Math.floor(anchosBase[indice] * factor)));
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
                anchosGuardados[manija.dataset.indice] = anchoFinal;
                anchosBase[manija.dataset.indice] = anchoFinal;
                ajustarAnchoTabla();
            }
            document.addEventListener('mousemove', mover);
            document.addEventListener('mouseup', soltar);
        });
    });

    // --- Ocultar/mostrar columnas (avisa 'columnas' para que las gráficas se recalculen) ---
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
                ctx.emitir('columnas');
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

    // --- Columnas monetarias a la derecha (celdas y total): una columna es monetaria con el mismo
    // criterio de la fila de totales — todo su texto es numérico y alguna celda termina en ",NN"
    // (así PHP formatea los float). Se decide con todas las filas, no solo las visibles, para que
    // la alineación no cambie al filtrar. ---
    (function alinearColumnasMonetarias() {
        var filaTotales = tabla.querySelector('tfoot .fila-totales-tdt');
        for (var indice = 0; indice < tdtColumnas.length; indice++) {
            var celdas = filasOriginales.map(function (fila) { return fila.children[indice + 1]; }).filter(Boolean);
            var textos = celdas.map(function (celda) { return celda.textContent.trim(); }).filter(function (t) { return t !== '' && t !== '—'; });
            if (textos.length === 0 || !textos.every(ctx.esTextoNumerico) || !textos.some(function (t) { return /,\d{2}$/.test(t); })) {
                continue;
            }
            celdas.forEach(function (celda) { celda.classList.add('columna-moneda'); });
            if (filaTotales && filaTotales.children[indice + 1]) { filaTotales.children[indice + 1].classList.add('columna-moneda'); }
        }
    })();

    // --- Fila de filtros pegada bajo los encabezados (se mide después de repartir anchos) ---
    var filaEncabezados = tabla.querySelector('tr.fila-encabezados');
    var filaFiltros = ctx.filaFiltros;
    if (filaEncabezados && filaFiltros) {
        var altura = filaEncabezados.getBoundingClientRect().height;
        Array.prototype.forEach.call(filaFiltros.querySelectorAll('th'), function (celda) { celda.style.top = altura + 'px'; });
    }

    // --- Orden ---
    tabla.querySelectorAll('.encabezado-columna').forEach(function (encabezado) {
        encabezado.addEventListener('click', function () {
            var indice = parseInt(encabezado.dataset.indice, 10);
            var ascendente = !encabezado.classList.contains('orden-asc');

            tabla.querySelectorAll('.encabezado-columna').forEach(function (otro) { otro.classList.remove('orden-asc', 'orden-desc'); });
            encabezado.classList.add(ascendente ? 'orden-asc' : 'orden-desc');

            var filas = filasEnOrden.slice();
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
            filasEnOrden = filas;
            renderizarVentana();
        });
    });

    // --- Fila fija de totales (pie de la tabla, no de la gráfica): suma cada columna numérica
    // (valores Y cantidades, cualquiera que tenga solo texto numérico en sus filas visibles —
    // ctx.esTextoNumerico(), el mismo criterio que las gráficas usan invertido para elegir
    // dimensiones) sobre las filas que el filtro deje visibles. Una columna se muestra en pesos solo
    // si sus celdas ya vienen formateadas como tal (terminan en ",NN" — así las guarda PHP con
    // number_format() para cualquier valor float); una columna de enteros simples (cantidades) se
    // suma y se muestra sin símbolo de moneda, para no confundir "2 monitores" con "$2".
    function construirFilaTotales() {
        var filaTotales = tabla.querySelector('tfoot .fila-totales-tdt');
        if (!filaTotales) {
            return;
        }

        var filasDatos = ctx.filasVisiblesParaGrafica();

        for (var indice = 0; indice < tdtColumnas.length; indice++) {
            var celda = filaTotales.children[indice + 1];
            if (!celda) {
                continue;
            }

            var textos = filasDatos.map(function (fila) { return (fila[indice] || '').trim(); }).filter(function (t) { return t !== '' && t !== '—'; });
            var esNumerica = textos.length > 0 && textos.every(ctx.esTextoNumerico);

            if (!esNumerica) {
                celda.textContent = '';
                continue;
            }

            var suma = textos.reduce(function (acumulado, t) { return acumulado + ctx.parseNumeroCeldaTdt(t); }, 0);
            var esMoneda = textos.some(function (t) { return /,\d{2}$/.test(t); });
            celda.textContent = esMoneda ? ctx.formatoMonedaTdt(suma) : Math.round(suma).toLocaleString('es-CO');
        }
    }

    ctx.on('filtros', function () {
        renderizarVentana();
        construirFilaTotales();
    });

    // --- Selección de fila(s) + Editar (con 1 fila navega al formulario real; con varias abre el
    // modal de edición masiva del Proyecto PDI, si este origen lo admite — ver
    // parciales/modal-edicion-masiva-proyecto.php y modelo/EdicionMasivaProyecto.php) /
    // Eliminar (real, admite varias a la vez — ver AnalisisControlador::procesarEliminarModulo()
    // y PeticionesControlador::procesarAccionCeldaTipoDetalle(), ambas ya aceptan un lote). ---
    var casillaSeleccionarTodo = document.getElementById('tdt-seleccionar-todo');
    var botonEditar = document.getElementById('tdt-boton-editar');
    var botonEliminar = document.getElementById('tdt-boton-eliminar');
    var formAccion = document.getElementById('tdt-form-accion');
    var formAccionValor = document.getElementById('tdt-form-accion-valor');
    var formOrigenIdsJson = document.getElementById('tdt-form-origen-ids-json');
    var modalEdicionMasiva = document.getElementById('tdt-modal-edicion-masiva');

    function filasSeleccionadas() {
        return filasOriginales.filter(function (fila) {
            return fila.querySelector('.tabla-seleccion-fila:checked') !== null;
        });
    }

    function actualizarBotonesSeleccion() {
        var seleccionadas = filasSeleccionadas();
        var unica = seleccionadas.length === 1 ? seleccionadas[0] : null;
        var todasEditables = seleccionadas.length > 0 && seleccionadas.every(function (fila) { return fila.dataset.puedeEditar === '1'; });
        var puedeEditar = unica
            ? (unica.dataset.puedeEditar === '1' && unica.dataset.rutaEditar)
            : (seleccionadas.length > 1 && !!modalEdicionMasiva && todasEditables);
        if (botonEditar) {
            botonEditar.disabled = !puedeEditar;
            botonEditar.title = seleccionadas.length > 1
                ? (modalEdicionMasiva ? (todasEditables ? 'Editar el Proyecto PDI de las ' + seleccionadas.length + ' filas seleccionadas' : 'Alguna fila seleccionada no se puede editar') : 'Selecciona una sola fila para editar')
                : 'Editar';
        }
        if (botonEliminar) { botonEliminar.disabled = seleccionadas.length === 0; }
        filasOriginales.forEach(function (tr) {
            tr.classList.toggle('fila-seleccionada', tr.querySelector('.tabla-seleccion-fila:checked') !== null);
        });
        if (casillaSeleccionarTodo) {
            // "Seleccionar todo" se refiere solo a lo que el filtro deja ver.
            var visibles = filasVisibles();
            var seleccionadasVisibles = visibles.filter(function (fila) {
                return fila.querySelector('.tabla-seleccion-fila:checked') !== null;
            }).length;
            casillaSeleccionarTodo.checked = visibles.length > 0 && seleccionadasVisibles === visibles.length;
            casillaSeleccionarTodo.indeterminate = seleccionadasVisibles > 0 && seleccionadasVisibles < visibles.length;
        }
    }

    // Al cambiar el filtro se desmarcan las filas que quedan ocultas (Editar/Eliminar nunca deben
    // actuar sobre algo que el usuario ya no ve) y se recalculan los botones y "seleccionar todo".
    ctx.on('filtros', function () {
        filasOriginales.forEach(function (fila) {
            if (fila.classList.contains('fila-oculta-filtro')) {
                var casilla = fila.querySelector('.tabla-seleccion-fila');
                if (casilla) { casilla.checked = false; }
            }
        });
        actualizarBotonesSeleccion();
    });

    filasOriginales.forEach(function (fila) {
        var casilla = fila.querySelector('.tabla-seleccion-fila');
        casilla.addEventListener('change', actualizarBotonesSeleccion);
    });

    if (casillaSeleccionarTodo) {
        casillaSeleccionarTodo.addEventListener('change', function () {
            var marcar = casillaSeleccionarTodo.checked;
            filasVisibles().forEach(function (fila) {
                var casilla = fila.querySelector('.tabla-seleccion-fila');
                if (casilla) { casilla.checked = marcar; }
            });
            actualizarBotonesSeleccion();
        });
    }

    if (botonEditar) {
        botonEditar.addEventListener('click', function () {
            var seleccionadas = filasSeleccionadas();
            if (seleccionadas.length === 0 || botonEditar.disabled) { return; }

            if (seleccionadas.length === 1) {
                window.location.href = seleccionadas[0].dataset.rutaEditar;
                return;
            }

            if (modalEdicionMasiva) {
                var resumen = document.getElementById('tdt-edicion-masiva-resumen');
                if (resumen) { resumen.textContent = 'Se actualizará el Proyecto PDI de las ' + seleccionadas.length + ' filas seleccionadas.'; }
                if (window.establecerValorBuscable) { window.establecerValorBuscable('masivo-proyecto_id', ''); }
                modalEdicionMasiva.classList.add('abierto');
            }
        });
    }

    if (modalEdicionMasiva) {
        var cerrarEdicionMasiva = function () { modalEdicionMasiva.classList.remove('abierto'); };
        ['tdt-cerrar-edicion-masiva', 'tdt-cancelar-edicion-masiva'].forEach(function (id) {
            var boton = document.getElementById(id);
            if (boton) { boton.addEventListener('click', cerrarEdicionMasiva); }
        });
        modalEdicionMasiva.addEventListener('click', function (evento) { if (evento.target === modalEdicionMasiva) { cerrarEdicionMasiva(); } });

        var botonAplicarMasiva = document.getElementById('tdt-aplicar-edicion-masiva');
        if (botonAplicarMasiva) {
            botonAplicarMasiva.addEventListener('click', function () {
                var campoProyecto = document.getElementById('masivo-proyecto_id');
                var proyectoId = campoProyecto ? campoProyecto.value : '';
                if (!proyectoId) {
                    window.alert('Selecciona un Proyecto PDI de la lista.');
                    return;
                }
                var seleccionadas = filasSeleccionadas();
                if (seleccionadas.length === 0) { return; }

                var campoProyectoForm = formAccion.querySelector('input[name="proyecto_id"]');
                if (!campoProyectoForm) {
                    campoProyectoForm = document.createElement('input');
                    campoProyectoForm.type = 'hidden';
                    campoProyectoForm.name = 'proyecto_id';
                    formAccion.appendChild(campoProyectoForm);
                }
                campoProyectoForm.value = proyectoId;
                formOrigenIdsJson.value = JSON.stringify(seleccionadas.map(function (fila) { return parseInt(fila.dataset.origenId, 10); }));
                formAccionValor.value = 'editar_proyecto_masivo';
                botonAplicarMasiva.disabled = true;
                formAccion.submit();
            });
        }
    }

    if (botonEliminar) {
        botonEliminar.addEventListener('click', function () {
            var seleccionadas = filasSeleccionadas();
            if (seleccionadas.length === 0 || botonEliminar.disabled) { return; }

            var mensaje = seleccionadas.length === 1
                ? '¿Eliminar este ítem? No se puede deshacer.'
                : '¿Eliminar estos ' + seleccionadas.length + ' ítems? No se puede deshacer.';
            if (!window.confirm(mensaje)) {
                return;
            }

            var ids = seleccionadas.map(function (fila) { return parseInt(fila.dataset.origenId, 10); });
            formOrigenIdsJson.value = JSON.stringify(ids);
            formAccionValor.value = 'eliminar_celda';
            formAccion.submit();
        });
    }

    // Primer render (cuando ya se registraron todas las vistas): mide la altura real de la fila.
    // Luego, "Ver" centra la fila resaltada.
    ctx.on('listo', function () {
        renderizarVentana();
        var filaResaltada = filasOriginales.filter(function (fila) { return fila.classList.contains('fila-resaltada'); })[0];
        if (filaResaltada && contenedorScroll) {
            var posicionResaltada = filasVisibles().indexOf(filaResaltada);
            if (posicionResaltada >= 0) {
                contenedorScroll.scrollTop = Math.max(0, posicionResaltada * ALTURA_FILA - contenedorScroll.clientHeight / 2);
                renderizarVentana();
            }
        }

        actualizarBotonesSeleccion();
        construirFilaTotales();
    });
});
