<?php

require_once __DIR__ . '/../modelo/Usuario.php';

class LoginControlador
{
    private Usuario $modeloUsuario;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
    }

    public function index(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->procesar();
        }

        require __DIR__ . '/../vista/login/login.php';
    }

    private function procesar(): string
    {
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($correo === '' || $password === '') {
            return 'Debes completar correo electrónico y contraseña.';
        }

        $datos = $this->modeloUsuario->verificarCredenciales($correo, $password);

        if ($datos === false) {
            return 'Correo o contraseña incorrectos.';
        }

        $this->modeloUsuario->registrarAcceso((int) $datos['id']);

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $datos['id'];
        $_SESSION['usuario_nombre'] = $datos['nombre'];
        $_SESSION['usuario_rol'] = $datos['rol'];
        $_SESSION['usuario_rol_nombre'] = $datos['rol_nombre'] ?? '—';
        $_SESSION['usuario_estamento'] = $datos['estamento_nombre'] ?? '—';
        $_SESSION['usuario_dependencia'] = $datos['dependencia_nombre'] ?? '—';
        $_SESSION['usuario_super_admin'] = (int) ($datos['es_super_admin'] ?? 0) === 1;

        header('Location: index.php?ruta=dashboard');
        exit;
    }
}
