<?php
/**
 * Vista "Gráfica" de Programación presupuestal (Ingresos/Egresos), con tres modos (selector
 * Barras | Torta | Apilada, recordado por lado en localStorage):
 * - Barras: una barra por cada fila del árbol (secciones, subsecciones y líneas), en el mismo
 *   orden; el porcentaje sobre el total va en la punta de cada barra.
 * - Torta y Apilada: dibujan la "frontera" del árbol — las filas visibles que no están
 *   desplegadas. Al principio (recogido) son las secciones; al desplegar una, su porción se parte
 *   en sus hijas con tonos de su mismo color, y al recogerla vuelve a ser una sola. Como la
 *   frontera nunca incluye a la vez una fila y sus hijas, no hay doble conteo y siempre suma el
 *   total. La lista de filas de abajo sirve de leyenda (muestra de color en las filas dibujadas).
 * Cada fila muestra el valor que ese nodo ya tiene en el árbol (un total es la suma de sus hijas,
 * calculada por PresupuestoInstitucional); el total de arriba es solo la suma de las raíces, igual
 * que la fila "Total" del árbol.
 *
 * Armonizada con el árbol: mismo botón de despliegue (.arbol-boton-expandir), misma sangría y
 * tamaño de control (--arbol-control / --arbol-sangria-paso), mismo marcador por nivel y color de
 * sección (marcadorNivelArbol() / colorGrupoArbol(), definidos en arbol.php) y mismo estado
 * inicial desplegado/recogido ($arbolExpandidoPorDefecto).
 *
 * Variables esperadas (las mismas de arbol.php): $arbolDatos, $columnasAnios, $lado,
 * $arbolExpandidoPorDefecto.
 */

$etiquetaAnioGrafica = (string) ($columnasAnios[0]['etiqueta'] ?? '');
$expandidoGrafica = $arbolExpandidoPorDefecto ?? true;

$totalGrafica = 0.0;
$maximoGrafica = 0.0;
foreach ($arbolDatos as $raizGrafica) {
    $valorRaiz = (float) ($raizGrafica['valores']['vigente'] ?? 0.0);
    $totalGrafica += $valorRaiz;
    // Escala común: la raíz más grande — una hija siempre se ve más corta que su padre.
    $maximoGrafica = max($maximoGrafica, abs($valorRaiz));
}
$maximoGrafica = $maximoGrafica > 0 ? $maximoGrafica : 1.0;

/**
 * Tono de una hija a partir del color de su padre, para que en Torta/Apilada las subsecciones de
 * una misma sección se distingan sin salirse de su color: alterna más claro / más oscuro y se
 * aleja un poco más del color base a medida que avanza entre sus hermanas.
 */
$tonoHijaGrafica = static function (string $hexPadre, int $indice, int $hermanas): string {
    $rgb = sscanf(ltrim($hexPadre, '#'), '%02x%02x%02x');
    $avance = $hermanas > 1 ? $indice / ($hermanas - 1) : 0.0;
    [$destino, $proporcion] = $indice % 2 === 0 ? [255, 0.18 + 0.45 * $avance] : [0, 0.12 + 0.28 * $avance];
    $mezcla = array_map(static fn (int $canal): int => (int) round($canal + ($destino - $canal) * $proporcion), $rgb);

    return sprintf('#%02x%02x%02x', ...$mezcla);
};

