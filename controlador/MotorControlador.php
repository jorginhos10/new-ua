<?php

require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Linea.php';

class MotorControlador
{
    private Motor $modeloMotor;
    private Linea $modeloLinea;

    public function __construct()
    {
        $this->modeloMotor = new Motor();
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

        $motores = $this->modeloMotor->obtenerTodos();
        $lineas = $this->modeloLinea->obtenerTodas();

        require __DIR__ . '/../vista/motores/index.php';
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $lineaId = $_POST['linea_id'] ?? '';

        if ($codigo === '' || $nombre === '' || $lineaId === '') {
            return ['El código, el nombre y la línea son obligatorios.', ''];
        }

        $this->modeloMotor->crear($codigo, $nombre, (int) $lineaId);

        return ['', 'Motor agregado correctamente.'];
    }
}
