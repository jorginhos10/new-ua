<?php
/**
 * Configuraciones > Gastos fijos (solo SA). Mismo formulario que "Registrar gasto" de
 * vista/gastos/index.php — mismos parciales (selector de dependencia, proyecto PDI, contrato común,
 * categoría de gasto), mismos ids y nombres de campo, así lo atiende el mismo JS de app.js (abrir/
 * cerrar #modal-gasto, buscadores, distribución por meses) y de gastos-categoria.js — pero sin
 * techo. Estos gastos solo se reflejan en Análisis de distribución (módulo Gasto).
 *
 * Variables (ver GastoFijoControlador::index()): $gastosFijos, $gastoFijoParaEditar,
 * $anioSeleccionadoId, $aniosActivos, $sedes, $proyectos, $rubros, $contratosComunes,
 * $dependenciasOpciones, $catalogoCategoriasGasto, $mapeoCategoriasGasto, $error, $exito, $erroresFila.
 */
$tituloPagina = 'Gastos fijos';
$nombresMeses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
$nombresMesesCompletos = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$editando = $gastoFijoParaEditar !== null;
$valores = $gastoFijoParaEditar ?? [];
$mesesMarcados = $editando ? array_map('intval', explode(',', (string) $valores['meses'])) : [];
$anioFormulario = $editando ? (int) $valores['anio_presupuestal_id'] : $anioSeleccionadoId;
$totalAnio = array_sum(array_map(static fn (array $g): float => (float) $g['valor_total'], $gastosFijos));
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <div class="tarjeta-encabezado" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div>
                <h1 style="margin: 0;">Gastos fijos</h1>
                <p class="texto-atenuado" style="margin: 0.25rem 0 0;">Solo se reflejan en Análisis &gt; Análisis de distribución (módulo Gasto). No afectan Gastos, Techos, Techos y Metas, Dashboard ni Articulación PDI.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <form method="GET" action="index.php">
                    <input type="hidden" name="ruta" value="gastos-fijos">
                    <select name="anio_id" onchange="this.form.submit()" aria-label="Año presupuestal">
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>" <?= (int) $anioOpcion['id'] === $anioSeleccionadoId ? 'selected' : '' ?>><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <a class="icono-boton" href="index.php?ruta=gastos-fijos-exportar-plantilla" title="Descargar plantilla">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </a>
                <button type="button" class="icono-boton" id="boton-importar-gastos-fijos" title="Importar plantilla">
                    <svg class="svg-importar" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                </button>
                <a class="icono-boton" href="index.php?ruta=gastos-fijos-exportar&amp;anio_id=<?= (int) $anioSeleccionadoId ?>" title="Exportar a Excel">
                    <svg class="svg-exportar" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </a>
                <button type="button" class="boton-agregar" id="boton-abrir-modal-gasto">+ Agregar gasto fijo</button>
            </div>
        </div>

        <form method="POST" action="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>" enctype="multipart/form-data" id="form-importar-gastos-fijos" style="display: none;">
            <input type="hidden" name="accion" value="importar">
            <input type="file" name="archivo" id="archivo-importar-gastos-fijos" accept=".xlsx">
        </form>

        <?php if ($error !== ''): ?>
        <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php if (!empty($erroresFila)): ?>
        <ul class="lista-errores-importacion">
            <?php foreach ($erroresFila as $errorFila): ?>
            <li><?= htmlspecialchars($errorFila) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($exito !== ''): ?>
        <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <p class="texto-atenuado"><?= count($gastosFijos) ?> gasto(s) fijo(s) · Total $<?= number_format($totalAnio, 2, ',', '.') ?></p>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <th>Sede</th>
                        <th>Proyecto PDI</th>
                        <th>Actividad</th>
                        <th>Rubro</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Costo unitario</th>
                        <th>Valor total</th>
                        <th>Meses</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gastosFijos)): ?>
                    <tr><td colspan="11" class="texto-atenuado">No hay gastos fijos para este año.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($gastosFijos as $gastoFijo): ?>
                    <tr>
                        <td><?= htmlspecialchars(Dependencia::nombreVisible($gastoFijo['dependencia'])) ?></td>
                        <td><?= htmlspecialchars($gastoFijo['sede_codigo'] . ' - ' . $gastoFijo['sede_nombre']) ?></td>
                        <td><?= htmlspecialchars($gastoFijo['proyecto_codigo'] . ' - ' . $gastoFijo['proyecto_nombre']) ?></td>
                        <td><?= htmlspecialchars($gastoFijo['actividad']) ?></td>
                        <td><?= htmlspecialchars($gastoFijo['rubro_id'] !== null ? $gastoFijo['rubro_codigo'] . ' - ' . $gastoFijo['rubro_descripcion'] : '—') ?></td>
                        <td><?= htmlspecialchars($gastoFijo['insumo']) ?></td>
                        <td><?= (int) $gastoFijo['cantidad'] ?></td>
                        <td>$<?= number_format((float) $gastoFijo['costo_unitario'], 2, ',', '.') ?></td>
                        <td>$<?= number_format((float) $gastoFijo['valor_total'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars(implode(', ', array_map(static fn ($mes) => $nombresMeses[(int) $mes] ?? $mes, array_filter(explode(',', (string) $gastoFijo['meses']), 'strlen')))) ?></td>
                        <td style="white-space: nowrap;">
                            <a class="boton-accion boton-accion-editar" href="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>&amp;editar_id=<?= (int) $gastoFijo['id'] ?>">Editar</a>
                            <form method="POST" action="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>" style="display: inline;" onsubmit="return confirm('¿Eliminar este gasto fijo? No se puede deshacer.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $gastoFijo['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal-gasto" class="modal-fondo<?= $editando ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2><?= $editando ? 'Editar gasto fijo' : 'Registrar gasto fijo' ?></h2>
                <?php if ($editando): ?>
                <a href="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>" class="modal-cerrar" aria-label="Cerrar">&times;</a>
                <?php else: ?>
                <button type="button" id="boton-cerrar-modal-gasto" class="modal-cerrar" aria-label="Cerrar">&times;</button>
                <?php endif; ?>
            </div>

            <form method="POST" action="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>" class="form-necesidad form-gasto">
                <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
                <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= (int) $valores['id'] ?>">
                <?php endif; ?>

                <div class="campo">
                    <label for="anio_presupuestal_id">Año presupuestal *</label>
                    <select id="anio_presupuestal_id" name="anio_presupuestal_id" required>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>" <?= $anioFormulario === (int) $anioOpcion['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="sede_id">Sede *</label>
                    <select id="sede_id" name="sede_id" required>
                        <option value="">Selecciona una sede</option>
                        <?php foreach ($sedes as $sede): ?>
                        <option value="<?= (int) $sede['id'] ?>" <?= $editando && (int) $valores['sede_id'] === (int) $sede['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sede['codigo'] . ' - ' . $sede['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia';
                    $dependenciasOpciones = $dependenciasOpciones;
                    $dependenciaValorInicial = $editando ? $valores['dependencia'] : null;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <?php $idPrefijoProyecto = ''; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

                <?php $idPrefijoContrato = ''; require __DIR__ . '/../parciales/selector-contrato-comun.php'; ?>

                <div class="campo campo-ancho">
                    <label for="actividad">Actividad *</label>
                    <input type="text" id="actividad" name="actividad" placeholder="Diligenciar" maxlength="255" value="<?= htmlspecialchars((string) ($valores['actividad'] ?? '')) ?>" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="rubro_buscador">Rubro *</label>
                    <div class="selector-buscable" id="selector-rubro">
                        <input type="text" id="rubro_buscador" class="selector-buscable-input" placeholder="Buscar rubro..." autocomplete="off">
                        <input type="hidden" name="rubro_id" id="rubro_id">
                        <div class="selector-buscable-lista" id="rubro_lista">
                            <?php foreach ($rubros as $rubro): ?>
                            <div class="selector-buscable-opcion" data-id="<?= (int) $rubro['id'] ?>" data-texto="<?= htmlspecialchars($rubro['codigo'] . ' - ' . $rubro['descripcion']) ?>">
                                <?= htmlspecialchars($rubro['codigo'] . ' - ' . $rubro['descripcion']) ?>
                            </div>
                            <?php endforeach; ?>
                            <div class="selector-buscable-vacio">Sin resultados.</div>
                        </div>
                    </div>
                </div>

                <div class="par-campos">
                    <div class="campo">
                        <label for="insumo">Insumo *</label>
                        <input type="text" id="insumo" name="insumo" placeholder="Diligenciar" maxlength="200" value="<?= htmlspecialchars((string) ($valores['insumo'] ?? '')) ?>" required>
                    </div>

                    <?php
                    $prefijoCategoria = '';
                    $categoriaSeleccionada = (string) ($valores['categoria_gasto_id'] ?? '');
                    $origenCategoria = (string) ($valores['categoria_origen'] ?? '');
                    $confianzaCategoria = (string) ($valores['categoria_confianza'] ?? '');
                    require __DIR__ . '/../gastos/parciales/selector-categoria-gasto.php';
                    ?>
                </div>

                <div class="par-campos">
                    <div class="campo">
                        <label for="cantidad">Cantidad *</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" step="1" value="<?= $editando ? (int) $valores['cantidad'] : '' ?>" required>
                    </div>

                    <div class="campo">
                        <label for="costo_unitario">Costo unitario *</label>
                        <input type="number" id="costo_unitario" name="costo_unitario" min="0" step="0.01" value="<?= $editando ? htmlspecialchars((string) $valores['costo_unitario']) : '' ?>" required>
                    </div>
                </div>

                <div class="campo campo-ancho">
                    <label>Meses de ejecución *</label>
                    <div class="calendario-meses-envoltorio">
                        <div class="calendario-meses">
                            <div class="calendario-meses-cabecera">Calendario</div>
                            <div class="calendario-meses-grilla">
                                <?php foreach ($nombresMesesCompletos as $numero => $nombre): ?>
                                <label class="mes-celda">
                                    <input type="checkbox" name="meses[]" value="<?= $numero ?>" <?= in_array($numero, $mesesMarcados, true) ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($nombre) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="resumen-meses">
                            <h3>Distribución del presupuesto</h3>
                            <ul id="lista-meses-seleccionados" class="lista-meses-seleccionados">
                                <li class="lista-meses-vacio">Selecciona los meses de ejecución.</li>
                            </ul>
                            <div class="resumen-meses-total">
                                <span>Total</span>
                                <span id="resumen-meses-total-valor" class="resumen-meses-total-valor">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="boton-enviar"><?= $editando ? 'Guardar cambios' : 'Registrar gasto fijo' ?></button>
            </form>
        </div>
    </div>

    <script type="application/json" id="datos-categorias-gasto"><?= json_encode(['catalogo' => $catalogoCategoriasGasto, 'mapeo' => $mapeoCategoriasGasto], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
    <script src="publico/js/categorizador-gastos.js?v=<?= @filemtime(__DIR__ . '/../../publico/js/categorizador-gastos.js') ?: 1 ?>"></script>
    <script src="publico/js/gastos-categoria.js?v=<?= @filemtime(__DIR__ . '/../../publico/js/gastos-categoria.js') ?: 1 ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Importar: el icono abre el selector de archivo y lo envía al elegirlo (igual que en Gastos).
        var botonImportar = document.getElementById('boton-importar-gastos-fijos');
        var archivoImportar = document.getElementById('archivo-importar-gastos-fijos');
        if (botonImportar && archivoImportar) {
            botonImportar.addEventListener('click', function () { archivoImportar.click(); });
            archivoImportar.addEventListener('change', function () {
                if (archivoImportar.files.length > 0) {
                    document.getElementById('form-importar-gastos-fijos').submit();
                }
            });
        }

        <?php if ($editando): ?>
        // Edición: los buscadores (proyecto, contrato, rubro) se precargan con el helper de app.js,
        // y se recalcula la distribución por meses con los valores cargados.
        establecerValorBuscable('proyecto_id', <?= json_encode((string) $valores['proyecto_id']) ?>);
        establecerValorBuscable('objeto_proyecto_paa', <?= json_encode((string) ($valores['objeto_proyecto_paa'] ?? '')) ?>);
        establecerValorBuscable('rubro_id', <?= json_encode((string) ($valores['rubro_id'] ?? '')) ?>);
        var mesMarcado = document.querySelector('#modal-gasto input[name="meses[]"]:checked');
        if (mesMarcado) { mesMarcado.dispatchEvent(new Event('change', { bubbles: true })); }
        <?php endif; ?>
    });
    </script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
