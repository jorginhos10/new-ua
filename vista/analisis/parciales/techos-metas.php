<?php
/**
 * Pestaña "Techos y Metas" de ?ruta=analisis. Variables esperadas (ver
 * AnalisisControlador::renderizarTechosMetas()): $datos (nodos, totales, serie), $bloque,
 * $vista, $dependenciaFiltroActual.
 */

$etiquetasBloque = [
    'gastos' => 'Gasto principal',
    'extension' => 'Extensión',
    'postgrado' => 'Postgrado',
    'unisalud' => 'Unidad de Salud',
];
$esGastos = $bloque === 'gastos';
$totales = $datos['totales'];
$serie = $datos['serie'];

if (!function_exists('dineroTechosMetas')) {
    function dineroTechosMetas(float $valor): string
    {
        return '$' . number_format($valor, 2, ',', '.');
    }

    function porcentajeTechosMetas(?float $valor): string
    {
        return $valor === null ? '—' : number_format($valor, 1, ',', '.') . '%';
    }

    function montoCompactoTechosMetas(float $valor): string
    {
        if (abs($valor) >= 1e9) {
            return '$' . number_format($valor / 1e9, 1, ',', '.') . ' mil M';
        }
        if (abs($valor) >= 1e6) {
            return '$' . number_format($valor / 1e6, 0, ',', '.') . ' M';
        }

        return '$' . number_format($valor, 0, ',', '.');
    }

    function barraTechosMetas(?float $porcentaje): string
    {
        if ($porcentaje === null) {
            return '<span class="texto-atenuado">—</span>';
        }

        $excedida = $porcentaje > 100;
        return '<div class="tm-barra" title="' . porcentajeTechosMetas($porcentaje) . '">'
            . '<div class="tm-barra-relleno' . ($excedida ? ' tm-barra-excedida' : '') . '" style="width:' . round(min($porcentaje, 100), 1) . '%;"></div></div>';
    }

    function filaTechosMetas(array $nodo, string $bloque, int $nivel, array $ancestros): void
    {
        $id = (int) $nodo['id'];
        $tieneHijos = $nodo['hijos'] !== [];
        $ancestrosTexto = implode(',', $ancestros);
        $destacada = !empty($nodo['esGrupo']) || !empty($nodo['destacado']);
        $clasesFila = 'tm-fila' . ($tieneHijos ? ' tm-expandible' : '') . ($destacada ? ' tm-grupo' : '');

        echo '<tr class="' . $clasesFila . '" data-id="' . $id . '" data-ancestros="' . $ancestrosTexto . '">';
        echo '<td class="tm-celda-nombre"><div class="tm-nombre-contenido" style="margin-left:' . ($nivel * 1.5) . 'rem;">';
        if ($tieneHijos) {
            echo '<button type="button" class="tm-toggle" data-id="' . $id . '" aria-expanded="true" title="Contraer/expandir" aria-label="Contraer/expandir">'
                . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></button>';
        } else {
            echo '<span class="tm-sangria-hoja" aria-hidden="true"></span>';
        }
        echo '<span>' . htmlspecialchars(Dependencia::nombreVisible($nodo['nombre'])) . '</span></div></td>';

        if ($bloque === 'gastos') {
            $techo = $nodo['techo'] === null ? null : (float) $nodo['techo'];
            $restante = $nodo['restante'] === null ? null : (float) $nodo['restante'];
            $porcentaje = $nodo['porcentaje'] === null ? null : (float) $nodo['porcentaje'];
            echo '<td class="tm-num">' . ($techo === null ? '—' : dineroTechosMetas($techo)) . '</td>';
            echo '<td class="tm-num">' . dineroTechosMetas((float) $nodo['asignacion']) . '</td>';
            echo '<td class="tm-num' . ($restante !== null && $restante < -0.005 ? ' tm-negativo' : '') . '">' . ($restante === null ? '—' : dineroTechosMetas($restante)) . '</td>';
            echo '<td class="tm-num' . ($porcentaje !== null && $porcentaje > 100 ? ' tm-negativo' : '') . '">' . porcentajeTechosMetas($porcentaje) . '</td>';
            echo '<td class="tm-celda-barra">' . barraTechosMetas($porcentaje) . '</td>';
        } else {
            echo '<td class="tm-num">' . dineroTechosMetas((float) $nodo['ingresos']) . '</td>';
        }
        echo "</tr>\n";

        foreach ($nodo['hijos'] as $hijo) {
            filaTechosMetas($hijo, $bloque, $nivel + 1, array_merge($ancestros, [$id]));
        }
    }
}

