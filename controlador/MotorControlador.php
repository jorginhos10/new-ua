<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Linea.php';

class MotorControlador extends ControladorListaCatalogo
{
    private Motor $modeloMotor;
    private Linea $modeloLinea;

    public function __construct()
    {
        $this->modeloMotor = new Motor();
        $this->modeloLinea = new Linea();
    }

    protected function modelo(): object
    {
        return $this->modeloMotor;
    }

    protected function tabla(): string
    {
        return 'motores';
    }

    protected function ruta(): string
    {
        return 'motores';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/motores/index.php';
    }

    protected function columnasCsv(): array
    {
        return ['codigo', 'nombre', 'linea_codigo', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['codigo'], $registro['nombre'], $registro['linea_codigo'], $registro['estado']];
    }

    /**
     * Todas las líneas, no solo activas: si una línea ya asignada a un motor se desactiva, el
     * <select> de edición del motor debe seguir mostrándola (marcada "inactiva") — si solo
     * ofreciera activas, guardar cualquier otro cambio en ese motor reasignaría su línea en
     * silencio al perder la opción seleccionada.
     */
    protected function datosExtra(): array
    {
        return ['lineasOpciones' => $this->modeloLinea->obtenerTodas()];
    }

    protected function crear(): array
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

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $lineaId = $_POST['linea_id'] ?? '';

        if ($id <= 0 || $this->modeloMotor->obtenerPorId($id) === null) {
            return ['El motor que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $nombre === '' || $lineaId === '') {
            return ['El código, el nombre y la línea son obligatorios.', ''];
        }

        $this->modeloMotor->actualizar($id, $codigo, $nombre, (int) $lineaId);

        return ['', 'Motor actualizado correctamente.'];
    }
}
