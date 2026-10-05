<?php

require_once __DIR__ . '/../modelo/Convocatoria.php';
require_once __DIR__ . '/../modelo/FuenteFinanciacion.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/CuentaRegresiva.php';

/** Administración de convocatorias de Perfil de proyectos y su consolidado (solo administrador). */
class ConvocatoriaControlador
{
    private const MAX_ANIOS_VIGENCIA_ADICIONALES = 5;

    private Convocatoria $modeloConvocatoria;
    private FuenteFinanciacion $modeloFuente;
    private Dependencia $modeloDependencia;
    private AnioPresupuestal $modeloAnio;

    public function __construct()
    {
        $this->modeloConvocatoria = new Convocatoria();
        $this->modeloFuente = new FuenteFinanciacion();
        $this->modeloDependencia = new Dependencia();
        $this->modeloAnio = new AnioPresupuestal();
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
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'cambiar_activa') {
                $this->modeloConvocatoria->cambiarActiva((int) ($_POST['id'] ?? 0));
                header('Location: index.php?ruta=convocatorias');
                exit;
            }

            [$error, $exito] = $this->guardar();
        }

        $vista = ($_GET['vista'] ?? '') === 'consolidado' ? 'consolidado' : 'convocatorias';
        $convocatorias = $this->modeloConvocatoria->obtenerTodas();
        $fuentesActivas = $this->modeloFuente->obtenerActivas();
        $dependencias = $this->modeloDependencia->obtenerActivas();
        $arbolDependencias = $this->construirArbolDependencias($dependencias);
        $aniosVigencia = $this->obtenerAniosVigencia();

        $convocatoriaEditar = null;
        if (isset($_GET['editar_id']) && ctype_digit((string) $_GET['editar_id'])) {
            $convocatoriaEditar = $this->modeloConvocatoria->obtenerPorId((int) $_GET['editar_id']);
        }

        $fuentesMarcadas = $convocatoriaEditar !== null ? $this->modeloConvocatoria->fuenteIdsHabilitadas((int) $convocatoriaEditar['id']) : [];
        $dependenciasMarcadas = [];
        if ($convocatoriaEditar !== null) {
            foreach ($this->modeloConvocatoria->dependenciasMarcadas((int) $convocatoriaEditar['id']) as $marca) {
                $dependenciasMarcadas[(int) $marca['dependencia_id']] = (int) $marca['incluir_descendientes'] === 1;
            }
        }

        $consolidado = [];
        if ($vista === 'consolidado') {
            foreach ($convocatorias as $convocatoria) {
                $consolidado[] = [
                    'convocatoria' => $convocatoria,
                    'filas' => $this->modeloConvocatoria->consolidadoPorDependenciaYFuente((int) $convocatoria['id']),
                ];
            }
        }

        require __DIR__ . '/../vista/convocatorias/index.php';
    }

    /** Crea o edita una convocatoria con sus fuentes y dependencias. Devuelve [error, exito]. */
    private function guardar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $vigencia = (int) ($_POST['vigencia'] ?? 0);
        $audiencia = $_POST['audiencia'] ?? '';
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaCierre = trim($_POST['fecha_cierre'] ?? '');

        if ($nombre === '' || $vigencia <= 0 || $fechaInicio === '' || $fechaCierre === '') {
            return ['El nombre, la vigencia y las fechas son obligatorios.', ''];
        }

        if (!in_array($audiencia, Convocatoria::AUDIENCIAS, true)) {
            return ['Selecciona quién formula en la convocatoria.', ''];
        }

        $inicio = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        $cierre = DateTime::createFromFormat('Y-m-d', $fechaCierre);

        if ($inicio === false || $cierre === false) {
            return ['Las fechas ingresadas no son válidas.', ''];
        }

        if ($cierre <= $inicio) {
            return ['La fecha de cierre debe ser posterior a la fecha de inicio.', ''];
        }

        // Valor por proyecto: libre (sin tope) o con un tope que ningún proyecto puede superar.
        $tope = null;
        if (($_POST['tope_modo'] ?? 'libre') === 'tope') {
            $topeTexto = trim($_POST['tope_valor'] ?? '');

            if (!is_numeric($topeTexto) || (float) $topeTexto <= 0) {
                return ['Indica un tope por proyecto mayor que cero, o elige valor libre.', ''];
            }

            $tope = round((float) $topeTexto, 2);
        }

        $datos = [
            'nombre' => $nombre,
            'vigencia' => $vigencia,
            'audiencia' => $audiencia,
            'fecha_inicio' => $fechaInicio,
            'fecha_cierre' => $fechaCierre,
            'tope_por_proyecto' => $tope,
        ];

        if ($id > 0 && $this->modeloConvocatoria->obtenerPorId($id) !== null) {
            $this->modeloConvocatoria->actualizar($id, $datos);
            $mensaje = 'Convocatoria actualizada correctamente.';
        } else {
            $id = $this->modeloConvocatoria->crear($datos, (int) $_SESSION['usuario_id']);
            $mensaje = 'Convocatoria creada correctamente.';
        }

        $this->modeloConvocatoria->guardarFuentes($id, array_map('intval', $_POST['fuentes'] ?? []));
        $this->modeloConvocatoria->guardarDependencias($id, $this->leerDependenciasPost());

        return ['', $mensaje];
    }

    /**
     * Árbol de dependencias para elegir las habilitadas, con la misma forma que el árbol de Techos.
     * Primer nivel: las hijas de la raíz (Superadmin) y las dependencias sin padre (p. ej. Soporte Técnico).
     * Solo aparecen las activas y visibles; una dependencia inactiva se quita y sus hijas suben de nivel.
     */
    private function construirArbolDependencias(array $activas): array
    {
        $activasIds = array_map(static fn (array $d): int => (int) $d['id'], $activas);
        $todas = $this->modeloDependencia->obtenerTodas();

        $raizId = null;
        foreach ($todas as $dependencia) {
            if (!empty($dependencia['es_raiz_superadmin'])) {
                $raizId = (int) $dependencia['id'];
                break;
            }
        }

        $nodos = [];
        foreach ($todas as $dependencia) {
            if (!empty($dependencia['es_raiz_superadmin'])) {
                continue;
            }

            $primerNivel = $dependencia['flujo_id'] === null || (int) $dependencia['flujo_id'] === $raizId;

            if ($primerNivel) {
                $nodos[] = [
                    'dependencia' => $dependencia,
                    'hijos' => $this->modeloDependencia->construirArbolDescendientes((int) $dependencia['id'], true),
                ];
            }
        }

        $filtrar = function (array $lista) use (&$filtrar, $activasIds): array {
            $resultado = [];

            foreach ($lista as $nodo) {
                $hijos = $filtrar($nodo['hijos']);

                if (in_array((int) $nodo['dependencia']['id'], $activasIds, true)) {
                    $resultado[] = ['dependencia' => $nodo['dependencia'], 'hijos' => $hijos];
                } else {
                    $resultado = array_merge($resultado, $hijos);
                }
            }

            usort($resultado, static fn (array $a, array $b): int => strcasecmp($a['dependencia']['nombre'], $b['dependencia']['nombre']));

            return $resultado;
        };

        return $filtrar($nodos);
    }

    /** Dependencias marcadas; "incluir descendientes" aplica solo a las marcadas. */
    private function leerDependenciasPost(): array
    {
        $marcadas = array_map('intval', $_POST['dependencias'] ?? []);
        $conDescendientes = array_map('intval', $_POST['descendientes'] ?? []);
        $filas = [];

        foreach (array_unique($marcadas) as $dependenciaId) {
            $filas[] = [
                'dependencia_id' => $dependenciaId,
                'incluir_descendientes' => in_array($dependenciaId, $conDescendientes, true),
            ];
        }

        return $filas;
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
}
