<?php
/**
 * Vista "Indicadores" del Comparativo vigencia inicial (tab=comparativo): la proyección del año
 * vigente (2027) frente al año anterior (2026) en sus tres momentos — Inicial (aprobado), Final
 * (definitivo tras modificaciones) y Corte (ejecutado a la fecha) — más el cierre estimado (corte
 * llevado al año completo). Se alterna con el árbol con el toggle "Árbol | Indicadores" de
 * arbol.php (mismo contenedor #arbol-grafica-contenedor que la gráfica de PP).
 *
 * Los cálculos vienen hechos de AnalisisControlador::calcularComparativoVigencia(); aquí solo se
 * dibujan: tarjetas del total y cuatro gráficas por sección raíz (barras por sección, variación %
 * frente a una base elegible, ejecución vs tiempo y distribución comparada). Sin librerías: barras
 * HTML/CSS con los colores de sección del árbol (colorGrupoArbol()).
 *
 * Variables esperadas: $comparativoVigencia, $anioVigenteNumero, $anioAnteriorNumero, $lado.
 */
$cmp = $comparativoVigencia ?? null;
if ($cmp === null) {
    return;
}

$anioProy = (int) $anioVigenteNumero;
$anioBase = (int) $anioAnteriorNumero;
$esIngresos = $cmp['lado'] === 'ingresos';
$nombreEjecucion = $esIngresos ? 'Recaudo' : 'Ejecución';
$verboEjecutar = $esIngresos ? 'recaudar' : 'ejecutar';
$total = $cmp['total'];
$secciones = $cmp['secciones'];
$porcentajeTiempo = (float) $cmp['porcentajeTiempo'];

$moneda = static fn (?float $valor): string => $valor === null ? 'Sin dato' : '$' . number_format($valor, 0, ',', '.');
$monedaConSigno = static fn (?float $valor): string => $valor === null ? 'Sin dato' : ($valor > 0 ? '+' : ($valor < 0 ? '−' : '')) . '$' . number_format(abs($valor), 0, ',', '.');
$porcentaje = static fn (?float $valor, bool $signo = false): string => $valor === null ? 'Sin dato' : (($signo && $valor > 0) ? '+' : '') . number_format($valor, 1, ',', '.') . '%';
$claseSigno = static fn (?float $valor): string => $valor === null ? 'cmp-neutro' : ($valor > 0 ? 'cmp-sube' : ($valor < 0 ? 'cmp-baja' : 'cmp-neutro'));

$bases = [
    'inicial' => $anioBase . ' Inicial',
    'final' => $anioBase . ' Final',
    'corte' => $anioBase . ' Corte',
    'cierre' => 'Cierre estimado ' . $anioBase,
];
$series = [
    'inicial' => ['etiqueta' => $anioBase . ' Inicial', 'clase' => 'cmp-s-inicial'],
    'final' => ['etiqueta' => $anioBase . ' Final', 'clase' => 'cmp-s-final'],
    'corte' => ['etiqueta' => $anioBase . ' Corte', 'clase' => 'cmp-s-corte'],
    'proyeccion' => ['etiqueta' => (string) $anioProy, 'clase' => 'cmp-s-proyeccion'],
];

$maximoBarras = 0.0;
foreach ($secciones as $seccion) {
    foreach (['inicial', 'final', 'corte', 'proyeccion'] as $clave) {
        $maximoBarras = max($maximoBarras, abs((float) $seccion[$clave]));
    }
}
$maximoBarras = $maximoBarras > 0 ? $maximoBarras : 1.0;

