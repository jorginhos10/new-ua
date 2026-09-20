<?php
// Vista de prueba "Comparativo Árbol" — prototipo aislado (ver DevControlador). Combina tabla y
// árbol jerárquico usando los proyectos del PDI (Línea > Motor > Proyecto) como ejemplo: cada
// Línea es la suma de sus Motores, y cada Motor la suma de sus Proyectos. Toma datos reales de
// Gastos solo como referencia; no escribe nada ni reemplaza ninguna vista real.

require_once __DIR__ . '/../../../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../../../modelo/Linea.php';
require_once __DIR__ . '/../../../modelo/Motor.php';
require_once __DIR__ . '/../../../modelo/Proyecto.php';
require_once __DIR__ . '/../../../modelo/Gasto.php';

$modeloAnioArbol = new AnioPresupuestal();
$aniosActivosArbol = $modeloAnioArbol->obtenerActivos();
$aniosTodosArbol = $modeloAnioArbol->obtenerTodos();

$aniosPorNumeroArbol = [];
foreach ($aniosTodosArbol as $filaAnio) {
    $aniosPorNumeroArbol[(int) $filaAnio['anio']] = $filaAnio;
}

$anioVigenteRegistroArbol = $aniosActivosArbol[0] ?? ($aniosTodosArbol[0] ?? null);
$anioVigenteNumeroArbol = $anioVigenteRegistroArbol !== null ? (int) $anioVigenteRegistroArbol['anio'] : (int) date('Y');
$anioAnteriorNumeroArbol = $anioVigenteNumeroArbol - 1;

// Fecha de corte del año anterior: por defecto, el mismo día/mes de hoy pero en el año anterior
// (comparación "a la misma altura del año"), ajustable por el usuario vía ?corte=YYYY-MM-DD.
$fechaCorteDefectoArbol = $anioAnteriorNumeroArbol . date('-m-d');
$fechaCorteArbol = (string) ($_GET['corte'] ?? $fechaCorteDefectoArbol);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCorteArbol)) {
    $fechaCorteArbol = $fechaCorteDefectoArbol;
}

// --- Definición de columnas: vigente, año anterior (Final + a corte) y 4 años más antes de ese
// (año anterior cuenta como el primero de los "últimos 5 años"). Todas se calculan siempre; los
// botones de arriba solo muestran/ocultan sus columnas (ver CSS .mostrar-anterior /
// .mostrar-historico), y "Últimos 5 años" solo aparece cuando "Año anterior" está activo. ---
$columnasAniosArbol = [
    ['clave' => 'vigente', 'etiqueta' => (string) $anioVigenteNumeroArbol, 'registro' => $aniosPorNumeroArbol[$anioVigenteNumeroArbol] ?? null, 'corte' => null, 'grupo' => 'vigente'],
    ['clave' => 'anterior_total', 'etiqueta' => $anioAnteriorNumeroArbol . ' (Final)', 'registro' => $aniosPorNumeroArbol[$anioAnteriorNumeroArbol] ?? null, 'corte' => null, 'grupo' => 'anterior'],
    ['clave' => 'anterior_corte', 'etiqueta' => $anioAnteriorNumeroArbol . ' (' . date('d/m/Y', strtotime($fechaCorteArbol)) . ')', 'registro' => $aniosPorNumeroArbol[$anioAnteriorNumeroArbol] ?? null, 'corte' => $fechaCorteArbol, 'grupo' => 'anterior'],
];
for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
    $anioHistoricoArbol = $anioVigenteNumeroArbol - $desplazamiento;
    $columnasAniosArbol[] = [
        'clave' => 'historico_' . $anioHistoricoArbol,
        'etiqueta' => (string) $anioHistoricoArbol,
        'registro' => $aniosPorNumeroArbol[$anioHistoricoArbol] ?? null,
        'corte' => null,
        'grupo' => 'historico',
    ];
}

$modeloGastoArbol = new Gasto();

/** Suma valor_total por proyecto_id para un año dado, opcionalmente solo hasta una fecha de corte
 * (usa creado_en como referencia de "cuándo se registró", a falta de una fecha de ejecución por
 * transacción — es la aproximación razonable con los datos que hay). */
