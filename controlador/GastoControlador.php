<?php

require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/DuplicadorFilas.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Rubro.php';
require_once __DIR__ . '/../modelo/ContratoComun.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/MenuPermiso.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/TipoDependenciaRol.php';
require_once __DIR__ . '/../modelo/PeticionArchivada.php';

class GastoControlador
{
    private Gasto $modeloGasto;
    private Linea $modeloLinea;
    private Motor $modeloMotor;
    private Proyecto $modeloProyecto;
    private Rubro $modeloRubro;
    private ContratoComun $modeloContratoComun;
    private AnioPresupuestal $modeloAnio;
    private Sede $modeloSede;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;
    private PresupuestoDependencia $modeloPresupuestoDependencia;
    private MenuPermiso $modeloMenuPermiso;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;
    private TipoDependenciaRol $modeloTipoDependenciaRol;

    private const CAMPOS_REQUERIDOS = [
        'sede_id',
        'anio_presupuestal_id',
        'dependencia',
        'proyecto_id',
        'actividad',
        'rubro_id',
        'insumo',
        'cantidad',
        'costo_unitario',
    ];

    public function __construct()
    {
        $this->modeloGasto = new Gasto();
        $this->modeloLinea = new Linea();
        $this->modeloMotor = new Motor();
        $this->modeloProyecto = new Proyecto();
        $this->modeloRubro = new Rubro();
        $this->modeloContratoComun = new ContratoComun();
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloSede = new Sede();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloPresupuestoDependencia = new PresupuestoDependencia();
        $this->modeloMenuPermiso = new MenuPermiso();
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
        $accion = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminar();
            } elseif ($accion === 'eliminar_seleccionados') {
                [$error, $exito] = $this->eliminarSeleccionados();
            } elseif ($accion === 'duplicar_seleccionados') {
                [$error, $exito] = $this->duplicarSeleccionados();
            } elseif ($accion === 'enviar_todo') {
                [$error, $exito] = $this->enviarTodo();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $lineas = $this->modeloLinea->obtenerTodas();
        $motores = $this->modeloMotor->obtenerTodos();
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $rubros = $this->modeloRubro->obtenerActivosPorCategoria('egresos');
        $contratosComunes = $this->modeloContratoComun->obtenerActivos();
        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $sedes = $this->modeloSede->obtenerTodas();

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;
        $dependenciaUsuario = $dependenciaUsuarioId !== null ? $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId) : null;
        $dependenciaUsuarioEsRaiz = $dependenciaUsuario !== null && !empty($dependenciaUsuario['es_raiz_superadmin']);

        $dependenciasSugeridas = [];
        $dependenciasConTipo = [];
        $tieneHijas = false;
        $dependenciaPorDefecto = null;

        if ($dependenciaUsuario !== null) {
            $descendientes = $this->modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId);
            $tieneHijas = !empty($descendientes);

            if (!$dependenciaUsuarioEsRaiz) {
                $dependenciasSugeridas[] = $dependenciaUsuario['nombre'];
                $dependenciaPorDefecto = $dependenciaUsuario['nombre'];
                $dependenciasConTipo[] = ['nombre' => $dependenciaUsuario['nombre'], 'tipo' => $dependenciaUsuario['tipo'] ?? ''];
            }

