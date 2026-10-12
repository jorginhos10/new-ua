<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/Linea.php';

class LineaControlador extends ControladorListaCatalogo
{
    private Linea $modeloLinea;

    public function __construct()
    {
        $this->modeloLinea = new Linea();
    }

    protected function modelo(): object
    {
        return $this->modeloLinea;
    }

    protected function tabla(): string
    {
        return 'lineas';
    }

    protected function ruta(): string
    {
        return 'lineas';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/lineas/index.php';
    }

    protected function registros(): array
    {
        return $this->modeloLinea->obtenerTodas();
    }

    protected function columnasCsv(): array
    {
        return ['codigo', 'nombre', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['codigo'], $registro['nombre'], $registro['estado']];
    }

    protected function crear(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');

        if ($codigo === '' || $nombre === '') {
            return ['El código y el nombre son obligatorios.', ''];
        }

        if ($this->modeloLinea->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloLinea->crear($codigo, $nombre);

        return ['', 'Línea agregada correctamente.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $this->modeloLinea->obtenerPorId($id) === null) {
            return ['La línea que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $nombre === '') {
            return ['El código y el nombre son obligatorios.', ''];
        }

        $this->modeloLinea->actualizar($id, $codigo, $nombre);

        return ['', 'Línea actualizada correctamente.'];
    }
}
