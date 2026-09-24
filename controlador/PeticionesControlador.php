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
require_once __DIR__ . '/../modelo/PeticionHistorial.php';
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
require_once __DIR__ . '/../config/conexion.php';

class PeticionesControlador
{
    private PDO $db;
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
    private PeticionHistorial $modeloHistorial;
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

    // Caches de una sola petición HTTP (nunca persisten entre requests): evitan volver a consultar
    // lo mismo fila por fila cuando el landing de Peticiones lista cientos/miles de ítems — ver
    // obtenerRegistroGastoCacheado(), obtenerUsuarioActualCacheado() y esSuperAdminRaiz().
    private array $cacheRegistroPorClave = [];
    private bool $usuarioActualCargado = false;
    private ?array $usuarioActualCache = null;
    private ?bool $cacheEsSuperAdminRaiz = null;
    private bool $dependenciaUsuarioActualCargada = false;
    private ?array $dependenciaUsuarioActualCache = null;
    private ?array $nombresDescendientesUsuarioActualCache = null;

    private const VISTAS = ['pendientes', 'consolidado', 'archivar', 'enviadas'];

    private const ORIGENES_GASTO = ['gasto_principal', 'gasto_extension', 'gasto_postgrado', 'gasto_unisalud', 'gasto_sin_excedentes'];

    private const ORIGENES_AUTOGESTION = ['gasto_extension', 'gasto_postgrado', 'gasto_unisalud', 'gasto_sin_excedentes', 'necesidad'];

    private const TABLAS_ORIGEN = [
        'arl' => 'solicitudes_arl',
        'monitores' => 'solicitudes_monitores',
        'ops' => 'solicitudes_ops',
        'otros' => 'solicitudes_peticiones',
        'necesidad' => 'necesidades_academicas',
        'gasto_principal' => 'gastos',
        'gasto_extension' => 'gastos_extension',
        'gasto_postgrado' => 'gastos_postgrado',
        'gasto_unisalud' => 'gastos_unisalud',
        'gasto_sin_excedentes' => 'gastos_sin_excedentes',
        'ingreso_extension' => 'ingresos_extension',
        'ingreso_postgrado' => 'ingresos_postgrado',
        'ingreso_unisalud' => 'ingresos_unisalud',
        'ingreso_sin_excedentes' => 'ingresos_sin_excedentes',
    ];

    private const TABLAS_CONCEPTOS_ORIGEN = [
        'ingreso_extension' => ['ingresos_extension_conceptos', 'ingreso_id'],
        'ingreso_postgrado' => ['ingresos_postgrado_conceptos', 'ingreso_id'],
        'ingreso_unisalud' => ['ingresos_unisalud_conceptos', 'ingreso_id'],
        'ingreso_sin_excedentes' => ['ingresos_sin_excedentes_conceptos', 'ingreso_id'],
    ];

    public function __construct()
    {
        $this->db = Conexion::obtener();
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
        $this->modeloHistorial = new PeticionHistorial();
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
                $origenAccion = $_POST['origen'] ?? '';
                $origenIdAccion = (int) ($_POST['origen_id'] ?? 0);
                $this->modeloArchivada->archivar([
                    'origen' => $origenAccion,
                    'origen_id' => $origenIdAccion,
                    'accion' => $accion === 'aprobar' ? 'aprobada' : 'archivada',
                    'tipo' => $_POST['tipo'] ?? '',
                    'detalle' => $_POST['detalle'] ?? '',
                    'cantidad' => $_POST['cantidad'] !== '' ? $_POST['cantidad'] : null,
                    'valor' => $_POST['valor'] !== '' ? (float) $_POST['valor'] : null,
                    'ruta_ver' => $_POST['ruta_ver'] ?? 'index.php?ruta=peticiones',
                    'ruta_origen' => $_POST['ruta_origen'] ?? null,
                ]);
                $this->modeloHistorial->registrar(
                    $origenAccion,
                    $origenIdAccion,
                    $accion === 'aprobar' ? 'aprobada' : 'archivada',
                    $accion === 'aprobar' ? 'Consolidado' : 'Archivado'
                );
            } elseif ($accion === 'restaurar') {
                $archivadaId = (int) ($_POST['id'] ?? 0);
                $archivadaRestaurar = $this->modeloArchivada->obtenerPorId($archivadaId);
                $this->modeloArchivada->restaurar($archivadaId);
                if ($archivadaRestaurar !== null) {
                    $this->modeloHistorial->registrar($archivadaRestaurar['origen'], (int) $archivadaRestaurar['origen_id'], 'restaurada', 'Restaurado desde Archivados');
                }
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
            } elseif ($accion === 'aprobar_pendientes_grupo' || $accion === 'archivar_pendientes_grupo') {
                [$errorPendGrupo, $exitoPendGrupo] = $this->procesarPendientesGrupo(
                    $accion === 'aprobar_pendientes_grupo' ? 'aprobada' : 'archivada'
                );
                $_SESSION['peticiones_flash_error'] = $errorPendGrupo;
                $_SESSION['peticiones_flash_exito'] = $exitoPendGrupo;
            } elseif ($accion === 'eliminar_pendientes_grupo') {
                [$errorEliminarGrupo, $exitoEliminarGrupo] = $this->eliminarPendientesGrupo();
                $_SESSION['peticiones_flash_error'] = $errorEliminarGrupo;
                $_SESSION['peticiones_flash_exito'] = $exitoEliminarGrupo;
            } elseif ($accion === 'enviar_pendientes_grupo') {
                [$errorEnviarPend, $exitoEnviarPend] = $this->enviarPendientesGrupo();
                $_SESSION['peticiones_flash_error'] = $errorEnviarPend;
                $_SESSION['peticiones_flash_exito'] = $exitoEnviarPend;
            } elseif ($accion === 'archivar_consolidado') {
                [$errorArchivarCons, $exitoArchivarCons] = $this->archivarConsolidadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorArchivarCons;
                $_SESSION['peticiones_flash_exito'] = $exitoArchivarCons;
            } elseif ($accion === 'desconsolidar_grupo') {
                [$errorDesconsolidar, $exitoDesconsolidar] = $this->desconsolidarGrupo();
                $_SESSION['peticiones_flash_error'] = $errorDesconsolidar;
                $_SESSION['peticiones_flash_exito'] = $exitoDesconsolidar;
            } elseif ($accion === 'consolidar_archivado') {
                [$errorConsolidarArch, $exitoConsolidarArch] = $this->consolidarArchivadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorConsolidarArch;
                $_SESSION['peticiones_flash_exito'] = $exitoConsolidarArch;
            } elseif ($accion === 'duplicar_archivado') {
                [$errorDuplicarArch, $exitoDuplicarArch] = $this->duplicarArchivadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorDuplicarArch;
                $_SESSION['peticiones_flash_exito'] = $exitoDuplicarArch;
            } elseif ($accion === 'enviar_archivado') {
                [$errorEnviarArch, $exitoEnviarArch] = $this->enviarArchivadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorEnviarArch;
                $_SESSION['peticiones_flash_exito'] = $exitoEnviarArch;
            } elseif ($accion === 'consolidar_enviado') {
                [$errorConsolidarEnv, $exitoConsolidarEnv] = $this->consolidarEnviadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorConsolidarEnv;
                $_SESSION['peticiones_flash_exito'] = $exitoConsolidarEnv;
            } elseif ($accion === 'duplicar_enviado') {
                [$errorDuplicarEnv, $exitoDuplicarEnv] = $this->duplicarEnviadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorDuplicarEnv;
                $_SESSION['peticiones_flash_exito'] = $exitoDuplicarEnv;
            } elseif ($accion === 'enviar_enviado') {
                [$errorEnviarEnv, $exitoEnviarEnv] = $this->enviarEnviadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorEnviarEnv;
                $_SESSION['peticiones_flash_exito'] = $exitoEnviarEnv;
            }

            $vistaDestino = $_POST['vista'] ?? 'pendientes';
            $destino = 'index.php?ruta=peticiones&vista=' . urlencode($vistaDestino);
            if (!empty($_POST['anio_id'])) {
                $destino .= '&anio_id=' . (int) $_POST['anio_id'];
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
        // "Auditar" es ahora un toggle global en la headerbar (ver AuditoriaControlador), no un
        // parámetro de esta página — persiste al navegar entre módulos.
        $modoJerarquia = $esSuperAdminRaiz && !empty($_SESSION['modo_auditoria']);

        if ($anioSeleccionadoId <= 0) {
            $pendientes = [];
        } elseif ($modoJerarquia) {
            $pendientes = $this->construirVistaJerarquica($anioSeleccionadoId, $dependenciasPermitidas);
        } else {
            $pendientes = $this->construirPendientes($anioSeleccionadoId, $dependenciasPermitidas);
        }

        if (!$modoJerarquia) {
            // Gasto y Perfil de proyectos (necesidad) son los orígenes con volumen suficiente para
            // que verlos uno por uno inunde la tabla — se agrupan en una fila por dependencia de
            // origen (ver agruparPendientesPorDependencia()), separados por dependencia Y por tipo
            // (nunca se mezcla un Gasto con una Necesidad de la misma dependencia en un solo grupo).
            // El resto de orígenes (Solicitudes, Ingresos, redirigidos) se queda siempre individual
            // — no se mezclan entre sí hasta que se envían a Consolidado por tipo.
            $origenesAgrupables = ['gasto_principal', 'necesidad'];
            $pendientesAgrupables = array_values(array_filter($pendientes, static fn (array $item): bool => in_array($item['origen'], $origenesAgrupables, true)));
            $pendientesResto = array_values(array_filter($pendientes, static fn (array $item): bool => !in_array($item['origen'], $origenesAgrupables, true)));
            $pendientes = array_merge(
                $this->agruparPendientesPorDependencia($pendientesAgrupables),
                $pendientesResto
            );
        }

        $aprobados = $vista === 'consolidado' ? $this->modeloArchivada->obtenerPorAccion('aprobada') : [];
        $aprobados = $this->filtrarPorDependencia($aprobados, $dependenciasPermitidas);

        $filasDetalladasConsolidado = $vista === 'consolidado' ? $this->construirFilasDetalleCompleto($aprobados, $anioSeleccionadoId) : [];

        $consolidado = [];
        foreach ($aprobados as $indice => $item) {
            $tipo = $item['tipo'];
            // Las peticiones "Otros" (solicitudes_peticiones) son variantes entre sí — cada una
            // tiene su propio concepto y valor — así que no se fusionan en una sola fila sumada
            // como el resto de tipos; cada una queda como su propia fila en Consolidado.
            $claveGrupo = $item['origen'] === 'otros' ? $tipo . '#' . $item['origen_id'] : $tipo;
            if (!isset($consolidado[$claveGrupo])) {
                $consolidado[$claveGrupo] = ['tipo' => $tipo, 'cantidad' => 0, 'ruta' => $item['ruta_origen'] ?? 'index.php?ruta=peticiones', 'items' => [], 'puede_editar' => true];
            }
            $consolidado[$claveGrupo]['cantidad']++;
            $filaDetalle = $filasDetalladasConsolidado[$indice] ?? [];
            if (empty($filaDetalle['puede_editar'])) {
                $consolidado[$claveGrupo]['puede_editar'] = false;
            }
            $consolidado[$claveGrupo]['items'][] = [
                'origen' => $item['origen'],
                'origen_id' => (int) $item['origen_id'],
                'tipo' => $tipo,
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $this->construirRutaVer($item['origen'], (int) $item['origen_id'], 'aprobada'),
                'ruta_origen' => $item['ruta_origen'] ?? 'index.php?ruta=peticiones',
                'puede_editar' => $filaDetalle['puede_editar'] ?? false,
                'dependencia' => $filaDetalle['dependencia'] ?? $item['detalle'],
                'sede' => $filaDetalle['sede'] ?? null,
                'linea' => $filaDetalle['linea'] ?? null,
                'motor' => $filaDetalle['motor'] ?? null,
                'proyecto' => $filaDetalle['proyecto'] ?? null,
                'objeto_proyecto_paa' => $filaDetalle['objeto_proyecto_paa'] ?? null,
                'actividad' => $filaDetalle['actividad'] ?? null,
                'rubro' => $filaDetalle['rubro'] ?? null,
                'insumo' => $filaDetalle['insumo'] ?? null,
                'costo_unitario' => $filaDetalle['costo_unitario'] ?? null,
                'meses' => $filaDetalle['meses'] ?? null,
                'techo' => $filaDetalle['techo'] ?? null,
            ];
        }
        $consolidado = array_values($consolidado);

        $consolidadoUnificado = ($vista === 'consolidado' && $modoJerarquia)
            ? $this->construirConsolidadoUnificado($aprobados, $anioSeleccionadoId)
            : [];

        $archivados = $vista === 'archivar' ? $this->modeloArchivada->obtenerPorAccion('archivada') : [];
        $archivados = $this->filtrarPorDependencia($archivados, $dependenciasPermitidas);
        foreach ($archivados as &$itemArchivado) {
            $itemArchivado['ruta_ver'] = $this->construirRutaVer($itemArchivado['origen'], (int) $itemArchivado['origen_id'], 'archivada');
        }
        unset($itemArchivado);

        $enviadas = ($vista === 'enviadas' && $anioSeleccionadoId > 0)
            ? $this->construirEnviadas($anioSeleccionadoId, $dependenciasPermitidas)
            : [];

        $dependenciasSugeridas = $this->modeloDependencia->obtenerActivasParaEnvio();
        $roles = $this->modeloRol->obtenerTodos();
        $rolesPorTipo = $this->modeloTipoDependenciaRol->obtenerMapaCompleto();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();

        require __DIR__ . '/../vista/peticiones/index.php';
    }

    /**
     * Devuelve el modelo de origen (ya instanciado en el constructor) correspondiente, para las
     * acciones genéricas de Editar/Eliminar del landing de "Ver".
     */
    private function obtenerModeloPorOrigen(string $origen): ?object
    {
        return match ($origen) {
            'arl' => $this->modeloSolicitud,
            'monitores' => $this->modeloMonitor,
            'ops' => $this->modeloOps,
            'otros' => $this->modeloPeticion,
            'necesidad' => $this->modeloNecesidad,
            'gasto_principal' => $this->modeloGasto,
            'gasto_extension' => $this->modeloGastoExtension,
            'gasto_postgrado' => $this->modeloGastoPostgrado,
            'gasto_unisalud' => $this->modeloGastoUnisalud,
            'gasto_sin_excedentes' => $this->modeloGastoSinExcedentes,
            'ingreso_extension' => $this->modeloIngresoExtension,
            'ingreso_postgrado' => $this->modeloIngresoPostgrado,
            'ingreso_unisalud' => $this->modeloIngresoUnisalud,
            'ingreso_sin_excedentes' => $this->modeloIngresoSinExcedentes,
            default => null,
        };
    }

