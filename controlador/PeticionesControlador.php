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

    private const VISTAS = ['pendientes', 'consolidado', 'archivar', 'enviadas'];

    /**
     * Agrupación de los 13 "origen" en bandejas por módulo (spec: navegación por bandejas en
     * Peticiones). "gasto_sin_excedentes"/"ingreso_sin_excedentes" NO tienen bandeja propia — se
     * reparten dinámicamente entre "extension" ("Convenios y Asesorías") y "postgrado" ("Convenios
     * Postgrados") según la categoría elegida al enviar (ver `categoria_peticion`, columna nueva en
     * esas 2 tablas); ese reparto lo resuelve `filtrarPorOrigenes()`, no esta lista estática.
     */
    private const BANDEJAS = [
        'gastos' => ['etiqueta' => 'Gastos', 'origenes' => ['gasto_principal']],
        'extension' => ['etiqueta' => 'Extensión', 'origenes' => ['gasto_extension', 'ingreso_extension']],
        'postgrado' => ['etiqueta' => 'Postgrado', 'origenes' => ['gasto_postgrado', 'ingreso_postgrado']],
        'unisalud' => ['etiqueta' => 'Unidad de Salud', 'origenes' => ['gasto_unisalud', 'ingreso_unisalud']],
        'perfil-proyectos' => ['etiqueta' => 'Perfil de Proyectos', 'origenes' => ['necesidad', 'necesidad_grupo']],
        'solicitudes' => ['etiqueta' => 'Solicitudes', 'origenes' => ['arl', 'monitores', 'ops', 'otros']],
    ];

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
            } elseif ($accion === 'archivar_consolidado') {
                [$errorArchivarCons, $exitoArchivarCons] = $this->archivarConsolidadoGrupo();
                $_SESSION['peticiones_flash_error'] = $errorArchivarCons;
                $_SESSION['peticiones_flash_exito'] = $exitoArchivarCons;
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
            if (!empty($_POST['bandeja']) && isset(self::BANDEJAS[$_POST['bandeja']])) {
                $destino .= '&bandeja=' . urlencode($_POST['bandeja']);
            }
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

        $bandejaSolicitada = $_GET['bandeja'] ?? null;
        $bandeja = isset(self::BANDEJAS[$bandejaSolicitada]) ? $bandejaSolicitada : null;

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

        $conteosBandejas = [];
        if ($bandeja === null) {
            foreach (self::BANDEJAS as $claveBandeja => $infoBandeja) {
                $conteosBandejas[$claveBandeja] = count($this->filtrarPorOrigenes($pendientes, $infoBandeja['origenes'], $claveBandeja));
            }
        } else {
            $pendientes = $this->filtrarPorOrigenes($pendientes, self::BANDEJAS[$bandeja]['origenes'], $bandeja);

            if ($bandeja === 'gastos' && !$modoJerarquia) {
                $pendientes = $this->agruparPendientesPorDependencia($pendientes, $anioSeleccionadoId, $bandeja);
            }
        }

        $aprobados = ($bandeja !== null && $vista === 'consolidado') ? $this->modeloArchivada->obtenerPorAccion('aprobada') : [];
        $aprobados = $this->filtrarPorDependencia($aprobados, $dependenciasPermitidas);
        if ($bandeja !== null) {
            $aprobados = $this->filtrarPorOrigenes($aprobados, self::BANDEJAS[$bandeja]['origenes'], $bandeja);
        }

        $tiposRedireccionados = [];
        if ($bandeja !== null && $vista === 'consolidado') {
            foreach ($this->modeloArchivada->obtenerRedireccionadas() as $redirigida) {
                $tiposRedireccionados[$redirigida['tipo']] = true;
            }
        }

        $filasDetalladasConsolidado = ($bandeja !== null && $vista === 'consolidado') ? $this->construirFilasDetalleCompleto($aprobados, $anioSeleccionadoId) : [];

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
                'ruta_ver' => $item['ruta_ver'],
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

        $consolidadoUnificado = ($bandeja !== null && $vista === 'consolidado' && $modoJerarquia)
            ? $this->construirConsolidadoUnificado($aprobados, $anioSeleccionadoId)
            : [];

        $archivados = ($bandeja !== null && $vista === 'archivar') ? $this->modeloArchivada->obtenerPorAccion('archivada') : [];
        $archivados = $this->filtrarPorDependencia($archivados, $dependenciasPermitidas);
        if ($bandeja !== null) {
            $archivados = $this->filtrarPorOrigenes($archivados, self::BANDEJAS[$bandeja]['origenes'], $bandeja);
        }

        $enviadas = ($bandeja !== null && $vista === 'enviadas' && $anioSeleccionadoId > 0)
            ? $this->construirEnviadas($anioSeleccionadoId, $dependenciasPermitidas)
            : [];
        if ($bandeja !== null) {
            $enviadas = $this->filtrarPorOrigenes($enviadas, self::BANDEJAS[$bandeja]['origenes'], $bandeja);
        }

        $dependenciasSugeridas = $this->modeloDependencia->obtenerActivasParaEnvio();
        $roles = $this->modeloRol->obtenerTodos();
        $rolesPorTipo = $this->modeloTipoDependenciaRol->obtenerMapaCompleto();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();
        $bandejas = self::BANDEJAS;

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'redireccionar_consolidado') {
                [$_SESSION['peticiones_flash_error'], $_SESSION['peticiones_flash_exito']] = $this->redireccionarConsolidado();
            } elseif ($accion === 'duplicar_consolidado') {
                [$_SESSION['peticiones_flash_error'], $_SESSION['peticiones_flash_exito']] = $this->duplicarConsolidadoGrupo();
            }

            $destino = 'index.php?ruta=consolidado-detalle';
            if (!empty($_POST['anio_id'])) {
                $destino .= '&anio_id=' . (int) $_POST['anio_id'];
            }
            if (!empty($_POST['tipo_filtro'])) {
                $destino .= '&tipo=' . urlencode($_POST['tipo_filtro']);
            }
            header('Location: ' . $destino);
            exit;
        }

        $error = $_SESSION['peticiones_flash_error'] ?? '';
        $exito = $_SESSION['peticiones_flash_exito'] ?? '';
        unset($_SESSION['peticiones_flash_error'], $_SESSION['peticiones_flash_exito']);

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
        $roles = $this->modeloRol->obtenerTodos();
        $dependenciasSugeridas = $this->modeloDependencia->obtenerActivasParaEnvio();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();

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
            $dependenciaNombre = $gastoOriginal['dependencia_destino'] ?? $gastoOriginal['dependencia'] ?? $item['detalle'];

            $dependenciaOrigen = $dependenciaNombre !== null && $dependenciaNombre !== ''
                ? $this->modeloDependencia->obtenerPorNombre($dependenciaNombre)
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
                'ruta_ver' => $item['ruta_ver'],
                'ruta_origen' => $item['ruta_origen'] ?? 'index.php?ruta=peticiones',
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
                $rutaVerItem = 'index.php?ruta=gasto-detalle&origen=' . $fuente['origen'] . '&id=' . (int) $fila['id'];
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
            $dependenciaNombre = $gastoOriginal['dependencia_destino'] ?? $gastoOriginal['dependencia'] ?? $item['detalle'];

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

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        if ($usuarioActual === null) {
            return false;
        }

        if ($this->esContribucionPostgrado($item)) {
            if ((int) ($usuarioActual['es_super_admin'] ?? 0) === 1) {
                return true;
            }

            if ($dependenciaActual !== 'DEPARTAMENTO DE POSTGRADOS') {
                return false;
            }

            $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;
            $dependenciaUsuario = $dependenciaUsuarioId !== null ? $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId) : null;

            return $dependenciaUsuario !== null
                && $dependenciaUsuario['nombre'] === 'DEPARTAMENTO DE POSTGRADOS'
                && ($dependenciaUsuario['tipo'] ?? null) === 'Departamento';
        }

        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaUsuarioId === null) {
            return false;
        }

        $dependenciaUsuario = $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId);

        if ($dependenciaUsuario === null) {
            return false;
        }

        if ($dependenciaUsuario['nombre'] === $dependenciaActual) {
            return true;
        }

        $nombresDescendientes = array_column($this->modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId), 'nombre');

        return in_array($dependenciaActual, $nombresDescendientes, true);
    }

    private function esContribucionPostgrado(array $item): bool
    {
        if ($item['origen'] !== 'gasto_postgrado') {
            return false;
        }

        $registro = $this->modeloGastoPostgrado->obtenerPorId((int) $item['origen_id']);

        return $registro !== null && ($registro['tipo_automatico'] ?? null) === 'contrib_postgrado';
    }

    private function resolverDependenciaActualItem(array $item): ?string
    {
        $modelosConDependencia = [
            'arl' => [$this->modeloSolicitud, 'facultad'],
            'monitores' => [$this->modeloMonitor, 'dependencia'],
            'ops' => [$this->modeloOps, 'dependencia'],
            'necesidad' => [$this->modeloNecesidad, 'dependencia_destino'],
            'gasto_principal' => [$this->modeloGasto, 'dependencia_destino'],
            'gasto_extension' => [$this->modeloGastoExtension, 'dependencia_destino'],
            'gasto_postgrado' => [$this->modeloGastoPostgrado, 'dependencia_destino'],
            'gasto_unisalud' => [$this->modeloGastoUnisalud, 'dependencia_destino'],
            'gasto_sin_excedentes' => [$this->modeloGastoSinExcedentes, 'dependencia_destino'],
            'ingreso_extension' => [$this->modeloIngresoExtension, 'dependencia_destino'],
            'ingreso_postgrado' => [$this->modeloIngresoPostgrado, 'dependencia_destino'],
            'ingreso_unisalud' => [$this->modeloIngresoUnisalud, 'dependencia_destino'],
            'ingreso_sin_excedentes' => [$this->modeloIngresoSinExcedentes, 'dependencia_destino'],
        ];

        if (!isset($modelosConDependencia[$item['origen']])) {
            return $item['detalle'] ?? null;
        }

        [$modelo, $campo] = $modelosConDependencia[$item['origen']];
        $registro = $modelo->obtenerPorId((int) $item['origen_id']);

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

        $cantidadItems = $this->modeloArchivada->redireccionarItems($pares, $dependenciaNombre);

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

        $cantidadItems = $this->modeloArchivada->redireccionarItems($pares, $dependenciaNombre, 'archivada');

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
            ], $dependenciaNombre);

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

    private function construirRutaVer(string $origen, int $origenId): string
    {
        $mapaSolicitud = ['arl' => 'arl', 'monitores' => 'monitores', 'ops' => 'ops', 'otros' => 'otros'];

        if (isset($mapaSolicitud[$origen])) {
            return 'index.php?ruta=solicitud-detalle&tipo=' . $mapaSolicitud[$origen] . '&id=' . $origenId;
        }

        if ($origen === 'necesidad') {
            return 'index.php?ruta=perfil-proyectos';
        }

        return 'index.php?ruta=gasto-detalle&origen=' . $origen . '&id=' . $origenId;
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
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem pendiente.', ''];
        }

        $eliminados = 0;

        foreach ($origenes as $indice => $origen) {
            $origen = trim((string) $origen);
            $origenId = (int) ($origenIds[$indice] ?? 0);

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
     * Filtra una lista de items (pendientes/aprobados/archivados/enviadas) a solo los que
     * pertenecen a la bandeja seleccionada, según su campo 'origen' (cada fila ya lo trae).
     *
     * Caso especial: "gasto_sin_excedentes"/"ingreso_sin_excedentes" no tienen bandeja propia en
     * `self::BANDEJAS` — pertenecen dinámicamente a "extension" o "postgrado" según la categoría
     * elegida al enviar (columna `categoria_peticion`, no una dependencia real). Se resuelve
     * consultando el registro vivo; `NULL` (ítems enviados antes de que existiera esta categoría)
     * se trata como "extension" para que no desaparezcan de Peticiones.
     */
    private function filtrarPorOrigenes(array $items, array $origenesPermitidos, ?string $bandeja = null): array
    {
        $modelosSinExcedentes = [
            'gasto_sin_excedentes' => $this->modeloGastoSinExcedentes,
            'ingreso_sin_excedentes' => $this->modeloIngresoSinExcedentes,
        ];

        return array_values(array_filter($items, function (array $item) use ($origenesPermitidos, $bandeja, $modelosSinExcedentes): bool {
            if (in_array($item['origen'], $origenesPermitidos, true)) {
                return true;
            }

            if ($bandeja === null || !in_array($bandeja, ['extension', 'postgrado'], true) || !isset($modelosSinExcedentes[$item['origen']])) {
                return false;
            }

            $registro = $modelosSinExcedentes[$item['origen']]->obtenerPorId((int) $item['origen_id']);
            $categoria = $registro['categoria_peticion'] ?? 'extension';

            return $categoria === $bandeja;
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

            if ($this->visibilidadSolicitud($fila['dependencia_destino'] ?? $fila['dependencia'], $fila['rol_destinatario_id'])) {
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
     * Aprueba o archiva una selección arbitraria de ítems de "Pendientes" (seleccionar todo /
     * aceptar seleccionados / archivar seleccionados). A diferencia de `archivarConsolidadoGrupo()`,
     * los ítems pendientes no necesitan resolverse contra la BD: sus datos ya viajan completos desde
     * la vista (igual que la acción individual "aprobar"/"archivar" de una sola fila).
     * Si la fila resumen "Perfil de proyectos" (origen='necesidad_grupo') va en la selección, se
     * delega en `procesarGrupoNecesidades()`, igual que hace la acción individual.
     */
    private function procesarPendientesGrupo(string $accionArchivada): array
    {
        $origenes = $_POST['item_origen'] ?? [];
        $origenIds = $_POST['item_origen_id'] ?? [];
        $tipos = $_POST['item_tipo'] ?? [];
        $detalles = $_POST['item_detalle'] ?? [];
        $cantidades = $_POST['item_cantidad'] ?? [];
        $valores = $_POST['item_valor'] ?? [];
        $rutasVer = $_POST['item_ruta_ver'] ?? [];
        $rutasOrigen = $_POST['item_ruta_origen'] ?? [];

        if (empty($origenes)) {
            return ['No seleccionaste ningún ítem pendiente.', ''];
        }

        $procesados = 0;
        $mensajesExtra = [];

        foreach ($origenes as $indice => $origen) {
            $origen = trim((string) $origen);

            if ($origen === 'necesidad_grupo') {
                [, $exitoGrupoNecesidades] = $this->procesarGrupoNecesidades($accionArchivada);
                if ($exitoGrupoNecesidades !== '') {
                    $mensajesExtra[] = $exitoGrupoNecesidades;
                }
                continue;
            }

            $origenId = (int) ($origenIds[$indice] ?? 0);

            $this->modeloArchivada->archivar([
                'origen' => $origen,
                'origen_id' => $origenId,
                'accion' => $accionArchivada,
                'tipo' => $tipos[$indice] ?? '',
                'detalle' => $detalles[$indice] ?? '',
                'cantidad' => ($cantidades[$indice] ?? '') !== '' ? $cantidades[$indice] : null,
                'valor' => ($valores[$indice] ?? '') !== '' ? (float) $valores[$indice] : null,
                'ruta_ver' => $rutasVer[$indice] ?? 'index.php?ruta=peticiones',
                'ruta_origen' => $rutasOrigen[$indice] ?? null,
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
                $pendientes[] = $this->fila('necesidad', (int) $fila['id'], 'Perfil de proyectos', $fila['dependencia_destino'] ?? $fila['dependencia'], null, (float) $fila['valor'], 'gasto', 'index.php?ruta=perfil-proyectos', 'index.php?ruta=perfil-proyectos');
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
            $filaGasto = $this->filaGasto('gasto_principal', $fila, 'Gasto', 'index.php?ruta=gastos', 'index.php?ruta=gasto-detalle&origen=gasto_principal&id=' . (int) $fila['id'] . $volver);
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
     * Agrupa en una sola fila los ítems pendientes que comparten dependencia (columna "Origen") —
     * hoy solo para la bandeja "Gastos", donde una misma dependencia suele enviar muchos gastos
     * sueltos a la vez y verlos uno por uno hace la lista muy larga. Cada fila resultante conserva
     * los ítems originales en 'items' para que Aprobar/Archivar/Eliminar actúen sobre todos a la vez
     * (mismo patrón de "seleccionar todo" ya usado en Consolidado por tipo), y "Ver" lleva a una
     * página aparte con el detalle (actividad/insumo) de cada uno.
     */
    private function agruparPendientesPorDependencia(array $pendientes, int $anioPresupuestalId, string $bandeja): array
    {
        $grupos = [];

        foreach ($pendientes as $item) {
            $clave = $item['detalle'];

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'origen' => $item['origen'],
                    'origen_id' => 0,
                    'tipo' => $item['tipo'],
                    'detalle' => $item['detalle'],
                    'cantidad' => 0,
                    'valor' => 0.0,
                    'accion_aprobar' => $item['accion_aprobar'],
                    'accion_rechazar' => $item['accion_rechazar'],
                    'ruta_origen' => $item['ruta_origen'],
                    'ruta_ver' => 'index.php?ruta=peticiones-pendientes-grupo&bandeja=' . urlencode($bandeja)
                        . '&anio_id=' . $anioPresupuestalId . '&dependencia=' . urlencode($clave),
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
     * Página de detalle de un grupo de pendientes (ver agruparPendientesPorDependencia()): lista
     * cada gasto de la dependencia solicitada con su actividad, insumo, cantidad y valor, con un
     * enlace "Ver" por ítem hacia el detalle completo (GastoDetalleControlador).
     */
    public function pendientesGrupo(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $dependencia = trim($_GET['dependencia'] ?? '');
        $anioPresupuestalId = (int) ($_GET['anio_id'] ?? 0);
        $bandeja = ($_GET['bandeja'] ?? '') === 'gastos' ? 'gastos' : null;

        if ($dependencia === '' || $anioPresupuestalId <= 0 || $bandeja === null) {
            header('Location: index.php?ruta=peticiones');
            exit;
        }

        $archivadas = $this->modeloArchivada->obtenerClavesProcesadas();
        $items = [];

        foreach ($this->modeloGasto->obtenerEnviadosPorAnio($anioPresupuestalId) as $fila) {
            if (($fila['tipo_automatico'] ?? null) !== null) {
                continue;
            }

            $dependenciaDestino = $fila['dependencia_destino'] ?? $fila['dependencia'];

            if ($dependenciaDestino !== $dependencia) {
                continue;
            }

            if (isset($archivadas['gasto_principal:' . $fila['id']])) {
                continue;
            }

            $rolDestinatarioId = !empty($fila['rol_destinatario_id']) ? (int) $fila['rol_destinatario_id'] : null;

            if (!$this->visibilidadSolicitud($dependenciaDestino, $rolDestinatarioId)) {
                continue;
            }

            $items[] = [
                'actividad' => $fila['actividad'],
                'insumo' => $fila['insumo'],
                'cantidad' => (int) $fila['cantidad'],
                'valor_total' => (float) $fila['valor_total'],
                'ruta_ver' => 'index.php?ruta=gasto-detalle&origen=gasto_principal&id=' . (int) $fila['id'],
            ];
        }

        $tituloPagina = 'Pendientes — ' . $dependencia;

        require __DIR__ . '/../vista/peticiones/pendientes-grupo.php';
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

        $agregar = function (string $origen, int $origenId, string $tipo, string $destino, ?string $cantidad, ?float $valor, string $rutaVer) use (&$enviadas, $acciones, $redireccionadas): void {
            $clave = $origen . ':' . $origenId;
            $accion = $acciones[$clave] ?? null;

            $estado = match ($accion) {
                'aprobada' => 'Consolidado',
                'archivada' => 'Archivado',
                'redireccionada' => 'Redireccionado a ' . ($redireccionadas[$clave] ?? '—'),
                default => 'Pendiente de revisión',
            };

            $enviadas[] = [
                'origen' => $origen,
                'origen_id' => $origenId,
                'tipo' => $tipo,
                'detalle' => $destino,
                'cantidad' => $cantidad,
                'valor' => $valor,
                'ruta_ver' => $rutaVer,
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
                $agregar('arl', (int) $fila['id'], 'ARL', $fila['enviada_a'] ?? $fila['facultad'], $totalPracticantes . ' practicantes', $totalValor, 'index.php?ruta=solicitud-detalle&tipo=arl&id=' . (int) $fila['id']);
            }
        }

        foreach ($this->modeloMonitor->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            if (!empty($fila['rol_destinatario_id']) && in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                $totalMonitores = (int) $fila['monitores_semestre1'] + (int) $fila['monitores_semestre2'];
                $agregar('monitores', (int) $fila['id'], 'Monitores', $fila['enviada_a'] ?? $fila['dependencia'], $totalMonitores . ' monitores', null, 'index.php?ruta=solicitud-detalle&tipo=monitores&id=' . (int) $fila['id']);
            }
        }

        foreach ($this->modeloOps->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            if (!empty($fila['rol_destinatario_id']) && in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                $totalOps = (float) $fila['valor'] * (int) $fila['cantidad'];
                $agregar('ops', (int) $fila['id'], 'OPS', $fila['enviada_a'] ?? $fila['dependencia'], $fila['cantidad'] . ' und.', $totalOps, 'index.php?ruta=solicitud-detalle&tipo=ops&id=' . (int) $fila['id']);
            }
        }

        foreach ($this->modeloPeticion->obtenerEnviadasPorAnio($anioPresupuestalId) as $fila) {
            if (!empty($fila['rol_destinatario_id']) && (int) ($fila['usuario_id'] ?? 0) === $usuarioActualId) {
                $totalValor = (float) $fila['valor_s1'] + (float) $fila['valor_s2'];
                $agregar('otros', (int) $fila['id'], 'Petición', $fila['enviada_a'] ?? '—', null, $totalValor, 'index.php?ruta=solicitud-detalle&tipo=otros&id=' . (int) $fila['id']);
            }
        }

        foreach ($this->modeloNecesidad->obtenerEnviadas() as $fila) {
            if (!empty($fila['rol_destinatario_id']) && in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                $agregar('necesidad', (int) $fila['id'], 'Perfil de proyectos', $fila['dependencia_destino'] ?? $fila['dependencia'], null, (float) $fila['valor'], 'index.php?ruta=perfil-proyectos');
            }
        }

        $mapaGasto = [
            ['modelo' => $this->modeloGasto, 'origen' => 'gasto_principal', 'tipo' => 'Gasto', 'metodo' => 'obtenerEnviadosPorAnio'],
            ['modelo' => $this->modeloGastoExtension, 'origen' => 'gasto_extension', 'tipo' => 'Extensión', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoPostgrado, 'origen' => 'gasto_postgrado', 'tipo' => 'Postgrado', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoUnisalud, 'origen' => 'gasto_unisalud', 'tipo' => 'Unisalud', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloGastoSinExcedentes, 'origen' => 'gasto_sin_excedentes', 'tipo' => 'Sin excedentes', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoExtension, 'origen' => 'ingreso_extension', 'tipo' => 'Ingreso Extensión', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoPostgrado, 'origen' => 'ingreso_postgrado', 'tipo' => 'Ingreso Postgrado', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoUnisalud, 'origen' => 'ingreso_unisalud', 'tipo' => 'Ingreso Unisalud', 'metodo' => 'obtenerPorAnio'],
            ['modelo' => $this->modeloIngresoSinExcedentes, 'origen' => 'ingreso_sin_excedentes', 'tipo' => 'Ingreso Sin excedentes', 'metodo' => 'obtenerPorAnio'],
        ];

        foreach ($mapaGasto as $fuente) {
            foreach ($fuente['modelo']->{$fuente['metodo']}($anioPresupuestalId) as $fila) {
                if (empty($fila['rol_destinatario_id']) || !in_array($fila['dependencia'], $dependenciasPermitidas, true)) {
                    continue;
                }

                $rutaVer = 'index.php?ruta=gasto-detalle&origen=' . $fuente['origen'] . '&id=' . (int) $fila['id'];
                $cantidad = isset($fila['cantidad']) ? $fila['cantidad'] . ' und.' : null;
                $agregar($fuente['origen'], (int) $fila['id'], $fuente['tipo'], $fila['dependencia_destino'] ?? $fila['dependencia'], $cantidad, (float) $fila['valor_total'], $rutaVer);
            }
        }

        return $enviadas;
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
        $dependenciaDestino = $fila['dependencia_destino'] ?? $fila['dependencia'];

        if (!$this->visibilidadSolicitud($dependenciaDestino, $rolDestinatarioId)) {
            return null;
        }

        return $this->fila($origen, (int) $fila['id'], $tipo, $dependenciaDestino, $fila['cantidad'] . ' und.', (float) $fila['valor_total'], 'gasto', $rutaOrigen, $rutaVer);
    }

    /**
     * Un ingreso de autogestión se maneja igual que un gasto de autogestión: solo lo ve, con
     * acceso completo, el usuario que coincide exactamente con la dependencia y el rol al que
     * fue enviado — nadie más.
     */
    private function filaIngreso(string $origen, array $fila, string $tipo, string $rutaOrigen, string $rutaVer): ?array
    {
        $rolDestinatarioId = !empty($fila['rol_destinatario_id']) ? (int) $fila['rol_destinatario_id'] : null;
        $dependenciaDestino = $fila['dependencia_destino'] ?? $fila['dependencia'];

        if (!$this->visibilidadSolicitud($dependenciaDestino, $rolDestinatarioId)) {
            return null;
        }

        return $this->fila($origen, (int) $fila['id'], $tipo, $dependenciaDestino, null, (float) $fila['valor_total'], 'gasto', $rutaOrigen, $rutaVer);
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
