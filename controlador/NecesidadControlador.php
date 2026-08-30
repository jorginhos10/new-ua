<?php

require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/RelojArenaFormulador.php';

class NecesidadControlador
{
    private Necesidad $modeloNecesidad;
    private Dependencia $modeloDependencia;
    private RelojArenaFormulador $modeloRelojFormulador;

    private const CAMPOS_REQUERIDOS = [
        'linea_inversion',
        'sublinea_inversion',
        'sede',
        'dependencia',
        'valor',
        'fuente_financiacion',
        'responsable',
    ];

    public function __construct()
    {
        $this->modeloNecesidad = new Necesidad();
        $this->modeloDependencia = new Dependencia();
        $this->modeloRelojFormulador = new RelojArenaFormulador();
    }

    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'invitado') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $error = '';
        $exito = '';
        $dentroDeVentana = $this->modeloRelojFormulador->estaDentroDeVentana();
        $configuracionFormulador = $this->modeloRelojFormulador->obtener();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$error, $exito] = $this->procesar($dentroDeVentana);
        }

        $misNecesidades = $this->modeloNecesidad->obtenerPorUsuario((int) $_SESSION['usuario_id']);
        $dependenciasSugeridas = array_column($this->modeloDependencia->obtenerActivas(), 'nombre');

        require __DIR__ . '/../vista/formulario-invitado/index.php';
    }

    private function procesar(bool $dentroDeVentana): array
    {
        if (!$dentroDeVentana) {
            return ['No estás dentro de la fecha habilitada para formular necesidades.', ''];
        }

        $datos = [];

        foreach ($_POST as $campo => $valor) {
            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        foreach (self::CAMPOS_REQUERIDOS as $campo) {
            if (($datos[$campo] ?? '') === '') {
                return ['Todos los campos obligatorios deben diligenciarse.', ''];
            }
        }

        if (!is_numeric($datos['valor']) || (float) $datos['valor'] < 0) {
            return ['El valor debe ser un número válido.', ''];
        }

        $this->modeloNecesidad->crear($datos, (int) $_SESSION['usuario_id']);

        return ['', 'Necesidad registrada correctamente.'];
    }
}
