<?php

require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Motor.php';

class ProyectoControlador
{
    private Proyecto $modeloProyecto;
    private Motor $modeloMotor;

    public function __construct()
    {
        $this->modeloProyecto = new Proyecto();
        $this->modeloMotor = new Motor();
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

        $proyectos = $this->modeloProyecto->obtenerTodos();
        $motores = $this->modeloMotor->obtenerTodos();

        require __DIR__ . '/../vista/proyectos/index.php';
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $motorId = $_POST['motor_id'] ?? '';

        if ($codigo === '' || $nombre === '' || $motorId === '') {
            return ['El código, el nombre y el motor son obligatorios.', ''];
        }

        $this->modeloProyecto->crear($codigo, $nombre, (int) $motorId);

        return ['', 'Proyecto agregado correctamente.'];
    }
}
