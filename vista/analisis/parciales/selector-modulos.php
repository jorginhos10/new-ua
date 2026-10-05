<?php
/**
 * Selector de módulo de la pestaña "Análisis de distribución": tarjeta propia a la izquierda de
 * la tabla (ver vista/analisis/index.php), con una mini-tarjeta por cada origen con techo (Gasto,
 * Extensión, Postgrado, Unisalud, Monitores, ARL — Sin Excedentes excluido a propósito, ver plan
 * el-techo-no-deberia-kind-candle: ese módulo está descontinuado). Clic en una mini-tarjeta
 * recarga la pestaña con ese origen activo.
 *
 * Cada tarjeta muestra el total (sin decimales; el valor exacto va en el title) y una barra con su
 * participación sobre el total de egresos de todos los módulos. Extensión y Postgrado
 * ($paresGastoIngreso) muestran Egresos e Ingresos como dos filas clicables — cada una lleva
 * directo a esa pestaña de la tabla (ver tabla-modulo.php) — más la diferencia entre ambos.
 *
 * Variables esperadas: $origenActivo, $totalesPorModulo, $paresGastoIngreso, $vista,
 * $snapshotIdActual, $dependenciaFiltroActual.
 */

$paresGastoIngreso = $paresGastoIngreso ?? [];

$etiquetasModuloSelector = [
    'gasto_principal' => 'Gasto',
    'gasto_extension' => 'Extensión',
    'gasto_postgrado' => 'Postgrado',
    'gasto_unisalud' => 'Unisalud',
    'monitores' => 'Monitores',
    'arl' => 'ARL',
];

$extraSelector = [];
if ($vista === 'repositorio' && !empty($snapshotIdActual)) {
    $extraSelector['snapshot_id'] = $snapshotIdActual;
} elseif ($vista === 'usuario' && !empty($dependenciaFiltroActual)) {
    $extraSelector['dependencia'] = $dependenciaFiltroActual;
}

// Base de la barra de participación: solo egresos (los ingresos no se reparten entre módulos).
$totalEgresosSelector = 0.0;
foreach (array_keys($etiquetasModuloSelector) as $origenSuma) {
    $totalEgresosSelector += (float) ($totalesPorModulo[$origenSuma] ?? 0.0);
}

