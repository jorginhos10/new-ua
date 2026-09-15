<?php

require_once __DIR__ . '/../modelo/Snapshot.php';
require_once __DIR__ . '/../modelo/Usuario.php';

class RepositorioControlador
{
    private Snapshot $modeloSnapshot;

    public function __construct()
    {
        $this->modeloSnapshot = new Snapshot();
    }

    public function index(): void
    {
        $this->requerirSuperAdmin();

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'crear') {
                [$error, $exito] = $this->crear();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminarSnapshot();
            }
        }

        $snapshots = $this->modeloSnapshot->obtenerTodos();

        require __DIR__ . '/../vista/repositorios/index.php';
    }

    private function crear(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $nombre = 'Snapshot ' . date('Y-m-d H:i:s');
        }

        try {
            $this->modeloSnapshot->crear($nombre, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return ['No se pudo crear el snapshot: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Snapshot "' . $nombre . '" creado correctamente.'];
    }

    private function eliminarSnapshot(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloSnapshot->obtenerPorId($id) === null) {
            return ['El snapshot que intentas eliminar no existe.', ''];
        }

        $this->modeloSnapshot->eliminar($id);

        return ['', 'Snapshot eliminado correctamente.'];
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
