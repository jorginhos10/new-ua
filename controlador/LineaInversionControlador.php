<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/LineaInversion.php';

class LineaInversionControlador extends ControladorListaCatalogo
{
    private LineaInversion $modeloLineaInversion;

    public function __construct()
    {
        $this->modeloLineaInversion = new LineaInversion();
    }

    protected function modelo(): object
    {
        return $this->modeloLineaInversion;
    }

    protected function tabla(): string
    {
        return 'lineas_inversion';
    }

    protected function ruta(): string
    {
        return 'lineas-inversion';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/lineas-inversion/index.php';
    }

    protected function registros(): array
    {
        return $this->modeloLineaInversion->obtenerTodas();
    }

    protected function columnasCsv(): array
    {
        return ['codigo', 'nombre', 'descripcion', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['codigo'], $registro['nombre'], $registro['descripcion'], $registro['estado']];
    }

    protected function crear(): array
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

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($id <= 0 || $this->modeloLineaInversion->obtenerPorId($id) === null) {
            return ['La línea de inversión que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $nombre === '' || $descripcion === '') {
            return ['El código, el nombre y la descripción son obligatorios.', ''];
        }

        $this->modeloLineaInversion->actualizar($id, $codigo, $nombre, $descripcion);

        return ['', 'Línea de inversión actualizada correctamente.'];
    }
}
