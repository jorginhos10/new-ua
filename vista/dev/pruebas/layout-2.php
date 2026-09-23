<?php
// Vista de prueba "Layout 2" — prototipo aislado (ver DevControlador). Explora una barra de
// navegación de dos niveles inspirada en Gmail: un riel angosto de iconos (con etiqueta debajo de
// cada uno) siempre visible, y una segunda columna con el detalle del ítem seleccionado que se
// puede ocultar del todo con el botón de hamburguesa — que vive dentro del riel, como el resto de
// sus botones, así que nunca desaparece (el riel jamás se oculta, solo la segunda columna). El
// logo SPPI vive arriba del todo en el riel por la misma razón. Es solo maqueta de navegación — el
// contenido real de cada página no vive aquí — pero usa nombres/rutas reales (Autogestión, Perfil
// de proyectos, Gastos, Solicitudes, Configuraciones) como referencia. Al ser una propuesta de
// sidebar alternativo, esta vista NO pasa por parciales/encabezado.php (crearía dos sidebars a la
// vez): es una página aparte, con su propio <html>, que solo reutiliza estilo.css para los tokens
// de color/tipografía.

require_once __DIR__ . '/../../../modelo/AutogestionItem.php';

$modeloAutogestionL2 = new AutogestionItem();
$itemsExtensionL2 = $modeloAutogestionL2->obtenerActivos('extension');
$itemsPostgradoL2 = $modeloAutogestionL2->obtenerActivos('postgrado');
$versionCssL2 = @filemtime(__DIR__ . '/../../../publico/css/estilo.css') ?: time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dev · Layout 2</title>
    <link rel="stylesheet" href="publico/css/estilo.css?v=<?= $versionCssL2 ?>">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        .l2-layout {
            display: flex;
            height: 100vh;
        }

        /* --- Riel: siempre visible (nunca se oculta), azul petróleo casi opaco a propósito — el
           fondo de la página (assets/img/home.webp, con palmeras verdes) se filtra por el blur y
           corre el tono hacia verde si el riel queda muy transparente, así que aquí el color propio
           domina (alpha alto) y el blur es solo para suavizar el borde, no para dejar ver el fondo. --- */
        .l2-rail {
            width: 92px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            background: linear-gradient(180deg, rgba(13, 61, 68, 0.96), rgba(6, 32, 36, 0.96));
            backdrop-filter: blur(30px) saturate(120%);
            -webkit-backdrop-filter: blur(30px) saturate(120%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .l2-rail-marca {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 52px;
            flex-shrink: 0;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .l2-rail-marca:hover {
            text-decoration: none;
            opacity: 0.85;
        }

        .l2-rail-hamburguesa-envoltorio {
            padding: 0.5rem 0.3rem 0;
        }

        .l2-rail-nav {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            padding: 0.6rem 0.3rem;
        }

        .l2-rail-pie {
            margin-top: auto;
            padding: 0.6rem 0.3rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .l2-rail-boton {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            width: 100%;
            padding: 0.5rem 0.25rem;
            border: none;
            border-radius: 12px;
            background: transparent;
            color: var(--color-sidebar-texto);
            cursor: pointer;
            transition: background var(--transicion), color var(--transicion);
        }

        .l2-rail-boton-etiqueta {
            font-size: 0.62rem;
            line-height: 1.1;
            text-align: center;
        }

        .l2-rail-boton:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .l2-rail-item.activo {
            background: rgba(10, 132, 255, 0.55);
            color: #fff;
        }

        /* --- Segunda columna: detalle del ítem del riel activo, se oculta entera con la
           hamburguesa. Mismo vidrio esmerilado que el riel, en versión clara. --- */
        .l2-columna2 {
            width: 260px;
            flex-shrink: 0;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-right: 1px solid var(--color-borde);
        }

        .l2-columna2.l2-columna2-oculta {
            display: none;
        }

        .l2-panel {
            display: none;
            flex-direction: column;
            gap: 0.1rem;
            padding: 1.25rem 1rem;
        }

        .l2-panel.activo {
            display: flex;
        }

        .l2-panel-titulo {
            margin: 0 0 0.75rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-texto);
        }

        .l2-panel-grupo {
            margin: 0.85rem 0 0.25rem;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-texto-tenue);
        }

        .l2-panel-link {
            padding: 0.5rem 0.6rem;
            border-radius: var(--radio-chico);
            color: var(--color-texto);
            font-size: 0.88rem;
            text-decoration: none;
        }

        .l2-panel-link:hover {
            background: rgba(0, 0, 0, 0.05);
            text-decoration: none;
        }

        .l2-panel-link-destacado {
            background: var(--color-primario-suave);
            color: var(--color-primario);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .l2-panel-vacio {
            color: var(--color-texto-tenue);
            font-size: 0.85rem;
        }

        /* --- Espacio restante (a la derecha del riel y la columna 2): headerbar fija arriba, a
           todo lo ancho y sin flotar (igual que la .encabezado real de hoy); debajo, topbar +
           workspace sí flotando como tarjetas, con el fondo detrás de ambas al 50% de
           --color-fondo (ni opaco ni transparente del todo) para que la imagen de fondo se note en
           el espacio entre ellas. El workspace siempre ocupa el 100% del alto que quede libre — lo
           que se ajusta es su contenido interno (.l2-workspace-contenido), nunca la tarjeta misma. --- */
        .l2-espacio-restante {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: transparent;
        }

        .l2-headerbar {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.25rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--color-borde);
        }

        .l2-espacio-restante-resto {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(245, 245, 247, 0.5);
        }

        .l2-topbar,
        .l2-workspace {
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: var(--radio);
            box-shadow: var(--sombra);
        }

        .l2-volver {
            display: inline-flex;
            align-items: center;
            color: var(--color-texto-secundario);
            font-size: 0.85rem;
            text-decoration: none;
        }

        .l2-volver:hover {
            text-decoration: underline;
        }

        .l2-headerbar-titulo {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--color-texto);
        }

        .l2-topbar {
            padding: 0.4rem 0.6rem;
        }

        /* --- Workspace: única pieza que crece (flex: 1) para llenar el resto del alto --- */
        .l2-workspace {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .l2-workspace-contenido {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 1.75rem 2rem;
        }

        .l2-workspace-placeholder {
            max-width: 640px;
            padding: 1.5rem;
            border: 1px dashed var(--color-borde);
            border-radius: var(--radio);
        }

        .l2-workspace-placeholder h2 {
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="l2-layout">
        <aside class="l2-rail">
            <div class="l2-rail-hamburguesa-envoltorio">
                <button type="button" class="l2-rail-boton" id="l2-hamburguesa" title="Mostrar u ocultar la segunda columna" aria-label="Mostrar u ocultar la segunda columna">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    <span class="l2-rail-boton-etiqueta">Menú</span>
                </button>
            </div>

            <a href="index.php?ruta=dashboard" class="l2-rail-marca">S P P I</a>

            <nav class="l2-rail-nav">
                <button type="button" class="l2-rail-boton l2-rail-item activo" data-panel="peticiones" title="Peticiones">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
                    <span class="l2-rail-boton-etiqueta">Peticiones</span>
                </button>
                <button type="button" class="l2-rail-boton l2-rail-item" data-panel="presupuesto" title="Presupuesto">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <span class="l2-rail-boton-etiqueta">Presupuesto</span>
                </button>
                <button type="button" class="l2-rail-boton l2-rail-item" data-panel="proyectos" title="Proyectos">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    <span class="l2-rail-boton-etiqueta">Proyectos</span>
                </button>
                <button type="button" class="l2-rail-boton l2-rail-item" data-panel="analisis" title="Análisis">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span class="l2-rail-boton-etiqueta">Análisis</span>
                </button>
                <button type="button" class="l2-rail-boton l2-rail-item" data-panel="solicitudes" title="Solicitudes">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    <span class="l2-rail-boton-etiqueta">Solicitudes</span>
                </button>
            </nav>

            <div class="l2-rail-pie">
                <button type="button" class="l2-rail-boton l2-rail-item" data-panel="administracion" title="Administración">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span class="l2-rail-boton-etiqueta">Admin.</span>
                </button>
            </div>
        </aside>

        <div class="l2-columna2" id="l2-columna2">
            <div class="l2-panel activo" data-panel="peticiones">
                <h2 class="l2-panel-titulo">Peticiones</h2>
                <a href="index.php?ruta=peticiones" class="l2-panel-link l2-panel-link-destacado">Ir a Peticiones</a>
            </div>

            <div class="l2-panel" data-panel="presupuesto">
                <h2 class="l2-panel-titulo">Presupuesto</h2>
                <a href="index.php?ruta=gastos" class="l2-panel-link l2-panel-link-destacado">Gastos</a>

                <p class="l2-panel-grupo">Autogestión · Extensión</p>
                <?php if (empty($itemsExtensionL2)): ?>
                <p class="l2-panel-vacio">Sin ítems activos</p>
                <?php else: ?>
                <?php foreach ($itemsExtensionL2 as $itemL2): ?>
                <a href="index.php?ruta=extension&autogestion_id=<?= (int) $itemL2['id'] ?>" class="l2-panel-link"><?= htmlspecialchars($itemL2['nombre']) ?></a>
                <?php endforeach; ?>
                <?php endif; ?>

                <p class="l2-panel-grupo">Autogestión · Postgrado</p>
                <?php if (empty($itemsPostgradoL2)): ?>
                <p class="l2-panel-vacio">Sin ítems activos</p>
                <?php else: ?>
                <?php foreach ($itemsPostgradoL2 as $itemL2): ?>
                <a href="index.php?ruta=postgrado&autogestion_id=<?= (int) $itemL2['id'] ?>" class="l2-panel-link"><?= htmlspecialchars($itemL2['nombre']) ?></a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="l2-panel" data-panel="proyectos">
                <h2 class="l2-panel-titulo">Proyectos</h2>
                <a href="index.php?ruta=perfil-proyectos" class="l2-panel-link l2-panel-link-destacado">Perfil de proyectos</a>
            </div>

            <div class="l2-panel" data-panel="analisis">
                <h2 class="l2-panel-titulo">Análisis</h2>
                <p class="l2-panel-vacio">Todavía no hay nada aquí — queda reservado para más adelante.</p>
            </div>

            <div class="l2-panel" data-panel="solicitudes">
                <h2 class="l2-panel-titulo">Solicitudes</h2>
                <a href="index.php?ruta=solicitudes" class="l2-panel-link l2-panel-link-destacado">Ir a Solicitudes</a>
            </div>

            <div class="l2-panel" data-panel="administracion">
                <h2 class="l2-panel-titulo">Administración</h2>
                <a href="index.php?ruta=configuraciones" class="l2-panel-link">Configuraciones</a>
                <a href="index.php?ruta=usuarios" class="l2-panel-link">Usuarios</a>
                <a href="index.php?ruta=dev" class="l2-panel-link">Dev</a>
            </div>
        </div>

        <div class="l2-espacio-restante">
            <header class="l2-headerbar">
                <a href="index.php?ruta=dev" class="l2-volver">← Volver a Dev</a>
                <h1 class="l2-headerbar-titulo" id="l2-contenido-titulo">Peticiones</h1>
            </header>

            <div class="l2-espacio-restante-resto">
            <div class="l2-topbar">
                <div class="grupo-iconos grupo-basico">
                    <button type="button" class="icono-boton" title="Buscar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                    <button type="button" class="icono-boton" title="Filtrar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    </button>
                    <button type="button" class="icono-boton" title="Mostrar u ocultar columnas">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="18" rx="1"></rect><rect x="14" y="3" width="7" height="18" rx="1"></rect></svg>
                    </button>
                    <span class="separador-grupo-basico"></span>
                    <button type="button" class="icono-boton activo" title="Vista de tabla" aria-pressed="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="9" x2="9" y2="21"></line></svg>
                    </button>
                    <button type="button" class="icono-boton" title="Vista de gráfica" aria-pressed="false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </button>
                    <span class="separador-grupo-basico"></span>
                    <button type="button" class="icono-boton" title="Editar" disabled>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    </button>
                    <button type="button" class="icono-boton" title="Eliminar" disabled>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                    </button>
                    <span class="separador-grupo-basico"></span>
                    <button type="button" class="icono-boton icono-exportar" title="Exportar a Excel">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    </button>
                </div>
            </div>

            <div class="l2-workspace">
                <div class="l2-workspace-contenido">
                    <div class="l2-workspace-placeholder">
                        <h2>Workspace</h2>
                        <p class="texto-atenuado">
                            Aquí iría el contenido real de la página seleccionada. La tarjeta siempre
                            ocupa el 100% del alto disponible (headerbar y topbar de arriba son de alto
                            fijo) — lo único que cambia según el contenido es el scroll interno de este
                            bloque, nunca el tamaño de la tarjeta. La barra de opciones de arriba es la
                            misma referencia visual de Dev &gt; Tabla, aquí solo decorativa.
                        </p>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var columna2 = document.getElementById('l2-columna2');
            var hamburguesa = document.getElementById('l2-hamburguesa');
            var botonesRail = document.querySelectorAll('.l2-rail-item');
            var paneles = document.querySelectorAll('.l2-panel');
            var tituloContenido = document.getElementById('l2-contenido-titulo');

            hamburguesa.addEventListener('click', function () {
                columna2.classList.toggle('l2-columna2-oculta');
            });

            botonesRail.forEach(function (boton) {
                boton.addEventListener('click', function () {
                    var panelObjetivo = boton.dataset.panel;

                    botonesRail.forEach(function (b) { b.classList.remove('activo'); });
                    boton.classList.add('activo');

                    paneles.forEach(function (panel) {
                        panel.classList.toggle('activo', panel.dataset.panel === panelObjetivo);
                    });

                    columna2.classList.remove('l2-columna2-oculta');
                    tituloContenido.textContent = boton.title;
                });
            });
        });
    </script>
</body>
</html>
