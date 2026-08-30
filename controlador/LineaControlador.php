<?php

require_once __DIR__ . '/../modelo/Linea.php';

class LineaControlador
{
    private Linea $modeloLinea;

    public function __construct()
    {
        $this->modeloLinea = new Linea();
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
            [$error, $exito] = $this->guardar();
        }

        $lineas = $this->modeloLinea->obtenerTodas();

        require __DIR__ . '/../vista/lineas/index.php';
    }

    private function guardar(): array
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
}