$monedaCorta = static fn (float $valor): string => '$' . number_format($valor, 0, ',', '.');
$monedaExacta = static fn (float $valor): string => '$' . number_format($valor, 2, ',', '.');
$urlOrigen = static fn (string $origen): string => analisisUrl('analisis', $vista, ['origen' => $origen] + $extraSelector);
?>
<style>
    .analisis-selector {
        padding: 0.6rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        height: 100%;
        box-sizing: border-box;
        overflow-y: auto;
    }

    .analisis-selector-titulo {
        margin: 0 0 0.15rem;
        font-size: 0.75rem;
        color: var(--color-texto-tenue);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .analisis-selector-item {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.65rem 0.75rem 0.7rem;
        border-radius: 10px;
        border: 1px solid var(--color-borde);
        background: var(--color-superficie);
        color: inherit;
        text-decoration: none;
        transition: border-color var(--transicion), background var(--transicion), box-shadow var(--transicion);
    }

    a.analisis-selector-item:hover,
    .analisis-selector-item:has(a:hover) {
        border-color: var(--color-borde-hover);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        text-decoration: none;
    }

    .analisis-selector-item.activo {
        border-color: var(--color-primario);
        background: var(--color-primario-suave);
        box-shadow: inset 3px 0 0 var(--color-primario);
    }

    .analisis-selector a:focus-visible {
        outline: 2px solid var(--color-primario);
        outline-offset: 2px;
    }

    .analisis-selector-cabecera {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        color: inherit;
        text-decoration: none;
    }

    .analisis-selector-cabecera:hover {
        text-decoration: none;
    }

    .analisis-selector-nombre {
        font-size: 0.85rem;
        color: var(--color-texto);
        font-weight: 600;
    }

    .analisis-selector-porcentaje {
        font-size: 0.72rem;
        color: var(--color-texto-tenue);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .analisis-selector-valor {
        font-size: 1rem;
        font-weight: 600;
        color: var(--color-texto);
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.01em;
    }

    .analisis-selector-barra {
        height: 4px;
        border-radius: 999px;
        background: var(--color-gris-fondo);
        overflow: hidden;
    }

    .analisis-selector-barra span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: var(--color-primario);
    }

    .analisis-selector-item.vacio .analisis-selector-valor,
    .analisis-selector-item.vacio .analisis-selector-nombre {
        color: var(--color-texto-tenue);
    }

    .analisis-selector-vacio {
        font-size: 0.72rem;
        color: var(--color-texto-tenue);
    }

    /* Extensión / Postgrado: una fila clicable por lado. */
    .analisis-selector-lados {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin: 0 -0.35rem;
    }

    .analisis-selector-lado {
        display: grid;
        grid-template-columns: 8px auto 1fr;
        align-items: center;
        column-gap: 0.45rem;
        min-height: 32px;
        padding: 0 0.35rem;
        border-radius: 6px;
        color: var(--color-texto-secundario);
        text-decoration: none;
        font-variant-numeric: tabular-nums;
        transition: background var(--transicion), color var(--transicion);
    }

    .analisis-selector-lado:hover {
        background: rgba(0, 0, 0, 0.05);
        color: var(--color-texto);
        text-decoration: none;
    }

    .analisis-selector-lado.activo {
        background: var(--color-superficie);
        color: var(--color-texto);
        font-weight: 600;
        box-shadow: 0 0 0 1px var(--color-primario);
    }

    .analisis-selector-lado-punto {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .analisis-selector-lado-punto.egresos {
        background: var(--color-primario);
    }

    .analisis-selector-lado-punto.ingresos {
        background: var(--color-excedentes);
    }

    .analisis-selector-lado-etiqueta {
        font-size: 0.78rem;
    }

    .analisis-selector-lado-valor {
        font-size: 0.85rem;
        text-align: right;
    }

    .analisis-selector-diferencia {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        padding-top: 0.35rem;
        border-top: 1px dashed var(--color-borde);
        font-size: 0.75rem;
        color: var(--color-texto-tenue);
        font-variant-numeric: tabular-nums;
    }

    .analisis-selector-diferencia strong {
        font-weight: 600;
    }

    .analisis-selector-diferencia .positiva {
        color: var(--color-exito-texto);
    }

    .analisis-selector-diferencia .negativa {
        color: var(--color-error-texto);
    }
</style>

<nav class="tarjeta analisis-selector" aria-label="Módulos">
    <h3 class="analisis-selector-titulo">Módulo</h3>
    <?php foreach ($etiquetasModuloSelector as $origenOpcion => $etiqueta): ?>
    <?php
    $origenIngresoPar = $paresGastoIngreso[$origenOpcion] ?? null;
    $totalEgresosModulo = (float) ($totalesPorModulo[$origenOpcion] ?? 0.0);
    $totalIngresosModulo = $origenIngresoPar !== null ? (float) ($totalesPorModulo[$origenIngresoPar] ?? 0.0) : 0.0;
    $esActivo = in_array($origenActivo, [$origenOpcion, $origenIngresoPar], true);
    $esVacio = $totalEgresosModulo == 0.0 && $totalIngresosModulo == 0.0;
    $participacion = $totalEgresosSelector > 0 ? ($totalEgresosModulo / $totalEgresosSelector) * 100 : 0.0;
    $textoParticipacion = number_format($participacion, 1, ',', '.') . '%';
    $tituloParticipacion = $textoParticipacion . ' del total de egresos de todos los módulos';
    ?>

    <?php if ($origenIngresoPar === null): ?>
    <a href="<?= htmlspecialchars($urlOrigen($origenOpcion)) ?>"
       class="analisis-selector-item<?= $esActivo ? ' activo' : '' ?><?= $esVacio ? ' vacio' : '' ?>"
       <?= $esActivo ? 'aria-current="page"' : '' ?>>
        <span class="analisis-selector-cabecera">
            <span class="analisis-selector-nombre"><?= htmlspecialchars($etiqueta) ?></span>
            <?php if (!$esVacio): ?>
            <span class="analisis-selector-porcentaje" title="<?= htmlspecialchars($tituloParticipacion) ?>"><?= $textoParticipacion ?></span>
            <?php endif; ?>
        </span>
        <span class="analisis-selector-valor" title="<?= htmlspecialchars($monedaExacta($totalEgresosModulo)) ?>"><?= $monedaCorta($totalEgresosModulo) ?></span>
        <?php if ($esVacio): ?>
        <span class="analisis-selector-vacio">Sin registros</span>
        <?php else: ?>
        <span class="analisis-selector-barra" title="<?= htmlspecialchars($tituloParticipacion) ?>"><span style="width: <?= number_format(max($participacion, 1.5), 2, '.', '') ?>%;"></span></span>
        <?php endif; ?>
    </a>
    <?php else: ?>
    <?php $diferencia = $totalIngresosModulo - $totalEgresosModulo; ?>
    <div class="analisis-selector-item<?= $esActivo ? ' activo' : '' ?><?= $esVacio ? ' vacio' : '' ?>">
        <a href="<?= htmlspecialchars($urlOrigen($origenOpcion)) ?>" class="analisis-selector-cabecera">
            <span class="analisis-selector-nombre"><?= htmlspecialchars($etiqueta) ?></span>
            <?php if (!$esVacio): ?>
            <span class="analisis-selector-porcentaje" title="<?= htmlspecialchars($tituloParticipacion) ?>"><?= $textoParticipacion ?></span>
            <?php endif; ?>
        </a>
        <div class="analisis-selector-lados">
            <?php foreach (['egresos' => [$origenOpcion, 'Egresos', $totalEgresosModulo], 'ingresos' => [$origenIngresoPar, 'Ingresos', $totalIngresosModulo]] as $claveLado => [$origenLado, $etiquetaLado, $totalLado]): ?>
            <a href="<?= htmlspecialchars($urlOrigen($origenLado)) ?>"
               class="analisis-selector-lado<?= $origenActivo === $origenLado ? ' activo' : '' ?>"
               title="Ver <?= $etiquetaLado ?> de <?= htmlspecialchars($etiqueta) ?> — <?= htmlspecialchars($monedaExacta($totalLado)) ?>"
               <?= $origenActivo === $origenLado ? 'aria-current="page"' : '' ?>>
                <span class="analisis-selector-lado-punto <?= $claveLado ?>" aria-hidden="true"></span>
                <span class="analisis-selector-lado-etiqueta"><?= $etiquetaLado ?></span>
                <span class="analisis-selector-lado-valor"><?= $monedaCorta($totalLado) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if (!$esVacio): ?>
        <span class="analisis-selector-barra" title="<?= htmlspecialchars($tituloParticipacion) ?>"><span style="width: <?= number_format(max($participacion, 1.5), 2, '.', '') ?>%;"></span></span>
        <div class="analisis-selector-diferencia" title="Ingresos − Egresos: <?= htmlspecialchars($monedaExacta($diferencia)) ?>">
            <span>Ingresos − Egresos</span>
            <strong class="<?= $diferencia >= 0 ? 'positiva' : 'negativa' ?>"><?= $diferencia >= 0 ? '+' : '−' ?><?= $monedaCorta(abs($diferencia)) ?></strong>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</nav>
