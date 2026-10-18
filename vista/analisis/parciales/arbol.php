<?php
/**
 * Árbol Línea > Motor > Proyecto para las pestañas "Articulación PDI" y "Programación
 * presupuestal" de ?ruta=analisis — portado de vista/dev/pruebas/arbol.php (ver ese archivo para
 * el diseño original). Diferencias con el prototipo:
 * - Los datos vienen de $arbolDatos/$columnasAnios/$totalesGenerales, ya resueltos por
 *   AnalisisControlador::renderizarArbol() vía FuenteDatosAnalisis (tiempo real/repositorio/
 *   usuario) — este parcial no sabe ni le importa de dónde vinieron.
 * - El rol Admin/Consulta ya no es un toggle propio: lo decide $rolVista, calculado por el
 *   controlador a partir del toggle de 3 vías de la franja superior (Tiempo real → admin;
 *   Repositorio/Usuario → consulta).
 * - Plantilla/Importar/Exportar y "Guardar versión" se promovieron a la franja superior
 *   (vista/analisis/index.php), junto al toggle de 3 vías — no viven aquí.
 * - $modoColumnas 'estructura' (solo año activo) vs 'completo' (+ anterior/histórico) decide si
 *   se muestran los controles de Año anterior/Últimos 5 años/fecha de corte.
 *
 * Variables esperadas: $arbolDatos, $columnasAnios, $totalesGenerales, $rolVista, $modoColumnas,
 * $fechaCorte, $anioAnteriorNumero, $pestanaArbol, $tab, $vista, $lado, $columnasExtra (array de
 * ['clave'=>..., 'etiqueta'=>...], vacío para Articulación PDI — ver
 * AnalisisControlador::renderizarArbol()). El toggle Egresos/Ingresos solo se dibuja para
 * $tab === 'programacion' — "Proyectos" también usa $modoColumnas 'completo' pero no tiene lados.
 */

/** Color de cada grupo raíz (por su posición) — lo comparten el árbol y su gráfica (arbol-grafica.php). */
function colorGrupoArbol(int $indiceRaiz): string
{
    $paleta = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

    return $paleta[$indiceRaiz % count($paleta)];
}

/**
 * Marcador de nivel, igual en el árbol y en su gráfica: una forma distinta por profundidad
 * (nivel 0 ■, nivel 1 □, nivel 2 ●, nivel 3 o más ○), del color de su grupo raíz.
 */
function marcadorNivelArbol(int $nivel, string $color): string
{
    return '<span class="arbol-marcador arbol-marcador-n' . min($nivel, 3) . '" style="--arbol-marcador-color: '
        . htmlspecialchars($color) . ';" title="Nivel ' . ($nivel + 1) . '" aria-hidden="true"></span>';
}