$anchoGrafica = 560;
$altoGrafica = 260;
$margenIzq = 74;
$margenDer = 14;
$margenSup = 20;
$margenInf = 34;
$areaAncho = $anchoGrafica - $margenIzq - $margenDer;
$areaAlto = $altoGrafica - $margenSup - $margenInf;
$cantidadAnios = count($serie);
$anchoSlot = $cantidadAnios > 0 ? $areaAncho / $cantidadAnios : $areaAncho;
$centroAnio = static fn (int $indice): float => $margenIzq + $anchoSlot * ($indice + 0.5);

$maximoPorcentaje = 100.0;
foreach ($serie as $punto) {
    if ($punto['porcentaje'] !== null) {
        $maximoPorcentaje = max($maximoPorcentaje, $punto['porcentaje']);
    }
}
$maximoPorcentaje = ceil($maximoPorcentaje / 10) * 10;
$coordenadasLinea = [];
foreach ($serie as $indice => $punto) {
    if ($punto['porcentaje'] === null) {
        continue;
    }
    $coordenadasLinea[] = [
        'x' => $centroAnio($indice),
        'y' => $margenSup + $areaAlto * (1 - $punto['porcentaje'] / $maximoPorcentaje),
        'anio' => $punto['anio'],
        'porcentaje' => $punto['porcentaje'],
    ];
}
$yReferencia100 = $margenSup + $areaAlto * (1 - 100 / $maximoPorcentaje);

$maximoValor = 0.0;
foreach ($serie as $punto) {
    if ($punto['valor'] !== null) {
        $maximoValor = max($maximoValor, $punto['valor']);
    }
}
$maximoValor = $maximoValor > 0 ? $maximoValor : 1.0;
$anchoBarra = min($anchoSlot * 0.5, 110);
$barras = [];
foreach ($serie as $indice => $punto) {
    if ($punto['valor'] === null) {
        continue;
    }
    $alturaBarra = $punto['valor'] / $maximoValor * $areaAlto;
    $barras[] = [
        'x' => $centroAnio($indice) - $anchoBarra / 2,
        'y' => $margenSup + $areaAlto - $alturaBarra,
        'alto' => $alturaBarra,
        'ancho' => $anchoBarra,
        'centro' => $centroAnio($indice),
        'anio' => $punto['anio'],
        'valor' => $punto['valor'],
    ];
}
?>

