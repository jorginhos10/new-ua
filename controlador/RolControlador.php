<?php

require_once __DIR__ . '/../modelo/Rol.php';

class RolControlador
{
    private Rol $modeloRol;

    public function __construct()
    {
        $this->modeloRol = new Rol();
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

        $roles = $this->modeloRol->obtenerTodos();

        require __DIR__ . '/../vista/roles/index.php';
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloRol->eliminar($id);
        }

        header('Location: index.php?ruta=roles');
        exit;
    }

    private function guardar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $ordenTexto = trim($_POST['orden'] ?? '');

        if ($nombre === '') {
            return ['El nombre del rol es obligatorio.', ''];
        }

        if ($this->modeloRol->existeRol($nombre)) {
            return ['Ese rol ya existe.', ''];
        }

        $orden = $ordenTexto !== '' ? (int) $ordenTexto : $this->modeloRol->obtenerSiguienteOrden();

        $this->modeloRol->crear($nombre, $orden);

        return ['', 'Rol agregado correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $ordenTexto = trim($_POST['orden'] ?? '');

        if ($id <= 0 || $this->modeloRol->obtenerPorId($id) === null) {
            return ['El rol que intentas editar no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre del rol es obligatorio.', ''];
        }

        if ($this->modeloRol->existeRol($nombre, $id)) {
            return ['Ese rol ya existe.', ''];
        }

        $orden = $ordenTexto !== '' ? (int) $ordenTexto : 0;

        $this->modeloRol->actualizar($id, $nombre, $orden);

        return ['', 'Rol actualizado correctamente.'];
    }
}
