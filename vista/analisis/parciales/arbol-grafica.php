<?php
/**
 * Vista "Gráfica" de Programación presupuestal (Ingresos/Egresos): el mismo árbol que pinta
 * arbol.php, aplanado a una fila por hoja, alimentando el motor de gráficas de tabla-real
 * (publico/js/tabla-real/: KPIs, barras/torta por dimensión, Pareto en "Proyecto PDI", filtro por
 * clic en barra). Mismo markup mínimo que vista/analisis/parciales/tabla-modulo.php, sin
 * Editar/Eliminar. La tabla plana nunca se muestra: el toggle "Árbol | Gráfica" de arbol.php abre
 * este contenedor y activa directo la vista de gráfica (#tdt-boton-vista-grafica).
 *
 * Variables esperadas (las mismas de arbol.php): $arbolDatos, $columnasAnios, $lado.
 */

require_once __DIR__ . '/../../../modelo/FilasTabla.php';

$esEgresosGrafica = $lado === 'egresos';
$etiquetaValorGrafica = 'Valor ' . ($columnasAnios[0]['etiqueta'] ?? '');

$columnasGrafica = ['Código', 'Grupo', 'Subgrupo', 'Descripción'];
$clavesGrafica = ['codigo', 'grupo', 'subgrupo', 'descripcion'];
if ($esEgresosGrafica) {
    $columnasGrafica[] = 'Proyecto PDI';
    $clavesGrafica[] = 'proyecto';
}
$columnasGrafica[] = 'Indexación';
$clavesGrafica[] = 'indexacion';
$columnasGrafica[] = $etiquetaValorGrafica;
$clavesGrafica[] = 'valor';

// Una fila por hoja (nodo sin hijas y que no es total), con su Grupo (raíz) y Subgrupo (nivel 1).
$filasGrafica = [];
$aplanarGrafica = function (array $nodos, ?string $grupo, ?string $subgrupo) use (&$aplanarGrafica, &$filasGrafica): void {
    foreach ($nodos as $nodo) {
        $nombre = trim(($nodo['codigo'] ?? '') . ' - ' . $nodo['etiqueta'], ' -');
        $grupoNodo = $grupo ?? $nombre;
        $subgrupoNodo = $grupo === null ? null : ($subgrupo ?? $nombre);

        if (!empty($nodo['hijos'])) {
            $aplanarGrafica($nodo['hijos'], $grupoNodo, $subgrupoNodo);
            continue;
        }
        if (!empty($nodo['esTotal'])) {
            continue;
        }

        $filasGrafica[] = [
            'origen_id' => count($filasGrafica) + 1,
            'puede_editar' => false,
            'codigo' => (string) ($nodo['codigo'] ?? ''),
            'grupo' => $grupoNodo,
            'subgrupo' => $subgrupoNodo ?? $grupoNodo,
            'descripcion' => (string) $nodo['etiqueta'],
            'proyecto' => trim((string) ($nodo['proyectosPdi'] ?? '')) !== '' ? (string) $nodo['proyectosPdi'] : '(Sin proyecto PDI)',
            'indexacion' => trim((string) ($nodo['indexacion'] ?? '')) !== '' ? (string) $nodo['indexacion'] : '(Sin indexación)',
            'valor' => (float) ($nodo['valores']['vigente'] ?? 0.0),
        ];
    }
};
$aplanarGrafica($arbolDatos, null, null);
?>
<div class="arbol-grafica-contenedor" id="arbol-grafica-contenedor" hidden>
    <?php if (empty($filasGrafica)): ?>
    <p class="texto-atenuado">No hay datos para graficar.</p>
    <?php else: ?>
    <div class="tabla-topbar">
        <div class="tabla-acciones">
            <div class="grupo-iconos grupo-basico" data-grupo="basico">
                <div class="buscar-envoltorio">
                    <button type="button" class="icono-boton" id="tdt-boton-buscar" title="Buscar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                    <div class="combo-envoltorio combo-buscar" id="tdt-combo-buscar">
                        <div class="combo-fantasma" aria-hidden="true"></div>
                        <input type="text" id="tdt-campo-buscar" class="combo-input" placeholder="Buscar…" autocomplete="off">
                    </div>
                </div>
                <button type="button" class="icono-boton" id="tdt-boton-filtrar" title="Filtrar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                </button>
                <?php // Los usa el toggle "Árbol | Gráfica" de arbol.php; la tabla plana no se muestra. ?>
                <button type="button" id="tdt-boton-vista-tabla" hidden aria-hidden="true"></button>
                <button type="button" id="tdt-boton-vista-grafica" hidden aria-hidden="true"></button>
            </div>
        </div>
    </div>

    <script type="application/json" id="tdt-filas"><?= json_encode(FilasTabla::paraJson($filasGrafica, $clavesGrafica), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
    <div class="tabla-scroll">
        <table class="tabla-dev-datos" id="tabla-programacion-<?= htmlspecialchars($lado) ?>" data-origen="programacion_<?= htmlspecialchars($lado) ?>" data-estado="analisis">
            <colgroup>
                <col style="width: 34px;">
                <?php foreach ($columnasGrafica as $indice => $columna): ?>
                <col id="tdt-col-<?= $indice ?>" style="width: 180px;">
                <?php endforeach; ?>
            </colgroup>
            <thead>
                <tr class="fila-encabezados">
                    <th class="col-seleccion"><input type="checkbox" id="tdt-seleccionar-todo" title="Seleccionar todo"></th>
                    <?php foreach ($columnasGrafica as $indice => $columna): ?>
                    <th>
                        <span class="encabezado-columna" data-indice="<?= $indice ?>"><?= htmlspecialchars($columna) ?></span>
                    </th>
                    <?php endforeach; ?>
                </tr>
                <tr class="fila-filtros">
                    <th></th>
                    <?php foreach ($columnasGrafica as $indice => $columna): ?>
                    <th>
                        <div class="combo-envoltorio combo-filtro combo-filtro-columna" data-indice="<?= $indice ?>">
                            <div class="combo-fantasma" aria-hidden="true"></div>
                            <input type="text" class="filtro-columna combo-input" data-indice="<?= $indice ?>" placeholder="Filtrar…" autocomplete="off">
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr class="fila-totales-tdt">
                    <td class="col-seleccion">Total</td>
                    <?php foreach ($columnasGrafica as $columna): ?>
                    <td></td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>

        <div class="tabla-grafica" id="tdt-grafica" hidden>
            <div class="tabla-grafica-kpis" id="tdt-grafica-kpis"></div>
            <div class="tabla-grafica-paneles" id="tdt-grafica-paneles"></div>
        </div>

        <div class="grafica-tooltip" id="tdt-grafica-tooltip"></div>
    </div>

    <script>
    var tdtColumnas = <?= json_encode($columnasGrafica, JSON_UNESCAPED_UNICODE) ?>;
    var tdtNamespace = <?= json_encode('programacion_' . $lado) ?>;
    </script>
    <script src="publico/js/tabla-real/nucleo.js?v=<?= filemtime(__DIR__ . '/../../../publico/js/tabla-real/nucleo.js') ?>"></script>
    <script src="publico/js/tabla-real/tabla.js?v=<?= filemtime(__DIR__ . '/../../../publico/js/tabla-real/tabla.js') ?>"></script>
    <script src="publico/js/tabla-real/graficos.js?v=<?= filemtime(__DIR__ . '/../../../publico/js/tabla-real/graficos.js') ?>"></script>
    <?php endif; ?>
</div>
