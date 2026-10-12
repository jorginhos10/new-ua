<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Motor.php';

class ProyectoControlador extends ControladorListaCatalogo
{
    private Proyecto $modeloProyecto;
    private Motor $modeloMotor;

    public function __construct()
    {
        $this->modeloProyecto = new Proyecto();
        $this->modeloMotor = new Motor();
    }

    protected function modelo(): object
    {
        return $this->modeloProyecto;
    }

    protected function tabla(): string
    {
        return 'proyectos';
    }

    protected function ruta(): string
    {
        return 'proyectos';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/proyectos/index.php';
    }

    protected function columnasCsv(): array
    {
        return ['linea_codigo', 'motor_codigo', 'codigo', 'nombre', 'nit', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['linea_codigo'], $registro['motor_codigo'], $registro['codigo'], $registro['nombre'], (string) $registro['nit'], $registro['estado']];
    }

    /** Motores con su línea ya resuelta (vía Motor::obtenerTodos()) — para el <select> de "Motor". */
    protected function datosExtra(): array
    {
        return ['motoresOpciones' => $this->modeloMotor->obtenerTodos()];
    }

    protected function crear(): array
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

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $motorId = $_POST['motor_id'] ?? '';

        if ($id <= 0 || $this->modeloProyecto->obtenerPorId($id) === null) {
            return ['El proyecto que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $nombre === '' || $motorId === '') {
            return ['El código, el nombre y el motor son obligatorios.', ''];
        }

        $this->modeloProyecto->actualizar($id, $codigo, $nombre, (int) $motorId);

        return ['', 'Proyecto actualizado correctamente.'];
    }
}
