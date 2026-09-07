<?php

require_once __DIR__ . '/../modelo/Usuario.php';

class ConfiguracionesControlador
{
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

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;

        require __DIR__ . '/../vista/configuraciones/index.php';
    }
}
