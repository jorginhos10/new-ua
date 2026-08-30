<?php

session_start();

require_once __DIR__ . '/controlador/LoginControlador.php';
require_once __DIR__ . '/controlador/RegistroControlador.php';
require_once __DIR__ . '/controlador/DashboardControlador.php';
require_once __DIR__ . '/controlador/UsuarioControlador.php';
require_once __DIR__ . '/controlador/NecesidadControlador.php';
require_once __DIR__ . '/controlador/ProximamenteControlador.php';
require_once __DIR__ . '/controlador/ConfiguracionesControlador.php';
require_once __DIR__ . '/controlador/RolControlador.php';
require_once __DIR__ . '/controlador/EstamentoControlador.php';
require_once __DIR__ . '/controlador/LineaControlador.php';
require_once __DIR__ . '/controlador/MotorControlador.php';
require_once __DIR__ . '/controlador/ProyectoControlador.php';
require_once __DIR__ . '/controlador/GastoControlador.php';
require_once __DIR__ . '/controlador/RubroControlador.php';
require_once __DIR__ . '/controlador/ContratoComunControlador.php';
require_once __DIR__ . '/controlador/LineaInversionControlador.php';
require_once __DIR__ . '/controlador/SublineaInversionControlador.php';
require_once __DIR__ . '/controlador/RubroCategoriaControlador.php';
require_once __DIR__ . '/controlador/AnioPresupuestalControlador.php';
require_once __DIR__ . '/controlador/SedeControlador.php';
require_once __DIR__ . '/controlador/DependenciaControlador.php';
require_once __DIR__ . '/controlador/FacultadControlador.php';
require_once __DIR__ . '/controlador/RelojArenaControlador.php';
require_once __DIR__ . '/controlador/AutogestionControlador.php';
require_once __DIR__ . '/controlador/ExtensionControlador.php';
require_once __DIR__ . '/controlador/PostgradoControlador.php';
require_once __DIR__ . '/controlador/UnisaludControlador.php';
require_once __DIR__ . '/controlador/SinExcedentesControlador.php';
require_once __DIR__ . '/controlador/PerfilProyectosControlador.php';
require_once __DIR__ . '/controlador/SolicitudControlador.php';
require_once __DIR__ . '/controlador/SolicitudDetalleControlador.php';
require_once __DIR__ . '/controlador/GastoDetalleControlador.php';
require_once __DIR__ . '/controlador/PeticionesControlador.php';
require_once __DIR__ . '/controlador/JerarquiaControlador.php';
require_once __DIR__ . '/controlador/VariableMacroeconomicaControlador.php';
require_once __DIR__ . '/controlador/MensajeControlador.php';
require_once __DIR__ . '/controlador/PerfilControlador.php';
require_once __DIR__ . '/controlador/TechosControlador.php';
require_once __DIR__ . '/controlador/HistorialControlador.php';

$ruta = $_GET['ruta'] ?? 'login';

$rutaAMenuKey = [
    'peticiones' => 'peticiones',
    'consolidado-detalle' => 'peticiones',
    'consolidado-autogestion' => 'peticiones',
    'extension' => 'extension',
    'postgrado' => 'postgrado',
    'unisalud' => 'unisalud',
    'sin-excedentes' => 'sin-excedentes',
    'gastos' => 'gastos',
    'solicitudes' => 'solicitudes',
    'perfil-proyectos' => 'perfil-proyectos',
    'techos' => 'techos',
    'resumen-techos' => 'techos',
    'control-versiones' => 'techos',
    'configurar-presupuestos' => 'configuraciones',
    'configuraciones' => 'configuraciones',
    'usuarios' => 'configuraciones',
    'roles' => 'configuraciones',
    'estamentos' => 'configuraciones',
    'lineas' => 'configuraciones',
    'motores' => 'configuraciones',
    'proyectos' => 'configuraciones',
    'rubros' => 'configuraciones',
    'contratos-comunes' => 'configuraciones',
    'lineas-inversion' => 'configuraciones',
    'sublineas-inversion' => 'configuraciones',
    'rubro-categorias' => 'configuraciones',
    'anios-presupuestales' => 'configuraciones',
    'sedes' => 'configuraciones',
    'dependencias' => 'configuraciones',
    'facultades' => 'configuraciones',
    'jerarquias' => 'configuraciones',
    'variables-macroeconomicas' => 'configuraciones',
    'reloj-arena' => 'configuraciones',
];

