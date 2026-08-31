<?php
/**
 * Estado "Edición" (Épica 2, 2.3): reemplaza la tabla mientras se edita un gasto.
 * Reutiliza los mismos campos e ids (`editar-*`) que ya poblaba el JS del antiguo
 * modal `#modal-editar-gasto` — solo cambia el contenedor (ya no es un modal) y el
 * botón oculto + auto-click que dispara ese mismo poblado al cargar la página.
 *
 * Variables esperadas: $gastoParaEditar (array), $anioSeleccionadoId, $volverEdicion,
 * $error, $aniosActivos, $sedes, $tieneHijas, $dependenciaPorDefecto,
 * $dependenciasSugeridas, $proyectos, $rubros, $nombresMesesCompletos.
 */
?>
<h2 class="titulo-formulario-edicion">Editar gasto</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=gastos&anio_id=<?= (int) $anioSeleccionadoId ?>&editar_id=<?= (int) $gastoParaEditar['id'] ?>"
    id="form-editar-gasto"
    class="form-necesidad"
>
    <input type="hidden" name="accion" value="actualizar">
    <input type="hidden" name="id" id="editar-gasto-id" value="">
    <input type="hidden" name="volver" id="editar-gasto-volver" value="">

    <div class="campo">
        <label for="editar-anio_presupuestal_id">Año presupuestal *</label>
        <select id="editar-anio_presupuestal_id" name="anio_presupuestal_id" required>
            <option value="">Selecciona un año</option>
            <?php foreach ($aniosActivos as $anioOpcion): ?>
            <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-sede_id">Sede *</label>
        <select id="editar-sede_id" name="sede_id" required>
            <option value="">Selecciona una sede</option>
            <?php foreach ($sedes as $sede): ?>
            <option value="<?= (int) $sede['id'] ?>"><?= htmlspecialchars($sede['codigo'] . ' - ' . $sede['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="<?= (!$tieneHijas && $dependenciaPorDefecto !== null) ? 'editar-dependencia' : 'editar-dependencia_buscador' ?>">Dependencia *</label>
        <?php if (!$tieneHijas && $dependenciaPorDefecto !== null): ?>
        <select disabled>
            <option selected><?= htmlspecialchars($dependenciaPorDefecto) ?></option>
        </select>
        <input type="hidden" name="dependencia" id="editar-dependencia" value="<?= htmlspecialchars($dependenciaPorDefecto) ?>">
        <?php else: ?>
        <?php
        $idPrefijoDependencia = 'editar-';
        $nombreCampoDependencia = 'dependencia';
        $dependenciasOpciones = $dependenciasSugeridas;
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
        <?php endif; ?>
    </div>

    <?php $idPrefijoProyecto = 'editar-'; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

    <?php $idPrefijoContrato = 'editar-'; require __DIR__ . '/../parciales/selector-contrato-comun.php'; ?>

    <div class="campo campo-ancho">
        <label for="editar-actividad">Actividad *</label>
        <input type="text" id="editar-actividad" name="actividad" placeholder="Diligenciar" required>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-rubro_buscador">Rubro *</label>
        <div class="selector-buscable" id="editar-selector-rubro">
            <input type="text" id="editar-rubro_buscador" class="selector-buscable-input" placeholder="Buscar rubro..." autocomplete="off">
            <input type="hidden" name="rubro_id" id="editar-rubro_id">
            <div class="selector-buscable-lista" id="editar-rubro_lista">
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
        <label for="editar-insumo">Insumo *</label>
        <input type="text" id="editar-insumo" name="insumo" placeholder="Diligenciar" required>
    </div>

    <div class="campo">
        <label for="editar-cantidad">Cantidad *</label>
        <input type="number" id="editar-cantidad" name="cantidad" min="1" step="1" required>
    </div>

    <div class="campo">
        <label for="editar-costo_unitario">Costo unitario *</label>
        <input type="number" id="editar-costo_unitario" name="costo_unitario" min="0" step="0.01" required>
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
    id="boton-editar-gasto-desde-edicion"
    class="boton-editar-gasto boton-editar-fila-generico"
    hidden
    data-gasto="<?= htmlspecialchars(json_encode($gastoParaEditar + ['volver' => $volverEdicion])) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-gasto-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
