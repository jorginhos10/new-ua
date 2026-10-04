<?php
/**
 * Desplegable opcional "Categoría de gasto" (36 subcategorías agrupadas por macro). Variables
 * esperadas: $catalogoCategoriasGasto, $prefijoCategoria ('' en alta, 'editar-' en edición),
 * $categoriaSeleccionada, $origenCategoria, $confianzaCategoria (opcionales).
 */
$prefijoCategoria = $prefijoCategoria ?? '';
$categoriaSeleccionada = $categoriaSeleccionada ?? '';
$origenCategoria = $origenCategoria ?? '';
$confianzaCategoria = $confianzaCategoria ?? '';
$catalogoCategoriasGasto = $catalogoCategoriasGasto ?? [];
?>
<div class="campo">
    <label for="<?= $prefijoCategoria ?>categoria_gasto_id">Categoría de gasto <span class="texto-atenuado">(opcional)</span></label>
    <select id="<?= $prefijoCategoria ?>categoria_gasto_id" name="categoria_gasto_id" data-prefijo="<?= $prefijoCategoria ?>">
        <option value="">Sin categoría</option>
        <?php $macroActual = null; ?>
        <?php foreach ($catalogoCategoriasGasto as $categoriaOpcion): ?>
            <?php if ($categoriaOpcion['macro'] !== $macroActual): ?>
                <?php if ($macroActual !== null): ?></optgroup><?php endif; ?>
                <optgroup label="<?= htmlspecialchars($categoriaOpcion['macro']) ?>">
                <?php $macroActual = $categoriaOpcion['macro']; ?>
            <?php endif; ?>
            <option value="<?= htmlspecialchars($categoriaOpcion['id']) ?>" <?= $categoriaOpcion['id'] === $categoriaSeleccionada ? 'selected' : '' ?>><?= htmlspecialchars($categoriaOpcion['id'] . ' · ' . $categoriaOpcion['subcategoria']) ?></option>
        <?php endforeach; ?>
        <?php if ($macroActual !== null): ?></optgroup><?php endif; ?>
    </select>
    <input type="hidden" name="categoria_origen" id="<?= $prefijoCategoria ?>categoria_origen" value="<?= htmlspecialchars((string) $origenCategoria) ?>">
    <input type="hidden" name="categoria_confianza" id="<?= $prefijoCategoria ?>categoria_confianza" value="<?= htmlspecialchars((string) $confianzaCategoria) ?>">
    <p class="texto-atenuado" id="<?= $prefijoCategoria ?>categoria_sugerencia" style="margin: 0.3rem 0 0;"></p>
</div>