function renderFilaArbolAnalisis(array $nodo, array $columnasAnios, ?string $padreId, array $columnasExtra = [], bool $mostrarVariacion = false, bool $expandido = true, string $colorGrupo = '#2a78d6'): void
{
    $tieneHijos = !empty($nodo['hijos']);
    $esTotal = $nodo['esTotal'] ?? false;
    // Recogido por defecto: todas las filas con hijos salen contraídas y solo se ven las raíces.
    $clases = 'arbol-fila nivel-' . $nodo['nivel'] . ($esTotal ? ' arbol-fila-total' : '') . ($tieneHijos ? ' arbol-fila-expandible' : '')
        . (!$expandido && $tieneHijos ? ' arbol-contraido' : '') . (!$expandido && $padreId !== null ? ' arbol-fila-oculta' : '');
    echo '<tr class="' . $clases . '" data-id="' . htmlspecialchars($nodo['id']) . '"'
        . ($padreId !== null ? ' data-padre="' . htmlspecialchars($padreId) . '"' : '')
        . ' data-expandido="' . ($expandido ? '1' : '0') . '">';

    $celdasDespues = '';
    foreach ($columnasExtra as $columnaExtra) {
        $textoExtra = (string) ($nodo[$columnaExtra['clave']] ?? '');
        // Proyecto(s) PDI: solo el conteo ("pry ##"), no la lista concatenada.
        if ($columnaExtra['clave'] === 'proyectosPdi') {
            $nits = (string) ($nodo['proyectosPdiNits'] ?? '');
            $textoExtra = $textoExtra === '' ? '' : 'pry ' . count(explode(';', $nits));
        }
        $celdaExtra = '<td class="arbol-celda-extra arbol-celda-extra-' . htmlspecialchars($columnaExtra['clave']) . '">' . htmlspecialchars($textoExtra) . '</td>';
        // 'despues' (Detalle, Fuentes de financiación en "Proyectos"): a la derecha de la descripción.
        if (!empty($columnaExtra['despues'])) {
            $celdasDespues .= $celdaExtra;
        } else {
            echo $celdaExtra;
        }
    }

    // El flex vive en un <div> interno, nunca en el <td>: un <td> con display:flex deja de ser
    // celda de tabla y descuadra sus bordes/ancho contra el resto de la columna.
    echo '<td class="arbol-celda-etiqueta"><div class="arbol-etiqueta-contenido">';
    echo '<span class="arbol-sangria" style="width: calc(' . (int) $nodo['nivel'] . ' * var(--arbol-sangria-paso));"></span>';
    if ($tieneHijos) {
        echo '<button type="button" class="arbol-boton-expandir" data-id="' . htmlspecialchars($nodo['id']) . '" title="Contraer/expandir" aria-label="Contraer/expandir" aria-expanded="' . ($expandido ? 'true' : 'false') . '">'
            . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>'
            . '</button>';
    } else {
        // Mismo ancho que el botón de despliegue: las hojas quedan alineadas con sus hermanas.
        echo '<span class="arbol-espaciador" aria-hidden="true"></span>';
    }
    echo marcadorNivelArbol((int) $nodo['nivel'], $colorGrupo);
    $tituloEtiqueta = $tieneHijos ? 'Clic para contraer/expandir · doble clic para renombrar (solo Tiempo real)' : 'Doble clic para renombrar (solo Tiempo real)';
    echo '<span class="arbol-etiqueta-texto" title="' . $tituloEtiqueta . '">' . htmlspecialchars($nodo['etiqueta']) . '</span>';
    echo '</div></td>';
    echo $celdasDespues;

    foreach ($columnasAnios as $columna) {
        $valor = $nodo['valores'][$columna['clave']] ?? 0.0;
        echo '<td class="arbol-celda-valor ' . $columna['grupo'] . '" data-clave="' . htmlspecialchars($columna['clave']) . '" data-valor="' . number_format($valor, 2, '.', '') . '">$' . number_format($valor, 2, ',', '.') . '</td>';
        if ($mostrarVariacion && $columna['clave'] === 'vigente') {
            // Punto de color de "Indexación" (solo hojas — ver PresupuestoInstitucional::colorIndexacion()):
            // el texto de la plantilla es el tooltip, el color sale de ese mismo texto.
            $colorIndexacion = isset($nodo['indexacion']) ? PresupuestoInstitucional::colorIndexacion($nodo['indexacion']) : null;
            echo '<td class="arbol-celda-indexacion">';
            if ($colorIndexacion !== null) {
                echo '<span class="arbol-punto-indexacion" style="background:' . htmlspecialchars($colorIndexacion) . '" title="' . htmlspecialchars($nodo['indexacion']) . '"></span>';
            }
            echo '</td>';
            // Celdas vacías: el % lo llena el JS (actualizarPorcentajesGrupoArbol/actualizarVariacionesArbol),
            // así también se recalculan al editar.
            echo '<td class="arbol-celda-porcentaje-grupo porcentaje-grupo"></td>';
            echo '<td class="arbol-celda-variacion variacion"></td>';
        }
    }

    echo '</tr>';

    foreach ($nodo['hijos'] as $hijo) {
        renderFilaArbolAnalisis($hijo, $columnasAnios, $nodo['id'], $columnasExtra, $mostrarVariacion, $expandido, $colorGrupo);
    }
}
?>
<style>
    .arbol-tarjeta {
        padding: 0.5rem;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
    }

    .arbol-topbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
        padding: 0.5rem 0.5rem 0.75rem;
        flex-shrink: 0;
    }

    .arbol-nombre {
        margin: 0;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .arbol-acciones {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-left: auto;
    }

    .arbol-lado-toggle {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .arbol-lado-toggle a {
        text-decoration: none;
    }

    /* Rayita vertical entre los dos grupos (Ingresos/Egresos | Árbol/Gráfica). */
    .arbol-lado-toggle + .arbol-lado-toggle {
        border-left: 1px solid var(--color-borde);
        padding-left: 0.75rem;
        margin-left: 0.35rem;
    }

    .arbol-boton-toggle {
        border: 1px solid var(--color-borde);
        background: var(--color-superficie);
        border-radius: 999px;
        padding: 0.35rem 0.9rem;
        font-size: 0.82rem;
        cursor: pointer;
        color: var(--color-texto-secundario);
        transition: background var(--transicion), color var(--transicion), border-color var(--transicion);
        white-space: nowrap;
    }

    .arbol-boton-toggle:hover {
        border-color: var(--color-borde-hover);
    }

    .arbol-boton-toggle.activo {
        background: var(--color-primario);
        border-color: var(--color-primario);
        color: #fff;
    }

    .arbol-corte-envoltorio {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        color: var(--color-texto-secundario);
    }

    .arbol-corte-envoltorio[hidden] {
        display: none;
    }

    .arbol-corte-envoltorio input[type="date"] {
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        color: var(--color-texto);
    }

    .arbol-scroll {
        flex: 1;
        min-height: 0;
        overflow: auto;
    }

    /* Vista "Gráfica" de Programación presupuestal (arbol-grafica.php): ocupa el mismo alto que el
       árbol, para que graficos.js (ajustarAlturaGrafica) calcule bien el espacio disponible. */
    .arbol-grafica-contenedor:not([hidden]) {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .arbol-tabla {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
    }

    .arbol-tabla th,
    .arbol-tabla td {
        padding: 0.4rem 0.85rem;
        border-bottom: 1px solid var(--color-borde);
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        vertical-align: middle;
    }

    /* Columnas de texto (Código, Proyecto(s) PDI, Descripción) a la izquierda — encabezado,
       fila Total y filas de datos con la misma alineación; las cifras quedan a la derecha. */
    .arbol-tabla .arbol-celda-extra,
    .arbol-tabla .arbol-celda-etiqueta {
        text-align: left;
    }

    .arbol-tabla .arbol-celda-etiqueta {
        white-space: normal;
        min-width: 240px;
    }

    /* "Proyectos": Detalle es texto largo y Fuentes de financiación una lista de nombres. */
    .arbol-tabla .arbol-celda-extra-detalle,
    .arbol-tabla .arbol-celda-extra-fuentesFinanciacion {
        white-space: normal;
        min-width: 240px;
    }

    .arbol-tabla .arbol-celda-extra-proyectosPdi {
        width: 3rem;
        min-width: 3rem;
        max-width: 3rem;
    }

    .arbol-tabla td.arbol-celda-vacia {
        text-align: center;
        padding: 1.5rem 0.85rem;
    }

    .arbol-tabla thead th {
        position: sticky;
        top: 0;
        background: var(--color-superficie);
        z-index: 2;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--color-texto);
    }

    .arbol-tabla thead tr:first-child th:first-child {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--color-texto-secundario);
    }

    /* Misma altura que una fila del árbol (su celda de descripción mide --arbol-control + el
       padding común) y letra un poco más grande que el resto. */
    .arbol-fila-totales th {
        background: var(--color-fondo);
        border-bottom: 2px solid var(--color-borde-hover);
        font-weight: 700;
        font-size: 1.05rem;
        line-height: var(--arbol-control);
    }

    /* Filas '.0' del presupuesto institucional (suma de sus descendientes) — mismo tratamiento
       visual que ya tenía nivel-0, pero aplicable a CUALQUIER nivel (una fila total puede estar a
       2 o 3 niveles de profundidad, a diferencia de Línea/Motor/Proyecto que siempre es 3 niveles
       fijos). No afecta a Articulación PDI (esos nodos nunca traen 'esTotal'). */
    .arbol-fila-total {
        font-weight: 700;
        background: var(--color-fondo);
    }

    .arbol-celda-extra {
        color: var(--color-texto-secundario);
        font-variant-numeric: normal;
    }

    /* Proyecto(s) PDI solo se muestra en Tiempo real: en Repositorio/Usuario esta pestaña es de
       solo consulta. Código sí se ve en los tres modos (pedido del usuario). */
    body.arbol-vista-consulta .arbol-celda-extra:not(.arbol-celda-extra-codigo):not(.arbol-celda-extra-detalle):not(.arbol-celda-extra-fuentesFinanciacion) {
        display: none;
    }

    .arbol-encabezado-alternable {
        cursor: pointer;
        text-decoration: underline dotted;
        text-underline-offset: 3px;
    }

    .arbol-encabezado-alternable.mostrando-porcentaje {
        color: var(--color-primario);
    }

    .arbol-tabla th.anterior,
    .arbol-tabla td.anterior,
    .arbol-tabla th.historico,
    .arbol-tabla td.historico {
        display: none;
    }

    .arbol-tabla.mostrar-anterior th.anterior,
    .arbol-tabla.mostrar-anterior td.anterior {
        display: table-cell;
    }

    .arbol-tabla.mostrar-historico th.historico,
    .arbol-tabla.mostrar-historico td.historico {
        display: table-cell;
    }

    .arbol-tabla th.variacion,
    .arbol-tabla td.variacion {
        display: none;
        width: 1%;
        min-width: 64px;
        white-space: nowrap;
        text-align: right;
    }

    .arbol-tabla.mostrar-anterior th.variacion,
    .arbol-tabla.mostrar-anterior td.variacion {
        display: table-cell;
    }

    .arbol-tabla th.arbol-celda-indexacion,
    .arbol-tabla td.arbol-celda-indexacion {
        width: 1%;
        max-width: 22px;
        min-width: 0;
        padding-left: 0.15rem;
        padding-right: 0.15rem;
        text-align: center;
    }

    .arbol-punto-indexacion {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        cursor: default;
    }

    .arbol-tabla th.porcentaje-grupo,
    .arbol-tabla td.porcentaje-grupo {
        width: 1%;
        min-width: 0;
        padding-left: 0.3rem;
        padding-right: 0.5rem;
        white-space: nowrap;
        text-align: right;
    }

    .arbol-tabla td.porcentaje-grupo {
        color: #1F3B75;
        font-weight: 600;
    }

    .arbol-tabla .variacion-positiva {
        color: var(--color-exito-texto);
    }

    .arbol-tabla .variacion-negativa {
        color: var(--color-error-texto);
    }

    .arbol-tabla .variacion-na {
        color: var(--color-texto-tenue);
    }

    .arbol-fila.nivel-0 {
        font-weight: 700;
        background: var(--color-fondo);
    }

    .arbol-fila.nivel-1 {
        font-weight: 600;
    }

    .arbol-fila.nivel-2 td.arbol-celda-valor {
        color: var(--color-texto-secundario);
    }

    .arbol-fila:hover {
        background: var(--color-primario-suave);
    }

    .arbol-fila.nivel-2 td.arbol-celda-valor {
        cursor: pointer;
    }

    .arbol-fila.nivel-2 td.arbol-celda-valor:hover {
        background: var(--color-primario-suave);
        color: var(--color-texto);
    }

    body.arbol-vista-consulta .arbol-fila.nivel-2 td.arbol-celda-valor {
        cursor: default;
    }

    body.arbol-vista-consulta .arbol-fila.nivel-2 td.arbol-celda-valor:hover {
        background: transparent;
        color: var(--color-texto-secundario);
    }

    .arbol-input-editar {
        width: 100%;
        min-width: 90px;
        text-align: right;
        border: 1px solid var(--color-primario);
        border-radius: 4px;
        padding: 0.1rem 0.3rem;
        font: inherit;
        font-variant-numeric: tabular-nums;
    }

    .arbol-etiqueta-texto {
        cursor: pointer;
        border-radius: 4px;
        padding: 0 3px;
    }

    .arbol-etiqueta-texto:hover {
        background: var(--color-primario-suave);
    }

    body.arbol-vista-consulta .arbol-etiqueta-texto {
        cursor: default;
    }

    body.arbol-vista-consulta .arbol-etiqueta-texto:hover {
        background: transparent;
    }

    .arbol-input-nombre {
        width: 100%;
        min-width: 160px;
        text-align: left;
        border: 1px solid var(--color-primario);
        border-radius: 4px;
        padding: 0.1rem 0.3rem;
        font: inherit;
    }

    .arbol-fila-oculta {
        display: none;
    }

    /* Tamaños compartidos por el árbol y su gráfica (arbol-grafica.php): mismo botón de despliegue
       y misma sangría por nivel en las dos vistas. */
    .arbol-tabla,
    .arbol-grafica-filas {
        --arbol-control: 32px;
        --arbol-sangria-paso: 24px;
    }

    /* Táctil: el botón de despliegue crece a un tamaño cómodo para el dedo. */
    @media (pointer: coarse) {
        .arbol-tabla,
        .arbol-grafica-filas {
            --arbol-control: 40px;
        }
    }

    /* Móvil: menos sangría y relleno para que la descripción no se coma la pantalla. */
    @media (max-width: 640px) {
        .arbol-tabla,
        .arbol-grafica-filas {
            --arbol-sangria-paso: 12px;
        }

        .arbol-tabla th,
        .arbol-tabla td {
            padding: 0.35rem 0.5rem;
            font-size: 0.85rem;
        }

        .arbol-tabla .arbol-celda-etiqueta {
            min-width: 170px;
        }

        .arbol-topbar {
            padding: 0.4rem 0.25rem 0.6rem;
        }

        .arbol-acciones {
            margin-left: 0;
            width: 100%;
        }
    }

    .arbol-etiqueta-contenido {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        min-height: var(--arbol-control);
    }

    .arbol-sangria {
        display: inline-block;
        flex-shrink: 0;
    }

    /* Filas con hijos: toda la celda de descripción sirve para contraer/expandir. */
    .arbol-fila-expandible .arbol-celda-etiqueta {
        cursor: pointer;
        user-select: none;
    }

    .arbol-boton-expandir {
        border: 1px solid transparent;
        background: transparent;
        cursor: pointer;
        color: var(--color-texto-secundario);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--arbol-control);
        height: var(--arbol-control);
        padding: 0;
        flex-shrink: 0;
        border-radius: 6px;
        transition: background var(--transicion), border-color var(--transicion);
        touch-action: manipulation;
    }

    .arbol-boton-expandir:hover,
    .arbol-fila-expandible .arbol-celda-etiqueta:hover .arbol-boton-expandir {
        background: rgba(0, 0, 0, 0.08);
    }

    .arbol-boton-expandir:focus-visible {
        outline: 2px solid var(--color-primario);
        outline-offset: 1px;
    }

    .arbol-boton-expandir svg {
        transition: transform var(--transicion);
    }

    .arbol-fila.arbol-contraido .arbol-boton-expandir svg {
        transform: rotate(-90deg);
    }

    /* Mismo ancho que el botón de despliegue, para que las hojas queden alineadas con sus
       hermanas que sí tienen hijos. */
    .arbol-espaciador {
        display: inline-block;
        width: var(--arbol-control);
        flex-shrink: 0;
    }

    /* Marcador de nivel (ver marcadorNivelArbol()): ■ nivel 0, □ nivel 1, ● nivel 2, ○ nivel 3+,
       del color del grupo raíz — igual en el árbol y en la gráfica. */
    .arbol-marcador {
        display: inline-block;
        flex-shrink: 0;
        box-sizing: border-box;
        width: 10px;
        height: 10px;
        border: 2px solid var(--arbol-marcador-color);
    }

    .arbol-marcador-n0 {
        background: var(--arbol-marcador-color);
        border-radius: 3px;
    }

    .arbol-marcador-n1 {
        background: transparent;
        border-radius: 3px;
    }

    .arbol-marcador-n2 {
        width: 8px;
        height: 8px;
        background: var(--arbol-marcador-color);
        border-radius: 50%;
    }

    .arbol-marcador-n3 {
        width: 8px;
        height: 8px;
        background: transparent;
        border-radius: 50%;
    }
