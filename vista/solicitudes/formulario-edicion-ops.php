<?php
/**
 * Estado "Edición" (Épica 2, 2.3) para Solicitudes — pestaña OPS.
 * Reutiliza los mismos campos e ids (`editar-ops-*`) que ya poblaba el JS del
 * antiguo modal `#modal-editar-ops` — solo cambia el contenedor (ya no es un modal)
 * y el botón oculto + auto-click que dispara ese mismo poblado al cargar la página.
 *
 * Variables esperadas: $opsParaEditar (array), $anioSeleccionadoId, $volverEdicion,
 * $error, $aniosActivos, $sedes, $proyectos, $dependenciasSugeridas, $roles,
 * $rubros, $perfilesOps.
 */
?>
<h2 class="titulo-formulario-edicion">Editar solicitud — OPS prestación de servicios</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=solicitudes&tab=ops&anio_id=<?= (int) $anioSeleccionadoId ?>&tipo_solicitud=ops&editar_id=<?= (int) $opsParaEditar['id'] ?>"
    id="form-editar-ops"
    class="form-necesidad"
>
    <input type="hidden" name="accion" value="actualizar_ops">
    <input type="hidden" name="tab" value="ops">
    <input type="hidden" name="id" id="editar-ops-id" value="">
    <input type="hidden" name="volver" id="editar-ops-volver" value="">

    <div class="campo">
        <label for="editar-ops-anio">Año presupuestal *</label>
        <select id="editar-ops-anio" name="anio_presupuestal_id" required>
            <option value="">Selecciona un año</option>
            <?php foreach ($aniosActivos as $anioOpcion): ?>
            <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-ops-sede">Sede *</label>
        <select id="editar-ops-sede" name="sede_id" required>
            <option value="">Selecciona una sede</option>
            <?php foreach ($sedes as $sede): ?>
            <option value="<?= (int) $sede['id'] ?>"><?= htmlspecialchars($sede['codigo'] . ' - ' . $sede['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php $idPrefijoProyecto = 'editar-ops-'; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

    <div class="campo">
        <label for="editar-ops-dependencia_buscador">Dependencia académico/administrativa *</label>
        <?php
        $idPrefijoDependencia = '';
        $nombreCampoDependencia = 'dependencia';
        $idBaseDependenciaOverride = 'editar-ops-dependencia';
        $dependenciasOpciones = $dependenciasSugeridas;
        $dependenciaDataSelectRol = 'editar-ops-rol';
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
    </div>

    <div class="campo">
        <label for="editar-ops-rol">Rol al que se enviará *</label>
        <select id="editar-ops-rol" name="rol_destinatario_id" required>
            <option value="">Selecciona un rol</option>
            <?php foreach ($roles as $rolOpcion): ?>
            <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-ops-rubro_buscador">Rubro *</label>
        <div class="selector-buscable" id="editar-ops-selector-rubro">
            <input type="text" id="editar-ops-rubro_buscador" class="selector-buscable-input" placeholder="Buscar rubro..." autocomplete="off">
            <input type="hidden" name="rubro_id" id="editar-ops-rubro_id">
            <div class="selector-buscable-lista" id="editar-ops-rubro_lista">
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
        <label for="editar-ops-perfil">Perfil *</label>
        <select id="editar-ops-perfil" name="perfil" required>
            <option value="">Selecciona un perfil</option>
            <?php foreach ($perfilesOps as $perfilClave => $perfilNombre): ?>
            <option value="<?= htmlspecialchars($perfilClave) ?>"><?= htmlspecialchars($perfilNombre) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-ops-valor">Valor *</label>
        <input type="number" id="editar-ops-valor" name="valor" min="0" step="0.01" value="0" required>
    </div>

    <div class="campo">
        <label for="editar-ops-cantidad">Cantidad *</label>
        <input type="number" id="editar-ops-cantidad" name="cantidad" min="1" step="1" value="1" required>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-ops-observaciones">Observaciones</label>
        <input type="text" id="editar-ops-observaciones" name="observaciones" placeholder="Opcional">
    </div>
</form>

<button
    type="button"
    id="boton-editar-ops-desde-edicion"
    class="fila-menu-editar-ops boton-editar-fila-generico"
    hidden
    data-ops="<?= htmlspecialchars(json_encode($opsParaEditar + ['volver' => $volverEdicion], JSON_UNESCAPED_UNICODE)) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-ops-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
