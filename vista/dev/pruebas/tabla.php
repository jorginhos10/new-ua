<?php
// Vista de prueba "Tabla" — prototipo aislado (ver DevControlador). Toma datos reales de Gastos
// solo como referencia visual; no lee del formulario real ni escribe nada.
require_once __DIR__ . '/../../../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../../../modelo/Gasto.php';

$aniosActivos = (new AnioPresupuestal())->obtenerActivos();
$anioId = (int) ($aniosActivos[0]['id'] ?? 0);
$gastos = $anioId > 0 ? (new Gasto())->obtenerPorAnio($anioId) : [];
$gastos = array_slice($gastos, 0, 25);

$nombresMeses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

$columnas = ['Sede', 'Dependencia', 'Proyecto PDI', 'Actividad', 'Rubro', 'Insumo', 'Cantidad', 'Costo unitario', 'Valor total', 'Meses'];

$filas = array_map(static function (array $g) use ($nombresMeses): array {
    $meses = $g['meses'] !== '' ? implode(', ', array_map(static fn ($m) => $nombresMeses[(int) $m] ?? $m, explode(',', $g['meses']))) : '';

    return [
        $g['sede_codigo'] . ' - ' . $g['sede_nombre'],
        $g['dependencia'],
        $g['proyecto_codigo'] . ' - ' . $g['proyecto_nombre'],
        $g['actividad'],
        $g['rubro_id'] !== null ? $g['rubro_codigo'] . ' - ' . $g['rubro_descripcion'] : ($g['rubro_texto'] ?? '—'),
        $g['insumo'],
        number_format((float) $g['cantidad'], 0, ',', '.'),
        '$' . number_format((float) $g['costo_unitario'], 2, ',', '.'),
        '$' . number_format((float) $g['valor_total'], 2, ',', '.'),
        $meses,
    ];
}, $gastos);

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
        border-bottom: 1px solid var(--color-borde);
        margin-bottom: 0.5rem;
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
                <button type="button" class="icono-boton" title="Ver" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
                <button type="button" class="icono-boton" title="Editar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                <button type="button" class="icono-boton" title="Eliminar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                </button>
                <button type="button" class="icono-boton grupo-iconos-colapsar" title="Más acciones" data-toggle-menu>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle></svg>
                </button>
                <div class="grupo-iconos-menu">
                    <button type="button" disabled>Ver</button>
                    <button type="button" disabled>Editar</button>
                    <button type="button" disabled>Eliminar</button>
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
                <button type="button" class="boton-principal-azul" title="Guardar">Guardar</button>
                <button type="button" class="boton-enviar-icono" id="tabla-boton-enviar" title="Selecciona al menos una fila para enviar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($filas)): ?>
    <p class="texto-atenuado">No hay gastos en el año presupuestal activo para usar de referencia.</p>
    <?php else: ?>
    <div class="tabla-scroll">
        <table class="tabla-dev-datos" id="tabla-dev-datos">
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
                <?php foreach ($filas as $fila): ?>
                <tr>
                    <td class="col-seleccion"><input type="checkbox" class="tabla-seleccion-fila"></td>
                    <?php foreach ($fila as $valor): ?>
                    <td><?= htmlspecialchars($valor) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
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
    }

    filtros.forEach(function (campo) {
        campo.addEventListener('input', aplicarFiltros);
    });

    // --- Selección de filas y habilitar "Enviar" ---
    var seleccionarTodo = document.getElementById('tabla-seleccionar-todo');
    var botonEnviar = document.getElementById('tabla-boton-enviar');
    var casillasFila = tabla.querySelectorAll('.tabla-seleccion-fila');

    function actualizarEnviar() {
        var hayAlgunaSeleccionada = Array.prototype.some.call(casillasFila, function (c) { return c.checked; });
        botonEnviar.disabled = !hayAlgunaSeleccionada;
        botonEnviar.classList.toggle('habilitado', hayAlgunaSeleccionada);
        botonEnviar.title = hayAlgunaSeleccionada ? 'Enviar seleccionados' : 'Selecciona al menos una fila para enviar';
    }

    casillasFila.forEach(function (casilla) {
        casilla.addEventListener('change', function () {
            casilla.closest('tr').classList.toggle('fila-seleccionada', casilla.checked);
            actualizarEnviar();
        });
    });

    if (seleccionarTodo) {
        seleccionarTodo.addEventListener('change', function () {
            casillasFila.forEach(function (casilla) {
                casilla.checked = seleccionarTodo.checked;
                casilla.closest('tr').classList.toggle('fila-seleccionada', casilla.checked);
            });
            actualizarEnviar();
        });
    }
});
</script>

<?php require __DIR__ . '/../../parciales/pie.php'; ?>
