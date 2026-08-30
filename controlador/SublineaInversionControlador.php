<?php

require_once __DIR__ . '/../modelo/SublineaInversion.php';
require_once __DIR__ . '/../modelo/LineaInversion.php';

class SublineaInversionControlador
{
    private SublineaInversion $modeloSublineaInversion;
    private LineaInversion $modeloLineaInversion;

    public function __construct()
    {
        $this->modeloSublineaInversion = new SublineaInversion();
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

        $sublineasInversion = $this->modeloSublineaInversion->obtenerTodas();
        $lineasInversion = $this->modeloLineaInversion->obtenerActivas();

        require __DIR__ . '/../vista/sublineas-inversion/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloSublineaInversion->cambiarEstado($id);
        }

        header('Location: index.php?ruta=sublineas-inversion');
        exit;
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $lineaInversionId = (int) ($_POST['linea_inversion_id'] ?? 0);

        if ($codigo === '' || $nombre === '' || $descripcion === '' || $lineaInversionId <= 0) {
            return ['El código, el nombre, la descripción y la línea de inversión son obligatorios.', ''];
        }

        if ($this->modeloSublineaInversion->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloSublineaInversion->crear($codigo, $nombre, $descripcion, $lineaInversionId);

        return ['', 'Sublínea de inversión agregada correctamente.'];
    }
}
