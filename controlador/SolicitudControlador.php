<?php

require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/SolicitudMonitor.php';
require_once __DIR__ . '/../modelo/SolicitudOps.php';
require_once __DIR__ . '/../modelo/SolicitudPeticion.php';
require_once __DIR__ . '/../modelo/DuplicadorFilas.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/VariableMacroeconomica.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Rubro.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/TipoDependenciaRol.php';
require_once __DIR__ . '/../modelo/PeticionArchivada.php';

class SolicitudControlador
{
    private SolicitudArl $modeloSolicitud;
    private SolicitudMonitor $modeloMonitor;
    private SolicitudOps $modeloOps;
    private SolicitudPeticion $modeloPeticion;
    private AnioPresupuestal $modeloAnio;
    private VariableMacroeconomica $modeloVariable;
    private Sede $modeloSede;
    private Linea $modeloLinea;
    private Motor $modeloMotor;
    private Proyecto $modeloProyecto;
    private Rubro $modeloRubro;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;
    private TipoDependenciaRol $modeloTipoDependenciaRol;

    private const NIVELES_RIESGO = [1, 2, 3, 4, 5];

    private const PORCENTAJES_RIESGO = [
        1 => 0.522,
        2 => 1.044,
        3 => 2.436,
        4 => 4.350,
        5 => 6.960,
    ];

    private const NOMBRE_VARIABLE_SMLV = 'SMMLV';

    private const TABS = ['arl', 'monitores', 'ops', 'otros'];

    private const TIPOS_MONITOR = [
        'solidario_academico' => 'Solidario (académico)',
        'deportivo' => 'Deportivo',
        'cultural' => 'Cultural',
        'administrativo' => 'Administrativo',
    ];

    private const PERFILES_OPS = [
        'profesional_especializado' => 'Profesional especializado',
        'profesional_universitario' => 'Profesional universitario',
        'tecnico_administrativo' => 'Técnico administrativo',
        'asesor' => 'Asesor',
        'auxiliares_administrativos' => 'Auxiliares administrativos',
    ];

