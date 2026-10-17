<?php
/**
 * Vista "Gráfica" de Programación presupuestal (Ingresos/Egresos): UNA sola gráfica de barras con
 * todos los niveles del árbol (grupos, subgrupos y líneas), en el mismo orden y con la misma
 * lógica que arbol.php — cada fila muestra el valor que ese nodo ya tiene en el árbol (un total es
 * la suma de sus hijas, calculada por PresupuestoInstitucional), nunca se vuelve a sumar, así que
 * no hay doble conteo. El total de arriba es solo la suma de las raíces, igual que la fila "Total"
 * del árbol. Usa las mismas clases visuales de las gráficas de tabla-real (grafica-panel,
 * grafica-fila-barra, grafica-stat…) para verse igual que el resto de gráficas de Análisis.
 *
 * Variables esperadas (las mismas de arbol.php): $arbolDatos, $columnasAnios, $lado.
 */

$etiquetaAnioGrafica = (string) ($columnasAnios[0]['etiqueta'] ?? '');
$paletaGrafica = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

$totalGrafica = 0.0;
foreach ($arbolDatos as $raizGrafica) {
    $totalGrafica += (float) ($raizGrafica['valores']['vigente'] ?? 0.0);
}

// Escala común para todas las barras: la raíz más grande — así una hija siempre se ve más corta
// que su padre y las barras de distintos grupos son comparables entre sí.
$maximoGrafica = 0.0;
foreach ($arbolDatos as $raizGrafica) {
    $maximoGrafica = max($maximoGrafica, abs((float) ($raizGrafica['valores']['vigente'] ?? 0.0)));
}
$maximoGrafica = $maximoGrafica > 0 ? $maximoGrafica : 1.0;

