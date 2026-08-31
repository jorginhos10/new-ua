<?php
/**
 * Estado "Edición" (Épica 2, 2.3) para Unisalud — pestaña Ingresos.
 * Reutiliza los mismos campos e ids (`editar-ingreso-*`) que ya poblaba el JS del
 * antiguo modal `#modal-editar-ingreso` — solo cambia el contenedor (ya no es un modal)
 * y el botón oculto + auto-click que dispara ese mismo poblado al cargar la página.
 *
 * Variables esperadas: $ingresoParaEditar (array), $anioSeleccionadoId, $volverEdicion,
 * $error, $aniosActivos, $tieneHijas, $dependenciaPorDefecto, $dependenciasSugeridas.
 */
?>
<h2 class="titulo-formulario-edicion">Editar ingreso</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=unisalud&tab=ingresos&anio_id=<?= (int) $anioSeleccionadoId ?>&editar_id=<?= (int) $ingresoParaEditar['id'] ?>"
    id="form-editar-ingreso"
    class="form-necesidad"
>
    <input type="hidden" name="tab" value="ingresos">
    <input type="hidden" name="accion" value="actualizar">
    <input type="hidden" name="id" id="editar-ingreso-id" value="">
    <input type="hidden" name="volver" id="editar-ingreso-volver" value="">

    <div class="campo">
        <label for="editar-ingreso-anio_presupuestal_id">Año presupuestal *</label>
        <select id="editar-ingreso-anio_presupuestal_id" name="anio_presupuestal_id" required>
            <option value="">Selecciona un año</option>
            <?php foreach ($aniosActivos as $anioOpcion): ?>
            <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="<?= (!$tieneHijas && $dependenciaPorDefecto !== null) ? 'editar-ingreso-dependencia' : 'editar-ingreso-dependencia_buscador' ?>">Dependencia *</label>
        <?php if (!$tieneHijas && $dependenciaPorDefecto !== null): ?>
        <select disabled>
            <option selected><?= htmlspecialchars($dependenciaPorDefecto) ?></option>
        </select>
        <input type="hidden" name="dependencia" id="editar-ingreso-dependencia" value="<?= htmlspecialchars($dependenciaPorDefecto) ?>">
        <?php else: ?>
        <?php
        $idPrefijoDependencia = 'editar-ingreso-';
        $nombreCampoDependencia = 'dependencia';
        $dependenciasOpciones = $dependenciasSugeridas;
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
        <?php endif; ?>
    </div>

    <div class="campo campo-ancho">
        <label>Conceptos *</label>
        <div class="filas-conceptos" id="editar-filas-conceptos">
            <div class="fila-concepto">
                <div class="campo">
                    <label>Concepto</label>
                    <input type="text" name="concepto[]" placeholder="Ej. Matrícula">
                </div>
                <div class="campo">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad_concepto[]" min="1" step="1">
                </div>
                <div class="campo">
                    <label>Valor unitario</label>
                    <input type="number" name="valor_concepto[]" min="0" step="0.01">
                </div>
                <button type="button" class="boton-quitar-fila" aria-label="Quitar concepto">&times;</button>
            </div>
        </div>
        <button type="button" class="boton-agregar-fila">+ Agregar concepto</button>
    </div>

    <div class="campo">
        <label for="editar-ingreso-concepto_adicional">Concepto adicional</label>
        <input type="text" id="editar-ingreso-concepto_adicional" name="concepto_adicional" placeholder="Opcional">
    </div>

    <div class="campo">
        <label for="editar-ingreso-valor_adicional">Valor adicional</label>
        <input type="number" id="editar-ingreso-valor_adicional" name="valor_adicional" min="0" step="0.01" placeholder="0.00">
    </div>
</form>

<button
    type="button"
    id="boton-editar-ingreso-desde-edicion"
    class="boton-editar-ingreso boton-editar-fila-generico"
    hidden
    data-ingreso="<?= htmlspecialchars(json_encode($ingresoParaEditar + ['volver' => $volverEdicion])) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-ingreso-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
