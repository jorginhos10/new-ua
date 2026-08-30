<?php

require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/SolicitudMonitor.php';
require_once __DIR__ . '/../modelo/SolicitudOps.php';
require_once __DIR__ . '/../modelo/SolicitudPeticion.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/GastoExtension.php';
require_once __DIR__ . '/../modelo/GastoPostgrado.php';
require_once __DIR__ . '/../modelo/GastoUnisalud.php';
require_once __DIR__ . '/../modelo/GastoSinExcedentes.php';
require_once __DIR__ . '/../modelo/IngresoExtension.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/IngresoUnisalud.php';
require_once __DIR__ . '/../modelo/IngresoSinExcedentes.php';
require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/PeticionArchivada.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/TipoDependenciaRol.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/Rubro.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/ExportadorExcel.php';

class PeticionesControlador
{
    private SolicitudArl $modeloSolicitud;
    private SolicitudMonitor $modeloMonitor;
    private SolicitudOps $modeloOps;
    private SolicitudPeticion $modeloPeticion;
    private Gasto $modeloGasto;
    private GastoExtension $modeloGastoExtension;
    private GastoPostgrado $modeloGastoPostgrado;
    private GastoUnisalud $modeloGastoUnisalud;
    private GastoSinExcedentes $modeloGastoSinExcedentes;
    private IngresoExtension $modeloIngresoExtension;
    private IngresoPostgrado $modeloIngresoPostgrado;
    private IngresoUnisalud $modeloIngresoUnisalud;
    private IngresoSinExcedentes $modeloIngresoSinExcedentes;
    private Necesidad $modeloNecesidad;
    private AnioPresupuestal $modeloAnio;
    private PeticionArchivada $modeloArchivada;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;
    private TipoDependenciaRol $modeloTipoDependenciaRol;
    private PresupuestoDependencia $modeloPresupuestoDependencia;
    private Rubro $modeloRubro;
    private Sede $modeloSede;
    private Linea $modeloLinea;
    private Motor $modeloMotor;
    private Proyecto $modeloProyecto;

    private const VISTAS = ['pendientes', 'consolidado', 'archivar'];

    private const ORIGENES_GASTO = ['gasto_principal', 'gasto_extension', 'gasto_postgrado', 'gasto_unisalud', 'gasto_sin_excedentes'];

    private const ORIGENES_AUTOGESTION = ['gasto_extension', 'gasto_postgrado', 'gasto_unisalud', 'gasto_sin_excedentes', 'necesidad'];

    public function __construct()
    {
        $this->modeloSolicitud = new SolicitudArl();
        $this->modeloMonitor = new SolicitudMonitor();
        $this->modeloOps = new SolicitudOps();
        $this->modeloPeticion = new SolicitudPeticion();
        $this->modeloGasto = new Gasto();
        $this->modeloGastoExtension = new GastoExtension();
        $this->modeloGastoPostgrado = new GastoPostgrado();
        $this->modeloGastoUnisalud = new GastoUnisalud();
        $this->modeloGastoSinExcedentes = new GastoSinExcedentes();
        $this->modeloIngresoExtension = new IngresoExtension();
        $this->modeloIngresoPostgrado = new IngresoPostgrado();
        $this->modeloIngresoUnisalud = new IngresoUnisalud();
        $this->modeloIngresoSinExcedentes = new IngresoSinExcedentes();
        $this->modeloNecesidad = new Necesidad();
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloArchivada = new PeticionArchivada();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
        $this->modeloTipoDependenciaRol = new TipoDependenciaRol();
        $this->modeloPresupuestoDependencia = new PresupuestoDependencia();
        $this->modeloRubro = new Rubro();
        $this->modeloSede = new Sede();
        $this->modeloLinea = new Linea();
        $this->modeloMotor = new Motor();
        $this->modeloProyecto = new Proyecto();
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if (($accion === 'archivar' || $accion === 'aprobar') && ($_POST['origen'] ?? '') === 'necesidad_grupo') {
                [$_SESSION['peticiones_flash_error'], $_SESSION['peticiones_flash_exito']] = $this->procesarGrupoNecesidades(
                    $accion === 'aprobar' ? 'aprobada' : 'archivada'
                );
            } elseif ($accion === 'archivar' || $accion === 'aprobar') {
                $this->modeloArchivada->archivar([
                    'origen' => $_POST['origen'] ?? '',
                    'origen_id' => (int) ($_POST['origen_id'] ?? 0),
                    'accion' => $accion === 'aprobar' ? 'aprobada' : 'archivada',
                    'tipo' => $_POST['tipo'] ?? '',
                    'detalle' => $_POST['detalle'] ?? '',
                    'cantidad' => $_POST['cantidad'] !== '' ? $_POST['cantidad'] : null,
                    'valor' => $_POST['valor'] !== '' ? (float) $_POST['valor'] : null,
                    'ruta_ver' => $_POST['ruta_ver'] ?? 'index.php?ruta=peticiones',
                    'ruta_origen' => $_POST['ruta_origen'] ?? null,
                ]);
            } elseif ($accion === 'restaurar') {
                $this->modeloArchivada->restaurar((int) ($_POST['id'] ?? 0));
            } elseif ($accion === 'redireccionar_consolidado') {
                [$errorRedireccion, $exitoRedireccion] = $this->redireccionarConsolidado();
                $_SESSION['peticiones_flash_error'] = $errorRedireccion;
                $_SESSION['peticiones_flash_exito'] = $exitoRedireccion;
            } elseif ($accion === 'rechazar_redireccion') {
                [$errorRechazo, $exitoRechazo] = $this->rechazarRedireccion();
                $_SESSION['peticiones_flash_error'] = $errorRechazo;
                $_SESSION['peticiones_flash_exito'] = $exitoRechazo;
            } elseif ($accion === 'eliminar_pendiente') {
                [$errorEliminar, $exitoEliminar] = $this->eliminarPendiente();
                $_SESSION['peticiones_flash_error'] = $errorEliminar;
                $_SESSION['peticiones_flash_exito'] = $exitoEliminar;
            }

            $vistaDestino = $accion === 'aprobar' ? 'consolidado' : ($_POST['vista'] ?? 'pendientes');
            $destino = 'index.php?ruta=peticiones&vista=' . urlencode($vistaDestino);
            if (!empty($_POST['anio_id'])) {
                $destino .= '&anio_id=' . (int) $_POST['anio_id'];
            }
            if (($_POST['modo'] ?? '') === 'jerarquia') {
                $destino .= '&modo=jerarquia';
            }
            header('Location: ' . $destino);
            exit;
        }

