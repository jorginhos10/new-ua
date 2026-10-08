<?php
/**
 * Pestaña "Presentación" de ?ruta=analisis: la diapositiva (.ppsx) de OneDrive incrustada con el
 * visor de Office. Solo el superadmin ve el formulario para cambiar el enlace. Variables esperadas
 * (ver AnalisisControlador::renderizarPresentacion()): $presentacion (url, actualizado_en,
 * actualizado_por_nombre, o null), $urlPresentacionIncrustada, $puedeEditarPresentacion, $vista.
 */

$presentacion = $presentacion ?? null;
$urlPresentacionIncrustada = $urlPresentacionIncrustada ?? null;
$puedeEditarPresentacion = $puedeEditarPresentacion ?? false;
?>

<style>
    .presentacion-analisis {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        padding: 0 0.5rem 0.75rem;
    }

    .presentacion-analisis-barra {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: var(--color-texto-tenue);
    }

    .presentacion-analisis-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        flex: 1;
        min-width: 280px;
    }

    .presentacion-analisis-form input[type="text"] {
        flex: 1;
        min-width: 220px;
        padding: 0.45rem 0.6rem;
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        background: var(--color-superficie);
        color: var(--color-texto);
        font-size: 0.85rem;
    }

    .presentacion-analisis-boton {
        padding: 0.45rem 0.9rem;
        border: none;
        border-radius: 6px;
        background: var(--color-primario);
        color: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
    }

    .presentacion-analisis-enlace {
        color: var(--color-primario);
        text-decoration: none;
        white-space: nowrap;
    }

    .presentacion-analisis-enlace:hover {
        text-decoration: underline;
    }

    .presentacion-analisis-marco {
        flex: 1;
        min-height: 0;
        border: 1px solid var(--color-borde);
        border-radius: 8px;
        overflow: hidden;
        background: var(--color-superficie);
    }

    .presentacion-analisis-marco iframe {
        display: block;
        width: 100%;
        height: 100%;
        border: 0;
    }

    .presentacion-analisis-vacio {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border: 1px dashed var(--color-borde);
        border-radius: 8px;
        color: var(--color-texto-tenue);
        font-size: 0.9rem;
        text-align: center;
        padding: 2rem 1rem;
    }
</style>

<div class="presentacion-analisis">
    <?php if ($puedeEditarPresentacion || $presentacion !== null): ?>
    <div class="presentacion-analisis-barra">
        <?php if ($puedeEditarPresentacion): ?>
        <form class="presentacion-analisis-form" method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>">
            <input type="hidden" name="accion" value="guardar_presentacion">
            <input type="text" name="url_presentacion" value="<?= htmlspecialchars($presentacion['url'] ?? '') ?>" placeholder="Enlace de OneDrive de la presentación (.ppsx) o código &quot;Insertar&quot;" required>
            <button type="submit" class="presentacion-analisis-boton"><?= $presentacion !== null ? 'Cambiar' : 'Guardar' ?></button>
        </form>
        <?php endif; ?>

        <?php if ($presentacion !== null): ?>
        <span>
            Actualizada <?= htmlspecialchars(date('d/m/Y H:i', strtotime($presentacion['actualizado_en']))) ?><?= !empty($presentacion['actualizado_por_nombre']) ? ' por ' . htmlspecialchars($presentacion['actualizado_por_nombre']) : '' ?>
            · <a class="presentacion-analisis-enlace" href="<?= htmlspecialchars($presentacion['url']) ?>" target="_blank" rel="noopener">Abrir en OneDrive</a>
        </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($urlPresentacionIncrustada !== null): ?>
    <div class="presentacion-analisis-marco">
        <iframe src="<?= htmlspecialchars($urlPresentacionIncrustada) ?>" title="Presentación" allowfullscreen referrerpolicy="no-referrer"></iframe>
    </div>
    <?php else: ?>
    <div class="presentacion-analisis-vacio">
        <strong>Todavía no hay una presentación cargada.</strong>
        <?php if ($puedeEditarPresentacion): ?>
        <span>En OneDrive: clic derecho sobre el .ppsx → Compartir → "Cualquier persona con el vínculo puede ver" → Copiar vínculo, y pégalo arriba.</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