function totalesPorProyectoArbol(?array $registroAnio, ?string $fechaCorte, Gasto $modeloGasto): array
{
    if ($registroAnio === null) {
        return [];
    }

    $gastos = $modeloGasto->obtenerPorAnio((int) $registroAnio['id']);
    $totales = [];

    foreach ($gastos as $gasto) {
        if ($fechaCorte !== null && $gasto['creado_en'] > $fechaCorte . ' 23:59:59') {
            continue;
        }
        $idProyecto = (int) $gasto['proyecto_id'];
        $totales[$idProyecto] = ($totales[$idProyecto] ?? 0.0) + (float) $gasto['valor_total'];
    }

    return $totales;
}

$totalesPorColumnaArbol = [];
foreach ($columnasAniosArbol as $columna) {
    $totalesPorColumnaArbol[$columna['clave']] = totalesPorProyectoArbol($columna['registro'], $columna['corte'], $modeloGastoArbol);
}

// --- Árbol Línea > Motor > Proyecto, con cada nivel sumando el de sus hijos por columna. ---
$lineasArbol = (new Linea())->obtenerTodas();
$motoresArbol = (new Motor())->obtenerTodos();
$proyectosArbol = (new Proyecto())->obtenerTodos();

$clavesColumnasArbol = array_column($columnasAniosArbol, 'clave');
$arbolDatos = [];

foreach ($lineasArbol as $linea) {
    $nodoLinea = [
        'id' => 'linea-' . $linea['id'],
        'etiqueta' => $linea['codigo'] . ' - ' . $linea['nombre'],
        'nivel' => 0,
        'valores' => array_fill_keys($clavesColumnasArbol, 0.0),
        'hijos' => [],
    ];

    foreach ($motoresArbol as $motor) {
        if ((int) $motor['linea_id'] !== (int) $linea['id']) {
            continue;
        }

        $nodoMotor = [
            'id' => 'motor-' . $motor['id'],
            'etiqueta' => $motor['codigo'] . ' - ' . $motor['nombre'],
            'nivel' => 1,
            'valores' => array_fill_keys($clavesColumnasArbol, 0.0),
            'hijos' => [],
        ];

        foreach ($proyectosArbol as $proyecto) {
            if ((int) $proyecto['motor_id'] !== (int) $motor['id']) {
                continue;
            }

            $valoresProyecto = [];
            foreach ($clavesColumnasArbol as $clave) {
                $valor = $totalesPorColumnaArbol[$clave][(int) $proyecto['id']] ?? 0.0;
                $valoresProyecto[$clave] = $valor;
                $nodoMotor['valores'][$clave] += $valor;
            }

            $nodoMotor['hijos'][] = [
                'id' => 'proyecto-' . $proyecto['id'],
                'etiqueta' => $proyecto['codigo'] . ' - ' . $proyecto['nombre'],
                'nivel' => 2,
                'valores' => $valoresProyecto,
                'hijos' => [],
            ];
        }

        foreach ($clavesColumnasArbol as $clave) {
            $nodoLinea['valores'][$clave] += $nodoMotor['valores'][$clave];
        }
        $nodoLinea['hijos'][] = $nodoMotor;
    }

    $arbolDatos[] = $nodoLinea;
}

$totalesGeneralesArbol = array_fill_keys($clavesColumnasArbol, 0.0);
foreach ($arbolDatos as $nodoLineaTotal) {
    foreach ($clavesColumnasArbol as $clave) {
        $totalesGeneralesArbol[$clave] += $nodoLineaTotal['valores'][$clave];
    }
}

function renderFilaArbol(array $nodo, array $columnasAnios, ?string $padreId): void
{
    $tieneHijos = !empty($nodo['hijos']);
    echo '<tr class="arbol-fila nivel-' . $nodo['nivel'] . '" data-id="' . htmlspecialchars($nodo['id']) . '"'
        . ($padreId !== null ? ' data-padre="' . htmlspecialchars($padreId) . '"' : '')
        . ' data-expandido="1">';

    echo '<td class="arbol-celda-etiqueta">';
    echo '<span class="arbol-sangria" style="width:' . ($nodo['nivel'] * 22) . 'px"></span>';
    if ($tieneHijos) {
        echo '<button type="button" class="arbol-boton-expandir" data-id="' . htmlspecialchars($nodo['id']) . '" title="Contraer/expandir">'
            . '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>'
            . '</button>';
    } else {
        echo '<span class="arbol-punto" aria-hidden="true"></span>';
    }
    echo '<span class="arbol-etiqueta-texto" title="Doble clic para renombrar (solo Admin)">' . htmlspecialchars($nodo['etiqueta']) . '</span>';
    echo '</td>';

    foreach ($columnasAnios as $columna) {
        $valor = $nodo['valores'][$columna['clave']] ?? 0.0;
        echo '<td class="arbol-celda-valor ' . $columna['grupo'] . '" data-clave="' . htmlspecialchars($columna['clave']) . '" data-valor="' . number_format($valor, 2, '.', '') . '">$' . number_format($valor, 2, ',', '.') . '</td>';
    }

    echo '</tr>';

    foreach ($nodo['hijos'] as $hijo) {
        renderFilaArbol($hijo, $columnasAnios, $nodo['id']);
    }
}