</style>

<?php
// Variación % (vigente vs año anterior Final): solo en la página, nunca en la plantilla.
$clavesColumnasArbol = array_column($columnasAnios, 'clave');
$mostrarVariacion = $tab === 'programacion' && in_array('vigente', $clavesColumnasArbol, true) && in_array('anterior_total', $clavesColumnasArbol, true);
$esSuperAdmin = $esSuperAdmin ?? false;
$columnasExtraAntes = array_filter($columnasExtra, static fn (array $c): bool => empty($c['despues']));
$columnasExtraDespues = array_filter($columnasExtra, static fn (array $c): bool => !empty($c['despues']));
$arbolExpandidoPorDefecto = $arbolExpandidoPorDefecto ?? true;
?>
<div class="tarjeta arbol-tarjeta">
    <?php if ($modoColumnas === 'completo' || $esSuperAdmin || !empty($accionesAnalisisHtml)): ?>
    <div class="arbol-topbar">
        <?php if ($tab === 'programacion'): ?>
        <div class="arbol-lado-toggle">
            <a href="<?= htmlspecialchars(analisisUrl('programacion', $vista, ['lado' => 'ingresos'])) ?>" class="arbol-boton-toggle <?= $lado === 'ingresos' ? 'activo' : '' ?>">Ingresos</a>
            <a href="<?= htmlspecialchars(analisisUrl('programacion', $vista, ['lado' => 'egresos'])) ?>" class="arbol-boton-toggle <?= $lado === 'egresos' ? 'activo' : '' ?>">Egresos</a>
        </div>
        <?php // Alterna entre el árbol y su gráfica jerárquica (ver arbol-grafica.php). ?>
        <div class="arbol-lado-toggle" id="arbol-vista-toggle">
            <button type="button" class="arbol-boton-toggle activo" data-vista-arbol="arbol" aria-pressed="true">Árbol</button>
            <button type="button" class="arbol-boton-toggle" data-vista-arbol="grafica" aria-pressed="false">Gráfica</button>
        </div>
        <?php endif; ?>
        <div class="arbol-acciones">
            <?php if ($esSuperAdmin): ?>
            <button type="button" class="arbol-boton-toggle <?= $arbolExpandidoPorDefecto ? 'activo' : '' ?>" id="arbol-boton-expansion-defecto" aria-pressed="<?= $arbolExpandidoPorDefecto ? 'true' : 'false' ?>" title="Solo SA: define si las listas de esta pestaña salen desplegadas (activo) o recogidas para todos">Listas desplegadas</button>
            <?php endif; ?>
            <?php if ($modoColumnas === 'completo'): ?>
            <button type="button" class="arbol-boton-toggle" id="arbol-boton-anterior">Año anterior</button>
            <div class="arbol-corte-envoltorio" id="arbol-corte-envoltorio" hidden>
                <label for="arbol-fecha-corte">Corte <?= $anioAnteriorNumero ?>:</label>
                <input type="date" id="arbol-fecha-corte" value="<?= htmlspecialchars($fechaCorte) ?>">
            </div>
            <button type="button" class="arbol-boton-toggle" id="arbol-boton-historico" hidden>Últimos 5 años</button>
            <?php endif; ?>
            <?= $accionesAnalisisHtml ?? '' ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="arbol-scroll">
        <table class="arbol-tabla" id="arbol-tabla">
            <thead>
                <tr>
                    <?php foreach ($columnasExtraAntes as $columnaExtra): ?>
                    <th class="arbol-celda-extra arbol-celda-extra-<?= htmlspecialchars($columnaExtra['clave']) ?>"><?= htmlspecialchars($columnaExtra['etiqueta']) ?></th>
                    <?php endforeach; ?>
                    <th class="arbol-celda-etiqueta"><?= htmlspecialchars($etiquetaColumnaArbol ?? 'Línea / Motor / Proyecto') ?></th>
                    <?php foreach ($columnasExtraDespues as $columnaExtra): ?>
                    <th class="arbol-celda-extra arbol-celda-extra-<?= htmlspecialchars($columnaExtra['clave']) ?>"><?= htmlspecialchars($columnaExtra['etiqueta']) ?></th>
                    <?php endforeach; ?>
                    <?php foreach ($columnasAnios as $columna): ?>
                    <th class="<?= $columna['grupo'] ?>" data-clave="<?= htmlspecialchars($columna['clave']) ?>"><?= htmlspecialchars($columna['etiqueta']) ?></th>
                    <?php if ($mostrarVariacion && $columna['clave'] === 'vigente'): ?>
                    <th class="arbol-celda-indexacion" title="Indexación: el criterio con el que se proyecta esta línea (solo hojas), diligenciado desde la plantilla."></th>
                    <th class="porcentaje-grupo" title="Porcentaje que representa esta línea sobre el total de su sección (1, 2, 3...)">%</th>
                    <th class="variacion" title="Variación: (<?= htmlspecialchars($columna['etiqueta']) ?> − año anterior Final) / año anterior Final">Var. %</th>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <tr class="arbol-fila-totales" id="arbol-fila-totales">
                    <?php foreach ($columnasExtraAntes as $columnaExtra): ?>
                    <th class="arbol-celda-extra arbol-celda-extra-<?= htmlspecialchars($columnaExtra['clave']) ?>"></th>
                    <?php endforeach; ?>
                    <th class="arbol-celda-etiqueta">Total</th>
                    <?php foreach ($columnasExtraDespues as $columnaExtra): ?>
                    <th class="arbol-celda-extra arbol-celda-extra-<?= htmlspecialchars($columnaExtra['clave']) ?>"></th>
                    <?php endforeach; ?>
                    <?php foreach ($columnasAnios as $columna): ?>
                    <th class="arbol-celda-valor <?= $columna['grupo'] ?>" data-clave="<?= htmlspecialchars($columna['clave']) ?>" data-valor="<?= number_format($totalesGenerales[$columna['clave']], 2, '.', '') ?>">$<?= number_format($totalesGenerales[$columna['clave']], 2, ',', '.') ?></th>
                    <?php if ($mostrarVariacion && $columna['clave'] === 'vigente'): ?>
                    <th class="arbol-celda-indexacion"></th>
                    <th class="porcentaje-grupo"></th>
                    <th class="arbol-celda-variacion variacion"></th>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($arbolDatos)): ?>
                <tr><td colspan="<?= count($columnasAnios) + 1 + count($columnasExtra) + ($mostrarVariacion ? 3 : 0) ?>" class="texto-atenuado arbol-celda-vacia">No hay datos para mostrar.</td></tr>
                <?php endif; ?>
                <?php foreach ($arbolDatos as $indiceRaiz => $nodoLinea): ?>
                <?php renderFilaArbolAnalisis($nodoLinea, $columnasAnios, null, $columnasExtra, $mostrarVariacion, $arbolExpandidoPorDefecto, colorGrupoArbol((int) $indiceRaiz)); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($tab === 'programacion'): ?>
    <?php require __DIR__ . '/arbol-grafica.php'; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabla = document.getElementById('arbol-tabla');
    if (!tabla) {
        return;
    }

    var rolVistaArbol = <?= json_encode($rolVista) ?>;
    var sufijoClaveArbol = <?= json_encode($pestanaArbol) ?>;
    document.body.classList.toggle('arbol-vista-consulta', rolVistaArbol !== 'admin');

    var filaEncabezadosArbol = tabla.querySelector('thead tr:first-child');
    var filaTotalesArbol = document.getElementById('arbol-fila-totales');
    if (filaEncabezadosArbol && filaTotalesArbol) {
        var alturaEncabezadosArbol = filaEncabezadosArbol.getBoundingClientRect().height;
        Array.prototype.forEach.call(filaTotalesArbol.querySelectorAll('th'), function (celda) {
            celda.style.top = alturaEncabezadosArbol + 'px';
        });
    }

    var CLAVE_ANTERIOR = 'analisis_arbol_mostrar_anterior_' + sufijoClaveArbol;
    var CLAVE_HISTORICO = 'analisis_arbol_mostrar_historico_' + sufijoClaveArbol;

    function leerBool(clave) {
        try {
            return localStorage.getItem(clave) === '1';
        } catch (error) {
            return false;
        }
    }

    function guardarBool(clave, valor) {
        try {
            localStorage.setItem(clave, valor ? '1' : '0');
        } catch (error) {
            // localStorage no disponible: la preferencia simplemente no persiste
        }
    }

    var botonAnterior = document.getElementById('arbol-boton-anterior');
    var botonHistorico = document.getElementById('arbol-boton-historico');
    var envoltorioCorte = document.getElementById('arbol-corte-envoltorio');
    var mostrarAnteriorActual = false;

    function actualizarVisibilidadCorte() {
        if (envoltorioCorte) {
            envoltorioCorte.hidden = !(mostrarAnteriorActual && rolVistaArbol === 'admin');
        }
    }

    function aplicarMostrarAnterior(mostrar) {
        mostrarAnteriorActual = mostrar;
        tabla.classList.toggle('mostrar-anterior', mostrar);
        if (botonAnterior) {
            botonAnterior.classList.toggle('activo', mostrar);
        }
        if (botonHistorico) {
            botonHistorico.hidden = !mostrar;
        }
        if (!mostrar && tabla.classList.contains('mostrar-historico')) {
            aplicarMostrarHistorico(false);
            guardarBool(CLAVE_HISTORICO, false);
        }
        actualizarVisibilidadCorte();
    }

    function aplicarMostrarHistorico(mostrar) {
        tabla.classList.toggle('mostrar-historico', mostrar);
        if (botonHistorico) {
            botonHistorico.classList.toggle('activo', mostrar);
        }
    }

    aplicarMostrarAnterior(leerBool(CLAVE_ANTERIOR));
    aplicarMostrarHistorico(leerBool(CLAVE_HISTORICO));

    if (botonAnterior) {
        botonAnterior.addEventListener('click', function () {
            var nuevoValor = !tabla.classList.contains('mostrar-anterior');
            aplicarMostrarAnterior(nuevoValor);
            guardarBool(CLAVE_ANTERIOR, nuevoValor);
        });
    }

    if (botonHistorico) {
        botonHistorico.addEventListener('click', function () {
            var nuevoValor = !tabla.classList.contains('mostrar-historico');
            aplicarMostrarHistorico(nuevoValor);
            guardarBool(CLAVE_HISTORICO, nuevoValor);
        });
    }

    var campoCorte = document.getElementById('arbol-fecha-corte');
    if (campoCorte) {
        campoCorte.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('corte', campoCorte.value);
            window.location.href = url.toString();
        });
    }

    // --- Árbol: expandir/contraer. ---
    var filas = Array.prototype.slice.call(tabla.querySelectorAll('.arbol-fila'));
    var filasPorId = {};
    filas.forEach(function (fila) { filasPorId[fila.dataset.id] = fila; });

    function estaVisible(fila) {
        var padreId = fila.dataset.padre;
        if (!padreId) {
            return true;
        }
        var filaPadre = filasPorId[padreId];
        if (!filaPadre) {
            return true;
        }
        return filaPadre.dataset.expandido !== '0' && estaVisible(filaPadre);
    }

    function actualizarVisibilidad() {
        filas.forEach(function (fila) {
            fila.classList.toggle('arbol-fila-oculta', !estaVisible(fila));
        });
    }

    function alternarFila(fila) {
        var expandidoActual = fila.dataset.expandido !== '0';
        fila.dataset.expandido = expandidoActual ? '0' : '1';
        fila.classList.toggle('arbol-contraido', expandidoActual);
        var boton = fila.querySelector('.arbol-boton-expandir');
        if (boton) {
            boton.setAttribute('aria-expanded', expandidoActual ? 'false' : 'true');
        }
        actualizarVisibilidad();
    }

    // --- Solo SA: estado por defecto (desplegado/recogido) de las listas de esta pestaña, para
    // todos. Se guarda en BD y además se aplica de una vez a la vista actual. ---
    function aplicarExpansionATodas(expandido) {
        filas.forEach(function (fila) {
            if (!fila.classList.contains('arbol-fila-expandible')) {
                return;
            }
            fila.dataset.expandido = expandido ? '1' : '0';
            fila.classList.toggle('arbol-contraido', !expandido);
            var boton = fila.querySelector('.arbol-boton-expandir');
            if (boton) {
                boton.setAttribute('aria-expanded', expandido ? 'true' : 'false');
            }
        });
        actualizarVisibilidad();
    }

    var botonExpansionDefecto = document.getElementById('arbol-boton-expansion-defecto');
    if (botonExpansionDefecto) {
        botonExpansionDefecto.addEventListener('click', function () {
            var nuevoValor = !botonExpansionDefecto.classList.contains('activo');
            var datos = new FormData();
            datos.append('accion', 'configurar_expansion_arbol');
            datos.append('expandido', nuevoValor ? '1' : '0');

            botonExpansionDefecto.disabled = true;
            fetch(window.location.href, { method: 'POST', body: datos, credentials: 'same-origin' })
                .then(function (respuesta) { return respuesta.json(); })
                .then(function (resultado) {
                    if (!resultado.ok) {
                        throw new Error(resultado.error || 'No se pudo guardar.');
                    }
                    botonExpansionDefecto.classList.toggle('activo', nuevoValor);
                    botonExpansionDefecto.setAttribute('aria-pressed', nuevoValor ? 'true' : 'false');
                    aplicarExpansionATodas(nuevoValor);
                })
                .catch(function (error) {
                    alert('No se pudo guardar la configuración: ' + error.message);
                })
                .then(function () {
                    botonExpansionDefecto.disabled = false;
                });
        });
    }

    // Un solo listener por celda de descripción: cubre el clic en el botón y en el texto
    // (pensado para táctil, donde el botón solo es un blanco pequeño). Se ignora mientras se
    // está renombrando (hay un <input> dentro).
    tabla.querySelectorAll('.arbol-fila-expandible .arbol-celda-etiqueta').forEach(function (celda) {
        celda.addEventListener('click', function (evento) {
            if (evento.target.closest('input')) {
                return;
            }
            alternarFila(celda.closest('.arbol-fila'));
        });
    });

    actualizarVisibilidad();

    // --- Editar valores (solo Tiempo real, solo filas de Proyecto): doble clic cambia el valor
    // en memoria y recalcula en cascada — no escribe nada en la base de datos real. ---
    function formatoMonedaArbol(valor) {
        return '$' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalcularAncestroArbol(idFila) {
        var fila = filasPorId[idFila];
        if (!fila) {
            return;
        }

        var hijos = filas.filter(function (f) { return f.dataset.padre === idFila; });

        Array.prototype.forEach.call(fila.querySelectorAll('td.arbol-celda-valor'), function (celda) {
            var clave = celda.dataset.clave;
            var suma = hijos.reduce(function (acumulado, hijo) {
                var celdaHijo = hijo.querySelector('td.arbol-celda-valor[data-clave="' + clave + '"]');
                return acumulado + (celdaHijo ? parseFloat(celdaHijo.dataset.valor) || 0 : 0);
            }, 0);
            celda.dataset.valor = suma;
            celda.textContent = formatoMonedaArbol(suma);
        });

        if (fila.dataset.padre) {
            recalcularAncestroArbol(fila.dataset.padre);
        }
    }

    function recalcularTotalesGeneralesArbol() {
        if (!filaTotalesArbol) {
            return;
        }
        var lineas = filas.filter(function (f) { return f.classList.contains('nivel-0'); });
        Array.prototype.forEach.call(filaTotalesArbol.querySelectorAll('[data-clave]'), function (celda) {
            var clave = celda.dataset.clave;
            var suma = lineas.reduce(function (acumulado, linea) {
                var celdaLinea = linea.querySelector('td.arbol-celda-valor[data-clave="' + clave + '"]');
                return acumulado + (celdaLinea ? parseFloat(celdaLinea.dataset.valor) || 0 : 0);
            }, 0);
            celda.dataset.valor = suma;
            celda.textContent = formatoMonedaArbol(suma);
        });
    }

    // --- Variación % = (vigente − anterior Final) / anterior Final; N.A. si no se puede calcular. ---
    function actualizarVariacionesArbol() {
        Array.prototype.forEach.call(tabla.querySelectorAll('.arbol-celda-variacion'), function (celda) {
            var fila = celda.parentNode;
            var celdaVigente = fila.querySelector('[data-clave="vigente"]');
            var celdaAnterior = fila.querySelector('[data-clave="anterior_total"]');
            var vigente = celdaVigente ? parseFloat(celdaVigente.dataset.valor) : NaN;
            var anterior = celdaAnterior ? parseFloat(celdaAnterior.dataset.valor) : NaN;
            var variacion = (vigente - anterior) / anterior * 100;

            celda.classList.remove('variacion-positiva', 'variacion-negativa', 'variacion-na');
            if (!isFinite(variacion)) {
                celda.textContent = 'N.A.';
                celda.classList.add('variacion-na');
                return;
            }
            celda.textContent = (variacion > 0 ? '+' : '') + variacion.toLocaleString('es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            if (variacion > 0) {
                celda.classList.add('variacion-positiva');
            } else if (variacion < 0) {
                celda.classList.add('variacion-negativa');
            }
        });
    }

    // --- % Participación: por defecto valor de la línea / Total general (fila "Total", igual que la
    // gráfica); con el toggle del encabezado, / valor de su Gran Grupo (la raíz nivel-0 de la que
    // cuelga, código "1", "2", "3"...) — para una raíz, eso es ella misma, por eso le queda 100%.
    // Columna siempre visible (a diferencia de Var. %, que depende de "Año anterior"); el título
    // de la cabecera queda corto ("%") para ahorrar espacio, con la explicación larga como tooltip. ---
    function filaGrupoRaizArbol(fila) {
        var actual = fila;
        while (actual && actual.dataset.padre && filasPorId[actual.dataset.padre]) {
            actual = filasPorId[actual.dataset.padre];
        }
        return actual;
    }

    // Modo del "%" (toggle en su encabezado, guardado por pestaña): 'total' = sobre el Total
    // general (la fila "Total", igual que la gráfica — por defecto) o 'grupo' = sobre su Gran Grupo.
    var CLAVE_PORCENTAJE = 'analisis_arbol_porcentaje_modo_' + sufijoClaveArbol;
    var modoPorcentajeArbol = 'total';
    try {
        if (localStorage.getItem(CLAVE_PORCENTAJE) === 'grupo') { modoPorcentajeArbol = 'grupo'; }
    } catch (error) {
        // localStorage no disponible: queda el modo por defecto
    }

    function actualizarPorcentajesGrupoArbol() {
        var celdaTotalGeneral = filaTotalesArbol ? filaTotalesArbol.querySelector('[data-clave="vigente"]') : null;
        var totalGeneral = celdaTotalGeneral ? parseFloat(celdaTotalGeneral.dataset.valor) : NaN;

        Array.prototype.forEach.call(tabla.querySelectorAll('.arbol-celda-porcentaje-grupo'), function (celda) {
            var fila = celda.parentNode;
            var celdaValor = fila.querySelector('[data-clave="vigente"]');
            var valor = celdaValor ? parseFloat(celdaValor.dataset.valor) : NaN;
            var denominador = totalGeneral;
            if (modoPorcentajeArbol === 'grupo') {
                var filaGrupo = filaGrupoRaizArbol(fila);
                var celdaGrupo = filaGrupo ? filaGrupo.querySelector('[data-clave="vigente"]') : null;
                denominador = celdaGrupo ? parseFloat(celdaGrupo.dataset.valor) : NaN;
            }
            var porcentaje = valor / denominador * 100;

            celda.classList.remove('variacion-na');
            if (!isFinite(porcentaje)) {
                celda.textContent = 'N.A.';
                celda.classList.add('variacion-na');
                return;
            }
            celda.textContent = porcentaje.toLocaleString('es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
        });
    }

    // Encabezado "%" como toggle (mismo estilo que el de la columna "a corte"): el texto dice el
    // modo activo y un clic alterna, recalcula y guarda la elección.
    var encabezadoPorcentajeArbol = tabla.querySelector('thead tr:first-child th.porcentaje-grupo');

    function pintarEncabezadoPorcentajeArbol() {
        if (!encabezadoPorcentajeArbol) { return; }
        var esTotal = modoPorcentajeArbol === 'total';
        encabezadoPorcentajeArbol.textContent = esTotal ? '% total' : '% sección';
        encabezadoPorcentajeArbol.title = (esTotal
            ? 'Porcentaje de cada línea sobre el Total general (como en la gráfica)'
            : 'Porcentaje de cada línea sobre el total de su sección (1, 2, 3...)')
            + ' · clic para alternar';
        encabezadoPorcentajeArbol.classList.toggle('mostrando-porcentaje', !esTotal);
    }

    if (encabezadoPorcentajeArbol) {
        encabezadoPorcentajeArbol.classList.add('arbol-encabezado-alternable');
        encabezadoPorcentajeArbol.addEventListener('click', function () {
            modoPorcentajeArbol = modoPorcentajeArbol === 'total' ? 'grupo' : 'total';
            try { localStorage.setItem(CLAVE_PORCENTAJE, modoPorcentajeArbol); } catch (error) { /* no persiste */ }
            pintarEncabezadoPorcentajeArbol();
            actualizarPorcentajesGrupoArbol();
        });
    }

    pintarEncabezadoPorcentajeArbol();
    actualizarVariacionesArbol();
    actualizarPorcentajesGrupoArbol();

    // Programación presupuestal y Proyectos (SA, Tiempo real): lo editado con doble clic se guarda
    // en la BD (ver AnalisisControlador::editarArbolPresupuesto()). Articulación PDI sale de los
    // gastos, así que ahí la edición sigue siendo solo de presentación.
    var arbolGuardaEnBd = <?= json_encode(in_array($tab, ['programacion', 'proyectos'], true) && $vista === 'tiempo_real' && AccesoAnalisis::actual()['clase'] === 'superadmin') ?>;

    // Manda un cambio al servidor; si falla, avisa y llama a revertir() para deshacerlo en pantalla.
    function guardarCambioArbol(campos, revertir) {
        var datos = new FormData();
        Object.keys(campos).forEach(function (nombre) {
            datos.append(nombre, campos[nombre]);
        });
        fetch(window.location.href, { method: 'POST', body: datos, credentials: 'same-origin' })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (resultado) {
                if (!resultado.ok) {
                    throw new Error(resultado.error || 'No se pudo guardar.');
                }
            })
            .catch(function (error) {
                alert('No se guardó el cambio: ' + error.message);
                revertir();
            });
    }

    function aplicarValorCeldaArbol(celda, valor) {
        celda.dataset.valor = valor;
        celda.textContent = formatoMonedaArbol(valor);

        var filaProyecto = celda.closest('.arbol-fila');
        if (filaProyecto && filaProyecto.dataset.padre) {
            recalcularAncestroArbol(filaProyecto.dataset.padre);
        }
        recalcularTotalesGeneralesArbol();
        actualizarTodasCeldasCorteArbol();
        actualizarVariacionesArbol();
        actualizarPorcentajesGrupoArbol();
    }

    // En el árbol presupuestal se editan las hojas de cualquier nivel (los totales son la suma de
    // sus hijas); en Articulación PDI, los proyectos (nivel 2), como antes.
    var selectorCeldasEditables = arbolGuardaEnBd
        ? '.arbol-fila:not(.arbol-fila-expandible):not(.arbol-fila-total) td.arbol-celda-valor'
        : '.arbol-fila.nivel-2 td.arbol-celda-valor';

    Array.prototype.forEach.call(tabla.querySelectorAll(selectorCeldasEditables), function (celda) {
        celda.addEventListener('dblclick', function () {
            if (rolVistaArbol !== 'admin' || celda.querySelector('input')) {
                return;
            }

            var valorActual = parseFloat(celda.dataset.valor) || 0;
            var textoOriginal = celda.textContent;

            var input = document.createElement('input');
            input.type = 'number';
            input.step = '0.01';
            input.className = 'arbol-input-editar';
            input.value = valorActual;

            celda.textContent = '';
            celda.appendChild(input);
            input.focus();
            input.select();

            function confirmar() {
                var nuevoValor = parseFloat(input.value);
                if (isNaN(nuevoValor)) {
                    nuevoValor = valorActual;
                }
                aplicarValorCeldaArbol(celda, nuevoValor);

                var fila = celda.closest('.arbol-fila');
                if (arbolGuardaEnBd && nuevoValor !== valorActual && fila) {
                    guardarCambioArbol(
                        { accion: 'editar_valor_arbol', id: fila.dataset.id, clave: celda.dataset.clave, valor: nuevoValor },
                        function () { aplicarValorCeldaArbol(celda, valorActual); }
                    );
                }
            }

            input.addEventListener('blur', confirmar);
            input.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    input.blur();
                } else if (evento.key === 'Escape') {
                    input.removeEventListener('blur', confirmar);
                    celda.textContent = textoOriginal;
                }
            });
        });
    });

    // --- Doble clic en "(a corte)" alterna valor/porcentaje contra el "Final" de esa fila. ---
    var mostrandoPorcentajeCorte = false;

    function calcularPorcentajeArbol(valorParcial, valorTotal) {
        return valorTotal > 0 ? (valorParcial / valorTotal) * 100 : 0;
    }

    function formatoPorcentajeArbol(valor) {
        return valor.toLocaleString('es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
    }

    function actualizarCeldaCorteArbol(fila) {
        var celdaCorte = fila.querySelector('[data-clave="anterior_corte"]');
        var celdaFinal = fila.querySelector('[data-clave="anterior_total"]');
        if (!celdaCorte) {
            return;
        }
        var valorCorte = parseFloat(celdaCorte.dataset.valor) || 0;
        if (mostrandoPorcentajeCorte) {
            var valorFinal = celdaFinal ? (parseFloat(celdaFinal.dataset.valor) || 0) : 0;
            celdaCorte.textContent = formatoPorcentajeArbol(calcularPorcentajeArbol(valorCorte, valorFinal));
        } else {
            celdaCorte.textContent = formatoMonedaArbol(valorCorte);
        }
    }

    function actualizarTodasCeldasCorteArbol() {
        filas.forEach(actualizarCeldaCorteArbol);
        if (filaTotalesArbol) {
            actualizarCeldaCorteArbol(filaTotalesArbol);
        }
    }

    var encabezadoCorteArbol = tabla.querySelector('thead tr:first-child th[data-clave="anterior_corte"]');
    if (encabezadoCorteArbol) {
        encabezadoCorteArbol.classList.add('arbol-encabezado-alternable');
        encabezadoCorteArbol.title = 'Doble clic: alternar entre valores y % del año anterior "Final"';
        encabezadoCorteArbol.addEventListener('dblclick', function () {
            mostrandoPorcentajeCorte = !mostrandoPorcentajeCorte;
            encabezadoCorteArbol.classList.toggle('mostrando-porcentaje', mostrandoPorcentajeCorte);
            actualizarTodasCeldasCorteArbol();
        });
    }

    // --- Renombrar (Tiempo real, cualquier nivel): no toca sumas. En el árbol presupuestal se
    // guarda en la BD (arbolGuardaEnBd); en Articulación PDI es solo de presentación. ---
    Array.prototype.forEach.call(tabla.querySelectorAll('.arbol-etiqueta-texto'), function (etiqueta) {
        etiqueta.addEventListener('dblclick', function (evento) {
            if (rolVistaArbol !== 'admin' || etiqueta.querySelector('input')) {
                return;
            }
            evento.stopPropagation();

            var textoOriginal = etiqueta.textContent;
            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'arbol-input-nombre';
            input.value = textoOriginal;

            etiqueta.textContent = '';
            etiqueta.appendChild(input);
            input.focus();
            input.select();

            function confirmarNombre() {
                var nuevoTexto = input.value.trim();
                etiqueta.textContent = nuevoTexto !== '' ? nuevoTexto : textoOriginal;

                var fila = etiqueta.closest('.arbol-fila');
                if (arbolGuardaEnBd && nuevoTexto !== '' && nuevoTexto !== textoOriginal && fila) {
                    guardarCambioArbol(
                        { accion: 'renombrar_linea_arbol', id: fila.dataset.id, descripcion: nuevoTexto },
                        function () { etiqueta.textContent = textoOriginal; }
                    );
                }
            }

            input.addEventListener('blur', confirmarNombre);
            input.addEventListener('keydown', function (eventoTecla) {
                if (eventoTecla.key === 'Enter') {
                    input.blur();
                } else if (eventoTecla.key === 'Escape') {
                    input.removeEventListener('blur', confirmarNombre);
                    etiqueta.textContent = textoOriginal;
                }
            });
        });
    });
});
</script>

