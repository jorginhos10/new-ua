<?php
/**
 * Landing único de "Ver" en Peticiones: la tabla real de un origen (mismo estado/bandeja en la
 * que estaba el ítem clicado), con esa fila resaltada. Estructura y mecánica (buscar, filtrar por
 * columna, ordenar, ocultar/redimensionar columnas, vista de gráfica) portadas literalmente del
 * prototipo Dev > Tabla (vista/dev/pruebas/tabla.php) — con Editar/Eliminar reales (no el demo en
 * memoria del prototipo), reutilizando el modelo real de cada origen vía
 * PeticionesControlador::procesarAccionCeldaTipoDetalle().
 *
 * Variables esperadas del controlador: $columnas, $clavesFila, $filasCompletas, $resaltarId,
 * $origen, $estado, $anioSeleccionadoId, $tituloPagina, $rutaVolver, $camposEditables, $error.
 */
require __DIR__ . '/../parciales/encabezado.php';

$idTabla = 'tabla-' . $origen . '-' . $estado;
?>

<div class="tarjeta tarjeta-tabla">
    <div class="tabla-topbar">
        <a href="<?= htmlspecialchars($rutaVolver) ?>" class="tabla-boton-volver" title="Volver">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </a>

        <h2 class="tabla-nombre"><?= htmlspecialchars($tituloPagina) ?></h2>

        <div class="tabla-acciones">
            <div class="grupo-iconos grupo-basico" data-grupo="basico">
                <?php if (!empty($filasCompletas)): ?>
                <div class="buscar-envoltorio">
                    <button type="button" class="icono-boton" id="tdt-boton-buscar" title="Buscar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                    <div class="combo-envoltorio combo-buscar" id="tdt-combo-buscar">
                        <div class="combo-fantasma" aria-hidden="true"></div>
                        <input type="text" id="tdt-campo-buscar" class="combo-input" placeholder="Buscar en la tabla…" autocomplete="off">
                    </div>
                </div>
                <button type="button" class="icono-boton" id="tdt-boton-filtrar" title="Filtrar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                </button>
                <div class="columnas-envoltorio">
                    <button type="button" class="icono-boton" id="tdt-boton-columnas" title="Mostrar u ocultar columnas">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="18" rx="1"></rect><rect x="14" y="3" width="7" height="18" rx="1"></rect></svg>
                    </button>
                    <div class="combo-tarjeta columnas-tarjeta" id="tdt-columnas-tarjeta">
                        <?php foreach ($columnas as $indice => $columna): ?>
                        <label class="columnas-tarjeta-item">
                            <input type="checkbox" class="columnas-checkbox" data-indice="<?= $indice ?>" checked>
                            <?= htmlspecialchars($columna) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <span class="separador-grupo-basico"></span>
                <?php endif; ?>
                <button type="button" class="icono-boton activo" id="tdt-boton-vista-tabla" title="Vista de tabla" aria-pressed="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="9" x2="9" y2="21"></line></svg>
                </button>
                <button type="button" class="icono-boton" id="tdt-boton-vista-grafica" title="Vista de gráfica (dashboard)" aria-pressed="false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </button>
                <span class="separador-grupo-basico"></span>
                <button type="button" class="icono-boton" id="tdt-boton-editar" title="Editar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                <button type="button" class="icono-boton" id="tdt-boton-eliminar" title="Eliminar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                </button>
                <?php if (!empty($filasCompletas)): ?>
                <span class="separador-grupo-basico"></span>
                <a href="index.php?ruta=peticiones-tipo-detalle&estado=<?= urlencode($estado) ?>&origen=<?= urlencode($origen) ?>&anio_id=<?= (int) $anioSeleccionadoId ?>&exportar=xlsx" class="icono-boton icono-exportar" title="Exportar a Excel">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
    <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (empty($filasCompletas)): ?>
    <p class="texto-atenuado">No hay elementos para mostrar.</p>
    <?php else: ?>
    <div class="tabla-scroll">
        <table class="tabla-dev-datos" id="<?= htmlspecialchars($idTabla) ?>" data-origen="<?= htmlspecialchars($origen) ?>" data-estado="<?= htmlspecialchars($estado) ?>">
            <colgroup>
                <col style="width: 34px;">
                <?php foreach ($columnas as $indice => $columna): ?>
                <col id="tdt-col-<?= $indice ?>" style="width: 150px;">
                <?php endforeach; ?>
            </colgroup>
            <thead>
                <tr class="fila-encabezados">
                    <th class="col-seleccion"><input type="checkbox" id="tdt-seleccionar-todo" title="Seleccionar todo"></th>
                    <?php foreach ($columnas as $indice => $columna): ?>
                    <th>
                        <span class="encabezado-columna" data-indice="<?= $indice ?>">
                            <?= htmlspecialchars($columna) ?>
                            <button type="button" class="boton-ordenar" title="Ordenar por <?= htmlspecialchars($columna) ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 15 12 20 17 15"></polyline><polyline points="7 9 12 4 17 9"></polyline></svg>
                            </button>
                        </span>
                        <span class="redimensionador-columna" data-indice="<?= $indice ?>" title="Arrastra para cambiar el ancho"></span>
                    </th>
                    <?php endforeach; ?>
                </tr>
                <tr class="fila-filtros">
                    <th></th>
                    <?php foreach ($columnas as $indice => $columna): ?>
                    <th>
                        <div class="combo-envoltorio combo-filtro combo-filtro-columna" data-indice="<?= $indice ?>">
                            <div class="combo-fantasma" aria-hidden="true"></div>
                            <input type="text" class="filtro-columna combo-input" data-indice="<?= $indice ?>" placeholder="Filtrar…" autocomplete="off">
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filasCompletas as $filaCompleta): ?>
                <tr
                    data-origen-id="<?= (int) $filaCompleta['origen_id'] ?>"
                    class="<?= (int) $filaCompleta['origen_id'] === (int) $resaltarId ? 'fila-resaltada' : '' ?>"
                >
                    <td class="col-seleccion"><input type="checkbox" class="tabla-seleccion-fila"></td>
                    <?php foreach ($clavesFila as $indice => $clave): ?>
                    <?php
                    $valorCelda = $filaCompleta[$clave] ?? '—';
                    $esEditable = !empty($camposEditables) && in_array($clave, array_column($camposEditables, 'clave'), true);
                    if (is_float($valorCelda)) {
                        $valorCelda = number_format($valorCelda, 2, ',', '.');
                    }
                    // Algunos campos se muestran formateados para lectura/gráfica (ej. "meses" como
                    // Ene/Feb) pero deben editarse en su forma cruda (ej. "1,2") — 'meses_crudo' es
                    // ese valor real, si existe para esta clave.
                    $valorCrudo = $filaCompleta[$clave . '_crudo'] ?? $valorCelda;
                    ?>
                    <td<?= $esEditable ? ' data-editable="' . htmlspecialchars($clave) . '" data-valor-crudo="' . htmlspecialchars((string) $valorCrudo) . '"' : '' ?>><?= htmlspecialchars((string) $valorCelda) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="tabla-grafica" id="tdt-grafica" hidden>
            <div class="tabla-grafica-kpis" id="tdt-grafica-kpis"></div>
            <div class="tabla-grafica-paneles" id="tdt-grafica-paneles"></div>
        </div>

        <div class="grafica-tooltip" id="tdt-grafica-tooltip"></div>
    </div>
    <?php endif; ?>