        $error = $_SESSION['peticiones_flash_error'] ?? '';
        $exito = $_SESSION['peticiones_flash_exito'] ?? '';
        unset($_SESSION['peticiones_flash_error'], $_SESSION['peticiones_flash_exito']);

        $vistaSolicitada = $_GET['vista'] ?? 'pendientes';
        $vista = in_array($vistaSolicitada, self::VISTAS, true) ? $vistaSolicitada : 'pendientes';

        $aniosActivos = $this->modeloAnio->obtenerActivos();

        $anioSeleccionadoId = 0;

        if (!empty($aniosActivos)) {
            $anioSeleccionadoId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) $aniosActivos[0]['id'];

            $idsValidos = array_map('intval', array_column($aniosActivos, 'id'));
            if (!in_array($anioSeleccionadoId, $idsValidos, true)) {
                $anioSeleccionadoId = (int) $aniosActivos[0]['id'];
            }
        }

        $dependenciasPermitidas = $this->obtenerDependenciasPermitidas();

        $esSuperAdminRaiz = $this->esSuperAdminRaiz();
        $modoJerarquia = $esSuperAdminRaiz && ($_GET['modo'] ?? '') === 'jerarquia';

        if ($anioSeleccionadoId <= 0) {
            $pendientes = [];
        } elseif ($modoJerarquia) {
            $pendientes = $this->construirVistaJerarquica($anioSeleccionadoId, $dependenciasPermitidas);
        } else {
            $pendientes = $this->construirPendientes($anioSeleccionadoId, $dependenciasPermitidas);
        }

        $aprobados = $vista === 'consolidado' ? $this->modeloArchivada->obtenerPorAccion('aprobada') : [];
        $aprobados = $this->filtrarPorDependencia($aprobados, $dependenciasPermitidas);

        $tiposRedireccionados = [];
        if ($vista === 'consolidado') {
            foreach ($this->modeloArchivada->obtenerRedireccionadas() as $redirigida) {
                $tiposRedireccionados[$redirigida['tipo']] = true;
            }
        }

        $consolidado = [];
        foreach ($aprobados as $item) {
            $tipo = $item['tipo'];
            if (!isset($consolidado[$tipo])) {
                $consolidado[$tipo] = ['tipo' => $tipo, 'cantidad' => 0, 'ruta' => $item['ruta_origen'] ?? 'index.php?ruta=peticiones', 'items' => []];
            }
            $consolidado[$tipo]['cantidad']++;
            $consolidado[$tipo]['items'][] = [
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $item['ruta_ver'],
            ];
        }
        $consolidado = array_values($consolidado);

        $consolidadoUnificado = ($vista === 'consolidado' && $modoJerarquia)
            ? $this->construirConsolidadoUnificado($aprobados, $anioSeleccionadoId)
            : [];

        $archivados = $vista === 'archivar' ? $this->modeloArchivada->obtenerPorAccion('archivada') : [];
        $archivados = $this->filtrarPorDependencia($archivados, $dependenciasPermitidas);

        $dependenciasSugeridas = $this->modeloDependencia->obtenerActivas();
        $roles = $this->modeloRol->obtenerTodos();
        $rolesPorTipo = $this->modeloTipoDependenciaRol->obtenerMapaCompleto();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();

        require __DIR__ . '/../vista/peticiones/index.php';
    }

    /**
     * Landing de detalle: muestra (y permite exportar a Excel) todo lo consolidado/aprobado,
     * en filas individuales con el mismo nivel de detalle que la tabla de Gastos — dependencia,
     * sede, línea, motor, proyecto, rubro, actividad, insumo, costo unitario, valor total, meses
     * y el techo presupuestal de cada dependencia — opcionalmente filtrado por tipo.
     */
    public function detalle(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $anioSeleccionadoId = (int) ($_GET['anio_id'] ?? 0);
        $tipoFiltro = trim($_GET['tipo'] ?? '');

        $dependenciasPermitidas = $this->obtenerDependenciasPermitidas();

        $aprobados = $this->modeloArchivada->obtenerPorAccion('aprobada');
        $aprobados = $this->filtrarPorDependencia($aprobados, $dependenciasPermitidas);

        if ($tipoFiltro !== '') {
            $aprobados = array_values(array_filter($aprobados, static function (array $item) use ($tipoFiltro): bool {
                return $item['tipo'] === $tipoFiltro;
            }));
        }

        $filas = $this->construirFilasDetalleCompleto($aprobados, $anioSeleccionadoId);

        if (($_GET['exportar'] ?? '') === 'xlsx') {
            $encabezados = [
                'Tipo', 'Dependencia', 'Sede', 'Línea estratégica', 'Motor de desarrollo', 'Proyecto PDI',
                'Objeto/Proyecto (PAA)', 'Actividad', 'Rubro', 'Insumo', 'Cantidad', 'Costo unitario',
                'Valor total', 'Meses', 'Techo presupuestal',
            ];

            $filasExportar = array_map(static function (array $fila): array {
                return [
                    $fila['tipo'],
                    $fila['dependencia'] ?? '—',
                    $fila['sede'],
                    $fila['linea'],
                    $fila['motor'],
                    $fila['proyecto'],
                    $fila['objeto_proyecto_paa'],
                    $fila['actividad'],
                    $fila['rubro'],
                    $fila['insumo'],
                    $fila['cantidad'] ?? '—',
                    $fila['costo_unitario'] ?? '',
                    $fila['valor_total'] ?? '',
                    $fila['meses'],
                    $fila['techo'] ?? '',
                ];
            }, $filas);

            $nombreTipo = $tipoFiltro !== '' ? '-' . preg_replace('/[^A-Za-z0-9]+/', '-', $tipoFiltro) : '';
            ExportadorExcel::descargar('consolidado' . $nombreTipo . '-' . date('Y-m-d') . '.xlsx', $encabezados, $filasExportar);

            return;
        }

        $anio = $anioSeleccionadoId > 0 ? $this->modeloAnio->obtenerPorId($anioSeleccionadoId) : null;

        require __DIR__ . '/../vista/peticiones/consolidado-detalle.php';
    }

    /**
     * Landing aparte (para cualquier dependencia, no solo el superadmin) con lo aceptado en
     * autogestión (Extensión, Postgrado, Unisalud, Sin excedentes) y Perfil de proyectos, dentro
     * de la propia jerarquía del usuario (él mismo + hijas + nietas + demás descendientes) — sin el
     * nivel de consolidación institucional que tiene el administrador (no agrupa por tipo, no
     * permite aprobar/redireccionar, solo muestra cómo quedó distribuido lo aceptado).
     */
    public function autogestion(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $anioSeleccionadoId = 0;

        if (!empty($aniosActivos)) {
            $anioSeleccionadoId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) $aniosActivos[0]['id'];

            $idsValidos = array_map('intval', array_column($aniosActivos, 'id'));
            if (!in_array($anioSeleccionadoId, $idsValidos, true)) {
                $anioSeleccionadoId = (int) $aniosActivos[0]['id'];
            }
        }

        $dependenciasPermitidas = $this->obtenerDependenciasPermitidas();

        $aprobados = $this->modeloArchivada->obtenerPorAccion('aprobada');
        $aprobados = array_values(array_filter($aprobados, static function (array $item): bool {
            return in_array($item['origen'], self::ORIGENES_AUTOGESTION, true);
        }));
        $aprobados = $this->filtrarPorDependencia($aprobados, $dependenciasPermitidas);

        $filas = $this->construirFilasDetalleCompleto($aprobados, $anioSeleccionadoId);

        $resumenPorDependencia = [];
        foreach ($filas as $fila) {
            $nombre = $fila['dependencia'] ?? '—';

            if (!isset($resumenPorDependencia[$nombre])) {
                $resumenPorDependencia[$nombre] = ['dependencia' => $nombre, 'cantidad_items' => 0, 'valor_total' => 0.0];
            }

            $resumenPorDependencia[$nombre]['cantidad_items']++;
            $resumenPorDependencia[$nombre]['valor_total'] += $fila['valor_total'] ?? 0.0;
        }
        $resumenPorDependencia = array_values($resumenPorDependencia);
        usort($resumenPorDependencia, static function (array $a, array $b): int {
            return $b['valor_total'] <=> $a['valor_total'];
        });

        if (($_GET['exportar'] ?? '') === 'xlsx') {
            $encabezados = [
                'Tipo', 'Dependencia', 'Sede', 'Línea estratégica', 'Motor de desarrollo', 'Proyecto PDI',
                'Objeto/Proyecto (PAA)', 'Actividad', 'Rubro', 'Insumo', 'Cantidad', 'Costo unitario',
                'Valor total', 'Meses',
            ];

            $filasExportar = array_map(static function (array $fila): array {
                return [
                    $fila['tipo'],
                    $fila['dependencia'] ?? '—',
                    $fila['sede'],
                    $fila['linea'],
                    $fila['motor'],
                    $fila['proyecto'],
                    $fila['objeto_proyecto_paa'],
                    $fila['actividad'],
                    $fila['rubro'],
                    $fila['insumo'],
                    $fila['cantidad'] ?? '—',
                    $fila['costo_unitario'] ?? '',
                    $fila['valor_total'] ?? '',
                    $fila['meses'],
                ];
            }, $filas);

            ExportadorExcel::descargar('autogestion-perfil-proyectos-' . date('Y-m-d') . '.xlsx', $encabezados, $filasExportar);

            return;
        }

        $anio = $anioSeleccionadoId > 0 ? $this->modeloAnio->obtenerPorId($anioSeleccionadoId) : null;

        require __DIR__ . '/../vista/peticiones/autogestion-consolidado.php';
    }

    private function construirFilasDetalleCompleto(array $aprobados, int $anioPresupuestalId): array
    {
        $sedesPorId = [];
        foreach ($this->modeloSede->obtenerTodas() as $sede) {
            $sedesPorId[(int) $sede['id']] = $sede['codigo'] . ' - ' . $sede['nombre'];
        }

        $lineasPorId = [];
        foreach ($this->modeloLinea->obtenerTodas() as $linea) {
            $lineasPorId[(int) $linea['id']] = $linea['codigo'] . ' - ' . $linea['nombre'];
        }

        $motoresPorId = [];
        foreach ($this->modeloMotor->obtenerTodos() as $motor) {
            $motoresPorId[(int) $motor['id']] = $motor['codigo'] . ' - ' . $motor['nombre'];
        }

        $proyectosPorId = [];
        foreach ($this->modeloProyecto->obtenerTodos() as $proyecto) {
            $proyectosPorId[(int) $proyecto['id']] = $proyecto['codigo'] . ' - ' . $proyecto['nombre'];
        }

        $rubrosPorId = [];
        foreach ($this->modeloRubro->obtenerTodos() as $rubro) {
            $rubrosPorId[(int) $rubro['id']] = $rubro['codigo'] . ' - ' . $rubro['descripcion'];
        }

        $presupuestosPorDependenciaId = $anioPresupuestalId > 0 ? $this->modeloPresupuestoDependencia->obtenerPorAnio($anioPresupuestalId) : [];

        $modelosGasto = [
            'gasto_principal' => $this->modeloGasto,
            'gasto_extension' => $this->modeloGastoExtension,
            'gasto_postgrado' => $this->modeloGastoPostgrado,
            'gasto_unisalud' => $this->modeloGastoUnisalud,
            'gasto_sin_excedentes' => $this->modeloGastoSinExcedentes,
        ];

        $filas = [];

        foreach ($aprobados as $item) {
            $gastoOriginal = null;

            if (in_array($item['origen'], self::ORIGENES_GASTO, true) && isset($modelosGasto[$item['origen']])) {
                $gastoOriginal = $modelosGasto[$item['origen']]->obtenerPorId((int) $item['origen_id']);
            }

            // Si el registro original todavía existe, se prefiere su dependencia actual sobre la
            // guardada en el archivo aprobado — esta última es una foto fija que puede quedar
            // desactualizada (ej. reaprobaciones, sustitución de dependencias tipo Dumi al enviar).
            $dependenciaNombre = $gastoOriginal['dependencia'] ?? $item['detalle'];

            $dependenciaOrigen = $dependenciaNombre !== null && $dependenciaNombre !== ''
                ? $this->modeloDependencia->obtenerPorNombre($dependenciaNombre)
                : null;
            $techo = $dependenciaOrigen !== null ? ($presupuestosPorDependenciaId[(int) $dependenciaOrigen['id']]['techo'] ?? null) : null;

            $fila = [
                'tipo' => $item['tipo'],
                'dependencia' => $dependenciaNombre,
                'sede' => '—',
                'linea' => '—',
                'motor' => '—',
                'proyecto' => '—',
                'objeto_proyecto_paa' => '—',
                'actividad' => '—',
                'rubro' => '—',
                'insumo' => '—',
                'cantidad' => $item['cantidad'],
                'costo_unitario' => null,
                'valor_total' => $item['valor'] !== null ? (float) $item['valor'] : null,
                'meses' => '—',
                'techo' => $techo !== null ? (float) $techo : null,
                'ruta_ver' => $item['ruta_ver'],
            ];

            if ($gastoOriginal !== null) {
                $fila['sede'] = $sedesPorId[(int) ($gastoOriginal['sede_id'] ?? 0)] ?? '—';
                $fila['linea'] = $lineasPorId[(int) ($gastoOriginal['linea_id'] ?? 0)] ?? '—';
                $fila['motor'] = $motoresPorId[(int) ($gastoOriginal['motor_id'] ?? 0)] ?? '—';
                $fila['proyecto'] = $proyectosPorId[(int) ($gastoOriginal['proyecto_id'] ?? 0)] ?? '—';
                $fila['objeto_proyecto_paa'] = $gastoOriginal['objeto_proyecto_paa'] ?? '—';
                $fila['actividad'] = $gastoOriginal['actividad'] ?? '—';
                $fila['insumo'] = $gastoOriginal['insumo'] ?? '—';
                $fila['costo_unitario'] = isset($gastoOriginal['costo_unitario']) ? (float) $gastoOriginal['costo_unitario'] : null;
                $fila['meses'] = $gastoOriginal['meses'] !== '' ? $gastoOriginal['meses'] : '—';

                if (!empty($gastoOriginal['rubro_id']) && isset($rubrosPorId[(int) $gastoOriginal['rubro_id']])) {
                    $fila['rubro'] = $rubrosPorId[(int) $gastoOriginal['rubro_id']];
                } elseif (!empty($gastoOriginal['rubro_texto'])) {
                    $fila['rubro'] = $gastoOriginal['rubro_texto'];
                }
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    /**
     * Determina si la dependencia del usuario actual es la dependencia raíz del superadministrador.
     */
    private function esSuperAdminRaiz(): bool
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaUsuarioId === null) {
            return false;
        }

        $dependenciaUsuario = $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId);

        return $dependenciaUsuario !== null && !empty($dependenciaUsuario['es_raiz_superadmin']);
    }

    /**
     * Vista de supervisión para el superadmin: muestra TODO lo que está por debajo en la jerarquía
     * (sin importar a quién fue enviado ni el estado), marcando con un check lo que ya fue aceptado
     * o consolidado, para diferenciarlo de lo que sigue pendiente.
     */
    private function construirVistaJerarquica(int $anioPresupuestalId, array $dependenciasPermitidas): array
    {
        $acciones = $this->modeloArchivada->obtenerAccionesPorClave();
        $items = [];

        $filaJerarquia = function (string $origen, int $origenId, string $tipo, string $detalle, ?string $cantidad, ?float $valor, string $rutaVer, string $rutaOrigen) use ($acciones): array {
            $clave = $origen . ':' . $origenId;
            $accion = $acciones[$clave] ?? null;

            return [
                'origen' => $origen,
                'origen_id' => $origenId,
                'tipo' => $tipo,
                'detalle' => $detalle,
                'cantidad' => $cantidad,
                'valor' => $valor,
                'ruta_ver' => $rutaVer,
                'ruta_origen' => $rutaOrigen,
                'estado_item' => $accion ?? 'pendiente',
            ];
        };

        foreach ($this->modeloSolicitud->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalPracticantes = (int) $fila['riesgo1_estudiantes'] + (int) $fila['riesgo2_estudiantes']
                + (int) $fila['riesgo3_estudiantes'] + (int) $fila['riesgo4_estudiantes'] + (int) $fila['riesgo5_estudiantes'];
            $totalValor = (float) $fila['riesgo1_valor'] + (float) $fila['riesgo2_valor']
                + (float) $fila['riesgo3_valor'] + (float) $fila['riesgo4_valor'] + (float) $fila['riesgo5_valor'];
            $items[] = ['origen' => 'arl', 'facultad' => $fila['facultad']] + $filaJerarquia('arl', (int) $fila['id'], 'ARL', $fila['facultad'], $totalPracticantes . ' practicantes', $totalValor, 'index.php?ruta=solicitud-detalle&tipo=arl&id=' . (int) $fila['id'], 'index.php?ruta=solicitudes&tab=arl');
        }

        foreach ($this->modeloMonitor->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalMonitores = (int) $fila['monitores_semestre1'] + (int) $fila['monitores_semestre2'];
            $items[] = ['origen' => 'monitores', 'facultad' => $fila['dependencia']] + $filaJerarquia('monitores', (int) $fila['id'], 'Monitores', $fila['dependencia'], $totalMonitores . ' monitores', null, 'index.php?ruta=solicitud-detalle&tipo=monitores&id=' . (int) $fila['id'], 'index.php?ruta=solicitudes&tab=monitores');
        }

        foreach ($this->modeloOps->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalOps = (float) $fila['valor'] * (int) $fila['cantidad'];
            $items[] = ['origen' => 'ops', 'facultad' => $fila['dependencia']] + $filaJerarquia('ops', (int) $fila['id'], 'OPS', $fila['dependencia'], $fila['cantidad'] . ' und.', $totalOps, 'index.php?ruta=solicitud-detalle&tipo=ops&id=' . (int) $fila['id'], 'index.php?ruta=solicitudes&tab=ops');
        }

        foreach ($this->modeloPeticion->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalValor = (float) $fila['valor_s1'] + (float) $fila['valor_s2'];
            $items[] = ['origen' => 'otros', 'facultad' => null] + $filaJerarquia('otros', (int) $fila['id'], 'Petición', $fila['concepto'], null, $totalValor, 'index.php?ruta=solicitud-detalle&tipo=otros&id=' . (int) $fila['id'], 'index.php?ruta=solicitudes&tab=otros');
        }

        foreach ($this->modeloNecesidad->obtenerEnviadas() as $fila) {
            $items[] = ['origen' => 'necesidad', 'facultad' => $fila['dependencia']] + $filaJerarquia('necesidad', (int) $fila['id'], 'Perfil de proyectos', $fila['dependencia'], null, (float) $fila['valor'], 'index.php?ruta=perfil-proyectos', 'index.php?ruta=perfil-proyectos');
        }

        $mapaGastos = [
            ['modelo' => $this->modeloGasto, 'origen' => 'gasto_principal', 'tipo' => 'Gasto', 'ruta' => 'index.php?ruta=gastos', 'metodo' => 'obtenerEnviadosPorAnio'],
            ['modelo' => $this->modeloGastoExtension, 'origen' => 'gasto_extension', 'tipo' => 'Extensión', 'ruta' => 'index.php?ruta=extension', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoPostgrado, 'origen' => 'gasto_postgrado', 'tipo' => 'Postgrado', 'ruta' => 'index.php?ruta=postgrado', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoUnisalud, 'origen' => 'gasto_unisalud', 'tipo' => 'Unisalud', 'ruta' => 'index.php?ruta=unisalud', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoSinExcedentes, 'origen' => 'gasto_sin_excedentes', 'tipo' => 'Sin excedentes', 'ruta' => 'index.php?ruta=sin-excedentes', 'metodo' => 'obtenerPorAnio'],
        ];

        foreach ($mapaGastos as $fuente) {
            foreach ($fuente['modelo']->{$fuente['metodo']}($anioPresupuestalId) as $fila) {
                // En gasto_principal se descarta cualquier automático (ej. "techo_hijo", ya filtrado
                // también en el propio modelo). En los módulos de autogestión sí interesan los
                // automáticos "enviados" (excedentes, contribución a posgrado): son ítems reales que
                // deben llegar como petición una vez enviados junto con el resto.
                if ($fuente['origen'] === 'gasto_principal' && ($fila['tipo_automatico'] ?? null) !== null) {
                    continue;
                }

                if (($fuente['origen'] !== 'gasto_principal') && ($fila['estado'] ?? 'borrador') !== 'enviado') {
                    continue;
                }

                $tipo = ($fuente['origen'] === 'gasto_extension' && !empty($fila['autogestion_nombre'])) ? ucfirst($fila['autogestion_nombre']) : $fuente['tipo'];
                $rutaVerItem = $fuente['origen'] === 'gasto_principal'
                    ? $fuente['ruta']
                    : 'index.php?ruta=gasto-detalle&origen=' . $fuente['origen'] . '&id=' . (int) $fila['id'];
                $items[] = ['origen' => $fuente['origen'], 'facultad' => $fila['dependencia']] + $filaJerarquia($fuente['origen'], (int) $fila['id'], $tipo, $fila['dependencia'], $fila['cantidad'] . ' und.', (float) $fila['valor_total'], $rutaVerItem, $fuente['ruta']);
            }
        }

        $mapaIngresos = [
            ['modelo' => $this->modeloIngresoExtension, 'origen' => 'ingreso_extension', 'tipo' => 'Ingreso Extensión', 'ruta' => 'index.php?ruta=extension'],
            ['modelo' => $this->modeloIngresoPostgrado, 'origen' => 'ingreso_postgrado', 'tipo' => 'Ingreso Postgrado', 'ruta' => 'index.php?ruta=postgrado'],
            ['modelo' => $this->modeloIngresoUnisalud, 'origen' => 'ingreso_unisalud', 'tipo' => 'Ingreso Unisalud', 'ruta' => 'index.php?ruta=unisalud'],
            ['modelo' => $this->modeloIngresoSinExcedentes, 'origen' => 'ingreso_sin_excedentes', 'tipo' => 'Ingreso Sin excedentes', 'ruta' => 'index.php?ruta=sin-excedentes'],
        ];

        foreach ($mapaIngresos as $fuente) {
            foreach ($fuente['modelo']->obtenerPorAnio($anioPresupuestalId) as $fila) {
                if (($fila['estado'] ?? 'borrador') !== 'enviado') {
                    continue;
                }

                $tipo = ($fuente['origen'] === 'ingreso_extension' && !empty($fila['autogestion_nombre'])) ? ucfirst($fila['autogestion_nombre']) . ' (ingreso)' : $fuente['tipo'];
                $rutaVerItem = 'index.php?ruta=gasto-detalle&origen=' . $fuente['origen'] . '&id=' . (int) $fila['id'];
                $items[] = ['origen' => $fuente['origen'], 'facultad' => $fila['dependencia']] + $filaJerarquia($fuente['origen'], (int) $fila['id'], $tipo, $fila['dependencia'], null, (float) $fila['valor_total'], $rutaVerItem, $fuente['ruta']);
            }
        }

        $items = array_values(array_filter($items, static function (array $item) use ($dependenciasPermitidas): bool {
            return $item['facultad'] === null || in_array($item['facultad'], $dependenciasPermitidas, true);
        }));

        return $items;
    }

    /**
     * Vista de consolidado en un solo archivo/tabla para el superadmin: toma todo lo ya aprobado
     * (de cualquier tipo, dependencia o módulo) y lo muestra en filas individuales — no agrupadas
     * por tipo — conservando su dependencia, techo presupuestal, rubro y valor.
     */
    private function construirConsolidadoUnificado(array $aprobados, int $anioPresupuestalId): array
    {
        $rubrosPorId = [];
        foreach ($this->modeloRubro->obtenerTodos() as $rubro) {
            $rubrosPorId[(int) $rubro['id']] = $rubro['descripcion'];
        }

        $presupuestosPorDependenciaId = $anioPresupuestalId > 0 ? $this->modeloPresupuestoDependencia->obtenerPorAnio($anioPresupuestalId) : [];

        $modelosGasto = [
            'gasto_principal' => $this->modeloGasto,
            'gasto_extension' => $this->modeloGastoExtension,
            'gasto_postgrado' => $this->modeloGastoPostgrado,
            'gasto_unisalud' => $this->modeloGastoUnisalud,
            'gasto_sin_excedentes' => $this->modeloGastoSinExcedentes,
        ];

        $filas = [];

        foreach ($aprobados as $item) {
            $rubro = null;
            $gastoOriginal = null;

            if (in_array($item['origen'], self::ORIGENES_GASTO, true) && isset($modelosGasto[$item['origen']])) {
                $gastoOriginal = $modelosGasto[$item['origen']]->obtenerPorId((int) $item['origen_id']);

                if ($gastoOriginal !== null) {
                    if (!empty($gastoOriginal['rubro_id']) && isset($rubrosPorId[(int) $gastoOriginal['rubro_id']])) {
                        $rubro = $rubrosPorId[(int) $gastoOriginal['rubro_id']];
                    } elseif (!empty($gastoOriginal['rubro_texto'])) {
                        $rubro = $gastoOriginal['rubro_texto'];
                    }
                }
            }

            // Se prefiere la dependencia actual del registro original (si todavía existe) sobre
            // la guardada en el archivo aprobado, que es una foto fija y puede quedar desactualizada.
            $dependenciaNombre = $gastoOriginal['dependencia'] ?? $item['detalle'];

            $techo = null;
            $dependenciaOrigen = $dependenciaNombre !== null ? $this->modeloDependencia->obtenerPorNombre($dependenciaNombre) : null;

            if ($dependenciaOrigen !== null) {
                $techo = $presupuestosPorDependenciaId[(int) $dependenciaOrigen['id']]['techo'] ?? null;
            }

            $filas[] = [
                'tipo' => $item['tipo'],
                'dependencia' => $dependenciaNombre,
                'rubro' => $rubro,
                'techo' => $techo !== null ? (float) $techo : null,
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'] !== null ? (float) $item['valor'] : null,
                'ruta_ver' => $item['ruta_ver'],
            ];
        }

        return $filas;
    }

    private function redireccionarConsolidado(): array
    {
        $tipo = trim($_POST['tipo'] ?? '');
        $dependenciaNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if ($tipo === '') {
            return ['El grupo consolidado que intentas redireccionar no existe.', ''];
        }

        if ($dependenciaNombre === '') {
            return ['Selecciona la dependencia a la que se redireccionará.', ''];
        }

        $rol = $rolDestinatarioId > 0 ? $this->modeloRol->obtenerPorId($rolDestinatarioId) : null;

        if ($rol === null) {
            return ['Selecciona el rol al que se redireccionará.', ''];
        }

        $dependencia = $this->modeloDependencia->obtenerPorNombre($dependenciaNombre);
        $destinatarios = $dependencia !== null
            ? $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependencia['id'], $rolDestinatarioId)
            : [];

        if (empty($destinatarios)) {
            return ['No se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '" para notificar.', ''];
        }

        $cantidadItems = $this->modeloArchivada->redireccionar($tipo, $dependenciaNombre);

        if ($cantidadItems === 0) {
            return ['No hay ítems consolidados de "' . $tipo . '" para redireccionar.', ''];
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);
        $asunto = 'Petición consolidada redireccionada — ' . $tipo;
        $cuerpo = 'Se te redireccionó el grupo consolidado de "' . $tipo . '" (' . $cantidadItems . ' ítem(s)) para tu gestión en "' . $dependenciaNombre . '".';

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear($remitenteId, (int) $destinatario['id'], $asunto, $cuerpo);
        }

        return ['', 'Se redireccionó "' . $tipo . '" (' . $cantidadItems . ' ítem(s)) a ' . count($destinatarios) . ' usuario(s) con el rol "' . $rol['nombre'] . '".'];
    }

    private function rechazarRedireccion(): array
    {
        $origen = trim($_POST['origen'] ?? '');
        $origenId = (int) ($_POST['origen_id'] ?? 0);

        if ($origen === '' || $origenId <= 0) {
            return ['El elemento que intentas rechazar no existe.', ''];
        }

        if (!$this->modeloArchivada->rechazarRedireccion($origen, $origenId)) {
            return ['No se pudo rechazar la redirección.', ''];
        }

        return ['', 'Se rechazó la redirección y se devolvió a la dependencia de origen.'];
    }

    private function eliminarPendiente(): array
    {
        $origen = trim($_POST['origen'] ?? '');
        $origenId = (int) ($_POST['origen_id'] ?? 0);

        if ($origen === '' || $origenId <= 0) {
            return ['El elemento que intentas eliminar no existe.', ''];
        }

        $eliminado = match ($origen) {
            'arl' => $this->modeloSolicitud->eliminar($origenId),
            'monitores' => $this->modeloMonitor->eliminar($origenId),
            'ops' => $this->modeloOps->eliminar($origenId),
            'otros' => $this->modeloPeticion->eliminar($origenId),
            'gasto_principal' => $this->modeloGasto->eliminar($origenId),
            'gasto_extension' => $this->modeloGastoExtension->eliminar($origenId),
            'gasto_postgrado' => $this->modeloGastoPostgrado->eliminar($origenId),
            'gasto_unisalud' => $this->modeloGastoUnisalud->eliminar($origenId),
            'gasto_sin_excedentes' => $this->modeloGastoSinExcedentes->eliminar($origenId),
            'ingreso_extension' => $this->modeloIngresoExtension->eliminar($origenId),
            'ingreso_postgrado' => $this->modeloIngresoPostgrado->eliminar($origenId),
            'ingreso_unisalud' => $this->modeloIngresoUnisalud->eliminar($origenId),
            'ingreso_sin_excedentes' => $this->modeloIngresoSinExcedentes->eliminar($origenId),
            'necesidad' => $this->modeloNecesidad->eliminar($origenId),
            default => false,
        };

        if (!$eliminado) {
            return ['No se pudo eliminar el elemento.', ''];
        }

        $this->modeloArchivada->eliminarPorOrigen($origen, $origenId);

        return ['', 'Elemento eliminado correctamente.'];
    }

    private function obtenerDependenciasPermitidas(): array
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaUsuarioId === null) {
            return [];
        }

        $dependenciaUsuario = $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId);

        if ($dependenciaUsuario === null) {
            return [];
        }

        $permitidas = [$dependenciaUsuario['nombre']];

        foreach ($this->modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId) as $descendiente) {
            $permitidas[] = $descendiente['nombre'];
        }

        return $permitidas;
    }

    private function filtrarPorDependencia(array $items, array $dependenciasPermitidas): array
    {
        return array_values(array_filter($items, static function (array $item) use ($dependenciasPermitidas): bool {
            return $item['origen'] === 'otros' || $item['origen'] === 'necesidad_grupo' || in_array($item['detalle'], $dependenciasPermitidas, true);
        }));
    }

    /**
     * Necesidades (Perfil de proyectos) enviadas, visibles solo para quien coincide exactamente
     * con el rol y la dependencia a los que fueron enviadas — igual que ARL/Monitores/OPS — y que
     * todavía no fueron archivadas/aprobadas.
     */
    private function obtenerNecesidadesVisibles(): array
    {
        $archivadas = $this->modeloArchivada->obtenerClavesProcesadas();
        $visibles = [];

        foreach ($this->modeloNecesidad->obtenerEnviadas() as $fila) {
            if (isset($archivadas['necesidad:' . $fila['id']])) {
                continue;
            }

            if ($this->visibilidadSolicitud($fila['dependencia'], $fila['rol_destinatario_id'])) {
                $visibles[] = $fila;
            }
        }

        return $visibles;
    }

    /**
     * Aprueba o archiva de una sola vez todas las necesidades visibles para el usuario actual
     * (el "Aceptar todos"/"Archivar todos" del grupo de Perfil de proyectos en Pendientes), sin
     * afectar las que estén dirigidas a otro rol o dependencia.
     */
    private function procesarGrupoNecesidades(string $accionArchivada): array
    {
        $visibles = $this->obtenerNecesidadesVisibles();

        if (empty($visibles)) {
            return ['No hay proyectos pendientes para procesar.', ''];
        }

        foreach ($visibles as $fila) {
            $this->modeloArchivada->archivar([
                'origen' => 'necesidad',
                'origen_id' => (int) $fila['id'],
                'accion' => $accionArchivada,
                'tipo' => 'Perfil de proyectos',
                'detalle' => $fila['dependencia'],
                'cantidad' => null,
                'valor' => (float) $fila['valor'],
                'ruta_ver' => 'index.php?ruta=perfil-proyectos',
                'ruta_origen' => 'index.php?ruta=perfil-proyectos',
            ]);
        }

        $verbo = $accionArchivada === 'aprobada' ? 'aceptaron' : 'archivaron';

        return ['', 'Se ' . $verbo . ' ' . count($visibles) . ' proyecto(s) de Perfil de proyectos.'];
    }

    private function construirPendientes(int $anioPresupuestalId, array $dependenciasPermitidas): array
    {
        $archivadas = $this->modeloArchivada->obtenerClavesProcesadas();
        $pendientes = [];
        $volver = '&volver=' . urlencode('index.php?ruta=peticiones&vista=pendientes&anio_id=' . $anioPresupuestalId);

        $pendientesSolicitudes = [];

        foreach ($this->modeloSolicitud->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalPracticantes = (int) $fila['riesgo1_estudiantes'] + (int) $fila['riesgo2_estudiantes']
                + (int) $fila['riesgo3_estudiantes'] + (int) $fila['riesgo4_estudiantes'] + (int) $fila['riesgo5_estudiantes'];
            $totalValor = (float) $fila['riesgo1_valor'] + (float) $fila['riesgo2_valor']
                + (float) $fila['riesgo3_valor'] + (float) $fila['riesgo4_valor'] + (float) $fila['riesgo5_valor'];

            if ($this->visibilidadSolicitud($fila['facultad'], $fila['rol_destinatario_id'])) {
                $pendientesSolicitudes[] = $this->fila('arl', (int) $fila['id'], 'ARL', $fila['facultad'], $totalPracticantes . ' practicantes', $totalValor, 'solicitud', 'index.php?ruta=solicitudes&tab=arl', 'index.php?ruta=solicitud-detalle&tipo=arl&id=' . (int) $fila['id'] . $volver);
            }
        }

        foreach ($this->modeloMonitor->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalMonitores = (int) $fila['monitores_semestre1'] + (int) $fila['monitores_semestre2'];
            if ($this->visibilidadSolicitud($fila['dependencia'], $fila['rol_destinatario_id'])) {
                $pendientesSolicitudes[] = $this->fila('monitores', (int) $fila['id'], 'Monitores', $fila['dependencia'], $totalMonitores . ' monitores', null, 'solicitud', 'index.php?ruta=solicitudes&tab=monitores', 'index.php?ruta=solicitud-detalle&tipo=monitores&id=' . (int) $fila['id'] . $volver);
            }
        }

        foreach ($this->modeloOps->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalOps = (float) $fila['valor'] * (int) $fila['cantidad'];
            if ($this->visibilidadSolicitud($fila['dependencia'], $fila['rol_destinatario_id'])) {
                $pendientesSolicitudes[] = $this->fila('ops', (int) $fila['id'], 'OPS', $fila['dependencia'], $fila['cantidad'] . ' und.', $totalOps, 'solicitud', 'index.php?ruta=solicitudes&tab=ops', 'index.php?ruta=solicitud-detalle&tipo=ops&id=' . (int) $fila['id'] . $volver);
            }
        }

        foreach ($this->modeloPeticion->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalValor = (float) $fila['valor_s1'] + (float) $fila['valor_s2'];
            if ($this->visibilidadSolicitud(null, $fila['rol_destinatario_id'])) {
                $pendientesSolicitudes[] = $this->fila('otros', (int) $fila['id'], 'Petición', $fila['concepto'], null, $totalValor, 'solicitud', 'index.php?ruta=solicitudes&tab=otros', 'index.php?ruta=solicitud-detalle&tipo=otros&id=' . (int) $fila['id'] . $volver);
            }
        }

        $pendientesSolicitudes = array_values(array_filter($pendientesSolicitudes, static function (array $item) use ($archivadas): bool {
            return !isset($archivadas[$item['origen'] . ':' . $item['origen_id']]);
        }));

        $necesidadesVisibles = $this->obtenerNecesidadesVisibles();

        if (count($necesidadesVisibles) > 1) {
            $totalValorNecesidades = array_sum(array_map(static fn (array $f): float => (float) $f['valor'], $necesidadesVisibles));
            $pendientes[] = [
                'origen' => 'necesidad_grupo',
                'origen_id' => 0,
                'tipo' => 'Perfil de proyectos',
                'detalle' => count($necesidadesVisibles) . ' proyectos pendientes',
                'cantidad' => (string) count($necesidadesVisibles),
                'valor' => $totalValorNecesidades,
                'accion_aprobar' => 'Aceptar todos',
                'accion_rechazar' => 'Archivar todos',
                'ruta_origen' => 'index.php?ruta=perfil-proyectos',
                'ruta_ver' => 'index.php?ruta=perfil-proyectos',
                'semaforo' => null,
                'puede_actuar' => true,
            ];
        } else {
            foreach ($necesidadesVisibles as $fila) {
                $pendientes[] = $this->fila('necesidad', (int) $fila['id'], 'Perfil de proyectos', $fila['dependencia'], null, (float) $fila['valor'], 'gasto', 'index.php?ruta=perfil-proyectos', 'index.php?ruta=perfil-proyectos');
            }
        }

        $pendientes = array_values(array_filter($pendientes, static function (array $item) use ($archivadas): bool {
            return !isset($archivadas[$item['origen'] . ':' . $item['origen_id']]);
        }));

        foreach ($this->modeloArchivada->obtenerRedireccionadas() as $redirigida) {
            $pendientes[] = $this->filaRedireccionada($redirigida);
        }

        $pendientes = $this->filtrarPorDependencia($pendientes, $dependenciasPermitidas);

        $pendientesGasto = [];

        foreach ($this->modeloGasto->obtenerEnviadosPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_principal', $fila, 'Gasto', 'index.php?ruta=gastos', 'index.php?ruta=gastos');
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoExtension->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $tipo = !empty($fila['autogestion_nombre']) ? ucfirst($fila['autogestion_nombre']) : 'Extensión';
            $filaGasto = $this->filaGasto('gasto_extension', $fila, $tipo, 'index.php?ruta=extension', 'index.php?ruta=gasto-detalle&origen=gasto_extension&id=' . (int) $fila['id'] . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoPostgrado->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_postgrado', $fila, 'Postgrado', 'index.php?ruta=postgrado', 'index.php?ruta=gasto-detalle&origen=gasto_postgrado&id=' . (int) $fila['id'] . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoUnisalud->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_unisalud', $fila, 'Unisalud', 'index.php?ruta=unisalud', 'index.php?ruta=gasto-detalle&origen=gasto_unisalud&id=' . (int) $fila['id'] . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoSinExcedentes->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_sin_excedentes', $fila, 'Sin excedentes', 'index.php?ruta=sin-excedentes', 'index.php?ruta=gasto-detalle&origen=gasto_sin_excedentes&id=' . (int) $fila['id'] . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloIngresoExtension->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $tipo = !empty($fila['autogestion_nombre']) ? ucfirst($fila['autogestion_nombre']) . ' (ingreso)' : 'Ingreso Extensión';
            $filaIngreso = $this->filaIngreso('ingreso_extension', $fila, $tipo, 'index.php?ruta=extension', 'index.php?ruta=gasto-detalle&origen=ingreso_extension&id=' . (int) $fila['id'] . $volver);
            if ($filaIngreso !== null) {
                $pendientesGasto[] = $filaIngreso;
            }
        }

        foreach ($this->modeloIngresoPostgrado->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaIngreso = $this->filaIngreso('ingreso_postgrado', $fila, 'Ingreso Postgrado', 'index.php?ruta=postgrado', 'index.php?ruta=gasto-detalle&origen=ingreso_postgrado&id=' . (int) $fila['id'] . $volver);
            if ($filaIngreso !== null) {
                $pendientesGasto[] = $filaIngreso;
            }
        }

        foreach ($this->modeloIngresoUnisalud->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaIngreso = $this->filaIngreso('ingreso_unisalud', $fila, 'Ingreso Unisalud', 'index.php?ruta=unisalud', 'index.php?ruta=gasto-detalle&origen=ingreso_unisalud&id=' . (int) $fila['id'] . $volver);
            if ($filaIngreso !== null) {
                $pendientesGasto[] = $filaIngreso;
            }
        }

        foreach ($this->modeloIngresoSinExcedentes->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaIngreso = $this->filaIngreso('ingreso_sin_excedentes', $fila, 'Ingreso Sin excedentes', 'index.php?ruta=sin-excedentes', 'index.php?ruta=gasto-detalle&origen=ingreso_sin_excedentes&id=' . (int) $fila['id'] . $volver);
            if ($filaIngreso !== null) {
                $pendientesGasto[] = $filaIngreso;
            }
        }

        $pendientesGasto = array_values(array_filter($pendientesGasto, static function (array $item) use ($archivadas): bool {
            return !isset($archivadas[$item['origen'] . ':' . $item['origen_id']]);
        }));

        return array_merge($pendientes, $pendientesSolicitudes, $pendientesGasto);
    }

    /**
     * Una solicitud (ARL/Monitores/OPS/Otros) solo debe ser visible en Peticiones para el usuario
     * que coincide exactamente con la dependencia y el rol al que fue enviada — nadie más la ve.
     */
    private function visibilidadSolicitud(?string $dependenciaNombre, ?int $rolDestinatarioId): bool
    {
        if ($rolDestinatarioId === null) {
            return false;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $rolUsuarioId = !empty($usuarioActual['rol_id']) ? (int) $usuarioActual['rol_id'] : null;

        if ($rolUsuarioId !== $rolDestinatarioId) {
            return false;
        }

        if ($dependenciaNombre === null) {
            return true;
        }

        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaUsuarioId === null) {
            return false;
        }

        $dependenciaUsuario = $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId);

        return $dependenciaUsuario !== null && $dependenciaUsuario['nombre'] === $dependenciaNombre;
    }

    /**
     * Un gasto (principal o de autogestión) se maneja igual que una solicitud: solo lo ve, con
     * acceso completo, el usuario que coincide exactamente con la dependencia y el rol al que
     * fue enviado — nadie más.
     */
    private function filaGasto(string $origen, array $fila, string $tipo, string $rutaOrigen, string $rutaVer): ?array
    {
        $rolDestinatarioId = !empty($fila['rol_destinatario_id']) ? (int) $fila['rol_destinatario_id'] : null;

        if (!$this->visibilidadSolicitud($fila['dependencia'], $rolDestinatarioId)) {
            return null;
        }

        return $this->fila($origen, (int) $fila['id'], $tipo, $fila['dependencia'], $fila['cantidad'] . ' und.', (float) $fila['valor_total'], 'gasto', $rutaOrigen, $rutaVer);
    }

    /**
     * Un ingreso de autogestión se maneja igual que un gasto de autogestión: solo lo ve, con
     * acceso completo, el usuario que coincide exactamente con la dependencia y el rol al que
     * fue enviado — nadie más.
     */
    private function filaIngreso(string $origen, array $fila, string $tipo, string $rutaOrigen, string $rutaVer): ?array
    {
        $rolDestinatarioId = !empty($fila['rol_destinatario_id']) ? (int) $fila['rol_destinatario_id'] : null;

        if (!$this->visibilidadSolicitud($fila['dependencia'], $rolDestinatarioId)) {
            return null;
        }

        return $this->fila($origen, (int) $fila['id'], $tipo, $fila['dependencia'], null, (float) $fila['valor_total'], 'gasto', $rutaOrigen, $rutaVer);
    }

    private function filaRedireccionada(array $redirigida): array
    {
        return [
            'origen' => $redirigida['origen'],
            'origen_id' => (int) $redirigida['origen_id'],
            'tipo' => $redirigida['tipo'],
            'detalle' => $redirigida['redireccionado_a_dependencia'] ?? $redirigida['detalle'],
            'cantidad' => $redirigida['cantidad'],
            'valor' => $redirigida['valor'] !== null ? (float) $redirigida['valor'] : null,
            'accion_aprobar' => 'Consolidar',
            'accion_rechazar' => 'Rechazar',
            'ruta_origen' => $redirigida['ruta_origen'] ?? 'index.php?ruta=peticiones',
            'ruta_ver' => $redirigida['ruta_ver'],
            'redireccionado' => true,
            'semaforo' => null,
            'puede_actuar' => true,
        ];
    }

    private function fila(string $origen, int $origenId, string $tipo, string $detalle, ?string $cantidad, ?float $valor, string $familia, string $ruta, string $rutaVer, ?array $semaforo = null): array
    {
        return [
            'origen' => $origen,
            'origen_id' => $origenId,
            'tipo' => $tipo,
            'detalle' => $detalle,
            'cantidad' => $cantidad,
            'valor' => $valor,
            'accion_aprobar' => $familia === 'solicitud' ? 'Consolidar' : 'Aprobar e incorporar',
            'accion_rechazar' => $familia === 'solicitud' ? 'Rechazar' : 'devolver',
            'ruta_origen' => $ruta,
            'ruta_ver' => $rutaVer,
            'semaforo' => $semaforo,
            'puede_actuar' => true,
        ];
    }
}