            foreach ($descendientes as $descendiente) {
                if (!empty($descendiente['es_raiz_superadmin'])) {
                    continue;
                }

                $dependenciasSugeridas[] = $descendiente['nombre'];
                $dependenciasConTipo[] = ['nombre' => $descendiente['nombre'], 'tipo' => $descendiente['tipo'] ?? ''];
            }
        }

        $anioSeleccionadoId = 0;

        if (!empty($aniosActivos)) {
            $anioSeleccionadoId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) $aniosActivos[0]['id'];

            $idsValidos = array_map('intval', array_column($aniosActivos, 'id'));
            if (!in_array($anioSeleccionadoId, $idsValidos, true)) {
                $anioSeleccionadoId = (int) $aniosActivos[0]['id'];
            }
        }

        $gastos = $anioSeleccionadoId > 0 ? $this->modeloGasto->obtenerPorAnio($anioSeleccionadoId) : [];
        $gastos = $this->filtrarPorPropietarioODestinatario($gastos, $usuarioActual, $dependenciasSugeridas);

        $gastoParaEditar = null;
        if (isset($_GET['editar_id']) && ctype_digit((string) $_GET['editar_id'])) {
            $gastoParaEditar = $this->modeloGasto->obtenerPorId((int) $_GET['editar_id']);
        }
        $volverEdicion = $_GET['volver'] ?? '';

        $anioSeleccionado = null;
        foreach ($aniosActivos as $anioFila) {
            if ((int) $anioFila['id'] === $anioSeleccionadoId) {
                $anioSeleccionado = $anioFila;
                break;
            }
        }

        $menuPermitido = $usuarioActual !== null ? $this->modeloMenuPermiso->calcularPermitidoParaUsuario($usuarioActual) : null;
        $puedeVerTechos = $menuPermitido === null || in_array('techos', $menuPermitido, true);

        $techoDependencia = null;
        if ($dependenciaUsuarioId !== null && $anioSeleccionadoId > 0) {
            if ($dependenciaUsuarioEsRaiz) {
                $techoDependencia = $anioSeleccionado !== null ? (float) $anioSeleccionado['presupuesto'] : null;
            } else {
                $presupuestosDependencia = $this->modeloPresupuestoDependencia->obtenerPorAnio($anioSeleccionadoId);
                $techoDependencia = $presupuestosDependencia[$dependenciaUsuarioId]['techo'] ?? null;
                $techoDependencia = $techoDependencia !== null ? (float) $techoDependencia : null;
            }
        }

        $totalGastado = ($dependenciaUsuario !== null && $anioSeleccionadoId > 0)
            ? $this->modeloGasto->obtenerTotalEjecutadoPorAnioYDependencia($anioSeleccionadoId, $dependenciaUsuario['nombre'])
            : 0.0;
        $presupuestoAnio = $techoDependencia ?? 0.0;
        $porcentajeGastado = $presupuestoAnio > 0 ? min(100, ($totalGastado / $presupuestoAnio) * 100) : 0.0;
        $puedeEnviarTodo = $presupuestoAnio > 0 && $totalGastado >= $presupuestoAnio;

        $roles = $this->modeloRol->obtenerTodos();
        $rolesPorTipo = $this->modeloTipoDependenciaRol->obtenerMapaCompleto();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();

        $diaActual = (int) date('z') + 1;
        $totalDiasAnio = date('L') ? 366 : 365;

        require __DIR__ . '/../vista/gastos/index.php';
    }

    private function guardar(): array
    {
        [$datos, $error] = $this->validarDatos();

        if ($error !== '') {
            return [$error, ''];
        }

        $errorDependencia = $this->validarDependenciaPermitida($datos['dependencia']);

        if ($errorDependencia !== '') {
            return [$errorDependencia, ''];
        }

        $errorTecho = $this->validarLimiteTecho($datos);

        if ($errorTecho !== '') {
            return [$errorTecho, ''];
        }

        try {
            $this->modeloGasto->crear($datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar el gasto. Verifica el año, la sede, la línea, el motor, el proyecto y el rubro seleccionados.', ''];
        }

        return ['', 'Gasto registrado correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloGasto->obtenerPorId($id) === null) {
            return ['El gasto que intentas editar no existe.', ''];
        }

        [$datos, $error] = $this->validarDatos();

        if ($error !== '') {
            return [$error, ''];
        }

        $errorDependencia = $this->validarDependenciaPermitida($datos['dependencia']);

        if ($errorDependencia !== '') {
            return [$errorDependencia, ''];
        }

        $errorTecho = $this->validarLimiteTecho($datos, $id);

        if ($errorTecho !== '') {
            return [$errorTecho, ''];
        }

        try {
            $this->modeloGasto->actualizar($id, $datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar el gasto. Verifica el año, la sede, la línea, el motor, el proyecto y el rubro seleccionados.', ''];
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen(
            'gasto_principal',
            $id,
            $datos['cantidad'] * $datos['costo_unitario'],
            $datos['dependencia']
        );

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=gastos&anio_id=' . $datos['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
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

        $permitidas = [];

        if (empty($dependenciaUsuario['es_raiz_superadmin'])) {
            $permitidas[] = $dependenciaUsuario['nombre'];
        }

        foreach ($this->modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId) as $descendiente) {
            if (!empty($descendiente['es_raiz_superadmin'])) {
                continue;
            }

            $permitidas[] = $descendiente['nombre'];
        }

        return $permitidas;
    }

    /**
     * Un gasto solo debe ser visible, en su propio listado, para quien lo creó o para quien
     * coincide exactamente con la dependencia y el rol al que fue enviado — nadie más lo ve,
     * ni siquiera un nivel superior de la jerarquía, hasta que se le envía explícitamente.
     * Los gastos anteriores a este control (sin usuario_id registrado) se mantienen visibles
     * por dependencia, para no ocultar información ya existente.
     */
    private function filtrarPorPropietarioODestinatario(array $items, ?array $usuarioActual, array $dependenciasPermitidas): array
    {
        $usuarioActualId = (int) ($usuarioActual['id'] ?? 0);
        $dependenciaUsuarioNombre = null;

        if (!empty($usuarioActual['dependencia_id'])) {
            $dependenciaFila = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);
            $dependenciaUsuarioNombre = $dependenciaFila['nombre'] ?? null;
        }

        $rolUsuarioId = !empty($usuarioActual['rol_id']) ? (int) $usuarioActual['rol_id'] : null;

        return array_values(array_filter($items, static function (array $item) use ($usuarioActualId, $dependenciaUsuarioNombre, $rolUsuarioId, $dependenciasPermitidas): bool {
            if ($item['usuario_id'] === null) {
                return in_array($item['dependencia'], $dependenciasPermitidas, true);
            }

            if ($usuarioActualId > 0 && (int) $item['usuario_id'] === $usuarioActualId) {
                return true;
            }

            return $item['estado'] === 'enviado'
                && $rolUsuarioId !== null
                && (int) ($item['rol_destinatario_id'] ?? 0) === $rolUsuarioId
                && $item['dependencia_destino'] === $dependenciaUsuarioNombre;
        }));
    }

    /**
     * Si $dependencia es de tipo "Dumi" (una etiqueta de distribución interna, sin usuarios ni
     * rol propios), sube por flujo_id hasta encontrar la dependencia real que la remite.
     */
    private function resolverDependenciaRemitente(array $dependencia): array
    {
        $limite = 10;

        while (($dependencia['tipo'] ?? '') === 'Dumi' && !empty($dependencia['flujo_id']) && $limite-- > 0) {
            $padre = $this->modeloDependencia->obtenerPorId((int) $dependencia['flujo_id']);

            if ($padre === null) {
                break;
            }

            $dependencia = $padre;
        }

        return $dependencia;
    }

    private function validarDependenciaPermitida(string $dependenciaNombre): string
    {
        if (!in_array($dependenciaNombre, $this->obtenerDependenciasPermitidas(), true)) {
            return 'La dependencia seleccionada no está disponible para tu usuario.';
        }

        return '';
    }

    private function validarLimiteTecho(array $datos, int $idExcluir = 0): string
    {
        $dependenciaObjetivo = $this->modeloDependencia->obtenerPorNombre($datos['dependencia']);

        if ($dependenciaObjetivo === null || !empty($dependenciaObjetivo['es_raiz_superadmin'])) {
            return '';
        }

        $dependenciaObjetivoId = (int) $dependenciaObjetivo['id'];

        $presupuestosDependencia = $this->modeloPresupuestoDependencia->obtenerPorAnio($datos['anio_presupuestal_id']);
        $techoDependencia = $presupuestosDependencia[$dependenciaObjetivoId]['techo'] ?? null;
        $techoDependencia = $techoDependencia !== null ? (float) $techoDependencia : 0.0;

        $totalActual = $this->modeloGasto->obtenerTotalEjecutadoPorAnioYDependencia($datos['anio_presupuestal_id'], $dependenciaObjetivo['nombre']);

        if ($idExcluir > 0) {
            $existente = $this->modeloGasto->obtenerPorId($idExcluir);

            if ($existente !== null) {
                $totalActual -= (float) $existente['valor_total'];
            }
        }

        $nuevoValor = $datos['cantidad'] * $datos['costo_unitario'];

        if ($totalActual + $nuevoValor > $techoDependencia) {
            $disponible = max(0, $techoDependencia - $totalActual);

            return 'Este gasto supera el techo presupuestal de "' . $dependenciaObjetivo['nombre'] . '". Disponible: ' . number_format($disponible, 2) . '.';
        }

        return '';
    }

    private function eliminar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloGasto->obtenerPorId($id) === null) {
            return ['El gasto que intentas eliminar no existe.', ''];
        }

        $this->modeloGasto->eliminar($id);

        return ['', 'Gasto eliminado correctamente.'];
    }

    private function eliminarSeleccionados(): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $eliminados = 0;

        foreach ($ids as $id) {
            if ($id > 0 && $this->modeloGasto->obtenerPorId($id) !== null) {
                $this->modeloGasto->eliminar($id);
                $eliminados++;
            }
        }

        if ($eliminados === 0) {
            return ['No se eliminó ningún gasto.', ''];
        }

        return ['', 'Se eliminaron ' . $eliminados . ' gasto(s).'];
    }

    private function duplicarSeleccionados(): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $db = Conexion::obtener();
        $duplicados = 0;

        foreach ($ids as $id) {
            if ($id > 0 && DuplicadorFilas::duplicarFila($db, 'gastos', $id) !== null) {
                $duplicados++;
            }
        }

        if ($duplicados === 0) {
            return ['No se duplicó ningún gasto.', ''];
        }

        return ['', 'Se duplicaron ' . $duplicados . ' gasto(s).'];
    }

    private function enviarTodo(): array
    {
        $anioId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $dependenciaNombre = trim($_POST['dependencia'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if ($anioId <= 0 || $dependenciaNombre === '' || $rolDestinatarioId <= 0) {
            return ['Selecciona la dependencia y el rol al que se enviarán los gastos.', ''];
        }

        $errorDependencia = $this->validarDependenciaPermitida($dependenciaNombre);

        if ($errorDependencia !== '') {
            return [$errorDependencia, ''];
        }

        $rol = $this->modeloRol->obtenerPorId($rolDestinatarioId);

        if ($rol === null) {
            return ['El rol seleccionado no existe.', ''];
        }

        // Una dependencia tipo "Dumi" (ej. "CONCURSO DOCENTE") es solo una etiqueta interna sin
        // usuarios propios: si se elige como remitente, se sustituye por la dependencia real que
        // la contiene, y sus propios gastos se envían también bajo esa dependencia real.
        $dependenciaObjetivo = $this->modeloDependencia->obtenerPorNombre($dependenciaNombre);
        $dependenciaObjetivo = $dependenciaObjetivo !== null ? $this->resolverDependenciaRemitente($dependenciaObjetivo) : null;
        if ($dependenciaObjetivo !== null) {
            $dependenciaNombre = $dependenciaObjetivo['nombre'];
        }

        $nombresDumi = [];
        if ($dependenciaObjetivo !== null) {
            foreach ($this->modeloDependencia->obtenerHijasDirectas((int) $dependenciaObjetivo['id']) as $hija) {
                if (($hija['tipo'] ?? '') === 'Dumi') {
                    $nombresDumi[] = $hija['nombre'];
                }
            }
        }

        $destinatarios = $dependenciaObjetivo !== null
            ? $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependenciaObjetivo['id'], $rolDestinatarioId)
            : [];

        if (count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        $enviados = $this->modeloGasto->enviarTodosBorrador($anioId, $dependenciaNombre, $rolDestinatarioId, $nombresDumi);

        if ($enviados === 0) {
            return ['No hay gastos en borrador para enviar en "' . $dependenciaNombre . '".', ''];
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear(
                $remitenteId,
                (int) $destinatario['id'],
                'Gastos enviados — ' . $dependenciaNombre,
                'Se enviaron ' . $enviados . ' gasto(s) de "' . $dependenciaNombre . '" para tu revisión.'
            );
        }

        if (empty($destinatarios)) {
            return ['', 'Se enviaron ' . $enviados . ' gasto(s), pero no se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaNombre . '" para notificar.'];
        }

        return ['', 'Se enviaron ' . $enviados . ' gasto(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ').'];
    }

    private function validarDatos(): array
    {
        $datos = [];

        foreach ($_POST as $campo => $valor) {
            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        foreach (self::CAMPOS_REQUERIDOS as $campo) {
            if (($datos[$campo] ?? '') === '') {
                return [[], 'Todos los campos son obligatorios.'];
            }
        }

        if (!is_numeric($datos['cantidad']) || (int) $datos['cantidad'] <= 0) {
            return [[], 'La cantidad debe ser un número entero mayor a 0.'];
        }

        if (!is_numeric($datos['costo_unitario']) || (float) $datos['costo_unitario'] < 0) {
            return [[], 'El costo unitario debe ser un número válido.'];
        }

        $meses = array_filter(
            array_map('intval', $datos['meses'] ?? []),
            static fn (int $mes): bool => $mes >= 1 && $mes <= 12
        );

        if (empty($meses)) {
            return [[], 'Selecciona al menos un mes de ejecución.'];
        }

        $meses = array_unique($meses);
        sort($meses);
        $datos['meses'] = implode(',', $meses);

        $proyectoPdi = $this->modeloProyecto->obtenerPorId((int) $datos['proyecto_id']);

        if ($proyectoPdi === null) {
            return [[], 'Selecciona un proyecto PDI válido.'];
        }

        $datos['anio_presupuestal_id'] = (int) $datos['anio_presupuestal_id'];
        $datos['sede_id'] = (int) $datos['sede_id'];
        $datos['proyecto_id'] = (int) $proyectoPdi['id'];
        $datos['motor_id'] = (int) $proyectoPdi['motor_id'];
        $datos['linea_id'] = (int) $proyectoPdi['linea_id'];
        $datos['rubro_id'] = (int) $datos['rubro_id'];
        $datos['cantidad'] = (int) $datos['cantidad'];
        $datos['costo_unitario'] = (float) $datos['costo_unitario'];
        $datos['usuario_id'] = (int) ($_SESSION['usuario_id'] ?? 0);

        return [$datos, ''];
    }
}