// Filas en orden de árbol (profundidad primero).
$filasGrafica = [];
$lineasGrafica = 0;
$recorrerGrafica = function (array $nodos, ?string $padre, ?string $colorSeccion, ?string $colorPadre, ?float $valorSeccion) use (&$recorrerGrafica, &$filasGrafica, &$lineasGrafica, $tonoHijaGrafica): void {
    $hermanas = count($nodos);
    foreach (array_values($nodos) as $indice => $nodo) {
        $colorNodo = $colorSeccion ?? colorGrupoArbol($indice);
        $colorSegmento = $colorPadre === null ? $colorNodo : $tonoHijaGrafica($colorPadre, $indice, $hermanas);
        $valorSeccionNodo = $valorSeccion ?? (float) ($nodo['valores']['vigente'] ?? 0.0);
        $tieneHijos = !empty($nodo['hijos']);
        if (!$tieneHijos && empty($nodo['esTotal'])) {
            $lineasGrafica++;
        }
        $filasGrafica[] = [
            'id' => (string) $nodo['id'],
            'padre' => $padre,
            'nivel' => (int) $nodo['nivel'],
            'etiqueta' => trim(($nodo['codigo'] ?? '') . ' - ' . $nodo['etiqueta'], ' -'),
            'valor' => (float) ($nodo['valores']['vigente'] ?? 0.0),
            'color' => $colorNodo,
            'colorSegmento' => $colorSegmento,
            'valorSeccion' => $valorSeccionNodo,
            'tieneHijos' => $tieneHijos,
            'esTotal' => !empty($nodo['esTotal']),
        ];
        if ($tieneHijos) {
            $recorrerGrafica($nodo['hijos'], (string) $nodo['id'], $colorNodo, $colorSegmento, $valorSeccionNodo);
        }
    }
};
$recorrerGrafica($arbolDatos, null, null, null, null);