    /**
     * Ítems individuales de un origen puntual, en el mismo estado/bandeja en la que se encontraba
     * el ítem sobre el que se pulsó "Ver" (pendiente/aprobada/archivada/enviada), con forma
     * compatible con construirFilasDetalleCompleto() (origen, origen_id, tipo, detalle, cantidad,
     * valor, ruta_ver, ruta_origen).
     */
    private function obtenerItemsCrudosPorEstado(string $estado, string $origen, int $anioPresupuestalId, array $dependenciasPermitidas): array
    {
        if ($estado === 'aprobada' || $estado === 'archivada') {
            $items = $this->modeloArchivada->obtenerPorAccion($estado);
            $items = $this->filtrarPorDependencia($items, $dependenciasPermitidas);

            return array_values(array_filter($items, static fn (array $item): bool => $item['origen'] === $origen));
        }

        if ($estado === 'enviada') {
            $items = $this->construirEnviadas($anioPresupuestalId, $dependenciasPermitidas);

            return array_values(array_filter($items, static fn (array $item): bool => $item['origen'] === $origen));
        }

        // El superadmin puede pulsar "Ver" en modo jerarquía sobre un ítem pendiente que no le fue
        // dirigido a él (es el caso que originó este rediseño): ni construirPendientes() ni
        // obtenerNecesidadesVisibles() sirven ahí, porque ambos exigen ser el destinatario exacto
        // (visibilidadSolicitud()). construirVistaJerarquica() sí puede verlo (solo exige estar en
        // la rama de dependencias del superadmin), así que se reutiliza para 'pendiente' en ese caso.
        if ($this->esSuperAdminRaiz()) {
            $items = $this->construirVistaJerarquica($anioPresupuestalId, $dependenciasPermitidas);

            return array_values(array_filter(
                $items,
                static fn (array $item): bool => $item['origen'] === $origen && ($item['estado_item'] ?? 'pendiente') === 'pendiente'
            ));
        }

        // 'pendiente': Perfil de proyectos no pasa por construirPendientes() con una fila por ítem
        // cuando hay más de uno visible (se resume en una sola fila "N proyectos pendientes" para
        // esa vista) — aquí sí interesa cada ítem individual, así que se arma directo desde
        // obtenerNecesidadesVisibles().
        if ($origen === 'necesidad') {
            return array_map(static function (array $fila): array {
                return [
                    'origen' => 'necesidad',
                    'origen_id' => (int) $fila['id'],
                    'tipo' => 'Perfil de proyectos',
                    'detalle' => $fila['dependencia_destino'] ?? $fila['dependencia'],
                    'cantidad' => null,
                    'valor' => (float) $fila['valor'],
                    'ruta_origen' => 'index.php?ruta=perfil-proyectos',
                ];
            }, $this->obtenerNecesidadesVisibles());
        }

        $items = $this->construirPendientes($anioPresupuestalId, $dependenciasPermitidas);

        return array_values(array_filter($items, static fn (array $item): bool => $item['origen'] === $origen));
    }

    /**
     * Landing único de "Ver": la tabla real de ese origen (idéntica, estructura y funciones, a
     * Dev > Tabla — vista/dev/pruebas/tabla.php), filtrada al mismo estado/bandeja en la que
     * estaba el ítem clicado, con esa fila resaltada. Editar/Eliminar (in-place, sin modal aparte)
     * reutilizan el modelo real de cada origen — nunca mutan un array en memoria como el prototipo.
     */
    public function tipoDetalle(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $estado = $_GET['estado'] ?? '';
        $origen = $_GET['origen'] ?? '';
        $resaltarId = (int) ($_GET['resaltar_id'] ?? 0);
        // Cuando "Ver" viene de una fila-grupo (ej. "Gasto — DEPARTAMENTO X — 29 ítem(s)" en
        // Pendientes, ver agruparPendientesPorDependencia()), solo debe cargar los ítems de ESE
        // grupo — no todos los pendientes del origen mezclados con los de otras dependencias.
        $dependenciaFiltro = trim($_GET['dependencia'] ?? '');

        if (!in_array($estado, ['pendiente', 'aprobada', 'archivada', 'enviada'], true) || !isset(self::TABLAS_ORIGEN[$origen])) {
            header('Location: index.php?ruta=peticiones');
            exit;
        }

        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $anioSeleccionadoId = (int) ($_GET['anio_id'] ?? ($aniosActivos[0]['id'] ?? 0));
        $dependenciasPermitidas = $this->obtenerDependenciasPermitidas();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->procesarAccionCeldaTipoDetalle($origen);
            $destino = 'index.php?ruta=peticiones-tipo-detalle&estado=' . urlencode($estado) . '&origen=' . urlencode($origen)
                . '&anio_id=' . $anioSeleccionadoId . '&resaltar_id=' . $resaltarId;
            if ($dependenciaFiltro !== '') {
                $destino .= '&dependencia=' . urlencode($dependenciaFiltro);
            }
            if ($error !== '') {
                $_SESSION['peticiones_flash_error'] = $error;
            }
            header('Location: ' . $destino);
            exit;
        }

        $error = $_SESSION['peticiones_flash_error'] ?? '';
        unset($_SESSION['peticiones_flash_error']);

        $itemsCrudos = $this->obtenerItemsCrudosPorEstado($estado, $origen, $anioSeleccionadoId, $dependenciasPermitidas);

        if ($dependenciaFiltro !== '') {
            $itemsCrudos = array_values(array_filter(
                $itemsCrudos,
                static fn (array $item): bool => ($item['dependencia_origen'] ?? $item['detalle'] ?? null) === $dependenciaFiltro
            ));
        }

        // Deja el botón "Volver" del formulario de edición real apuntando de regreso a este mismo
        // landing (mismo estado/año/filtro de dependencia con el que se entró), no al índice plano
        // del módulo.
        $rutaVolverEditar = 'index.php?ruta=peticiones-tipo-detalle&estado=' . urlencode($estado) . '&origen=' . urlencode($origen)
            . '&anio_id=' . $anioSeleccionadoId . '&resaltar_id=' . $resaltarId;
        if ($dependenciaFiltro !== '') {
            $rutaVolverEditar .= '&dependencia=' . urlencode($dependenciaFiltro);
        }

        $resultado = $this->construirFilasPorOrigen($origen, $itemsCrudos, $anioSeleccionadoId, $estado, $rutaVolverEditar);
        $columnas = $resultado['columnas'];
        $clavesFila = $resultado['claves'];
        $filasCompletas = $resultado['filas'];

        if (($_GET['exportar'] ?? '') === 'xlsx') {
            $filasExportar = array_map(static function (array $fila) use ($clavesFila): array {
                return array_map(static function (string $clave) use ($fila) {
                    $valor = $fila[$clave] ?? '—';

                    return is_float($valor) ? number_format($valor, 2, ',', '.') : $valor;
                }, $clavesFila);
            }, $filasCompletas);

            ExportadorExcel::descargar($origen . '-' . $estado . '-' . date('Y-m-d') . '.xlsx', $columnas, $filasExportar);

            return;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $etiquetasEstado = ['pendiente' => 'Pendientes', 'aprobada' => 'Consolidado', 'archivada' => 'Archivados', 'enviada' => 'Enviadas'];
        $etiquetasOrigen = [
            'arl' => 'ARL', 'monitores' => 'Monitores', 'ops' => 'OPS', 'otros' => 'Petición',
            'necesidad' => 'Perfil de proyectos', 'gasto_principal' => 'Gasto', 'gasto_extension' => 'Extensión',
            'gasto_postgrado' => 'Postgrado', 'gasto_unisalud' => 'Unisalud', 'gasto_sin_excedentes' => 'Convenios',
            'ingreso_extension' => 'Ingreso Extensión', 'ingreso_postgrado' => 'Ingreso Postgrado',
            'ingreso_unisalud' => 'Ingreso Unisalud', 'ingreso_sin_excedentes' => 'Ingreso Convenios',
        ];
        $tituloPagina = ($filasCompletas[0]['tipo'] ?? $etiquetasOrigen[$origen] ?? $origen) . ' — ' . ($etiquetasEstado[$estado] ?? $estado);
        if ($dependenciaFiltro !== '') {
            $tituloPagina .= ' — ' . $dependenciaFiltro;
        }

        $rutaVolver = 'index.php?ruta=peticiones&vista=' . ($estado === 'pendiente' ? 'pendientes' : ($estado === 'aprobada' ? 'consolidado' : ($estado === 'archivada' ? 'archivar' : 'enviadas')));

        // Ancho inicial por columna (se puede arrastrar después): ~8px por carácter del valor más
        // largo (encabezado incluido), entre 90 y 320px — mismo criterio que el prototipo Dev >
        // Tabla, para que el ancho de partida ya muestre el contenido sin truncar.
        $anchosColumna = [];
        foreach ($columnas as $indice => $columna) {
            $clave = $clavesFila[$indice];
            $maxLargo = mb_strlen($columna);
            foreach ($filasCompletas as $filaCompleta) {
                $valorLargo = $filaCompleta[$clave] ?? '';
                if (is_float($valorLargo)) {
                    $valorLargo = number_format($valorLargo, 2, ',', '.');
                }
                $maxLargo = max($maxLargo, mb_strlen((string) $valorLargo));
            }
            $anchosColumna[$indice] = max(90, min(320, $maxLargo * 8 + 40));
        }

        // La tabla de Gasto (ORIGENES_GASTO) tiene 14 columnas — de entrada oculta las que menos
        // se consultan (Línea estratégica/Motor de desarrollo/Cantidad/Costo unitario/Techo
        // presupuestal, ya implícitas en Valor total) para ganar espacio; el usuario las puede
        // volver a mostrar desde "Mostrar u ocultar columnas" igual que cualquier otra.
        $indicesOcultosPorDefecto = [];
        if (in_array($origen, self::ORIGENES_GASTO, true)) {
            $clavesOcultasPorDefecto = ['linea', 'motor', 'cantidad', 'costo_unitario', 'techo'];
            $indicesOcultosPorDefecto = array_values(array_intersect_key(
                array_flip($clavesFila),
                array_flip($clavesOcultasPorDefecto)
            ));
        }

        require __DIR__ . '/../vista/peticiones/tipo-detalle.php';
    }