$maximoVariacion = [];
foreach (array_keys($bases) as $base) {
    $maximo = 0.0;
    foreach ($secciones as $seccion) {
        $maximo = max($maximo, abs((float) ($seccion['variacion'][$base]['porcentaje'] ?? 0.0)));
    }
    $maximoVariacion[$base] = $maximo > 0 ? $maximo : 1.0;
}
$nombreSeccion = static fn (array $seccion): string => trim(($seccion['codigo'] !== '' ? $seccion['codigo'] . ' ' : '') . $seccion['etiqueta']);
?>
<style>
    .cmp-panel { flex: 1; min-height: 0; overflow: auto; padding: 0.25rem 0.25rem 1rem; }
    .cmp-tarjetas { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 0.6rem; margin-bottom: 1rem; }
    .cmp-tarjeta { border: 1px solid var(--color-borde); border-radius: 8px; padding: 0.55rem 0.7rem; background: var(--color-superficie); }
    .cmp-tarjeta-titulo { font-size: 0.72rem; color: var(--color-texto-tenue); text-transform: uppercase; letter-spacing: 0.03em; }
    .cmp-tarjeta-valor { font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; margin-top: 0.15rem; }
    .cmp-tarjeta-detalle { font-size: 0.78rem; font-variant-numeric: tabular-nums; margin-top: 0.1rem; }
    .cmp-sube { color: #1e6b3c; }
    .cmp-baja { color: #a83232; }
    .cmp-neutro { color: var(--color-texto-tenue); }
    .cmp-bloque { border: 1px solid var(--color-borde); border-radius: 8px; padding: 0.7rem 0.8rem; margin-bottom: 1rem; background: var(--color-superficie); }
    .cmp-bloque h3 { margin: 0 0 0.6rem; font-size: 0.9rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; }
    .cmp-leyenda { display: flex; gap: 0.75rem; flex-wrap: wrap; font-size: 0.72rem; font-weight: 400; color: var(--color-texto-secundario); }
    .cmp-leyenda span::before { content: ''; display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin-right: 0.3rem; vertical-align: middle; background: var(--cmp-color); }
    .cmp-fila { display: grid; grid-template-columns: minmax(140px, 28%) 1fr; gap: 0.6rem; align-items: center; padding: 0.3rem 0; border-top: 1px solid var(--color-borde); }
    .cmp-fila:first-of-type { border-top: none; }
    .cmp-nombre { font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; overflow: hidden; }
    .cmp-nombre span:last-child { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cmp-punto { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .cmp-barras { display: flex; flex-direction: column; gap: 2px; }
    .cmp-barra { display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-variant-numeric: tabular-nums; }
    .cmp-barra i { display: block; height: 9px; border-radius: 2px; min-width: 1px; }
    .cmp-s-inicial { --cmp-color: #b9c3d1; } .cmp-s-final { --cmp-color: #7f8ea3; } .cmp-s-corte { --cmp-color: #eda100; } .cmp-s-proyeccion { --cmp-color: var(--color-primario); }
    .cmp-barra i { background: var(--cmp-color); }
    .cmp-bases { display: inline-flex; gap: 0.25rem; }
    .cmp-bases button { border: 1px solid var(--color-borde); background: var(--color-superficie); border-radius: 999px; padding: 0.15rem 0.6rem; font-size: 0.72rem; cursor: pointer; color: var(--color-texto-secundario); }
    .cmp-bases button.activo { border-color: var(--color-primario); color: var(--color-primario); font-weight: 600; }
    .cmp-divergente { position: relative; height: 16px; }
    .cmp-divergente::before { content: ''; position: absolute; left: 50%; top: 0; bottom: 0; border-left: 1px solid var(--color-borde-hover); }
    .cmp-divergente i { position: absolute; top: 3px; height: 10px; border-radius: 2px; }
    .cmp-divergente b { position: absolute; top: 0; font-size: 0.7rem; font-weight: 600; font-variant-numeric: tabular-nums; line-height: 16px; }
    .cmp-ejecucion { position: relative; height: 14px; background: var(--color-gris-fondo); border-radius: 3px; }
    .cmp-ejecucion i { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 3px; }
    .cmp-ejecucion em { position: absolute; top: -3px; bottom: -3px; width: 2px; background: var(--color-texto); }
    .cmp-ejecucion-dato { font-size: 0.7rem; font-variant-numeric: tabular-nums; margin-top: 2px; }
    .cmp-apilada { display: flex; height: 22px; border-radius: 4px; overflow: hidden; gap: 1px; }
    .cmp-apilada span { display: block; height: 100%; }
    .cmp-tabla { width: 100%; border-collapse: collapse; font-size: 0.78rem; margin-top: 0.6rem; }
    .cmp-tabla th, .cmp-tabla td { padding: 0.25rem 0.4rem; border-bottom: 1px solid var(--color-borde); text-align: right; font-variant-numeric: tabular-nums; }
    .cmp-tabla th:first-child, .cmp-tabla td:first-child { text-align: left; }
</style>

<div class="arbol-grafica-contenedor" id="arbol-grafica-contenedor" hidden>
<div class="cmp-panel">
    <?php // ---- Tarjetas del total ---- ?>
    <div class="cmp-tarjetas">
        <div class="cmp-tarjeta" title="Proyección del año <?= $anioProy ?> (total del árbol)">
            <div class="cmp-tarjeta-titulo">Proyección <?= $anioProy ?></div>
            <div class="cmp-tarjeta-valor"><?= $moneda($total['proyeccion']) ?></div>
        </div>
        <?php foreach ($bases as $base => $nombreBase): ?>
        <?php $var = $total['variacion'][$base]; ?>
        <div class="cmp-tarjeta" title="<?= htmlspecialchars($anioProy . ' frente a ' . $nombreBase . ($base === 'cierre' ? ' (lo ejecutado a corte llevado al año completo)' : '')) ?>">
            <div class="cmp-tarjeta-titulo">vs <?= htmlspecialchars($nombreBase) ?></div>
            <div class="cmp-tarjeta-valor <?= $claseSigno($var['porcentaje']) ?>"><?= $porcentaje($var['porcentaje'], true) ?></div>
            <div class="cmp-tarjeta-detalle"><?= $monedaConSigno($var['valor']) ?> · base <?= $moneda($base === 'cierre' ? $total['cierre'] : $total[$base]) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="cmp-tarjeta" title="Cuánto se adicionó (o redujo) durante <?= $anioBase ?>: Final − Inicial">
            <div class="cmp-tarjeta-titulo">Modificación <?= $anioBase ?></div>
            <div class="cmp-tarjeta-valor <?= $claseSigno($total['modificacion']) ?>"><?= $monedaConSigno($total['modificacion']) ?></div>
            <div class="cmp-tarjeta-detalle"><?= $porcentaje($total['modificacionPorcentaje'], true) ?> sobre el inicial</div>
        </div>
        <div class="cmp-tarjeta" title="<?= $nombreEjecucion ?> a la fecha de corte: Corte / Final">
            <div class="cmp-tarjeta-titulo"><?= $nombreEjecucion ?> a corte</div>
            <div class="cmp-tarjeta-valor"><?= $porcentaje($total['ejecucion']) ?></div>
            <div class="cmp-tarjeta-detalle">Saldo por <?= $verboEjecutar ?>: <?= $moneda($total['saldo']) ?></div>
        </div>
        <div class="cmp-tarjeta" title="<?= $nombreEjecucion ?> real menos el % del año transcurrido a la fecha de corte (<?= htmlspecialchars(date('d/m/Y', strtotime($cmp['fechaCorte']))) ?>)">
            <div class="cmp-tarjeta-titulo">Ritmo vs tiempo</div>
            <div class="cmp-tarjeta-valor <?= $claseSigno($total['ritmo']) ?>"><?= $total['ritmo'] === null ? 'Sin dato' : ($total['ritmo'] > 0 ? '+' : '') . number_format($total['ritmo'], 1, ',', '.') . ' pp' ?></div>
            <div class="cmp-tarjeta-detalle"><?= number_format($porcentajeTiempo, 1, ',', '.') ?>% del año <?= $anioBase ?> transcurrido</div>
        </div>
    </div>

    <?php if (empty($secciones)): ?>
    <p class="texto-atenuado">No hay datos para mostrar.</p>
    <?php else: ?>

    <?php // ---- 1. Barras por sección ---- ?>
    <div class="cmp-bloque">
        <h3>Valores por sección
            <span class="cmp-leyenda">
                <?php foreach ($series as $serie): ?><span class="<?= $serie['clase'] ?>"><?= htmlspecialchars($serie['etiqueta']) ?></span><?php endforeach; ?>
            </span>
        </h3>
        <?php foreach ($secciones as $indice => $seccion): ?>
        <div class="cmp-fila">
            <div class="cmp-nombre" title="<?= htmlspecialchars($nombreSeccion($seccion)) ?>"><span class="cmp-punto" style="background: <?= htmlspecialchars(colorGrupoArbol((int) $indice)) ?>;"></span><span><?= htmlspecialchars($nombreSeccion($seccion)) ?></span></div>
            <div class="cmp-barras">
                <?php foreach ($series as $clave => $serie): ?>
                <div class="cmp-barra <?= $serie['clase'] ?>" title="<?= htmlspecialchars($serie['etiqueta'] . ': ' . $moneda((float) $seccion[$clave])) ?>">
                    <i style="width: <?= number_format(abs((float) $seccion[$clave]) / $maximoBarras * 80, 2, '.', '') ?>%;"></i><?= $moneda((float) $seccion[$clave]) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php // ---- 2. Variación % por sección (base elegible) ---- ?>
    <div class="cmp-bloque" id="cmp-variacion">
        <h3>Variación % de <?= $anioProy ?> por sección
            <span class="cmp-bases" role="group" aria-label="Base de comparación">
                <?php foreach ($bases as $base => $nombreBase): ?>
                <button type="button" data-base="<?= $base ?>" class="<?= $base === 'final' ? 'activo' : '' ?>"><?= htmlspecialchars($base === 'cierre' ? 'Cierre estimado' : ucfirst($base)) ?></button>
                <?php endforeach; ?>
            </span>
        </h3>
        <?php foreach ($bases as $base => $nombreBase): ?>
        <div class="cmp-variacion-base" data-base="<?= $base ?>"<?= $base === 'final' ? '' : ' hidden' ?>>
            <?php foreach ($secciones as $indice => $seccion): ?>
            <?php
            $pct = $seccion['variacion'][$base]['porcentaje'];
            $ancho = $pct === null ? 0 : min(50, abs($pct) / $maximoVariacion[$base] * 50);
            $color = $pct === null ? 'transparent' : ($pct >= 0 ? '#1e6b3c' : '#a83232');
            $posicion = $pct !== null && $pct < 0 ? 'right: 50%;' : 'left: 50%;';
            $posicionTexto = $pct !== null && $pct < 0 ? 'right: calc(50% + ' . number_format($ancho, 2, '.', '') . '% + 4px);' : 'left: calc(50% + ' . number_format($ancho, 2, '.', '') . '% + 4px);';
            ?>
            <div class="cmp-fila">
                <div class="cmp-nombre" title="<?= htmlspecialchars($nombreSeccion($seccion)) ?>"><span class="cmp-punto" style="background: <?= htmlspecialchars(colorGrupoArbol((int) $indice)) ?>;"></span><span><?= htmlspecialchars($nombreSeccion($seccion)) ?></span></div>
                <div class="cmp-divergente" title="<?= htmlspecialchars($anioProy . ' vs ' . $nombreBase . ': ' . $porcentaje($pct, true) . ' (' . $monedaConSigno($seccion['variacion'][$base]['valor']) . ')') ?>">
                    <i style="<?= $posicion ?> width: <?= number_format($ancho, 2, '.', '') ?>%; background: <?= $color ?>;"></i>
                    <b class="<?= $claseSigno($pct) ?>" style="<?= $posicionTexto ?>"><?= $porcentaje($pct, true) ?></b>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php // ---- 3. Ejecución vs tiempo ---- ?>
    <div class="cmp-bloque">
        <h3><?= $nombreEjecucion ?> <?= $anioBase ?> vs tiempo transcurrido
            <span class="cmp-leyenda"><span style="--cmp-color: var(--color-texto);">Marca: <?= number_format($porcentajeTiempo, 1, ',', '.') ?>% del año al <?= htmlspecialchars(date('d/m/Y', strtotime($cmp['fechaCorte']))) ?></span></span>
        </h3>
        <?php foreach ($secciones as $indice => $seccion): ?>
        <?php
        $ejecucion = $seccion['ejecucion'];
        $ritmo = $seccion['ritmo'];
        // ±5 pp alrededor del tiempo transcurrido = al ritmo; por debajo, atrasada; por encima, adelantada.
        $colorEjecucion = $ritmo === null ? 'var(--color-borde-hover)' : ($ritmo < -5 ? '#a83232' : ($ritmo > 5 ? '#1e6b3c' : 'var(--color-primario)'));
        $estadoRitmo = $ritmo === null ? '' : ($ritmo < -5 ? 'atrasada' : ($ritmo > 5 ? 'adelantada' : 'al ritmo'));
        ?>
        <div class="cmp-fila">
            <div class="cmp-nombre" title="<?= htmlspecialchars($nombreSeccion($seccion)) ?>"><span class="cmp-punto" style="background: <?= htmlspecialchars(colorGrupoArbol((int) $indice)) ?>;"></span><span><?= htmlspecialchars($nombreSeccion($seccion)) ?></span></div>
            <div>
                <div class="cmp-ejecucion" title="<?= htmlspecialchars($nombreEjecucion . ': ' . $porcentaje($ejecucion) . ' (' . $moneda($seccion['corte']) . ' de ' . $moneda($seccion['final']) . ')') ?>">
                    <i style="width: <?= number_format(min(100, (float) ($ejecucion ?? 0)), 2, '.', '') ?>%; background: <?= $colorEjecucion ?>;"></i>
                    <em style="left: <?= number_format(min(100, $porcentajeTiempo), 2, '.', '') ?>%;"></em>
                </div>
                <div class="cmp-ejecucion-dato"><?= $porcentaje($ejecucion) ?><?= $ritmo !== null ? ' · ' . ($ritmo > 0 ? '+' : '') . number_format($ritmo, 1, ',', '.') . ' pp · ' . $estadoRitmo : '' ?> · saldo <?= $moneda($seccion['saldo']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php // ---- 4. Distribución comparada ---- ?>
    <div class="cmp-bloque">
        <h3>Distribución: <?= $anioBase ?> Final vs <?= $anioProy ?></h3>
        <?php foreach (['participacionFinal' => $anioBase . ' Final', 'participacionProyeccion' => (string) $anioProy] as $claveParticipacion => $nombreBarra): ?>
        <div class="cmp-fila">
            <div class="cmp-nombre"><span><?= htmlspecialchars($nombreBarra) ?></span></div>
            <div class="cmp-apilada">
                <?php foreach ($secciones as $indice => $seccion): ?>
                <?php $participacion = (float) ($seccion[$claveParticipacion] ?? 0.0); ?>
                <?php if ($participacion > 0): ?>
                <span style="flex: <?= number_format($participacion, 4, '.', '') ?> 1 0; background: <?= htmlspecialchars(colorGrupoArbol((int) $indice)) ?>;" title="<?= htmlspecialchars($nombreSeccion($seccion) . ': ' . $porcentaje($participacion)) ?>"></span>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <table class="cmp-tabla">
            <thead>
                <tr><th>Sección</th><th><?= $anioBase ?> Final</th><th><?= $anioProy ?></th><th title="Puntos porcentuales">Cambio</th></tr>
            </thead>
            <tbody>
                <?php foreach ($secciones as $indice => $seccion): ?>
                <tr>
                    <td><span class="cmp-punto" style="display: inline-block; background: <?= htmlspecialchars(colorGrupoArbol((int) $indice)) ?>;"></span> <?= htmlspecialchars($nombreSeccion($seccion)) ?></td>
                    <td><?= $porcentaje($seccion['participacionFinal']) ?></td>
                    <td><?= $porcentaje($seccion['participacionProyeccion']) ?></td>
                    <td class="<?= $claseSigno($seccion['cambioParticipacion']) ?>"><?= $seccion['cambioParticipacion'] === null ? 'Sin dato' : ($seccion['cambioParticipacion'] > 0 ? '+' : '') . number_format($seccion['cambioParticipacion'], 1, ',', '.') . ' pp' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
// Base de la gráfica de variación (Inicial | Final | Corte | Cierre estimado), recordada por lado.
document.addEventListener('DOMContentLoaded', function () {
    var bloque = document.getElementById('cmp-variacion');
    if (!bloque) { return; }
    var clave = 'analisis_comparativo_base_' + <?= json_encode($lado) ?>;
    var botones = bloque.querySelectorAll('.cmp-bases button');
    var paneles = bloque.querySelectorAll('.cmp-variacion-base');

    function mostrar(base) {
        Array.prototype.forEach.call(botones, function (boton) { boton.classList.toggle('activo', boton.dataset.base === base); });
        Array.prototype.forEach.call(paneles, function (panel) { panel.hidden = panel.dataset.base !== base; });
        try { localStorage.setItem(clave, base); } catch (e) { /* sin localStorage */ }
    }

    Array.prototype.forEach.call(botones, function (boton) {
        boton.addEventListener('click', function () { mostrar(boton.dataset.base); });
    });

    var guardada = null;
    try { guardada = localStorage.getItem(clave); } catch (e) { guardada = null; }
    if (guardada && bloque.querySelector('.cmp-variacion-base[data-base="' + guardada + '"]')) {
        mostrar(guardada);
    }
});
</script>
