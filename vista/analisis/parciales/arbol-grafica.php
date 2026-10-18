<?php
/**
 * Vista "Gráfica" de Programación presupuestal (Ingresos/Egresos): UNA sola gráfica de barras con
 * todos los niveles del árbol (grupos, subgrupos y líneas), en el mismo orden y con la misma
 * lógica que arbol.php — cada fila muestra el valor que ese nodo ya tiene en el árbol (un total es
 * la suma de sus hijas, calculada por PresupuestoInstitucional), nunca se vuelve a sumar, así que
 * no hay doble conteo. El total de arriba es solo la suma de las raíces, igual que la fila "Total"
 * del árbol.
 *
 * Armonizada con el árbol: mismo botón de despliegue (.arbol-boton-expandir), misma sangría y
 * tamaño de control (--arbol-control / --arbol-sangria-paso), mismo marcador por nivel y color de
 * grupo (marcadorNivelArbol() / colorGrupoArbol(), definidos en arbol.php) y mismo estado inicial
 * desplegado/recogido ($arbolExpandidoPorDefecto). Las barras usan las clases visuales de las
 * gráficas de tabla-real (grafica-barra-pista/relleno/valor, grafica-stat).
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

// Filas en orden de árbol (profundidad primero).
$filasGrafica = [];
$lineasGrafica = 0;
$recorrerGrafica = function (array $nodos, ?string $padre, ?string $color) use (&$recorrerGrafica, &$filasGrafica, &$lineasGrafica): void {
    foreach ($nodos as $indice => $nodo) {
        $colorNodo = $color ?? colorGrupoArbol((int) $indice);
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
            'tieneHijos' => $tieneHijos,
            'esTotal' => !empty($nodo['esTotal']),
        ];
        if ($tieneHijos) {
            $recorrerGrafica($nodo['hijos'], (string) $nodo['id'], $colorNodo);
        }
    }
};
$recorrerGrafica($arbolDatos, null, null);
?>
<div class="arbol-grafica-contenedor" id="arbol-grafica-contenedor" hidden>
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

    <div class="arbol-grafica-cuerpo">
        <div class="arbol-grafica-encabezado" aria-hidden="true">
            <span>Descripción</span>
            <span><?= htmlspecialchars($etiquetaAnioGrafica) ?></span>
            <span>Valor (% del total)</span>
        </div>
        <div class="arbol-grafica-filas" id="arbol-grafica-filas">
            <?php foreach ($filasGrafica as $fila): ?>
            <?php
            $porcentajeFila = $totalGrafica != 0.0 ? $fila['valor'] / $totalGrafica * 100 : 0.0;
            $anchoFila = max(0.5, min(100, abs($fila['valor']) / $maximoGrafica * 100));
            $clasesFila = 'arbol-fila arbol-grafica-fila nivel-' . $fila['nivel']
                . ($fila['esTotal'] ? ' arbol-fila-total' : '')
                . ($fila['tieneHijos'] ? ' arbol-fila-expandible' : '')
                . (!$expandidoGrafica && $fila['tieneHijos'] ? ' arbol-contraido' : '')
                . (!$expandidoGrafica && $fila['padre'] !== null ? ' arbol-fila-oculta' : '');
            ?>
            <div class="<?= $clasesFila ?>" data-id="<?= htmlspecialchars($fila['id']) ?>"<?= $fila['padre'] !== null ? ' data-padre="' . htmlspecialchars($fila['padre']) . '"' : '' ?> data-expandido="<?= $expandidoGrafica ? '1' : '0' ?>"
                 title="<?= htmlspecialchars($fila['etiqueta'] . ': $' . number_format($fila['valor'], 2, ',', '.') . ' (' . number_format($porcentajeFila, 1, ',', '.') . '% del total)') ?>">
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
                    <span class="arbol-grafica-texto"><?= htmlspecialchars($fila['etiqueta']) ?></span>
                </div>
                <div class="grafica-barra-pista">
                    <div class="grafica-barra-relleno" style="width: <?= number_format($anchoFila, 2, '.', '') ?>%; background: <?= htmlspecialchars($fila['color']) ?>;"></div>
                </div>
                <span class="grafica-barra-valor">$<?= number_format($fila['valor'], 2, ',', '.') ?> <small>(<?= number_format($porcentajeFila, 1, ',', '.') ?>%)</small></span>
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

        .arbol-grafica-fila .grafica-barra-valor {
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .arbol-grafica-fila .grafica-barra-valor small {
            color: var(--color-texto-tenue);
            font-weight: 400;
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

            .arbol-grafica-fila {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 0.25rem 0.6rem;
                padding: 0.35rem 0.5rem;
            }

            .arbol-grafica-fila .arbol-grafica-etiqueta {
                grid-column: 1 / -1;
                font-size: 0.85rem;
            }

            .arbol-grafica-fila .grafica-barra-pista {
                margin-left: calc(var(--arbol-control) + 14px);
            }

            .arbol-grafica-fila .grafica-barra-valor {
                font-size: 0.8rem;
            }
        }
    </style>

    <script>
    // Plegar/desplegar en la gráfica con la misma lógica del árbol (data-expandido,
    // .arbol-contraido, .arbol-fila-oculta): clic en el botón o en toda la descripción.
    (function () {
        var filas = Array.prototype.slice.call(document.querySelectorAll('#arbol-grafica-filas .arbol-grafica-fila'));
        var porId = {};
        filas.forEach(function (fila) { porId[fila.dataset.id] = fila; });

        function estaVisible(fila) {
            var padre = fila.dataset.padre ? porId[fila.dataset.padre] : null;
            if (!padre) { return true; }
            return padre.dataset.expandido !== '0' && estaVisible(padre);
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
        }

        filas.filter(function (fila) { return fila.classList.contains('arbol-fila-expandible'); }).forEach(function (fila) {
            fila.querySelector('.arbol-grafica-etiqueta').addEventListener('click', function () { alternar(fila); });
        });
    })();
    </script>
    <?php endif; ?>
</div>
