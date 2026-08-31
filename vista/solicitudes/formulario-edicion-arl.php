<?php
/**
 * Estado "Edición" (Épica 2, 2.3) para Solicitudes — pestaña ARL.
 * Reutiliza los mismos campos e ids (`editar-solicitud-*`) que ya poblaba el JS del
 * antiguo modal `#modal-editar-solicitud` — solo cambia el contenedor (ya no es un
 * modal) y el botón oculto + auto-click que dispara ese mismo poblado al cargar la
 * página.
 *
 * Variables esperadas: $arlParaEditar (array), $anioSeleccionadoId, $volverEdicion,
 * $error, $aniosActivos, $dependenciasSugeridas, $roles, $numerosRomanos,
 * $smlvPorAnio, $porcentajesRiesgo.
 */
?>
<h2 class="titulo-formulario-edicion">Editar solicitud — ARL de estudiantes en prácticas</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=solicitudes&anio_id=<?= (int) $anioSeleccionadoId ?>&tipo_solicitud=arl&editar_id=<?= (int) $arlParaEditar['id'] ?>"
    id="form-editar-solicitud-arl"
    class="form-necesidad"
    data-smlv-por-anio="<?= htmlspecialchars(json_encode($smlvPorAnio)) ?>"
    data-porcentajes-riesgo="<?= htmlspecialchars(json_encode($porcentajesRiesgo)) ?>"
>
    <input type="hidden" name="accion" value="actualizar">
    <input type="hidden" name="tab" value="arl">
    <input type="hidden" name="id" id="editar-solicitud-id" value="">
    <input type="hidden" name="volver" id="editar-solicitud-volver" value="">

    <div class="campo">
        <label for="editar-solicitud-anio">Año presupuestal *</label>
        <select id="editar-solicitud-anio" name="anio_presupuestal_id" required>
            <option value="">Selecciona un año</option>
            <?php foreach ($aniosActivos as $anioOpcion): ?>
            <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-solicitud-facultad_buscador">Facultad *</label>
        <?php
        $idPrefijoDependencia = '';
        $nombreCampoDependencia = 'facultad';
        $idBaseDependenciaOverride = 'editar-solicitud-facultad';
        $dependenciasOpciones = $dependenciasSugeridas;
        $dependenciaDataSelectRol = 'editar-solicitud-rol';
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
    </div>

    <div class="campo">
        <label for="editar-solicitud-rol">Rol al que se enviará *</label>
        <select id="editar-solicitud-rol" name="rol_destinatario_id" required>
            <option value="">Selecciona un rol</option>
            <?php foreach ($roles as $rolOpcion): ?>
            <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo campo-ancho">
        <label>Riesgos ARL *</label>
        <div class="tabla-scroll">
            <table class="tabla-usuarios tabla-solicitud-riesgos">
                <thead>
                    <tr>
                        <th>Clase de riesgo</th>
                        <th>Número de estudiantes</th>
                        <th>Valor anual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($numerosRomanos as $nivel => $numero): ?>
                    <tr>
                        <td>Riesgo <?= $numero ?></td>
                        <td>
                            <label class="etiqueta-oculta" for="editar-solicitud-riesgo<?= $nivel ?>_estudiantes">Número de estudiantes Riesgo <?= $numero ?></label>
                            <input type="number" id="editar-solicitud-riesgo<?= $nivel ?>_estudiantes" name="riesgo<?= $nivel ?>_estudiantes" class="campo-solicitud-estudiantes" data-nivel="<?= $nivel ?>" min="0" step="1" value="0">
                        </td>
                        <td>
                            <label class="etiqueta-oculta" for="editar-solicitud-riesgo<?= $nivel ?>_valor">Valor anual Riesgo <?= $numero ?></label>
                            <input type="text" id="editar-solicitud-riesgo<?= $nivel ?>_valor" name="riesgo<?= $nivel ?>_valor" class="campo-solicitud-valor" value="0.00" readonly>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<button
    type="button"
    id="boton-editar-solicitud-desde-edicion"
    class="fila-menu-editar-solicitud boton-editar-fila-generico"
    hidden
    data-solicitud="<?= htmlspecialchars(json_encode($arlParaEditar + ['volver' => $volverEdicion], JSON_UNESCAPED_UNICODE)) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-solicitud-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
