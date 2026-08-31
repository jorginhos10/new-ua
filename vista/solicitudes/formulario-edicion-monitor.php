<?php
/**
 * Estado "Edición" (Épica 2, 2.3) para Solicitudes — pestaña Monitores.
 * Reutiliza los mismos campos e ids (`editar-monitor-*`) que ya poblaba el JS del
 * antiguo modal `#modal-editar-monitor` — solo cambia el contenedor (ya no es un
 * modal) y el botón oculto + auto-click que dispara ese mismo poblado al cargar la
 * página.
 *
 * Variables esperadas: $monitorParaEditar (array), $anioSeleccionadoId,
 * $volverEdicion, $error, $aniosActivos, $dependenciasSugeridas, $roles,
 * $tiposMonitor.
 */
?>
<h2 class="titulo-formulario-edicion">Editar solicitud — Monitores</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=solicitudes&tab=monitores&anio_id=<?= (int) $anioSeleccionadoId ?>&tipo_solicitud=monitores&editar_id=<?= (int) $monitorParaEditar['id'] ?>"
    id="form-editar-monitor"
    class="form-necesidad"
>
    <input type="hidden" name="accion" value="actualizar_monitor">
    <input type="hidden" name="tab" value="monitores">
    <input type="hidden" name="id" id="editar-monitor-id" value="">
    <input type="hidden" name="volver" id="editar-monitor-volver" value="">

    <div class="campo">
        <label for="editar-monitor-anio">Año presupuestal *</label>
        <select id="editar-monitor-anio" name="anio_presupuestal_id" required>
            <option value="">Selecciona un año</option>
            <?php foreach ($aniosActivos as $anioOpcion): ?>
            <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-monitor-dependencia_buscador">Dependencia *</label>
        <?php
        $idPrefijoDependencia = '';
        $nombreCampoDependencia = 'dependencia';
        $idBaseDependenciaOverride = 'editar-monitor-dependencia';
        $dependenciasOpciones = $dependenciasSugeridas;
        $dependenciaDataSelectRol = 'editar-monitor-rol';
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
    </div>

    <div class="campo">
        <label for="editar-monitor-rol">Rol al que se enviará *</label>
        <select id="editar-monitor-rol" name="rol_destinatario_id" required>
            <option value="">Selecciona un rol</option>
            <?php foreach ($roles as $rolOpcion): ?>
            <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-monitor-tipo">Tipo *</label>
        <select id="editar-monitor-tipo" name="tipo" required>
            <option value="">Selecciona un tipo</option>
            <?php foreach ($tiposMonitor as $tipoClave => $tipoNombre): ?>
            <option value="<?= htmlspecialchars($tipoClave) ?>"><?= htmlspecialchars($tipoNombre) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-monitor-semestre1">Monitores semestre I</label>
        <input type="number" id="editar-monitor-semestre1" name="monitores_semestre1" min="0" step="1" value="0">
    </div>

    <div class="campo">
        <label for="editar-monitor-semestre2">Monitores semestre II</label>
        <input type="number" id="editar-monitor-semestre2" name="monitores_semestre2" min="0" step="1" value="0">
    </div>
</form>

<button
    type="button"
    id="boton-editar-monitor-desde-edicion"
    class="fila-menu-editar-monitor boton-editar-fila-generico"
    hidden
    data-monitor="<?= htmlspecialchars(json_encode($monitorParaEditar + ['volver' => $volverEdicion], JSON_UNESCAPED_UNICODE)) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-monitor-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