    public function __construct()
    {
        $this->modeloSolicitud = new SolicitudArl();
        $this->modeloMonitor = new SolicitudMonitor();
        $this->modeloOps = new SolicitudOps();
        $this->modeloPeticion = new SolicitudPeticion();
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloVariable = new VariableMacroeconomica();
        $this->modeloSede = new Sede();
        $this->modeloLinea = new Linea();
        $this->modeloMotor = new Motor();
        $this->modeloProyecto = new Proyecto();
        $this->modeloRubro = new Rubro();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
        $this->modeloTipoDependenciaRol = new TipoDependenciaRol();
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
        $tab = $this->normalizarTab($_GET['tab'] ?? 'arl');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tab = $this->normalizarTab($_POST['tab'] ?? $tab);
            $accion = $_POST['accion'] ?? 'crear';

            if ($accion === 'eliminar') {
                $this->eliminar();
            } elseif ($accion === 'eliminar_seleccionados') {
                $this->eliminarSeleccionados();
            } elseif ($accion === 'duplicar_seleccionados') {
                $this->duplicarSeleccionados();
            } elseif ($accion === 'eliminar_monitor_seleccionados') {
                $this->eliminarMonitorSeleccionados();
            } elseif ($accion === 'duplicar_monitor_seleccionados') {
                $this->duplicarMonitorSeleccionados();
            } elseif ($accion === 'eliminar_ops_seleccionados') {
                $this->eliminarOpsSeleccionados();
            } elseif ($accion === 'duplicar_ops_seleccionados') {
                $this->duplicarOpsSeleccionados();
            } elseif ($accion === 'eliminar_peticion_seleccionados') {
                $this->eliminarPeticionSeleccionados();
            } elseif ($accion === 'duplicar_peticion_seleccionados') {
                $this->duplicarPeticionSeleccionados();
            } elseif ($accion === 'enviar') {
                [$error, $exito] = $this->enviar();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } elseif ($accion === 'crear_monitor') {
                [$error, $exito] = $this->guardarMonitor();
            } elseif ($accion === 'actualizar_monitor') {
                [$error, $exito] = $this->actualizarMonitor();
            } elseif ($accion === 'eliminar_monitor') {
                $this->eliminarMonitor();
            } elseif ($accion === 'enviar_monitor') {
                [$error, $exito] = $this->enviarMonitor();
            } elseif ($accion === 'crear_ops') {
                [$error, $exito] = $this->guardarOps();
            } elseif ($accion === 'actualizar_ops') {
                [$error, $exito] = $this->actualizarOps();
            } elseif ($accion === 'eliminar_ops') {
                $this->eliminarOps();
            } elseif ($accion === 'enviar_ops') {
                [$error, $exito] = $this->enviarOps();
            } elseif ($accion === 'crear_peticion') {
                [$error, $exito] = $this->guardarPeticion();
            } elseif ($accion === 'actualizar_peticion') {
                [$error, $exito] = $this->actualizarPeticion();
            } elseif ($accion === 'eliminar_peticion') {
                $this->eliminarPeticion();
            } elseif ($accion === 'enviar_peticion') {
                [$error, $exito] = $this->enviarPeticion();
            } else {
                [$error, $exito] = $this->guardar();
            }
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
        $usuarioActualId = (int) ($_SESSION['usuario_id'] ?? 0);

        $solicitudes = $anioSeleccionadoId > 0 ? $this->modeloSolicitud->obtenerPorAnio($anioSeleccionadoId) : [];
        $solicitudes = array_values(array_filter(
            $solicitudes,
            static fn (array $fila): bool => (int) ($fila['usuario_id'] ?? 0) === $usuarioActualId
                || ($fila['usuario_id'] === null && in_array($fila['facultad'], $dependenciasPermitidas, true))
        ));

        $solicitudesMonitores = $anioSeleccionadoId > 0 ? $this->modeloMonitor->obtenerPorAnio($anioSeleccionadoId) : [];
        $solicitudesMonitores = array_values(array_filter(
            $solicitudesMonitores,
            static fn (array $fila): bool => (int) ($fila['usuario_id'] ?? 0) === $usuarioActualId
                || ($fila['usuario_id'] === null && in_array($fila['dependencia'], $dependenciasPermitidas, true))
        ));

        $solicitudesOps = $anioSeleccionadoId > 0 ? $this->modeloOps->obtenerPorAnio($anioSeleccionadoId) : [];
        $solicitudesOps = array_values(array_filter(
            $solicitudesOps,
            static fn (array $fila): bool => (int) ($fila['usuario_id'] ?? 0) === $usuarioActualId
                || ($fila['usuario_id'] === null && in_array($fila['dependencia'], $dependenciasPermitidas, true))
        ));

        $solicitudesPeticiones = $anioSeleccionadoId > 0 ? $this->modeloPeticion->obtenerPorAnio($anioSeleccionadoId) : [];
        $solicitudesPeticiones = array_values(array_filter(
            $solicitudesPeticiones,
            static fn (array $fila): bool => (int) ($fila['usuario_id'] ?? 0) === $usuarioActualId || $fila['usuario_id'] === null
        ));
        $smlvPorAnio = $this->modeloVariable->obtenerValoresPorNombre(self::NOMBRE_VARIABLE_SMLV);
        $porcentajesRiesgo = self::PORCENTAJES_RIESGO;
        $tiposMonitor = self::TIPOS_MONITOR;
        $perfilesOps = self::PERFILES_OPS;
        $sedes = $this->modeloSede->obtenerTodas();
        $lineas = $this->modeloLinea->obtenerTodas();
        $motores = $this->modeloMotor->obtenerTodos();
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $rubros = $this->modeloRubro->obtenerActivosPorCategoria('egresos');
        $dependenciasSugeridas = $this->modeloDependencia->obtenerActivasParaEnvio();
        $roles = $this->modeloRol->obtenerTodos();
        $rolesPorTipo = $this->modeloTipoDependenciaRol->obtenerMapaCompleto();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();

        $arlParaEditar = null;
        $monitorParaEditar = null;
        $opsParaEditar = null;
        $peticionParaEditar = null;
        if (isset($_GET['editar_id']) && ctype_digit((string) $_GET['editar_id'])) {
            $editarIdSolicitud = (int) $_GET['editar_id'];
            $tipoSolicitud = $_GET['tipo_solicitud'] ?? '';
            if ($tipoSolicitud === 'arl') {
                $arlParaEditar = $this->modeloSolicitud->obtenerPorId($editarIdSolicitud);
            } elseif ($tipoSolicitud === 'monitores') {
                $monitorParaEditar = $this->modeloMonitor->obtenerPorId($editarIdSolicitud);
            } elseif ($tipoSolicitud === 'ops') {
                $opsParaEditar = $this->modeloOps->obtenerPorId($editarIdSolicitud);
            } elseif ($tipoSolicitud === 'otros') {
                $peticionParaEditar = $this->modeloPeticion->obtenerPorId($editarIdSolicitud);
            }
        }
        $volverEdicion = $_GET['volver'] ?? '';
        $tipoSolicitudEdicion = $_GET['tipo_solicitud'] ?? '';
        $modoEdicion = $arlParaEditar !== null || $monitorParaEditar !== null || $opsParaEditar !== null || $peticionParaEditar !== null;

        require __DIR__ . '/../vista/solicitudes/index.php';
    }

