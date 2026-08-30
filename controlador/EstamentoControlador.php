<?php

require_once __DIR__ . '/../modelo/Estamento.php';

class EstamentoControlador
{
    private Estamento $modeloEstamento;

    public function __construct()
    {
        $this->modeloEstamento = new Estamento();
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

            if ($accion === 'eliminar') {
                $this->eliminar();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $estamentos = $this->modeloEstamento->obtenerTodos();

        require __DIR__ . '/../vista/estamentos/index.php';
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloEstamento->eliminar($id);
        }

        header('Location: index.php?ruta=estamentos');
        exit;
    }

    private function guardar(): array
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

    private function actualizar(): array
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
