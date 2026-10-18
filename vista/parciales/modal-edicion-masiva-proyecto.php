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
$idPrefijoProyecto = 'masivo-';
$proyectosPdi = (new Proyecto())->obtenerTodos();
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
        <div class="modal-pie" style="gap: 0.5rem;">
            <button type="button" class="boton-secundario" id="tdt-cancelar-edicion-masiva">Cancelar</button>
            <button type="button" class="boton-agregar" id="tdt-aplicar-edicion-masiva">Aplicar a las filas seleccionadas</button>
        </div>
    </div>
</div>