    /**
     * Columnas + filas para el landing de "Ver" (peticiones-tipo-detalle), a la medida del origen
     * pedido — no el set fijo de columnas de Gasto para todo. Cada familia de origen tiene sus
     * propios campos reales (ARL: niveles de riesgo; Monitores: semestres; OPS: perfil/valor;
     * Otros: concepto/semestres; Necesidad: sus propios campos), consultados con el modelo real de
     * cada uno (obtenerModeloPorOrigen()) — nunca se fuerza un origen a las columnas de otro.
     */
    private function construirFilasPorOrigen(string $origen, array $itemsCrudos, int $anioPresupuestalId, string $estado, string $rutaVolverEditar = 'index.php?ruta=peticiones'): array
    {
        if (in_array($origen, self::ORIGENES_GASTO, true)) {
            return [
                'columnas' => ['Dependencia', 'Sede', 'Línea estratégica', 'Motor de desarrollo', 'Proyecto PDI', 'Objeto/Proyecto (PAA)', 'Actividad', 'Rubro', 'Insumo', 'Cantidad', 'Costo unitario', 'Valor total', 'Meses', 'Techo presupuestal'],
                'claves' => ['dependencia', 'sede', 'linea', 'motor', 'proyecto', 'objeto_proyecto_paa', 'actividad', 'rubro', 'insumo', 'cantidad', 'costo_unitario', 'valor_total', 'meses', 'techo'],
                'filas' => $this->construirFilasDetalleCompleto($itemsCrudos, $anioPresupuestalId, $estado, $rutaVolverEditar),
            ];
        }

        $modelo = $this->obtenerModeloPorOrigen($origen);
        $filas = [];

        foreach ($itemsCrudos as $item) {
            // OPS solo trae los códigos/nombres de sede/línea/motor/proyecto/rubro (columnas
            // reales que se muestran aquí) con obtenerDetallePorId(); obtenerPorId() no los une.
            $metodoObtener = ($origen === 'ops' && method_exists($modelo, 'obtenerDetallePorId')) ? 'obtenerDetallePorId' : 'obtenerPorId';
            $registro = $modelo !== null ? $modelo->$metodoObtener((int) $item['origen_id']) : null;
            if ($registro === null) {
                continue;
            }

            $fila = [
                'origen' => $origen,
                'origen_id' => (int) $item['origen_id'],
                'tipo' => $item['tipo'] ?? $registro['tipo'] ?? '',
                'ruta_ver' => $this->construirRutaVer($origen, (int) $item['origen_id'], $estado),
                'ruta_origen' => $item['ruta_origen'] ?? 'index.php?ruta=peticiones',
                'ruta_editar' => $this->construirRutaEditar($origen, (int) $item['origen_id'], $rutaVolverEditar),
                'puede_editar' => $this->esPropietarioActualDeItem($item) || $this->esSuperAdminRaiz(),
            ];

            if (in_array($origen, ['ingreso_extension', 'ingreso_postgrado', 'ingreso_unisalud', 'ingreso_sin_excedentes'], true)) {
                $fila['dependencia'] = $registro['dependencia'] ?? '—';
                $fila['concepto_adicional'] = $registro['concepto_adicional'] !== '' ? ($registro['concepto_adicional'] ?? '—') : '—';
                $fila['valor_adicional'] = isset($registro['valor_adicional']) ? (float) $registro['valor_adicional'] : null;
                $fila['valor_total'] = isset($registro['valor_total']) ? (float) $registro['valor_total'] : null;
            } elseif ($origen === 'arl') {
                $fila['facultad'] = $registro['facultad'] ?? '—';
                $totalEstudiantes = 0;
                $totalValor = 0.0;
                for ($nivel = 1; $nivel <= 5; $nivel++) {
                    $estudiantes = (int) ($registro['riesgo' . $nivel . '_estudiantes'] ?? 0);
                    $valor = (float) ($registro['riesgo' . $nivel . '_valor'] ?? 0);
                    $fila['riesgo' . $nivel . '_estudiantes'] = $estudiantes;
                    $fila['riesgo' . $nivel . '_valor'] = $valor;
                    $totalEstudiantes += $estudiantes;
                    $totalValor += $valor;
                }
                $fila['total_estudiantes'] = $totalEstudiantes;
                $fila['total_valor'] = $totalValor;
            } elseif ($origen === 'monitores') {
                $fila['dependencia'] = $registro['dependencia'] ?? '—';
                $fila['tipo_monitor'] = $registro['tipo'] ?? '—';
                $fila['monitores_semestre1'] = (int) ($registro['monitores_semestre1'] ?? 0);
                $fila['monitores_semestre2'] = (int) ($registro['monitores_semestre2'] ?? 0);
                $fila['monitores_total'] = $fila['monitores_semestre1'] + $fila['monitores_semestre2'];
            } elseif ($origen === 'ops') {
                $fila['sede'] = trim(($registro['sede_codigo'] ?? '') . ' - ' . ($registro['sede_nombre'] ?? ''), ' -');
                $fila['dependencia'] = $registro['dependencia'] ?? '—';
                $fila['linea'] = trim(($registro['linea_codigo'] ?? '') . ' - ' . ($registro['linea_nombre'] ?? ''), ' -');
                $fila['motor'] = trim(($registro['motor_codigo'] ?? '') . ' - ' . ($registro['motor_nombre'] ?? ''), ' -');
                $fila['proyecto'] = trim(($registro['proyecto_codigo'] ?? '') . ' - ' . ($registro['proyecto_nombre'] ?? ''), ' -');
                $fila['rubro'] = trim(($registro['rubro_codigo'] ?? '') . ' - ' . ($registro['rubro_descripcion'] ?? ''), ' -');
                $fila['perfil'] = $registro['perfil'] ?? '—';
                $fila['valor_unitario'] = isset($registro['valor']) ? (float) $registro['valor'] : null;
                $fila['cantidad'] = (int) ($registro['cantidad'] ?? 0);
                $fila['ops_total'] = (float) ($registro['valor'] ?? 0) * (int) ($registro['cantidad'] ?? 0);
            } elseif ($origen === 'otros') {
                $fila['concepto'] = $registro['concepto'] ?? '—';
                $fila['semestre1'] = (int) ($registro['semestre1'] ?? 0);
                $fila['semestre2'] = (int) ($registro['semestre2'] ?? 0);
                $fila['valor_s1'] = isset($registro['valor_s1']) ? (float) $registro['valor_s1'] : null;
                $fila['valor_s2'] = isset($registro['valor_s2']) ? (float) $registro['valor_s2'] : null;
                $fila['otros_total'] = (float) ($registro['valor_s1'] ?? 0) + (float) ($registro['valor_s2'] ?? 0);
            } elseif ($origen === 'necesidad') {
                $fila['vigencia'] = $registro['vigencia'] !== null ? (int) $registro['vigencia'] : null;
                $fila['nombre_necesidad'] = $registro['nombre_necesidad'] ?? '—';
                $fila['estamento_solicitante_nombre'] = $registro['estamento_solicitante_nombre'] ?? '—';
                $fila['beneficiarios_cantidad'] = $registro['beneficiarios_cantidad'] !== null ? (int) $registro['beneficiarios_cantidad'] : null;
                $fila['sede_nombre'] = $registro['sede_nombre'] ?? '—';
                $fila['dependencia'] = $registro['dependencia'] ?? '—';
                $fila['programa_academico'] = $registro['programa_academico'] ?? '—';
                $fila['linea_inversion_nombre'] = $registro['linea_inversion_nombre'] ?? $registro['linea_inversion'] ?? '—';
                $fila['proyecto_nombre'] = $registro['proyecto_nombre'] ?? '—';
                $fila['valor'] = isset($registro['valor']) ? (float) $registro['valor'] : null;
                $fila['fuente_financiacion'] = $registro['fuente_financiacion'] ?? '—';
            }

            $filas[] = $fila;
        }

        $definiciones = [
            'ingreso' => [
                'columnas' => ['Dependencia', 'Concepto adicional', 'Valor adicional', 'Valor total'],
                'claves' => ['dependencia', 'concepto_adicional', 'valor_adicional', 'valor_total'],
            ],
            'arl' => [
                'columnas' => ['Facultad', 'Riesgo I (est.)', 'Riesgo I (valor)', 'Riesgo II (est.)', 'Riesgo II (valor)', 'Riesgo III (est.)', 'Riesgo III (valor)', 'Riesgo IV (est.)', 'Riesgo IV (valor)', 'Riesgo V (est.)', 'Riesgo V (valor)', 'Total estudiantes', 'Total valor'],
                'claves' => ['facultad', 'riesgo1_estudiantes', 'riesgo1_valor', 'riesgo2_estudiantes', 'riesgo2_valor', 'riesgo3_estudiantes', 'riesgo3_valor', 'riesgo4_estudiantes', 'riesgo4_valor', 'riesgo5_estudiantes', 'riesgo5_valor', 'total_estudiantes', 'total_valor'],
            ],
            'monitores' => [
                'columnas' => ['Dependencia', 'Tipo', 'Semestre I', 'Semestre II', 'Total'],
                'claves' => ['dependencia', 'tipo_monitor', 'monitores_semestre1', 'monitores_semestre2', 'monitores_total'],
            ],
            'ops' => [
                'columnas' => ['Sede', 'Dependencia', 'Línea', 'Motor', 'Proyecto', 'Rubro', 'Perfil', 'Valor unitario', 'Cantidad', 'Total'],
                'claves' => ['sede', 'dependencia', 'linea', 'motor', 'proyecto', 'rubro', 'perfil', 'valor_unitario', 'cantidad', 'ops_total'],
            ],
            'otros' => [
                'columnas' => ['Concepto', 'Semestre I (cant.)', 'Semestre II (cant.)', 'Valor semestre I', 'Valor semestre II', 'Total'],
                'claves' => ['concepto', 'semestre1', 'semestre2', 'valor_s1', 'valor_s2', 'otros_total'],
            ],
            'necesidad' => [
                'columnas' => ['Vigencia', 'Nombre de la necesidad', 'Estamento solicitante', 'Beneficiarios', 'Sede', 'Dependencia', 'Programa académico', 'Línea de inversión', 'Proyecto PDI', 'Valor', 'Fuente de financiación'],
                'claves' => ['vigencia', 'nombre_necesidad', 'estamento_solicitante_nombre', 'beneficiarios_cantidad', 'sede_nombre', 'dependencia', 'programa_academico', 'linea_inversion_nombre', 'proyecto_nombre', 'valor', 'fuente_financiacion'],
            ],
        ];

        $familia = in_array($origen, ['ingreso_extension', 'ingreso_postgrado', 'ingreso_unisalud', 'ingreso_sin_excedentes'], true) ? 'ingreso' : $origen;
        $definicion = $definiciones[$familia] ?? ['columnas' => [], 'claves' => []];

        return [
            'columnas' => $definicion['columnas'],
            'claves' => $definicion['claves'],
            'filas' => $filas,
        ];
    }

    /**
     * Elimina, de verdad, el ítem seleccionado en el landing de "Ver" — solo si quien actúa es su
     * propio dueño (mismo criterio que ya usa Perfil de proyectos: usuario_id de la sesión igual al
     * usuario_id del registro), o el superadministrador. Reutiliza el modelo real de cada origen
     * (Gasto::eliminar(), Necesidad::eliminar(), etc.), nunca reescribe esa lógica. La edición ya no
     * es in-place aquí: "Editar" navega al formulario real del módulo dueño (ver construirRutaEditar()).
     */
    private function procesarAccionCeldaTipoDetalle(string $origen): string
    {
        $id = (int) ($_POST['origen_id'] ?? 0);
        $modelo = $this->obtenerModeloPorOrigen($origen);

        if ($id <= 0 || $modelo === null || !method_exists($modelo, 'obtenerPorId')) {
            return 'Ítem inválido.';
        }

        $registro = $modelo->obtenerPorId($id);

        if ($registro === null) {
            return 'El ítem ya no existe.';
        }

        $esDueno = isset($registro['usuario_id']) && (int) $registro['usuario_id'] === (int) $_SESSION['usuario_id'];
        if (!$esDueno && empty($_SESSION['usuario_super_admin'])) {
            return 'No tienes permiso para modificar este ítem.';
        }

        if (!method_exists($modelo, 'eliminar')) {
            return 'Este origen no admite eliminar desde aquí.';
        }

        $modelo->eliminar($id);

        return '';
    }

    /**
     * Landing aparte (para cualquier dependencia, no solo el superadmin) con lo aceptado en
     * autogestión (Extensión, Postgrado, Unisalud, Convenios) y Perfil de proyectos, dentro
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

    /**
     * Log de auditoría (solo lectura) de una bandeja: todo lo que le pasó a sus ítems desde que
     * llegaron a Peticiones (aprobar/archivar/editar/redireccionar/rechazar/duplicar/eliminar). No
     * incluye el momento "Enviado" (eso ocurre en el módulo de origen, fuera de este controlador).
     */
    /**
     * Historial de auditoría de un solo ítem (no de toda una bandeja): quién hizo qué y cuándo
     * desde que llegó a Peticiones. Se accede desde un botón por ítem en Consolidado/Enviadas,
     * no desde un botón global por pestaña.
     */
    public function historialItem(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $origen = trim($_GET['origen'] ?? '');
        $origenId = (int) ($_GET['origen_id'] ?? 0);
        $volver = trim($_GET['volver'] ?? '') !== '' ? $_GET['volver'] : 'index.php?ruta=peticiones';

        if ($origen === '' || $origenId <= 0 || !$this->puedeVerHistorialItem($origen, $origenId)) {
            header('Location: index.php?ruta=peticiones');
            exit;
        }

        $eventos = $this->modeloHistorial->obtenerPorOrigenYId($origen, $origenId);

        require __DIR__ . '/../vista/peticiones/historial-item.php';
    }

    /**
     * Mismo criterio de visibilidad que filtrarPorDependencia(), pero para un ítem puntual: por
     * dependencia (resuelta en vivo, ya que peticiones_historial solo guarda origen/origen_id) o,
     * para 'otros' (sin dependencia propia), por rol destinatario.
     */
    private function puedeVerHistorialItem(string $origen, int $origenId): bool
    {
        if ($origen === 'otros') {
            $rolDestinatario = $this->obtenerRolDestinatarioOtros($origenId);

            return $rolDestinatario !== null && $rolDestinatario === $this->obtenerRolUsuarioActual();
        }

        $dependenciaOrigen = $this->obtenerDependenciaOrigen($origen, $origenId);

        return $dependenciaOrigen === null || in_array($dependenciaOrigen, $this->obtenerDependenciasPermitidas(), true);
    }

    /**
     * Dependencia real (en vivo) de un ítem, según su tabla de origen. Devuelve null si el
     * registro ya no existe, o si ese tipo de origen no tiene un campo de dependencia propio.
     */
    private function obtenerDependenciaOrigen(string $origen, int $origenId): ?string
    {
        $mapaCampo = [
            'gasto_principal' => [$this->modeloGasto, 'dependencia'],
            'gasto_extension' => [$this->modeloGastoExtension, 'dependencia'],
            'gasto_postgrado' => [$this->modeloGastoPostgrado, 'dependencia'],
            'gasto_unisalud' => [$this->modeloGastoUnisalud, 'dependencia'],
            'gasto_sin_excedentes' => [$this->modeloGastoSinExcedentes, 'dependencia'],
            'ingreso_extension' => [$this->modeloIngresoExtension, 'dependencia'],
            'ingreso_postgrado' => [$this->modeloIngresoPostgrado, 'dependencia'],
            'ingreso_unisalud' => [$this->modeloIngresoUnisalud, 'dependencia'],
            'ingreso_sin_excedentes' => [$this->modeloIngresoSinExcedentes, 'dependencia'],
            'necesidad' => [$this->modeloNecesidad, 'dependencia'],
            'arl' => [$this->modeloSolicitud, 'facultad'],
            'monitores' => [$this->modeloMonitor, 'dependencia'],
            'ops' => [$this->modeloOps, 'dependencia'],
        ];

        if (!isset($mapaCampo[$origen])) {
            return null;
        }

        [$modelo, $campo] = $mapaCampo[$origen];
        $fila = $modelo->obtenerPorId($origenId);

        return $fila[$campo] ?? null;
    }

