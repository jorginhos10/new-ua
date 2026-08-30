<?php

require_once __DIR__ . '/../modelo/LineaInversion.php';

class LineaInversionControlador
{
    private LineaInversion $modeloLineaInversion;

    public function __construct()
    {
        $this->modeloLineaInversion = new LineaInversion();
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
            if (($_POST['accion'] ?? '') === 'cambiar_estado') {
                $this->cambiarEstado();
            }

            [$error, $exito] = $this->guardar();
        }

        $lineasInversion = $this->modeloLineaInversion->obtenerTodas();

        require __DIR__ . '/../vista/lineas-inversion/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloLineaInversion->cambiarEstado($id);
        }

        header('Location: index.php?ruta=lineas-inversion');
        exit;
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($codigo === '' || $nombre === '' || $descripcion === '') {
            return ['El código, el nombre y la descripción son obligatorios.', ''];
        }

        if ($this->modeloLineaInversion->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloLineaInversion->crear($codigo, $nombre, $descripcion);

        return ['', 'Línea de inversión agregada correctamente.'];
    }
}
