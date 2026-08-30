<?php

require_once __DIR__ . '/../modelo/Sede.php';

class SedeControlador
{
    private Sede $modeloSede;

    public function __construct()
    {
        $this->modeloSede = new Sede();
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

        $sedes = $this->modeloSede->obtenerTodas();

        require __DIR__ . '/../vista/sedes/index.php';
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');

        if ($codigo === '' || $nombre === '') {
            return ['El código y el nombre son obligatorios.', ''];
        }

        if ($this->modeloSede->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloSede->crear($codigo, $nombre);

        return ['', 'Sede agregada correctamente.'];
    }
}
