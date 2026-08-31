<?php
/**
 * Estado "Edición" (Épica 2, 2.3) para Perfil de Proyectos.
 * Reutiliza los mismos campos e ids (`editar-proyecto-*`) que ya poblaba el JS del
 * antiguo modal `#modal-editar-proyecto` — solo cambia el contenedor (ya no es un modal)
 * y el botón oculto + auto-click que dispara ese mismo poblado al cargar la página.
 *
 * Variables esperadas: $proyectoParaEditar (array), $volverEdicion, $error,
 * $aniosVigencia, $estamentos, $sedes, $dependenciasSugeridas, $dependenciasPrograma,
 * $proyectos, $avaladores.
 */
?>
<h2 class="titulo-formulario-edicion">Editar proyecto</h2>

<?php if (!empty($error)): ?>
<p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form
    method="POST"
    action="index.php?ruta=perfil-proyectos&editar_id=<?= (int) $proyectoParaEditar['id'] ?>"
    id="form-editar-proyecto"
    class="form-necesidad"
>
    <input type="hidden" name="accion" value="actualizar_proyecto">
    <input type="hidden" name="id" id="editar-proyecto-id" value="">
    <input type="hidden" name="volver" id="editar-proyecto-volver" value="">

    <div class="campo">
        <label for="editar-proyecto-vigencia">Vigencia *</label>
        <select id="editar-proyecto-vigencia" name="vigencia" required>
            <option value="">Selecciona la vigencia</option>
            <?php foreach ($aniosVigencia as $anioVigencia): ?>
            <option value="<?= (int) $anioVigencia ?>"><?= (int) $anioVigencia ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-proyecto-nombre_necesidad">Nombre de la necesidad *</label>
        <input type="text" id="editar-proyecto-nombre_necesidad" name="nombre_necesidad" placeholder="Diligenciar" required>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-proyecto-descripcion">Descripción</label>
        <textarea id="editar-proyecto-descripcion" name="descripcion" rows="3" placeholder="Diligenciar"></textarea>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-proyecto-justificacion">Justificación</label>
        <textarea id="editar-proyecto-justificacion" name="justificacion" rows="3" placeholder="Diligenciar"></textarea>
    </div>

    <div class="campo">
        <label for="editar-proyecto-estamento_solicitante_id">Estamento solicitante *</label>
        <select id="editar-proyecto-estamento_solicitante_id" name="estamento_solicitante_id" required>
            <option value="">Selecciona un estamento</option>
            <?php foreach ($estamentos as $estamentoOpcion): ?>
            <option value="<?= (int) $estamentoOpcion['id'] ?>"><?= htmlspecialchars($estamentoOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-proyecto-beneficiarios_cantidad">Beneficiarios (cantidad)</label>
        <input type="number" id="editar-proyecto-beneficiarios_cantidad" name="beneficiarios_cantidad" min="0" step="1" placeholder="0">
    </div>

    <div class="campo campo-ancho">
        <label>Beneficiarios por estamento</label>
        <div class="grupo-tags" id="editar-proyecto-beneficiarios-estamentos-grupo">
            <?php foreach ($estamentos as $estamentoTag): ?>
            <input
                type="checkbox"
                class="tag-chip"
                name="beneficiarios_estamentos[]"
                value="<?= (int) $estamentoTag['id'] ?>"
                data-label="<?= htmlspecialchars($estamentoTag['nombre']) ?>"
                title="<?= htmlspecialchars($estamentoTag['nombre']) ?>"
            >
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    $idPrefijoLineaInversion = 'editar-proyecto-';
    $idCampoSublineaInversion = 'editar-proyecto-sublinea_inversion';
    require __DIR__ . '/../parciales/selector-linea-inversion.php';
    ?>

    <?php
    $idPrefijoSublineaInversion = 'editar-proyecto-';
    require __DIR__ . '/../parciales/selector-sublinea-inversion.php';
    ?>

    <div class="campo">
        <label for="editar-proyecto-detalle_inversion">Inversión (detalle)</label>
        <input type="text" id="editar-proyecto-detalle_inversion" name="detalle_inversion" placeholder="Elegir de acuerdo al alcance en la pestaña definiciones">
    </div>

    <div class="campo">
        <label for="editar-proyecto-sede_id">Sede *</label>
        <select id="editar-proyecto-sede_id" name="sede_id" required>
            <option value="">Selecciona una sede</option>
            <?php foreach ($sedes as $sedeOpcion): ?>
            <option value="<?= (int) $sedeOpcion['id'] ?>"><?= htmlspecialchars($sedeOpcion['codigo'] . ' - ' . $sedeOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="editar-proyecto-dependencia_buscador">Dependencia *</label>
        <?php
        $idPrefijoDependencia = 'editar-proyecto-';
        $nombreCampoDependencia = 'dependencia';
        $idBaseDependenciaOverride = 'editar-proyecto-dependencia';
        $dependenciasOpciones = $dependenciasSugeridas;
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
    </div>

    <div class="campo">
        <label for="editar-proyecto-programa-academico-dependencia_buscador">Programa académico</label>
        <?php
        $idPrefijoDependencia = 'editar-proyecto-programa-academico-';
        $nombreCampoDependencia = 'programa_academico';
        $idBaseDependenciaOverride = 'editar-proyecto-programa-academico-dependencia';
        $dependenciasOpciones = $dependenciasPrograma;
        require __DIR__ . '/../parciales/selector-dependencia.php';
        ?>
    </div>

    <?php $idPrefijoProyecto = 'editar-proyecto-'; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

    <div class="campo campo-ancho">
        <label for="editar-proyecto-articulacion_plan">Articulación con Plan/planes</label>
        <input type="text" id="editar-proyecto-articulacion_plan" name="articulacion_plan" placeholder="Diligenciar">
    </div>

    <div class="campo campo-ancho">
        <label for="editar-proyecto-espacio_intervenir">Espacio a intervenir</label>
        <input type="text" id="editar-proyecto-espacio_intervenir" name="espacio_intervenir" placeholder="Salón, laboratorio, taller u oficina de acuerdo a nomenclatura de la sede">
    </div>

    <div class="campo campo-ancho">
        <label for="editar-proyecto-requisitos_normativos">Requisitos normativos</label>
        <input type="text" id="editar-proyecto-requisitos_normativos" name="requisitos_normativos" placeholder="Diligenciar">
    </div>

    <div class="campo">
        <label for="editar-proyecto-valor">Valor *</label>
        <input type="number" id="editar-proyecto-valor" name="valor" min="0" step="0.01" placeholder="0.00" required>
    </div>

    <div class="campo">
        <label for="editar-proyecto-fuente_financiacion">Fuente de financiación *</label>
        <input type="text" id="editar-proyecto-fuente_financiacion" name="fuente_financiacion" placeholder="Diligenciar" required>
    </div>

    <div class="campo">
        <label for="editar-proyecto-responsable_usuario_id">Responsable *</label>
        <select id="editar-proyecto-responsable_usuario_id" name="responsable_usuario_id" required>
            <option value="">Selecciona un avalador</option>
            <?php foreach ($avaladores as $avaladorOpcion): ?>
            <option value="<?= (int) $avaladorOpcion['id'] ?>"><?= htmlspecialchars($avaladorOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo campo-ancho">
        <label for="editar-proyecto-observaciones">Observaciones</label>
        <textarea id="editar-proyecto-observaciones" name="observaciones" rows="3" placeholder="Diligenciar"></textarea>
    </div>
</form>

<button
    type="button"
    id="boton-editar-proyecto-desde-edicion"
    class="boton-editar-proyecto"
    hidden
    data-proyecto="<?= htmlspecialchars(json_encode($proyectoParaEditar + ['volver' => $volverEdicion], JSON_UNESCAPED_UNICODE)) ?>"
></button>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var boton = document.getElementById('boton-editar-proyecto-desde-edicion');
            if (boton) {
                boton.click();
            }
        }, 0);
    });
</script>
