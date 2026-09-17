<?php
$tituloPagina = 'Consolidado detallado';
$nombresMeses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];
require __DIR__ . '/../parciales/encabezado.php';

$parametrosExportar = ['ruta' => 'consolidado-detalle', 'exportar' => 'xlsx'];
if ($anioSeleccionadoId > 0) {
    $parametrosExportar['anio_id'] = $anioSeleccionadoId;
}
if ($tipoFiltro !== '') {
    $parametrosExportar['tipo'] = $tipoFiltro;
}

$opcionesTipo = array_values(array_unique(array_filter(array_map(
    static fn (array $fila): string => $fila['tipo'] ?? '',
    $filas
))));
sort($opcionesTipo);

$opcionesDependencia = array_values(array_unique(array_filter(array_map(
    static fn (array $fila): string => $fila['dependencia'] ?? '',
    $filas
))));
sort($opcionesDependencia);

$opcionesSede = array_values(array_unique(array_filter(array_map(
    static fn (array $fila): string => $fila['sede'] ?? '',
    $filas
))));
sort($opcionesSede);

$opcionesLinea = array_values(array_unique(array_filter(array_map(
    static fn (array $fila): string => $fila['linea'] ?? '',
    $filas
))));
sort($opcionesLinea);

$opcionesRubro = array_values(array_unique(array_filter(array_map(
    static fn (array $fila): string => $fila['rubro'] ?? '',
    $filas
))));
sort($opcionesRubro);
?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Consolidado detallado<?= $tipoFiltro !== '' ? ' — ' . htmlspecialchars($tipoFiltro) : '' ?></h1>
                <p class="texto-atenuado">Todo lo aprobado y consolidado<?= $anio !== null ? ' para ' . htmlspecialchars((string) $anio['anio']) : '' ?>, con el mismo detalle que la tabla de Gastos: dependencia, sede, línea, motor, proyecto, rubro, actividad, insumo y techo presupuestal.</p>
            </div>
            <div class="grupo-acciones-encabezado" id="barra-acciones-consolidado">
                <button type="button" id="boton-consolidado-ver" class="boton-accion boton-accion-ver" disabled>Ver</button>
                <button type="button" id="boton-consolidado-editar" class="boton-accion boton-accion-editar" disabled>Editar</button>
                <button type="button" id="boton-consolidado-redireccionar" class="boton-accion boton-accion-enviar" disabled>Redireccionar</button>
                <button type="button" id="boton-consolidado-duplicar" class="boton-agregar" disabled>Duplicar</button>
                <a href="index.php?<?= http_build_query($parametrosExportar) ?>" class="boton-accion boton-accion-enviar">Exportar Excel</a>
                <a href="index.php?ruta=peticiones&vista=consolidado<?= $anioSeleccionadoId > 0 ? '&anio_id=' . (int) $anioSeleccionadoId : '' ?>" class="boton-accion boton-accion-ver">&larr; Volver a Peticiones</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (!empty($filas)): ?>
        <div class="barra-filtros">
            <?php if (count($opcionesTipo) > 1): ?>
            <div class="campo">
                <label for="consolidado-detalle-tipo">Tipo</label>
                <select id="consolidado-detalle-tipo">
                    <option value="">Todos</option>
                    <?php foreach ($opcionesTipo as $opcionTipo): ?>
                    <option value="<?= htmlspecialchars($opcionTipo) ?>"><?= htmlspecialchars($opcionTipo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="campo">
                <label for="consolidado-detalle-dependencia">Dependencia</label>
                <select id="consolidado-detalle-dependencia">
                    <option value="">Todas</option>
                    <?php foreach ($opcionesDependencia as $opcionDependencia): ?>
                    <option value="<?= htmlspecialchars($opcionDependencia) ?>"><?= htmlspecialchars($opcionDependencia) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="consolidado-detalle-sede">Sede</label>
                <select id="consolidado-detalle-sede">
                    <option value="">Todas</option>
                    <?php foreach ($opcionesSede as $opcionSede): ?>
                    <option value="<?= htmlspecialchars($opcionSede) ?>"><?= htmlspecialchars($opcionSede) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="consolidado-detalle-linea">Línea estratégica</label>
                <select id="consolidado-detalle-linea">
                    <option value="">Todas</option>
                    <?php foreach ($opcionesLinea as $opcionLinea): ?>
                    <option value="<?= htmlspecialchars($opcionLinea) ?>"><?= htmlspecialchars($opcionLinea) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="consolidado-detalle-rubro">Rubro</label>
                <select id="consolidado-detalle-rubro">
                    <option value="">Todos</option>
                    <?php foreach ($opcionesRubro as $opcionRubro): ?>
                    <option value="<?= htmlspecialchars($opcionRubro) ?>"><?= htmlspecialchars($opcionRubro) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="acciones-filtros-consulta">
                <button type="button" class="boton-secundario" id="consolidado-detalle-limpiar-filtros">Limpiar filtros</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="barra-rapida-consulta">
            <div class="buscador-rapido-consulta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="consolidado-detalle-filtro-rapido" placeholder="Filtrar y buscar en la tabla…">
            </div>
            <span class="contador-registros" id="consolidado-detalle-contador"><?= count($filas) ?> registro<?= count($filas) === 1 ? '' : 's' ?></span>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-usuarios" id="consolidado-detalle-tabla">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkbox-consolidado-todos" <?= empty($filas) ? 'disabled' : '' ?>></th>
                        <th class="th-ordenable">Tipo</th>
                        <th class="th-ordenable">Dependencia</th>
                        <th>Sede</th>
                        <th>Línea estratégica</th>
                        <th>Motor de desarrollo</th>
                        <th>Proyecto PDI</th>
                        <th>Objeto/Proyecto (PAA)</th>
                        <th class="th-ordenable">Actividad</th>
                        <th>Rubro</th>
                        <th>Insumo</th>
                        <th class="th-ordenable">Cantidad</th>
                        <th>Costo unitario</th>
                        <th class="th-ordenable">Valor total</th>
                        <th>Meses</th>
                        <th>Techo presupuestal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                    <?php
                    $mesesFila = $fila['meses'] !== '—' && $fila['meses'] !== ''
                        ? array_map(static fn ($mes) => $nombresMeses[(int) trim($mes)] ?? trim($mes), explode(',', $fila['meses']))
                        : [];
                    $itemJs = [
                        'origen' => $fila['origen'],
                        'origen_id' => $fila['origen_id'],
                        'tipo' => $fila['tipo'],
                        'detalle' => $fila['dependencia'],
                        'dependencia' => $fila['dependencia'],
                        'cantidad' => $fila['cantidad'],
                        'valor' => $fila['valor_total'],
                        'ruta_ver' => $fila['ruta_ver'],
                        'ruta_origen' => $fila['ruta_origen'] ?? 'index.php?ruta=peticiones',
                        'puede_editar' => !empty($fila['puede_editar']),
                        'sede' => $fila['sede'],
                        'linea' => $fila['linea'],
                        'motor' => $fila['motor'],
                        'proyecto' => $fila['proyecto'],
                        'objeto_proyecto_paa' => $fila['objeto_proyecto_paa'],
                        'actividad' => $fila['actividad'],
                        'rubro' => $fila['rubro'],
                        'insumo' => $fila['insumo'],
                        'costo_unitario' => $fila['costo_unitario'],
                        'meses' => $fila['meses'],
                        'techo' => $fila['techo'],
                    ];
                    ?>
                    <tr
                        data-fila-tipo="<?= htmlspecialchars($fila['tipo'] ?? '') ?>"
                        data-fila-dependencia="<?= htmlspecialchars($fila['dependencia'] ?? '') ?>"
                        data-fila-sede="<?= htmlspecialchars($fila['sede'] ?? '') ?>"
                        data-fila-linea="<?= htmlspecialchars($fila['linea'] ?? '') ?>"
                        data-fila-rubro="<?= htmlspecialchars($fila['rubro'] ?? '') ?>"
                    >
                        <td>
                            <input
                                type="checkbox"
                                class="checkbox-consolidado"
                                data-tipo="<?= htmlspecialchars($fila['tipo']) ?>"
                                data-anio-id="<?= (int) $anioSeleccionadoId ?>"
                                data-items="<?= htmlspecialchars(json_encode([$itemJs])) ?>"
                                data-puede-editar="<?= !empty($fila['puede_editar']) ? '1' : '0' ?>"
                                data-redireccionado="0"
                            >
                        </td>
                        <td><?= htmlspecialchars($fila['tipo']) ?></td>
                        <td><?= $fila['dependencia'] !== null ? htmlspecialchars($fila['dependencia']) : '—' ?></td>
                        <td><?= htmlspecialchars($fila['sede']) ?></td>
                        <td><?= htmlspecialchars($fila['linea']) ?></td>
                        <td><?= htmlspecialchars($fila['motor']) ?></td>
                        <td><?= htmlspecialchars($fila['proyecto']) ?></td>
                        <td><?= htmlspecialchars($fila['objeto_proyecto_paa']) ?></td>
                        <td><?= htmlspecialchars($fila['actividad']) ?></td>
                        <td><?= htmlspecialchars($fila['rubro']) ?></td>
                        <td><?= htmlspecialchars($fila['insumo']) ?></td>
                        <td data-orden="<?= $fila['cantidad'] !== null ? (float) $fila['cantidad'] : 0 ?>"><?= $fila['cantidad'] !== null ? htmlspecialchars((string) $fila['cantidad']) : '—' ?></td>
                        <td><?= $fila['costo_unitario'] !== null ? number_format($fila['costo_unitario'], 2, ',', '.') : '—' ?></td>
                        <td data-orden="<?= $fila['valor_total'] !== null ? (float) $fila['valor_total'] : 0 ?>"><?= $fila['valor_total'] !== null ? '$ ' . number_format($fila['valor_total'], 2, ',', '.') : '—' ?></td>
                        <td><?= !empty($mesesFila) ? htmlspecialchars(implode(', ', $mesesFila)) : '—' ?></td>
                        <td><?= $fila['techo'] !== null ? '$ ' . number_format($fila['techo'], 2, ',', '.') : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($filas)): ?>
                    <tr>
                        <td colspan="16">No hay elementos consolidados para mostrar.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal-ver-consolidado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Detalle de <span id="ver-consolidado-tipo"></span></h2>
                <button type="button" id="boton-cerrar-modal-ver-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="enlace-modo-jerarquia">
                <a id="enlace-ver-consolidado-completo" href="#">Ver detalle completo (dependencias, techos y rubros) y exportar a Excel &rarr;</a>
            </p>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Cantidad</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="ver-consolidado-cuerpo"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-editar-consolidado" class="modal-fondo" data-anio-id="<?= (int) $anioSeleccionadoId ?>" data-bandeja="">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar <span id="editar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-editar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Cada ítem se edita en el formulario real de su módulo de origen, con todos sus campos. Solo puedes editar los ítems que hoy son tuyos (o cualquiera si eres superadmin).</p>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="editar-consolidado-cuerpo"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-redireccionar-consolidado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Redireccionar <span id="redireccionar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-redireccionar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=consolidado-detalle" class="form-necesidad form-confirmar-envio" data-campo-dependencia="redireccionar-consolidado-dependencia" data-campo-rol="redireccionar-consolidado-rol">
                <input type="hidden" name="accion" value="redireccionar_consolidado">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="tipo_filtro" value="<?= htmlspecialchars($tipoFiltro) ?>">
                <div id="redireccionar-consolidado-campos-items"></div>

                <div class="campo">
                    <label for="redireccionar-consolidado-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'redireccionar-consolidado-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="redireccionar-consolidado-destinatario">¿A quién se enviará? *</label>
                    <select
                        id="redireccionar-consolidado-destinatario"
                        class="selector-rol-destinatario"
                        data-campo-dependencia="redireccionar-consolidado-dependencia"
                        data-campo-rol-oculto="redireccionar-consolidado-rol"
                        data-campo-usuario-oculto="redireccionar-consolidado-usuario"
                        required
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                    <input type="hidden" id="redireccionar-consolidado-rol" name="rol_destinatario_id">
                    <input type="hidden" id="redireccionar-consolidado-usuario" name="usuario_destinatario_id">
                </div>

                <button type="submit" class="boton-enviar">Redireccionar</button>
            </form>
        </div>
    </div>

    <div id="modal-duplicar-consolidado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Duplicar <span id="duplicar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-duplicar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Se creará una copia exacta de cada ítem seleccionado (incluyendo sus conceptos, si aplica), como un ítem nuevo separado dentro del mismo tipo.</p>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody id="duplicar-consolidado-cuerpo"></tbody>
                </table>
            </div>

            <form method="POST" action="index.php?ruta=consolidado-detalle" class="form-necesidad">
                <input type="hidden" name="accion" value="duplicar_consolidado">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="tipo_filtro" value="<?= htmlspecialchars($tipoFiltro) ?>">
                <div id="duplicar-consolidado-campos-items"></div>

                <button type="submit" class="boton-enviar">Duplicar</button>
            </form>
        </div>
    </div>

    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

    <script>
    (function () {
        var filtroRapido = document.getElementById('consolidado-detalle-filtro-rapido');
        var contador = document.getElementById('consolidado-detalle-contador');
        var tabla = document.getElementById('consolidado-detalle-tabla');
        var selectTipo = document.getElementById('consolidado-detalle-tipo');
        var selectDependencia = document.getElementById('consolidado-detalle-dependencia');
        var selectSede = document.getElementById('consolidado-detalle-sede');
        var selectLinea = document.getElementById('consolidado-detalle-linea');
        var selectRubro = document.getElementById('consolidado-detalle-rubro');
        var botonLimpiar = document.getElementById('consolidado-detalle-limpiar-filtros');

        if (!filtroRapido || !contador || !tabla) {
            return;
        }

        var filas = Array.prototype.slice.call(tabla.querySelectorAll('tbody > tr'));

        function aplicarFiltros() {
            var texto = filtroRapido.value.trim().toLowerCase();
            var tipo = selectTipo ? selectTipo.value : '';
            var dependencia = selectDependencia ? selectDependencia.value : '';
            var sede = selectSede ? selectSede.value : '';
            var linea = selectLinea ? selectLinea.value : '';
            var rubro = selectRubro ? selectRubro.value : '';
            var visibles = 0;

            filas.forEach(function (fila) {
                var coincide = true;

                if (tipo !== '' && fila.dataset.filaTipo !== tipo) {
                    coincide = false;
                }
                if (coincide && dependencia !== '' && fila.dataset.filaDependencia !== dependencia) {
                    coincide = false;
                }
                if (coincide && sede !== '' && fila.dataset.filaSede !== sede) {
                    coincide = false;
                }
                if (coincide && linea !== '' && fila.dataset.filaLinea !== linea) {
                    coincide = false;
                }
                if (coincide && rubro !== '' && fila.dataset.filaRubro !== rubro) {
                    coincide = false;
                }
                if (coincide && texto !== '' && fila.textContent.toLowerCase().indexOf(texto) === -1) {
                    coincide = false;
                }

                fila.style.display = coincide ? '' : 'none';
                if (coincide) {
                    visibles++;
                }
            });

            contador.textContent = visibles + ' registro' + (visibles === 1 ? '' : 's');
        }

        filtroRapido.addEventListener('input', aplicarFiltros);
        if (selectTipo) { selectTipo.addEventListener('change', aplicarFiltros); }
        if (selectDependencia) { selectDependencia.addEventListener('change', aplicarFiltros); }
        if (selectSede) { selectSede.addEventListener('change', aplicarFiltros); }
        if (selectLinea) { selectLinea.addEventListener('change', aplicarFiltros); }
        if (selectRubro) { selectRubro.addEventListener('change', aplicarFiltros); }
        if (botonLimpiar) {
            botonLimpiar.addEventListener('click', function () {
                filtroRapido.value = '';
                if (selectTipo) { selectTipo.value = ''; }
                if (selectDependencia) { selectDependencia.value = ''; }
                if (selectSede) { selectSede.value = ''; }
                if (selectLinea) { selectLinea.value = ''; }
                if (selectRubro) { selectRubro.value = ''; }
                aplicarFiltros();
            });
        }
    })();
    </script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
