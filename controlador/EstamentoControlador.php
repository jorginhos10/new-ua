<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/Estamento.php';

class EstamentoControlador extends ControladorListaCatalogo
{
    private Estamento $modeloEstamento;

    public function __construct()
    {
        $this->modeloEstamento = new Estamento();
    }

    protected function modelo(): object
    {
        return $this->modeloEstamento;
    }

    protected function tabla(): string
    {
        return 'estamentos';
    }

    protected function ruta(): string
    {
        return 'estamentos';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/estamentos/index.php';
    }

    protected function columnasCsv(): array
    {
        return ['nombre', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['nombre'], $registro['estado']];
    }

    protected function crear(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre del estamento es obligatorio.', ''];
        }

        if ($this->modeloEstamento->existeEstamento($nombre)) {
            return ['Ese estamento ya existe.', ''];
        }

        $this->modeloEstamento->crear($nombre);

        return ['', 'Estamento agregado correctamente.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $this->modeloEstamento->obtenerPorId($id) === null) {
            return ['El estamento que intentas editar no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre del estamento es obligatorio.', ''];
        }

        if ($this->modeloEstamento->existeEstamento($nombre, $id)) {
            return ['Ese estamento ya existe.', ''];
        }

        $this->modeloEstamento->actualizar($id, $nombre);

        return ['', 'Estamento actualizado correctamente.'];
    }
}
