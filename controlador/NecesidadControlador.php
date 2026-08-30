<?php

require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/RelojArenaFormulador.php';
require_once __DIR__ . '/../modelo/LineaInversion.php';
require_once __DIR__ . '/../modelo/SublineaInversion.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Estamento.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';

class NecesidadControlador
{
    private Necesidad $modeloNecesidad;
    private Dependencia $modeloDependencia;
    private RelojArenaFormulador $modeloRelojFormulador;
    private LineaInversion $modeloLineaInversion;
    private SublineaInversion $modeloSublineaInversion;
    private Sede $modeloSede;
    private Proyecto $modeloProyecto;
    private Estamento $modeloEstamento;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private AnioPresupuestal $modeloAnio;

    private const CAMPOS_REQUERIDOS = [
        'vigencia',
        'nombre_necesidad',
        'estamento_solicitante_id',
        'linea_inversion',
        'sublinea_inversion',
        'sede_id',
        'dependencia',
        'valor',
        'fuente_financiacion',
        'responsable_usuario_id',
    ];

    private const PROGRAMA_ACADEMICO_TIPOS = ['pregrado', 'postgrado'];

    private const MAX_ANIOS_VIGENCIA_ADICIONALES = 5;

    public function __construct()
    {
        $this->modeloNecesidad = new Necesidad();
        $this->modeloDependencia = new Dependencia();
        $this->modeloRelojFormulador = new RelojArenaFormulador();
        $this->modeloLineaInversion = new LineaInversion();
        $this->modeloSublineaInversion = new SublineaInversion();
        $this->modeloSede = new Sede();
        $this->modeloProyecto = new Proyecto();
        $this->modeloEstamento = new Estamento();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloAnio = new AnioPresupuestal();
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
        $dependenciasPrograma = $this->modeloDependencia->obtenerPorTipos(self::PROGRAMA_ACADEMICO_TIPOS);
        $lineasInversion = $this->modeloLineaInversion->obtenerActivas();
        $sublineasInversion = $this->modeloSublineaInversion->obtenerActivas();
        $sedes = $this->modeloSede->obtenerTodas();
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $estamentos = $this->modeloEstamento->obtenerTodos();
        $avaladores = $this->obtenerAvaladores();
        $aniosVigencia = $this->obtenerAniosVigencia();

        require __DIR__ . '/../vista/formulario-invitado/index.php';
    }

    private function obtenerAvaladores(): array
    {
        $roles = $this->modeloRol->obtenerTodos();
        $rolAvalador = null;

        foreach ($roles as $rol) {
            if ($rol['nombre'] === 'Avalador') {
                $rolAvalador = $rol;
                break;
            }
        }

        if ($rolAvalador === null) {
            return [];
        }

        return $this->modeloUsuario->obtenerPorRolId((int) $rolAvalador['id']);
    }

    private function obtenerAniosVigencia(): array
    {
        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $anioBase = !empty($aniosActivos) ? (int) $aniosActivos[0]['anio'] : (int) date('Y');

        $anios = [];
        for ($i = 0; $i <= self::MAX_ANIOS_VIGENCIA_ADICIONALES; $i++) {
            $anios[] = $anioBase + $i;
        }

        return $anios;
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

        if (!is_numeric($datos['vigencia'])) {
            return ['Selecciona una vigencia válida.', ''];
        }

        if (isset($datos['beneficiarios_cantidad']) && $datos['beneficiarios_cantidad'] !== '') {
            if (!is_numeric($datos['beneficiarios_cantidad']) || (int) $datos['beneficiarios_cantidad'] < 0) {
                return ['Los beneficiarios deben ser un número entero válido.', ''];
            }
            $datos['beneficiarios_cantidad'] = (int) $datos['beneficiarios_cantidad'];
        } else {
            $datos['beneficiarios_cantidad'] = '';
        }

        $lineaElegida = $this->modeloLineaInversion->obtenerPorCodigo($datos['linea_inversion']);

        if ($lineaElegida === null) {
            return ['Selecciona una línea de inversión válida.', ''];
        }

        $sublinea = $this->modeloSublineaInversion->obtenerPorCodigo($datos['sublinea_inversion']);

        if ($sublinea === null || (int) $sublinea['linea_inversion_id'] !== (int) $lineaElegida['id']) {
            return ['La sublínea de inversión seleccionada no pertenece a la línea elegida.', ''];
        }

        $datos['sede_id'] = (int) $datos['sede_id'];
        $datos['estamento_solicitante_id'] = (int) $datos['estamento_solicitante_id'];
        $datos['responsable_usuario_id'] = (int) $datos['responsable_usuario_id'];
        $datos['proyecto_pdi_id'] = !empty($datos['proyecto_id']) ? (int) $datos['proyecto_id'] : null;
        $datos['vigencia'] = (int) $datos['vigencia'];
        $datos['beneficiarios_estamentos'] = array_map('intval', $_POST['beneficiarios_estamentos'] ?? []);

        $this->modeloNecesidad->crear($datos, (int) $_SESSION['usuario_id']);

        return ['', 'Necesidad registrada correctamente.'];
    }
}
