<?php
// Vista de prueba "Tabla" — prototipo aislado (ver DevControlador). Toma datos reales de Gastos
// solo como referencia visual; no lee del formulario real ni escribe nada.
//
// ?demo=1 cambia a un array chico fijo (5 insumos de oficina) y activa Ver/Editar/Eliminar/
// Guardar/Enviar de verdad (solo en memoria del navegador, nunca toca la base de datos) — es
// para poder probar el comportamiento de los botones sin arriesgar datos reales de Gastos.
$modoDemo = !empty($_GET['demo']);

require_once __DIR__ . '/../../../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../../../modelo/Gasto.php';
require_once __DIR__ . '/../../../modelo/Usuario.php';
require_once __DIR__ . '/../../../modelo/Dependencia.php';
require_once __DIR__ . '/../../../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../../../modelo/RelojArenaConfiguracion.php';

$modeloGastoTabla = new Gasto();
$aniosActivos = (new AnioPresupuestal())->obtenerActivos();
$anioId = (int) ($aniosActivos[0]['id'] ?? 0);
$anioSeleccionado = null;
foreach ($aniosActivos as $anioFila) {
    if ((int) $anioFila['id'] === $anioId) {
        $anioSeleccionado = $anioFila;
        break;
    }
}
$gastos = $anioId > 0 ? $modeloGastoTabla->obtenerPorAnio($anioId) : [];
$gastos = array_slice($gastos, 0, 25);

// Franja de contexto (pestañas Gastos/Ingresos + barra de presupuesto): misma referencia que usa
// la página real de Gastos (GastoControlador::index()), calculada aquí de nuevo porque esta vista
// vive aparte y no comparte estado con ese controlador. No aplica en modo demo (array chico, sin
// año/dependencia real detrás).
$dependenciaUsuarioEsRaizTabla = false;
$presupuestoAnioTabla = 0.0;
$totalGastadoTabla = 0.0;
$porcentajeGastadoTabla = 0.0;
$diasFaltantesRelojTabla = null;
$dependenciaUsuarioIdTabla = null;
$relojArenaConfigTabla = null;

if (!$modoDemo) {
    $usuarioTabla = (new Usuario())->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
    $dependenciaUsuarioIdTabla = !empty($usuarioTabla['dependencia_id']) ? (int) $usuarioTabla['dependencia_id'] : null;
    $dependenciaUsuarioTabla = $dependenciaUsuarioIdTabla !== null ? (new Dependencia())->obtenerPorId($dependenciaUsuarioIdTabla) : null;
    $dependenciaUsuarioEsRaizTabla = $dependenciaUsuarioTabla !== null && !empty($dependenciaUsuarioTabla['es_raiz_superadmin']);

    $techoDependenciaTabla = null;
    if ($dependenciaUsuarioIdTabla !== null && $anioId > 0) {
        if ($dependenciaUsuarioEsRaizTabla) {
            $techoDependenciaTabla = $anioSeleccionado !== null ? (float) $anioSeleccionado['presupuesto'] : null;
        } else {
            $presupuestosDependenciaTabla = (new PresupuestoDependencia())->obtenerPorAnio($anioId);
            $techoDependenciaTabla = $presupuestosDependenciaTabla[$dependenciaUsuarioIdTabla]['techo'] ?? null;
            $techoDependenciaTabla = $techoDependenciaTabla !== null ? (float) $techoDependenciaTabla : null;
        }
    }

    $totalGastadoTabla = ($dependenciaUsuarioTabla !== null && $anioId > 0)
        ? $modeloGastoTabla->obtenerTotalEjecutadoPorAnioYDependencia($anioId, $dependenciaUsuarioTabla['nombre'])
        : 0.0;
    $presupuestoAnioTabla = $techoDependenciaTabla ?? 0.0;
    $porcentajeGastadoTabla = $presupuestoAnioTabla > 0 ? min(100, ($totalGastadoTabla / $presupuestoAnioTabla) * 100) : 0.0;

    // Reloj de arena que aplica aquí: el general (institucional), el mismo que se calcula para el
    // widget circular del dashboard (DashboardControlador::obtenerRelojArena()) — Gastos no tiene
    // un reloj propio como Necesidades (ese es "Formulador", ligado solo a ese módulo).
    $relojArenaConfigTabla = (new RelojArenaConfiguracion())->obtener();
    if ($relojArenaConfigTabla !== null) {
        $inicioRelojTabla = new DateTime($relojArenaConfigTabla['fecha_inicio']);
        $cierreRelojTabla = new DateTime($relojArenaConfigTabla['fecha_cierre']);
        $hoyRelojTabla = new DateTime('today');

        if ($hoyRelojTabla < $inicioRelojTabla) {
            $diasFaltantesRelojTabla = (int) $inicioRelojTabla->diff($cierreRelojTabla)->days;
        } elseif ($hoyRelojTabla > $cierreRelojTabla) {
            $diasFaltantesRelojTabla = 0;
        } else {
            $diasFaltantesRelojTabla = (int) $hoyRelojTabla->diff($cierreRelojTabla)->days;
        }
    }
}

$nombresMeses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

if ($modoDemo) {
    // Array chico y fijo (siempre el mismo al recargar) para poder probar Ver/Editar/Eliminar/
    // Guardar/Enviar de principio a fin sin arriesgar datos reales. Vive solo en el navegador: se
    // reinicia si recargas la página.
    $columnas = ['Insumo', 'Cantidad', 'Precio unitario', 'Total', 'Estado'];
    $datosDemo = [
        ['id' => 1, 'insumo' => 'Resma de papel', 'cantidad' => 10, 'precio' => 15000, 'estado' => 'Pendiente'],
        ['id' => 2, 'insumo' => 'Marcador borrable', 'cantidad' => 20, 'precio' => 3000, 'estado' => 'Pendiente'],
        ['id' => 3, 'insumo' => 'Caja de lapiceros', 'cantidad' => 5, 'precio' => 12000, 'estado' => 'Pendiente'],
        ['id' => 4, 'insumo' => 'Carpeta AZ', 'cantidad' => 8, 'precio' => 7000, 'estado' => 'Pendiente'],
        ['id' => 5, 'insumo' => 'Grapadora', 'cantidad' => 3, 'precio' => 18000, 'estado' => 'Pendiente'],
    ];

    $filas = array_map(static function (array $d): array {
        return [
            $d['insumo'],
            (string) $d['cantidad'],
            '$' . number_format($d['precio'], 2, ',', '.'),
            '$' . number_format($d['cantidad'] * $d['precio'], 2, ',', '.'),
            $d['estado'],
        ];
    }, $datosDemo);
} else {
    $columnas = ['Sede', 'Dependencia', 'Proyecto PDI', 'Contratos comunes', 'Actividad', 'Rubro', 'Insumo', 'Cantidad', 'Costo unitario', 'Valor total', 'Meses'];

    $filas = array_map(static function (array $g) use ($nombresMeses): array {
        $meses = $g['meses'] !== '' ? implode(', ', array_map(static fn ($m) => $nombresMeses[(int) $m] ?? $m, explode(',', $g['meses']))) : '';

        return [
            $g['sede_codigo'] . ' - ' . $g['sede_nombre'],
            $g['dependencia'],
            $g['proyecto_codigo'] . ' - ' . $g['proyecto_nombre'],
            (string) ($g['objeto_proyecto_paa'] ?? ''),
            $g['actividad'],
            $g['rubro_id'] !== null ? $g['rubro_codigo'] . ' - ' . $g['rubro_descripcion'] : ($g['rubro_texto'] ?? '—'),
            $g['insumo'],
            number_format((float) $g['cantidad'], 0, ',', '.'),
            '$' . number_format((float) $g['costo_unitario'], 2, ',', '.'),
            '$' . number_format((float) $g['valor_total'], 2, ',', '.'),
            $meses,
        ];
    }, $gastos);
}

// Valores únicos por columna, para alimentar el <datalist> del filtro-combobox de cada una.
$valoresPorColumna = array_fill(0, count($columnas), []);
foreach ($filas as $fila) {
    foreach ($fila as $indice => $valor) {
        if ($valor !== '') {
            $valoresPorColumna[$indice][$valor] = true;
        }
    }
}
foreach ($valoresPorColumna as $indice => $valores) {
    $valoresPorColumna[$indice] = array_keys($valores);
    sort($valoresPorColumna[$indice], SORT_NATURAL | SORT_FLAG_CASE);
}

// Valores únicos de TODA la tabla (todas las columnas juntas), para el <datalist> de la búsqueda
// general del grupo básico.
$valoresUnicosTabla = array_keys(array_merge(...array_map(static fn (array $v): array => array_fill_keys($v, true), $valoresPorColumna ?: [[]])));
sort($valoresUnicosTabla, SORT_NATURAL | SORT_FLAG_CASE);

// Ancho inicial por columna (se puede arrastrar después): ~8px por carácter del valor más largo
// (encabezado incluido), entre 90 y 320px.
$anchosColumna = [];
foreach ($columnas as $indice => $columna) {
    $maxLargo = mb_strlen($columna);
    foreach ($filas as $fila) {
        $maxLargo = max($maxLargo, mb_strlen((string) ($fila[$indice] ?? '')));
    }
    $anchosColumna[$indice] = max(90, min(320, $maxLargo * 8 + 40));
}

$tituloPagina = 'Dev · Tabla';
require __DIR__ . '/../../parciales/encabezado.php';
?>

