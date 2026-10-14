<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/SublineaInversion.php';
require_once __DIR__ . '/../modelo/LineaInversion.php';

class SublineaInversionControlador extends ControladorListaCatalogo
{
    private SublineaInversion $modeloSublineaInversion;
    private LineaInversion $modeloLineaInversion;

    public function __construct()
    {
        $this->modeloSublineaInversion = new SublineaInversion();
        $this->modeloLineaInversion = new LineaInversion();
    }

    protected function modelo(): object
    {
        return $this->modeloSublineaInversion;
    }

    protected function tabla(): string
    {
        return 'sublineas_inversion';
    }

    protected function ruta(): string
    {
        return 'sublineas-inversion';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/sublineas-inversion/index.php';
    }

    protected function registros(): array
    {
        return $this->modeloSublineaInversion->obtenerTodas();
    }

    protected function columnasCsv(): array
    {
        return ['linea_codigo', 'codigo', 'nombre', 'descripcion', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['linea_codigo'], $registro['codigo'], $registro['nombre'], $registro['descripcion'], $registro['estado']];
    }

    /** Todas las líneas de inversión, no solo activas — ver nota equivalente en MotorControlador. */
    protected function datosExtra(): array
    {
        return ['lineasOpciones' => $this->modeloLineaInversion->obtenerTodas()];
    }

    protected function crear(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $lineaInversionId = $_POST['linea_inversion_id'] ?? '';

        if ($codigo === '' || $nombre === '' || $descripcion === '' || $lineaInversionId === '') {
            return ['El código, el nombre, la descripción y la línea de inversión son obligatorios.', ''];
        }

        if ($this->modeloSublineaInversion->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloSublineaInversion->crear($codigo, $nombre, $descripcion, (int) $lineaInversionId);

        return ['', 'Sublínea de inversión agregada correctamente.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $lineaInversionId = $_POST['linea_inversion_id'] ?? '';

        if ($id <= 0 || $this->modeloSublineaInversion->obtenerPorId($id) === null) {
            return ['La sublínea de inversión que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $nombre === '' || $descripcion === '' || $lineaInversionId === '') {
            return ['El código, el nombre, la descripción y la línea de inversión son obligatorios.', ''];
        }

        $this->modeloSublineaInversion->actualizar($id, $codigo, $nombre, $descripcion, (int) $lineaInversionId);

        return ['', 'Sublínea de inversión actualizada correctamente.'];
    }
}
