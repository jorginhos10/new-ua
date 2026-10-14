<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/Sede.php';

class SedeControlador extends ControladorListaCatalogo
{
    private Sede $modeloSede;

    public function __construct()
    {
        $this->modeloSede = new Sede();
    }

    protected function modelo(): object
    {
        return $this->modeloSede;
    }

    protected function tabla(): string
    {
        return 'sedes';
    }

    protected function ruta(): string
    {
        return 'sedes';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/sedes/index.php';
    }

    protected function registros(): array
    {
        return $this->modeloSede->obtenerTodas();
    }

    protected function columnasCsv(): array
    {
        return ['codigo', 'nombre', 'nit', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['codigo'], $registro['nombre'], $registro['nit'], $registro['estado']];
    }

    protected function crear(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');

        $error = $this->validar($codigo, $nombre, $nit, null);

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloSede->crear($codigo, $nombre, $nit);

        return ['', 'Sede agregada correctamente.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloSede->obtenerPorId($id) === null) {
            return ['La sede que intentas editar no existe.', ''];
        }

        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');

        $error = $this->validar($codigo, $nombre, $nit, $id);

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloSede->actualizar($id, $codigo, $nombre, $nit);

        return ['', 'Sede actualizada correctamente.'];
    }

    private function validar(string $codigo, string $nombre, string $nit, ?int $excluirId): string
    {
        if ($codigo === '' || $nombre === '' || $nit === '') {
            return 'El código, el nombre y el NIT son obligatorios.';
        }

        if (!preg_match(Sede::PATRON_NIT, $nit)) {
            return 'El NIT debe tener dos dígitos y empezar por 0 (de 00 a 09).';
        }

        if ($this->modeloSede->existeCodigo($codigo, $excluirId)) {
            return 'Ese código ya existe.';
        }

        if ($this->modeloSede->existeNit($nit, $excluirId)) {
            return 'Ese NIT ya lo tiene otra sede.';
        }

        return '';
    }
}
