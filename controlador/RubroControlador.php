<?php

require_once __DIR__ . '/../modelo/Rubro.php';

class RubroControlador
{
    private Rubro $modeloRubro;

    public function __construct()
    {
        $this->modeloRubro = new Rubro();
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

        $rubros = $this->modeloRubro->obtenerTodos();

        require __DIR__ . '/../vista/rubros/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloRubro->cambiarEstado($id);
        }

        header('Location: index.php?ruta=rubros');
        exit;
    }

    private function guardar(): array
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
}
