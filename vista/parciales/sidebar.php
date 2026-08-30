<?php
$rolActual = $_SESSION['usuario_rol'] ?? '';
$rutaActual = $_GET['ruta'] ?? 'dashboard';

$menuPermitido = null; // null = sin restricción configurada (se muestra todo)

if (!empty($_SESSION['usuario_id']) && $rolActual === 'administrador') {
    require_once __DIR__ . '/../../modelo/Usuario.php';
    require_once __DIR__ . '/../../modelo/MenuPermiso.php';

    $modeloUsuarioSidebar = new Usuario();
    $usuarioActualSidebar = $modeloUsuarioSidebar->obtenerPorId((int) $_SESSION['usuario_id']);

    if ($usuarioActualSidebar !== null) {
        $permitidoSidebar = (new MenuPermiso())->calcularPermitidoParaUsuario($usuarioActualSidebar);
        $menuPermitido = $permitidoSidebar === null ? null : array_flip($permitidoSidebar);
    }
}

$puedeVerMenu = static fn (string $clave): bool => $menuPermitido === null || isset($menuPermitido[$clave]);
?>
<aside class="barra-lateral">
    <a href="index.php?ruta=dashboard" class="marca">Programacion<br>Presupuestal</a>
        <nav class="menu-lateral">
    <?php if ($rolActual === 'administrador'): ?>
            <?php if ($puedeVerMenu('inicio') || $puedeVerMenu('peticiones')): ?>
            <p class="grupo-menu">Resumen</p>
            <?php if ($puedeVerMenu('inicio')): ?>
            <a href="index.php">Inicio</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('peticiones')): ?>
            <a href="index.php?ruta=peticiones" class="<?= $rutaActual === 'peticiones' ? 'activo' : '' ?>">Peticiones</a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($puedeVerMenu('extension') || $puedeVerMenu('postgrado') || $puedeVerMenu('unisalud') || $puedeVerMenu('sin-excedentes')): ?>
            <p class="grupo-menu">Autogestión</p>
            <?php if ($puedeVerMenu('extension')): ?>
            <a href="index.php?ruta=extension" class="<?= $rutaActual === 'extension' ? 'activo' : '' ?>">Extensión</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('postgrado')): ?>
            <a href="index.php?ruta=postgrado" class="<?= $rutaActual === 'postgrado' ? 'activo' : '' ?>">Postgrado</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('unisalud')): ?>
            <a href="index.php?ruta=unisalud" class="<?= $rutaActual === 'unisalud' ? 'activo' : '' ?>">Unidad de Salud</a>
            <?php endif; ?>
            <?php if ($puedeVerMenu('sin-excedentes')): ?>
            <a href="index.php?ruta=sin-excedentes" class="<?= $rutaActual === 'sin-excedentes' ? 'activo' : '' ?>">Sin excedentes</a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($puedeVerMenu('gastos') || $puedeVerMenu('solicitudes') || $puedeVerMenu('techos')): ?>
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
            <?php endif; ?>

            <?php if ($puedeVerMenu('perfil-proyectos')): ?>
            <p class="grupo-menu">Proyectos</p>
            <a href="index.php?ruta=perfil-proyectos" class="<?= $rutaActual === 'perfil-proyectos' ? 'activo' : '' ?>">Perfil de proyectos</a>
            <?php endif; ?>

            <?php if ($puedeVerMenu('configuraciones')): ?>
            <p class="grupo-menu">Administración</p>
            <a href="index.php?ruta=configuraciones" class="<?= in_array($rutaActual, ['configuraciones', 'usuarios', 'roles', 'estamentos', 'lineas', 'motores', 'proyectos', 'rubros', 'anios-presupuestales', 'sedes', 'dependencias', 'facultades', 'jerarquias', 'variables-macroeconomicas', 'reloj-arena'], true) ? 'activo' : '' ?>">Configuraciones</a>
            <?php endif; ?>
    <?php else: ?>
            <p class="grupo-menu">Resumen</p>
            <a href="index.php">Inicio</a>
            <a href="index.php?ruta=dashboard" class="<?= $rutaActual === 'dashboard' ? 'activo' : '' ?>">Dashboard</a>
            <a href="index.php?ruta=formulario-invitado" class="<?= $rutaActual === 'formulario-invitado' ? 'activo' : '' ?>">Formulario de necesidades</a>
    <?php endif; ?>
        </nav>
</aside>
