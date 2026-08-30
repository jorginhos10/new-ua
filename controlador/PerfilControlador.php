<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';

class PerfilControlador
{
    private Usuario $modeloUsuario;
    private Dependencia $modeloDependencia;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
        $this->modeloDependencia = new Dependencia();
    }

    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'actualizar_datos') {
                [$error, $exito] = $this->actualizarDatos($usuarioId);
            } elseif ($accion === 'actualizar_password') {
                [$error, $exito] = $this->actualizarPassword($usuarioId);
            }
        }

        $usuario = $this->modeloUsuario->obtenerPorId($usuarioId);

        if ($usuario === null) {
            header('Location: index.php?ruta=logout');
            exit;
        }

        $dependencia = !empty($usuario['dependencia_id'])
            ? $this->modeloDependencia->obtenerPorId((int) $usuario['dependencia_id'])
            : null;

        require __DIR__ . '/../vista/perfil/index.php';
    }

    private function actualizarDatos(int $usuarioId): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if ($nombre === '' || $correo === '') {
            return ['El nombre y el correo son obligatorios.', ''];
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['Ingresa un correo válido.', ''];
        }

        if ($this->modeloUsuario->existeCorreo($correo, $usuarioId)) {
            return ['Ese correo ya está en uso por otro usuario.', ''];
        }

        $this->modeloUsuario->actualizar($usuarioId, $nombre, $correo);
        $_SESSION['usuario_nombre'] = $nombre;

        return ['', 'Datos actualizados correctamente.'];
    }

    private function actualizarPassword(int $usuarioId): array
    {
        $actual = (string) ($_POST['password_actual'] ?? '');
        $nueva = (string) ($_POST['password_nueva'] ?? '');
        $confirmar = (string) ($_POST['password_confirmar'] ?? '');

        if ($actual === '' || $nueva === '' || $confirmar === '') {
            return ['Completa los tres campos de contraseña.', ''];
        }

        if (strlen($nueva) < 6) {
            return ['La nueva contraseña debe tener al menos 6 caracteres.', ''];
        }

        if ($nueva !== $confirmar) {
            return ['La confirmación no coincide con la nueva contraseña.', ''];
        }

        $usuario = $this->modeloUsuario->obtenerPorId($usuarioId);

        if ($usuario === null) {
            return ['No se pudo verificar tu usuario.', ''];
        }

        $usuarioConPassword = $this->modeloUsuario->buscarPorCorreo($usuario['correo']);

        if ($usuarioConPassword === false || !password_verify($actual, $usuarioConPassword['password'])) {
            return ['La contraseña actual no es correcta.', ''];
        }

        $this->modeloUsuario->actualizarPassword($usuarioId, $nueva);

        return ['', 'Contraseña actualizada correctamente.'];
    }
}