    private function construirFilasDetalleCompleto(array $aprobados, int $anioPresupuestalId, string $estado = 'aprobada', string $rutaVolverEditar = 'index.php?ruta=peticiones'): array
    {
        // El PAC (línea de "Meses" en la vista de gráfica del landing) agrupa por nombre de mes,
        // no por el número crudo que guarda la tabla — sin esta conversión "1,2,3" nunca calzaría
        // con las etiquetas Ene/Feb/Mar y el panel quedaría siempre en cero.
        $nombresMeses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

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

        // Precargado una sola vez (en vez de un obtenerPorNombre() por fila): con cientos/miles de
        // filas, resolver la dependencia de cada una disparaba esa misma cantidad de consultas.
        $dependenciasPorNombre = [];
        foreach ($this->modeloDependencia->obtenerTodas() as $dependenciaCatalogo) {
            $dependenciasPorNombre[$dependenciaCatalogo['nombre']] = $dependenciaCatalogo;
        }

        $filas = [];

        foreach ($aprobados as $item) {
            $gastoOriginal = null;

            if (in_array($item['origen'], self::ORIGENES_GASTO, true)) {
                $gastoOriginal = $this->obtenerRegistroPorOrigenCacheado($item['origen'], (int) $item['origen_id']);
            }

            // Se usa la dependencia de ORIGEN del gasto (a qué programa/dependencia pertenece el
            // dinero), no la de DESTINO (a quién se le envió) — esta última es solo para saber quién
            // debe verlo, y puede ser una dependencia distinta (ej. la Facultad, cuando el gasto es
            // de un programa hijo suyo sin destinatario propio). Si el registro original ya no
            // existe, se usa el detalle guardado en el archivo aprobado como respaldo.
            $dependenciaNombre = $gastoOriginal['dependencia'] ?? $gastoOriginal['dependencia_destino'] ?? $item['detalle'];

            $dependenciaOrigen = $dependenciaNombre !== null && $dependenciaNombre !== ''
                ? ($dependenciasPorNombre[$dependenciaNombre] ?? null)
                : null;
            $techo = $dependenciaOrigen !== null ? ($presupuestosPorDependenciaId[(int) $dependenciaOrigen['id']]['techo'] ?? null) : null;

            $fila = [
                'origen' => $item['origen'],
                'origen_id' => (int) $item['origen_id'],
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
                'ruta_ver' => $this->construirRutaVer($item['origen'], (int) $item['origen_id'], $estado),
                'ruta_origen' => $item['ruta_origen'] ?? 'index.php?ruta=peticiones',
                'ruta_editar' => $this->construirRutaEditar($item['origen'], (int) $item['origen_id'], $rutaVolverEditar),
                'puede_editar' => $this->esPropietarioActualDeItem($item) || $this->esSuperAdminRaiz(),
            ];

            if ($gastoOriginal !== null) {
                $fila['sede'] = $sedesPorId[(int) ($gastoOriginal['sede_id'] ?? 0)] ?? '—';
                $fila['linea'] = $lineasPorId[(int) ($gastoOriginal['linea_id'] ?? 0)] ?? '—';
                $fila['motor'] = $motoresPorId[(int) ($gastoOriginal['motor_id'] ?? 0)] ?? '—';
                $fila['proyecto'] = $proyectosPorId[(int) ($gastoOriginal['proyecto_id'] ?? 0)] ?? '—';
                $fila['objeto_proyecto_paa'] = $gastoOriginal['objeto_proyecto_paa'] ?? '—';
                $fila['actividad'] = $gastoOriginal['actividad'] ?? '—';
                $fila['insumo'] = $gastoOriginal['insumo'] ?? '—';
                $fila['cantidad'] = isset($gastoOriginal['cantidad']) ? (float) $gastoOriginal['cantidad'] : $fila['cantidad'];
                $fila['costo_unitario'] = isset($gastoOriginal['costo_unitario']) ? (float) $gastoOriginal['costo_unitario'] : null;
                $fila['meses'] = $gastoOriginal['meses'] !== ''
                    ? implode(', ', array_map(static fn ($mes) => $nombresMeses[(int) $mes] ?? $mes, explode(',', $gastoOriginal['meses'])))
                    : '—';

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
     * Usuario logueado, cacheado durante la petición — $_SESSION['usuario_id'] no cambia a mitad
     * de un mismo request, así que no hay razón para volver a consultarlo fila por fila (esto se
     * llama una vez por cada ítem al calcular 'puede_editar' en listados de cientos de filas).
     */
    private function obtenerUsuarioActualCacheado(): ?array
    {
        if (!$this->usuarioActualCargado) {
            $this->usuarioActualCache = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
            $this->usuarioActualCargado = true;
        }

        return $this->usuarioActualCache;
    }

    /**
     * Determina si la dependencia del usuario actual es la dependencia raíz del superadministrador.
     * Cacheado por la misma razón que obtenerUsuarioActualCacheado(): el resultado no cambia a
     * mitad de un request.
     */
    private function esSuperAdminRaiz(): bool
    {
        if ($this->cacheEsSuperAdminRaiz !== null) {
            return $this->cacheEsSuperAdminRaiz;
        }

        $usuarioActual = $this->obtenerUsuarioActualCacheado();
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaUsuarioId === null) {
            return $this->cacheEsSuperAdminRaiz = false;
        }

        $dependenciaUsuario = $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId);

        return $this->cacheEsSuperAdminRaiz = ($dependenciaUsuario !== null && !empty($dependenciaUsuario['es_raiz_superadmin']));
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

        $filaJerarquia = function (string $origen, int $origenId, string $tipo, string $detalle, ?string $cantidad, ?float $valor, string $rutaOrigen) use ($acciones): array {
            $clave = $origen . ':' . $origenId;
            $accion = $acciones[$clave] ?? null;
            $estadoItem = $accion ?? 'pendiente';

            return [
                'origen' => $origen,
                'origen_id' => $origenId,
                'tipo' => $tipo,
                'detalle' => $detalle,
                'cantidad' => $cantidad,
                'valor' => $valor,
                'ruta_ver' => $this->construirRutaVer($origen, $origenId, $estadoItem),
                'ruta_origen' => $rutaOrigen,
                'estado_item' => $estadoItem,
            ];
        };

        foreach ($this->modeloSolicitud->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalPracticantes = (int) $fila['riesgo1_estudiantes'] + (int) $fila['riesgo2_estudiantes']
                + (int) $fila['riesgo3_estudiantes'] + (int) $fila['riesgo4_estudiantes'] + (int) $fila['riesgo5_estudiantes'];
            $totalValor = (float) $fila['riesgo1_valor'] + (float) $fila['riesgo2_valor']
                + (float) $fila['riesgo3_valor'] + (float) $fila['riesgo4_valor'] + (float) $fila['riesgo5_valor'];
            $items[] = ['origen' => 'arl', 'facultad' => $fila['facultad']] + $filaJerarquia('arl', (int) $fila['id'], 'ARL', $fila['facultad'], $totalPracticantes . ' practicantes', $totalValor, 'index.php?ruta=solicitudes&tab=arl');
        }

        foreach ($this->modeloMonitor->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalMonitores = (int) $fila['monitores_semestre1'] + (int) $fila['monitores_semestre2'];
            $items[] = ['origen' => 'monitores', 'facultad' => $fila['dependencia']] + $filaJerarquia('monitores', (int) $fila['id'], 'Monitores', $fila['dependencia'], $totalMonitores . ' monitores', null, 'index.php?ruta=solicitudes&tab=monitores');
        }

        foreach ($this->modeloOps->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalOps = (float) $fila['valor'] * (int) $fila['cantidad'];
            $items[] = ['origen' => 'ops', 'facultad' => $fila['dependencia']] + $filaJerarquia('ops', (int) $fila['id'], 'OPS', $fila['dependencia'], $fila['cantidad'] . ' und.', $totalOps, 'index.php?ruta=solicitudes&tab=ops');
        }

        foreach ($this->modeloPeticion->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalValor = (float) $fila['valor_s1'] + (float) $fila['valor_s2'];
            $items[] = ['origen' => 'otros', 'facultad' => null] + $filaJerarquia('otros', (int) $fila['id'], 'Petición', $fila['concepto'], null, $totalValor, 'index.php?ruta=solicitudes&tab=otros');
        }

        foreach ($this->modeloNecesidad->obtenerEnviadas() as $fila) {
            $items[] = ['origen' => 'necesidad', 'facultad' => $fila['dependencia']] + $filaJerarquia('necesidad', (int) $fila['id'], 'Perfil de proyectos', $fila['dependencia'], null, (float) $fila['valor'], 'index.php?ruta=perfil-proyectos');
        }

        $mapaGastos = [
            ['modelo' => $this->modeloGasto, 'origen' => 'gasto_principal', 'tipo' => 'Gasto', 'ruta' => 'index.php?ruta=gastos', 'metodo' => 'obtenerEnviadosPorAnio'],
            ['modelo' => $this->modeloGastoExtension, 'origen' => 'gasto_extension', 'tipo' => 'Extensión', 'ruta' => 'index.php?ruta=extension', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoPostgrado, 'origen' => 'gasto_postgrado', 'tipo' => 'Postgrado', 'ruta' => 'index.php?ruta=postgrado', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoUnisalud, 'origen' => 'gasto_unisalud', 'tipo' => 'Unisalud', 'ruta' => 'index.php?ruta=unisalud', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoSinExcedentes, 'origen' => 'gasto_sin_excedentes', 'tipo' => 'Convenios', 'ruta' => 'index.php?ruta=sin-excedentes', 'metodo' => 'obtenerPorAnio'],
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
                $items[] = ['origen' => $fuente['origen'], 'facultad' => $fila['dependencia']] + $filaJerarquia($fuente['origen'], (int) $fila['id'], $tipo, $fila['dependencia'], $fila['cantidad'] . ' und.', (float) $fila['valor_total'], $fuente['ruta']);
            }
        }

        $mapaIngresos = [
            ['modelo' => $this->modeloIngresoExtension, 'origen' => 'ingreso_extension', 'tipo' => 'Ingreso Extensión', 'ruta' => 'index.php?ruta=extension'],
            ['modelo' => $this->modeloIngresoPostgrado, 'origen' => 'ingreso_postgrado', 'tipo' => 'Ingreso Postgrado', 'ruta' => 'index.php?ruta=postgrado'],
            ['modelo' => $this->modeloIngresoUnisalud, 'origen' => 'ingreso_unisalud', 'tipo' => 'Ingreso Unisalud', 'ruta' => 'index.php?ruta=unisalud'],
            ['modelo' => $this->modeloIngresoSinExcedentes, 'origen' => 'ingreso_sin_excedentes', 'tipo' => 'Ingreso Convenios', 'ruta' => 'index.php?ruta=sin-excedentes'],
        ];

        foreach ($mapaIngresos as $fuente) {
            foreach ($fuente['modelo']->obtenerPorAnio($anioPresupuestalId) as $fila) {
                if (($fila['estado'] ?? 'borrador') !== 'enviado') {
                    continue;
                }

                $tipo = ($fuente['origen'] === 'ingreso_extension' && !empty($fila['autogestion_nombre'])) ? ucfirst($fila['autogestion_nombre']) . ' (ingreso)' : $fuente['tipo'];
                $items[] = ['origen' => $fuente['origen'], 'facultad' => $fila['dependencia']] + $filaJerarquia($fuente['origen'], (int) $fila['id'], $tipo, $fila['dependencia'], null, (float) $fila['valor_total'], $fuente['ruta']);
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

            // Se usa la dependencia de ORIGEN del gasto (ver construirFilasDetalleCompleto()), no la
            // de destino (a quién se envió) — se prefiere el registro original si todavía existe
            // sobre lo guardado en el archivo aprobado, que es una foto fija y puede desactualizarse.
            $dependenciaNombre = $gastoOriginal['dependencia'] ?? $gastoOriginal['dependencia_destino'] ?? $item['detalle'];

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
                'ruta_ver' => $this->construirRutaVer($item['origen'], (int) $item['origen_id'], 'aprobada'),
            ];
        }

        return $filas;
    }

    /**
     * Determina si el usuario actual puede editar un ítem consolidado en este momento: o bien su
     * propia dependencia coincide exactamente con la dependencia actual del ítem (no la foto fija
     * guardada al aprobar), o bien su dependencia es "padre" (ancestra, a cualquier nivel) de la
     * dependencia actual del ítem — todo padre puede editar lo que ya consolidaron sus hijas.
     *
     * Excepción: la Contribución a posgrado (5%) solo la puede editar el superadmin, o el Avalador/
     * Gestor que está exactamente en DEPARTAMENTO DE POSTGRADOS (la dependencia con tipo
     * "Departamento", no cualquiera de sus programas) — ni el resto de dependencias padres (esta
     * regla general de jerarquía no aplica aquí) ni sus hijas (los programas académicos/gestores
     * hijos tampoco), igual que en el propio módulo de Postgrado.
     */
    private function esPropietarioActualDeItem(array $item): bool
    {
        if ($item['origen'] === 'otros') {
            return true;
        }

        $dependenciaActual = $this->resolverDependenciaActualItem($item);

        if ($dependenciaActual === null || $dependenciaActual === '') {
            return false;
        }

        $usuarioActual = $this->obtenerUsuarioActualCacheado();

        if ($usuarioActual === null) {
            return false;
        }

        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($this->esContribucionPostgrado($item)) {
            if ((int) ($usuarioActual['es_super_admin'] ?? 0) === 1) {
                return true;
            }

            if ($dependenciaActual !== 'DEPARTAMENTO DE POSTGRADOS') {
                return false;
            }

            $dependenciaUsuario = $dependenciaUsuarioId !== null ? $this->obtenerDependenciaUsuarioActualCacheada($dependenciaUsuarioId) : null;

            return $dependenciaUsuario !== null
                && $dependenciaUsuario['nombre'] === 'DEPARTAMENTO DE POSTGRADOS'
                && ($dependenciaUsuario['tipo'] ?? null) === 'Departamento';
        }

        if ($dependenciaUsuarioId === null) {
            return false;
        }

        $dependenciaUsuario = $this->obtenerDependenciaUsuarioActualCacheada($dependenciaUsuarioId);

        if ($dependenciaUsuario === null) {
            return false;
        }

        if ($dependenciaUsuario['nombre'] === $dependenciaActual) {
            return true;
        }

        $nombresDescendientes = $this->obtenerNombresDescendientesUsuarioActualCacheados($dependenciaUsuarioId);

        return in_array($dependenciaActual, $nombresDescendientes, true);
    }

    /**
     * Dependencia del usuario actual, cacheada durante la petición — misma razón que
     * obtenerUsuarioActualCacheado(): el id no cambia entre filas.
     */
    private function obtenerDependenciaUsuarioActualCacheada(int $dependenciaUsuarioId): ?array
    {
        if (!$this->dependenciaUsuarioActualCargada) {
            $this->dependenciaUsuarioActualCache = $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId);
            $this->dependenciaUsuarioActualCargada = true;
        }

