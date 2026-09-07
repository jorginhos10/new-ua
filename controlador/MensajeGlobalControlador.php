<?php

require_once __DIR__ . '/../modelo/MensajeGlobal.php';
require_once __DIR__ . '/../modelo/Usuario.php';

class MensajeGlobalControlador
{
    private MensajeGlobal $modeloMensaje;

    public function __construct()
    {
        $this->modeloMensaje = new MensajeGlobal();
    }

    public function index(): void
    {
        $this->requerirSuperAdmin();

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contenido = trim($_POST['contenido'] ?? '');

            if (mb_strlen($contenido) > 2000) {
                $error = 'El mensaje no puede superar los 2000 caracteres.';
            } else {
                $this->modeloMensaje->guardar($contenido, (int) $_SESSION['usuario_id']);
                $exito = 'Mensaje actualizado correctamente.';
            }
        }

        $mensaje = $this->modeloMensaje->obtener();

        require __DIR__ . '/../vista/mensaje-global/index.php';
    }

    private function requerirSuperAdmin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;

        if (!$esSuperAdmin) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }
}
