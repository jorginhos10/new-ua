<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/TipoDependenciaRol.php';
require_once __DIR__ . '/../modelo/MenuPermiso.php';
require_once __DIR__ . '/../modelo/Estamento.php';

class UsuarioControlador
{
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Dependencia $modeloDependencia;
    private TipoDependenciaRol $modeloRolesPorTipo;
    private MenuPermiso $modeloMenuPermiso;
    private Estamento $modeloEstamento;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloDependencia = new Dependencia();
        $this->modeloRolesPorTipo = new TipoDependenciaRol();
        $this->modeloMenuPermiso = new MenuPermiso();
        $this->modeloEstamento = new Estamento();
    }

    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'eliminar') {
                $this->eliminar();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } elseif ($accion === 'actualizar_permisos') {
                [$error, $exito] = $this->actualizarPermisos();
            } else {
                [$error, $exito] = $this->guardarAdministrador();
            }
        }

        $tab = ($_GET['tab'] ?? 'administradores') === 'invitados' ? 'invitados' : 'administradores';

        $administradores = $this->modeloUsuario->obtenerPorRol('administrador');
        $invitados = $this->modeloUsuario->obtenerPorRol('invitado');
        $roles = $this->modeloRol->obtenerTodos();
        $dependencias = $this->modeloDependencia->obtenerActivas();
        $estamentos = $this->modeloEstamento->obtenerTodos();
        $rolesPorTipo = $this->modeloRolesPorTipo->obtenerMapaCompleto();
        $tiposDependencia = $this->modeloRolesPorTipo->obtenerTiposDisponibles();
        $itemsMenu = require __DIR__ . '/../config/menu_items.php';
        $menuPorTipo = $this->modeloMenuPermiso->obtenerPlantillasCompletas();

        foreach ($administradores as &$admin) {
            $admin['menu_efectivo'] = $this->modeloMenuPermiso->calcularPermitidoParaUsuario($admin) ?? [];
        }
        unset($admin);

        $usuarioActualIndex = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $esSuperAdminActual = $usuarioActualIndex !== null && (int) ($usuarioActualIndex['es_super_admin'] ?? 0) === 1;

        require __DIR__ . '/../vista/usuarios/index.php';
    }

    /**
     * Permite a un superadmin entrar a la cuenta de otro usuario administrativo (Gestor, Avalador,
     * etc.) para ver la aplicación tal como la ve él, sin conocer ni cambiar su contraseña. Guarda
     * el id del superadmin en 'impersonador_id' para poder volver con dejarDeImpersonar(); no anida
     * impersonaciones (si ya se está impersonando, no se sobrescribe el id original).
     */
    public function impersonar(): void
    {
        $this->requerirSuperAdmin();

        $idObjetivo = (int) ($_GET['id'] ?? 0);
        $usuarioObjetivo = $idObjetivo > 0 ? $this->modeloUsuario->obtenerPorIdConNombres($idObjetivo) : null;

        if ($usuarioObjetivo === null || (int) ($usuarioObjetivo['es_super_admin'] ?? 0) === 1 || $idObjetivo === (int) $_SESSION['usuario_id']) {
            header('Location: index.php?ruta=usuarios');
            exit;
        }

        if (empty($_SESSION['impersonador_id'])) {
            $_SESSION['impersonador_id'] = (int) $_SESSION['usuario_id'];
        }

        $_SESSION['usuario_id'] = (int) $usuarioObjetivo['id'];
        $_SESSION['usuario_nombre'] = $usuarioObjetivo['nombre'];
        $_SESSION['usuario_rol'] = $usuarioObjetivo['rol'];
        $_SESSION['usuario_rol_nombre'] = $usuarioObjetivo['rol_nombre'] ?? '—';
        $_SESSION['usuario_estamento'] = $usuarioObjetivo['estamento_nombre'] ?? '—';
        $_SESSION['usuario_dependencia'] = $usuarioObjetivo['dependencia_nombre'] ?? '—';
        $_SESSION['usuario_super_admin'] = false;

        header('Location: index.php?ruta=dashboard');
        exit;
    }

    /**
     * Restaura la sesión del superadmin que estaba impersonando a alguien. No usa logout.php
     * porque ese destruye toda la sesión; aquí solo se reponen las claves de usuario.
     */
    public function dejarDeImpersonar(): void
    {
        if (empty($_SESSION['impersonador_id'])) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $superAdmin = $this->modeloUsuario->obtenerPorIdConNombres((int) $_SESSION['impersonador_id']);
        unset($_SESSION['impersonador_id']);

        if ($superAdmin === null) {
            header('Location: index.php?ruta=logout');
            exit;
        }

        $_SESSION['usuario_id'] = (int) $superAdmin['id'];
        $_SESSION['usuario_nombre'] = $superAdmin['nombre'];
        $_SESSION['usuario_rol'] = $superAdmin['rol'];
        $_SESSION['usuario_rol_nombre'] = $superAdmin['rol_nombre'] ?? '—';
        $_SESSION['usuario_estamento'] = $superAdmin['estamento_nombre'] ?? '—';
        $_SESSION['usuario_dependencia'] = $superAdmin['dependencia_nombre'] ?? '—';
        $_SESSION['usuario_super_admin'] = (int) ($superAdmin['es_super_admin'] ?? 0) === 1;

        header('Location: index.php?ruta=usuarios');
        exit;
    }

    private function requerirSuperAdmin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;

        if (!$esSuperAdmin) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }

    private function guardarAdministrador(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $rolId = (int) ($_POST['rol_id'] ?? 0);
        $dependenciaId = (int) ($_POST['dependencia_id'] ?? 0);
        $estamentoId = (int) ($_POST['estamento_id'] ?? 0);

        if ($nombre === '' || $correo === '' || $password === '') {
            return ['Todos los campos son obligatorios.', ''];
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['Ingresa un correo electrónico válido.', ''];
        }

        if ($this->modeloUsuario->existeCorreo($correo)) {
            return ['Ese correo ya está registrado.', ''];
        }

        $this->modeloUsuario->crear(
            $nombre,
            $correo,
            $password,
            'administrador',
            null,
            $rolId > 0 ? $rolId : null,
            $dependenciaId > 0 ? $dependenciaId : null,
            $estamentoId > 0 ? $estamentoId : null
        );

        return ['', 'Administrador agregado correctamente.'];
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloUsuario->eliminar($id);
        }

        header('Location: index.php?ruta=usuarios&tab=' . (($_POST['tab'] ?? '') === 'invitados' ? 'invitados' : 'administradores'));
        exit;
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $rolId = (int) ($_POST['rol_id'] ?? 0);
        $dependenciaId = (int) ($_POST['dependencia_id'] ?? 0);
        $estamentoId = (int) ($_POST['estamento_id'] ?? 0);

        if ($id <= 0 || $this->modeloUsuario->obtenerPorId($id) === null) {
            return ['El usuario que intentas editar no existe.', ''];
        }

        if ($nombre === '' || $correo === '') {
            return ['Todos los campos son obligatorios.', ''];
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['Ingresa un correo electrónico válido.', ''];
        }

        if ($this->modeloUsuario->existeCorreo($correo, $id)) {
            return ['Ese correo ya está registrado.', ''];
        }

        $this->modeloUsuario->actualizar($id, $nombre, $correo, $estamentoId > 0 ? $estamentoId : null);
        $this->modeloUsuario->actualizarPermisos($id, $rolId > 0 ? $rolId : null, $dependenciaId > 0 ? $dependenciaId : null);

        if ($password !== '') {
            $this->modeloUsuario->actualizarPassword($id, $password);
        }

        return ['', 'Usuario actualizado correctamente.'];
    }

    private function actualizarPermisos(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $rolId = (int) ($_POST['rol_id'] ?? 0);
        $dependenciaId = (int) ($_POST['dependencia_id'] ?? 0);
        $menuKeys = $_POST['menu'] ?? [];

        if ($id <= 0 || $this->modeloUsuario->obtenerPorId($id) === null) {
            return ['El usuario que intentas editar no existe.', ''];
        }

        $this->modeloUsuario->actualizarPermisos(
            $id,
            $rolId > 0 ? $rolId : null,
            $dependenciaId > 0 ? $dependenciaId : null
        );

        $this->modeloUsuario->actualizarMenuPersonalizado($id, true);
        $this->modeloMenuPermiso->guardarMenuUsuario($id, $menuKeys);

        return ['', 'Permisos actualizados correctamente.'];
    }
}