        return $this->dependenciaUsuarioActualCache;
    }

    /**
     * Nombres de las dependencias descendientes de la del usuario actual, cacheados durante la
     * petición. obtenerDescendientesPlano() recorre TODO el árbol de dependencias (con su propia
     * consulta completa a la tabla) — sin este cache, un listado de cientos/miles de filas volvía
     * a recorrer el árbol entero una vez POR FILA, siendo con diferencia el costo más alto de
     * generar el landing de Peticiones.
     */
    private function obtenerNombresDescendientesUsuarioActualCacheados(int $dependenciaUsuarioId): array
    {
        if ($this->nombresDescendientesUsuarioActualCache === null) {
            $this->nombresDescendientesUsuarioActualCache = array_column(
                $this->modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId),
                'nombre'
            );
        }

        return $this->nombresDescendientesUsuarioActualCache;
    }

    /**
     * Registro crudo de un ítem por origen/id, cacheado durante la petición (misma razón que
     * obtenerUsuarioActualCacheado()): el mismo ítem se vuelve a pedir en varios puntos
     * (construirFilasDetalleCompleto, esPropietarioActualDeItem, esContribucionPostgrado) al
     * listar cientos/miles de filas en el landing de Peticiones — sin este cache, cada fila
     * disparaba varias consultas repetidas por el mismo id.
     */
    private function obtenerRegistroPorOrigenCacheado(string $origen, int $origenId): ?array
    {
        $clave = $origen . ':' . $origenId;

        if (array_key_exists($clave, $this->cacheRegistroPorClave)) {
            return $this->cacheRegistroPorClave[$clave];
        }

        $modelosPorOrigen = [
            'arl' => $this->modeloSolicitud,
            'monitores' => $this->modeloMonitor,
            'ops' => $this->modeloOps,
            'necesidad' => $this->modeloNecesidad,
            'gasto_principal' => $this->modeloGasto,
            'gasto_extension' => $this->modeloGastoExtension,
            'gasto_postgrado' => $this->modeloGastoPostgrado,
            'gasto_unisalud' => $this->modeloGastoUnisalud,
            'gasto_sin_excedentes' => $this->modeloGastoSinExcedentes,
            'ingreso_extension' => $this->modeloIngresoExtension,
            'ingreso_postgrado' => $this->modeloIngresoPostgrado,
            'ingreso_unisalud' => $this->modeloIngresoUnisalud,
            'ingreso_sin_excedentes' => $this->modeloIngresoSinExcedentes,
        ];

        $modelo = $modelosPorOrigen[$origen] ?? null;

        return $this->cacheRegistroPorClave[$clave] = ($modelo !== null ? $modelo->obtenerPorId($origenId) : null);
    }

    private function esContribucionPostgrado(array $item): bool
    {
        if ($item['origen'] !== 'gasto_postgrado') {
            return false;
        }

        $registro = $this->obtenerRegistroPorOrigenCacheado('gasto_postgrado', (int) $item['origen_id']);

        return $registro !== null && ($registro['tipo_automatico'] ?? null) === 'contrib_postgrado';
    }

    private function resolverDependenciaActualItem(array $item): ?string
    {
        $camposDependenciaPorOrigen = [
            'arl' => 'facultad',
            'monitores' => 'dependencia',
            'ops' => 'dependencia',
            'necesidad' => 'dependencia_destino',
            'gasto_principal' => 'dependencia_destino',
            'gasto_extension' => 'dependencia_destino',
            'gasto_postgrado' => 'dependencia_destino',
            'gasto_unisalud' => 'dependencia_destino',
            'gasto_sin_excedentes' => 'dependencia_destino',
            'ingreso_extension' => 'dependencia_destino',
            'ingreso_postgrado' => 'dependencia_destino',
            'ingreso_unisalud' => 'dependencia_destino',
            'ingreso_sin_excedentes' => 'dependencia_destino',
        ];

        if (!isset($camposDependenciaPorOrigen[$item['origen']])) {
            return $item['detalle'] ?? null;
        }

        $campo = $camposDependenciaPorOrigen[$item['origen']];
        $registro = $this->obtenerRegistroPorOrigenCacheado($item['origen'], (int) $item['origen_id']);

        if ($registro === null || empty($registro[$campo])) {
            return $item['detalle'] ?? null;
        }

        return $registro[$campo];
    }

    /**
     * Redirecciona una selección arbitraria de ítems consolidados (uno o varios tipos a la vez) a
     * otra dependencia/rol. No exige propiedad actual del ítem — igual que el comportamiento
     * original por tipo, cualquiera con acceso a Consolidado puede redireccionar.
     */
    private function redireccionarConsolidado(): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];
        $dependenciaNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem para redireccionar.', ''];
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

        if (count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        $pares = [];
        foreach ($origenes as $indice => $origen) {
            $pares[] = ['origen' => trim((string) $origen), 'origen_id' => (int) ($origenIds[$indice] ?? 0)];
        }

        // Se guarda quién es exactamente el destinatario (único con ese rol, o desambiguado
        // arriba) — si no, cualquiera con ese rol en la dependencia vería el ítem redireccionado
        // en Pendientes, no solo la persona elegida.
        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;

        $cantidadItems = $this->modeloArchivada->redireccionarItems($pares, $dependenciaNombre, 'aprobada', $rolDestinatarioId, $usuarioDestinatarioResuelto);

        if ($cantidadItems === 0) {
            return ['No hay ítems seleccionados para redireccionar.', ''];
        }

        foreach ($pares as $par) {
            $this->modeloHistorial->registrar($par['origen'], $par['origen_id'], 'redireccionada', 'Redireccionado a ' . $dependenciaNombre);
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);
        $asunto = 'Petición consolidada redireccionada';
        $cuerpo = 'Se te redireccionaron ' . $cantidadItems . ' ítem(s) consolidado(s) para tu gestión en "' . $dependenciaNombre . '".';

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear($remitenteId, (int) $destinatario['id'], $asunto, $cuerpo);
        }

        return ['', 'Se redireccionaron ' . $cantidadItems . ' ítem(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaNombre . '").'];
    }

    /**
     * Archiva una selección arbitraria de ítems consolidados: no hace falta reenviar tipo/detalle/
     * cantidad/valor porque ya están guardados en la fila aprobada — solo se re-archiva la misma
     * fila cambiando accion a 'archivada' (mismo upsert por origen+origen_id que ya usa `archivar()`).
     * Inverso de `consolidarArchivadoGrupo()`.
     */
    private function archivarConsolidadoGrupo(): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem para archivar.', ''];
        }

        $aprobadosPorClave = $this->obtenerPorAccionYClave('aprobada');
        $archivados = 0;

        foreach ($origenes as $indice => $origen) {
            $origen = trim((string) $origen);
            $origenId = (int) ($origenIds[$indice] ?? 0);
            $clave = $origen . ':' . $origenId;

            if (!isset($aprobadosPorClave[$clave])) {
                continue;
            }

            $item = $aprobadosPorClave[$clave];

            $this->modeloArchivada->archivar([
                'origen' => $item['origen'],
                'origen_id' => (int) $item['origen_id'],
                'accion' => 'archivada',
                'tipo' => $item['tipo'],
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $item['ruta_ver'],
                'ruta_origen' => $item['ruta_origen'],
            ]);

            $this->modeloHistorial->registrar($item['origen'], (int) $item['origen_id'], 'archivada', 'Archivado desde Consolidado');

            $archivados++;
        }

        if ($archivados === 0) {
            return ['No se pudo archivar ningún ítem.', ''];
        }

        return ['', 'Se archivaron ' . $archivados . ' ítem(s).'];
    }

    /**
     * Orígenes cuya tabla tiene columnas dependencia_destino/rol_destinatario_id/usuario_destinatario_id
     * homogéneas (mismo patrón que resolverDependenciaActualItem()) — son los que
     * reasignarDestinatarioAUsuarioActual() sabe reasignar al desconsolidar. "arl" usa "facultad" en
     * vez de "dependencia_destino" y se maneja aparte; "monitores"/"ops"/"otros" no comparten este
     * patrón de destinatario y se dejan sin reasignar (solo se desconsolidan, sin cambiar a quién
     * quedan visibles).
     */
    private const ORIGENES_CON_DEPENDENCIA_DESTINO = [
        'gasto_principal', 'gasto_extension', 'gasto_postgrado', 'gasto_unisalud', 'gasto_sin_excedentes',
        'ingreso_extension', 'ingreso_postgrado', 'ingreso_unisalud', 'ingreso_sin_excedentes', 'necesidad',
    ];

    /**
     * Desconsolida una selección arbitraria de ítems ya aprobados (Consolidado por tipo): elimina
     * su fila de peticiones_archivadas, lo que los devuelve a "no procesado" — vuelven a aparecer
     * en Pendientes, ahí agrupados por dependencia de origen (agruparPendientesPorDependencia())
     * en vez de fusionados por tipo. Además, quedan asignados al usuario que desconsolida (no a
     * quien los recibió originalmente): quien deshace la consolidación es quien vuelve a
     * gestionarlos en su propia bandeja de Pendientes.
     */
    private function desconsolidarGrupo(): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem para desconsolidar.', ''];
        }

        $aprobadosPorClave = $this->obtenerPorAccionYClave('aprobada');
        $desconsolidados = 0;

        foreach ($origenes as $indice => $origen) {
            $origen = trim((string) $origen);
            $origenId = (int) ($origenIds[$indice] ?? 0);
            $clave = $origen . ':' . $origenId;

            if (!isset($aprobadosPorClave[$clave])) {
                continue;
            }

            $this->modeloArchivada->eliminarPorOrigen($origen, $origenId);
            $this->reasignarDestinatarioAUsuarioActual($origen, $origenId);
            $this->modeloHistorial->registrar($origen, $origenId, 'desconsolidada', 'Desconsolidado desde Consolidado, vuelve a Pendientes de quien lo desconsolidó');

            $desconsolidados++;
        }

        if ($desconsolidados === 0) {
            return ['No se pudo desconsolidar ningún ítem.', ''];
        }

        return ['', 'Se desconsolidaron ' . $desconsolidados . ' ítem(s). Vuelven a tu bandeja de Pendientes.'];
    }

    /**
     * Reasigna el destinatario (dependencia/rol/usuario) de un ítem al usuario que está
     * desconsolidando, para que aparezca en SU Pendientes en vez de quedar visible solo para quien
     * lo recibió originalmente. Es un no-op para orígenes que no comparten el patrón homogéneo de
     * destinatario (ver ORIGENES_CON_DEPENDENCIA_DESTINO).
     */
    private function reasignarDestinatarioAUsuarioActual(string $origen, int $origenId): void
    {
        if ($origen !== 'arl' && !in_array($origen, self::ORIGENES_CON_DEPENDENCIA_DESTINO, true)) {
            return;
        }

        if (!isset(self::TABLAS_ORIGEN[$origen])) {
            return;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        if ($usuarioActual === null || empty($usuarioActual['dependencia_id']) || empty($usuarioActual['rol_id'])) {
            return;
        }

        $dependenciaUsuario = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);

        if ($dependenciaUsuario === null) {
            return;
        }

        $columnaDependencia = $origen === 'arl' ? 'facultad' : 'dependencia_destino';
        $tabla = self::TABLAS_ORIGEN[$origen];

        $consulta = $this->db->prepare(
            "UPDATE {$tabla} SET {$columnaDependencia} = :dependencia, rol_destinatario_id = :rol, usuario_destinatario_id = :usuario WHERE id = :id"
        );
        $consulta->execute([
            'dependencia' => $dependenciaUsuario['nombre'],
            'rol' => (int) $usuarioActual['rol_id'],
            'usuario' => (int) $usuarioActual['id'],
            'id' => $origenId,
        ]);
    }

    /**
     * Consolida una selección arbitraria de ítems archivados: no hace falta reenviar tipo/detalle/
     * cantidad/valor porque ya están guardados en la fila archivada — solo se re-archiva la misma
     * fila cambiando accion a 'aprobada' (mismo upsert por origen+origen_id que ya usa `archivar()`).
     */
    private function consolidarArchivadoGrupo(): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem para consolidar.', ''];
        }

        $archivadosPorClave = $this->obtenerPorAccionYClave('archivada');
        $consolidados = 0;

        foreach ($origenes as $indice => $origen) {
            $origen = trim((string) $origen);
            $origenId = (int) ($origenIds[$indice] ?? 0);
            $clave = $origen . ':' . $origenId;

            if (!isset($archivadosPorClave[$clave])) {
                continue;
            }

            $item = $archivadosPorClave[$clave];

            $this->modeloArchivada->archivar([
                'origen' => $item['origen'],
                'origen_id' => (int) $item['origen_id'],
                'accion' => 'aprobada',
                'tipo' => $item['tipo'],
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $item['ruta_ver'],
                'ruta_origen' => $item['ruta_origen'],
            ]);

            $this->modeloHistorial->registrar($item['origen'], (int) $item['origen_id'], 'aprobada', 'Consolidado desde Archivo');

            $consolidados++;
        }

        if ($consolidados === 0) {
            return ['No se pudo consolidar ningún ítem.', ''];
        }

        return ['', 'Se consolidaron ' . $consolidados . ' ítem(s).'];
    }

    /**
     * Igual que `duplicarConsolidadoGrupo()`, pero para ítems archivados: la copia se archiva de
     * nuevo con accion='archivada' (se queda en Archivo, no salta a Consolidado).
     */
    private function duplicarArchivadoGrupo(): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem para duplicar.', ''];
        }

        $archivadosPorClave = $this->obtenerPorAccionYClave('archivada');
        $itemsValidados = [];

        foreach ($origenes as $indice => $origen) {
            $origen = trim((string) $origen);
            $origenId = (int) ($origenIds[$indice] ?? 0);
            $clave = $origen . ':' . $origenId;

            if (isset($archivadosPorClave[$clave])) {
                $itemsValidados[] = $archivadosPorClave[$clave];
            }
        }

        $duplicados = 0;

        foreach ($itemsValidados as $item) {
            $nuevoId = $this->duplicarRegistroOrigen($item['origen'], (int) $item['origen_id']);

            if ($nuevoId === null) {
                continue;
            }

            $this->modeloArchivada->archivar([
                'origen' => $item['origen'],
                'origen_id' => $nuevoId,
                'accion' => 'archivada',
                'tipo' => $item['tipo'],
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $this->construirRutaVer($item['origen'], $nuevoId),
                'ruta_origen' => $this->construirRutaOrigen($item['origen']),
            ]);

            $this->modeloHistorial->registrar($item['origen'], (int) $item['origen_id'], 'duplicada', 'Se duplicó desde Archivo, nuevo id ' . $nuevoId);

            $duplicados++;
        }

        if ($duplicados === 0) {
            return ['No se pudo duplicar ningún ítem.', ''];
        }

        return ['', 'Se duplicaron ' . $duplicados . ' ítem(s).'];
    }

    /**
     * Igual que `redireccionarConsolidado()`, pero para ítems archivados: al enviarlos pasan a
     * 'redireccionada', lo que ya los "desarchiva" (dejan de tener accion='archivada').
     */
    private function enviarArchivadoGrupo(): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];
        $dependenciaNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem para enviar.', ''];
        }

        if ($dependenciaNombre === '') {
            return ['Selecciona la dependencia a la que se enviará.', ''];
        }

        $rol = $rolDestinatarioId > 0 ? $this->modeloRol->obtenerPorId($rolDestinatarioId) : null;

        if ($rol === null) {
            return ['Selecciona el rol al que se enviará.', ''];
        }

        $dependencia = $this->modeloDependencia->obtenerPorNombre($dependenciaNombre);
        $destinatarios = $dependencia !== null
            ? $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependencia['id'], $rolDestinatarioId)
            : [];

        if (empty($destinatarios)) {
            return ['No se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '" para notificar.', ''];
        }

        if (count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        $pares = [];
        foreach ($origenes as $indice => $origen) {
            $pares[] = ['origen' => trim((string) $origen), 'origen_id' => (int) ($origenIds[$indice] ?? 0)];
        }

        // Se guarda quién es exactamente el destinatario (único con ese rol, o desambiguado
        // arriba) — si no, cualquiera con ese rol en la dependencia vería el ítem en Pendientes,
        // no solo la persona elegida.
        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;

        $cantidadItems = $this->modeloArchivada->redireccionarItems($pares, $dependenciaNombre, 'archivada', $rolDestinatarioId, $usuarioDestinatarioResuelto);

        if ($cantidadItems === 0) {
            return ['No hay ítems seleccionados para enviar.', ''];
        }

        foreach ($pares as $par) {
            $this->modeloHistorial->registrar($par['origen'], $par['origen_id'], 'redireccionada', 'Enviado desde Archivo a ' . $dependenciaNombre);
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);
        $asunto = 'Petición archivada enviada';
        $cuerpo = 'Se te enviaron ' . $cantidadItems . ' ítem(s) archivado(s) para tu gestión en "' . $dependenciaNombre . '".';

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear($remitenteId, (int) $destinatario['id'], $asunto, $cuerpo);
        }

        return ['', 'Se enviaron ' . $cantidadItems . ' ítem(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaNombre . '").'];
    }

    /**
     * Lee el snapshot (tipo/detalle/cantidad/valor/ruta_ver) que el cliente envió por cada ítem
     * seleccionado en Enviadas — a diferencia de Consolidado/Archivo, estos ítems pueden no tener
     * todavía ninguna fila en `peticiones_archivadas`, así que el controlador no tiene de dónde
     * recuperar esos datos por su cuenta; el navegador ya los tiene (los mismos que se renderizaron
     * en la tabla), y viajan como arrays paralelos a item_origen[]/item_origen_id[].
     */
    private function leerSnapshotEnviados(): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];
        $tipos = $_POST['item_tipo'] ?? [];
        $detalles = $_POST['item_detalle'] ?? [];
        $cantidades = $_POST['item_cantidad'] ?? [];
        $valores = $_POST['item_valor'] ?? [];
        $rutasVer = $_POST['item_ruta_ver'] ?? [];
        $accionesActuales = $_POST['item_accion_actual'] ?? [];

        $items = [];
        foreach ($origenes as $indice => $origen) {
            $origen = trim((string) $origen);

            if (!isset(self::TABLAS_ORIGEN[$origen])) {
                continue;
            }

            $items[] = [
                'origen' => $origen,
                'origen_id' => (int) ($origenIds[$indice] ?? 0),
                'tipo' => (string) ($tipos[$indice] ?? ''),
                'detalle' => (string) ($detalles[$indice] ?? ''),
                'cantidad' => ($cantidades[$indice] ?? '') !== '' ? (string) $cantidades[$indice] : null,
                'valor' => ($valores[$indice] ?? '') !== '' ? (float) $valores[$indice] : null,
                'ruta_ver' => (string) ($rutasVer[$indice] ?? 'index.php?ruta=peticiones'),
                'accion_actual' => ($accionesActuales[$indice] ?? '') !== '' ? (string) $accionesActuales[$indice] : null,
            ];
        }

        return $items;
    }

    /**
     * Consolida una selección arbitraria de ítems de Enviadas, sin importar su estado actual
     * (pendiente/archivado/redireccionado): upsert directo a accion='aprobada' vía `archivar()`.
     */
    private function consolidarEnviadoGrupo(): array
    {
        $items = $this->leerSnapshotEnviados();

        if (empty($items)) {
            return ['No seleccionaste ningún ítem para consolidar.', ''];
        }

        foreach ($items as $item) {
            $this->modeloArchivada->archivar([
                'origen' => $item['origen'],
                'origen_id' => $item['origen_id'],
                'accion' => 'aprobada',
                'tipo' => $item['tipo'],
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $item['ruta_ver'],
                'ruta_origen' => $this->construirRutaOrigen($item['origen']),
            ]);

            $this->modeloHistorial->registrar($item['origen'], $item['origen_id'], 'aprobada', 'Consolidado desde Enviadas');
        }

        return ['', 'Se consolidaron ' . count($items) . ' ítem(s).'];
    }

    /**
     * Igual que `duplicarConsolidadoGrupo()`/`duplicarArchivadoGrupo()`, pero para ítems de
     * Enviadas: la copia queda en el mismo estado que tenía el original (o sin fila propia, si el
     * original todavía no tenía ninguna).
     */
    private function duplicarEnviadoGrupo(): array
    {
        $items = $this->leerSnapshotEnviados();

        if (empty($items)) {
            return ['No seleccionaste ningún ítem para duplicar.', ''];
        }

        $duplicados = 0;

        foreach ($items as $item) {
            $nuevoId = $this->duplicarRegistroOrigen($item['origen'], $item['origen_id']);

            if ($nuevoId === null) {
                continue;
            }

            if ($item['accion_actual'] !== null) {
                $this->modeloArchivada->archivar([
                    'origen' => $item['origen'],
                    'origen_id' => $nuevoId,
                    'accion' => $item['accion_actual'],
                    'tipo' => $item['tipo'],
                    'detalle' => $item['detalle'],
                    'cantidad' => $item['cantidad'],
                    'valor' => $item['valor'],
                    'ruta_ver' => $this->construirRutaVer($item['origen'], $nuevoId),
                    'ruta_origen' => $this->construirRutaOrigen($item['origen']),
                ]);
            }

            $this->modeloHistorial->registrar($item['origen'], $item['origen_id'], 'duplicada', 'Se duplicó desde Enviadas, nuevo id ' . $nuevoId);

            $duplicados++;
        }

        if ($duplicados === 0) {
            return ['No se pudo duplicar ningún ítem.', ''];
        }

        return ['', 'Se duplicaron ' . $duplicados . ' ítem(s).'];
    }

    /**
     * Igual que `redireccionarConsolidado()`/`enviarArchivadoGrupo()`, pero para ítems de Enviadas:
     * usa `redireccionarDirecto()` (upsert) en vez de `redireccionarItems()` (UPDATE) porque estos
     * ítems pueden no tener todavía ninguna fila en `peticiones_archivadas`.
     */
    private function enviarEnviadoGrupo(): array
    {
        $items = $this->leerSnapshotEnviados();
        $dependenciaNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if (empty($items)) {
            return ['No seleccionaste ningún ítem para enviar.', ''];
        }

        if ($dependenciaNombre === '') {
            return ['Selecciona la dependencia a la que se enviará.', ''];
        }

        $rol = $rolDestinatarioId > 0 ? $this->modeloRol->obtenerPorId($rolDestinatarioId) : null;

        if ($rol === null) {
            return ['Selecciona el rol al que se enviará.', ''];
        }

        $dependencia = $this->modeloDependencia->obtenerPorNombre($dependenciaNombre);
        $destinatarios = $dependencia !== null
            ? $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependencia['id'], $rolDestinatarioId)
            : [];

        if (empty($destinatarios)) {
            return ['No se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '" para notificar.', ''];
        }

        if (count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        // Se guarda quién es exactamente el destinatario (único con ese rol, o desambiguado
        // arriba) — si no, cualquiera con ese rol en la dependencia vería el ítem en Pendientes,
        // no solo la persona elegida.
        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;

        foreach ($items as $item) {
            $this->modeloArchivada->redireccionarDirecto([
                'origen' => $item['origen'],
                'origen_id' => $item['origen_id'],
                'tipo' => $item['tipo'],
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $item['ruta_ver'],
                'ruta_origen' => $this->construirRutaOrigen($item['origen']),
            ], $dependenciaNombre, $rolDestinatarioId, $usuarioDestinatarioResuelto);

            $this->modeloHistorial->registrar($item['origen'], $item['origen_id'], 'redireccionada', 'Enviado desde Enviadas a ' . $dependenciaNombre);
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);
        $asunto = 'Petición enviada';
        $cuerpo = 'Se te enviaron ' . count($items) . ' ítem(s) para tu gestión en "' . $dependenciaNombre . '".';

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear($remitenteId, (int) $destinatario['id'], $asunto, $cuerpo);
        }

        return ['', 'Se enviaron ' . count($items) . ' ítem(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaNombre . '").'];
    }

    /**
     * Envía (redirecciona) una selección arbitraria de ítems de "Pendientes" a otra
     * dependencia/rol, en vez de dejarlos para su destinatario original — igual patrón que
     * enviarArchivadoGrupo()/enviarEnviadoGrupo(), pero operando directamente sobre pendientes
     * (que pueden no tener todavía ninguna fila en peticiones_archivadas, por eso
     * redireccionarDirecto() y no redireccionarItems()).
     */
    private function enviarPendientesGrupo(): array
    {
        $items = $this->leerItemsJson();
        $dependenciaNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if (empty($items)) {
            return ['No seleccionaste ningún ítem para enviar.', ''];
        }

        if ($dependenciaNombre === '') {
            return ['Selecciona la dependencia a la que se enviará.', ''];
        }

        $rol = $rolDestinatarioId > 0 ? $this->modeloRol->obtenerPorId($rolDestinatarioId) : null;

        if ($rol === null) {
            return ['Selecciona el rol al que se enviará.', ''];
        }

        $dependencia = $this->modeloDependencia->obtenerPorNombre($dependenciaNombre);
        $destinatarios = $dependencia !== null
            ? $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependencia['id'], $rolDestinatarioId)
            : [];

        if (empty($destinatarios)) {
            return ['No se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '" para notificar.', ''];
        }

        if (count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;
        $enviados = 0;

        foreach ($items as $item) {
            $origen = $item['origen'];

            if ($origen === '' || $origen === 'necesidad_grupo') {
                continue;
            }

            $origenId = $item['origen_id'];

            $this->modeloArchivada->redireccionarDirecto([
                'origen' => $origen,
                'origen_id' => $origenId,
                'tipo' => $item['tipo'],
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $item['ruta_ver'],
                'ruta_origen' => $item['ruta_origen'],
            ], $dependenciaNombre, $rolDestinatarioId, $usuarioDestinatarioResuelto);

            $this->modeloHistorial->registrar($origen, $origenId, 'redireccionada', 'Enviado desde Pendientes a ' . $dependenciaNombre);

            $enviados++;
        }

        if ($enviados === 0) {
            return ['No se pudo enviar ningún ítem.', ''];
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);
        $asunto = 'Petición enviada';
        $cuerpo = 'Se te enviaron ' . $enviados . ' ítem(s) para tu gestión en "' . $dependenciaNombre . '".';

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear($remitenteId, (int) $destinatario['id'], $asunto, $cuerpo);
        }

        return ['', 'Se enviaron ' . $enviados . ' ítem(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaNombre . '").'];
    }

    private function obtenerPorAccionYClave(string $accion): array
    {
        $filasPorClave = [];
        foreach ($this->modeloArchivada->obtenerPorAccion($accion) as $fila) {
            $filasPorClave[$fila['origen'] . ':' . $fila['origen_id']] = $fila;
        }

        return $filasPorClave;
    }

    private function duplicarRegistroOrigen(string $origen, int $origenId): ?int
    {
        if (!isset(self::TABLAS_ORIGEN[$origen])) {
            return null;
        }

        $nuevoId = $this->duplicarFilaTabla(self::TABLAS_ORIGEN[$origen], $origenId);

        if ($nuevoId === null) {
            return null;
        }

        if (isset(self::TABLAS_CONCEPTOS_ORIGEN[$origen])) {
            [$tablaHijo, $campoFk] = self::TABLAS_CONCEPTOS_ORIGEN[$origen];
            $this->duplicarFilasHijo($tablaHijo, $campoFk, $origenId, $nuevoId);
        }

        return $nuevoId;
    }

    private function obtenerColumnasTabla(string $tabla): array
    {
        $consulta = $this->db->query("SHOW COLUMNS FROM {$tabla}");

        return array_column($consulta->fetchAll(), 'Field');
    }

    private function duplicarFilaTabla(string $tabla, int $id): ?int
    {
        $columnas = $this->obtenerColumnasTabla($tabla);
        $columnasCopiar = array_values(array_filter(
            $columnas,
            static fn (string $columna): bool => !in_array($columna, ['id', 'creado_en', 'actualizado_en'], true)
        ));

        if (empty($columnasCopiar)) {
            return null;
        }

        $listaColumnas = implode(', ', $columnasCopiar);
        $consulta = $this->db->prepare("INSERT INTO {$tabla} ({$listaColumnas}) SELECT {$listaColumnas} FROM {$tabla} WHERE id = :id");
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0 ? (int) $this->db->lastInsertId() : null;
    }

    private function duplicarFilasHijo(string $tablaHijo, string $campoFk, int $idViejo, int $idNuevo): void
    {
        $columnas = $this->obtenerColumnasTabla($tablaHijo);
        $columnasCopiar = array_values(array_filter(
            $columnas,
            static fn (string $columna): bool => !in_array($columna, ['id', $campoFk], true)
        ));

        $consultaHijos = $this->db->prepare("SELECT * FROM {$tablaHijo} WHERE {$campoFk} = :id");
        $consultaHijos->execute(['id' => $idViejo]);
        $hijos = $consultaHijos->fetchAll();

        if (empty($hijos)) {
            return;
        }

        $listaColumnas = implode(', ', array_merge([$campoFk], $columnasCopiar));
        $marcadores = implode(', ', array_merge([':fk'], array_map(static fn (string $columna): string => ':' . $columna, $columnasCopiar)));
        $consultaInsertar = $this->db->prepare("INSERT INTO {$tablaHijo} ({$listaColumnas}) VALUES ({$marcadores})");

        foreach ($hijos as $hijo) {
            $parametros = ['fk' => $idNuevo];
            foreach ($columnasCopiar as $columna) {
                $parametros[$columna] = $hijo[$columna];
            }
            $consultaInsertar->execute($parametros);
        }
    }

    private function construirRutaVer(string $origen, int $origenId, string $estado): string
    {
        return 'index.php?ruta=peticiones-tipo-detalle&estado=' . $estado . '&origen=' . $origen . '&resaltar_id=' . $origenId;
    }

    private function construirRutaOrigen(string $origen): string
    {
        $mapa = [
            'arl' => 'index.php?ruta=solicitudes&tab=arl',
            'monitores' => 'index.php?ruta=solicitudes&tab=monitores',
            'ops' => 'index.php?ruta=solicitudes&tab=ops',
            'otros' => 'index.php?ruta=solicitudes&tab=otros',
            'necesidad' => 'index.php?ruta=perfil-proyectos',
            'gasto_principal' => 'index.php?ruta=gastos',
            'gasto_extension' => 'index.php?ruta=extension',
            'gasto_postgrado' => 'index.php?ruta=postgrado',
            'gasto_unisalud' => 'index.php?ruta=unisalud',
            'gasto_sin_excedentes' => 'index.php?ruta=sin-excedentes',
            'ingreso_extension' => 'index.php?ruta=extension',
            'ingreso_postgrado' => 'index.php?ruta=postgrado',
            'ingreso_unisalud' => 'index.php?ruta=unisalud',
            'ingreso_sin_excedentes' => 'index.php?ruta=sin-excedentes',
        ];

        return $mapa[$origen] ?? 'index.php?ruta=peticiones';
    }

    /**
     * URL al formulario de edición REAL del módulo dueño de este origen (mismo `?editar_id=` que
     * ya usa cada controlador real: Gasto, Extensión, Postgrado, Unisalud, Convenios, Solicitudes,
     * Perfil de proyectos) — no una edición in-place de celdas. `$rutaVolver` deja el botón "Volver"
     * de ese formulario apuntando de regreso a este mismo landing (tipo-detalle), no al índice
     * plano del módulo.
     */
    private function construirRutaEditar(string $origen, int $origenId, string $rutaVolver): string
    {
        $volver = '&volver=' . urlencode($rutaVolver);

        return match ($origen) {
            'gasto_principal' => 'index.php?ruta=gastos&editar_id=' . $origenId . $volver,
            'gasto_extension' => 'index.php?ruta=extension&editar_id=' . $origenId . '&tab=egresos' . $volver,
            'ingreso_extension' => 'index.php?ruta=extension&editar_id=' . $origenId . '&tab=ingresos' . $volver,
            'gasto_postgrado' => 'index.php?ruta=postgrado&editar_id=' . $origenId . '&tab=egresos' . $volver,
            'ingreso_postgrado' => 'index.php?ruta=postgrado&editar_id=' . $origenId . '&tab=ingresos' . $volver,
            'gasto_unisalud' => 'index.php?ruta=unisalud&editar_id=' . $origenId . '&tab=egresos' . $volver,
            'ingreso_unisalud' => 'index.php?ruta=unisalud&editar_id=' . $origenId . '&tab=ingresos' . $volver,
            'gasto_sin_excedentes' => 'index.php?ruta=sin-excedentes&editar_id=' . $origenId . '&tab=egresos' . $volver,
            'ingreso_sin_excedentes' => 'index.php?ruta=sin-excedentes&editar_id=' . $origenId . '&tab=ingresos' . $volver,
            'arl' => 'index.php?ruta=solicitudes&editar_id=' . $origenId . '&tipo_solicitud=arl&tab=arl' . $volver,
            'monitores' => 'index.php?ruta=solicitudes&editar_id=' . $origenId . '&tipo_solicitud=monitores&tab=monitores' . $volver,
            'ops' => 'index.php?ruta=solicitudes&editar_id=' . $origenId . '&tipo_solicitud=ops&tab=ops' . $volver,
            'otros' => 'index.php?ruta=solicitudes&editar_id=' . $origenId . '&tipo_solicitud=otros&tab=otros' . $volver,
            'necesidad' => 'index.php?ruta=perfil-proyectos&editar_id=' . $origenId . $volver,
            default => $this->construirRutaOrigen($origen),
        };
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

        $this->modeloHistorial->registrar($origen, $origenId, 'rechazada_redireccion', 'Se rechazó la redirección, vuelve a la dependencia de origen');

        return ['', 'Se rechazó la redirección y se devolvió a la dependencia de origen.'];
    }

    private function eliminarPendiente(): array
    {
        $origen = trim($_POST['origen'] ?? '');
        $origenId = (int) ($_POST['origen_id'] ?? 0);

        if ($origen === '' || $origenId <= 0) {
            return ['El elemento que intentas eliminar no existe.', ''];
        }

        if (!$this->eliminarUnPendiente($origen, $origenId)) {
            return ['No se pudo eliminar el elemento.', ''];
        }

        return ['', 'Elemento eliminado correctamente.'];
    }

    /**
     * Elimina en lote una selección de ítems pendientes (ver agruparPendientesPorDependencia()) —
     * misma lógica que eliminarPendiente(), pero sobre arrays item_origen[]/item_origen_id[].
     */
    private function eliminarPendientesGrupo(): array
    {
        $items = $this->leerItemsJson();

        if (empty($items)) {
            return ['No seleccionaste ningún ítem pendiente.', ''];
        }

        $eliminados = 0;

        foreach ($items as $item) {
            $origen = $item['origen'];
            $origenId = $item['origen_id'];

            if ($origen === '' || $origenId <= 0) {
                continue;
            }

            if ($this->eliminarUnPendiente($origen, $origenId)) {
                $eliminados++;
            }
        }

        if ($eliminados === 0) {
            return ['No se pudo eliminar ningún ítem.', ''];
        }

        return ['', 'Se eliminaron ' . $eliminados . ' ítem(s).'];
    }

    private function eliminarUnPendiente(string $origen, int $origenId): bool
    {
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
            return false;
        }

        $this->modeloHistorial->registrar($origen, $origenId, 'eliminada', 'Eliminado permanentemente desde Pendientes');
        $this->modeloArchivada->eliminarPorOrigen($origen, $origenId);

        return true;
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
        $rolUsuarioActual = $this->obtenerRolUsuarioActual();

        return array_values(array_filter($items, function (array $item) use ($dependenciasPermitidas, $rolUsuarioActual): bool {
            if ($item['origen'] === 'necesidad_grupo') {
                return true;
            }

            // 'otros' (Petición sin dependencia) no tiene campo de dependencia — se dirige a
            // todos los que tengan el rol destinatario, sin importar la dependencia (ver
            // SolicitudControlador::notificarYMarcarEnviada). Antes esta rama dejaba pasar
            // CUALQUIER Petición (Otra) a TODOS los usuarios sin comprobar siquiera el rol, lo
            // que hacía que, una vez consolidada/archivada, una facultad viera las peticiones de
            // otra. Se reemplaza por el mismo chequeo de rol que ya se usa al construir Pendientes.
            if ($item['origen'] === 'otros') {
                $rolDestinatario = $this->obtenerRolDestinatarioOtros((int) $item['origen_id']);

                return $rolDestinatario !== null && $rolDestinatario === $rolUsuarioActual;
            }

            return in_array($item['detalle'], $dependenciasPermitidas, true);
        }));
    }

    private function obtenerRolUsuarioActual(): ?int
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        return !empty($usuarioActual['rol_id']) ? (int) $usuarioActual['rol_id'] : null;
    }

    private function obtenerRolDestinatarioOtros(int $origenId): ?int
    {
        $fila = $this->modeloPeticion->obtenerPorId($origenId);

        return !empty($fila['rol_destinatario_id']) ? (int) $fila['rol_destinatario_id'] : null;
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

            if ($this->visibilidadSolicitud($fila['dependencia_destino'] ?? $fila['dependencia'], $fila['rol_destinatario_id'], $fila['usuario_destinatario_id'] ?? null)) {
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
                'ruta_ver' => $this->construirRutaVer('necesidad', (int) $fila['id'], $accionArchivada),
                'ruta_origen' => 'index.php?ruta=perfil-proyectos',
            ]);
            $this->modeloHistorial->registrar(
                'necesidad',
                (int) $fila['id'],
                $accionArchivada,
                $accionArchivada === 'aprobada' ? 'Consolidado (aceptar todos)' : 'Archivado (archivar todos)'
            );
        }

        $verbo = $accionArchivada === 'aprobada' ? 'aceptaron' : 'archivaron';

        return ['', 'Se ' . $verbo . ' ' . count($visibles) . ' proyecto(s) de Perfil de proyectos.'];
    }

    /**
     * Decodifica el snapshot de ítems seleccionados en Pendientes, enviado como un solo campo JSON
     * (items_json) en vez de arrays paralelos item_origen[]/item_tipo[]/... — una fila de Pendientes
     * agrupada por dependencia puede traer cientos de gastos, y con un campo por dato de cada ítem
     * se superaba fácilmente el límite de variables por petición de PHP (max_input_vars, 1000 por
     * defecto): el navegador mostraba el formulario lleno, pero el servidor recibía la petición
     * truncada en silencio, perdiendo los campos que venían después (como dependencia_destino). Ver
     * enviarFormularioPendientes() y el manejador de #modal-enviar-pendientes en app.js.
     *
     * @return array<int, array{origen: string, origen_id: int, tipo: string, detalle: string, cantidad: ?string, valor: ?float, ruta_ver: string, ruta_origen: ?string}>
     */
    private function leerItemsJson(): array
    {
        $crudo = $_POST['items_json'] ?? '';

        if ($crudo === '') {
            return [];
        }

        $decodificado = json_decode($crudo, true);

        if (!is_array($decodificado)) {
            return [];
        }

        $items = [];

        foreach ($decodificado as $item) {
            if (!is_array($item) || empty($item['origen'])) {
                continue;
            }

            $items[] = [
                'origen' => trim((string) $item['origen']),
                'origen_id' => (int) ($item['origen_id'] ?? 0),
                'tipo' => (string) ($item['tipo'] ?? ''),
                'detalle' => (string) ($item['detalle'] ?? ''),
                'cantidad' => isset($item['cantidad']) && $item['cantidad'] !== '' ? (string) $item['cantidad'] : null,
                'valor' => isset($item['valor']) && $item['valor'] !== '' ? (float) $item['valor'] : null,
                'ruta_ver' => (string) ($item['ruta_ver'] ?? 'index.php?ruta=peticiones'),
                'ruta_origen' => isset($item['ruta_origen']) && $item['ruta_origen'] !== '' ? (string) $item['ruta_origen'] : null,
            ];
        }

        return $items;
    }

    /**
     * Aprueba o archiva una selección arbitraria de ítems de "Pendientes" (seleccionar todo /
     * aceptar seleccionados / archivar seleccionados). A diferencia de `archivarConsolidadoGrupo()`,
     * los ítems pendientes no necesitan resolverse contra la BD: sus datos ya viajan completos desde
     * la vista (igual que la acción individual "aprobar"/"archivar" de una sola fila).
     * Si la fila resumen "Perfil de proyectos" (origen='necesidad_grupo') va en la selección, se
     * delega en `procesarGrupoNecesidades()`, igual que hace la acción individual.
     */
    private function procesarPendientesGrupo(string $accionArchivada): array
    {
        $items = $this->leerItemsJson();

        if (empty($items)) {
            return ['No seleccionaste ningún ítem pendiente.', ''];
        }

        $procesados = 0;
        $mensajesExtra = [];

        foreach ($items as $item) {
            $origen = $item['origen'];

            if ($origen === 'necesidad_grupo') {
                [, $exitoGrupoNecesidades] = $this->procesarGrupoNecesidades($accionArchivada);
                if ($exitoGrupoNecesidades !== '') {
                    $mensajesExtra[] = $exitoGrupoNecesidades;
                }
                continue;
            }

            $origenId = $item['origen_id'];

            $this->modeloArchivada->archivar([
                'origen' => $origen,
                'origen_id' => $origenId,
                'accion' => $accionArchivada,
                'tipo' => $item['tipo'],
                'detalle' => $item['detalle'],
                'cantidad' => $item['cantidad'],
                'valor' => $item['valor'],
                'ruta_ver' => $item['ruta_ver'],
                'ruta_origen' => $item['ruta_origen'],
            ]);

            $this->modeloHistorial->registrar(
                $origen,
                $origenId,
                $accionArchivada,
                $accionArchivada === 'aprobada' ? 'Consolidado (aceptar seleccionados)' : 'Archivado (archivar seleccionados)'
            );

            $procesados++;
        }

        if ($procesados === 0 && empty($mensajesExtra)) {
            return ['No se pudo procesar ningún ítem.', ''];
        }

        $verbo = $accionArchivada === 'aprobada' ? 'aceptaron' : 'archivaron';
        $mensaje = $procesados > 0 ? 'Se ' . $verbo . ' ' . $procesados . ' ítem(s) pendiente(s).' : '';

        if (!empty($mensajesExtra)) {
            $mensaje = trim($mensaje . ' ' . implode(' ', $mensajesExtra));
        }

        return ['', $mensaje];
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

            if ($this->visibilidadSolicitud($fila['facultad'], $fila['rol_destinatario_id'], $fila['usuario_destinatario_id'] ?? null)) {
                $pendientesSolicitudes[] = $this->fila('arl', (int) $fila['id'], 'ARL', $fila['facultad'], $totalPracticantes . ' practicantes', $totalValor, 'solicitud', 'index.php?ruta=solicitudes&tab=arl', $this->construirRutaVer('arl', (int) $fila['id'], 'pendiente') . $volver);
            }
        }

        foreach ($this->modeloMonitor->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalMonitores = (int) $fila['monitores_semestre1'] + (int) $fila['monitores_semestre2'];
            if ($this->visibilidadSolicitud($fila['dependencia'], $fila['rol_destinatario_id'], $fila['usuario_destinatario_id'] ?? null)) {
                $pendientesSolicitudes[] = $this->fila('monitores', (int) $fila['id'], 'Monitores', $fila['dependencia'], $totalMonitores . ' monitores', null, 'solicitud', 'index.php?ruta=solicitudes&tab=monitores', $this->construirRutaVer('monitores', (int) $fila['id'], 'pendiente') . $volver);
            }
        }

        foreach ($this->modeloOps->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalOps = (float) $fila['valor'] * (int) $fila['cantidad'];
            if ($this->visibilidadSolicitud($fila['dependencia'], $fila['rol_destinatario_id'], $fila['usuario_destinatario_id'] ?? null)) {
                $pendientesSolicitudes[] = $this->fila('ops', (int) $fila['id'], 'OPS', $fila['dependencia'], $fila['cantidad'] . ' und.', $totalOps, 'solicitud', 'index.php?ruta=solicitudes&tab=ops', $this->construirRutaVer('ops', (int) $fila['id'], 'pendiente') . $volver);
            }
        }

        foreach ($this->modeloPeticion->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            $totalValor = (float) $fila['valor_s1'] + (float) $fila['valor_s2'];
            if ($this->visibilidadSolicitud(null, $fila['rol_destinatario_id'], $fila['usuario_destinatario_id'] ?? null)) {
                $pendientesSolicitudes[] = $this->fila('otros', (int) $fila['id'], 'Petición', $fila['concepto'], null, $totalValor, 'solicitud', 'index.php?ruta=solicitudes&tab=otros', $this->construirRutaVer('otros', (int) $fila['id'], 'pendiente') . $volver);
            }
        }

        $pendientesSolicitudes = array_values(array_filter($pendientesSolicitudes, static function (array $item) use ($archivadas): bool {
            return !isset($archivadas[$item['origen'] . ':' . $item['origen_id']]);
        }));

        // Perfil de proyectos ya no se resume en una sola fila "N proyectos pendientes" que abría
        // el landing viejo (index.php?ruta=perfil-proyectos) sin distinguir dependencia — cada
        // necesidad es su propia fila (como el resto de orígenes), y agruparPendientesPorDependencia()
        // (ver más abajo) las agrupa por dependencia de origen igual que ya hace con Gasto, con
        // "Ver" llevando a la tabla real (peticiones-tipo-detalle), filtrada a esa dependencia.
        foreach ($this->obtenerNecesidadesVisibles() as $fila) {
            $itemNecesidad = $this->fila('necesidad', (int) $fila['id'], 'Perfil de proyectos', $fila['dependencia_destino'] ?? $fila['dependencia'], null, (float) $fila['valor'], 'gasto', 'index.php?ruta=perfil-proyectos', $this->construirRutaVer('necesidad', (int) $fila['id'], 'pendiente'));
            $itemNecesidad['dependencia_origen'] = $fila['dependencia'];
            $pendientes[] = $itemNecesidad;
        }

        $pendientes = array_values(array_filter($pendientes, static function (array $item) use ($archivadas): bool {
            return !isset($archivadas[$item['origen'] . ':' . $item['origen_id']]);
        }));

        foreach ($this->modeloArchivada->obtenerRedireccionadas() as $redirigida) {
            $filaRedirigida = $this->filaRedireccionada($redirigida);
            if ($filaRedirigida !== null) {
                $pendientes[] = $filaRedirigida;
            }
        }

        $pendientes = $this->filtrarPorDependencia($pendientes, $dependenciasPermitidas);

        $pendientesGasto = [];

        foreach ($this->modeloGasto->obtenerEnviadosPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_principal', $fila, 'Gasto', 'index.php?ruta=gastos', $this->construirRutaVer('gasto_principal', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoExtension->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $tipo = !empty($fila['autogestion_nombre']) ? ucfirst($fila['autogestion_nombre']) : 'Extensión';
            $filaGasto = $this->filaGasto('gasto_extension', $fila, $tipo, 'index.php?ruta=extension', $this->construirRutaVer('gasto_extension', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoPostgrado->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_postgrado', $fila, 'Postgrado', 'index.php?ruta=postgrado', $this->construirRutaVer('gasto_postgrado', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoUnisalud->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_unisalud', $fila, 'Unisalud', 'index.php?ruta=unisalud', $this->construirRutaVer('gasto_unisalud', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloGastoSinExcedentes->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaGasto = $this->filaGasto('gasto_sin_excedentes', $fila, 'Convenios', 'index.php?ruta=sin-excedentes', $this->construirRutaVer('gasto_sin_excedentes', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaGasto !== null) {
                $pendientesGasto[] = $filaGasto;
            }
        }

        foreach ($this->modeloIngresoExtension->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $tipo = !empty($fila['autogestion_nombre']) ? ucfirst($fila['autogestion_nombre']) . ' (ingreso)' : 'Ingreso Extensión';
            $filaIngreso = $this->filaIngreso('ingreso_extension', $fila, $tipo, 'index.php?ruta=extension', $this->construirRutaVer('ingreso_extension', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaIngreso !== null) {
                $pendientesGasto[] = $filaIngreso;
            }
        }

        foreach ($this->modeloIngresoPostgrado->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaIngreso = $this->filaIngreso('ingreso_postgrado', $fila, 'Ingreso Postgrado', 'index.php?ruta=postgrado', $this->construirRutaVer('ingreso_postgrado', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaIngreso !== null) {
                $pendientesGasto[] = $filaIngreso;
            }
        }

        foreach ($this->modeloIngresoUnisalud->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaIngreso = $this->filaIngreso('ingreso_unisalud', $fila, 'Ingreso Unisalud', 'index.php?ruta=unisalud', $this->construirRutaVer('ingreso_unisalud', (int) $fila['id'], 'pendiente') . $volver);
            if ($filaIngreso !== null) {
                $pendientesGasto[] = $filaIngreso;
            }
        }

        foreach ($this->modeloIngresoSinExcedentes->obtenerPorAnio($anioPresupuestalId) as $fila) {
            $filaIngreso = $this->filaIngreso('ingreso_sin_excedentes', $fila, 'Ingreso Convenios', 'index.php?ruta=sin-excedentes', $this->construirRutaVer('ingreso_sin_excedentes', (int) $fila['id'], 'pendiente') . $volver);
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
     * Agrupa en una sola fila los ítems pendientes que comparten dependencia (columna "Origen") —
     * hoy solo para la bandeja "Gastos", donde una misma dependencia suele enviar muchos gastos
     * sueltos a la vez y verlos uno por uno hace la lista muy larga. Cada fila resultante conserva
     * los ítems originales en 'items' para que Aprobar/Archivar/Eliminar actúen sobre todos a la vez
     * (mismo patrón de "seleccionar todo" ya usado en Consolidado por tipo), y "Ver" lleva a una
     * página aparte con el detalle (actividad/insumo) de cada uno.
     */
    private function agruparPendientesPorDependencia(array $pendientes): array
    {
        $grupos = [];

        foreach ($pendientes as $item) {
            // Se agrupa por la dependencia de ORIGEN (dependencia_origen si el origen la trae, ej.
            // Gasto/Ingreso; si no, "detalle" — la dependencia/facultad remitente en Solicitudes),
            // no por la dependencia DESTINO a la que se envió: varios ítems de programas distintos
            // (ej. "INGENIERÍA QUÍMICA", "INGENIERÍA MECÁNICA") pueden llegar al mismo destinatario
            // en un solo envío, y deben verse como grupos separados, no fusionados en uno solo. La
            // clave incluye el origen para no mezclar, por ejemplo, un Gasto y un ARL de la misma
            // dependencia en un solo grupo.
            $nombreDependencia = $item['dependencia_origen'] ?? $item['detalle'];
            $clave = $item['origen'] . '|' . $nombreDependencia;

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'origen' => $item['origen'],
                    'origen_id' => 0,
                    'tipo' => $item['tipo'],
                    'detalle' => $nombreDependencia,
                    'cantidad' => 0,
                    'valor' => 0.0,
                    'accion_aprobar' => $item['accion_aprobar'],
                    'accion_rechazar' => $item['accion_rechazar'],
                    'ruta_origen' => $item['ruta_origen'],
                    'ruta_ver' => $this->construirRutaVer($item['origen'], 0, 'pendiente') . '&dependencia=' . urlencode($nombreDependencia),
                    'redireccionado' => false,
                    'semaforo' => null,
                    'puede_actuar' => true,
                    'items' => [],
                ];
            }

            $grupos[$clave]['cantidad']++;
            $grupos[$clave]['valor'] += $item['valor'] !== null ? (float) $item['valor'] : 0.0;
            $grupos[$clave]['items'][] = $item;
        }

        return array_values(array_map(static function (array $grupo): array {
            $grupo['cantidad'] = $grupo['cantidad'] . ' ítem(s)';

            return $grupo;
        }, $grupos));
    }

    /**
     * Construye la vista "Peticiones Enviadas": todo lo que la dependencia del usuario actual (o
     * alguna de sus hijas) ya envió, sin importar el estado en el que se encuentre ahora (pendiente,
     * consolidado, archivado o redireccionado) — es de solo lectura para el emisor, complementaria a
     * "Pendientes" (que muestra lo que le enviaron a él). Reutiliza los mismos métodos de modelo que
     * construirPendientes(), con el gate invertido (emisor en vez de receptor).
     */
    private function construirEnviadas(int $anioPresupuestalId, array $dependenciasPermitidas): array
    {
        $acciones = $this->modeloArchivada->obtenerAccionesPorClave();
        $redireccionadas = [];
        foreach ($this->modeloArchivada->obtenerRedireccionadas() as $redirigida) {
            $redireccionadas[$redirigida['origen'] . ':' . $redirigida['origen_id']] = $redirigida['redireccionado_a_dependencia'];
        }

        $usuarioActualId = (int) ($_SESSION['usuario_id'] ?? 0);
        $enviadas = [];

        $agregar = function (string $origen, int $origenId, string $tipo, string $destino, ?string $cantidad, ?float $valor) use (&$enviadas, $acciones, $redireccionadas): void {
            $clave = $origen . ':' . $origenId;
            $accion = $acciones[$clave] ?? null;

            $estado = match ($accion) {
                'aprobada' => 'Consolidado',
                'archivada' => 'Archivado',
                'redireccionada' => 'Redireccionado a ' . ($redireccionadas[$clave] ?? '—'),
                default => 'Pendiente de revisión',
            };

            // "Ver" abre la tabla real correspondiente al estado ACTUAL del ítem (aprobada/archivada
            // si el destinatario ya actuó; si no, la de "enviada" — la vista propia de este listado).
            $estadoTabla = in_array($accion, ['aprobada', 'archivada'], true) ? $accion : 'enviada';

            $enviadas[] = [
                'origen' => $origen,
                'origen_id' => $origenId,
                'tipo' => $tipo,
                'detalle' => $destino,
                'cantidad' => $cantidad,
                'valor' => $valor,
                'ruta_ver' => $this->construirRutaVer($origen, $origenId, $estadoTabla),
                'estado_enviada' => $estado,
                'accion_actual' => $accion,
            ];
        };

        foreach ($this->modeloSolicitud->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            if (!empty($fila['rol_destinatario_id']) && in_array($fila['facultad'], $dependenciasPermitidas, true)) {
                $totalPracticantes = (int) $fila['riesgo1_estudiantes'] + (int) $fila['riesgo2_estudiantes']
                    + (int) $fila['riesgo3_estudiantes'] + (int) $fila['riesgo4_estudiantes'] + (int) $fila['riesgo5_estudiantes'];
                $totalValor = (float) $fila['riesgo1_valor'] + (float) $fila['riesgo2_valor']
                    + (float) $fila['riesgo3_valor'] + (float) $fila['riesgo4_valor'] + (float) $fila['riesgo5_valor'];
                $agregar('arl', (int) $fila['id'], 'ARL', $fila['enviada_a'] ?? $fila['facultad'], $totalPracticantes . ' practicantes', $totalValor);
            }
        }

        foreach ($this->modeloMonitor->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            if (!empty($fila['rol_destinatario_id']) && in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                $totalMonitores = (int) $fila['monitores_semestre1'] + (int) $fila['monitores_semestre2'];
                $agregar('monitores', (int) $fila['id'], 'Monitores', $fila['enviada_a'] ?? $fila['dependencia'], $totalMonitores . ' monitores', null);
            }
        }

        foreach ($this->modeloOps->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            if (!empty($fila['rol_destinatario_id']) && in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                $totalOps = (float) $fila['valor'] * (int) $fila['cantidad'];
                $agregar('ops', (int) $fila['id'], 'OPS', $fila['enviada_a'] ?? $fila['dependencia'], $fila['cantidad'] . ' und.', $totalOps);
            }
        }

        foreach ($this->modeloPeticion->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            if (!empty($fila['rol_destinatario_id']) && (int) ($fila['usuario_id'] ?? 0) === $usuarioActualId) {
                $totalValor = (float) $fila['valor_s1'] + (float) $fila['valor_s2'];
                $agregar('otros', (int) $fila['id'], 'Petición', $fila['enviada_a'] ?? '—', null, $totalValor);
            }
        }

        foreach ($this->modeloNecesidad->obtenerEnviadas() as $fila) {
            if (!empty($fila['rol_destinatario_id']) && in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                $agregar('necesidad', (int) $fila['id'], 'Perfil de proyectos', $fila['dependencia_destino'] ?? $fila['dependencia'], null, (float) $fila['valor']);
            }
        }

        $mapaGasto = [
            ['modelo' => $this->modeloGasto, 'origen' => 'gasto_principal', 'tipo' => 'Gasto', 'metodo' => 'obtenerEnviadosPorAnio'],
            ['modelo' => $this->modeloGastoExtension, 'origen' => 'gasto_extension', 'tipo' => 'Extensión', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoPostgrado, 'origen' => 'gasto_postgrado', 'tipo' => 'Postgrado', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoUnisalud, 'origen' => 'gasto_unisalud', 'tipo' => 'Unisalud', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoSinExcedentes, 'origen' => 'gasto_sin_excedentes', 'tipo' => 'Convenios', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoExtension, 'origen' => 'ingreso_extension', 'tipo' => 'Ingreso Extensión', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoPostgrado, 'origen' => 'ingreso_postgrado', 'tipo' => 'Ingreso Postgrado', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoUnisalud, 'origen' => 'ingreso_unisalud', 'tipo' => 'Ingreso Unisalud', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoSinExcedentes, 'origen' => 'ingreso_sin_excedentes', 'tipo' => 'Ingreso Convenios', 'metodo' => 'obtenerPorAnio'],
        ];

        foreach ($mapaGasto as $fuente) {
            foreach ($fuente['modelo']->{$fuente['metodo']}($anioPresupuestalId) as $fila) {
                if (empty($fila['rol_destinatario_id']) || !in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                    continue;
                }

                $cantidad = isset($fila['cantidad']) ? $fila['cantidad'] . ' und.' : null;
                $agregar($fuente['origen'], (int) $fila['id'], $fuente['tipo'], $fila['dependencia_destino'] ?? $fila['dependencia'], $cantidad, (float) $fila['valor_total']);
            }
        }

        return $enviadas;
    }

    /**
     * Una solicitud (ARL/Monitores/OPS/Otros/gasto/ingreso/etc.) solo debe ser visible en
     * Peticiones para el usuario que coincide exactamente con la dependencia y el rol al que fue
     * enviada — nadie más la ve. Cuando además se conoce el usuario específico elegido como
     * destinatario ($usuarioDestinatarioId, guardado al enviar si había más de uno con ese rol en
     * la dependencia), la visibilidad se restringe a esa persona exacta — si no, cualquier otro
     * usuario con el mismo rol en la misma dependencia también vería la petición, aunque no fue
     * a quien se le envió. Los envíos antiguos (sin ese dato guardado) mantienen el comportamiento
     * de siempre: visible para cualquiera con ese rol en esa dependencia.
     */
    private function visibilidadSolicitud(?string $dependenciaNombre, ?int $rolDestinatarioId, ?int $usuarioDestinatarioId = null): bool
    {
        if ($rolDestinatarioId === null) {
            return false;
        }

        $usuarioActualId = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($usuarioDestinatarioId !== null && $usuarioDestinatarioId !== $usuarioActualId) {
            return false;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId($usuarioActualId);
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
        $usuarioDestinatarioId = !empty($fila['usuario_destinatario_id']) ? (int) $fila['usuario_destinatario_id'] : null;
        $dependenciaDestino = $fila['dependencia_destino'] ?? $fila['dependencia'];

        if (!$this->visibilidadSolicitud($dependenciaDestino, $rolDestinatarioId, $usuarioDestinatarioId)) {
            return null;
        }

        $item = $this->fila($origen, (int) $fila['id'], $tipo, $dependenciaDestino, $fila['cantidad'] . ' und.', (float) $fila['valor_total'], 'gasto', $rutaOrigen, $rutaVer);
        // 'detalle' es la dependencia DESTINO (a quién se le envió, usada para visibilidad); para
        // agrupar/mostrar en Pendientes por Gastos necesitamos la dependencia de ORIGEN del propio
        // gasto (a qué programa/dependencia pertenece el dinero), que puede ser otra distinta —
        // ver agruparPendientesPorDependencia().
        $item['dependencia_origen'] = $fila['dependencia'];

        return $item;
    }

    /**
     * Un ingreso de autogestión se maneja igual que un gasto de autogestión: solo lo ve, con
     * acceso completo, el usuario que coincide exactamente con la dependencia y el rol al que
     * fue enviado — nadie más.
     */
    private function filaIngreso(string $origen, array $fila, string $tipo, string $rutaOrigen, string $rutaVer): ?array
    {
        $rolDestinatarioId = !empty($fila['rol_destinatario_id']) ? (int) $fila['rol_destinatario_id'] : null;
        $usuarioDestinatarioId = !empty($fila['usuario_destinatario_id']) ? (int) $fila['usuario_destinatario_id'] : null;
        $dependenciaDestino = $fila['dependencia_destino'] ?? $fila['dependencia'];

        if (!$this->visibilidadSolicitud($dependenciaDestino, $rolDestinatarioId, $usuarioDestinatarioId)) {
            return null;
        }

        return $this->fila($origen, (int) $fila['id'], $tipo, $dependenciaDestino, null, (float) $fila['valor_total'], 'gasto', $rutaOrigen, $rutaVer);
    }

    /**
     * Un ítem redirigido solo debe ser visible para el rol+usuario específico al que se
     * redireccionó (ver visibilidadSolicitud()) — antes no se filtraba nada, así que cualquiera en
     * la dependencia destino lo veía sin importar su rol. Los redireccionamientos hechos ANTES de
     * este fix no tienen rol_destinatario_id guardado (la columna no existía); para esos se
     * conserva el comportamiento histórico (visible por dependencia, sin filtrar rol) para no
     * ocultar de golpe peticiones ya en curso — los redireccionamientos nuevos sí quedan acotados
     * al rol/usuario exactos.
     */
    private function filaRedireccionada(array $redirigida): ?array
    {
        $dependenciaDestino = $redirigida['redireccionado_a_dependencia'] ?? $redirigida['detalle'];
        $rolDestinatarioId = !empty($redirigida['rol_destinatario_id']) ? (int) $redirigida['rol_destinatario_id'] : null;

        if ($rolDestinatarioId !== null) {
            $usuarioDestinatarioId = !empty($redirigida['usuario_destinatario_id']) ? (int) $redirigida['usuario_destinatario_id'] : null;

            if (!$this->visibilidadSolicitud($dependenciaDestino, $rolDestinatarioId, $usuarioDestinatarioId)) {
                return null;
            }
        } else {
            $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
            $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;
            $dependenciaUsuario = $dependenciaUsuarioId !== null ? $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId) : null;

            if ($dependenciaUsuario === null || $dependenciaUsuario['nombre'] !== $dependenciaDestino) {
                return null;
            }
        }

        $item = [
            'origen' => $redirigida['origen'],
            'origen_id' => (int) $redirigida['origen_id'],
            'tipo' => $redirigida['tipo'],
            'detalle' => $dependenciaDestino,
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

        // 'detalle' es la dependencia DESTINO (a quién se redireccionó); igual que en filaGasto(),
        // para que Pendientes de Gastos agrupe por dependencia de ORIGEN (y no fusione en un solo
        // grupo ítems de varios programas distintos redireccionados juntos desde Consolidado por
        // tipo — ver agruparPendientesPorDependencia()) se recupera la dependencia real del gasto.
        if ($redirigida['origen'] === 'gasto_principal') {
            $gastoOriginal = $this->modeloGasto->obtenerPorId((int) $redirigida['origen_id']);
            $item['dependencia_origen'] = $gastoOriginal['dependencia'] ?? $dependenciaDestino;
        }

        return $item;
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
