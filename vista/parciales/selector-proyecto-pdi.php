<?php
/**
 * Selector buscable único para Línea/Motor/Proyecto (PDI). Reemplaza los 3 <select> en cascada:
 * el usuario solo elige el Proyecto (los que empiezan por "P"); la Línea y el Motor son solo para
 * categorizar/mostrar contexto y se derivan en el servidor a partir del proyecto elegido.
 *
 * Variables esperadas antes de incluir este parcial:
 * - $idPrefijoProyecto (string): prefijo de ids, ej. '' para crear, 'editar-' o 'editar-egreso-' para editar.
 * - $proyectosPdi (array): filas de Proyecto::obtenerTodos() (id, codigo, nombre, motor_id, motor_codigo,
 *   motor_nombre, linea_id, linea_codigo, linea_nombre).
 */
?>
<div class="campo campo-ancho">
    <label for="<?= $idPrefijoProyecto ?>proyecto_buscador">Proyecto PDI (línea / motor / proyecto) *</label>
    <div class="selector-buscable" id="<?= $idPrefijoProyecto ?>selector-proyecto">
        <input type="text" id="<?= $idPrefijoProyecto ?>proyecto_buscador" class="selector-buscable-input" placeholder="Buscar línea, motor o proyecto..." autocomplete="off">
        <input type="hidden" name="proyecto_id" id="<?= $idPrefijoProyecto ?>proyecto_id">
        <div class="selector-buscable-lista" id="<?= $idPrefijoProyecto ?>proyecto_lista">
            <?php foreach ($proyectosPdi as $proyectoPdi): ?>
            <?php
            $mostrar = $proyectoPdi['linea_codigo'] . ' · ' . $proyectoPdi['motor_codigo'] . ' · ' . $proyectoPdi['codigo'] . ' - ' . $proyectoPdi['nombre'];
            $buscar = $proyectoPdi['linea_codigo'] . ' ' . $proyectoPdi['linea_nombre'] . ' '
                . $proyectoPdi['motor_codigo'] . ' ' . $proyectoPdi['motor_nombre'] . ' '
                . $proyectoPdi['codigo'] . ' ' . $proyectoPdi['nombre'];
            ?>
            <div class="selector-buscable-opcion" data-id="<?= (int) $proyectoPdi['id'] ?>" data-mostrar="<?= htmlspecialchars($mostrar) ?>" data-texto="<?= htmlspecialchars($buscar) ?>">
                <?= htmlspecialchars($mostrar) ?>
            </div>
            <?php endforeach; ?>
            <div class="selector-buscable-vacio">Sin resultados.</div>
        </div>
    </div>
</div>
