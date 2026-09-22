<?php
// Tipo de cuenta crudo ('administrador'/'consejo_superior'/'invitado'), usado aquí solo para
// decidir qué bloque de menú renderizar — deliberadamente NO se llama $rolActual, porque
// encabezado.php ya usa ese nombre para el rol específico resuelto (Gestor/Avalador/Superadmin/
// etc., ver Rol::obtenerPorId()) y este archivo se incluye DESDE ADENTRO de encabezado.php: si
// reutilizara el mismo nombre, lo pisaría antes de que cualquier vista llegara a mostrarlo.
$tipoCuentaActual = $_SESSION['usuario_rol'] ?? '';
$rutaActual = $_GET['ruta'] ?? 'dashboard';

$menuPermitido = null; // null = sin restricción configurada (se muestra todo, o el fallback fijo)
$puedeVerActas = false;
$esDependenciaSuperadmin = false;
// Para Consulta (consejo_superior) y Formulador (invitado): si nadie configuró todavía una
// plantilla de menú para su tipo, se respeta el menú fijo de siempre (ver ramas de abajo) en vez
// de interpretar "sin restricción" como "mostrar todo" — así ningún usuario existente pierde ni
// gana acceso hasta que un administrador configure algo a propósito (Jerarquías > Mapa > ⚙, o
// Usuarios > Permisos).
$menuConfiguradoParaTipo = false;
$itemsMenuSidebar = [];

if (!empty($_SESSION['usuario_id']) && in_array($tipoCuentaActual, ['administrador', 'consejo_superior', 'invitado'], true)) {
    require_once __DIR__ . '/../../modelo/Usuario.php';
    require_once __DIR__ . '/../../modelo/MenuPermiso.php';
    require_once __DIR__ . '/../../modelo/Dependencia.php';

    $modeloUsuarioSidebar = new Usuario();
    $usuarioActualSidebar = $modeloUsuarioSidebar->obtenerPorId((int) $_SESSION['usuario_id']);

    if ($usuarioActualSidebar !== null) {
        $modeloMenuPermisoSidebar = new MenuPermiso();

        if ($tipoCuentaActual === 'invitado') {
            // Para Invitados, dependencia_id es su Facultad real (para poder enrutar su
            // necesidad al Gestor de esa Facultad) — NUNCA su alcance de menú: esa Facultad
            // puede tener (y normalmente tiene) una plantilla de menú pensada para el personal
            // administrativo real que trabaja ahí (Gastos, Solicitudes, Actas...), que un
            // invitado no debe heredar. Por eso, para este rol, calcularPermitidoParaUsuario()
            // no se usa (mezclaría ambos alcances) — solo se respeta una personalización
            // individual ya guardada (Usuarios > Formulador > Permisos).
            $permitidoSidebar = (int) ($usuarioActualSidebar['menu_personalizado'] ?? 0) === 1
                ? $modeloMenuPermisoSidebar->obtenerMenuUsuario((int) $usuarioActualSidebar['id'])
                : null;
        } else {
            $permitidoSidebar = $modeloMenuPermisoSidebar->calcularPermitidoParaUsuario($usuarioActualSidebar);
        }

        $menuConfiguradoParaTipo = $permitidoSidebar !== null;
        $menuPermitido = $permitidoSidebar === null ? null : array_flip($permitidoSidebar);

        if ($tipoCuentaActual === 'administrador' && !empty($usuarioActualSidebar['dependencia_id'])) {
            $dependenciaActualSidebar = (new Dependencia())->obtenerPorId((int) $usuarioActualSidebar['dependencia_id']);
            $puedeVerActas = $dependenciaActualSidebar !== null
                && in_array($dependenciaActualSidebar['tipo'] ?? '', ['Facultad', 'Vicerrectoria'], true);
            $esDependenciaSuperadmin = $dependenciaActualSidebar !== null
                && !empty($dependenciaActualSidebar['es_raiz_superadmin']);
        }
    }

    if (in_array($tipoCuentaActual, ['consejo_superior', 'invitado'], true)) {
        $itemsMenuSidebar = require __DIR__ . '/../../config/menu_items.php';
    }
}