if (
    !empty($_SESSION['usuario_id'])
    && ($_SESSION['usuario_rol'] ?? '') === 'administrador'
    && isset($rutaAMenuKey[$ruta])
) {
    require_once __DIR__ . '/modelo/Usuario.php';
    require_once __DIR__ . '/modelo/MenuPermiso.php';

    $usuarioActualRouter = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);

    if ($usuarioActualRouter !== null) {
        $permitidoRouter = (new MenuPermiso())->calcularPermitidoParaUsuario($usuarioActualRouter);

        if ($permitidoRouter !== null && !in_array($rutaAMenuKey[$ruta], $permitidoRouter, true)) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }
}

switch ($ruta) {
    case 'dashboard':
        (new DashboardControlador())->index();
        break;

    case 'configuraciones':
        (new ConfiguracionesControlador())->index();
        break;

    case 'usuarios':
        (new UsuarioControlador())->index();
        break;

    case 'sedes':
        (new SedeControlador())->index();
        break;

    case 'dependencias':
        (new DependenciaControlador())->index();
        break;

    case 'facultades':
        (new FacultadControlador())->index();
        break;

    case 'reloj-arena':
        (new RelojArenaControlador())->index();
        break;

    case 'autogestion':
        (new AutogestionControlador())->index();
        break;

    case 'roles':
        (new RolControlador())->index();
        break;

    case 'estamentos':
        (new EstamentoControlador())->index();
        break;

    case 'rubros':
        (new RubroControlador())->index();
        break;

    case 'contratos-comunes':
        (new ContratoComunControlador())->index();
        break;

    case 'lineas-inversion':
        (new LineaInversionControlador())->index();
        break;

    case 'sublineas-inversion':
        (new SublineaInversionControlador())->index();
        break;

    case 'rubro-categorias':
        (new RubroCategoriaControlador())->index();
        break;

    case 'anios-presupuestales':
        (new AnioPresupuestalControlador())->index();
        break;

    case 'configurar-presupuestos':
        (new AnioPresupuestalControlador())->configurarPresupuestos();
        break;

    case 'lineas':
        (new LineaControlador())->index();
        break;

    case 'motores':
        (new MotorControlador())->index();
        break;

    case 'proyectos':
        (new ProyectoControlador())->index();
        break;

    case 'gastos':
        (new GastoControlador())->index();
        break;

    case 'extension':
        (new ExtensionControlador())->index();
        break;

    case 'postgrado':
        (new PostgradoControlador())->index();
        break;

    case 'unisalud':
        (new UnisaludControlador())->index();
        break;

    case 'sin-excedentes':
        (new SinExcedentesControlador())->index();
        break;

    case 'formulario-invitado':
        (new NecesidadControlador())->index();
        break;

    case 'perfil-proyectos':
        (new PerfilProyectosControlador())->index();
        break;

    case 'perfil-proyectos-exportar':
        (new PerfilProyectosControlador())->exportar();
        break;

    case 'solicitudes':
        (new SolicitudControlador())->index();
        break;

    case 'solicitud-detalle':
        (new SolicitudDetalleControlador())->index();
        break;

    case 'gasto-detalle':
        (new GastoDetalleControlador())->index();
        break;

    case 'peticiones':
        (new PeticionesControlador())->index();
        break;

    case 'consolidado-detalle':
        (new PeticionesControlador())->detalle();
        break;

    case 'consolidado-autogestion':
        (new PeticionesControlador())->autogestion();
        break;

    case 'jerarquias':
        (new JerarquiaControlador())->index();
        break;

    case 'variables-macroeconomicas':
        (new VariableMacroeconomicaControlador())->index();
        break;

    case 'mensajes':
        (new MensajeControlador())->index();
        break;

    case 'perfil':
        (new PerfilControlador())->index();
        break;

    case 'mensajes-marcar-leido':
        (new MensajeControlador())->marcarLeido();
        break;

    case 'techos':
        (new TechosControlador())->index();
        break;

    case 'resumen-techos':
        (new TechosControlador())->resumen();
        break;

    case 'techos-alternar-bloqueo':
        (new TechosControlador())->alternarBloqueo();
        break;

    case 'control-versiones':
        (new HistorialControlador())->versiones();
        break;

    case 'proximamente':
        (new ProximamenteControlador())->index();
        break;

    case 'registro':
        (new RegistroControlador())->index();
        break;

    case 'logout':
        require __DIR__ . '/vista/login/logout.php';
        break;

    case 'login':
    default:
        (new LoginControlador())->index();
        break;
}