<style>
    /* --- Prototipo "Tabla": aparte del resto de estilos, no toca .tarjeta ni componentes reales.
       Estas reglas son un <style> local a esta página, así que NO afectan el resto del sistema
       (encabezado.php/estilo.css siguen igual en cualquier otra vista). --- */

    /* Headerbar y workspace a la mitad del padding habitual (0.9rem 2rem y 2rem respectivamente),
       y toda la cadena de alto (html/body → layout → contenido → workspace → tarjeta → tabla)
       fijada a 100vh (no solo min-height) para que la página en sí NUNCA haga scroll: el único
       que se desborda hacia abajo es .tabla-scroll, con su propia barra. El padding del workspace
       queda parejo en los 4 lados (no solo arriba/lados). */
    html, body {
        height: 100%;
        overflow: hidden;
    }

    .layout {
        height: 100vh;
        min-height: 0;
    }

    .contenido-principal {
        min-height: 0;
    }

    .encabezado {
        padding: 0.45rem 1rem;
        flex-shrink: 0;
    }

    .area-contenido {
        display: flex;
        flex-direction: column;
        padding: 1rem;
        min-height: 0;
        flex: 1;
    }

    .tarjeta-tabla {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        padding: 0.5rem;
    }

    .tabla-topbar {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.6rem;
        flex-wrap: wrap;
    }

    .tabla-boton-volver {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        flex-shrink: 0;
        border-radius: 999px;
        border: 1px solid var(--color-borde);
        background: var(--color-superficie);
        color: var(--color-texto);
        cursor: pointer;
        transition: background var(--transicion), border-color var(--transicion);
    }

    .tabla-boton-volver:hover {
        background: var(--color-fondo);
        border-color: var(--color-borde-hover);
    }

    .tabla-nombre {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--color-texto);
        white-space: nowrap;
    }

    .tabla-acciones {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-left: auto;
        flex-wrap: wrap;
    }

    .grupo-iconos {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.15rem;
        padding: 0.2rem;
        background: var(--color-fondo);
        border-radius: 999px;
    }

    /* Un grupo sin botones (ej. "específico", reservado) no se muestra como píldora vacía */
    .grupo-iconos:empty {
        display: none;
    }

    .grupo-iconos.grupo-datos {
        background: var(--color-gris-fondo);
    }

    .icono-boton {
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
        text-decoration: none;
        transition: background var(--transicion), color var(--transicion);
    }

    .grupo-iconos.grupo-datos .icono-boton {
        color: var(--color-gris-texto);
    }

    .grupo-iconos.grupo-datos .icono-boton.icono-exportar {
        color: var(--color-primario);
    }

    .icono-boton:hover {
        background: rgba(0, 0, 0, 0.08);
    }

    .icono-boton.activo {
        background: var(--color-primario-suave);
        color: var(--color-primario);
    }

    .icono-boton:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .icono-boton:disabled:hover {
        background: transparent;
    }

    /* Los grupos se colapsan a un menú desplegable SOLO cuando no caben en el ancho disponible
       (pantallas angostas): en vez de mostrar los iconos Y el menú a la vez (duplicado), a partir
       de este punto de quiebre se ocultan los iconos individuales y aparece únicamente el botón
       "⋯", que abre el mismo grupo de acciones como menú. */
    .grupo-iconos-colapsar {
        display: none;
        color: var(--color-texto-tenue);
    }

    @media (max-width: 900px) {
        .grupo-iconos:not(.grupo-vista) > .icono-boton:not(.grupo-iconos-colapsar) {
            display: none;
        }

        .grupo-iconos-colapsar {
            display: inline-flex;
        }
    }

    .grupo-iconos-menu {
        position: absolute;
        top: calc(100% + 0.35rem);
        right: 0;
        display: none;
        flex-direction: column;
        min-width: 170px;
        background: var(--color-superficie);
        border: 1px solid var(--color-borde);
        border-radius: var(--radio-chico);
        box-shadow: var(--sombra);
        padding: 0.3rem;
        z-index: 6;
    }

    .grupo-iconos-menu.abierto {
        display: flex;
    }

    .grupo-iconos-menu button,
    .grupo-iconos-menu a {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.4rem 0.55rem;
        border: none;
        background: transparent;
        border-radius: var(--radio-chico);
        text-align: left;
        text-decoration: none;
        font-size: 0.82rem;
        color: var(--color-texto);
        cursor: pointer;
    }

    .grupo-iconos-menu button:hover {
        background: var(--color-fondo);
    }

    .grupo-boton-principal {
        display: flex;
        border-radius: 999px;
        overflow: hidden;
        box-shadow: var(--sombra);
    }

    .boton-principal-azul {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0 1rem;
        height: 34px;
        border: none;
        background: var(--color-primario);
        color: #fff;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: background var(--transicion);
    }

    .boton-principal-azul:hover {
        background: var(--color-primario-oscuro);
    }

    .boton-enviar-icono {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 34px;
        border: none;
        border-left: 1px solid rgba(255, 255, 255, 0.35);
        background: var(--color-gris-texto);
        color: #fff;
        cursor: not-allowed;
        transition: background var(--transicion);
    }

    .boton-enviar-icono.habilitado {
        background: var(--color-exito-texto);
        cursor: pointer;
    }

    .tabla-scroll {
        flex: 1;
        min-height: 0;
        overflow: auto;
        border: 1px solid var(--color-borde);
        border-radius: var(--radio-chico);
        /* Scrollbar invisible hasta que se necesita Y el mouse está encima (Firefox) */
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    .tabla-scroll:hover {
        scrollbar-color: var(--color-borde-hover) transparent;
    }

    /* Chrome/Edge/Safari: mismo criterio, vía pseudo-elementos */
    .tabla-scroll::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .tabla-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .tabla-scroll::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 999px;
        border: 2px solid transparent;
        background-clip: content-box;
    }

    .tabla-scroll:hover::-webkit-scrollbar-thumb {
        background-color: var(--color-borde-hover);
        background-clip: content-box;
    }

    .tabla-scroll::-webkit-scrollbar-thumb:hover {
        background-color: var(--color-texto-tenue);
        background-clip: content-box;
    }

    .tabla-dev-datos {
        border-collapse: collapse;
        table-layout: fixed;
        width: max-content;
        font-size: 0.82rem;
    }

    .tabla-dev-datos th,
    .tabla-dev-datos td {
        min-width: 0;
        padding: 0.5rem 0.6rem;
        border-bottom: 1px solid var(--color-borde);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
    }

    .redimensionador-columna {
        position: absolute;
        top: 0;
        right: 0;
        width: 6px;
        height: 100%;
        cursor: col-resize;
        z-index: 3;
        touch-action: none;
    }

    /* Línea delgada SIEMPRE visible, justo en el borde real de la columna (a diferencia del área
       de agarre completa, que se mantiene invisible hasta el hover) para poder encontrarla sin
       tener que adivinar dónde está. */
    .redimensionador-columna::after {
        content: '';
        position: absolute;
        top: 15%;
        bottom: 15%;
        right: 0;
        width: 1px;
        background: var(--color-borde);
    }

    .redimensionador-columna:hover,
    .redimensionador-columna.redimensionando {
        background: var(--color-primario);
        opacity: 0.5;
    }

    .redimensionador-columna:hover::after,
    .redimensionador-columna.redimensionando::after {
        background: transparent;
    }

    body.tabla-redimensionando-cursor,
    body.tabla-redimensionando-cursor * {
        cursor: col-resize !important;
        user-select: none !important;
    }

    .tabla-dev-datos thead th {
        position: sticky;
        background: var(--color-superficie);
        z-index: 2;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--color-texto-tenue);
    }

    .tabla-dev-datos thead tr.fila-encabezados th {
        top: 0;
    }

    .tabla-dev-datos thead tr.fila-filtros th {
        padding: 0.3rem 0.5rem;
    }

    /* Los filtros están apagados (fila oculta) hasta que se invocan con el botón "Filtrar" */
    .tabla-dev-datos thead tr.fila-filtros {
        display: none;
    }

    .tabla-dev-datos thead tr.fila-filtros.visible {
        display: table-row;
    }


    .col-seleccion {
        width: 34px;
        text-align: center !important;
    }

    .encabezado-columna {
        position: relative;
        z-index: 4;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        cursor: pointer;
        user-select: none;
    }

    .boton-ordenar {
        border: none;
        background: transparent;
        color: var(--color-texto-tenue);
        cursor: pointer;
        padding: 0;
        display: inline-flex;
    }

    .encabezado-columna.orden-asc .boton-ordenar,
    .encabezado-columna.orden-desc .boton-ordenar {
        color: var(--color-primario);
    }

    .separador-grupo-basico {
        width: 1px;
        height: 18px;
        background: var(--color-borde);
        margin: 0 0.15rem;
        flex-shrink: 0;
    }

    /* Pestaña sin equivalente real en esta tabla de referencia: se ve, pero no simula ser
       clicable (cursor y sin el hover que sí tienen las pestañas de verdad). */
    .pestana[aria-disabled="true"] {
        cursor: not-allowed;
        opacity: 0.55;
    }

    .pestana[aria-disabled="true"]:hover {
        color: var(--color-texto-tenue);
    }

    /* --- Franja de contexto compacta: la barra de color hace de separador entre la topbar y las
       pestañas (no un bloque .progreso-presupuesto aparte), y la explicación de los colores +
       reloj de arena comparten la misma fila angosta que las pestañas, a la derecha. --- */
    .tabla-separador-progreso {
        height: 4px;
        background: var(--color-fondo);
        margin: 0 0 0.5rem;
        overflow: hidden;
    }

    .tabla-separador-progreso-relleno {
        height: 100%;
        background: var(--color-primario);
        transition: width 0.3s ease;
    }

    .tabla-franja-pestanas {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .tabla-franja-pestanas .pestanas {
        margin-bottom: 0;
        border-bottom: none;
    }

    .tabla-franja-pestanas .pestana {
        padding: 0.35rem 0.7rem;
        font-size: 0.8rem;
    }

    .tabla-leyenda-compacta {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        font-size: 0.75rem;
        color: var(--color-texto-tenue);
        white-space: nowrap;
    }

    .tabla-leyenda-item {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .tabla-leyenda-punto {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .tabla-leyenda-punto-gastado {
        background: var(--color-primario);
    }

    .tabla-leyenda-punto-disponible {
        background: var(--color-borde-hover);
    }

    .tabla-nota-demo {
        margin: 0 0 0.6rem;
        font-size: 0.8rem;
    }

    /* --- Modal reutilizado para Ver/Editar en modo demo --- */
    .modal-demo-campo {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        margin-bottom: 0.9rem;
    }

    .modal-demo-campo label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--color-texto-secundario);
    }

    .modal-demo-campo input {
        padding: 0.5rem 0.65rem;
        border: 1px solid var(--color-borde);
        border-radius: var(--radio-chico);
        font-size: 0.85rem;
    }

    .modal-demo-campo input:focus {
        outline: none;
        border-color: var(--color-primario);
    }

    .tabla-toast {
        position: fixed;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        background: var(--color-texto);
        color: #fff;
        padding: 0.6rem 1.1rem;
        border-radius: var(--radio-chico);
        font-size: 0.85rem;
        box-shadow: var(--sombra);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease, transform 0.2s ease;
        z-index: 50;
    }

    .tabla-toast.visible {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* --- Combobox propio: predicción en gris dentro del mismo input (ghost text) para
       Buscar y Filtrar, y tarjeta desplegable con los valores únicos solo para Filtrar --- */
    .combo-envoltorio {
        position: relative;
        font-size: 0.78rem;
    }

    .combo-envoltorio.combo-filtro {
        width: 100%;
        min-width: 90px;
    }

    .combo-fantasma {
        box-sizing: border-box;
        height: 32px;
        padding: 0.3rem 0.5rem;
        border: 1px solid var(--color-borde);
        border-radius: var(--radio-chico);
        background: var(--color-superficie);
        white-space: pre;
        line-height: 1.4;
        overflow: hidden;
        pointer-events: none;
    }

    .combo-fantasma-tecleado {
        color: transparent;
    }

    .combo-fantasma-sugerencia {
        color: var(--color-texto-tenue);
    }

    .combo-input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        padding: 0.3rem 0.5rem;
        border: 1px solid transparent;
        border-radius: var(--radio-chico);
        background: transparent;
        font-size: inherit;
        font-family: inherit;
        color: var(--color-texto);
        box-sizing: border-box;
    }

    .combo-input:focus {
        outline: none;
    }

    .combo-envoltorio:focus-within .combo-fantasma {
        border-color: var(--color-primario);
    }

    .combo-tarjeta {
        position: absolute;
        top: calc(100% + 0.3rem);
        left: 0;
        right: 0;
        display: none;
        flex-direction: column;
        max-height: 220px;
        overflow-y: auto;
        background: var(--color-superficie);
        border: 1px solid var(--color-borde);
        border-radius: var(--radio-chico);
        box-shadow: var(--sombra);
        padding: 0.3rem;
        z-index: 8;
    }

    .combo-tarjeta.abierta {
        display: flex;
    }

    .combo-tarjeta-item {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.35rem 0.5rem;
        border: none;
        background: transparent;
        border-radius: var(--radio-chico);
        font-size: 0.8rem;
        color: var(--color-texto);
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .combo-tarjeta-item:hover {
        background: var(--color-fondo);
    }

    .combo-tarjeta-vacio {
        padding: 0.4rem 0.5rem;
        font-size: 0.78rem;
        color: var(--color-texto-tenue);
    }

    /* --- Mostrar/ocultar columnas: reutiliza el look de .combo-tarjeta, pero angosta a su
       botón (no al ancho del ícono) y con checkboxes en vez de opciones de una sola selección --- */
    .columnas-envoltorio {
        position: relative;
    }

    .columnas-tarjeta {
        left: 0;
        right: auto;
        min-width: 210px;
    }

    .columnas-tarjeta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.5rem;
        border-radius: var(--radio-chico);
        font-size: 0.8rem;
        color: var(--color-texto);
        cursor: pointer;
        white-space: nowrap;
    }

    .columnas-tarjeta-item:hover {
        background: var(--color-fondo);
    }

    .buscar-envoltorio {
        position: relative;
        display: flex;
        align-items: center;
    }

    .combo-envoltorio.combo-buscar {
        width: 0;
        opacity: 0;
        overflow: hidden;
        transition: width 0.2s ease, opacity 0.15s ease, margin-left 0.2s ease;
    }

    .combo-envoltorio.combo-buscar .combo-fantasma {
        border-radius: 999px;
        white-space: nowrap;
    }

    .buscar-envoltorio.desplegado .combo-envoltorio.combo-buscar {
        width: 190px;
        opacity: 1;
        margin-left: 0.3rem;
    }

    .tabla-dev-datos tbody tr.fila-oculta-filtro {
        display: none;
    }

    .tabla-dev-datos tbody tr:hover {
        background: var(--color-fondo);
    }

    .tabla-dev-datos tbody tr.fila-seleccionada {
        background: var(--color-primario-suave);
    }

    /* --- Vista de gráfica: dashboard de decisión (respeta encabezados/filtros de arriba, solo
    reemplaza las filas del cuerpo). Los encabezados y la fila de filtros del <thead> siguen
    visibles y funcionando; el <tbody> se oculta y este bloque ocupa su lugar. ---
    Paleta categórica (identidad de cada porción/serie en la torta): 8 tonos validados para
    distinguirse entre sí incluso con daltonismo; con más de 8 categorías el ciclo se repite
    (a pedido: sin límite de categorías ni "Otros"), por eso la leyenda y el tooltip siempre
    llevan la etiqueta y el valor — nunca dependen solo del color. */
    .tabla-grafica {
        --serie-1: #2a78d6;
        --serie-2: #eb6834;
        --serie-3: #1baf7a;
        --serie-4: #eda100;
        --serie-5: #e87ba4;
        --serie-6: #008300;
        --serie-7: #4a3aa7;
        --serie-8: #e34948;
        display: none;
        flex-direction: column;
        min-height: 0;
        padding: 0.75rem 0.25rem 0.5rem;
    }

    #tabla-dev-datos.modo-grafica tbody {
        display: none;
    }

    #tabla-dev-datos.modo-grafica ~ .tabla-grafica {
        display: flex;
    }

    .tabla-grafica-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.5rem;
        margin-bottom: 0.6rem;
        flex-shrink: 0;
    }

    .grafica-stat {
        background: var(--color-fondo);
        border-radius: var(--radio-chico);
        padding: 0.45rem 0.65rem;
    }

    .grafica-stat-etiqueta {
        margin: 0 0 0.15rem;
        font-size: 0.7rem;
        color: var(--color-texto-tenue);
    }

    .grafica-stat-valor {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--color-texto);
    }

    .tabla-grafica-paneles {
        flex: 1;
        min-height: 0;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        grid-template-rows: minmax(0, 2fr) minmax(0, 1fr);
        gap: 0.75rem;
    }

    .grafica-panel {
        background: var(--color-fondo);
        border-radius: var(--radio-chico);
        padding: 0.8rem 1rem 0.9rem;
        display: flex;
        flex-direction: column;
        min-height: 0;
        cursor: zoom-in;
    }

    .grafica-panel-cabecera {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .grafica-panel-titulo {
        margin: 0 0.1rem 0.15rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--color-texto);
    }

    .grafica-panel-subtitulo {
        margin: 0 0.1rem 0.6rem;
        font-size: 0.76rem;
        color: var(--color-texto-tenue);
        flex-shrink: 0;
    }

    .grafica-panel-vacio {
        margin: 0;
        font-size: 0.85rem;
        color: var(--color-texto-tenue);
    }

    /* --- Alternador barras/torta: mismo patrón visual que .icono-boton, en miniatura --- */
    .grafica-tipo-toggle {
        display: inline-flex;
        border-radius: 999px;
        background: var(--color-superficie);
        border: 1px solid var(--color-borde);
        flex-shrink: 0;
    }

    .grafica-tipo-boton {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border: none;
        background: transparent;
        border-radius: 999px;
        color: var(--color-texto-secundario);
        cursor: pointer;
    }

    .grafica-tipo-boton.activo {
        background: var(--color-primario);
        color: #fff;
    }

    .grafica-panel-cuerpo {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
    }

    .grafica-fila-barra {
        display: grid;
        grid-template-columns: minmax(70px, 120px) 1fr auto;
        align-items: center;
        gap: 0.6rem;
        padding: 0.25rem 0.1rem;
        border-radius: 4px;
    }

    .grafica-barra-etiqueta {
        font-size: 0.8rem;
        color: var(--color-texto-secundario);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .grafica-barra-pista {
        height: 18px;
        border-radius: 0 4px 4px 0;
        overflow: hidden;
    }

    .grafica-barra-relleno {
        height: 100%;
        min-width: 3px;
        background: var(--color-primario);
        border-radius: 0 4px 4px 0;
        transition: filter var(--transicion);
    }

    .grafica-fila-barra:hover .grafica-barra-relleno,
    .grafica-fila-barra:focus-visible .grafica-barra-relleno {
        filter: brightness(0.85);
    }

    .grafica-fila-barra:focus-visible {
        outline: 2px solid var(--color-primario);
        outline-offset: 1px;
    }

    .grafica-barra-valor {
        font-size: 0.78rem;
        color: var(--color-texto-secundario);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    /* --- Torta: SVG a la izquierda + leyenda desplazable a la derecha (nunca se corta la lista) --- */
    .grafica-torta-cuerpo {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        height: 100%;
    }

    .grafica-torta-svg {
        flex-shrink: 0;
    }

    .grafica-torta-porcion {
        cursor: pointer;
        transition: filter var(--transicion), opacity var(--transicion);
        stroke: var(--color-fondo);
        stroke-width: 1.5;
    }

    .grafica-torta-porcion:hover,
    .grafica-torta-porcion.resaltada {
        filter: brightness(0.88);
    }

    .grafica-torta-leyenda {
        flex: 1;
        min-height: 0;
        min-width: 0;
        height: 100%;
        overflow-y: auto;
    }

    .grafica-torta-leyenda-fila {
        display: grid;
        grid-template-columns: 10px 1fr auto;
        align-items: center;
        gap: 0.5rem;
        padding: 0.2rem 0.3rem;
        border-radius: 4px;
        cursor: pointer;
    }

    .grafica-torta-leyenda-fila:hover,
    .grafica-torta-leyenda-fila.resaltada {
        background: rgba(0, 0, 0, 0.05);
    }

    .grafica-torta-leyenda-punto {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .grafica-torta-leyenda-etiqueta {
        font-size: 0.78rem;
        color: var(--color-texto-secundario);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .grafica-torta-leyenda-valor {
        font-size: 0.76rem;
        color: var(--color-texto-tenue);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    /* --- Línea (PAC estimado por mes): el valor de cada fila se reparte entre sus meses de
    ejecución y se suma por mes — Ene a Dic siempre en orden cronológico, nunca por magnitud. --- */
    .grafica-linea-cuerpo {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .grafica-linea-svg {
        width: 100%;
        flex: 1;
        min-height: 0;
    }

    .grafica-linea-base {
        stroke: var(--color-borde);
        stroke-width: 1;
    }

    .grafica-linea-trazo {
        stroke: var(--color-primario);
        stroke-width: 2;
        stroke-linejoin: round;
        stroke-linecap: round;
    }

    .grafica-linea-punto {
        fill: var(--color-primario);
        stroke: var(--color-fondo);
        stroke-width: 1.5;
        cursor: pointer;
        transition: r var(--transicion);
    }

    .grafica-linea-punto:hover,
    .grafica-linea-punto:focus {
        r: 6;
        outline: none;
    }

    .grafica-linea-meses-etiquetas {
        display: flex;
        justify-content: space-between;
        padding: 0.2rem 8px 0;
        flex-shrink: 0;
    }

    .grafica-linea-mes-etiqueta {
        font-size: 0.66rem;
        color: var(--color-texto-tenue);
    }

    .grafica-tooltip {
        position: fixed;
        display: none;
        max-width: 260px;
        padding: 0.45rem 0.65rem;
        border-radius: 6px;
        background: var(--color-texto);
        color: #fff;
        font-size: 0.78rem;
        line-height: 1.4;
        pointer-events: none;
        z-index: 90;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.22);
    }

    .grafica-tooltip strong {
        font-variant-numeric: tabular-nums;
    }

    /* --- Panel ampliado en el propio lugar: en vez de un popup (tapaba los filtros), doble clic
    (o el ícono de ampliar) oculta los otros 3 paneles y este ocupa toda la cuadrícula — los
    encabezados y filtros de arriba nunca se cubren, porque todo pasa dentro del área normal
    del dashboard. Doble clic de nuevo (o "restaurar") vuelve a las 4 gráficas. --- */
    .tabla-grafica-paneles.un-panel {
        grid-template-columns: 1fr;
        grid-template-rows: 1fr;
    }

    .grafica-panel.expandido .grafica-torta-svg {
        width: 260px;
        height: 260px;
    }

    .grafica-panel.expandido .grafica-fila-barra {
        grid-template-columns: minmax(120px, 220px) 1fr auto;
        padding: 0.4rem 0.2rem;
    }

    .grafica-panel.expandido .grafica-barra-pista {
        height: 22px;
    }
</style>

<div class="tarjeta tarjeta-tabla">
    <div class="tabla-topbar">
        <button type="button" class="tabla-boton-volver" title="Volver" onclick="window.location.href='index.php?ruta=dev'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>

        <h2 class="tabla-nombre">Gastos (referencia)</h2>

        <div class="tabla-acciones">
            <div class="grupo-iconos grupo-basico" data-grupo="basico">
                <?php if (!empty($filas)): ?>
                <div class="buscar-envoltorio">
                    <button type="button" class="icono-boton" id="tabla-boton-buscar" title="Buscar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                    <div class="combo-envoltorio combo-buscar" id="tabla-combo-buscar" data-valores="<?= htmlspecialchars(json_encode($valoresUnicosTabla)) ?>">
                        <div class="combo-fantasma" aria-hidden="true"></div>
                        <input type="text" id="tabla-campo-buscar" class="combo-input" placeholder="Buscar en la tabla…" autocomplete="off">
                    </div>
                </div>
                <button type="button" class="icono-boton" id="tabla-boton-filtrar" title="Filtrar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                </button>
                <div class="columnas-envoltorio">
                    <button type="button" class="icono-boton" id="tabla-boton-columnas" title="Mostrar u ocultar columnas">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="18" rx="1"></rect><rect x="14" y="3" width="7" height="18" rx="1"></rect></svg>
                    </button>
                    <div class="combo-tarjeta columnas-tarjeta" id="tabla-columnas-tarjeta">
                        <?php foreach ($columnas as $indice => $columna): ?>
                        <label class="columnas-tarjeta-item">
                            <input type="checkbox" class="columnas-checkbox" data-indice="<?= $indice ?>" checked>
                            <?= htmlspecialchars($columna) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <span class="separador-grupo-basico"></span>
                <?php endif; ?>
                <button type="button" class="icono-boton activo" id="tabla-boton-vista-tabla" title="Vista de tabla" aria-pressed="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="9" x2="9" y2="21"></line></svg>
                </button>
                <button type="button" class="icono-boton" id="tabla-boton-vista-grafica" title="Vista de gráfica (dashboard)" aria-pressed="false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </button>
                <span class="separador-grupo-basico"></span>
                <button type="button" class="icono-boton" id="tabla-boton-editar" title="Editar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                <button type="button" class="icono-boton" id="tabla-boton-eliminar" title="Eliminar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                </button>
                <button type="button" class="icono-boton grupo-iconos-colapsar" title="Más acciones" data-toggle-menu>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle></svg>
                </button>
                <div class="grupo-iconos-menu">
                    <button type="button" id="tabla-boton-vista-tabla-menu">Vista de tabla</button>
                    <button type="button" id="tabla-boton-vista-grafica-menu">Vista de gráfica</button>
                    <button type="button" id="tabla-boton-editar-menu" disabled>Editar</button>
                    <button type="button" id="tabla-boton-eliminar-menu" disabled>Eliminar</button>
                </div>
            </div>

            <!-- Reservado: acciones propias de esta tabla en particular (cuando aplique). Vacío no se muestra (ver .grupo-iconos:empty). -->
            <div class="grupo-iconos grupo-especifico" data-grupo="especifico"></div>

            <div class="grupo-iconos grupo-datos" data-grupo="datos">
                <a href="index.php?ruta=gastos-exportar-plantilla" class="icono-boton" title="Exportar plantilla">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </a>
                <button type="button" class="icono-boton" title="Importar" id="tabla-boton-importar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
                <input type="file" id="tabla-archivo-importar" accept=".xlsx" hidden>
                <a href="index.php?ruta=gastos-exportar" class="icono-boton icono-exportar" title="Exportar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                </a>
                <button type="button" class="icono-boton grupo-iconos-colapsar" title="Más acciones" data-toggle-menu>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle></svg>
                </button>
                <div class="grupo-iconos-menu">
                    <a href="index.php?ruta=gastos-exportar-plantilla">Exportar plantilla</a>
                    <button type="button" id="tabla-boton-importar-menu">Importar</button>
                    <a href="index.php?ruta=gastos-exportar">Exportar</a>
                </div>
            </div>

            <div class="grupo-boton-principal" data-grupo="principal">
                <button type="button" class="boton-principal-azul" id="tabla-boton-guardar" title="Guardar">Guardar</button>
                <button type="button" class="boton-enviar-icono" id="tabla-boton-enviar" title="Selecciona al menos una fila para enviar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
        </div>
    </div>

    <?php if ($modoDemo): ?>
    <p class="texto-atenuado tabla-nota-demo">
        Modo demo: array chico y fijo (5 insumos), solo en el navegador. Ver, Editar, Eliminar,
        Guardar y Enviar funcionan de verdad aquí — recarga la página para reiniciarlo.
    </p>
    <?php else: ?>
    <!-- Franja de contexto entre la topbar y la tabla: la barra de color hace de separador (no de
    bloque aparte), y las pestañas + la explicación de los colores comparten la misma fila
    angosta, para no desperdiciar espacio vertical. -->
    <?php if ($presupuestoAnioTabla > 0): ?>
    <div class="tabla-separador-progreso" title="<?= number_format($porcentajeGastadoTabla, 1) ?>% del <?= $dependenciaUsuarioEsRaizTabla ? 'presupuesto' : 'techo' ?> <?= (int) $anioSeleccionado['anio'] ?> ejecutado">
        <div class="tabla-separador-progreso-relleno" style="width: <?= number_format($porcentajeGastadoTabla, 2, '.', '') ?>%;"></div>
    </div>
    <?php endif; ?>

    <div class="tabla-franja-pestanas">
        <div class="pestanas">
            <span class="pestana activa">Gastos</span>
            <span class="pestana" aria-disabled="true" title="Esta tabla de referencia no tiene un módulo de Ingresos equivalente">Ingresos</span>
        </div>

        <div class="tabla-leyenda-compacta">
            <?php if ($diasFaltantesRelojTabla !== null): ?>
            <span class="tabla-leyenda-item" title="Reloj de arena institucional: <?= htmlspecialchars($relojArenaConfigTabla['fecha_inicio']) ?> al <?= htmlspecialchars($relojArenaConfigTabla['fecha_cierre']) ?>">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12"></path><path d="M6 22h12"></path><path d="M6 2c0 6 12 6 12 12s-12 6-12 12"></path><path d="M18 2c0 6-12 6-12 12s12 6 12 12"></path></svg>
                <?= $diasFaltantesRelojTabla ?> día<?= $diasFaltantesRelojTabla === 1 ? '' : 's' ?>
            </span>
            <?php endif; ?>
            <?php if ($presupuestoAnioTabla > 0): ?>
            <span class="tabla-leyenda-item"><span class="tabla-leyenda-punto tabla-leyenda-punto-gastado"></span>Gastado: $<?= number_format($totalGastadoTabla, 2, ',', '.') ?></span>
            <span class="tabla-leyenda-item"><span class="tabla-leyenda-punto tabla-leyenda-punto-disponible"></span>Disponible: $<?= number_format(max(0, $presupuestoAnioTabla - $totalGastadoTabla), 2, ',', '.') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($filas)): ?>
    <p class="texto-atenuado">No hay gastos en el año presupuestal activo para usar de referencia.</p>
    <?php else: ?>
    <div class="tabla-scroll">
        <table class="tabla-dev-datos" id="tabla-dev-datos" data-modo-demo="<?= $modoDemo ? '1' : '0' ?>">
            <colgroup>
                <col style="width: 34px;">
                <?php foreach ($columnas as $indice => $columna): ?>
                <col id="tabla-col-<?= $indice ?>" style="width: <?= $anchosColumna[$indice] ?>px;">
                <?php endforeach; ?>
            </colgroup>
            <thead>
                <tr class="fila-encabezados">
                    <th class="col-seleccion"><input type="checkbox" id="tabla-seleccionar-todo" title="Seleccionar todo"></th>
                    <?php foreach ($columnas as $indice => $columna): ?>
                    <th>
                        <span class="encabezado-columna" data-indice="<?= $indice ?>">
                            <?= htmlspecialchars($columna) ?>
                            <button type="button" class="boton-ordenar" title="Ordenar por <?= htmlspecialchars($columna) ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 15 12 20 17 15"></polyline><polyline points="7 9 12 4 17 9"></polyline></svg>
                            </button>
                        </span>
                        <span class="redimensionador-columna" data-indice="<?= $indice ?>" title="Arrastra para cambiar el ancho"></span>
                    </th>
                    <?php endforeach; ?>
                </tr>
                <tr class="fila-filtros">
                    <th></th>
                    <?php foreach ($columnas as $indice => $columna): ?>
                    <th>
                        <div class="combo-envoltorio combo-filtro combo-filtro-columna" data-indice="<?= $indice ?>" data-valores="<?= htmlspecialchars(json_encode($valoresPorColumna[$indice])) ?>">
                            <div class="combo-fantasma" aria-hidden="true"></div>
                            <input type="text" class="filtro-columna combo-input" data-indice="<?= $indice ?>" placeholder="Filtrar…" autocomplete="off">
                            <div class="combo-tarjeta"></div>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $indiceFila => $fila): ?>
                <tr<?= $modoDemo ? ' data-id="' . (int) $datosDemo[$indiceFila]['id'] . '"' : '' ?>>
                    <td class="col-seleccion"><input type="checkbox" class="tabla-seleccion-fila"></td>
                    <?php foreach ($fila as $valor): ?>
                    <td><?= htmlspecialchars($valor) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="tabla-grafica" id="tabla-grafica" hidden>
            <div class="tabla-grafica-kpis" id="tabla-grafica-kpis"></div>
            <div class="tabla-grafica-paneles" id="tabla-grafica-paneles"></div>
        </div>

        <div class="grafica-tooltip" id="tabla-grafica-tooltip"></div>
    </div>
    <?php endif; ?>

    <?php if ($modoDemo): ?>
    <div class="modal-fondo" id="tabla-modal-demo">
        <div class="modal-caja" style="max-width: 420px;">
            <div class="modal-cabecera">
                <h2 id="tabla-modal-demo-titulo">Editar insumo</h2>
                <button type="button" class="modal-cerrar" id="tabla-modal-demo-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form id="tabla-modal-demo-formulario">
                <div class="modal-demo-campo">
                    <label for="tabla-modal-demo-insumo">Insumo</label>
                    <input type="text" id="tabla-modal-demo-insumo" required>
                </div>
                <div class="modal-demo-campo">
                    <label for="tabla-modal-demo-cantidad">Cantidad</label>
                    <input type="number" id="tabla-modal-demo-cantidad" min="1" required>
                </div>
                <div class="modal-demo-campo">
                    <label for="tabla-modal-demo-precio">Precio unitario</label>
                    <input type="number" id="tabla-modal-demo-precio" min="0" step="0.01" required>
                </div>
                <div class="modal-pie">
                    <button type="submit" class="boton-agregar">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="tabla-toast" id="tabla-toast"></div>
    <?php endif; ?>
</div>

<script>
var columnasTabla = <?php echo json_encode($columnas, JSON_UNESCAPED_UNICODE); ?>;

document.addEventListener('DOMContentLoaded', function () {
    // --- Colapsar cada grupo de iconos a un menú desplegable ---
    document.querySelectorAll('[data-toggle-menu]').forEach(function (boton) {
        var menu = boton.parentElement.querySelector('.grupo-iconos-menu');

        boton.addEventListener('click', function (evento) {
            evento.stopPropagation();
            var yaAbierto = menu.classList.contains('abierto');
            document.querySelectorAll('.grupo-iconos-menu.abierto').forEach(function (m) { m.classList.remove('abierto'); });
            menu.classList.toggle('abierto', !yaAbierto);
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.grupo-iconos-menu.abierto').forEach(function (m) { m.classList.remove('abierto'); });
    });

    // --- Combobox propio (reemplaza <input list>+<datalist> nativos): texto en gris que se
    // autocompleta dentro del mismo input, y opcionalmente (Filtrar) una tarjeta desplegable con
    // los valores únicos, como un <select>, al hacer clic. ---
    function crearCombobox(envoltorio, valores, desplegable) {
        var input = envoltorio.querySelector('.combo-input');
        var fantasma = envoltorio.querySelector('.combo-fantasma');
        var tarjeta = envoltorio.querySelector('.combo-tarjeta');
        var tecleado = document.createElement('span');
        var sugerenciaSpan = document.createElement('span');
        tecleado.className = 'combo-fantasma-tecleado';
        sugerenciaSpan.className = 'combo-fantasma-sugerencia';
        fantasma.appendChild(tecleado);
        fantasma.appendChild(sugerenciaSpan);

        function sugerenciaPara(texto) {
            if (texto === '') {
                return '';
            }
            var textoMin = texto.toLowerCase();
            var encontrado = valores.find(function (v) {
                return v.length > texto.length && v.toLowerCase().indexOf(textoMin) === 0;
            });
            return encontrado ? encontrado.slice(texto.length) : '';
        }

        function actualizarFantasma() {
            tecleado.textContent = input.value;
            sugerenciaSpan.textContent = sugerenciaPara(input.value);
        }

        function cerrarTarjeta() {
            if (tarjeta) {
                tarjeta.classList.remove('abierta');
            }
        }

        function renderTarjeta() {
            if (!tarjeta) {
                return;
            }

            var textoMin = input.value.toLowerCase();
            var coincidencias = valores.filter(function (v) { return v.toLowerCase().indexOf(textoMin) !== -1; });

            tarjeta.innerHTML = '';

            if (coincidencias.length === 0) {
                var vacio = document.createElement('div');
                vacio.className = 'combo-tarjeta-vacio';
                vacio.textContent = 'Sin coincidencias';
                tarjeta.appendChild(vacio);
                return;
            }

            coincidencias.slice(0, 50).forEach(function (valor) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'combo-tarjeta-item';
                item.textContent = valor;
                item.addEventListener('mousedown', function (evento) {
                    evento.preventDefault();
                    input.value = valor;
                    actualizarFantasma();
                    cerrarTarjeta();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
                tarjeta.appendChild(item);
            });
        }

        input.addEventListener('input', function () {
            actualizarFantasma();
            if (desplegable) {
                tarjeta.classList.add('abierta');
                renderTarjeta();
            }
        });

        input.addEventListener('keydown', function (evento) {
            var sugerencia = sugerenciaSpan.textContent;
            if (sugerencia && (evento.key === 'Tab' || evento.key === 'ArrowRight') && input.selectionStart === input.value.length) {
                evento.preventDefault();
                input.value += sugerencia;
                actualizarFantasma();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            } else if (evento.key === 'Escape') {
                cerrarTarjeta();
            }
        });

        if (desplegable) {
            input.addEventListener('click', function () {
                renderTarjeta();
                tarjeta.classList.add('abierta');
            });
            input.addEventListener('focus', function () {
                renderTarjeta();
                tarjeta.classList.add('abierta');
            });
            document.addEventListener('click', function (evento) {
                if (!envoltorio.contains(evento.target)) {
                    cerrarTarjeta();
                }
            });
        }

        envoltorio.actualizarFantasmaCombo = actualizarFantasma;
        actualizarFantasma();
    }

    document.querySelectorAll('.combo-filtro-columna').forEach(function (envoltorio) {
        crearCombobox(envoltorio, JSON.parse(envoltorio.dataset.valores || '[]'), true);
    });

    var comboBuscar = document.getElementById('tabla-combo-buscar');
    if (comboBuscar) {
        crearCombobox(comboBuscar, JSON.parse(comboBuscar.dataset.valores || '[]'), false);
    }

    var tabla = document.getElementById('tabla-dev-datos');

    if (!tabla) {
        return;
    }

    var modoDemo = tabla.dataset.modoDemo === '1';
    var cuerpo = tabla.querySelector('tbody');
    var filasOriginales = Array.prototype.slice.call(cuerpo.querySelectorAll('tr'));

    // --- Encabezados fijos: la fila de filtros se apila justo debajo de la de encabezados ---
    var filaEncabezados = tabla.querySelector('tr.fila-encabezados');
    var filaFiltros = tabla.querySelector('tr.fila-filtros');
    if (filaEncabezados && filaFiltros) {
        var altura = filaEncabezados.getBoundingClientRect().height;
        Array.prototype.forEach.call(filaFiltros.querySelectorAll('th'), function (celda) {
            celda.style.top = altura + 'px';
        });
    }

    // --- Redimensionar columnas: arrastra el borde derecho del encabezado (ancho vive en el
    // <col> de la columna, gracias a table-layout: fixed). El ancho final de cada una se recuerda
    // en localStorage, por índice de columna, para que sobreviva a recargar la página. ---
    var CLAVE_ANCHOS = 'dev_tabla_anchos_columnas';

    function leerAnchosGuardados() {
        try {
            return JSON.parse(localStorage.getItem(CLAVE_ANCHOS) || '{}');
        } catch (error) {
            return {};
        }
    }

    function guardarAncho(indice, ancho) {
        try {
            var anchos = leerAnchosGuardados();
            anchos[indice] = ancho;
            localStorage.setItem(CLAVE_ANCHOS, JSON.stringify(anchos));
        } catch (error) {
            // localStorage no disponible: el ancho simplemente no persiste
        }
    }

    // El ancho vive en el <col> (para el reparto inicial de table-layout: fixed), pero además se
    // fija con width + max-width en CADA celda de la columna (encabezado, filtro y todas las de
    // datos): algunos navegadores, sobre todo con encabezados position:sticky, dejan que el
    // contenido más largo empuje la celda más ancha que su <col> a pesar de table-layout: fixed;
    // max-width en la celda misma es lo que de verdad la obliga a encogerse por debajo de eso.
    function celdasDeColumna(indice) {
        var celdas = [];
        var encabezado = tabla.querySelector('.encabezado-columna[data-indice="' + indice + '"]');
        var filtro = tabla.querySelector('.combo-filtro-columna[data-indice="' + indice + '"]');

        if (encabezado) { celdas.push(encabezado.closest('th')); }
        if (filtro) { celdas.push(filtro.closest('th')); }

        cuerpo.querySelectorAll('tr td:nth-child(' + (parseInt(indice, 10) + 2) + ')').forEach(function (celda) {
            celdas.push(celda);
        });

        return celdas;
    }

    function fijarAnchoColumna(indice, anchoPx) {
        var col = document.getElementById('tabla-col-' + indice);
        if (col) {
            col.style.width = anchoPx + 'px';
        }

        celdasDeColumna(indice).forEach(function (celda) {
            celda.style.width = anchoPx + 'px';
            celda.style.maxWidth = anchoPx + 'px';
        });
    }

    // Los anchos iniciales (calculados en PHP) también se replican celda por celda desde el
    // primer render, no solo en el <col> — si no, la primera carga puede sufrir el mismo problema
    // que se arregla al arrastrar.
    var anchosGuardados = leerAnchosGuardados();
    tabla.querySelectorAll('colgroup col[id^="tabla-col-"]').forEach(function (col) {
        var indice = col.id.replace('tabla-col-', '');
        var ancho = Object.prototype.hasOwnProperty.call(anchosGuardados, indice)
            ? anchosGuardados[indice]
            : parseInt(col.style.width, 10);

        if (!isNaN(ancho)) {
            fijarAnchoColumna(indice, ancho);
        }
    });

    tabla.querySelectorAll('.redimensionador-columna').forEach(function (manija) {
        var col = document.getElementById('tabla-col-' + manija.dataset.indice);

        if (!col) {
            return;
        }

        manija.addEventListener('mousedown', function (evento) {
            evento.preventDefault();
            var xInicial = evento.clientX;
            var anchoInicial = col.getBoundingClientRect().width;

            manija.classList.add('redimensionando');
            document.body.classList.add('tabla-redimensionando-cursor');

            var anchoFinal = anchoInicial;

            function mover(eventoMover) {
                anchoFinal = Math.max(40, Math.round(anchoInicial + (eventoMover.clientX - xInicial)));
                fijarAnchoColumna(manija.dataset.indice, anchoFinal);
            }

            function soltar() {
                manija.classList.remove('redimensionando');
                document.body.classList.remove('tabla-redimensionando-cursor');
                document.removeEventListener('mousemove', mover);
                document.removeEventListener('mouseup', soltar);
                guardarAncho(manija.dataset.indice, anchoFinal);
            }

            document.addEventListener('mousemove', mover);
            document.addEventListener('mouseup', soltar);
        });
    });

    // --- Ocultar/mostrar columnas (con checkboxes) — el estado también se recuerda en
    // localStorage. Ocultar usa visibility:collapse en el <col>, que quita esa columna de TODAS
    // las filas (encabezados, filtros y datos) sin tocar el contenido de cada celda. ---
    var CLAVE_OCULTAS = 'dev_tabla_columnas_ocultas';

    function leerOcultasGuardadas() {
        try {
            return JSON.parse(localStorage.getItem(CLAVE_OCULTAS) || '[]');
        } catch (error) {
            return [];
        }
    }

    function guardarOcultas(indices) {
        try {
            localStorage.setItem(CLAVE_OCULTAS, JSON.stringify(indices));
        } catch (error) {
            // localStorage no disponible: la preferencia simplemente no persiste
        }
    }

    var botonColumnas = document.getElementById('tabla-boton-columnas');
    var tarjetaColumnas = document.getElementById('tabla-columnas-tarjeta');

    if (botonColumnas && tarjetaColumnas) {
        var casillasColumnas = tarjetaColumnas.querySelectorAll('.columnas-checkbox');

        function aplicarVisibilidadColumna(indice, visible) {
            var col = document.getElementById('tabla-col-' + indice);
            if (col) {
                col.style.visibility = visible ? '' : 'collapse';
            }
        }

        function indicesOcultosActuales() {
            return Array.prototype.filter.call(casillasColumnas, function (c) { return !c.checked; })
                .map(function (c) { return c.dataset.indice; });
        }

        leerOcultasGuardadas().forEach(function (indice) {
            aplicarVisibilidadColumna(indice, false);
            var casilla = tarjetaColumnas.querySelector('.columnas-checkbox[data-indice="' + indice + '"]');
            if (casilla) {
                casilla.checked = false;
            }
        });

        casillasColumnas.forEach(function (casilla) {
            casilla.addEventListener('change', function () {
                aplicarVisibilidadColumna(casilla.dataset.indice, casilla.checked);
                guardarOcultas(indicesOcultosActuales());
            });
        });

        botonColumnas.addEventListener('click', function (evento) {
            evento.stopPropagation();
            tarjetaColumnas.classList.toggle('abierta');
        });

        document.addEventListener('click', function (evento) {
            if (!botonColumnas.contains(evento.target) && !tarjetaColumnas.contains(evento.target)) {
                tarjetaColumnas.classList.remove('abierta');
            }
        });
    }

    // --- Los filtros quedan apagados (fila oculta) hasta que se invocan con "Filtrar" ---
    var botonFiltrar = document.getElementById('tabla-boton-filtrar');
    if (botonFiltrar && filaFiltros) {
        botonFiltrar.addEventListener('click', function () {
            var mostrar = !filaFiltros.classList.contains('visible');
            filaFiltros.classList.toggle('visible', mostrar);
            botonFiltrar.classList.toggle('activo', mostrar);

            if (!mostrar) {
                filaFiltros.querySelectorAll('.combo-filtro-columna').forEach(function (envoltorioFiltro) {
                    var campo = envoltorioFiltro.querySelector('.combo-input');
                    campo.value = '';
                    if (envoltorioFiltro.actualizarFantasmaCombo) { envoltorioFiltro.actualizarFantasmaCombo(); }
                });
                aplicarFiltros();
            }
        });
    }

    // --- Buscar: el ícono despliega un combobox propio (predicción en gris) que busca en
    // cualquier columna de la tabla, combinado con los filtros por columna si están activos ---
    var botonBuscar = document.getElementById('tabla-boton-buscar');
    var envoltorioBuscar = document.querySelector('.buscar-envoltorio');
    var campoBuscar = document.getElementById('tabla-campo-buscar');

    if (botonBuscar && envoltorioBuscar && campoBuscar) {
        botonBuscar.addEventListener('click', function () {
            var desplegar = !envoltorioBuscar.classList.contains('desplegado');
            envoltorioBuscar.classList.toggle('desplegado', desplegar);
            botonBuscar.classList.toggle('activo', desplegar);

            if (desplegar) {
                campoBuscar.focus();
            } else {
                campoBuscar.value = '';
                if (comboBuscar && comboBuscar.actualizarFantasmaCombo) { comboBuscar.actualizarFantasmaCombo(); }
                aplicarFiltros();
            }
        });

        campoBuscar.addEventListener('input', aplicarFiltros);
    }

    // --- Importar: dispara el selector de archivo real, pero no envía nada — es un prototipo
    // aislado, no se conecta a la importación real de Gastos hasta que se pida explícitamente. ---
    var archivoImportar = document.getElementById('tabla-archivo-importar');
    var abrirSelectorImportar = function () { archivoImportar.click(); };

    var botonImportar = document.getElementById('tabla-boton-importar');
    var botonImportarMenu = document.getElementById('tabla-boton-importar-menu');
    if (botonImportar) { botonImportar.addEventListener('click', abrirSelectorImportar); }
    if (botonImportarMenu) { botonImportarMenu.addEventListener('click', abrirSelectorImportar); }

    if (archivoImportar) {
        archivoImportar.addEventListener('change', function () {
            var nombre = archivoImportar.files[0] ? archivoImportar.files[0].name : '';
            if (nombre) {
                alert('Prueba de interfaz: se seleccionó "' + nombre + '". Este prototipo no importa datos reales todavía.');
            }
            archivoImportar.value = '';
        });
    }

    // --- Orden ---
    document.querySelectorAll('.encabezado-columna').forEach(function (encabezado) {
        encabezado.addEventListener('click', function () {
            var indice = parseInt(encabezado.dataset.indice, 10);
            var ascendente = !encabezado.classList.contains('orden-asc');

            document.querySelectorAll('.encabezado-columna').forEach(function (otro) {
                otro.classList.remove('orden-asc', 'orden-desc');
            });
            encabezado.classList.add(ascendente ? 'orden-asc' : 'orden-desc');

            var filas = Array.prototype.slice.call(cuerpo.querySelectorAll('tr'));
            filas.sort(function (filaA, filaB) {
                var textoA = filaA.children[indice + 1].textContent.trim();
                var textoB = filaB.children[indice + 1].textContent.trim();
                var numeroA = parseFloat(textoA.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.'));
                var numeroB = parseFloat(textoB.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.'));
                var comparacion;

                if (!isNaN(numeroA) && !isNaN(numeroB) && textoA !== '' && textoB !== '') {
                    comparacion = numeroA - numeroB;
                } else {
                    comparacion = textoA.localeCompare(textoB, 'es');
                }

                return ascendente ? comparacion : -comparacion;
            });

            filas.forEach(function (fila) { cuerpo.appendChild(fila); });
        });
    });

    // --- Filtro por columna + búsqueda general (una fila se muestra solo si pasa AMBOS) ---
    var filtros = tabla.querySelectorAll('.filtro-columna');

    function aplicarFiltros() {
        var activos = Array.prototype.map.call(filtros, function (campo) {
            return { indice: parseInt(campo.dataset.indice, 10), valor: campo.value.trim().toLowerCase() };
        }).filter(function (f) { return f.valor !== ''; });

        var busqueda = campoBuscar ? campoBuscar.value.trim().toLowerCase() : '';

        filasOriginales.forEach(function (fila) {
            var cumpleColumnas = activos.every(function (filtro) {
                var texto = fila.children[filtro.indice + 1].textContent.toLowerCase();
                return texto.indexOf(filtro.valor) !== -1;
            });

            var cumpleBusqueda = busqueda === '' || fila.textContent.toLowerCase().indexOf(busqueda) !== -1;

            fila.classList.toggle('fila-oculta-filtro', !(cumpleColumnas && cumpleBusqueda));
        });

        actualizarGrafica();
    }

    filtros.forEach(function (campo) {
        campo.addEventListener('input', aplicarFiltros);
    });

    // --- Vista de gráfica: dashboard de decisión (Actividad, Rubro, Dependencia, Proyecto PDI)
    // calculado sobre las filas que pasan los filtros de arriba. Los encabezados y filtros del
    // <thead> no se tocan; solo se oculta el <tbody> y se muestra este panel en su lugar. Cada
    // panel alterna entre barras y torta, sin recortar a "Otros" (a pedido: todas las categorías
    // quedan siempre visibles, con scroll interno si no caben). ---
    var vistaGraficaActiva = false;
    var PALETA_SERIES_TABLA = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
    var CLAVE_TIPO_GRAFICO = 'dev_tabla_grafica_tipo';

    function colorSerieTabla(indice) {
        return PALETA_SERIES_TABLA[indice % PALETA_SERIES_TABLA.length];
    }

    var tipoGraficoPorDimension = (function () {
        try {
            return JSON.parse(localStorage.getItem(CLAVE_TIPO_GRAFICO) || '{}');
        } catch (error) {
            return {};
        }
    }());

    function leerTipoGrafico(nombreDimension) {
        return tipoGraficoPorDimension[nombreDimension] === 'torta' ? 'torta' : 'barras';
    }

    function fijarTipoGrafico(nombreDimension, tipo) {
        tipoGraficoPorDimension[nombreDimension] = tipo;
        try {
            localStorage.setItem(CLAVE_TIPO_GRAFICO, JSON.stringify(tipoGraficoPorDimension));
        } catch (error) {
            // localStorage no disponible: la preferencia simplemente no persiste
        }
    }

    function parseNumeroCeldaTabla(texto) {
        var limpio = String(texto).replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.');
        var numero = parseFloat(limpio);
        return isNaN(numero) ? 0 : numero;
    }

    function formatoMonedaTabla(valor) {
        return '$' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function indiceColumnaTabla(nombre) {
        return columnasTabla.findIndex(function (c) { return c.toLowerCase() === nombre.toLowerCase(); });
    }

    function obtenerIndiceValorTabla() {
        var indice = indiceColumnaTabla('Valor total');
        return indice !== -1 ? indice : indiceColumnaTabla('Total');
    }

    // Columnas numéricas que no sirven como dimensión de agrupación en el dashboard.
    var COLUMNAS_NO_DIMENSION_TABLA = ['cantidad', 'costo unitario', 'precio unitario', 'valor total', 'total', 'meses'];

    function filasVisiblesParaGrafica() {
        return filasOriginales
            .filter(function (fila) { return !fila.classList.contains('fila-oculta-filtro'); })
            .map(function (fila) {
                return Array.prototype.slice.call(fila.children, 1).map(function (td) { return td.textContent.trim(); });
            });
    }

    function agruparYSumar(filasDatos, indiceDimension, indiceValor) {
        var totalesPorCategoria = {};
        var orden = [];

        filasDatos.forEach(function (fila) {
            var categoria = fila[indiceDimension] || '(Sin dato)';
            var valor = parseNumeroCeldaTabla(fila[indiceValor]);

            if (!Object.prototype.hasOwnProperty.call(totalesPorCategoria, categoria)) {
                totalesPorCategoria[categoria] = 0;
                orden.push(categoria);
            }
            totalesPorCategoria[categoria] += valor;
        });

        return orden.map(function (categoria) { return { categoria: categoria, valor: totalesPorCategoria[categoria] }; })
            .sort(function (a, b) { return b.valor - a.valor; });
    }

    // --- Tooltip compartido (barras y torta): valor primero, en fuerte; la categoría lo precede ---
    var elementoTooltipGrafica = document.getElementById('tabla-grafica-tooltip');

    function mostrarTooltipGrafica(elementoReferencia, categoria, valor, porcentaje) {
        if (!elementoTooltipGrafica) {
            return;
        }
        elementoTooltipGrafica.innerHTML = '';
        elementoTooltipGrafica.appendChild(document.createTextNode(categoria + ': '));
        var fuerte = document.createElement('strong');
        fuerte.textContent = formatoMonedaTabla(valor);
        elementoTooltipGrafica.appendChild(fuerte);
        var linea2 = document.createElement('div');
        linea2.textContent = porcentaje.toFixed(1) + '% del total de este panel';
        elementoTooltipGrafica.appendChild(linea2);

        var rect = elementoReferencia.getBoundingClientRect();
        elementoTooltipGrafica.style.display = 'block';
        elementoTooltipGrafica.style.left = Math.max(4, Math.min(rect.left, window.innerWidth - 270)) + 'px';
        var topPos = rect.top - elementoTooltipGrafica.offsetHeight - 8;
        elementoTooltipGrafica.style.top = (topPos < 4 ? rect.bottom + 8 : topPos) + 'px';
    }

    function ocultarTooltipGrafica() {
        if (elementoTooltipGrafica) {
            elementoTooltipGrafica.style.display = 'none';
        }
    }

    // --- Cuerpo en barras: una fila por categoría, sin límite ni "Otros" — la propia lista hace
    // scroll si no caben todas en el alto del panel. Cada categoría conserva su color de serie
    // entre barras y torta, para reconocerla igual al alternar entre ambas. ---
    function construirCuerpoBarras(agregados, totalGeneral) {
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo';
        var maxValor = agregados.reduce(function (acc, a) { return Math.max(acc, a.valor); }, 0) || 1;

        agregados.forEach(function (item, indice) {
            var fila = document.createElement('div');
            fila.className = 'grafica-fila-barra';
            fila.tabIndex = 0;

            var etiqueta = document.createElement('span');
            etiqueta.className = 'grafica-barra-etiqueta';
            etiqueta.textContent = item.categoria;
            etiqueta.title = item.categoria;
            fila.appendChild(etiqueta);

            var pista = document.createElement('div');
            pista.className = 'grafica-barra-pista';
            var relleno = document.createElement('div');
            relleno.className = 'grafica-barra-relleno';
            relleno.style.background = colorSerieTabla(indice);
            relleno.style.width = Math.max(1, (item.valor / maxValor) * 100) + '%';
            pista.appendChild(relleno);
            fila.appendChild(pista);

            var valorSpan = document.createElement('span');
            valorSpan.className = 'grafica-barra-valor';
            valorSpan.textContent = formatoMonedaTabla(item.valor);
            fila.appendChild(valorSpan);

            var porcentaje = totalGeneral > 0 ? (item.valor / totalGeneral) * 100 : 0;
            fila.addEventListener('mouseenter', function () { mostrarTooltipGrafica(fila, item.categoria, item.valor, porcentaje); });
            fila.addEventListener('mouseleave', ocultarTooltipGrafica);
            fila.addEventListener('focus', function () { mostrarTooltipGrafica(fila, item.categoria, item.valor, porcentaje); });
            fila.addEventListener('blur', ocultarTooltipGrafica);

            contenedor.appendChild(fila);
        });

        return contenedor;
    }

    // --- Torta: SVG a mano (sin librería), con leyenda desplazable al lado que nunca se recorta
    // — hover en una porción resalta su fila de leyenda y viceversa. ---
    function puntoEnCirculoTabla(cx, cy, r, anguloGrados) {
        var rad = (Math.PI / 180) * anguloGrados;
        return { x: cx + r * Math.sin(rad), y: cy - r * Math.cos(rad) };
    }

    function trazoArcoTortaTabla(cx, cy, r, anguloInicio, anguloFin) {
        var p1 = puntoEnCirculoTabla(cx, cy, r, anguloInicio);
        var p2 = puntoEnCirculoTabla(cx, cy, r, anguloFin);
        var grande = (anguloFin - anguloInicio) > 180 ? 1 : 0;
        return ['M', cx, cy, 'L', p1.x, p1.y, 'A', r, r, 0, grande, 1, p2.x, p2.y, 'Z'].join(' ');
    }

    function construirCuerpoTorta(agregados, totalGeneral, tamanoGrande) {
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo grafica-torta-cuerpo';

        var svgNS = 'http://www.w3.org/2000/svg';
        var lado = tamanoGrande ? 240 : 100;
        var radio = lado / 2;
        var svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 ' + lado + ' ' + lado);
        svg.setAttribute('class', 'grafica-torta-svg');
        if (!tamanoGrande) {
            svg.setAttribute('width', lado);
            svg.setAttribute('height', lado);
        }

        if (agregados.length === 1 || totalGeneral <= 0) {
            var circulo = document.createElementNS(svgNS, 'circle');
            circulo.setAttribute('cx', radio);
            circulo.setAttribute('cy', radio);
            circulo.setAttribute('r', radio - 1);
            circulo.setAttribute('fill', colorSerieTabla(0));
            circulo.setAttribute('class', 'grafica-torta-porcion');
            circulo.dataset.indice = '0';
            svg.appendChild(circulo);
        } else {
            var acumulado = 0;
            agregados.forEach(function (item, indice) {
                var anguloInicio = acumulado * 360;
                acumulado += item.valor / totalGeneral;
                var anguloFin = acumulado * 360;
                var porcion = document.createElementNS(svgNS, 'path');
                porcion.setAttribute('d', trazoArcoTortaTabla(radio, radio, radio - 1, anguloInicio, anguloFin));
                porcion.setAttribute('fill', colorSerieTabla(indice));
                porcion.setAttribute('class', 'grafica-torta-porcion');
                porcion.dataset.indice = String(indice);
                svg.appendChild(porcion);
            });
        }

        contenedor.appendChild(svg);

        var leyenda = document.createElement('div');
        leyenda.className = 'grafica-torta-leyenda';

        agregados.forEach(function (item, indice) {
            var porcentaje = totalGeneral > 0 ? (item.valor / totalGeneral) * 100 : 0;
            var filaLeyenda = document.createElement('div');
            filaLeyenda.className = 'grafica-torta-leyenda-fila';
            filaLeyenda.tabIndex = 0;

            var punto = document.createElement('span');
            punto.className = 'grafica-torta-leyenda-punto';
            punto.style.background = colorSerieTabla(indice);
            filaLeyenda.appendChild(punto);

            var etiqueta = document.createElement('span');
            etiqueta.className = 'grafica-torta-leyenda-etiqueta';
            etiqueta.textContent = item.categoria;
            etiqueta.title = item.categoria;
            filaLeyenda.appendChild(etiqueta);

            var valorSpan = document.createElement('span');
            valorSpan.className = 'grafica-torta-leyenda-valor';
            valorSpan.textContent = porcentaje.toFixed(1) + '%';
            filaLeyenda.appendChild(valorSpan);

            var porcionSvg = svg.querySelector('[data-indice="' + indice + '"]');

            function resaltar() {
                filaLeyenda.classList.add('resaltada');
                if (porcionSvg) { porcionSvg.classList.add('resaltada'); }
                mostrarTooltipGrafica(filaLeyenda, item.categoria, item.valor, porcentaje);
            }

            function quitarResaltado() {
                filaLeyenda.classList.remove('resaltada');
                if (porcionSvg) { porcionSvg.classList.remove('resaltada'); }
                ocultarTooltipGrafica();
            }

            filaLeyenda.addEventListener('mouseenter', resaltar);
            filaLeyenda.addEventListener('mouseleave', quitarResaltado);
            filaLeyenda.addEventListener('focus', resaltar);
            filaLeyenda.addEventListener('blur', quitarResaltado);

            if (porcionSvg) {
                porcionSvg.addEventListener('mouseenter', resaltar);
                porcionSvg.addEventListener('mouseleave', quitarResaltado);
            }

            leyenda.appendChild(filaLeyenda);
        });

        contenedor.appendChild(leyenda);
        return contenedor;
    }

    // --- PAC estimado por mes: por cada fila se reparte su Valor total entre los meses de
    // ejecución que tenga marcados (ej. una fila de $1.200.000 en 3 meses aporta $400.000 a cada
    // uno de esos 3 meses), y luego se suman esos aportes por mes — siempre Ene a Dic en orden
    // cronológico, no por magnitud como los demás paneles. ---
    var NOMBRES_MESES_TABLA = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    function calcularPacPorMes(filasDatos, indiceMeses, indiceValor) {
        var totalesPorMes = NOMBRES_MESES_TABLA.map(function () { return 0; });

        filasDatos.forEach(function (fila) {
            var textoMeses = (fila[indiceMeses] || '').trim();
            if (textoMeses === '') {
                return;
            }
            var listaMeses = textoMeses.split(',').map(function (m) { return m.trim(); }).filter(function (m) { return m !== ''; });
            if (listaMeses.length === 0) {
                return;
            }
            var valorPorMes = parseNumeroCeldaTabla(fila[indiceValor]) / listaMeses.length;
            listaMeses.forEach(function (nombreMes) {
                var indiceMes = NOMBRES_MESES_TABLA.indexOf(nombreMes);
                if (indiceMes !== -1) {
                    totalesPorMes[indiceMes] += valorPorMes;
                }
            });
        });

        return totalesPorMes;
    }

    // --- Línea (PAC): SVG a mano sin librería. Sin recorte de categorías — siempre los 12 meses.
    // Las etiquetas de mes van en HTML aparte (no dentro del SVG) para que nunca se deformen con
    // el escalado no uniforme que estira el gráfico a todo el ancho del panel. ---
    function construirCuerpoLinea(valoresPorMes, etiquetasMeses) {
        var contenedor = document.createElement('div');
        contenedor.className = 'grafica-panel-cuerpo grafica-linea-cuerpo';

        var svgNS = 'http://www.w3.org/2000/svg';
        var anchoBase = 600;
        var altoBase = 200;
        var padX = 14;
        var padSuperior = 14;
        var padInferior = 14;
        var anchoUtil = anchoBase - (padX * 2);
        var altoUtil = altoBase - padSuperior - padInferior;
        var maxValor = Math.max.apply(null, valoresPorMes.concat([0])) || 1;
        var totalGeneral = valoresPorMes.reduce(function (a, b) { return a + b; }, 0);

        var svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 ' + anchoBase + ' ' + altoBase);
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.setAttribute('class', 'grafica-linea-svg');

        var puntos = valoresPorMes.map(function (valor, indice) {
            var x = padX + (anchoUtil * indice / (valoresPorMes.length - 1));
            var y = padSuperior + altoUtil - (altoUtil * (valor / maxValor));
            return { x: x, y: y, valor: valor };
        });

        var lineaBase = document.createElementNS(svgNS, 'line');
        lineaBase.setAttribute('x1', padX);
        lineaBase.setAttribute('x2', anchoBase - padX);
        lineaBase.setAttribute('y1', padSuperior + altoUtil);
        lineaBase.setAttribute('y2', padSuperior + altoUtil);
        lineaBase.setAttribute('class', 'grafica-linea-base');
        svg.appendChild(lineaBase);

        var trazo = document.createElementNS(svgNS, 'path');
        trazo.setAttribute('d', puntos.map(function (p, i) { return (i === 0 ? 'M' : 'L') + p.x + ' ' + p.y; }).join(' '));
        trazo.setAttribute('class', 'grafica-linea-trazo');
        trazo.setAttribute('fill', 'none');
        svg.appendChild(trazo);

        puntos.forEach(function (p, indice) {
            var circulo = document.createElementNS(svgNS, 'circle');
            circulo.setAttribute('cx', p.x);
            circulo.setAttribute('cy', p.y);
            circulo.setAttribute('r', 4);
            circulo.setAttribute('class', 'grafica-linea-punto');
            circulo.setAttribute('tabindex', '0');

            var porcentaje = totalGeneral > 0 ? (p.valor / totalGeneral) * 100 : 0;
            function mostrar() { mostrarTooltipGrafica(circulo, etiquetasMeses[indice], p.valor, porcentaje); }
            circulo.addEventListener('mouseenter', mostrar);
            circulo.addEventListener('mouseleave', ocultarTooltipGrafica);
            circulo.addEventListener('focus', mostrar);
            circulo.addEventListener('blur', ocultarTooltipGrafica);

            svg.appendChild(circulo);
        });

        contenedor.appendChild(svg);

        var filaEtiquetas = document.createElement('div');
        filaEtiquetas.className = 'grafica-linea-meses-etiquetas';
        etiquetasMeses.forEach(function (etiqueta) {
            var span = document.createElement('span');
            span.className = 'grafica-linea-mes-etiqueta';
            span.textContent = etiqueta;
            filaEtiquetas.appendChild(span);
        });
        contenedor.appendChild(filaEtiquetas);

        return contenedor;
    }

    function crearBotonTipoGrafico(tipo, tipoActivo, alElegir) {
        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'grafica-tipo-boton' + (tipo === tipoActivo ? ' activo' : '');
        boton.title = tipo === 'barras' ? 'Barras' : 'Torta';
        boton.innerHTML = tipo === 'barras'
            ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>'
            : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>';
        boton.addEventListener('click', function (evento) {
            evento.stopPropagation();
            alElegir(tipo);
        });
        return boton;
    }

    function construirToggleTipo(nombreDimension, contenedorToggle, alCambiar) {
        contenedorToggle.innerHTML = '';
        var tipoActual = leerTipoGrafico(nombreDimension);
        ['barras', 'torta'].forEach(function (tipo) {
            contenedorToggle.appendChild(crearBotonTipoGrafico(tipo, tipoActual, function (tipoElegido) {
                fijarTipoGrafico(nombreDimension, tipoElegido);
                alCambiar();
            }));
        });
    }

    // --- Panel compacto de la cuadrícula 2x2: cabecera (título + alternador) + cuerpo. Doble clic
    // (o el ícono de ampliar) oculta los otros 3 y este ocupa toda la cuadrícula — todo pasa
    // dentro del área normal del dashboard, así que los filtros de arriba nunca quedan tapados. ---
    var panelExpandidoNombre = null;

    function expandirPanel(nombreDimension) {
        panelExpandidoNombre = (panelExpandidoNombre === nombreDimension) ? null : nombreDimension;
        actualizarGrafica();
    }

    function renderizarPanel(contenedorPadre, dimension, agregados, totalGeneral, esExpandido) {
        var panel = document.createElement('div');
        panel.className = 'grafica-panel' + (esExpandido ? ' expandido' : '');
        panel.title = esExpandido ? 'Doble clic para volver a ver todas las gráficas' : 'Doble clic para ampliar';

        var cabecera = document.createElement('div');
        cabecera.className = 'grafica-panel-cabecera';

        var textos = document.createElement('div');
        var h3 = document.createElement('h3');
        h3.className = 'grafica-panel-titulo';
        h3.textContent = dimension.titulo;
        var subt = document.createElement('p');
        subt.className = 'grafica-panel-subtitulo';
        subt.textContent = dimension.subtitulo;
        textos.appendChild(h3);
        textos.appendChild(subt);
        cabecera.appendChild(textos);

        var acciones = document.createElement('div');
        acciones.style.display = 'flex';
        acciones.style.alignItems = 'center';
        acciones.style.gap = '0.4rem';
        acciones.style.flexShrink = '0';

        var contenedorToggle;
        if (!dimension.esLinea) {
            contenedorToggle = document.createElement('div');
            contenedorToggle.className = 'grafica-tipo-toggle';
            acciones.appendChild(contenedorToggle);
        }

        var botonAmpliar = document.createElement('button');
        botonAmpliar.type = 'button';
        botonAmpliar.className = 'icono-boton';
        botonAmpliar.title = esExpandido ? 'Restaurar' : 'Ampliar';
        botonAmpliar.innerHTML = esExpandido
            ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>'
            : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>';
        botonAmpliar.addEventListener('click', function (evento) {
            evento.stopPropagation();
            expandirPanel(dimension.nombre);
        });
        acciones.appendChild(botonAmpliar);

        cabecera.appendChild(acciones);
        panel.appendChild(cabecera);

        function actualizarPanelCompleto() {
            if (contenedorToggle) {
                construirToggleTipo(dimension.nombre, contenedorToggle, actualizarPanelCompleto);
            }

            var cuerpoAnterior = panel.querySelector('.grafica-panel-cuerpo');
            if (cuerpoAnterior) {
                cuerpoAnterior.remove();
            }

            if (dimension.esLinea) {
                panel.appendChild(construirCuerpoLinea(dimension.valoresPorMes, dimension.etiquetasMeses));
                return;
            }

            if (agregados.length === 0) {
                var vacio = document.createElement('p');
                vacio.className = 'grafica-panel-vacio grafica-panel-cuerpo';
                vacio.textContent = 'Sin datos para los filtros actuales.';
                panel.appendChild(vacio);
                return;
            }

            var tipo = leerTipoGrafico(dimension.nombre);
            var cuerpo = tipo === 'torta'
                ? construirCuerpoTorta(agregados, totalGeneral, esExpandido)
                : construirCuerpoBarras(agregados, totalGeneral);
            panel.appendChild(cuerpo);
        }

        actualizarPanelCompleto();

        panel.addEventListener('dblclick', function () {
            expandirPanel(dimension.nombre);
        });

        contenedorPadre.appendChild(panel);
    }

    window.addEventListener('resize', function () {
        if (vistaGraficaActiva) {
            ajustarAlturaGrafica();
        }
    });

    function ajustarAlturaGrafica() {
        var envoltorioScroll = tabla.closest('.tabla-scroll');
        var grafica = document.getElementById('tabla-grafica');
        if (!envoltorioScroll || !grafica) {
            return;
        }
        var alturaTabla = tabla.getBoundingClientRect().height;
        var alturaDisponible = envoltorioScroll.clientHeight - alturaTabla;
        grafica.style.height = Math.max(240, alturaDisponible) + 'px';
    }

    function actualizarGrafica() {
        if (!vistaGraficaActiva) {
            return;
        }

        var contenedorKpis = document.getElementById('tabla-grafica-kpis');
        var contenedorPaneles = document.getElementById('tabla-grafica-paneles');
        if (!contenedorKpis || !contenedorPaneles) {
            return;
        }

        var filasDatos = filasVisiblesParaGrafica();
        var indiceValor = obtenerIndiceValorTabla();

        var dimensionesPreferidas = ['Actividad', 'Rubro', 'Dependencia', 'Proyecto PDI', 'Sede'];
        var dimensiones = dimensionesPreferidas
            .map(function (nombre) { return { nombre: nombre, indice: indiceColumnaTabla(nombre) }; })
            .filter(function (d) { return d.indice !== -1; });

        if (dimensiones.length === 0) {
            dimensiones = columnasTabla
                .map(function (nombre, indice) { return { nombre: nombre, indice: indice }; })
                .filter(function (d) {
                    return d.indice !== indiceValor && COLUMNAS_NO_DIMENSION_TABLA.indexOf(d.nombre.toLowerCase()) === -1;
                })
                .slice(0, 5);
        }

        // --- KPIs ---
        contenedorKpis.innerHTML = '';

        var totalValor = indiceValor !== -1
            ? filasDatos.reduce(function (acc, fila) { return acc + parseNumeroCeldaTabla(fila[indiceValor]); }, 0)
            : 0;

        var indiceInsumo = indiceColumnaTabla('Insumo');
        var conteoInsumos = filasDatos.length;
        var etiquetaInsumos = 'Filas visibles';
        if (indiceInsumo !== -1) {
            var insumosUnicos = {};
            filasDatos.forEach(function (fila) { insumosUnicos[fila[indiceInsumo] || '(Sin dato)'] = true; });
            conteoInsumos = Object.keys(insumosUnicos).length;
            etiquetaInsumos = 'Insumos';
        }

        var tarjetasKpi = [
            { etiqueta: indiceValor !== -1 ? columnasTabla[indiceValor] + ' (suma)' : 'Filas', valor: indiceValor !== -1 ? formatoMonedaTabla(totalValor) : String(filasDatos.length) },
            { etiqueta: etiquetaInsumos, valor: String(conteoInsumos) }
        ];

        dimensiones.slice(0, 2).forEach(function (dimension) {
            var valoresUnicos = {};
            filasDatos.forEach(function (fila) { valoresUnicos[fila[dimension.indice] || '(Sin dato)'] = true; });
            tarjetasKpi.push({ etiqueta: dimension.nombre + ' distintos', valor: String(Object.keys(valoresUnicos).length) });
        });

        tarjetasKpi.forEach(function (kpi) {
            var tarjeta = document.createElement('div');
            tarjeta.className = 'grafica-stat';
            var etiqueta = document.createElement('p');
            etiqueta.className = 'grafica-stat-etiqueta';
            etiqueta.textContent = kpi.etiqueta;
            var valor = document.createElement('p');
            valor.className = 'grafica-stat-valor';
            valor.textContent = kpi.valor;
            tarjeta.appendChild(etiqueta);
            tarjeta.appendChild(valor);
            contenedorKpis.appendChild(tarjeta);
        });

        // --- Paneles: uno por dimensión (barras/torta, recuerda cuál se eligió) más, si la tabla
        // tiene columna "Meses", el panel de línea del PAC estimado. Si hay uno ampliado, se
        // muestra solo ese y ocupa toda la cuadrícula (ver .un-panel). ---
        contenedorPaneles.innerHTML = '';

        if (indiceValor === -1) {
            var avisoSinValor = document.createElement('p');
            avisoSinValor.className = 'grafica-panel-vacio';
            avisoSinValor.textContent = 'Esta tabla no tiene una columna de valor/total para agregar.';
            contenedorPaneles.appendChild(avisoSinValor);
        } else {
            var paneles = dimensiones.slice();

            var indiceMeses = indiceColumnaTabla('Meses');
            if (indiceMeses !== -1) {
                var valoresPorMes = calcularPacPorMes(filasDatos, indiceMeses, indiceValor);
                var filasConMeses = filasDatos.filter(function (fila) { return (fila[indiceMeses] || '').trim() !== ''; }).length;
                paneles.push({
                    nombre: 'Meses',
                    esLinea: true,
                    valoresPorMes: valoresPorMes,
                    etiquetasMeses: NOMBRES_MESES_TABLA,
                    titulo: 'PAC',
                    subtitulo: filasConMeses + ' insumo' + (filasConMeses === 1 ? '' : 's') + ' con meses de ejecución asignados'
                });
            }

            var panelesAMostrar = panelExpandidoNombre
                ? paneles.filter(function (p) { return p.nombre === panelExpandidoNombre; })
                : paneles;

            if (panelExpandidoNombre && panelesAMostrar.length === 0) {
                panelExpandidoNombre = null;
                panelesAMostrar = paneles;
            }

            contenedorPaneles.classList.toggle('un-panel', !!panelExpandidoNombre);

            panelesAMostrar.forEach(function (panelDato) {
                var esExpandido = panelDato.nombre === panelExpandidoNombre;

                if (panelDato.esLinea) {
                    renderizarPanel(contenedorPaneles, panelDato, null, 0, esExpandido);
                    return;
                }

                var agregados = agruparYSumar(filasDatos, panelDato.indice, indiceValor);
                var totalGeneral = agregados.reduce(function (acc, a) { return acc + a.valor; }, 0);
                panelDato.titulo = panelDato.nombre;
                panelDato.subtitulo = agregados.length + ' ' + panelDato.nombre.toLowerCase() + (agregados.length === 1 ? '' : 's') + ' con datos en el filtro actual';
                renderizarPanel(contenedorPaneles, panelDato, agregados, totalGeneral, esExpandido);
            });
        }

        ajustarAlturaGrafica();
    }

    var botonVistaTabla = document.getElementById('tabla-boton-vista-tabla');
    var botonVistaGrafica = document.getElementById('tabla-boton-vista-grafica');
    var botonVistaTablaMenu = document.getElementById('tabla-boton-vista-tabla-menu');
    var botonVistaGraficaMenu = document.getElementById('tabla-boton-vista-grafica-menu');

    function activarVistaTabla() {
        vistaGraficaActiva = false;
        tabla.classList.remove('modo-grafica');
        panelExpandidoNombre = null;
        if (botonVistaTabla) { botonVistaTabla.classList.add('activo'); botonVistaTabla.setAttribute('aria-pressed', 'true'); }
        if (botonVistaGrafica) { botonVistaGrafica.classList.remove('activo'); botonVistaGrafica.setAttribute('aria-pressed', 'false'); }
    }

    function activarVistaGrafica() {
        vistaGraficaActiva = true;
        tabla.classList.add('modo-grafica');
        if (botonVistaGrafica) { botonVistaGrafica.classList.add('activo'); botonVistaGrafica.setAttribute('aria-pressed', 'true'); }
        if (botonVistaTabla) { botonVistaTabla.classList.remove('activo'); botonVistaTabla.setAttribute('aria-pressed', 'false'); }
        actualizarGrafica();
    }

    [botonVistaTabla, botonVistaTablaMenu].forEach(function (b) { if (b) b.addEventListener('click', activarVistaTabla); });
    [botonVistaGrafica, botonVistaGraficaMenu].forEach(function (b) { if (b) b.addEventListener('click', activarVistaGrafica); });

    // --- Selección de filas y habilitar "Enviar" (y, en modo demo, Editar/Eliminar) ---
    var seleccionarTodo = document.getElementById('tabla-seleccionar-todo');
    var botonEnviar = document.getElementById('tabla-boton-enviar');
    var botonEditar = document.getElementById('tabla-boton-editar');
    var botonEliminar = document.getElementById('tabla-boton-eliminar');
    var botonEditarMenu = document.getElementById('tabla-boton-editar-menu');
    var botonEliminarMenu = document.getElementById('tabla-boton-eliminar-menu');

    function actualizarEnviar() {
        var seleccionadas = Array.prototype.filter.call(tabla.querySelectorAll('.tabla-seleccion-fila'), function (c) { return c.checked; });
        var hayAlgunaSeleccionada = seleccionadas.length > 0;
        botonEnviar.disabled = !hayAlgunaSeleccionada;
        botonEnviar.classList.toggle('habilitado', hayAlgunaSeleccionada);
        botonEnviar.title = hayAlgunaSeleccionada ? 'Enviar seleccionados' : 'Selecciona al menos una fila para enviar';

        if (modoDemo) {
            var unaSeleccionada = seleccionadas.length === 1;
            [botonEditar, botonEditarMenu].forEach(function (b) { if (b) b.disabled = !unaSeleccionada; });
            [botonEliminar, botonEliminarMenu].forEach(function (b) { if (b) b.disabled = !hayAlgunaSeleccionada; });
        }
    }

    function filaSeleccionadaId() {
        var casilla = tabla.querySelector('.tabla-seleccion-fila:checked');
        return casilla ? parseInt(casilla.closest('tr').dataset.id, 10) : null;
    }

    function idsSeleccionados() {
        return Array.prototype.map.call(tabla.querySelectorAll('.tabla-seleccion-fila:checked'), function (c) {
            return parseInt(c.closest('tr').dataset.id, 10);
        });
    }

    // Vuelve a engancharse tras redibujar el <tbody> (modo demo: eliminar/editar cambian las filas).
    function configurarSeleccionFilas() {
        tabla.querySelectorAll('.tabla-seleccion-fila').forEach(function (casilla) {
            casilla.addEventListener('change', function () {
                casilla.closest('tr').classList.toggle('fila-seleccionada', casilla.checked);
                actualizarEnviar();
            });
        });
        actualizarEnviar();
    }

    configurarSeleccionFilas();

    if (seleccionarTodo) {
        seleccionarTodo.addEventListener('change', function () {
            tabla.querySelectorAll('.tabla-seleccion-fila').forEach(function (casilla) {
                casilla.checked = seleccionarTodo.checked;
                casilla.closest('tr').classList.toggle('fila-seleccionada', casilla.checked);
            });
            actualizarEnviar();
        });
    }

    // --- Modo demo: array chico y fijo en memoria del navegador. Ver, Editar, Eliminar, Guardar
    // y Enviar funcionan de verdad aquí, pero nunca llaman al servidor ni tocan la base de datos
    // real; se reinician al recargar la página. ---
    if (modoDemo) {
        var datosDemo = <?php echo json_encode(array_values($datosDemo ?? []), JSON_UNESCAPED_UNICODE); ?>;

        var formatoMonedaDemo = function (valor) {
            return '$' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        var escaparHtmlDemo = function (texto) {
            var envoltorio = document.createElement('div');
            envoltorio.textContent = texto;
            return envoltorio.innerHTML;
        };

        var renderFilasDemo = function () {
            cuerpo.innerHTML = '';
            datosDemo.forEach(function (d) {
                var total = d.cantidad * d.precio;
                var fila = document.createElement('tr');
                fila.dataset.id = d.id;
                fila.innerHTML =
                    '<td class="col-seleccion"><input type="checkbox" class="tabla-seleccion-fila"></td>' +
                    '<td>' + escaparHtmlDemo(d.insumo) + '</td>' +
                    '<td>' + d.cantidad + '</td>' +
                    '<td>' + formatoMonedaDemo(d.precio) + '</td>' +
                    '<td>' + formatoMonedaDemo(total) + '</td>' +
                    '<td>' + escaparHtmlDemo(d.estado) + '</td>';
                cuerpo.appendChild(fila);
            });

            filasOriginales = Array.prototype.slice.call(cuerpo.querySelectorAll('tr'));
            configurarSeleccionFilas();
            aplicarFiltros();
        };

        var mostrarToastDemo = function (mensaje) {
            var toast = document.getElementById('tabla-toast');
            if (!toast) {
                return;
            }
            toast.textContent = mensaje;
            toast.classList.add('visible');
            clearTimeout(toast._tiempoDemo);
            toast._tiempoDemo = setTimeout(function () { toast.classList.remove('visible'); }, 2500);
        };

        var modalDemo = document.getElementById('tabla-modal-demo');
        var modalTitulo = document.getElementById('tabla-modal-demo-titulo');
        var modalFormulario = document.getElementById('tabla-modal-demo-formulario');
        var modalCerrar = document.getElementById('tabla-modal-demo-cerrar');
        var campoInsumo = document.getElementById('tabla-modal-demo-insumo');
        var campoCantidad = document.getElementById('tabla-modal-demo-cantidad');
        var campoPrecio = document.getElementById('tabla-modal-demo-precio');
        var idEnEdicionDemo = null;

        var cerrarModalDemo = function () {
            modalDemo.classList.remove('abierto');
            idEnEdicionDemo = null;
        };

        var abrirModalEditar = function (dato) {
            idEnEdicionDemo = dato.id;
            modalTitulo.textContent = 'Editar insumo';
            campoInsumo.value = dato.insumo;
            campoCantidad.value = dato.cantidad;
            campoPrecio.value = dato.precio;
            modalDemo.classList.add('abierto');
        };

        if (modalCerrar) {
            modalCerrar.addEventListener('click', cerrarModalDemo);
        }

        if (modalDemo) {
            modalDemo.addEventListener('click', function (evento) {
                if (evento.target === modalDemo) {
                    cerrarModalDemo();
                }
            });
        }

        var accionEditar = function () {
            var id = filaSeleccionadaId();
            var dato = datosDemo.find(function (d) { return d.id === id; });
            if (dato) {
                abrirModalEditar(dato);
            }
        };

        var accionEliminar = function () {
            var ids = idsSeleccionados();
            if (ids.length === 0) {
                return;
            }
            if (!confirm('¿Eliminar ' + ids.length + ' insumo(s) del array de prueba? (solo en este navegador; se restaura al recargar la página)')) {
                return;
            }
            datosDemo = datosDemo.filter(function (d) { return ids.indexOf(d.id) === -1; });
            renderFilasDemo();
            mostrarToastDemo('Eliminado(s) ' + ids.length + ' insumo(s) (solo en memoria).');
        };

        [botonEditar, botonEditarMenu].forEach(function (b) { if (b) b.addEventListener('click', accionEditar); });
        [botonEliminar, botonEliminarMenu].forEach(function (b) { if (b) b.addEventListener('click', accionEliminar); });

        if (modalFormulario) {
            modalFormulario.addEventListener('submit', function (evento) {
                evento.preventDefault();
                var dato = datosDemo.find(function (d) { return d.id === idEnEdicionDemo; });
                if (!dato) {
                    return;
                }
                dato.insumo = campoInsumo.value.trim() || dato.insumo;
                dato.cantidad = Math.max(1, parseInt(campoCantidad.value, 10) || dato.cantidad);
                dato.precio = Math.max(0, parseFloat(campoPrecio.value) || dato.precio);
                renderFilasDemo();
                cerrarModalDemo();
                mostrarToastDemo('Insumo actualizado (solo en memoria).');
            });
        }

        var botonGuardarDemo = document.getElementById('tabla-boton-guardar');
        if (botonGuardarDemo) {
            botonGuardarDemo.addEventListener('click', function () {
                mostrarToastDemo('Cambios "guardados" en el array de prueba (esto nunca toca la base de datos real).');
            });
        }

        botonEnviar.addEventListener('click', function () {
            var ids = idsSeleccionados();
            if (ids.length === 0) {
                return;
            }
            mostrarToastDemo('Enviado(s) ' + ids.length + ' insumo(s) de prueba (simulado, no hace ninguna petición).');
        });
    }
});
</script>

<?php require __DIR__ . '/../../parciales/pie.php'; ?>
