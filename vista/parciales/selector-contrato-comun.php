<?php
/**
 * Selector buscable para el campo "Contratos comunes" (antes "Objeto/Proyecto (PAA)", texto libre):
 * ahora es un input predecible, limitado al catálogo de Configuraciones > Listas > Contratos comunes.
 * Al elegir uno aparece un ícono "i" junto a la etiqueta que muestra su descripción en un tooltip.
 *
 * Variables esperadas antes de incluir este parcial:
 * - $idPrefijoContrato (string): prefijo de ids, ej. '' para crear, 'editar-' o 'editar-egreso-' para editar.
 * - $contratosComunes (array): filas de ContratoComun::obtenerActivos() (id, codigo, descripcion).
 */
?>
<div class="campo campo-ancho">
    <label for="<?= $idPrefijoContrato ?>objeto_proyecto_paa_buscador">
        Contratos comunes
        <span class="icono-info oculto" id="<?= $idPrefijoContrato ?>contrato-comun-info" tabindex="0" title="">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </span>
    </label>
    <div class="selector-buscable" id="<?= $idPrefijoContrato ?>selector-contrato-comun">
        <input type="text" id="<?= $idPrefijoContrato ?>objeto_proyecto_paa_buscador" class="selector-buscable-input" placeholder="Buscar contrato común..." autocomplete="off">
        <input type="hidden" name="objeto_proyecto_paa" id="<?= $idPrefijoContrato ?>objeto_proyecto_paa">
        <div class="selector-buscable-lista" id="<?= $idPrefijoContrato ?>contrato_comun_lista">
            <?php foreach ($contratosComunes as $contrato): ?>
            <div class="selector-buscable-opcion" data-id="<?= htmlspecialchars($contrato['codigo']) ?>" data-texto="<?= htmlspecialchars($contrato['codigo']) ?>" data-descripcion="<?= htmlspecialchars($contrato['descripcion']) ?>">
                <?= htmlspecialchars($contrato['codigo']) ?>
            </div>
            <?php endforeach; ?>
            <div class="selector-buscable-vacio">Sin resultados.</div>
        </div>
    </div>
</div>
