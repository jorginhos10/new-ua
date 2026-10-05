<?php
/**
 * Tabla + gráficas de la pestaña "Análisis de distribución" — mismo motor que
 * vista/peticiones/tipo-detalle.php (estructura, tabla-real.js), adaptado: sin botón "Volver" ni
 * exportar (no aplican aquí), y Editar/Eliminar solo se muestran en modo Tiempo real (en
 * Repositorio/Usuario, $filasCompletas ya viene con 'puede_editar' forzado a false desde
 * AnalisisControlador::obtenerFilasAnalisis()).
 *
 * Variables esperadas: $columnas, $clavesFila, $filasCompletas, $anchosColumna,
 * $indicesOcultosPorDefecto, $origenActivo, $vista, $error, $pestanasGastoIngreso (null, o
 * ['egresos' => origen, 'ingresos' => origen, 'activo'] para Extensión/Postgrado — mismas
 * pestañas que peticiones-tipo-detalle).
 */

$pestanasGastoIngreso = $pestanasGastoIngreso ?? null;

$etiquetasModuloTabla = [
    'gasto_principal' => 'Gasto',
    'gasto_extension' => 'Extensión',
    'ingreso_extension' => 'Extensión',
    'gasto_postgrado' => 'Postgrado',
    'ingreso_postgrado' => 'Postgrado',
    'gasto_unisalud' => 'Unisalud',
    'monitores' => 'Monitores',
    'arl' => 'ARL',
];

$extraPestanaAnalisis = [];
if ($vista === 'repositorio' && !empty($snapshotIdActual)) {
    $extraPestanaAnalisis['snapshot_id'] = $snapshotIdActual;
} elseif ($vista === 'usuario' && !empty($dependenciaFiltroActual)) {
    $extraPestanaAnalisis['dependencia'] = $dependenciaFiltroActual;
}
$idTabla = 'tabla-analisis-' . $origenActivo;
?>

<div class="tarjeta tarjeta-tabla" style="flex:1; min-height:0; display:flex; flex-direction:column;">
    <div class="tabla-topbar">
        <h2 class="tabla-nombre"><?= htmlspecialchars($etiquetasModuloTabla[$origenActivo] ?? $origenActivo) ?></h2>

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
                            <input type="checkbox" class="columnas-checkbox" data-indice="<?= $indice ?>"<?= in_array($indice, $indicesOcultosPorDefecto, true) ? '' : ' checked' ?>>
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
                <?php if ($vista === 'tiempo_real'): ?>
                <span class="separador-grupo-basico"></span>
                <button type="button" class="icono-boton" id="tdt-boton-editar" title="Editar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                <button type="button" class="icono-boton" id="tdt-boton-eliminar" title="Eliminar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($pestanasGastoIngreso !== null): ?>
    <div class="pestanas analisis-pestanas-modulo" style="margin: 0 0 0.5rem;">
        <?php foreach (['egresos' => 'Egresos', 'ingresos' => 'Ingresos'] as $clavePestana => $etiquetaPestana): ?>
        <a href="<?= htmlspecialchars(analisisUrl('analisis', $vista, ['origen' => $pestanasGastoIngreso[$clavePestana]] + $extraPestanaAnalisis)) ?>" class="pestana<?= $pestanasGastoIngreso['activo'] === $clavePestana ? ' activa' : '' ?>"><?= $etiquetaPestana ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (empty($filasCompletas)): ?>
    <p class="texto-atenuado">No hay elementos para mostrar.</p>
    <?php else: ?>
    <?php require_once __DIR__ . '/../../../modelo/FilasTabla.php'; ?>
    <script type="application/json" id="tdt-filas"><?= json_encode(FilasTabla::paraJson($filasCompletas, $clavesFila), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
    <div class="tabla-scroll">
        <table class="tabla-dev-datos" id="<?= htmlspecialchars($idTabla) ?>" data-origen="<?= htmlspecialchars($origenActivo) ?>" data-estado="analisis">
            <colgroup>
                <col style="width: 34px;">
                <?php foreach ($columnas as $indice => $columna): ?>
                <col id="tdt-col-<?= $indice ?>" style="width: <?= $anchosColumna[$indice] ?>px;<?= in_array($indice, $indicesOcultosPorDefecto, true) ? ' visibility: collapse;' : '' ?>">
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
            <tbody></tbody>
            <tfoot>
                <tr class="fila-totales-tdt">
                    <td class="col-seleccion">Total</td>
                    <?php foreach ($columnas as $columna): ?>
                    <td></td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>

        <div class="tabla-grafica" id="tdt-grafica" hidden>
            <div class="tabla-grafica-kpis" id="tdt-grafica-kpis"></div>
            <div class="tabla-grafica-paneles" id="tdt-grafica-paneles"></div>
        </div>

        <div class="grafica-tooltip" id="tdt-grafica-tooltip"></div>
    </div>
    <?php endif; ?>
</div>

<?php if ($vista === 'tiempo_real'): ?>
<form method="POST" id="tdt-form-accion" action="<?= htmlspecialchars(analisisUrl('analisis', $vista, ['origen' => $origenActivo])) ?>" style="display:none;">
    <input type="hidden" name="accion" id="tdt-form-accion-valor" value="">
    <input type="hidden" name="origen_id" id="tdt-form-origen-id" value="">
</form>
<?php endif; ?>

<script>
var tdtColumnas = <?php echo json_encode($columnas, JSON_UNESCAPED_UNICODE); ?>;
var tdtClavesFila = <?php echo json_encode($clavesFila, JSON_UNESCAPED_UNICODE); ?>;
var tdtNamespace = <?php echo json_encode('analisis_' . $origenActivo); ?>;
</script>
<script src="publico/js/tabla-real.js?v=<?= filemtime(__DIR__ . '/../../../publico/js/tabla-real.js') ?>"></script>
