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

    <div class="tarjeta gastos-fijos">
        <?php
        // Misma barra estándar que Gastos (vista/parciales/barra-modulo.php): volver a
        // Configuraciones · título + año · píldoras de íconos (buscar · plantilla/importar/exportar)
        // · acción principal.
        $anioTextoActual = '';
        foreach ($aniosActivos as $anioOpcion) {
            if ((int) $anioOpcion['id'] === $anioSeleccionadoId) {
                $anioTextoActual = (string) $anioOpcion['anio'];
            }
        }
        ob_start();
        ?>
        <form method="GET" action="index.php" class="gastos-fijos-anio">
            <input type="hidden" name="ruta" value="gastos-fijos">
            <select name="anio_id" onchange="this.form.submit()" aria-label="Año presupuestal" title="Año presupuestal">
                <?php foreach ($aniosActivos as $anioOpcion): ?>
                <option value="<?= (int) $anioOpcion['id'] ?>" <?= (int) $anioOpcion['id'] === $anioSeleccionadoId ? 'selected' : '' ?>><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php
        $barraTituloExtra = ob_get_clean();
        $barraTitulo = 'Gastos fijos';
        $barraEstado = 'consulta';
        $barraRutaVolver = 'index.php?ruta=configuraciones';
        $barraTextoVolver = 'Volver a Configuraciones';
        $barraGruposIconos = [
            'basico' => [],
            'especifico' => [],
            'datos' => [
                ['icono' => 'plantilla', 'etiqueta' => 'Descargar plantilla (.xlsx)', 'tipo' => 'a', 'href' => 'index.php?ruta=gastos-fijos-exportar-plantilla'],
                ['id' => 'boton-importar-gastos-fijos', 'icono' => 'importar', 'etiqueta' => 'Importar gastos fijos (.xlsx)'],
                ['icono' => 'exportar', 'etiqueta' => 'Exportar gastos fijos (.xlsx)', 'tipo' => 'a', 'href' => 'index.php?ruta=gastos-fijos-exportar&anio_id=' . (int) $anioSeleccionadoId, 'disabled' => empty($gastosFijos), 'titulo_disabled' => 'No hay gastos fijos para exportar en este año'],
            ],
        ];
        $barraBuscar = empty($gastosFijos) ? null : '[data-buscar-gastos-fijos]';
        $barraBotonEnviar = null;
        $barraBotonPrincipal = ['id' => 'boton-abrir-modal-gasto', 'etiqueta' => '+ Agregar gasto fijo'];
        require __DIR__ . '/../parciales/barra-modulo.php';

        $dependenciasDistintas = count(array_unique(array_column($gastosFijos, 'dependencia')));
        ?>

        <form method="POST" action="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>" enctype="multipart/form-data" id="form-importar-gastos-fijos" style="display: none;">
            <input type="hidden" name="accion" value="importar">
            <input type="file" name="archivo" id="archivo-importar-gastos-fijos" accept=".xlsx">
        </form>

        <div class="gastos-fijos-aviso">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <span>Estos gastos <strong>solo se reflejan en Análisis &rsaquo; Análisis de distribución</strong> (módulo Gasto, como "Gasto fijo"). No afectan Gastos, Techos, Techos y Metas, Dashboard ni Articulación PDI.</span>
        </div>

        <?php if ($error !== ''): ?>
        <div class="mensaje-error">
            <?= htmlspecialchars($error) ?>
            <?php if (!empty($erroresFila)): ?>
            <ul class="lista-errores-importacion">
                <?php foreach ($erroresFila as $errorFila): ?>
                <li><?= htmlspecialchars($errorFila) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($exito !== ''): ?>
        <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (empty($gastosFijos)): ?>
        <div class="gastos-fijos-vacio">
            <span class="gastos-fijos-vacio-icono" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><line x1="12" y1="14" x2="12" y2="18"></line><line x1="10" y1="16" x2="14" y2="16"></line></svg>
            </span>
            <h2>Aún no hay gastos fijos para <?= htmlspecialchars($anioTextoActual) ?></h2>
            <p class="texto-atenuado">Regístralos uno a uno con el mismo formulario de Gastos, o cárgalos en bloque desde la plantilla de Excel.</p>
            <div class="gastos-fijos-vacio-acciones">
                <button type="button" class="boton-agregar" onclick="document.getElementById('boton-abrir-modal-gasto').click();">+ Agregar gasto fijo</button>
                <a class="boton-secundario" href="index.php?ruta=gastos-fijos-exportar-plantilla">Descargar plantilla</a>
                <button type="button" class="boton-secundario" onclick="document.getElementById('boton-importar-gastos-fijos').click();">Importar Excel</button>
            </div>
        </div>
        <?php else: ?>
        <div class="chips-gasto gastos-fijos-chips">
            <span class="chip-gasto chip-gasto-techo-total">Total <?= htmlspecialchars($anioTextoActual) ?>: $<?= number_format($totalAnio, 2, ',', '.') ?></span>
            <span class="chip-gasto chip-gasto-propio"><?= count($gastosFijos) ?> gasto<?= count($gastosFijos) === 1 ? '' : 's' ?> fijo<?= count($gastosFijos) === 1 ? '' : 's' ?></span>
            <span class="chip-gasto chip-gasto-propio"><?= $dependenciasDistintas ?> dependencia<?= $dependenciasDistintas === 1 ? '' : 's' ?></span>
        </div>

        <div class="tabla-scroll gastos-fijos-tabla" data-buscar-gastos-fijos>
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <th>Proyecto PDI</th>
                        <th>Actividad / insumo</th>
                        <th>Rubro</th>
                        <th class="celda-numero">Cantidad × costo</th>
                        <th class="celda-numero">Valor total</th>
                        <th>Meses</th>
                        <th class="celda-acciones"><span class="sr-solo">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gastosFijos as $gastoFijo): ?>
                    <tr class="<?= $editando && (int) $valores['id'] === (int) $gastoFijo['id'] ? 'fila-editando' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars(Dependencia::nombreVisible($gastoFijo['dependencia'])) ?></strong>
                            <span class="gastos-fijos-secundario"><?= htmlspecialchars($gastoFijo['sede_codigo'] . ' - ' . $gastoFijo['sede_nombre']) ?></span>
                        </td>
                        <td><span class="gastos-fijos-recortado" title="<?= htmlspecialchars($gastoFijo['proyecto_codigo'] . ' - ' . $gastoFijo['proyecto_nombre']) ?>"><?= htmlspecialchars($gastoFijo['proyecto_codigo'] . ' - ' . $gastoFijo['proyecto_nombre']) ?></span></td>
                        <td>
                            <?= htmlspecialchars($gastoFijo['actividad']) ?>
                            <span class="gastos-fijos-secundario"><?= htmlspecialchars($gastoFijo['insumo']) ?></span>
                        </td>
                        <td><span class="gastos-fijos-recortado" title="<?= htmlspecialchars($gastoFijo['rubro_id'] !== null ? $gastoFijo['rubro_codigo'] . ' - ' . $gastoFijo['rubro_descripcion'] : '—') ?>"><?= htmlspecialchars($gastoFijo['rubro_id'] !== null ? $gastoFijo['rubro_codigo'] . ' - ' . $gastoFijo['rubro_descripcion'] : '—') ?></span></td>
                        <td class="celda-numero">
                            <?= (int) $gastoFijo['cantidad'] ?> × $<?= number_format((float) $gastoFijo['costo_unitario'], 2, ',', '.') ?>
                        </td>
                        <td class="celda-numero"><strong>$<?= number_format((float) $gastoFijo['valor_total'], 2, ',', '.') ?></strong></td>
                        <td>
                            <span class="gastos-fijos-meses">
                                <?php foreach (array_filter(explode(',', (string) $gastoFijo['meses']), 'strlen') as $mes): ?>
                                <span class="gastos-fijos-mes"><?= htmlspecialchars($nombresMeses[(int) $mes] ?? $mes) ?></span>
                                <?php endforeach; ?>
                            </span>
                        </td>
                        <td class="celda-acciones">
                            <a class="boton-icono-accion" href="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>&amp;editar_id=<?= (int) $gastoFijo['id'] ?>" title="Editar" aria-label="Editar">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            </a>
                            <form method="POST" action="index.php?ruta=gastos-fijos&amp;anio_id=<?= (int) $anioSeleccionadoId ?>" onsubmit="return confirm('¿Eliminar este gasto fijo? No se puede deshacer.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $gastoFijo['id'] ?>">
                                <button type="submit" class="boton-icono-accion gastos-fijos-eliminar" title="Eliminar" aria-label="Eliminar">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5"><strong>Total</strong></td>
                        <td class="celda-numero"><strong>$<?= number_format($totalAnio, 2, ',', '.') ?></strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .gastos-fijos-anio select {
            padding: 0.3rem 1.8rem 0.3rem 0.75rem;
            border: 1px solid var(--color-borde);
            border-radius: 999px;
            background-color: var(--color-superficie);
            font: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--color-texto);
            cursor: pointer;
        }

        .gastos-fijos-aviso {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin: 0.75rem 0 1rem;
            padding: 0.7rem 0.9rem;
            border-radius: var(--radio-chico);
            background: var(--color-primario-suave);
            color: var(--color-texto-secundario);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .gastos-fijos-aviso svg {
            flex-shrink: 0;
            color: var(--color-primario);
            margin-top: 0.1rem;
        }

        .gastos-fijos-chips {
            margin: 0 0 0.85rem;
        }

        .gastos-fijos-vacio {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.4rem;
            padding: 2.5rem 1rem;
            border: 1px dashed var(--color-borde);
            border-radius: var(--radio);
        }

        .gastos-fijos-vacio-icono {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--color-primario-suave);
            color: var(--color-primario);
            margin-bottom: 0.4rem;
        }

        .gastos-fijos-vacio h2 {
            margin: 0;
            font-size: 1.1rem;
        }

        .gastos-fijos-vacio p {
            margin: 0 0 0.6rem;
            max-width: 460px;
        }

        .gastos-fijos-vacio-acciones {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
        }

        .gastos-fijos-tabla td {
            vertical-align: top;
        }

        .gastos-fijos-tabla tbody tr:hover {
            background: var(--color-fondo);
        }

        .gastos-fijos-tabla tr.fila-editando {
            background: var(--color-primario-suave);
        }

        .gastos-fijos-tabla .celda-numero {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .gastos-fijos-tabla .celda-acciones {
            width: 1%;
            white-space: nowrap;
        }

        .gastos-fijos-tabla .celda-acciones form {
            display: inline;
        }

        .gastos-fijos-tabla tfoot td {
            border-top: 2px solid var(--color-borde-hover);
            background: var(--color-fondo);
        }

        .gastos-fijos-secundario {
            display: block;
            font-size: 0.78rem;
            color: var(--color-texto-tenue);
            margin-top: 0.15rem;
        }

        .gastos-fijos-recortado {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
            max-width: 260px;
        }

        .gastos-fijos-meses {
            display: flex;
            flex-wrap: wrap;
            gap: 0.2rem;
            max-width: 200px;
        }

        .gastos-fijos-mes {
            padding: 0.05rem 0.4rem;
            border-radius: 999px;
            background: var(--color-gris-fondo);
            color: var(--color-gris-texto);
            font-size: 0.72rem;
        }

        .gastos-fijos-eliminar:hover {
            color: var(--color-error-texto);
        }

        .sr-solo {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
        }

        @media (max-width: 640px) {
            .gastos-fijos-recortado {
                max-width: 180px;
            }
        }
    </style>

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
