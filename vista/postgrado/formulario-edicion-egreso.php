<?php
/**
 * Estado "Edición" (Épica 2, 2.3) para Postgrado — pestaña Egresos.
 * Reutiliza los mismos campos e ids (`editar-egreso-*`) que ya poblaba el JS del
 * antiguo modal `#modal-editar-egreso` — solo cambia el contenedor (ya no es un modal)
 * y el botón oculto + auto-click que dispara ese mismo poblado al cargar la página.
 *
 * Variables esperadas: $egresoParaEditar (array), $anioSeleccionadoId, $volverEdicion,
 * $error, $aniosActivos, $categoriasEgreso, $sedes, $tieneHijas, $dependenciaPorDefecto,
 * $dependenciasSugeridas, $proyectos, $rubros, $nombresMesesCompletos.
 */
?>
<h2 class="titulo-formulario-edicion">Editar egreso</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=postgrado&tab=egresos&anio_id=<?= (int) $anioSeleccionadoId ?>&editar_id=<?= (int) $egresoParaEditar['id'] ?>"
    id="form-editar-egreso"
    class="form-necesidad"
>
    <input type="hidden" name="tab" value="egresos">
    <input type="hidden" name="accion" value="actualizar">
    <input type="hidden" name="id" id="editar-egreso-id" value="">
    <input type="hidden" name="volver" id="editar-egreso-volver" value="">

    <div class="campo">
        <label for="editar-egreso-anio_presupuestal_id">Año presupuestal *</label>
        <select id="editar-egreso-anio_presupuestal_id" name="anio_presupuestal_id" required>
            <option value="">Selecciona un año</option>
            <?php foreach ($aniosActivos as $anioOpcion): ?>
            <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-egreso-categoria">Categoría *</label>
        <select id="editar-egreso-categoria" name="categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categoriasEgreso as $categoriaOpcion): ?>
            <option value="<?= htmlspecialchars($categoriaOpcion) ?>"><?= htmlspecialchars($categoriaOpcion) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-egreso-sede_id">Sede *</label>
        <select id="editar-egreso-sede_id" name="sede_id" required>
            <option value="">Selecciona una sede</option>
            <?php foreach ($sedes as $sede): ?>
            <option value="<?= (int) $sede['id'] ?>"><?= htmlspecialchars($sede['codigo'] . ' - ' . $sede['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="<?= (!$tieneHijas && $dependenciaPorDefecto !== null) ? 'editar-egreso-dependencia' : 'editar-egreso-dependencia_buscador' ?>">Dependencia *</label>
        <?php if (!$tieneHijas && $dependenciaPorDefecto !== null): ?>
        <select disabled>
            <option selected><?= htmlspecialchars($dependenciaPorDefecto) ?></option>
        </select>
        <input type="hidden" name="dependencia" id="editar-egreso-dependencia" value="<?= htmlspecialchars($dependenciaPorDefecto) ?>">
        <?php else: ?>
        <?php
        $idPrefijoDependencia = 'editar-egreso-';
        $nombreCampoDependencia = 'dependencia';
        $dependenciasOpciones = $dependenciasSugeridas;
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
        <?php endif; ?>
    </div>

    <?php $idPrefijoProyecto = 'editar-egreso-'; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

    <?php $idPrefijoContrato = 'editar-egreso-'; require __DIR__ . '/../parciales/selector-contrato-comun.php'; ?>

    <div class="campo campo-ancho">
        <label for="editar-egreso-actividad">Actividad *</label>
        <input type="text" id="editar-egreso-actividad" name="actividad" placeholder="Diligenciar" required>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-egreso-rubro_buscador">Rubro *</label>
        <div class="selector-buscable" id="editar-egreso-selector-rubro">
            <input type="text" id="editar-egreso-rubro_buscador" class="selector-buscable-input" placeholder="Buscar rubro..." autocomplete="off">
            <input type="hidden" name="rubro_id" id="editar-egreso-rubro_id">
            <div class="selector-buscable-lista" id="editar-egreso-rubro_lista">
                <?php foreach ($rubros as $rubro): ?>
                <div class="selector-buscable-opcion" data-id="<?= (int) $rubro['id'] ?>" data-texto="<?= htmlspecialchars($rubro['codigo'] . ' - ' . $rubro['descripcion']) ?>">
                    <?= htmlspecialchars($rubro['codigo'] . ' - ' . $rubro['descripcion']) ?>
                </div>
                <?php endforeach; ?>
                <div class="selector-buscable-vacio">Sin resultados.</div>
            </div>
        </div>
    </div>

    <div class="campo">
        <label for="editar-egreso-insumo">Insumo *</label>
        <input type="text" id="editar-egreso-insumo" name="insumo" placeholder="Diligenciar" required>
    </div>

    <div class="campo">
        <label for="editar-egreso-cantidad">Cantidad *</label>
        <input type="number" id="editar-egreso-cantidad" name="cantidad" min="1" step="1" required>
    </div>

    <div class="campo">
        <label for="editar-egreso-costo_unitario">Costo unitario *</label>
        <input type="number" id="editar-egreso-costo_unitario" name="costo_unitario" min="0" step="0.01" required>
    </div>

    <div class="campo campo-ancho">
        <label>Meses de ejecución *</label>
        <div class="calendario-meses-envoltorio">
            <div class="calendario-meses">
                <div class="calendario-meses-cabecera">Calendario</div>
                <div class="calendario-meses-grilla">
                    <?php foreach ($nombresMesesCompletos as $numero => $nombre): ?>
                    <label class="mes-celda">
                        <input type="checkbox" name="meses[]" value="<?= $numero ?>">
                        <span><?= htmlspecialchars($nombre) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="resumen-meses">
                <h3>Distribución del presupuesto</h3>
                <ul class="lista-meses-seleccionados">
                    <li class="lista-meses-vacio">Selecciona los meses de ejecución.</li>
                </ul>
                <div class="resumen-meses-total">
                    <span>Total</span>
                    <span class="resumen-meses-total-valor">$0.00</span>
                </div>
            </div>
        </div>
    </div>
</form>

<button
    type="button"
    id="boton-editar-egreso-desde-edicion"
    class="boton-editar-egreso boton-editar-fila-generico"
    hidden
    data-gasto="<?= htmlspecialchars(json_encode($egresoParaEditar + ['volver' => $volverEdicion])) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-egreso-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