$formatoPorcentajeGrafica = static fn (float $porcentaje): string => number_format($porcentaje, 1, ',', '.') . "\u{00A0}%";
?>
<div class="arbol-grafica-contenedor modo-barras" id="arbol-grafica-contenedor" data-lado="<?= htmlspecialchars($lado) ?>" data-clave-porcentaje="<?= htmlspecialchars('analisis_arbol_porcentaje_modo_' . ($pestanaArbol ?? 'programacion_presupuestal')) ?>" hidden>
    <?php if (empty($filasGrafica)): ?>
    <p class="texto-atenuado arbol-celda-vacia">No hay datos para graficar.</p>
    <?php else: ?>
    <div class="arbol-grafica-kpis">
        <div class="grafica-stat">
            <p class="grafica-stat-etiqueta">Total <?= htmlspecialchars($etiquetaAnioGrafica) ?></p>
            <p class="grafica-stat-valor">$<?= number_format($totalGrafica, 2, ',', '.') ?></p>
        </div>
        <div class="grafica-stat">
            <p class="grafica-stat-etiqueta">Secciones</p>
            <p class="grafica-stat-valor"><?= count($arbolDatos) ?></p>
        </div>
        <div class="grafica-stat">
            <p class="grafica-stat-etiqueta">Líneas</p>
            <p class="grafica-stat-valor"><?= $lineasGrafica ?></p>
        </div>
    </div>

    <div class="arbol-grafica-modos">
        <div class="arbol-lado-toggle" role="group" aria-label="Tipo de gráfica">
            <button type="button" class="arbol-boton-toggle activo" data-modo-grafica="barras" aria-pressed="true">Barras</button>
            <button type="button" class="arbol-boton-toggle" data-modo-grafica="torta" aria-pressed="false">Torta</button>
            <button type="button" class="arbol-boton-toggle" data-modo-grafica="apilada" aria-pressed="false">Apilada</button>
        </div>
        <div class="arbol-lado-toggle" role="group" aria-label="Base del porcentaje">
            <button type="button" class="arbol-boton-toggle activo" data-modo-porcentaje="total" aria-pressed="true" title="Porcentaje sobre el total general">% total</button>
            <button type="button" class="arbol-boton-toggle" data-modo-porcentaje="grupo" aria-pressed="false" title="Porcentaje sobre el total de su sección">% sección</button>
        </div>
        <span class="arbol-grafica-ayuda texto-atenuado">Despliega las filas de la lista para ver sus partes en la gráfica.</span>
    </div>

    <div class="arbol-grafica-figura" id="arbol-grafica-figura">
        <div class="arbol-grafica-torta" id="arbol-grafica-torta"></div>
        <div class="arbol-grafica-apilada" id="arbol-grafica-apilada"></div>
    </div>

    <div class="arbol-grafica-cuerpo">
        <div class="arbol-grafica-encabezado" aria-hidden="true">
            <span>Descripción</span>
            <span class="arbol-grafica-encabezado-barra"><?= htmlspecialchars($etiquetaAnioGrafica) ?> (<span class="arbol-grafica-base-pct">% del total</span>)</span>
            <span>Valor</span>
        </div>
        <div class="arbol-grafica-filas" id="arbol-grafica-filas">
            <?php foreach ($filasGrafica as $fila): ?>
            <?php
            $porcentajeFila = $totalGrafica != 0.0 ? $fila['valor'] / $totalGrafica * 100 : 0.0;
            // 85 % como máximo: siempre queda espacio para el porcentaje en la punta de la barra.
            $anchoFila = max(0.5, min(100, abs($fila['valor']) / $maximoGrafica * 100)) * 0.85;
            $clasesFila = 'arbol-fila arbol-grafica-fila nivel-' . $fila['nivel']
                . ($fila['esTotal'] ? ' arbol-fila-total' : '')
                . ($fila['tieneHijos'] ? ' arbol-fila-expandible' : '')
                . (!$expandidoGrafica && $fila['tieneHijos'] ? ' arbol-contraido' : '')
                . (!$expandidoGrafica && $fila['padre'] !== null ? ' arbol-fila-oculta' : '');
            ?>
            <div class="<?= $clasesFila ?>" data-id="<?= htmlspecialchars($fila['id']) ?>"<?= $fila['padre'] !== null ? ' data-padre="' . htmlspecialchars($fila['padre']) . '"' : '' ?> data-expandido="<?= $expandidoGrafica ? '1' : '0' ?>"
                 data-valor="<?= number_format($fila['valor'], 2, '.', '') ?>" data-etiqueta="<?= htmlspecialchars($fila['etiqueta']) ?>" data-color-segmento="<?= htmlspecialchars($fila['colorSegmento']) ?>" data-valor-seccion="<?= number_format($fila['valorSeccion'], 2, '.', '') ?>"
                 title="<?= htmlspecialchars($fila['etiqueta'] . ': $' . number_format($fila['valor'], 2, ',', '.') . ' (' . $formatoPorcentajeGrafica($porcentajeFila) . ' del total)') ?>">
                <div class="arbol-etiqueta-contenido arbol-grafica-etiqueta">
                    <span class="arbol-sangria" style="width: calc(<?= $fila['nivel'] ?> * var(--arbol-sangria-paso));"></span>
                    <?php if ($fila['tieneHijos']): ?>
                    <button type="button" class="arbol-boton-expandir" title="Contraer/expandir" aria-label="Contraer/expandir" aria-expanded="<?= $expandidoGrafica ? 'true' : 'false' ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <?php else: ?>
                    <span class="arbol-espaciador" aria-hidden="true"></span>
                    <?php endif; ?>
                    <?= marcadorNivelArbol($fila['nivel'], $fila['color']) ?>
                    <span class="arbol-grafica-muestra" style="background: <?= htmlspecialchars($fila['colorSegmento']) ?>;" aria-hidden="true"></span>
                    <span class="arbol-grafica-texto"><?= htmlspecialchars($fila['etiqueta']) ?></span>
                </div>
                <div class="grafica-barra-pista arbol-grafica-pista">
                    <div class="grafica-barra-relleno" style="width: <?= number_format($anchoFila, 2, '.', '') ?>%; background: <?= htmlspecialchars($fila['color']) ?>;"></div>
                    <span class="arbol-grafica-pct"><?= $formatoPorcentajeGrafica($porcentajeFila) ?></span>
                </div>
                <span class="grafica-barra-valor">
                    $<?= number_format($fila['valor'], 2, ',', '.') ?>
                    <small class="arbol-grafica-pct-frontera"></small>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
        .arbol-grafica-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
            margin-bottom: 0.6rem;
            flex-shrink: 0;
        }

        .arbol-grafica-modos {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 0.6rem;
            flex-shrink: 0;
        }

        .arbol-grafica-ayuda {
            font-size: 0.8rem;
        }

        .modo-barras .arbol-grafica-ayuda,
        .modo-barras .arbol-grafica-figura,
        .modo-torta .arbol-grafica-apilada,
        .modo-apilada .arbol-grafica-torta {
            display: none;
        }

        .arbol-grafica-figura {
            flex-shrink: 0;
            margin-bottom: 0.6rem;
        }

        /* Torta: SVG centrado (el detalle va en la lista de abajo, que sirve de leyenda). */
        .arbol-grafica-torta {
            display: flex;
            justify-content: center;
        }

        .arbol-grafica-torta svg {
            width: min(340px, 80vw);
            height: auto;
        }

        .arbol-grafica-torta path,
        .arbol-grafica-torta circle {
            stroke: var(--color-superficie);
            stroke-width: 1.5;
            cursor: pointer;
            transition: opacity var(--transicion);
        }

        .arbol-grafica-torta-pct {
            font-size: 11px;
            font-weight: 700;
            fill: #fff;
            paint-order: stroke;
            stroke: rgba(0, 0, 0, 0.35);
            stroke-width: 2px;
        }

        .arbol-grafica-torta-pct.externo {
            font-size: 10px;
            fill: var(--color-texto);
            stroke: none;
        }

        .arbol-grafica-torta-guia {
            stroke: var(--color-texto-tenue);
            stroke-width: 1;
        }

        .arbol-grafica-torta svg:hover path:not(:hover),
        .arbol-grafica-torta svg:hover circle:not(:hover) {
            opacity: 0.6;
        }

        /* Apilada: una barra al 100 % partida en las porciones de la frontera del árbol. */
        .arbol-grafica-apilada {
            display: flex;
            height: 48px;
            border-radius: var(--radio-chico);
            overflow: hidden;
            background: var(--color-fondo);
        }

        .arbol-grafica-segmento {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 2px;
            height: 100%;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            border-right: 1px solid var(--color-superficie);
            cursor: pointer;
            transition: flex-basis 0.35s ease, filter var(--transicion);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
        }

        .arbol-grafica-segmento:hover {
            filter: brightness(0.9);
        }

        .arbol-grafica-cuerpo {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }

        /* Misma rejilla en encabezado y filas: descripción | barra | valor. */
        .arbol-grafica-encabezado,
        .arbol-grafica-fila {
            display: grid;
            grid-template-columns: minmax(220px, 38%) minmax(0, 1fr) minmax(170px, auto);
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 0.85rem;
        }

        /* Torta/Apilada: sin la columna de barras, la lista queda como leyenda (descripción | valor). */
        .modo-torta .arbol-grafica-encabezado,
        .modo-torta .arbol-grafica-fila,
        .modo-apilada .arbol-grafica-encabezado,
        .modo-apilada .arbol-grafica-fila {
            grid-template-columns: minmax(0, 1fr) minmax(170px, auto);
        }

        .modo-torta .arbol-grafica-pista,
        .modo-apilada .arbol-grafica-pista,
        .modo-torta .arbol-grafica-encabezado-barra,
        .modo-apilada .arbol-grafica-encabezado-barra {
            display: none;
        }

        .arbol-grafica-encabezado {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--color-superficie);
            border-bottom: 1px solid var(--color-borde);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--color-texto-secundario);
        }

        .arbol-grafica-encabezado span:last-child,
        .arbol-grafica-fila .grafica-barra-valor {
            text-align: right;
        }

        /* Filas con el mismo trato que las del árbol: borde inferior, nivel 0 / totales en negrita
           con fondo, nivel 1 seminegrita (clases .arbol-fila / .nivel-N / .arbol-fila-total). */
        .arbol-grafica-fila {
            border-bottom: 1px solid var(--color-borde);
            font-variant-numeric: tabular-nums;
        }

        /* Debe ganarle al display:grid de arriba (misma especificidad que .arbol-fila-oculta del árbol). */
        .arbol-grafica-fila.arbol-fila-oculta {
            display: none;
        }

        .arbol-grafica-fila.resaltada {
            background: var(--color-primario-suave);
        }

        /* Barra + porcentaje pegado a su punta. */
        .arbol-grafica-pista {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: transparent;
            overflow: visible;
        }

        .arbol-grafica-pct {
            flex-shrink: 0;
            font-size: 0.78rem;
            color: var(--color-texto-secundario);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .arbol-grafica-fila .grafica-barra-valor {
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .arbol-grafica-fila .grafica-barra-valor small {
            color: var(--color-texto-tenue);
            font-weight: 400;
        }

        /* Muestra de color: solo en Torta/Apilada y solo en las filas que se están dibujando. */
        .arbol-grafica-muestra {
            display: none;
            flex-shrink: 0;
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .modo-torta .arbol-grafica-fila.en-frontera .arbol-grafica-muestra,
        .modo-apilada .arbol-grafica-fila.en-frontera .arbol-grafica-muestra {
            display: inline-block;
        }

        .arbol-grafica-pct-frontera:empty {
            display: none;
        }

        .modo-barras .arbol-grafica-pct-frontera {
            display: none;
        }

        .arbol-grafica-fila.arbol-fila-expandible .arbol-grafica-etiqueta {
            cursor: pointer;
            user-select: none;
        }

        .arbol-grafica-fila.arbol-fila-expandible .arbol-grafica-etiqueta:hover .arbol-boton-expandir {
            background: rgba(0, 0, 0, 0.08);
        }

        .arbol-grafica-texto {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        /* Cada nivel más abajo, la barra un poco más tenue (la forma del marcador ya dice el nivel). */
        .arbol-grafica-fila.nivel-1 .grafica-barra-relleno { opacity: 0.85; }
        .arbol-grafica-fila.nivel-2 .grafica-barra-relleno { opacity: 0.7; }
        .arbol-grafica-fila.nivel-3 .grafica-barra-relleno,
        .arbol-grafica-fila.nivel-4 .grafica-barra-relleno { opacity: 0.55; }

        /* Móvil: la descripción arriba, a todo lo ancho; debajo la barra y el valor. */
        @media (max-width: 640px) {
            .arbol-grafica-kpis {
                grid-template-columns: 1fr 1fr;
            }

            .arbol-grafica-kpis .grafica-stat:first-child {
                grid-column: 1 / -1;
            }

            .arbol-grafica-encabezado {
                display: none;
            }

            .arbol-grafica-fila,
            .modo-torta .arbol-grafica-fila,
            .modo-apilada .arbol-grafica-fila {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 0.25rem 0.6rem;
                padding: 0.35rem 0.5rem;
            }

            .modo-barras .arbol-grafica-fila .arbol-grafica-etiqueta {
                grid-column: 1 / -1;
            }

            .arbol-grafica-fila .arbol-grafica-etiqueta {
                font-size: 0.85rem;
            }

            .arbol-grafica-fila .grafica-barra-pista {
                margin-left: calc(var(--arbol-control) + 14px);
            }

            .arbol-grafica-fila .grafica-barra-valor {
                font-size: 0.8rem;
            }

            .arbol-grafica-segmento {
                font-size: 0.68rem;
            }
        }
    </style>

    <script>
    // Plegar/desplegar con la misma lógica del árbol (data-expandido, .arbol-contraido,
    // .arbol-fila-oculta) + modos Barras/Torta/Apilada. Torta y Apilada dibujan la "frontera":
    // filas visibles que no están desplegadas (una fila desplegada se reemplaza por sus hijas).
    (function () {
        var contenedor = document.getElementById('arbol-grafica-contenedor');
        var filas = Array.prototype.slice.call(document.querySelectorAll('#arbol-grafica-filas .arbol-grafica-fila'));
        var torta = document.getElementById('arbol-grafica-torta');
        var apilada = document.getElementById('arbol-grafica-apilada');
        if (!contenedor || !filas.length) { return; }

        var claveModo = 'analisis_programacion_grafica_modo_' + (contenedor.dataset.lado || '');
        var modo = 'barras';
        // Base del porcentaje: misma preferencia (y misma clave) que el encabezado "%" del árbol.
        var clavePorcentaje = contenedor.dataset.clavePorcentaje || '';
        var modoPorcentaje = 'total';
        try { if (localStorage.getItem(clavePorcentaje) === 'grupo') { modoPorcentaje = 'grupo'; } } catch (error) { /* sin localStorage */ }
        var totalGeneral = filas.filter(function (fila) { return !fila.dataset.padre; })
            .reduce(function (suma, fila) { return suma + parseFloat(fila.dataset.valor); }, 0);
        var porId = {};
        filas.forEach(function (fila) { porId[fila.dataset.id] = fila; });

        function estaVisible(fila) {
            var padre = fila.dataset.padre ? porId[fila.dataset.padre] : null;
            if (!padre) { return true; }
            return padre.dataset.expandido !== '0' && estaVisible(padre);
        }

        function formatoPorcentaje(valor) {
            return valor.toLocaleString('es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' %';
        }

        function formatoMoneda(valor) {
            return '$' + valor.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // % de una fila sobre el total general o sobre el total de su sección, según el modo.
        function porcentajeDe(fila) {
            var base = modoPorcentaje === 'grupo' ? parseFloat(fila.dataset.valorSeccion) : totalGeneral;
            return base > 0 ? parseFloat(fila.dataset.valor) / base * 100 : 0;
        }

        function frontera() {
            return filas.filter(function (fila) {
                var desplegada = fila.classList.contains('arbol-fila-expandible') && fila.dataset.expandido !== '0';
                return estaVisible(fila) && !desplegada && parseFloat(fila.dataset.valor) > 0;
            });
        }

        function resaltar(fila, activo) {
            if (fila) { fila.classList.toggle('resaltada', activo); }
        }

        function enlazarSegmento(elemento, fila) {
            elemento.addEventListener('mouseenter', function () { resaltar(fila, true); });
            elemento.addEventListener('mouseleave', function () { resaltar(fila, false); });
            elemento.addEventListener('click', function () {
                // Clic en una porción con hijas: la despliega, igual que en la lista.
                if (fila.classList.contains('arbol-fila-expandible')) { alternar(fila); }
            });
        }

        function dibujarTorta(partes, total) {
            var ns = 'http://www.w3.org/2000/svg';
            var svg = document.createElementNS(ns, 'svg');
            // Margen alrededor del círculo para las etiquetas externas de las porciones pequeñas.
            svg.setAttribute('viewBox', '-40 -30 280 260');
            svg.setAttribute('role', 'img');
            svg.setAttribute('aria-label', 'Gráfica de torta');
            var angulo = -Math.PI / 2;
            var etiquetas = [];

            partes.forEach(function (parte) {
                var fraccion = parte.valor / total;
                var anguloInicio = angulo;
                var elemento;
                if (fraccion >= 0.9999) {
                    elemento = document.createElementNS(ns, 'circle');
                    elemento.setAttribute('cx', '100');
                    elemento.setAttribute('cy', '100');
                    elemento.setAttribute('r', '95');
                } else {
                    var fin = angulo + fraccion * 2 * Math.PI;
                    var x1 = 100 + 95 * Math.cos(angulo), y1 = 100 + 95 * Math.sin(angulo);
                    var x2 = 100 + 95 * Math.cos(fin), y2 = 100 + 95 * Math.sin(fin);
                    elemento = document.createElementNS(ns, 'path');
                    elemento.setAttribute('d', 'M100,100 L' + x1 + ',' + y1 + ' A95,95 0 ' + (fraccion > 0.5 ? 1 : 0) + ',1 ' + x2 + ',' + y2 + ' Z');
                    angulo = fin;
                }
                elemento.setAttribute('fill', parte.color);
                var titulo = document.createElementNS(ns, 'title');
                titulo.textContent = parte.etiqueta + ': ' + formatoMoneda(parte.valor) + ' (' + formatoPorcentaje(porcentajeDe(parte.fila)) + ')';
                elemento.appendChild(titulo);
                enlazarSegmento(elemento, parte.fila);
                svg.appendChild(elemento);

                // Porcentaje de la porción: adentro si cabe (>= 6 %), afuera con línea guía si no.
                var medio = fraccion >= 0.9999 ? -Math.PI / 2 : anguloInicio + fraccion * Math.PI;
                etiquetas.push({ medio: medio, adentro: fraccion >= 0.06, texto: formatoPorcentaje(porcentajeDe(parte.fila)), unica: fraccion >= 0.9999 });
            });

            // Las etiquetas van después de todas las porciones para quedar siempre encima.
            etiquetas.forEach(function (etiqueta) {
                var texto = document.createElementNS(ns, 'text');
                texto.setAttribute('text-anchor', 'middle');
                texto.setAttribute('dominant-baseline', 'central');
                texto.setAttribute('pointer-events', 'none');
                if (etiqueta.adentro) {
                    var radioTexto = etiqueta.unica ? 0 : 60;
                    texto.setAttribute('x', String(100 + radioTexto * Math.cos(etiqueta.medio)));
                    texto.setAttribute('y', String(100 + radioTexto * Math.sin(etiqueta.medio)));
                    texto.setAttribute('class', 'arbol-grafica-torta-pct');
                } else {
                    var cos = Math.cos(etiqueta.medio), sin = Math.sin(etiqueta.medio);
                    var guia = document.createElementNS(ns, 'line');
                    guia.setAttribute('x1', String(100 + 96 * cos));
                    guia.setAttribute('y1', String(100 + 96 * sin));
                    guia.setAttribute('x2', String(100 + 108 * cos));
                    guia.setAttribute('y2', String(100 + 108 * sin));
                    guia.setAttribute('class', 'arbol-grafica-torta-guia');
                    svg.appendChild(guia);
                    texto.setAttribute('x', String(100 + 122 * cos));
                    texto.setAttribute('y', String(100 + 118 * sin));
                    texto.setAttribute('class', 'arbol-grafica-torta-pct externo');
                }
                texto.textContent = etiqueta.texto;
                svg.appendChild(texto);
            });

            torta.innerHTML = '';
            torta.appendChild(svg);
        }

        function dibujarApilada(partes, total) {
            apilada.innerHTML = '';
            partes.forEach(function (parte) {
                var ancho = parte.valor / total * 100;
                var porcentaje = porcentajeDe(parte.fila);
                var segmento = document.createElement('div');
                segmento.className = 'arbol-grafica-segmento';
                segmento.style.flexBasis = ancho + '%';
                segmento.style.background = parte.color;
                segmento.title = parte.etiqueta + ': ' + formatoMoneda(parte.valor) + ' (' + formatoPorcentaje(porcentaje) + ')';
                if (ancho >= 6) { segmento.textContent = formatoPorcentaje(porcentaje); }
                enlazarSegmento(segmento, parte.fila);
                apilada.appendChild(segmento);
            });
        }

        function redibujar() {
            var enFrontera = modo === 'barras' ? [] : frontera();
            var total = enFrontera.reduce(function (suma, fila) { return suma + parseFloat(fila.dataset.valor); }, 0);

            filas.forEach(function (fila) {
                var esParte = enFrontera.indexOf(fila) !== -1;
                fila.classList.toggle('en-frontera', esParte);
                var celdaPct = fila.querySelector('.arbol-grafica-pct-frontera');
                if (celdaPct) {
                    celdaPct.textContent = esParte && total > 0 ? '(' + formatoPorcentaje(porcentajeDe(fila)) + ')' : '';
                }
                // Porcentaje en la punta de la barra (modo Barras), con la base elegida.
                var puntaPct = fila.querySelector('.arbol-grafica-pct');
                if (puntaPct) { puntaPct.textContent = formatoPorcentaje(porcentajeDe(fila)); }
            });
            var etiquetaBase = contenedor.querySelector('.arbol-grafica-base-pct');
            if (etiquetaBase) { etiquetaBase.textContent = modoPorcentaje === 'grupo' ? '% de su sección' : '% del total'; }

            if (modo === 'barras' || total <= 0) { return; }


            var partes = enFrontera.map(function (fila) {
                return { fila: fila, valor: parseFloat(fila.dataset.valor), color: fila.dataset.colorSegmento, etiqueta: fila.dataset.etiqueta };
            });
            if (modo === 'torta') {
                dibujarTorta(partes, total);
            } else {
                dibujarApilada(partes, total);
            }
        }

        function alternar(fila) {
            var expandido = fila.dataset.expandido !== '0';
            fila.dataset.expandido = expandido ? '0' : '1';
            fila.classList.toggle('arbol-contraido', expandido);
            var boton = fila.querySelector('.arbol-boton-expandir');
            if (boton) { boton.setAttribute('aria-expanded', expandido ? 'false' : 'true'); }
            filas.forEach(function (otra) {
                otra.classList.toggle('arbol-fila-oculta', !estaVisible(otra));
            });
            redibujar();
        }

        filas.filter(function (fila) { return fila.classList.contains('arbol-fila-expandible'); }).forEach(function (fila) {
            fila.querySelector('.arbol-grafica-etiqueta').addEventListener('click', function () { alternar(fila); });
        });

        var botonesModo = Array.prototype.slice.call(contenedor.querySelectorAll('[data-modo-grafica]'));

        function cambiarModo(nuevoModo) {
            modo = ['barras', 'torta', 'apilada'].indexOf(nuevoModo) !== -1 ? nuevoModo : 'barras';
            contenedor.classList.remove('modo-barras', 'modo-torta', 'modo-apilada');
            contenedor.classList.add('modo-' + modo);
            botonesModo.forEach(function (boton) {
                var activo = boton.dataset.modoGrafica === modo;
                boton.classList.toggle('activo', activo);
                boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
            });
            try { localStorage.setItem(claveModo, modo); } catch (error) { /* sin localStorage */ }
            redibujar();
        }

        botonesModo.forEach(function (boton) {
            boton.addEventListener('click', function () { cambiarModo(boton.dataset.modoGrafica); });
        });

        var botonesPorcentaje = Array.prototype.slice.call(contenedor.querySelectorAll('[data-modo-porcentaje]'));

        function cambiarPorcentaje(nuevo) {
            modoPorcentaje = nuevo === 'grupo' ? 'grupo' : 'total';
            botonesPorcentaje.forEach(function (boton) {
                var activo = boton.dataset.modoPorcentaje === modoPorcentaje;
                boton.classList.toggle('activo', activo);
                boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
            });
            try { localStorage.setItem(clavePorcentaje, modoPorcentaje); } catch (error) { /* sin localStorage */ }
            redibujar();
        }

        botonesPorcentaje.forEach(function (boton) {
            boton.addEventListener('click', function () { cambiarPorcentaje(boton.dataset.modoPorcentaje); });
        });
        cambiarPorcentaje(modoPorcentaje);

        var modoGuardado = 'barras';
        try { modoGuardado = localStorage.getItem(claveModo) || 'barras'; } catch (error) { modoGuardado = 'barras'; }
        cambiarModo(modoGuardado);
    })();
    </script>
    <?php endif; ?>
</div>
