<?php
/**
 * Estado "Edición" (Épica 2, 2.3) para Solicitudes — pestaña Petición (Otros).
 * Reutiliza los mismos campos e ids (`editar-peticion-*`) que ya poblaba el JS del
 * antiguo modal `#modal-editar-peticion` — solo cambia el contenedor (ya no es un
 * modal) y el botón oculto + auto-click que dispara ese mismo poblado al cargar la
 * página.
 *
 * Variables esperadas: $peticionParaEditar (array), $anioSeleccionadoId,
 * $volverEdicion, $error, $aniosActivos, $roles.
 */
?>
<h2 class="titulo-formulario-edicion">Editar solicitud — Petición</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=solicitudes&tab=otros&anio_id=<?= (int) $anioSeleccionadoId ?>&tipo_solicitud=otros&editar_id=<?= (int) $peticionParaEditar['id'] ?>"
    id="form-editar-peticion"
    class="form-necesidad"
>
    <input type="hidden" name="accion" value="actualizar_peticion">
    <input type="hidden" name="tab" value="otros">
    <input type="hidden" name="id" id="editar-peticion-id" value="">
    <input type="hidden" name="volver" id="editar-peticion-volver" value="">

    <div class="campo">
        <label for="editar-peticion-anio">Año presupuestal *</label>
        <select id="editar-peticion-anio" name="anio_presupuestal_id" required>
            <option value="">Selecciona un año</option>
            <?php foreach ($aniosActivos as $anioOpcion): ?>
            <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-peticion-concepto">Concepto *</label>
        <input type="text" id="editar-peticion-concepto" name="concepto" placeholder="Diligenciar" required>
    </div>

    <div class="campo">
        <label for="editar-peticion-rol">Rol al que se enviará *</label>
        <select id="editar-peticion-rol" name="rol_destinatario_id" required>
            <option value="">Selecciona un rol</option>
            <?php foreach ($roles as $rolOpcion): ?>
            <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-peticion-semestre1">Semestre 1</label>
        <input type="number" id="editar-peticion-semestre1" name="semestre1" min="0" step="1" value="0">
    </div>

    <div class="campo">
        <label for="editar-peticion-valor-s1">Valor S1</label>
        <input type="number" id="editar-peticion-valor-s1" name="valor_s1" min="0" step="0.01" value="0">
    </div>

    <div class="campo">
        <label for="editar-peticion-semestre2">Semestre 2</label>
        <input type="number" id="editar-peticion-semestre2" name="semestre2" min="0" step="1" value="0">
    </div>

    <div class="campo">
        <label for="editar-peticion-valor-s2">Valor S2</label>
        <input type="number" id="editar-peticion-valor-s2" name="valor_s2" min="0" step="0.01" value="0">
    </div>
</form>

<button
    type="button"
    id="boton-editar-peticion-desde-edicion"
    class="fila-menu-editar-peticion boton-editar-fila-generico"
    hidden
    data-peticion="<?= htmlspecialchars(json_encode($peticionParaEditar + ['volver' => $volverEdicion], JSON_UNESCAPED_UNICODE)) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-peticion-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