<div class="tarjeta tm-tarjeta">
    <style>
        .tm-tarjeta { padding: 0.5rem; display: flex; flex-direction: column; flex: 1; min-height: 0; margin: 0.5rem; }
        .tm-scroll { flex: 1; min-height: 0; overflow: auto; padding: 0.25rem 0.5rem 0.5rem; }
        .tm-pills { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.9rem; }
        .tm-pills a { font-size: 0.85rem; padding: 0.35rem 0.75rem; border: 1px solid var(--color-borde); border-radius: 999px; color: var(--color-texto); text-decoration: none; }
        .tm-pills a.activo { background: var(--color-primario); border-color: var(--color-primario); color: #fff; }
        .tm-tabla { --tm-control: 32px; width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        @media (pointer: coarse) { .tm-tabla { --tm-control: 40px; } }
        .tm-tabla th, .tm-tabla td { padding: 0.2rem 0.6rem; border-bottom: 1px solid var(--color-borde); text-align: left; }
        .tm-tabla td { height: var(--tm-control); }
        .tm-tabla thead th { position: sticky; top: 0; z-index: 1; background: var(--color-superficie); font-weight: 600; color: var(--color-texto-secundario); }
        .tm-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .tm-negativo { color: var(--color-error-texto, #a83232); }
        .tm-total td { font-weight: 700; background: var(--color-fondo); }
        .tm-grupo td { font-weight: 700; background: var(--color-fondo); }
        .tm-grupo .tm-celda-nombre { text-transform: uppercase; }
        .tm-celda-barra { min-width: 170px; }
        .tm-barra { height: 12px; min-width: 150px; background: var(--color-fondo); border: 1px solid var(--color-borde); border-radius: 999px; overflow: hidden; }
        .tm-barra-relleno { height: 100%; background: var(--color-primario); }
        .tm-barra-excedida { background: var(--color-error-texto, #a83232); }
        .tm-expandible .tm-celda-nombre { cursor: pointer; user-select: none; }
        .tm-nombre-contenido { display: flex; align-items: center; gap: 0.35rem; min-height: var(--tm-control); }
        .tm-toggle { width: var(--tm-control); height: var(--tm-control); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 0; border: 1px solid transparent; border-radius: 6px; background: transparent; color: var(--color-texto-secundario); cursor: pointer; }
        .tm-toggle:hover, .tm-expandible .tm-celda-nombre:hover .tm-toggle { background: rgba(0, 0, 0, 0.08); }
        .tm-toggle:focus-visible { outline: 2px solid var(--color-primario); outline-offset: 1px; }
        .tm-toggle svg { transition: transform 0.15s; }
        .tm-toggle[aria-expanded="false"] svg { transform: rotate(-90deg); }
        .tm-sangria-hoja { width: var(--tm-control); flex-shrink: 0; display: inline-block; }
        .tm-resumen { display: flex; gap: 1.2rem; flex-wrap: wrap; margin: 0.6rem 0 1rem; }
        .tm-resumen div { min-width: 140px; }
        .tm-resumen small { display: block; color: var(--color-texto-secundario); }
        .tm-aviso { font-size: 0.85rem; color: var(--color-texto-tenue); margin: 0.4rem 0; }
        .tm-grafica { display: block; width: 100%; height: auto; margin-bottom: 0.5rem; }
        .tm-graficas { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; align-items: start; }
        @media (max-width: 900px) { .tm-graficas { grid-template-columns: 1fr; } }
    </style>

    <div class="tm-scroll" id="tm-scroll">
        <div class="tm-pills">
            <?php foreach ($etiquetasBloque as $clave => $etiqueta): ?>
            <a href="<?= htmlspecialchars(analisisUrl('techos', $vista, ['bloque' => $clave] + ($vista === 'usuario' && $dependenciaFiltroActual !== null ? ['dependencia' => $dependenciaFiltroActual] : []))) ?>" class="<?= $clave === $bloque ? 'activo' : '' ?>"><?= htmlspecialchars($etiqueta) ?></a>
            <?php endforeach; ?>
        </div>

        <h1 style="font-size: 1.1rem;"><?= $esGastos ? 'Techo vs asignación' : 'Tope vs ingresos' ?> · <?= htmlspecialchars($etiquetasBloque[$bloque]) ?></h1>

        <?php if (!$esGastos && $vista === 'usuario'): ?>
        <p class="tm-aviso">El tope de autogestión es institucional (uno por módulo): en vista de usuario solo se muestran los ingresos de cada dependencia.</p>
        <?php elseif (!$esGastos && (float) ($totales['tope'] ?? 0) <= 0): ?>
        <p class="tm-aviso">Sin tope configurado para este módulo — el restante y el porcentaje aparecerán cuando se defina.</p>
        <?php endif; ?>

        <?php if (!$esGastos && $vista !== 'usuario'): ?>
        <div class="tm-resumen">
            <div><small>Tope del módulo</small><strong><?= dineroTechosMetas((float) ($totales['tope'] ?? 0)) ?></strong></div>
            <div><small>Ingresos</small><strong><?= dineroTechosMetas((float) ($totales['ingresos'] ?? 0)) ?></strong></div>
            <div><small>Restante</small><strong class="<?= (float) ($totales['restante'] ?? 0) < -0.005 ? 'tm-negativo' : '' ?>"><?= dineroTechosMetas((float) ($totales['restante'] ?? 0)) ?></strong></div>
            <div><small>Cumplimiento</small><strong><?= porcentajeTechosMetas($totales['porcentaje'] ?? null) ?></strong></div>
        </div>
        <?php endif; ?>

        <?php if (empty($datos['nodos'])): ?>
        <p class="texto-atenuado">No hay dependencias con valores para mostrar<?= $vista === 'usuario' ? ' en esta dependencia' : '' ?>.</p>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="tm-tabla" id="tm-tabla">
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <?php if ($esGastos): ?>
                        <th class="tm-num">Techo</th>
                        <th class="tm-num">Asignación</th>
                        <th class="tm-num">Restante</th>
                        <th class="tm-num">% asignado</th>
                        <th class="tm-celda-barra">Recurso asignado</th>
                        <?php else: ?>
                        <th class="tm-num">Ingresos</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['nodos'] as $nodoRaiz): ?>
                        <?php filaTechosMetas($nodoRaiz, $bloque, 0, []); ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="tm-total">
                        <td>Total</td>
                        <?php if ($esGastos): ?>
                        <td class="tm-num"><?= dineroTechosMetas((float) $totales['techo']) ?></td>
                        <td class="tm-num"><?= dineroTechosMetas((float) $totales['asignacion']) ?></td>
                        <td class="tm-num<?= (float) $totales['restante'] < -0.005 ? ' tm-negativo' : '' ?>"><?= dineroTechosMetas((float) $totales['restante']) ?></td>
                        <td class="tm-num"><?= porcentajeTechosMetas($totales['porcentaje']) ?></td>
                        <td class="tm-celda-barra"><?= barraTechosMetas($totales['porcentaje']) ?></td>
                        <?php else: ?>
                        <td class="tm-num"><?= dineroTechosMetas((float) $totales['ingresos']) ?></td>
                        <?php endif; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <div class="tm-graficas"><div>
        <h2 style="font-size: 0.95rem; margin: 1.2rem 0 0.4rem;"><?= $esGastos ? 'Techo por año' : 'Tope por año' ?></h2>
        <?php if ($barras === []): ?>
        <p class="texto-atenuado">Todavía no hay años con <?= $esGastos ? 'techo' : 'tope' ?> para graficar.</p>
        <?php else: ?>
        <svg class="tm-grafica" viewBox="0 0 <?= $anchoGrafica ?> <?= $altoGrafica ?>" role="img" aria-label="<?= $esGastos ? 'Techo' : 'Tope' ?> por año">
            <?php foreach ([0.0, $maximoValor / 2, $maximoValor] as $tick): ?>
            <?php $yTick = $margenSup + $areaAlto * (1 - $tick / $maximoValor); ?>
            <line x1="<?= $margenIzq ?>" y1="<?= $yTick ?>" x2="<?= $anchoGrafica - $margenDer ?>" y2="<?= $yTick ?>" style="stroke: var(--color-borde);" />
            <text x="<?= $margenIzq - 8 ?>" y="<?= $yTick + 4 ?>" text-anchor="end" style="font-size: 11px; fill: var(--color-texto-secundario);"><?= montoCompactoTechosMetas($tick) ?></text>
            <?php endforeach; ?>
            <?php foreach ($serie as $indiceAnio => $punto): ?>
            <text x="<?= round($centroAnio($indiceAnio), 1) ?>" y="<?= $altoGrafica - 12 ?>" text-anchor="middle" style="font-size: 11px; fill: var(--color-texto-secundario);"><?= $punto['anio'] ?></text>
            <?php endforeach; ?>
            <?php foreach ($barras as $barra): ?>
            <rect x="<?= round($barra['x'], 1) ?>" y="<?= round($barra['y'], 1) ?>" width="<?= round($barra['ancho'], 1) ?>" height="<?= round($barra['alto'], 1) ?>" rx="3" style="fill: var(--color-primario); opacity: 0.85;">
                <title><?= $barra['anio'] ?>: <?= dineroTechosMetas((float) $barra['valor']) ?></title>
            </rect>
            <text x="<?= round($barra['centro'], 1) ?>" y="<?= round($barra['y'] - 6, 1) ?>" text-anchor="middle" style="font-size: 11px; fill: var(--color-texto);"><?= montoCompactoTechosMetas($barra['valor']) ?></text>
            <?php endforeach; ?>
        </svg>
        <?php endif; ?>

        </div><div>
        <h2 style="font-size: 0.95rem; margin: 1.2rem 0 0.4rem;"><?= $esGastos ? 'Porcentaje asignado por año' : 'Porcentaje de cumplimiento por año' ?></h2>
        <?php if ($coordenadasLinea === []): ?>
        <p class="texto-atenuado">Todavía no hay años con <?= $esGastos ? 'techo' : 'tope' ?> e información para graficar.</p>
        <?php else: ?>
        <svg class="tm-grafica" viewBox="0 0 <?= $anchoGrafica ?> <?= $altoGrafica ?>" role="img" aria-label="Porcentaje por año">
            <?php for ($tick = 0; $tick <= $maximoPorcentaje; $tick += $maximoPorcentaje / 2): ?>
            <?php $yTick = $margenSup + $areaAlto * (1 - $tick / $maximoPorcentaje); ?>
            <line x1="<?= $margenIzq ?>" y1="<?= $yTick ?>" x2="<?= $anchoGrafica - $margenDer ?>" y2="<?= $yTick ?>" style="stroke: var(--color-borde);" />
            <text x="<?= $margenIzq - 8 ?>" y="<?= $yTick + 4 ?>" text-anchor="end" style="font-size: 11px; fill: var(--color-texto-secundario);"><?= number_format($tick, 0, ',', '.') ?>%</text>
            <?php endfor; ?>
            <line x1="<?= $margenIzq ?>" y1="<?= $yReferencia100 ?>" x2="<?= $anchoGrafica - $margenDer ?>" y2="<?= $yReferencia100 ?>" style="stroke: var(--color-texto-tenue); stroke-dasharray: 4 4;" />
            <polyline fill="none" style="stroke: var(--color-primario); stroke-width: 2.5;" points="<?= implode(' ', array_map(static fn (array $c): string => round($c['x'], 1) . ',' . round($c['y'], 1), $coordenadasLinea)) ?>" />
            <?php foreach ($serie as $indiceAnio => $punto): ?>
            <text x="<?= round($centroAnio($indiceAnio), 1) ?>" y="<?= $altoGrafica - 12 ?>" text-anchor="middle" style="font-size: 11px; fill: var(--color-texto-secundario);"><?= $punto['anio'] ?></text>
            <?php endforeach; ?>
            <?php foreach ($coordenadasLinea as $coordenada): ?>
            <circle cx="<?= round($coordenada['x'], 1) ?>" cy="<?= round($coordenada['y'], 1) ?>" r="5" style="fill: var(--color-primario);">
                <title><?= $coordenada['anio'] ?>: <?= porcentajeTechosMetas($coordenada['porcentaje']) ?></title>
            </circle>
            <?php endforeach; ?>
        </svg>
        <?php endif; ?>
        </div></div>
    </div>
</div>

<script>
(function () {
    var tabla = document.getElementById('tm-tabla');
    if (!tabla) {
        return;
    }

    var clave = 'techos_metas_contraidos_' + <?= json_encode($bloque) ?>;

    function leerContraidos() {
        try {
            var guardado = JSON.parse(localStorage.getItem(clave) || '[]');
            return Array.isArray(guardado) ? guardado.map(String) : [];
        } catch (error) {
            return [];
        }
    }

    function guardarContraidos(lista) {
        try {
            localStorage.setItem(clave, JSON.stringify(lista));
        } catch (error) {
            // localStorage no disponible: el plegado simplemente no se recuerda
        }
    }

    function marcarBoton(boton, expandido) {
        boton.setAttribute('aria-expanded', expandido ? 'true' : 'false');
    }

    function estaVisible(fila) {
        var ancestros = fila.dataset.ancestros ? fila.dataset.ancestros.split(',') : [];
        return ancestros.every(function (idAncestro) {
            var boton = tabla.querySelector('.tm-toggle[data-id="' + idAncestro + '"]');
            return !boton || boton.getAttribute('aria-expanded') === 'true';
        });
    }

    function refrescar() {
        tabla.querySelectorAll('tr.tm-fila').forEach(function (fila) {
            fila.hidden = !estaVisible(fila);
        });
    }

    var contraidosGuardados = leerContraidos();
    tabla.querySelectorAll('.tm-toggle').forEach(function (boton) {
        marcarBoton(boton, contraidosGuardados.indexOf(boton.dataset.id) === -1);
    });
    refrescar();

    // Un solo listener sobre la fila con hijas: cubre el clic en el botón y en el nombre.
    tabla.addEventListener('click', function (evento) {
        var fila = evento.target.closest('tr.tm-expandible');
        if (!fila) {
            return;
        }
        var boton = fila.querySelector('.tm-toggle');
        if (!boton) {
            return;
        }
        var estabaExpandido = boton.getAttribute('aria-expanded') === 'true';
        marcarBoton(boton, !estabaExpandido);

        var contraidos = leerContraidos().filter(function (id) { return id !== boton.dataset.id; });
        if (estabaExpandido) {
            contraidos.push(boton.dataset.id);
        }
        guardarContraidos(contraidos);
        refrescar();
    });
})();
</script>