</div>

<form method="POST" id="tdt-form-accion" action="index.php?ruta=peticiones-tipo-detalle&estado=<?= urlencode($estado) ?>&origen=<?= urlencode($origen) ?>&anio_id=<?= (int) $anioSeleccionadoId ?>&resaltar_id=<?= (int) $resaltarId ?>" style="display:none;">
    <input type="hidden" name="accion" id="tdt-form-accion-valor" value="">
    <input type="hidden" name="origen_id" id="tdt-form-origen-id" value="">
    <?php foreach ($camposEditables as $campo): ?>
    <input type="hidden" name="<?= htmlspecialchars($campo['clave']) ?>" id="tdt-form-campo-<?= htmlspecialchars($campo['clave']) ?>" value="">
    <?php endforeach; ?>
</form>

<script>
var tdtCamposEditables = <?php echo json_encode(array_column($camposEditables, 'clave')); ?>;
var tdtCamposEditablesInfo = <?php echo json_encode($camposEditables, JSON_UNESCAPED_UNICODE); ?>;
var tdtColumnas = <?php echo json_encode($columnas, JSON_UNESCAPED_UNICODE); ?>;
var tdtClavesFila = <?php echo json_encode($clavesFila, JSON_UNESCAPED_UNICODE); ?>;
var tdtNamespace = <?php echo json_encode($origen . '_' . $estado); ?>;
</script>
<script src="publico/js/tabla-real.js"></script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
