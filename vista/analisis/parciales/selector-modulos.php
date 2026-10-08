<?php
/**
 * Selector de módulo de la pestaña "Análisis de distribución": tarjeta propia a la izquierda de
 * la tabla (ver vista/analisis/index.php), con una mini-tarjeta por cada origen con techo (Gasto,
 * Extensión, Postgrado, Unisalud, Monitores, ARL — Sin Excedentes excluido a propósito, ver plan
 * el-techo-no-deberia-kind-candle: ese módulo está descontinuado). Clic en una mini-tarjeta
 * recarga la pestaña con ese origen activo.
 *
 * Cada tarjeta muestra el total (sin decimales; el valor exacto va en el title), arriba a la
 * derecha el % de asignación de egresos ($asignacionPorModulo) y, cuando aplica, una barra que
 * hace de separador entre el nombre y los valores: Gasto = avance contra su techo
 * ($techosPorModulo; roja si lo supera); Autogestión = distribución de sus egresos en Gastos /
 * Inversiones / Excedentes ($distribucionPorModulo). Ver
 * AnalisisControlador::obtenerIndicadoresSelector(). Extensión y Postgrado ($paresGastoIngreso)
 * muestran Egresos e Ingresos como dos filas clicables — cada una lleva directo a esa pestaña de
 * la tabla (ver tabla-modulo.php). Todas las tarjetas tienen la misma altura mínima.
 *
 * Variables esperadas: $origenActivo, $totalesPorModulo, $techosPorModulo, $asignacionPorModulo,
 * $distribucionPorModulo, $paresGastoIngreso, $vista, $snapshotIdActual, $dependenciaFiltroActual.
 */

$paresGastoIngreso = $paresGastoIngreso ?? [];
$techosPorModulo = $techosPorModulo ?? [];
$asignacionPorModulo = $asignacionPorModulo ?? [];
$distribucionPorModulo = $distribucionPorModulo ?? [];
$clasesDistribucion = ['Gastos' => 'gastos', 'Inversiones' => 'inversiones', 'Excedentes' => 'excedentes', 'Otros' => 'otros'];

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
        justify-content: space-between;
        gap: 0.25rem;
        min-height: 88px;
        box-sizing: border-box;
        padding: 0.45rem 0.6rem 0.5rem;
        border-radius: 8px;
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
        font-size: 0.8rem;
        color: var(--color-texto);
        font-weight: 600;
    }

    .analisis-selector-valor {
        font-size: 0.9rem;
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

    .analisis-selector-barra.excedido span {
        background: var(--color-error-texto);
    }

    /* Autogestión: un tramo por categoría de egreso. */
    .analisis-selector-barra.distribucion {
        display: flex;
        gap: 1px;
    }

    .analisis-selector-barra.distribucion span {
        border-radius: 0;
    }

    .analisis-selector-barra.distribucion .gastos {
        background: var(--color-costos);
    }

    .analisis-selector-barra.distribucion .inversiones {
        background: var(--color-inversion);
    }

    .analisis-selector-barra.distribucion .excedentes {
        background: var(--color-excedentes);
    }

    .analisis-selector-barra.distribucion .otros {
        background: var(--color-borde-hover);
    }

    .analisis-selector-porcentaje {
        font-size: 0.7rem;
        color: var(--color-texto-tenue);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .analisis-selector-porcentaje.excedido {
        color: var(--color-error-texto);
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
        min-height: 24px;
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
        font-size: 0.72rem;
    }

    .analisis-selector-lado-valor {
        font-size: 0.78rem;
        text-align: right;
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
    $asignacion = $asignacionPorModulo[$origenOpcion] ?? null;
    $techoModulo = $techosPorModulo[$origenOpcion] ?? null;
    $distribucion = $distribucionPorModulo[$origenOpcion] ?? null;

    // La barra (separador entre nombre y valores) se arma una vez para las dos variantes de tarjeta.
    $htmlBarra = '';
    if ($techoModulo !== null) {
        $avanceTecho = $techoModulo['avance'] / $techoModulo['techo'] * 100;
        $tituloTecho = number_format($avanceTecho, 1, ',', '.') . '% del ' . $techoModulo['etiqueta']
            . ' (' . $monedaCorta($techoModulo['avance']) . ' de ' . $monedaCorta($techoModulo['techo']) . ')';
        $htmlBarra = '<span class="analisis-selector-barra' . ($avanceTecho > 100 ? ' excedido' : '') . '" title="' . htmlspecialchars($tituloTecho) . '">'
            . '<span style="width: ' . number_format(min(100, max($avanceTecho, 1.5)), 2, '.', '') . '%;"></span></span>';
    } elseif ($distribucion !== null) {
        $totalDistribucion = array_sum($distribucion);
        $tramos = '';
        $resumenTramos = [];
        foreach ($distribucion as $categoria => $valorCategoria) {
            if ($valorCategoria <= 0) {
                continue;
            }
            $textoTramo = $categoria . ': ' . number_format($valorCategoria / $totalDistribucion * 100, 1, ',', '.') . '% (' . $monedaCorta($valorCategoria) . ')';
            $resumenTramos[] = $textoTramo;
            $tramos .= '<span class="' . $clasesDistribucion[$categoria] . '" style="flex: ' . number_format($valorCategoria, 2, '.', '') . ' 1 0;" title="' . htmlspecialchars($textoTramo) . '"></span>';
        }
        $htmlBarra = '<span class="analisis-selector-barra distribucion" title="' . htmlspecialchars('Distribución de egresos — ' . implode(' · ', $resumenTramos)) . '">' . $tramos . '</span>';
    } elseif ($origenOpcion === 'gasto_principal') {
        $htmlBarra = '<span class="analisis-selector-vacio">Sin techo</span>';
    }

    $htmlPorcentaje = $asignacion !== null
        ? '<span class="analisis-selector-porcentaje' . ($asignacion['porcentaje'] > 100 ? ' excedido' : '') . '" title="' . htmlspecialchars($asignacion['titulo']) . '">'
            . number_format($asignacion['porcentaje'], 1, ',', '.') . '%</span>'
        : '';
    ?>

    <?php if ($origenIngresoPar === null): ?>
    <a href="<?= htmlspecialchars($urlOrigen($origenOpcion)) ?>"
       class="analisis-selector-item<?= $esActivo ? ' activo' : '' ?><?= $esVacio ? ' vacio' : '' ?>"
       <?= $esActivo ? 'aria-current="page"' : '' ?>>
        <span class="analisis-selector-cabecera">
            <span class="analisis-selector-nombre"><?= htmlspecialchars($etiqueta) ?></span>
            <?= $htmlPorcentaje ?>
        </span>
        <?= $htmlBarra ?>
        <span class="analisis-selector-valor" title="<?= htmlspecialchars($monedaExacta($totalEgresosModulo)) ?>"><?= $monedaCorta($totalEgresosModulo) ?></span>
        <?php if ($esVacio): ?>
        <span class="analisis-selector-vacio">Sin registros</span>
        <?php endif; ?>
    </a>
    <?php else: ?>
    <div class="analisis-selector-item<?= $esActivo ? ' activo' : '' ?><?= $esVacio ? ' vacio' : '' ?>">
        <a href="<?= htmlspecialchars($urlOrigen($origenOpcion)) ?>" class="analisis-selector-cabecera">
            <span class="analisis-selector-nombre"><?= htmlspecialchars($etiqueta) ?></span>
            <?= $htmlPorcentaje ?>
        </a>
        <?= $htmlBarra ?>
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
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</nav>
