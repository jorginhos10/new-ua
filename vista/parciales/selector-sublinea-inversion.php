<?php
/**
 * Selector buscable para "Sublínea de inversión": condicionado por la línea de inversión elegida
 * en el campo hermano (selector-linea-inversion.php la filtra automáticamente por su código, vía
 * el mecanismo genérico [data-select-dependencia] -> .aplicarFiltroTipo()), y con tooltip de
 * descripción igual que selector-contrato-comun.php.
 *
 * Variables esperadas antes de incluir este parcial:
 * - $idPrefijoSublineaInversion (string): prefijo de ids, ej. '' para crear.
 * - $sublineasInversion (array): filas de SublineaInversion::obtenerActivas()
 *   (id, codigo, nombre, descripcion, linea_inversion_id, linea_codigo).
 */
?>
<div class="campo">
    <label for="<?= $idPrefijoSublineaInversion ?>sublinea_inversion_buscador">
        Sublínea de inversión *
        <span class="icono-info oculto" id="<?= $idPrefijoSublineaInversion ?>sublinea-inversion-info" tabindex="0" title="">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </span>
    </label>
    <div class="selector-buscable" id="<?= $idPrefijoSublineaInversion ?>selector-sublinea-inversion">
        <input type="text" id="<?= $idPrefijoSublineaInversion ?>sublinea_inversion_buscador" class="selector-buscable-input" placeholder="Elige primero una línea de inversión..." autocomplete="off">
        <input type="hidden" name="sublinea_inversion" id="<?= $idPrefijoSublineaInversion ?>sublinea_inversion">
        <div class="selector-buscable-lista" id="<?= $idPrefijoSublineaInversion ?>sublinea_inversion_lista">
            <?php foreach ($sublineasInversion as $sublinea): ?>
            <div
                class="selector-buscable-opcion"
                data-id="<?= htmlspecialchars($sublinea['codigo']) ?>"
                data-texto="<?= htmlspecialchars($sublinea['codigo'] . ' - ' . $sublinea['nombre']) ?>"
                data-descripcion="<?= htmlspecialchars($sublinea['descripcion']) ?>"
                data-tipo="<?= htmlspecialchars($sublinea['linea_codigo']) ?>"
            >
                <?= htmlspecialchars($sublinea['codigo'] . ' - ' . $sublinea['nombre']) ?>
            </div>
            <?php endforeach; ?>
            <div class="selector-buscable-vacio">Sin resultados. Elige primero una línea de inversión.</div>
        </div>
    </div>
</div>
