<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/ContratoComun.php';

class ContratoComunControlador extends ControladorListaCatalogo
{
    private ContratoComun $modeloContratoComun;

    public function __construct()
    {
        $this->modeloContratoComun = new ContratoComun();
    }

    protected function modelo(): object
    {
        return $this->modeloContratoComun;
    }

    protected function tabla(): string
    {
        return 'contratos_comunes';
    }

    protected function ruta(): string
    {
        return 'contratos-comunes';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/contratos-comunes/index.php';
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

        if ($this->modeloContratoComun->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloContratoComun->crear($codigo, $descripcion);

        return ['', 'Contrato común agregado correctamente.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($id <= 0 || $this->modeloContratoComun->obtenerPorId($id) === null) {
            return ['El contrato común que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $descripcion === '') {
            return ['El código y la descripción son obligatorios.', ''];
        }

        $this->modeloContratoComun->actualizar($id, $codigo, $descripcion);

        return ['', 'Contrato común actualizado correctamente.'];
    }
}