$puedeVerMenu = static fn (string $clave): bool => $menuPermitido === null || isset($menuPermitido[$clave]);
$puedeVerActas = $puedeVerActas && $puedeVerMenu('actas');
?>
<aside class="barra-lateral">
    <a href="index.php?ruta=dashboard" class="marca">S P P I</a>
        <nav class="menu-lateral">
    <?php if ($tipoCuentaActual === 'administrador'): ?>
            <?php if ($puedeVerMenu('inicio') || $puedeVerMenu('peticiones')): ?>
            <p class="grupo-menu">Resumen</p>
            <?php if ($puedeVerMenu('inicio')): ?>
            <a href="index.php">Inicio</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('peticiones')): ?>
            <a href="index.php?ruta=peticiones" class="<?= $rutaActual === 'peticiones' ? 'activo' : '' ?>">Peticiones</a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($puedeVerMenu('extension') || $puedeVerMenu('postgrado') || $puedeVerMenu('unisalud')): ?>
            <p class="grupo-menu">Autogestión</p>
            <?php if ($puedeVerMenu('extension')):
                require_once __DIR__ . '/../../modelo/AutogestionItem.php';
                $autogestionItemsSidebar = (new AutogestionItem())->obtenerActivos('extension');
            ?>
            <?php if (!empty($autogestionItemsSidebar)):
                $tabAutogestionSidebar = ($rutaActual === 'extension' && ($_GET['tab'] ?? '') === 'egresos') ? 'egresos' : 'ingresos';
                $anioAutogestionSidebar = $rutaActual === 'extension' ? (int) ($_GET['anio_id'] ?? 0) : 0;
                $autogestionSeleccionadoSidebar = $rutaActual === 'extension' ? (int) ($_GET['autogestion_id'] ?? 0) : 0;
            ?>
            <div class="menu-autogestion-item">
                <button type="button" class="enlace-lateral-toggle<?= $rutaActual === 'extension' ? ' activo' : '' ?>">
                    <span>Extensión</span>
                    <span class="menu-autogestion-item-flecha">▾</span>
                </button>
                <div class="menu-autogestion-item-dropdown<?= $rutaActual === 'extension' ? ' abierto' : '' ?>">
                    <?php foreach ($autogestionItemsSidebar as $itemAutogestionSidebar): ?>
                    <a
                        href="index.php?ruta=extension&tab=<?= htmlspecialchars($tabAutogestionSidebar) ?>&autogestion_id=<?= (int) $itemAutogestionSidebar['id'] ?>&anio_id=<?= $anioAutogestionSidebar ?>"
                        class="<?= $autogestionSeleccionadoSidebar === (int) $itemAutogestionSidebar['id'] ? 'activo' : '' ?>"
                    ><?= htmlspecialchars($itemAutogestionSidebar['nombre']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <a href="index.php?ruta=extension" class="<?= $rutaActual === 'extension' ? 'activo' : '' ?>">Extensión</a>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($puedeVerMenu('postgrado')):
                require_once __DIR__ . '/../../modelo/AutogestionItem.php';
                $autogestionItemsSidebarPostgrado = (new AutogestionItem())->obtenerActivos('postgrado');
            ?>
            <?php if (!empty($autogestionItemsSidebarPostgrado)):
                $tabAutogestionSidebarPostgrado = ($rutaActual === 'postgrado' && ($_GET['tab'] ?? '') === 'egresos') ? 'egresos' : 'ingresos';
                $anioAutogestionSidebarPostgrado = $rutaActual === 'postgrado' ? (int) ($_GET['anio_id'] ?? 0) : 0;
                $autogestionSeleccionadoSidebarPostgrado = $rutaActual === 'postgrado' ? (int) ($_GET['autogestion_id'] ?? 0) : 0;
            ?>
            <div class="menu-autogestion-item">
                <button type="button" class="enlace-lateral-toggle<?= $rutaActual === 'postgrado' ? ' activo' : '' ?>">
                    <span>Postgrado</span>
                    <span class="menu-autogestion-item-flecha">▾</span>
                </button>
                <div class="menu-autogestion-item-dropdown<?= $rutaActual === 'postgrado' ? ' abierto' : '' ?>">
                    <?php foreach ($autogestionItemsSidebarPostgrado as $itemAutogestionSidebarPostgrado): ?>
                    <a
                        href="index.php?ruta=postgrado&tab=<?= htmlspecialchars($tabAutogestionSidebarPostgrado) ?>&autogestion_id=<?= (int) $itemAutogestionSidebarPostgrado['id'] ?>&anio_id=<?= $anioAutogestionSidebarPostgrado ?>"
                        class="<?= $autogestionSeleccionadoSidebarPostgrado === (int) $itemAutogestionSidebarPostgrado['id'] ? 'activo' : '' ?>"
                    ><?= htmlspecialchars($itemAutogestionSidebarPostgrado['nombre']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <a href="index.php?ruta=postgrado" class="<?= $rutaActual === 'postgrado' ? 'activo' : '' ?>">Postgrado</a>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($puedeVerMenu('unisalud')): ?>
            <a href="index.php?ruta=unisalud" class="<?= $rutaActual === 'unisalud' ? 'activo' : '' ?>">Unidad de Salud</a>
            <?php endif; ?>
            <?php // Convenios (sin-excedentes) se oculta del sidebar: su único propósito era sumar al
            // tope de la tarjeta de Autogestión del Dashboard, que ahora mide solo Extensión — el
            // controlador, las rutas y los datos ya registrados siguen intactos (ver
            // SinExcedentesControlador), solo se quitó el enlace de navegación. ?>
            <?php endif; ?>

            <?php if ($puedeVerMenu('gastos') || $puedeVerMenu('solicitudes') || $puedeVerMenu('techos') || $puedeVerActas): ?>
            <p class="grupo-menu">Egresos</p>
            <?php if ($puedeVerMenu('gastos')): ?>
            <a href="index.php?ruta=gastos" class="<?= $rutaActual === 'gastos' ? 'activo' : '' ?>">Gastos</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('solicitudes')): ?>
            <a href="index.php?ruta=solicitudes" class="<?= $rutaActual === 'solicitudes' ? 'activo' : '' ?>">Solicitudes</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('techos')): ?>
            <a href="index.php?ruta=techos" class="<?= in_array($rutaActual, ['techos', 'control-versiones'], true) ? 'activo' : '' ?>">Techos</a>
            <?php endif; ?>
            <?php if ($puedeVerActas): ?>
            <a href="index.php?ruta=actas" class="<?= $rutaActual === 'actas' ? 'activo' : '' ?>">Actas</a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($puedeVerMenu('perfil-proyectos')): ?>
            <p class="grupo-menu">Proyectos</p>
            <a href="index.php?ruta=perfil-proyectos" class="<?= $rutaActual === 'perfil-proyectos' ? 'activo' : '' ?>">Perfil de proyectos</a>
            <?php endif; ?>

            <?php if ($puedeVerMenu('configuraciones') || $puedeVerMenu('usuarios') || $esDependenciaSuperadmin): ?>
            <p class="grupo-menu">Administración</p>
            <?php if ($puedeVerMenu('configuraciones')): ?>
            <a href="index.php?ruta=configuraciones" class="<?= in_array($rutaActual, ['configuraciones', 'usuarios', 'roles', 'estamentos', 'lineas', 'motores', 'proyectos', 'rubros', 'anios-presupuestales', 'sedes', 'dependencias', 'facultades', 'jerarquias', 'variables-macroeconomicas', 'reloj-arena'], true) ? 'activo' : '' ?>">Configuraciones</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('usuarios')): ?>
            <a href="index.php?ruta=usuarios" class="<?= $rutaActual === 'usuarios' ? 'activo' : '' ?>">Usuarios</a>
            <?php endif; ?>
            <?php if ($esDependenciaSuperadmin): ?>
            <a href="index.php?ruta=dev" class="<?= in_array($rutaActual, ['dev', 'dev-vista'], true) ? 'activo' : '' ?>">Dev</a>
            <?php endif; ?>
            <?php endif; ?>

            <p class="grupo-menu">Documentación</p>
            <a href="publico/documentos/ficha-tecnica.docx">Ficha técnica</a>
            <a href="publico/documentos/esencia-del-software.docx">¿Para qué sirve?</a>
    <?php elseif ($tipoCuentaActual === 'consejo_superior'): ?>
            <?php
            // "Consulta" ya no es un enlace fijo: es una opción más de config/menu_items.php,
            // igual que el resto — se muestra/oculta desde Jerarquías > Mapa > ⚙ o desde
            // Usuarios > Consulta > Permisos. Mientras nadie configure nada para este tipo, el
            // valor por defecto es mostrar solo "Consulta" (en vez de "sin restricción = todo").
            $puedeVerMenuConsejo = $menuConfiguradoParaTipo ? $puedeVerMenu : static fn (string $clave): bool => $clave === 'consulta';
            ?>
            <?php foreach ($itemsMenuSidebar as $grupoNombreSidebar => $itemsGrupoSidebar): ?>
            <?php $clavesVisiblesSidebar = array_filter(array_keys($itemsGrupoSidebar), $puedeVerMenuConsejo); ?>
            <?php if (!empty($clavesVisiblesSidebar)): ?>
            <p class="grupo-menu"><?= htmlspecialchars($grupoNombreSidebar) ?></p>
            <?php foreach ($clavesVisiblesSidebar as $claveSidebar): ?>
            <a href="index.php?ruta=<?= htmlspecialchars($claveSidebar) ?>" class="<?= $rutaActual === $claveSidebar ? 'activo' : '' ?>"><?= htmlspecialchars($itemsGrupoSidebar[$claveSidebar]) ?></a>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php endforeach; ?>
    <?php elseif ($tipoCuentaActual === 'invitado'): ?>
            <p class="grupo-menu">Resumen</p>
            <a href="index.php" class="<?= in_array($rutaActual, ['dashboard', 'perfil-proyectos'], true) ? 'activo' : '' ?>">Inicio</a>

            <?php if ($menuConfiguradoParaTipo): ?>
                <?php foreach ($itemsMenuSidebar as $grupoNombreSidebar => $itemsGrupoSidebar): ?>
                <?php $clavesVisiblesSidebar = array_filter(array_keys($itemsGrupoSidebar), $puedeVerMenu); ?>
                <?php if (!empty($clavesVisiblesSidebar)): ?>
                <p class="grupo-menu"><?= htmlspecialchars($grupoNombreSidebar) ?></p>
                <?php foreach ($clavesVisiblesSidebar as $claveSidebar): ?>
                <a href="index.php?ruta=<?= htmlspecialchars($claveSidebar) ?>" class="<?= $rutaActual === $claveSidebar ? 'activo' : '' ?>"><?= htmlspecialchars($itemsGrupoSidebar[$claveSidebar]) ?></a>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <p class="grupo-menu">Documentación</p>
            <a href="publico/documentos/ficha-tecnica.docx">Ficha técnica</a>
            <a href="publico/documentos/esencia-del-software.docx">¿Para qué sirve?</a>
    <?php else: ?>
            <p class="grupo-menu">Resumen</p>
            <a href="index.php">Inicio</a>
            <a href="index.php?ruta=dashboard" class="<?= $rutaActual === 'dashboard' ? 'activo' : '' ?>">Dashboard</a>

            <p class="grupo-menu">Documentación</p>
            <a href="publico/documentos/ficha-tecnica.docx">Ficha técnica</a>
            <a href="publico/documentos/esencia-del-software.docx">¿Para qué sirve?</a>
    <?php endif; ?>
        </nav>
</aside>
