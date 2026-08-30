<?php

require_once __DIR__ . '/../modelo/Facultad.php';

class FacultadControlador
{
    private Facultad $modeloFacultad;

    public function __construct()
    {
        $this->modeloFacultad = new Facultad();
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

        $facultades = $this->modeloFacultad->obtenerTodas();

        require __DIR__ . '/../vista/facultades/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloFacultad->cambiarEstado($id);
        }

        header('Location: index.php?ruta=facultades');
        exit;
    }

    private function guardar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        if ($this->modeloFacultad->existeNombre($nombre)) {
            return ['Esa facultad ya existe.', ''];
        }

        $this->modeloFacultad->crear($nombre);

        return ['', 'Facultad agregada correctamente.'];
    }
}