// Filas en orden de árbol (profundidad primero): [id, padre, nivel, etiqueta, valor, color, tieneHijos].
$filasGrafica = [];
$lineasGrafica = 0;
$recorrerGrafica = function (array $nodos, ?string $padre, int $nivel, ?string $color) use (&$recorrerGrafica, &$filasGrafica, &$lineasGrafica, $paletaGrafica): void {
    foreach ($nodos as $indice => $nodo) {
        $colorNodo = $color ?? $paletaGrafica[$indice % count($paletaGrafica)];
        $tieneHijos = !empty($nodo['hijos']);
        if (!$tieneHijos && empty($nodo['esTotal'])) {
            $lineasGrafica++;
        }
        $filasGrafica[] = [
            'id' => (string) $nodo['id'],
            'padre' => $padre,
            'nivel' => $nivel,
            'etiqueta' => trim(($nodo['codigo'] ?? '') . ' - ' . $nodo['etiqueta'], ' -'),
            'valor' => (float) ($nodo['valores']['vigente'] ?? 0.0),
            'color' => $colorNodo,
            'tieneHijos' => $tieneHijos,
        ];
        if ($tieneHijos) {
            $recorrerGrafica($nodo['hijos'], (string) $nodo['id'], $nivel + 1, $colorNodo);
        }
    }
};
$recorrerGrafica($arbolDatos, null, 0, null);
?>
<div class="arbol-grafica-contenedor" id="arbol-grafica-contenedor" hidden>
    <?php if (empty($filasGrafica)): ?>
    <p class="texto-atenuado">No hay datos para graficar.</p>
    <?php else: ?>
    <div class="tabla-grafica-kpis arbol-grafica-kpis">
        <div class="grafica-stat">
            <p class="grafica-stat-etiqueta">Total <?= htmlspecialchars($etiquetaAnioGrafica) ?></p>
            <p class="grafica-stat-valor">$<?= number_format($totalGrafica, 2, ',', '.') ?></p>
        </div>
        <div class="grafica-stat">
            <p class="grafica-stat-etiqueta">Grupos</p>
            <p class="grafica-stat-valor"><?= count($arbolDatos) ?></p>
        </div>
        <div class="grafica-stat">
            <p class="grafica-stat-etiqueta">Líneas</p>
            <p class="grafica-stat-valor"><?= $lineasGrafica ?></p>
        </div>
    </div>

    <div class="grafica-panel arbol-grafica-panel">
        <div class="grafica-panel-cabecera">
            <div>
                <h3 class="grafica-panel-titulo">Programación <?= htmlspecialchars($etiquetaAnioGrafica) ?> — <?= $lado === 'egresos' ? 'Egresos' : 'Ingresos' ?></h3>
                <p class="grafica-panel-subtitulo">Cada barra es el valor de esa fila en el árbol (los totales ya incluyen a sus hijas; no se suman dos veces). Clic en un grupo para plegar o desplegar.</p>
            </div>
        </div>
        <div class="grafica-panel-cuerpo arbol-grafica-cuerpo">
            <div class="grafica-barras-filas arbol-grafica-filas">
                <?php foreach ($filasGrafica as $fila): ?>
                <?php
                $porcentajeFila = $totalGrafica != 0.0 ? $fila['valor'] / $totalGrafica * 100 : 0.0;
                $anchoFila = max(0.5, min(100, abs($fila['valor']) / $maximoGrafica * 100));
                ?>
                <div class="grafica-fila-barra arbol-grafica-fila nivel-<?= (int) $fila['nivel'] ?><?= $fila['tieneHijos'] ? ' con-hijos' : '' ?>"
                     data-id="<?= htmlspecialchars($fila['id']) ?>"<?= $fila['padre'] !== null ? ' data-padre="' . htmlspecialchars($fila['padre']) . '"' : '' ?>
                     title="<?= htmlspecialchars($fila['etiqueta'] . ': $' . number_format($fila['valor'], 2, ',', '.') . ' (' . number_format($porcentajeFila, 1, ',', '.') . '% del total)') ?>"
                     <?= $fila['tieneHijos'] ? 'tabindex="0" role="button" aria-expanded="true"' : '' ?>>
                    <span class="grafica-barra-etiqueta" style="padding-left: <?= (int) $fila['nivel'] * 18 ?>px;">
                        <?php if ($fila['tieneHijos']): ?><span class="arbol-grafica-flecha" aria-hidden="true">▾</span><?php endif; ?>
                        <?= htmlspecialchars($fila['etiqueta']) ?>
                    </span>
                    <div class="grafica-barra-pista">
                        <div class="grafica-barra-relleno" style="width: <?= number_format($anchoFila, 2, '.', '') ?>%; background: <?= htmlspecialchars($fila['color']) ?>;"></div>
                    </div>
                    <span class="grafica-barra-valor">$<?= number_format($fila['valor'], 2, ',', '.') ?> (<?= number_format($porcentajeFila, 1, ',', '.') ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <style>
        .arbol-grafica-kpis {
            margin-bottom: 0.6rem;
        }

        .arbol-grafica-panel {
            flex: 1;
            min-height: 0;
            cursor: default;
        }

        .arbol-grafica-cuerpo {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }

        .arbol-grafica-filas {
            --grafica-ancho-etiqueta-defecto: 380px;
        }

        .arbol-grafica-fila {
            cursor: default;
        }

        .arbol-grafica-fila.con-hijos {
            cursor: pointer;
        }

        /* Raíces en negrita y barras completas; cada nivel más abajo, un poco más tenue. */
        .arbol-grafica-fila.nivel-0 .grafica-barra-etiqueta,
        .arbol-grafica-fila.nivel-0 .grafica-barra-valor {
            font-weight: 700;
            color: var(--color-texto);
        }

        .arbol-grafica-fila.nivel-1 .grafica-barra-relleno { opacity: 0.85; }
        .arbol-grafica-fila.nivel-2 .grafica-barra-relleno { opacity: 0.7; }
        .arbol-grafica-fila.nivel-3 .grafica-barra-relleno,
        .arbol-grafica-fila.nivel-4 .grafica-barra-relleno { opacity: 0.55; }

        .arbol-grafica-flecha {
            display: inline-block;
            width: 1em;
            transition: transform var(--transicion);
        }

        .arbol-grafica-fila.plegado .arbol-grafica-flecha {
            transform: rotate(-90deg);
        }

        .arbol-grafica-fila.oculta-por-padre {
            display: none;
        }
    </style>

    <script>
    // Plegar/desplegar en la gráfica, como en el árbol: ocultar un nodo oculta a todos sus
    // descendientes; al desplegar, solo reaparecen los que no tienen un ancestro plegado.
    (function () {
        var filas = Array.prototype.slice.call(document.querySelectorAll('.arbol-grafica-fila'));
        var porId = {};
        filas.forEach(function (fila) { porId[fila.dataset.id] = fila; });

        function tieneAncestroPlegado(fila) {
            var padre = fila.dataset.padre ? porId[fila.dataset.padre] : null;
            while (padre) {
                if (padre.classList.contains('plegado')) { return true; }
                padre = padre.dataset.padre ? porId[padre.dataset.padre] : null;
            }
            return false;
        }

        function alternar(fila) {
            var plegar = !fila.classList.contains('plegado');
            fila.classList.toggle('plegado', plegar);
            fila.setAttribute('aria-expanded', plegar ? 'false' : 'true');
            filas.forEach(function (otra) {
                otra.classList.toggle('oculta-por-padre', tieneAncestroPlegado(otra));
            });
        }

        filas.filter(function (fila) { return fila.classList.contains('con-hijos'); }).forEach(function (fila) {
            fila.addEventListener('click', function () { alternar(fila); });
            fila.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter' || evento.key === ' ') {
                    evento.preventDefault();
                    alternar(fila);
                }
            });
        });
    })();
    </script>
    <?php endif; ?>
</div>
