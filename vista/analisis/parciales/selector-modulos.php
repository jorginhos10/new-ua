<?php
/**
 * Selector de módulo de la pestaña "Análisis de distribución": tarjeta propia a la izquierda de
 * la tabla (ver vista/analisis/index.php), con una mini-tarjeta por cada origen con techo (Gasto,
 * Extensión, Postgrado, Unisalud, Monitores, ARL — Sin Excedentes excluido a propósito, ver plan
 * el-techo-no-deberia-kind-candle: ese módulo está descontinuado). Clic en una mini-tarjeta
 * recarga la pestaña con ese origen activo.
 *
 * Variables esperadas: $origenActivo, $totalesPorModulo, $vista, $snapshotIdActual,
 * $dependenciaFiltroActual.
 */

$etiquetasModuloSelector = [
    'gasto_principal' => 'Gasto',
    'gasto_extension' => 'Extensión',
    'gasto_postgrado' => 'Postgrado',
    'gasto_unisalud' => 'Unisalud',
    'monitores' => 'Monitores',
    'arl' => 'ARL',
];
?>
<style>
    .analisis-selector {
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        height: 100%;
        box-sizing: border-box;
    }

    .analisis-selector-titulo {
        margin: 0 0 0.15rem;
        font-size: 0.8rem;
        color: var(--color-texto-secundario);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .analisis-selector-item {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        border: 1px solid var(--color-borde);
        text-decoration: none;
        transition: border-color var(--transicion), background var(--transicion);
    }

    .analisis-selector-item:hover {
        border-color: var(--color-borde-hover);
    }

    .analisis-selector-item.activo {
        border-color: var(--color-primario);
        background: var(--color-primario-suave);
    }

    .analisis-selector-nombre {
        font-size: 0.82rem;
        color: var(--color-texto);
        font-weight: 600;
    }

    .analisis-selector-valor {
        font-size: 0.78rem;
        color: var(--color-texto-secundario);
        font-variant-numeric: tabular-nums;
    }
</style>

<div class="tarjeta analisis-selector">
    <h3 class="analisis-selector-titulo">Módulo</h3>
    <?php foreach ($etiquetasModuloSelector as $origenOpcion => $etiqueta): ?>
    <?php
    $extra = ['origen' => $origenOpcion];
    if ($vista === 'repositorio' && !empty($snapshotIdActual)) {
        $extra['snapshot_id'] = $snapshotIdActual;
    } elseif ($vista === 'usuario' && !empty($dependenciaFiltroActual)) {
        $extra['dependencia'] = $dependenciaFiltroActual;
    }
    ?>
    <a href="<?= htmlspecialchars(analisisUrl('analisis', $vista, $extra)) ?>" class="analisis-selector-item <?= $origenActivo === $origenOpcion ? 'activo' : '' ?>">
        <span class="analisis-selector-nombre"><?= htmlspecialchars($etiqueta) ?></span>
        <span class="analisis-selector-valor">$<?= number_format($totalesPorModulo[$origenOpcion] ?? 0.0, 2, ',', '.') ?></span>
    </a>
    <?php endforeach; ?>
</div>
