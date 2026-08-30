<?php
/**
 * Selector buscable para "Línea de inversión": al elegir una aparece un ícono "i" con su
 * descripción en tooltip (mismo mecanismo genérico que selector-contrato-comun.php), y además
 * filtra en cascada las opciones del selector de "Sublínea de inversión" asociado, reutilizando
 * el mecanismo genérico [data-select-dependencia] -> .aplicarFiltroTipo() ya cableado en app.js.
 *
 * Variables esperadas antes de incluir este parcial:
 * - $idPrefijoLineaInversion (string): prefijo de ids, ej. '' para crear.
 * - $lineasInversion (array): filas de LineaInversion::obtenerActivas() (id, codigo, nombre, descripcion).
 * - $idCampoSublineaInversion (string): id del campo oculto de sublínea (para el filtro en cascada).
 */
?>
<div class="campo">
    <label for="<?= $idPrefijoLineaInversion ?>linea_inversion_buscador">
        Línea de inversión *
        <span class="icono-info oculto" id="<?= $idPrefijoLineaInversion ?>linea-inversion-info" tabindex="0" title="">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </span>
    </label>
    <div class="selector-buscable" id="<?= $idPrefijoLineaInversion ?>selector-linea-inversion">
        <input type="text" id="<?= $idPrefijoLineaInversion ?>linea_inversion_buscador" class="selector-buscable-input" placeholder="Buscar línea de inversión..." autocomplete="off">
        <input
            type="hidden"
            name="linea_inversion"
            id="<?= $idPrefijoLineaInversion ?>linea_inversion"
            data-select-dependencia="<?= $idCampoSublineaInversion ?>"
        >
        <div class="selector-buscable-lista" id="<?= $idPrefijoLineaInversion ?>linea_inversion_lista">
            <?php foreach ($lineasInversion as $linea): ?>
            <div
                class="selector-buscable-opcion"
                data-id="<?= htmlspecialchars($linea['codigo']) ?>"
                data-texto="<?= htmlspecialchars($linea['codigo'] . ' - ' . $linea['nombre']) ?>"
                data-descripcion="<?= htmlspecialchars($linea['descripcion']) ?>"
            >
                <?= htmlspecialchars($linea['codigo'] . ' - ' . $linea['nombre']) ?>
            </div>
            <?php endforeach; ?>
            <div class="selector-buscable-vacio">Sin resultados.</div>
        </div>
    </div>
</div>
