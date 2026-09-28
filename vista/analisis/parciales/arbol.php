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

function renderFilaArbolAnalisis(array $nodo, array $columnasAnios, ?string $padreId, array $columnasExtra = []): void
{
    $tieneHijos = !empty($nodo['hijos']);
    $esTotal = $nodo['esTotal'] ?? false;
    $clases = 'arbol-fila nivel-' . $nodo['nivel'] . ($esTotal ? ' arbol-fila-total' : '');
    echo '<tr class="' . $clases . '" data-id="' . htmlspecialchars($nodo['id']) . '"'
        . ($padreId !== null ? ' data-padre="' . htmlspecialchars($padreId) . '"' : '')
        . ' data-expandido="1">';

    foreach ($columnasExtra as $columnaExtra) {
        echo '<td class="arbol-celda-extra">' . htmlspecialchars((string) ($nodo[$columnaExtra['clave']] ?? '')) . '</td>';
    }

    echo '<td class="arbol-celda-etiqueta">';
    echo '<span class="arbol-sangria" style="width:' . ($nodo['nivel'] * 22) . 'px"></span>';
    if ($tieneHijos) {
        echo '<button type="button" class="arbol-boton-expandir" data-id="' . htmlspecialchars($nodo['id']) . '" title="Contraer/expandir">'
            . '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>'
            . '</button>';
    } else {
        echo '<span class="arbol-punto" aria-hidden="true"></span>';
    }
    echo '<span class="arbol-etiqueta-texto" title="Doble clic para renombrar (solo Tiempo real)">' . htmlspecialchars($nodo['etiqueta']) . '</span>';
    echo '</td>';

    foreach ($columnasAnios as $columna) {
        $valor = $nodo['valores'][$columna['clave']] ?? 0.0;
        echo '<td class="arbol-celda-valor ' . $columna['grupo'] . '" data-clave="' . htmlspecialchars($columna['clave']) . '" data-valor="' . number_format($valor, 2, '.', '') . '">$' . number_format($valor, 2, ',', '.') . '</td>';
    }

    echo '</tr>';

    foreach ($nodo['hijos'] as $hijo) {
        renderFilaArbolAnalisis($hijo, $columnasAnios, $nodo['id'], $columnasExtra);
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

    .arbol-tabla th:first-child,
    .arbol-tabla td.arbol-celda-etiqueta {
        text-align: left;
        white-space: normal;
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

    .arbol-fila-totales th {
        background: var(--color-fondo);
        border-bottom: 2px solid var(--color-borde-hover);
        font-weight: 700;
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

    /* Código/Proyecto(s) PDI solo se muestran en Tiempo real — confirmado con el usuario: en
       Repositorio/Usuario esta pestaña es de solo consulta (descripción + cifras). */
    body.arbol-vista-consulta .arbol-celda-extra {
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

    .arbol-celda-etiqueta {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .arbol-sangria {
        display: inline-block;
        flex-shrink: 0;
    }

    .arbol-boton-expandir {
        border: none;
        background: transparent;
        cursor: pointer;
        color: var(--color-texto-secundario);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        border-radius: 4px;
        transition: background var(--transicion);
    }

    .arbol-boton-expandir:hover {
        background: rgba(0, 0, 0, 0.08);
    }

    .arbol-boton-expandir svg {
        transition: transform var(--transicion);
    }

    .arbol-fila.arbol-contraido .arbol-boton-expandir svg {
        transform: rotate(-90deg);
    }

    .arbol-punto {
        display: inline-block;
        width: 4px;
        height: 4px;
        min-width: 4px;
        border-radius: 50%;
        background: var(--color-borde-hover);
        margin: 0 8px;
    }
</style>

<div class="tarjeta arbol-tarjeta">
    <?php if ($modoColumnas === 'completo'): ?>
    <div class="arbol-topbar">
        <?php if ($tab === 'programacion'): ?>
        <div class="arbol-lado-toggle">
            <a href="<?= htmlspecialchars(analisisUrl('programacion', $vista, ['lado' => 'egresos'])) ?>" class="arbol-boton-toggle <?= $lado === 'egresos' ? 'activo' : '' ?>">Egresos</a>
            <a href="<?= htmlspecialchars(analisisUrl('programacion', $vista, ['lado' => 'ingresos'])) ?>" class="arbol-boton-toggle <?= $lado === 'ingresos' ? 'activo' : '' ?>">Ingresos</a>
        </div>
        <?php endif; ?>
        <div class="arbol-acciones">
            <button type="button" class="arbol-boton-toggle" id="arbol-boton-anterior">Año anterior</button>
            <div class="arbol-corte-envoltorio" id="arbol-corte-envoltorio" hidden>
                <label for="arbol-fecha-corte">Corte <?= $anioAnteriorNumero ?>:</label>
                <input type="date" id="arbol-fecha-corte" value="<?= htmlspecialchars($fechaCorte) ?>">
            </div>
            <button type="button" class="arbol-boton-toggle" id="arbol-boton-historico" hidden>Últimos 5 años</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="arbol-scroll">
        <table class="arbol-tabla" id="arbol-tabla">
            <thead>
                <tr>
                    <?php foreach ($columnasExtra as $columnaExtra): ?>
                    <th class="arbol-celda-extra"><?= htmlspecialchars($columnaExtra['etiqueta']) ?></th>
                    <?php endforeach; ?>
                    <th><?= htmlspecialchars($etiquetaColumnaArbol ?? 'Línea / Motor / Proyecto') ?></th>
                    <?php foreach ($columnasAnios as $columna): ?>
                    <th class="<?= $columna['grupo'] ?>" data-clave="<?= htmlspecialchars($columna['clave']) ?>"><?= htmlspecialchars($columna['etiqueta']) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr class="arbol-fila-totales" id="arbol-fila-totales">
                    <?php foreach ($columnasExtra as $columnaExtra): ?>
                    <th class="arbol-celda-extra"></th>
                    <?php endforeach; ?>
                    <th class="arbol-celda-etiqueta">Total</th>
                    <?php foreach ($columnasAnios as $columna): ?>
                    <th class="arbol-celda-valor <?= $columna['grupo'] ?>" data-clave="<?= htmlspecialchars($columna['clave']) ?>" data-valor="<?= number_format($totalesGenerales[$columna['clave']], 2, '.', '') ?>">$<?= number_format($totalesGenerales[$columna['clave']], 2, ',', '.') ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($arbolDatos)): ?>
                <tr><td colspan="<?= count($columnasAnios) + 1 + count($columnasExtra) ?>" class="texto-atenuado">No hay datos para mostrar.</td></tr>
                <?php endif; ?>
                <?php foreach ($arbolDatos as $nodoLinea): ?>
                <?php renderFilaArbolAnalisis($nodoLinea, $columnasAnios, null, $columnasExtra); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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

    tabla.querySelectorAll('.arbol-boton-expandir').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var fila = boton.closest('.arbol-fila');
            var expandidoActual = fila.dataset.expandido !== '0';
            fila.dataset.expandido = expandidoActual ? '0' : '1';
            fila.classList.toggle('arbol-contraido', expandidoActual);
            actualizarVisibilidad();
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

    Array.prototype.forEach.call(tabla.querySelectorAll('.arbol-fila.nivel-2 td.arbol-celda-valor'), function (celda) {
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
                celda.dataset.valor = nuevoValor;
                celda.textContent = formatoMonedaArbol(nuevoValor);

                var filaProyecto = celda.closest('.arbol-fila');
                if (filaProyecto && filaProyecto.dataset.padre) {
                    recalcularAncestroArbol(filaProyecto.dataset.padre);
                }
                recalcularTotalesGeneralesArbol();
                actualizarTodasCeldasCorteArbol();
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

    // --- Renombrar (Tiempo real, cualquier nivel): solo de presentación, no toca sumas. ---
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
