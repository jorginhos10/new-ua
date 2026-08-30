<?php

require_once __DIR__ . '/../modelo/RelojArenaConfiguracion.php';
require_once __DIR__ . '/../modelo/RelojArenaFormulador.php';

class RelojArenaControlador
{
    private RelojArenaConfiguracion $modeloReloj;
    private RelojArenaFormulador $modeloRelojFormulador;

    public function __construct()
    {
        $this->modeloReloj = new RelojArenaConfiguracion();
        $this->modeloRelojFormulador = new RelojArenaFormulador();
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
        $errorFormulador = '';
        $exitoFormulador = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formulario = $_POST['formulario'] ?? 'dashboard';

            if ($formulario === 'formulador') {
                [$errorFormulador, $exitoFormulador] = $this->guardar($this->modeloRelojFormulador);
            } else {
                [$error, $exito] = $this->guardar($this->modeloReloj);
            }
        }

        $configuracion = $this->modeloReloj->obtener();
        $configuracionFormulador = $this->modeloRelojFormulador->obtener();

        require __DIR__ . '/../vista/reloj-arena/index.php';
    }

    private function guardar(RelojArenaConfiguracion|RelojArenaFormulador $modelo): array
    {
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaCierre = trim($_POST['fecha_cierre'] ?? '');

        if ($fechaInicio === '' || $fechaCierre === '') {
            return ['La fecha de inicio y la fecha de cierre son obligatorias.', ''];
        }

        $inicio = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        $cierre = DateTime::createFromFormat('Y-m-d', $fechaCierre);

        if ($inicio === false || $cierre === false) {
            return ['Las fechas ingresadas no son válidas.', ''];
        }

        if ($cierre <= $inicio) {
            return ['La fecha de cierre debe ser posterior a la fecha de inicio.', ''];
        }

        $modelo->guardar($fechaInicio, $fechaCierre);

        return ['', 'Fechas guardadas correctamente.'];
    }
}