$tituloPagina = 'Dev · Comparativo Árbol';
require __DIR__ . '/../../parciales/encabezado.php';
?>

<style>
    /* --- Prototipo "Comparativo Árbol": aparte del resto de estilos, no toca .tarjeta ni
       componentes reales. Reglas locales a esta página, no afectan estilo.css/app.js. --- */
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

    .arbol-boton-volver {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--color-texto-secundario);
        cursor: pointer;
        transition: background var(--transicion);
    }

    .arbol-boton-volver:hover {
        background: rgba(0, 0, 0, 0.08);
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

    /* --- Plantilla/Importar/Exportar: solo vista Admin (prototipo — no conectado a un backend
    real todavía, igual que Importar en el prototipo "Tabla"). --- */
    .arbol-grupo-datos {
        display: inline-flex;
        align-items: center;
        gap: 0.1rem;
        background: var(--color-gris-fondo);
        border-radius: 999px;
        padding: 0.2rem;
    }

    .arbol-icono-boton {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--color-gris-texto);
        cursor: pointer;
        transition: background var(--transicion), color var(--transicion);
    }

    .arbol-icono-boton:hover {
        background: rgba(0, 0, 0, 0.08);
    }

    .arbol-icono-boton.arbol-icono-exportar {
        color: var(--color-primario);
    }

    body.arbol-vista-consulta .arbol-grupo-datos {
        display: none;
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

    /* Sin esto, nuestro propio "display: flex" de arriba le gana al [hidden] del navegador (una
       regla de autor siempre le gana a la del user-agent, sin importar la especificidad) y el
       envoltorio se queda visible aunque JS ponga .hidden = true. */
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

    /* --- Alternador de rol de prueba: Admin puede editar valores con doble clic; Consulta solo
    ve los valores y la fecha de corte que dejó el admin (no puede tocar nada). --- */
    .arbol-rol-toggle {
        display: inline-flex;
        border-radius: 999px;
        background: var(--color-superficie);
        border: 1px solid var(--color-borde);
        flex-shrink: 0;
    }

    .arbol-rol-boton {
        border: none;
        background: transparent;
        border-radius: 999px;
        padding: 0.3rem 0.8rem;
        font-size: 0.78rem;
        cursor: pointer;
        color: var(--color-texto-secundario);
        transition: background var(--transicion), color var(--transicion);
    }

    .arbol-rol-boton.activo {
        background: var(--color-primario);
        color: #fff;
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

    /* Encabezado "2026 (a corte)": doble clic alterna valores/porcentajes en toda la columna. */
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

    /* Solo el nivel de Proyecto se edita a mano; Línea y Motor son sumas y se recalculan solas.
       El resaltado/cursor de edición desaparece en modo Consulta (ver body.arbol-vista-consulta). */
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

    /* Nombres (Línea/Motor/Proyecto): editables con doble clic igual que los valores, en
    cualquier nivel — renombrar no afecta las sumas. */
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
    <div class="arbol-topbar">
        <button type="button" class="arbol-boton-volver" title="Volver" onclick="window.location.href='index.php?ruta=dev'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>

        <h2 class="arbol-nombre">Comparativo Árbol</h2>

        <div class="arbol-rol-toggle" id="arbol-rol-toggle" title="Prueba de vista según el rol: Admin edita, Consulta solo ve">
            <button type="button" class="arbol-rol-boton activo" data-rol="admin">Admin</button>
            <button type="button" class="arbol-rol-boton" data-rol="consulta">Consulta</button>
        </div>

        <div class="arbol-acciones">
            <div class="arbol-grupo-datos" id="arbol-grupo-datos">
                <button type="button" class="arbol-icono-boton" id="arbol-boton-plantilla" title="Plantilla">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </button>
                <button type="button" class="arbol-icono-boton" id="arbol-boton-importar" title="Importar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
                <input type="file" id="arbol-archivo-importar" accept=".xlsx,.csv" hidden>
                <button type="button" class="arbol-icono-boton arbol-icono-exportar" id="arbol-boton-exportar" title="Exportar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                </button>
            </div>
            <button type="button" class="arbol-boton-toggle" id="arbol-boton-anterior">Año anterior</button>
            <div class="arbol-corte-envoltorio" id="arbol-corte-envoltorio" hidden>
                <label for="arbol-fecha-corte">Corte <?= $anioAnteriorNumeroArbol ?>:</label>
                <input type="date" id="arbol-fecha-corte" value="<?= htmlspecialchars($fechaCorteArbol) ?>">
            </div>
            <button type="button" class="arbol-boton-toggle" id="arbol-boton-historico" hidden>Últimos 5 años</button>
        </div>
    </div>

    <div class="arbol-scroll">
        <table class="arbol-tabla" id="arbol-tabla">
            <thead>
                <tr>
                    <th>Línea / Motor / Proyecto</th>
                    <?php foreach ($columnasAniosArbol as $columna): ?>
                    <th class="<?= $columna['grupo'] ?>" data-clave="<?= htmlspecialchars($columna['clave']) ?>"><?= htmlspecialchars($columna['etiqueta']) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr class="arbol-fila-totales" id="arbol-fila-totales">
                    <th class="arbol-celda-etiqueta">Total</th>
                    <?php foreach ($columnasAniosArbol as $columna): ?>
                    <th class="arbol-celda-valor <?= $columna['grupo'] ?>" data-clave="<?= htmlspecialchars($columna['clave']) ?>" data-valor="<?= number_format($totalesGeneralesArbol[$columna['clave']], 2, '.', '') ?>">$<?= number_format($totalesGeneralesArbol[$columna['clave']], 2, ',', '.') ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arbolDatos as $nodoLinea): ?>
                <?php renderFilaArbol($nodoLinea, $columnasAniosArbol, null); ?>
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

    // La fila de totales es un segundo <th> sticky: se apila justo debajo de la fila de
    // encabezados (mismo patrón que la fila de filtros en el prototipo "Tabla").
    var filaEncabezadosArbol = tabla.querySelector('thead tr:first-child');
    var filaTotalesArbol = document.getElementById('arbol-fila-totales');
    if (filaEncabezadosArbol && filaTotalesArbol) {
        var alturaEncabezadosArbol = filaEncabezadosArbol.getBoundingClientRect().height;
        Array.prototype.forEach.call(filaTotalesArbol.querySelectorAll('th'), function (celda) {
            celda.style.top = alturaEncabezadosArbol + 'px';
        });
    }

    var CLAVE_ANTERIOR = 'dev_arbol_mostrar_anterior';
    var CLAVE_HISTORICO = 'dev_arbol_mostrar_historico';

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

    // El input de fecha de corte solo se ve con el año anterior visible Y en rol Admin — en
    // Consulta se oculta del todo (ver aplicarRolArbol más abajo); la fecha ya elegida por el
    // admin queda igual visible dentro del encabezado de la columna "(dd/mm/aaaa)".
    function actualizarVisibilidadCorte() {
        if (envoltorioCorte) {
            envoltorioCorte.hidden = !(mostrarAnteriorActual && rolArbolActual === 'admin');
        }
    }

    function aplicarMostrarAnterior(mostrar) {
        mostrarAnteriorActual = mostrar;
        tabla.classList.toggle('mostrar-anterior', mostrar);
        if (botonAnterior) {
            botonAnterior.classList.toggle('activo', mostrar);
        }
        // "Últimos 5 años" solo tiene sentido con año anterior visible (es el primero de esos 5
        // años), así que el botón aparece con él y se apaga si año anterior se vuelve a ocultar.
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

    // --- Árbol: expandir/contraer. Contraer un padre oculta todos sus descendientes sin importar
    // el estado propio de cada uno; al volver a expandir, cada hijo respeta su último estado. ---
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

    // --- Alternador de rol de prueba: Admin puede editar valores con doble clic y mover la fecha
    // de corte; Consulta solo ve los valores y la fecha de corte que dejó el admin — nada se envía
    // al servidor ni toca la base de datos real, todo vive en memoria del navegador. ---
    var CLAVE_ROL_ARBOL = 'dev_arbol_rol';
    var botonesRolArbol = document.querySelectorAll('.arbol-rol-boton');
    var rolArbolActual = 'admin';

    function leerRolArbol() {
        try {
            return localStorage.getItem(CLAVE_ROL_ARBOL) === 'consulta' ? 'consulta' : 'admin';
        } catch (error) {
            return 'admin';
        }
    }

    function guardarRolArbol(rol) {
        try {
            localStorage.setItem(CLAVE_ROL_ARBOL, rol);
        } catch (error) {
            // localStorage no disponible: la preferencia simplemente no persiste
        }
    }

    function aplicarRolArbol(rol) {
        rolArbolActual = rol;
        document.body.classList.toggle('arbol-vista-consulta', rol === 'consulta');
        botonesRolArbol.forEach(function (boton) {
            boton.classList.toggle('activo', boton.dataset.rol === rol);
        });
        if (campoCorte) {
            campoCorte.disabled = rol === 'consulta';
        }
        actualizarVisibilidadCorte();
    }

    aplicarRolArbol(leerRolArbol());

    botonesRolArbol.forEach(function (boton) {
        boton.addEventListener('click', function () {
            aplicarRolArbol(boton.dataset.rol);
            guardarRolArbol(boton.dataset.rol);
        });
    });

    // --- Editar valores (solo rol Admin, solo filas de Proyecto): doble clic cambia el valor en
    // memoria y recalcula en cascada las sumas de su Motor y su Línea. ---
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

    // La fila de totales (bajo los encabezados) es la suma de todas las Líneas por columna.
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
            if (rolArbolActual !== 'admin' || celda.querySelector('input')) {
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

    // --- Doble clic en el encabezado "2026 (a corte)" alterna, en TODAS las filas (y en el
    // Total), entre el valor en pesos y el porcentaje que representa contra el "2026 (Final)" de
    // esa misma fila — "cuánto llevábamos a esa fecha, de lo que terminamos ejecutando". ---
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

    // --- Plantilla / Importar / Exportar (solo Admin — la vista Consulta oculta todo el grupo
    // por CSS). Prototipo aislado: igual que "Importar" en el prototipo "Tabla", nada de esto
    // toca un backend real todavía. ---
    var botonPlantillaArbol = document.getElementById('arbol-boton-plantilla');
    if (botonPlantillaArbol) {
        botonPlantillaArbol.addEventListener('click', function () {
            alert('Prueba de interfaz: este prototipo todavía no genera una plantilla real de este árbol.');
        });
    }

    var botonImportarArbol = document.getElementById('arbol-boton-importar');
    var archivoImportarArbol = document.getElementById('arbol-archivo-importar');
    if (botonImportarArbol && archivoImportarArbol) {
        botonImportarArbol.addEventListener('click', function () { archivoImportarArbol.click(); });
        archivoImportarArbol.addEventListener('change', function () {
            var nombreArchivo = archivoImportarArbol.files[0] ? archivoImportarArbol.files[0].name : '';
            if (nombreArchivo) {
                alert('Prueba de interfaz: se seleccionó "' + nombreArchivo + '". Este prototipo no importa datos reales todavía.');
            }
            archivoImportarArbol.value = '';
        });
    }

    var botonExportarArbol = document.getElementById('arbol-boton-exportar');
    if (botonExportarArbol) {
        botonExportarArbol.addEventListener('click', function () {
            alert('Prueba de interfaz: este prototipo todavía no exporta datos reales.');
        });
    }

    // --- Renombrar (Admin, cualquier nivel): doble clic en el nombre de Línea/Motor/Proyecto lo
    // vuelve editable — renombrar es solo de presentación, no toca ninguna suma. ---
    Array.prototype.forEach.call(tabla.querySelectorAll('.arbol-etiqueta-texto'), function (etiqueta) {
        etiqueta.addEventListener('dblclick', function (evento) {
            if (rolArbolActual !== 'admin' || etiqueta.querySelector('input')) {
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

<?php require __DIR__ . '/../../parciales/pie.php'; ?>
