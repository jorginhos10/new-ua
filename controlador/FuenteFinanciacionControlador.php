<?php

require_once __DIR__ . '/../modelo/FuenteFinanciacion.php';

class FuenteFinanciacionControlador
{
    private FuenteFinanciacion $modeloFuente;

    public function __construct()
    {
        $this->modeloFuente = new FuenteFinanciacion();
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

            if ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $fuentes = $this->modeloFuente->obtenerTodas();

        require __DIR__ . '/../vista/fuentes-financiacion/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloFuente->cambiarEstado($id);
        }

        header('Location: index.php?ruta=fuentes-financiacion');
        exit;
    }

    private function guardar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre de la fuente es obligatorio.', ''];
        }

        if ($this->modeloFuente->existeNombre($nombre)) {
            return ['Esa fuente de financiación ya existe.', ''];
        }

        $this->modeloFuente->crear($nombre);

        return ['', 'Fuente de financiación agregada correctamente.'];
    }
}
