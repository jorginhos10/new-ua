<?php
require_once __DIR__ . '/../../modelo/Dependencia.php';
$tituloPagina = $tituloPagina ?? 'Sistema';
$nombreActual = $_SESSION['usuario_nombre'] ?? '';
$rolActual = $_SESSION['usuario_rol'] ?? '';
$estamentoActual = $_SESSION['usuario_estamento'] ?? '—';
$dependenciaActual = $_SESSION['usuario_dependencia'] ?? '—';
$esSuperAdminActual = false;
$esDependenciaRaizActual = false;
if (!empty($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/../../modelo/Usuario.php';
    $usuarioEncabezado = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
    $esSuperAdminActual = $usuarioEncabezado !== null && (int) ($usuarioEncabezado['es_super_admin'] ?? 0) === 1;
    $dependenciaEncabezado = !empty($usuarioEncabezado['dependencia_id'])
        ? (new Dependencia())->obtenerPorId((int) $usuarioEncabezado['dependencia_id'])
        : null;
    // "Auditar" (ver todo lo que cae en el árbol de dependencias, sin exigir ser el destinatario
    // exacto de cada ítem) es solo para la dependencia RAÍZ del superadmin — un concepto distinto
    // al flag usuario.es_super_admin de arriba (que solo decide la estrellita del nombre).
    $esDependenciaRaizActual = $dependenciaEncabezado !== null && !empty($dependenciaEncabezado['es_raiz_superadmin']);
    if (!isset($_SESSION['usuario_dependencia']) && $dependenciaEncabezado !== null) {
        $dependenciaActual = $dependenciaEncabezado['nombre'] ?? '—';
        $_SESSION['usuario_dependencia'] = $dependenciaActual;
    }
    if ($esSuperAdminActual) {
        $rolActual = 'Superadmin';
    } elseif ($usuarioEncabezado !== null && !empty($usuarioEncabezado['rol_id'])) {
        require_once __DIR__ . '/../../modelo/Rol.php';
        $rolCatalogoEncabezado = (new Rol())->obtenerPorId((int) $usuarioEncabezado['rol_id']);
        $rolActual = $rolCatalogoEncabezado['nombre'] ?? $rolActual;
        // Cuentas de tipo Consejo Superior: su rol "Consulta" se muestra como "Consejo Superior"
        // (igual que en la pestaña Consejo Superior de Usuarios).
        if (($_SESSION['usuario_rol'] ?? '') === 'consejo_superior' && $rolActual === 'Consulta') {
            $rolActual = 'Consejo Superior';
        }
    }
}
$urlActualEncabezado = 'index.php?' . http_build_query($_GET);
$inicialAvatar = $nombreActual !== '' ? mb_strtoupper(mb_substr($nombreActual, 0, 1)) : '?';
$versionCss = @filemtime(__DIR__ . '/../../publico/css/estilo.css') ?: time();
$versionJs = @filemtime(__DIR__ . '/../../publico/js/app.js') ?: time();

$iconosPorRuta = [
    'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="16" width="7" height="5" rx="1.5"></rect>',
    'peticiones' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>',
    'solicitudes' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
    'extension' => '<path d="M22 10L12 5 2 10l10 5 10-5z"></path><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"></path>',
    'postgrado' => '<path d="M22 10L12 5 2 10l10 5 10-5z"></path><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"></path>',
    'unisalud' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
    'gastos' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>',
    'perfil-proyectos' => '<rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
    'mensajes' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22 6 12 13 2 6"></polyline>',
    'techos' => '<path d="M3 21h18"></path><path d="M5 21V10l7-7 7 7v11"></path><line x1="9" y1="21" x2="9" y2="14"></line><line x1="15" y1="21" x2="15" y2="14"></line>',
    'control-versiones' => '<circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 16 14"></polyline>',
];

$iconoConfiguraciones = '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>';
foreach (['configuraciones', 'usuarios', 'roles', 'lineas', 'motores', 'proyectos', 'rubros', 'anios-presupuestales', 'sedes', 'dependencias', 'jerarquias', 'variables-macroeconomicas', 'repositorios'] as $rutaConfig) {
    $iconosPorRuta[$rutaConfig] = $iconoConfiguraciones;
}

$rutaParaIcono = $_GET['ruta'] ?? 'dashboard';
$iconoPaginaSvg = $iconosPorRuta[$rutaParaIcono] ?? $iconosPorRuta['dashboard'];

$mensajesRecientes = [];
$mensajesNoLeidos = 0;
if (!empty($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/../../modelo/Mensaje.php';
    $modeloMensajeEncabezado = new Mensaje();
    $mensajesRecientes = $modeloMensajeEncabezado->obtenerRecientesRecibidos((int) $_SESSION['usuario_id'], 5);
    $mensajesNoLeidos = $modeloMensajeEncabezado->contarNoLeidos((int) $_SESSION['usuario_id']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <link rel="stylesheet" href="publico/css/estilo.css?v=<?= $versionCss ?>">
</head>
<body>
    <div class="layout">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <div class="contenido-principal">
            <?php if (!empty($_SESSION['impersonador_id'])): ?>
            <div class="banner-impersonando">
                Estás viendo la aplicación como <strong><?= htmlspecialchars($nombreActual) ?></strong>.
                <a href="index.php?ruta=dejar-de-impersonar">Volver a mi cuenta</a>
            </div>
            <?php endif; ?>
            <header class="encabezado">
                <div class="titulo-pagina-grupo">
                    <button type="button" id="boton-hamburguesa" class="boton-hamburguesa" aria-label="Mostrar u ocultar menú">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <span class="icono-pagina" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $iconoPaginaSvg ?></svg>
                    </span>
                    <h1 class="titulo-pagina"><?= htmlspecialchars($tituloPagina) ?></h1>
                </div>

                <div class="grupo-acciones-encabezado">
                    <?php // Acciones propias de la página (ej. Análisis: exportar, snapshot, Tiempo real/Repositorio/Usuario). ?>
                    <?php if (!empty($accionesEncabezadoHtml)): ?>
                    <div class="acciones-encabezado-pagina"><?= $accionesEncabezadoHtml ?></div>
                    <?php endif; ?>
                    <?php if ($esDependenciaRaizActual): ?>
                    <a
                        href="index.php?ruta=alternar-auditoria&volver=<?= urlencode($urlActualEncabezado) ?>"
                        class="boton-auditar<?= !empty($_SESSION['modo_auditoria']) ? ' activo' : '' ?>"
                        title="Auditar: ver todo lo pendiente de tu árbol de dependencias, sin importar a quién esté dirigido"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Auditar
                    </a>
                    <?php endif; ?>

                    <div class="menu-mensajes">
                        <button type="button" id="menu-mensajes-boton" class="menu-mensajes-boton" aria-label="Mensajes">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22 6 12 13 2 6"></polyline></svg>
                            <?php if ($mensajesNoLeidos > 0): ?>
                            <span class="insignia-no-leidos"><?= $mensajesNoLeidos > 9 ? '9+' : $mensajesNoLeidos ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="menu-mensajes-dropdown" class="menu-mensajes-dropdown">
                            <div class="menu-mensajes-cabecera">Mensajes</div>
                            <?php if (empty($mensajesRecientes)): ?>
                            <p class="menu-mensajes-vacio">No hay mensajes</p>
                            <?php else: ?>
                            <div class="menu-mensajes-lista">
                                <?php foreach ($mensajesRecientes as $mensajeReciente): ?>
                                <button
                                    type="button"
                                    class="menu-mensajes-item boton-ver-mensaje<?= (int) $mensajeReciente['leido'] === 0 ? ' no-leido' : '' ?>"
                                    data-id="<?= (int) $mensajeReciente['id'] ?>"
                                    data-asunto="<?= htmlspecialchars($mensajeReciente['asunto']) ?>"
                                    data-cuerpo="<?= htmlspecialchars($mensajeReciente['cuerpo']) ?>"
                                    data-remitente="<?= htmlspecialchars($mensajeReciente['remitente_nombre']) ?>"
                                    data-destinatario="<?= htmlspecialchars($nombreActual) ?>"
                                    data-fecha="<?= htmlspecialchars($mensajeReciente['creado_en']) ?>"
                                    data-leido="<?= (int) $mensajeReciente['leido'] ?>"
                                    data-contraparte-id="<?= (int) $mensajeReciente['remitente_id'] ?>"
                                >
                                    <span class="menu-mensajes-item-remitente"><?= htmlspecialchars($mensajeReciente['remitente_nombre']) ?></span>
                                    <span class="menu-mensajes-item-asunto"><?= htmlspecialchars($mensajeReciente['asunto']) ?></span>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <a href="index.php?ruta=mensajes" class="menu-mensajes-ver-todo">Ver todo</a>
                        </div>
                    </div>

                    <div class="menu-usuario">
                        <button type="button" id="menu-usuario-boton" class="menu-usuario-boton">
                            <span class="avatar-usuario"><?= htmlspecialchars($inicialAvatar) ?></span>
                            <span class="nombre-usuario"><?= $esSuperAdminActual ? '<span class="estrella-super-admin" title="Super administrador">★</span> ' : '' ?><?= htmlspecialchars($nombreActual) ?></span>
                            <span class="flecha-menu">▾</span>
                        </button>
                        <div id="menu-usuario-dropdown" class="menu-usuario-dropdown">
                            <div class="menu-usuario-detalle">
                                <span class="detalle-usuario">Estamento: <?= htmlspecialchars($estamentoActual) ?></span>
                                <span class="detalle-usuario">Dependencia: <?= htmlspecialchars(Dependencia::nombreVisible($dependenciaActual)) ?></span>
                              <!--  <span class="detalle-usuario">Rol: <?= htmlspecialchars($rolActual) ?></span> -->
                            </div>
                            <a href="index.php?ruta=perfil">Perfil</a>
                            <a href="#" id="boton-abrir-acerca-de" data-abrir-acerca-de>Acerca de nosotros</a>
                            <a href="index.php?ruta=logout">Cerrar sesión</a>
                        </div>
                    </div>
                </div>
            </header>

            <?php // Mismo "Acerca de nosotros" de la app FOR-DE-144 (uniatlantico/vista/complementos/header.php). ?>
            <div id="modal-acerca-de" class="modal-fondo">
                <div class="modal-caja acerca-de-caja" role="dialog" aria-modal="true" aria-labelledby="acerca-de-titulo">
                    <button type="button" class="modal-cerrar acerca-de-cerrar" id="boton-cerrar-acerca-de" aria-label="Cerrar">&times;</button>
                    <div class="acerca-de-cuerpo">
                        <span class="acerca-de-icono" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"></path></svg>
                        </span>
                        <h2 id="acerca-de-titulo">Acerca de nosotros</h2>
                        <p>
                            Este software fue diseñado y desarrollado por
                            <strong>Cloud Control Technology</strong>, por
                            <strong>Jorge Albeiro Valencia Bolívar</strong>, quien otorga
                            uso, adquisición y entrega total de propiedad intelectual a la
                            <strong>Universidad del Atlántico</strong> y todas sus asociaciones,
                            dependencias y organismos afiliados. La Universidad del
                            Atlántico es titular y propietaria de todos los derechos sobre
                            esta plataforma y sus desarrollos derivados.
                        </p>
                        <div class="acerca-de-agradecimiento">
                            Agradecimiento especial a <strong>Gabriel Romero</strong>,
                            <strong>Shirlene Buelvas</strong> y <strong>Graciela Angulo</strong>
                            por ser fuente de apoyo e inspiración para las funciones y experiencia
                            de usuario en los desarrollos que se han venido realizando en la
                            Oficina de Planeación de la Universidad del Atlántico sede Norte.
                        </div>
                    </div>
                    <div class="acerca-de-pie">
                        <span class="acerca-de-pie-icono" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V10l7-5 7 5v11"></path><path d="M9 21v-6h6v6"></path></svg>
                        </span>
                        <span>&copy; 2026 Cloud Control Technology &middot; Universidad del Atlántico</span>
                    </div>
                </div>
            </div>
            <script>
            (function () {
                var modal = document.getElementById('modal-acerca-de');
                if (!modal) { return; }
                function cerrar() { modal.classList.remove('abierto'); }
                // Cualquier enlace con data-abrir-acerca-de lo abre (menú del usuario y la sección
                // "Documentación" del menú lateral); delegado en el documento porque el menú lateral
                // se dibuja después de este script.
                document.addEventListener('click', function (evento) {
                    var enlace = evento.target.closest('[data-abrir-acerca-de]');
                    if (!enlace) { return; }
                    evento.preventDefault();
                    var dropdown = document.getElementById('menu-usuario-dropdown');
                    if (dropdown) { dropdown.classList.remove('abierto'); }
                    modal.classList.add('abierto');
                });
                document.getElementById('boton-cerrar-acerca-de').addEventListener('click', cerrar);
                modal.addEventListener('click', function (evento) { if (evento.target === modal) { cerrar(); } });
                document.addEventListener('keydown', function (evento) { if (evento.key === 'Escape') { cerrar(); } });
            })();
            </script>

            <div id="modal-ver-mensaje" class="modal-fondo">
                <div class="modal-caja modal-caja-mensaje">
                    <div class="modal-cabecera">
                        <div class="ver-mensaje-titulo">
                            <span class="ver-mensaje-icono" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 6L2 7"></path></svg>
                            </span>
                            <h2 id="ver-mensaje-asunto"></h2>
                        </div>
                        <button type="button" id="boton-cerrar-modal-ver-mensaje" class="modal-cerrar" aria-label="Cerrar">&times;</button>
                    </div>
                    <div class="ver-mensaje-meta">
                        <span class="ver-mensaje-avatar" id="ver-mensaje-avatar" aria-hidden="true"></span>
                        <div class="ver-mensaje-meta-texto">
                            <strong id="ver-mensaje-remitente"></strong>
                            <span class="ver-mensaje-meta-detalle">
                                Para <span id="ver-mensaje-destinatario"></span>
                                <span class="ver-mensaje-separador" aria-hidden="true">·</span>
                                <time id="ver-mensaje-fecha"></time>
                            </span>
                        </div>
                    </div>
                    <p class="detalle-mensaje-cuerpo" id="ver-mensaje-cuerpo"></p>
                    <div class="modal-pie">
                        <button type="button" class="boton-secundario" data-cerrar-ver-mensaje>Cerrar</button>
                        <a id="boton-responder-mensaje" href="#" class="boton-agregar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 17 4 12 9 7"></polyline><path d="M20 18v-2a4 4 0 0 0-4-4H4"></path></svg>
                            Responder
                        </a>
                    </div>
                </div>
            </div>

            <main class="area-contenido">
