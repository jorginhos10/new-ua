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
            } elseif ($accion === 'editar') {
                [$error, $exito] = $this->editar();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminar();
            } else {
                [$error, $exito] = $this->crear();
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

    private function crear(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre de la fuente de financiación es obligatorio.', ''];
        }

        if ($this->modeloFuente->existeNombre($nombre)) {
            return ['Esa fuente de financiación ya existe.', ''];
        }

        $this->modeloFuente->crear($nombre);

        return ['', 'Fuente de financiación agregada correctamente.'];
    }

    private function editar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $this->modeloFuente->obtenerPorId($id) === null) {
            return ['La fuente de financiación no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre de la fuente de financiación es obligatorio.', ''];
        }

        if ($this->modeloFuente->existeNombre($nombre, $id)) {
            return ['Ya existe otra fuente de financiación con ese nombre.', ''];
        }

        $this->modeloFuente->actualizarNombre($id, $nombre);

        return ['', 'Fuente de financiación actualizada. Los proyectos que la usan muestran el nuevo nombre.'];
    }

    private function eliminar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $fuente = $id > 0 ? $this->modeloFuente->obtenerPorId($id) : null;

        if ($fuente === null) {
            return ['La fuente de financiación no existe.', ''];
        }

        // Una fuente usada por proyectos no se borra: esos proyectos perderían su fuente. Se desactiva.
        $cantidad = $this->modeloFuente->contarProyectos($id);

        if ($cantidad > 0) {
            return ['No se puede eliminar "' . $fuente['nombre'] . '" porque la usan ' . $cantidad . ' proyecto(s). Desactívala para que no se ofrezca en nuevas convocatorias.', ''];
        }

        $this->modeloFuente->eliminar($id);

        return ['', 'Fuente de financiación "' . $fuente['nombre'] . '" eliminada.'];
    }
}
