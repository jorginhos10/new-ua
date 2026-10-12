<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/Rubro.php';

class RubroControlador extends ControladorListaCatalogo
{
    private Rubro $modeloRubro;

    public function __construct()
    {
        $this->modeloRubro = new Rubro();
    }

    protected function modelo(): object
    {
        return $this->modeloRubro;
    }

    protected function tabla(): string
    {
        return 'rubros';
    }

    protected function ruta(): string
    {
        return 'rubros';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/rubros/index.php';
    }

    protected function columnasCsv(): array
    {
        return ['codigo', 'descripcion', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['codigo'], $registro['descripcion'], $registro['estado']];
    }

    protected function crear(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($codigo === '' || $descripcion === '') {
            return ['El código y la descripción son obligatorios.', ''];
        }

        if ($this->modeloRubro->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloRubro->crear($codigo, $descripcion);

        return ['', 'Rubro agregado correctamente.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($id <= 0 || $this->modeloRubro->obtenerPorId($id) === null) {
            return ['El rubro que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $descripcion === '') {
            return ['El código y la descripción son obligatorios.', ''];
        }

        $this->modeloRubro->actualizar($id, $codigo, $descripcion);

        return ['', 'Rubro actualizado correctamente.'];
    }
}
