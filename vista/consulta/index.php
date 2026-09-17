<?php $tituloPagina = 'Consulta'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>Consulta</h1>
        </div>

        <div class="pestanas">
            <a href="index.php?ruta=consulta&tipo=gastos" class="pestana<?= $tipo === 'gastos' ? ' activa' : '' ?>">Gastos</a>
            <a href="index.php?ruta=consulta&tipo=ingresos" class="pestana<?= $tipo === 'ingresos' ? ' activa' : '' ?>">Ingresos</a>
        </div>

        <?php if ($tipo === 'ingresos'): ?>
        <p class="texto-atenuado">Este módulo está en construcción.</p>
        <?php elseif (empty($anios)): ?>
        <p class="texto-atenuado">No hay años presupuestales activos para consultar.</p>
        <?php else: ?>

        <form method="GET" action="index.php" class="barra-filtros">
            <input type="hidden" name="ruta" value="consulta">
            <input type="hidden" name="tipo" value="gastos">

            <div class="campo">
                <label for="consulta-anio">Año presupuestal</label>
                <select id="consulta-anio" name="anio">
                    <?php foreach ($anios as $anio): ?>
                    <option value="<?= (int) $anio['id'] ?>" <?= (int) $anio['id'] === $anioSeleccionadoId ? 'selected' : '' ?>><?= htmlspecialchars($anio['anio']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="consulta-sede">Sede</label>
                <select id="consulta-sede" name="sede">
                    <option value="">Todas</option>
                    <?php foreach ($sedes as $sede): ?>
                    <option value="<?= htmlspecialchars($sede['nombre']) ?>" <?= $sedeFiltro === $sede['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($sede['codigo'] . ' - ' . $sede['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="consulta-dependencia">Dependencia</label>
                <select id="consulta-dependencia" name="dependencia">
                    <option value="">Todas</option>
                    <?php foreach ($dependencias as $dependencia): ?>
                    <option value="<?= htmlspecialchars($dependencia['nombre']) ?>" <?= $dependenciaFiltro === $dependencia['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($dependencia['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="consulta-estado">Estado</label>
                <select id="consulta-estado" name="estado">
                    <option value="">Todos</option>
                    <option value="borrador" <?= $estadoFiltro === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                    <option value="enviado" <?= $estadoFiltro === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                </select>
            </div>

            <div class="campo campo-busqueda-filtros">
                <label for="consulta-buscar">Actividad, rubro o insumo</label>
                <input type="text" id="consulta-buscar" name="buscar" value="<?= htmlspecialchars($textoFiltro) ?>" placeholder="Buscar…">
            </div>

            <div class="acciones-filtros-consulta">
                <button type="submit" class="boton-secundario">Filtrar</button>
                <a href="index.php?ruta=consulta&tipo=gastos" class="boton-secundario">Limpiar</a>
            </div>
        </form>

        <div class="barra-rapida-consulta">
            <div class="buscador-rapido-consulta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="consulta-filtro-rapido" placeholder="Filtrar y buscar en la tabla…">
            </div>
            <span class="contador-registros" id="consulta-contador"></span>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-usuarios" id="consulta-tabla">
                <thead>
                    <tr>
                        <th class="th-ordenable-consulta" data-campo="actividad">Actividad</th>
                        <th class="th-ordenable-consulta" data-campo="rubro">Rubro</th>
                        <th>Insumo</th>
                        <th class="th-ordenable-consulta" data-campo="cantidad">Cantidad</th>
                        <th>Costo unitario</th>
                        <th class="th-ordenable-consulta" data-campo="valor_total">Valor total</th>
                        <th>Sede</th>
                        <th>Dependencia</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="consulta-cuerpo"></tbody>
            </table>
        </div>

        <div class="paginacion-consulta">
            <div class="selector-tamano-consulta">
                <div class="campo">
                    <label for="consulta-tamano">Mostrar</label>
                    <select id="consulta-tamano">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
            <div class="acciones-filtros-consulta">
                <span class="contador-registros" id="consulta-info-paginacion"></span>
                <button type="button" class="boton-icono-accion" id="consulta-pag-prev" data-tooltip="Página anterior" title="Página anterior">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button type="button" class="boton-icono-accion" id="consulta-pag-next" data-tooltip="Página siguiente" title="Página siguiente">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>

        <div class="modal-fondo" id="consulta-modal-ver">
            <div class="modal-caja">
                <div class="modal-cabecera">
                    <h2 id="consulta-modal-titulo">Detalle del gasto</h2>
                    <button type="button" class="modal-cerrar" id="consulta-modal-cerrar" aria-label="Cerrar">&times;</button>
                </div>
                <dl class="detalle-solicitud" id="consulta-modal-detalle"></dl>
            </div>
        </div>

        <script>
        (function () {
            var filas = <?= json_encode(array_values($filasTabla), JSON_UNESCAPED_UNICODE) ?>;

            function escaparHtml(texto) {
                var div = document.createElement('div');
                div.textContent = texto === null || texto === undefined ? '' : String(texto);
                return div.innerHTML;
            }

            function moneda(valor) {
                return Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function badgeEstado(estado) {
                var clase = estado === 'Enviado' ? 'badge-enviado' : 'badge-borrador';
                return '<span class="badge-rol ' + clase + '">' + escaparHtml(estado) + '</span>';
            }

            var cuerpo = document.getElementById('consulta-cuerpo');
            var contador = document.getElementById('consulta-contador');
            var infoPaginacion = document.getElementById('consulta-info-paginacion');
            var btnPrev = document.getElementById('consulta-pag-prev');
            var btnNext = document.getElementById('consulta-pag-next');
            var selectorTamano = document.getElementById('consulta-tamano');

            var listaActual = filas.slice();
            var paginaActual = 1;
            var tamanoPagina = Number(selectorTamano.value);
            var ordenCampo = null;
            var ordenDireccion = 'asc';

            function renderPagina() {
                var total = listaActual.length;
                var totalPaginas = Math.max(1, Math.ceil(total / tamanoPagina));
                if (paginaActual > totalPaginas) { paginaActual = totalPaginas; }
                var inicio = (paginaActual - 1) * tamanoPagina;
                var fin = Math.min(inicio + tamanoPagina, total);

                cuerpo.innerHTML = '';

                listaActual.slice(inicio, fin).forEach(function (fila, i) {
                    var indiceReal = inicio + i;
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escaparHtml(fila.actividad) + '</td>' +
                        '<td>' + escaparHtml(fila.rubro) + '</td>' +
                        '<td>' + escaparHtml(fila.insumo) + '</td>' +
                        '<td>' + fila.cantidad + '</td>' +
                        '<td>' + moneda(fila.costo_unitario) + '</td>' +
                        '<td>' + moneda(fila.valor_total) + '</td>' +
                        '<td>' + escaparHtml(fila.sede) + '</td>' +
                        '<td>' + escaparHtml(fila.dependencia) + '</td>' +
                        '<td>' + badgeEstado(fila.estado) + '</td>' +
                        '<td><button type="button" class="boton-accion boton-accion-ver" data-indice="' + indiceReal + '">Ver</button></td>';
                    cuerpo.appendChild(tr);
                });

                if (total === 0) {
                    cuerpo.innerHTML = '<tr><td colspan="10">No hay resultados con estos filtros.</td></tr>';
                }

                cuerpo.querySelectorAll('.boton-accion-ver').forEach(function (boton) {
                    boton.addEventListener('click', function () {
                        abrirModal(listaActual[Number(boton.dataset.indice)]);
                    });
                });

                contador.textContent = total + ' registro' + (total === 1 ? '' : 's');
                infoPaginacion.textContent = total === 0 ? '' : 'Mostrando ' + (inicio + 1) + '–' + fin + ' de ' + total;
                btnPrev.disabled = paginaActual <= 1;
                btnNext.disabled = paginaActual >= totalPaginas;
            }

            function aplicarBusquedaRapida() {
                var texto = document.getElementById('consulta-filtro-rapido').value.trim().toLowerCase();
                listaActual = texto === ''
                    ? filas.slice()
                    : filas.filter(function (fila) {
                        return (fila.actividad + ' ' + fila.rubro + ' ' + fila.insumo).toLowerCase().indexOf(texto) !== -1;
                    });

                if (ordenCampo !== null) {
                    ordenarLista();
                }

                paginaActual = 1;
                renderPagina();
            }

            function ordenarLista() {
                listaActual.sort(function (a, b) {
                    var valorA = a[ordenCampo];
                    var valorB = b[ordenCampo];

                    if (typeof valorA === 'string') {
                        valorA = valorA.toLowerCase();
                        valorB = valorB.toLowerCase();
                    }

                    if (valorA < valorB) { return ordenDireccion === 'asc' ? -1 : 1; }
                    if (valorA > valorB) { return ordenDireccion === 'asc' ? 1 : -1; }
                    return 0;
                });
            }

            document.getElementById('consulta-filtro-rapido').addEventListener('input', aplicarBusquedaRapida);

            document.querySelectorAll('.th-ordenable-consulta').forEach(function (encabezado) {
                encabezado.addEventListener('click', function () {
                    var campo = encabezado.dataset.campo;

                    if (ordenCampo === campo) {
                        ordenDireccion = ordenDireccion === 'asc' ? 'desc' : 'asc';
                    } else {
                        ordenCampo = campo;
                        ordenDireccion = 'asc';
                    }

                    document.querySelectorAll('.th-ordenable-consulta').forEach(function (th) {
                        th.classList.remove('orden-asc', 'orden-desc');
                    });
                    encabezado.classList.add(ordenDireccion === 'asc' ? 'orden-asc' : 'orden-desc');

                    ordenarLista();
                    paginaActual = 1;
                    renderPagina();
                });
            });

            btnPrev.addEventListener('click', function () {
                if (paginaActual > 1) { paginaActual--; renderPagina(); }
            });
            btnNext.addEventListener('click', function () {
                var totalPaginas = Math.max(1, Math.ceil(listaActual.length / tamanoPagina));
                if (paginaActual < totalPaginas) { paginaActual++; renderPagina(); }
            });
            selectorTamano.addEventListener('change', function () {
                tamanoPagina = Number(selectorTamano.value);
                paginaActual = 1;
                renderPagina();
            });

            var modal = document.getElementById('consulta-modal-ver');
            var modalTitulo = document.getElementById('consulta-modal-titulo');
            var modalDetalle = document.getElementById('consulta-modal-detalle');

            function abrirModal(fila) {
                modalTitulo.textContent = fila.actividad;
                modalDetalle.innerHTML =
                    '<dt>Dependencia</dt><dd>' + escaparHtml(fila.dependencia) + '</dd>' +
                    '<dt>Sede</dt><dd>' + escaparHtml(fila.sede) + '</dd>' +
                    '<dt>Estado</dt><dd>' + badgeEstado(fila.estado) + '</dd>' +
                    '<dt>Línea estratégica</dt><dd>' + escaparHtml(fila.linea) + '</dd>' +
                    '<dt>Motor de desarrollo</dt><dd>' + escaparHtml(fila.motor) + '</dd>' +
                    '<dt>Proyecto PDI</dt><dd>' + escaparHtml(fila.proyecto) + '</dd>' +
                    '<dt>Rubro</dt><dd>' + escaparHtml(fila.rubro) + '</dd>' +
                    '<dt>Insumo</dt><dd>' + escaparHtml(fila.insumo) + '</dd>' +
                    '<dt>Cantidad</dt><dd>' + fila.cantidad + '</dd>' +
                    '<dt>Costo unitario</dt><dd>' + moneda(fila.costo_unitario) + '</dd>' +
                    '<dt>Valor total</dt><dd>' + moneda(fila.valor_total) + '</dd>' +
                    '<dt>Meses de ejecución</dt><dd>' + escaparHtml(fila.meses) + '</dd>';
                modal.classList.add('abierto');
            }

            document.getElementById('consulta-modal-cerrar').addEventListener('click', function () {
                modal.classList.remove('abierto');
            });
            modal.addEventListener('click', function (e) {
                if (e.target === modal) { modal.classList.remove('abierto'); }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { modal.classList.remove('abierto'); }
            });

            renderPagina();
        })();
        </script>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
