<?php
/**
 * Modal de edición masiva del Proyecto PDI de la tabla real (ver publico/js/tabla-real/tabla.js y
 * modelo/EdicionMasivaProyecto.php): se abre al pulsar Editar con VARIAS filas seleccionadas y
 * sobrescribe solo el Proyecto PDI de esas filas. Mismo selector de Proyecto PDI que los
 * formularios de gasto (parciales/selector-proyecto-pdi.php, con prefijo "masivo-"). Lo incluyen
 * vista/analisis/parciales/tabla-modulo.php y vista/peticiones/tipo-detalle.php solo cuando el
 * origen admite Proyecto PDI (EdicionMasivaProyecto::admite()).
 */
require_once __DIR__ . '/../../modelo/Proyecto.php';
require_once __DIR__ . '/../../modelo/EdicionMasivaProyecto.php';
$idPrefijoProyecto = 'masivo-';
$proyectosPdi = (new Proyecto())->obtenerTodos();

// Respaldos previos de esta edición (solo superadmin): cada edición masiva guarda el estado anterior
// de sus filas y aquí se puede revertir (ver EdicionMasivaProyecto::aplicar()/restaurar()).
$respaldosEdicionMasiva = [];
if (!empty($_SESSION['usuario_super_admin'])) {
    try {
        $respaldosEdicionMasiva = (new EdicionMasivaProyecto())->obtenerRespaldos((string) ($origenActivo ?? $origen ?? ''));
    } catch (Throwable $excepcion) {
        // Tabla aún no creada (instalar.php): el modal sigue funcionando sin la lista.
    }
}
?>
<div id="tdt-modal-edicion-masiva" class="modal-fondo">
    <div class="modal-caja" style="max-width: 560px;">
        <div class="modal-cabecera">
            <h2>Edición masiva</h2>
            <button type="button" class="modal-cerrar" id="tdt-cerrar-edicion-masiva" aria-label="Cerrar">&times;</button>
        </div>
        <p class="texto-atenuado" id="tdt-edicion-masiva-resumen">Se actualizará el Proyecto PDI de las filas seleccionadas.</p>
        <div class="form-necesidad">
            <?php require __DIR__ . '/selector-proyecto-pdi.php'; ?>
        </div>
        <p class="texto-atenuado" style="font-size: 0.82rem;">Solo cambia el Proyecto PDI (y su línea estratégica y motor de desarrollo); el resto de cada fila queda igual.</p>
        <?php if (!empty($respaldosEdicionMasiva)): ?>
        <div style="border-top: 1px solid var(--color-borde); padding-top: 0.5rem; margin-top: 0.5rem;">
            <strong style="font-size: 0.85rem;" title="Antes de cada edición masiva se guarda el estado anterior de sus filas (se conservan las <?= EdicionMasivaProyecto::MAX_RESPALDOS ?> últimas). Restaurar solo revierte las filas que todavía tienen el Proyecto PDI que puso esa edición.">Ediciones recientes (restaurar)</strong>
            <?php foreach ($respaldosEdicionMasiva as $respaldoEdicion): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; font-size: 0.82rem; margin-top: 0.35rem;">
                <span>
                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($respaldoEdicion['creado_en']))) ?> · <?= htmlspecialchars((string) ($respaldoEdicion['creado_por_nombre'] ?? '')) ?>
                    → <?= htmlspecialchars($respaldoEdicion['proyecto_nuevo_texto']) ?> · <?= (int) $respaldoEdicion['total_filas'] ?> <?= (int) $respaldoEdicion['total_filas'] === 1 ? 'fila' : 'filas' ?>
                </span>
                <?php if ($respaldoEdicion['restaurado_en'] !== null): ?>
                <span class="texto-atenuado">restaurada</span>
                <?php else: ?>
                <button type="button" class="boton-secundario tdt-restaurar-edicion-masiva" data-respaldo-id="<?= (int) $respaldoEdicion['id'] ?>">Restaurar</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="modal-pie" style="gap: 0.5rem;">
            <button type="button" class="boton-secundario" id="tdt-cancelar-edicion-masiva">Cancelar</button>
            <button type="button" class="boton-agregar" id="tdt-aplicar-edicion-masiva">Aplicar a las filas seleccionadas</button>
        </div>
    </div>
</div>
<?php if (!empty($respaldosEdicionMasiva)): ?>
<script>
document.querySelectorAll('.tdt-restaurar-edicion-masiva').forEach(function (boton) {
    boton.addEventListener('click', function () {
        var formAccion = document.getElementById('tdt-form-accion');
        var campoAccion = document.getElementById('tdt-form-accion-valor');
        if (!formAccion || !campoAccion) { return; }
        if (!window.confirm('¿Restaurar el Proyecto PDI anterior de las filas de esa edición masiva?')) { return; }

        var campoRespaldo = formAccion.querySelector('input[name="respaldo_id"]');
        if (!campoRespaldo) {
            campoRespaldo = document.createElement('input');
            campoRespaldo.type = 'hidden';
            campoRespaldo.name = 'respaldo_id';
            formAccion.appendChild(campoRespaldo);
        }
        campoRespaldo.value = boton.dataset.respaldoId;
        campoAccion.value = 'restaurar_edicion_masiva';
        boton.disabled = true;
        formAccion.submit();
    });
});
</script>
<?php endif; ?>