    private function normalizarTab(string $tab): string
    {
        return in_array($tab, self::TABS, true) ? $tab : 'arl';
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

    private function guardar(): array
    {
        [$error, $datos] = $this->calcularDatos();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloSolicitud->crear($datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar la facultad. Verifica el año presupuestal seleccionado.', ''];
        }

        return ['', 'Facultad agregada correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloSolicitud->obtenerPorId($id) === null) {
            return ['La solicitud que intentas editar no existe.', ''];
        }

        [$error, $datos] = $this->calcularDatos();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloSolicitud->actualizar($id, $datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar la solicitud. Verifica el año presupuestal seleccionado.', ''];
        }

        $valorTotalArl = 0.0;
        foreach (self::NIVELES_RIESGO as $nivel) {
            $valorTotalArl += (float) $datos['riesgo' . $nivel . '_valor'];
        }
        (new PeticionArchivada())->sincronizarDesdeOrigen('arl', $id, $valorTotalArl, $datos['facultad']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=solicitudes&tab=arl&anio_id=' . $datos['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function validarRolDestinatario(): array
    {
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if ($rolDestinatarioId <= 0 || $this->modeloRol->obtenerPorId($rolDestinatarioId) === null) {
            return ['Selecciona el rol al que se enviará la solicitud.', 0];
        }

        return ['', $rolDestinatarioId];
    }

    private function calcularDatos(): array
    {
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $facultad = trim($_POST['facultad'] ?? '');

        if ($anioPresupuestalId <= 0 || $facultad === '') {
            return ['El año presupuestal y la facultad son obligatorios.', []];
        }

        [$errorRol, $rolDestinatarioId] = $this->validarRolDestinatario();

        if ($errorRol !== '') {
            return [$errorRol, []];
        }

        $variableSmlv = $this->modeloVariable->obtenerPorNombreYAnio(self::NOMBRE_VARIABLE_SMLV, $anioPresupuestalId);

        if ($variableSmlv === null) {
            return [
                'Primero debes registrar la variable macroeconómica "SMMLV" para el año seleccionado en Configuraciones > Variables macroeconómicas.',
                [],
            ];
        }

        $smlv = (float) $variableSmlv['valor'];

        $datos = [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'facultad' => $facultad,
            'rol_destinatario_id' => $rolDestinatarioId,
            'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
        ];

        foreach (self::NIVELES_RIESGO as $nivel) {
            $estudiantes = $_POST['riesgo' . $nivel . '_estudiantes'] ?? '0';

            if ($estudiantes === '') {
                $estudiantes = '0';
            }

            if (!is_numeric($estudiantes) || (int) $estudiantes < 0) {
                return ['El número de estudiantes de cada riesgo debe ser un entero válido mayor o igual a 0.', []];
            }

            $estudiantes = (int) $estudiantes;

            $datos['riesgo' . $nivel . '_estudiantes'] = $estudiantes;
            $datos['riesgo' . $nivel . '_valor'] = round($estudiantes * $smlv * (self::PORCENTAJES_RIESGO[$nivel] / 100) * 12, 2);
        }

        return ['', $datos];
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloSolicitud->eliminar($id);
        }

        header('Location: index.php?ruta=solicitudes&tab=arl');
        exit;
    }

    private function eliminarSeleccionados(): void
    {
        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0 && $this->modeloSolicitud->obtenerPorId($id) !== null) {
                $this->modeloSolicitud->eliminar($id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=arl');
        exit;
    }

    private function duplicarSeleccionados(): void
    {
        $db = Conexion::obtener();

        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0) {
                DuplicadorFilas::duplicarFila($db, 'solicitudes_arl', $id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=arl');
        exit;
    }

    private function notificarYMarcarEnviada(
        object $modelo,
        int $id,
        ?int $rolDestinatarioId,
        ?string $dependenciaNombre,
        string $asunto,
        string $cuerpo
    ): array {
        if ($rolDestinatarioId === null) {
            return ['Esta solicitud no tiene un rol destinatario asignado.', ''];
        }

        $rol = $this->modeloRol->obtenerPorId($rolDestinatarioId);

        if ($rol === null) {
            return ['El rol destinatario configurado ya no existe.', ''];
        }

        if ($dependenciaNombre !== null) {
            $dependencia = $this->modeloDependencia->obtenerPorNombre($dependenciaNombre);
            $destinatarios = $dependencia !== null
                ? $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependencia['id'], $rolDestinatarioId)
                : [];
        } else {
            $destinatarios = $this->modeloUsuario->obtenerPorRolId($rolDestinatarioId);
        }

        if (empty($destinatarios)) {
            $ubicacion = $dependenciaNombre !== null ? ' en "' . $dependenciaNombre . '"' : '';

            return ['No se encontró ningún usuario con el rol "' . $rol['nombre'] . '"' . $ubicacion . ' para notificar.', ''];
        }

        // El filtro por destinatario específico solo aplica cuando el envío está acotado a una
        // dependencia (ahí sí tiene sentido "elegir a cuál Gestor/Avalador"). Para 'otros' (sin
        // dependencia, dirigida a todos los que tengan ese rol en toda la universidad) se mantiene
        // el envío masivo original: no tendría sentido pedir elegir uno entre decenas.
        if ($dependenciaNombre !== null && count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear($remitenteId, (int) $destinatario['id'], $asunto, $cuerpo);
        }

        $modelo->enviar($id, $rol['nombre']);

        $mensajeDestino = count($destinatarios) === 1
            ? $destinatarios[0]['nombre']
            : count($destinatarios) . ' usuario(s) con el rol "' . $rol['nombre'] . '"';

        return ['', 'Solicitud enviada a ' . $mensajeDestino . '.'];
    }

    private function enviar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $solicitud = $id > 0 ? $this->modeloSolicitud->obtenerPorId($id) : null;

        if ($solicitud === null) {
            return ['La solicitud que intentas enviar no existe.', ''];
        }

        return $this->notificarYMarcarEnviada(
            $this->modeloSolicitud,
            $id,
            $solicitud['rol_destinatario_id'] !== null ? (int) $solicitud['rol_destinatario_id'] : null,
            $solicitud['facultad'],
            'Nueva solicitud ARL — ' . $solicitud['facultad'],
            'Se registró una solicitud de ARL de estudiantes en prácticas para "' . $solicitud['facultad'] . '".'
        );
    }

    private function guardarMonitor(): array
    {
        [$error, $datos] = $this->calcularDatosMonitor();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloMonitor->crear($datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar la solicitud de monitores. Verifica el año presupuestal seleccionado.', ''];
        }

        return ['', 'Solicitud de monitores agregada correctamente.'];
    }

    private function actualizarMonitor(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloMonitor->obtenerPorId($id) === null) {
            return ['La solicitud que intentas editar no existe.', ''];
        }

        [$error, $datos] = $this->calcularDatosMonitor();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloMonitor->actualizar($id, $datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar la solicitud de monitores. Verifica el año presupuestal seleccionado.', ''];
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen('monitores', $id, null, $datos['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=solicitudes&tab=monitores&anio_id=' . $datos['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function calcularDatosMonitor(): array
    {
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $dependencia = trim($_POST['dependencia'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $semestre1 = $_POST['monitores_semestre1'] ?? '0';
        $semestre2 = $_POST['monitores_semestre2'] ?? '0';

        if ($anioPresupuestalId <= 0 || $dependencia === '') {
            return ['El año presupuestal y la dependencia son obligatorios.', []];
        }

        if (!array_key_exists($tipo, self::TIPOS_MONITOR)) {
            return ['Selecciona un tipo de monitor válido.', []];
        }

        [$errorRol, $rolDestinatarioId] = $this->validarRolDestinatario();

        if ($errorRol !== '') {
            return [$errorRol, []];
        }

        if ($semestre1 === '') {
            $semestre1 = '0';
        }

        if ($semestre2 === '') {
            $semestre2 = '0';
        }

        if (!is_numeric($semestre1) || (int) $semestre1 < 0 || !is_numeric($semestre2) || (int) $semestre2 < 0) {
            return ['El número de monitores por semestre debe ser un entero válido mayor o igual a 0.', []];
        }

        return ['', [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia' => $dependencia,
            'rol_destinatario_id' => $rolDestinatarioId,
            'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
            'tipo' => $tipo,
            'monitores_semestre1' => (int) $semestre1,
            'monitores_semestre2' => (int) $semestre2,
        ]];
    }

    private function eliminarMonitor(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloMonitor->eliminar($id);
        }

        header('Location: index.php?ruta=solicitudes&tab=monitores');
        exit;
    }

    private function eliminarMonitorSeleccionados(): void
    {
        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0 && $this->modeloMonitor->obtenerPorId($id) !== null) {
                $this->modeloMonitor->eliminar($id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=monitores');
        exit;
    }

    private function duplicarMonitorSeleccionados(): void
    {
        $db = Conexion::obtener();

        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0) {
                DuplicadorFilas::duplicarFila($db, 'solicitudes_monitores', $id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=monitores');
        exit;
    }

    private function enviarMonitor(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $solicitud = $id > 0 ? $this->modeloMonitor->obtenerPorId($id) : null;

        if ($solicitud === null) {
            return ['La solicitud que intentas enviar no existe.', ''];
        }

        return $this->notificarYMarcarEnviada(
            $this->modeloMonitor,
            $id,
            $solicitud['rol_destinatario_id'] !== null ? (int) $solicitud['rol_destinatario_id'] : null,
            $solicitud['dependencia'],
            'Nueva solicitud de monitores — ' . $solicitud['dependencia'],
            'Se registró una solicitud de monitores para "' . $solicitud['dependencia'] . '".'
        );
    }

    private function guardarOps(): array
    {
        [$error, $datos] = $this->calcularDatosOps();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloOps->crear($datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar la solicitud OPS. Verifica los datos seleccionados.', ''];
        }

        return ['', 'Solicitud OPS agregada correctamente.'];
    }

    private function actualizarOps(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloOps->obtenerPorId($id) === null) {
            return ['La solicitud que intentas editar no existe.', ''];
        }

        [$error, $datos] = $this->calcularDatosOps();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloOps->actualizar($id, $datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar la solicitud OPS. Verifica los datos seleccionados.', ''];
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen('ops', $id, $datos['valor'] * $datos['cantidad'], $datos['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=solicitudes&tab=ops&anio_id=' . $datos['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function calcularDatosOps(): array
    {
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $sedeId = (int) ($_POST['sede_id'] ?? 0);
        $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
        $dependencia = trim($_POST['dependencia'] ?? '');
        $rubroId = (int) ($_POST['rubro_id'] ?? 0);
        $perfil = $_POST['perfil'] ?? '';
        $valor = $_POST['valor'] ?? '';
        $cantidad = $_POST['cantidad'] ?? '';
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($anioPresupuestalId <= 0 || $sedeId <= 0 || $proyectoId <= 0
            || $dependencia === '' || $rubroId <= 0) {
            return ['Todos los campos son obligatorios, excepto observaciones.', []];
        }

        $proyectoPdi = $this->modeloProyecto->obtenerPorId($proyectoId);

        if ($proyectoPdi === null) {
            return ['Selecciona un proyecto PDI válido.', []];
        }

        $lineaId = (int) $proyectoPdi['linea_id'];
        $motorId = (int) $proyectoPdi['motor_id'];

        if (!array_key_exists($perfil, self::PERFILES_OPS)) {
            return ['Selecciona un perfil válido.', []];
        }

        [$errorRol, $rolDestinatarioId] = $this->validarRolDestinatario();

        if ($errorRol !== '') {
            return [$errorRol, []];
        }

        if ($valor === '' || !is_numeric($valor) || (float) $valor < 0) {
            return ['El valor debe ser un número válido mayor o igual a 0.', []];
        }

        if (!is_numeric($cantidad) || (int) $cantidad <= 0) {
            return ['La cantidad debe ser un número entero mayor a 0.', []];
        }

        return ['', [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'sede_id' => $sedeId,
            'linea_id' => $lineaId,
            'motor_id' => $motorId,
            'proyecto_id' => $proyectoId,
            'dependencia' => $dependencia,
            'rol_destinatario_id' => $rolDestinatarioId,
            'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
            'rubro_id' => $rubroId,
            'perfil' => $perfil,
            'valor' => (float) $valor,
            'cantidad' => (int) $cantidad,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ]];
    }

    private function eliminarOps(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloOps->eliminar($id);
        }

        header('Location: index.php?ruta=solicitudes&tab=ops');
        exit;
    }

    private function eliminarOpsSeleccionados(): void
    {
        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0 && $this->modeloOps->obtenerPorId($id) !== null) {
                $this->modeloOps->eliminar($id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=ops');
        exit;
    }

    private function duplicarOpsSeleccionados(): void
    {
        $db = Conexion::obtener();

        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0) {
                DuplicadorFilas::duplicarFila($db, 'solicitudes_ops', $id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=ops');
        exit;
    }

    private function enviarOps(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $solicitud = $id > 0 ? $this->modeloOps->obtenerPorId($id) : null;

        if ($solicitud === null) {
            return ['La solicitud que intentas enviar no existe.', ''];
        }

        return $this->notificarYMarcarEnviada(
            $this->modeloOps,
            $id,
            $solicitud['rol_destinatario_id'] !== null ? (int) $solicitud['rol_destinatario_id'] : null,
            $solicitud['dependencia'],
            'Nueva solicitud OPS — ' . $solicitud['dependencia'],
            'Se registró una solicitud OPS para "' . $solicitud['dependencia'] . '".'
        );
    }

    private function guardarPeticion(): array
    {
        [$error, $datos] = $this->calcularDatosPeticion();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloPeticion->crear($datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar la petición. Verifica el año presupuestal seleccionado.', ''];
        }

        return ['', 'Petición agregada correctamente.'];
    }

    private function actualizarPeticion(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloPeticion->obtenerPorId($id) === null) {
            return ['La petición que intentas editar no existe.', ''];
        }

        [$error, $datos] = $this->calcularDatosPeticion();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloPeticion->actualizar($id, $datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar la petición. Verifica el año presupuestal seleccionado.', ''];
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen('otros', $id, $datos['valor_s1'] + $datos['valor_s2'], $datos['concepto']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=solicitudes&tab=otros&anio_id=' . $datos['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function calcularDatosPeticion(): array
    {
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $concepto = trim($_POST['concepto'] ?? '');
        $semestre1 = $_POST['semestre1'] ?? '0';
        $valorS1 = $_POST['valor_s1'] ?? '0';
        $semestre2 = $_POST['semestre2'] ?? '0';
        $valorS2 = $_POST['valor_s2'] ?? '0';

        if ($anioPresupuestalId <= 0 || $concepto === '') {
            return ['El año presupuestal y el concepto son obligatorios.', []];
        }

        [$errorRol, $rolDestinatarioId] = $this->validarRolDestinatario();

        if ($errorRol !== '') {
            return [$errorRol, []];
        }

        foreach (['semestre1' => $semestre1, 'semestre2' => $semestre2] as $campo => $valor) {
            if ($valor === '') {
                $valor = '0';
            }

            if (!is_numeric($valor) || (int) $valor < 0) {
                return ['Los campos de semestre deben ser números enteros mayores o iguales a 0.', []];
            }
        }

        foreach (['valor_s1' => $valorS1, 'valor_s2' => $valorS2] as $campo => $valor) {
            if ($valor === '') {
                $valor = '0';
            }

            if (!is_numeric($valor) || (float) $valor < 0) {
                return ['Los valores por semestre deben ser números válidos mayores o iguales a 0.', []];
            }
        }

        return ['', [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'concepto' => $concepto,
            'rol_destinatario_id' => $rolDestinatarioId,
            'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
            'semestre1' => (int) ($semestre1 === '' ? 0 : $semestre1),
            'valor_s1' => (float) ($valorS1 === '' ? 0 : $valorS1),
            'semestre2' => (int) ($semestre2 === '' ? 0 : $semestre2),
            'valor_s2' => (float) ($valorS2 === '' ? 0 : $valorS2),
        ]];
    }

    private function eliminarPeticion(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloPeticion->eliminar($id);
        }

        header('Location: index.php?ruta=solicitudes&tab=otros');
        exit;
    }

    private function eliminarPeticionSeleccionados(): void
    {
        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0 && $this->modeloPeticion->obtenerPorId($id) !== null) {
                $this->modeloPeticion->eliminar($id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=otros');
        exit;
    }

    private function duplicarPeticionSeleccionados(): void
    {
        $db = Conexion::obtener();

        foreach (array_map('intval', $_POST['id'] ?? []) as $id) {
            if ($id > 0) {
                DuplicadorFilas::duplicarFila($db, 'solicitudes_peticiones', $id);
            }
        }

        header('Location: index.php?ruta=solicitudes&tab=otros');
        exit;
    }

    private function enviarPeticion(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $solicitud = $id > 0 ? $this->modeloPeticion->obtenerPorId($id) : null;

        if ($solicitud === null) {
            return ['La petición que intentas enviar no existe.', ''];
        }

        return $this->notificarYMarcarEnviada(
            $this->modeloPeticion,
            $id,
            $solicitud['rol_destinatario_id'] !== null ? (int) $solicitud['rol_destinatario_id'] : null,
            null,
            'Nueva petición — ' . $solicitud['concepto'],
            'Se registró una petición: "' . $solicitud['concepto'] . '".'
        );
    }
}