<?php if ($tab === 'programacion'): ?>
<script>
// Toggle "Árbol | Gráfica" de Programación presupuestal: alterna entre el árbol y la gráfica
// jerárquica de arbol-grafica.php. La elección se recuerda por lado (Ingresos/Egresos).
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('arbol-vista-toggle');
    var contenedorGrafica = document.getElementById('arbol-grafica-contenedor');
    var scrollArbol = document.querySelector('.arbol-tarjeta .arbol-scroll');
    var accionesArbol = document.querySelector('.arbol-tarjeta .arbol-acciones');
    if (!toggle || !contenedorGrafica || !scrollArbol) {
        return;
    }

    var claveVista = 'analisis_programacion_vista_' + <?= json_encode($lado) ?>;
    var botones = toggle.querySelectorAll('[data-vista-arbol]');

    function mostrarVista(vista) {
        var esGrafica = vista === 'grafica';
        scrollArbol.style.display = esGrafica ? 'none' : '';
        // Solo los controles propios del árbol (Año anterior, Listas desplegadas, corte...); las
        // acciones de la pestaña (.analisis-acciones: exportar, snapshot, vista) siguen visibles.
        if (accionesArbol) {
            Array.prototype.forEach.call(accionesArbol.children, function (hijo) {
                if (!hijo.classList.contains('analisis-acciones')) {
                    hijo.style.display = esGrafica ? 'none' : '';
                }
            });
        }
        contenedorGrafica.hidden = !esGrafica;
        Array.prototype.forEach.call(botones, function (boton) {
            var activo = boton.dataset.vistaArbol === vista;
            boton.classList.toggle('activo', activo);
            boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });

        try { localStorage.setItem(claveVista, vista); } catch (e) { /* sin localStorage */ }
    }

    Array.prototype.forEach.call(botones, function (boton) {
        boton.addEventListener('click', function () { mostrarVista(boton.dataset.vistaArbol); });
    });

    var vistaGuardada = null;
    try { vistaGuardada = localStorage.getItem(claveVista); } catch (e) { vistaGuardada = null; }
    if (vistaGuardada === 'grafica') {
        mostrarVista('grafica');
    }
});
</script>
<?php endif; ?>
