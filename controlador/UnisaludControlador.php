<?php

require_once __DIR__ . '/../modelo/GastoUnisalud.php';
require_once __DIR__ . '/../modelo/IngresoUnisalud.php';
require_once __DIR__ . '/../modelo/DuplicadorFilas.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Rubro.php';
require_once __DIR__ . '/../modelo/ContratoComun.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/AutogestionPorcentaje.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/PeticionArchivada.php';

class UnisaludControlador
{
    private GastoUnisalud $modeloGasto;
    private IngresoUnisalud $modeloIngreso;
    private Linea $modeloLinea;
    private Motor $modeloMotor;
    private Proyecto $modeloProyecto;
    private Rubro $modeloRubro;
    private ContratoComun $modeloContratoComun;
    private AnioPresupuestal $modeloAnio;
    private Sede $modeloSede;
    private AutogestionPorcentaje $modeloPorcentaje;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;

    private const CAMPOS_REQUERIDOS_EGRESO = [
        'sede_id',
        'anio_presupuestal_id',
        'categoria',
        'dependencia',
        'proyecto_id',
        'actividad',
        'rubro_id',
        'insumo',
        'cantidad',
        'costo_unitario',
    ];

    private const CATEGORIAS_EGRESO = [
        'Gastos',
        'Inversiones',
    ];

    private const AUTOMATICO_SEDE_ID = 1;
    private const AUTOMATICO_LINEA_ID = 5;
    private const AUTOMATICO_MOTOR_ID = 1;
    private const AUTOMATICO_PROYECTO_ID = 1;
    private const AUTOMATICO_RUBRO_TEXTO = 'SERVICIOS DE DISTRIBUCIÓN DE ELECTRICIDAD, GAS Y AGUA (POR CUENTA PROPIA)';
    private const AUTOMATICO_OBJETO_PROYECTO_PAA = 'SERVICIOS DE DISTRIBUCIÓN DE ELECTRICIDAD, GAS Y AGUA (POR CUENTA PROPIA)';
    private const AUTOMATICO_INSUMO = 'EXCEDENTES NIVEL CENTRAL';
    private const AUTOMATICO_ACTIVIDAD = 'Contribución al uso de servicios públicos';
    private const AUTOMATICO_MES = 2;

    public function __construct()
    {
        $this->modeloGasto = new GastoUnisalud();
        $this->modeloIngreso = new IngresoUnisalud();
        $this->modeloLinea = new Linea();
        $this->modeloMotor = new Motor();
        $this->modeloProyecto = new Proyecto();
        $this->modeloRubro = new Rubro();
        $this->modeloContratoComun = new ContratoComun();
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloSede = new Sede();
        $this->modeloPorcentaje = new AutogestionPorcentaje();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
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
        $tab = ($_GET['tab'] ?? 'ingresos') === 'egresos' ? 'egresos' : 'ingresos';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tab = ($_POST['tab'] ?? 'ingresos') === 'egresos' ? 'egresos' : 'ingresos';
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'enviar_todo') {
                [$error, $exito] = $this->enviarTodo();
            } elseif ($tab === 'egresos' && $accion === 'actualizar') {
                [$error, $exito] = $this->actualizarEgreso();
            } elseif ($tab === 'egresos' && $accion === 'eliminar') {
                [$error, $exito] = $this->eliminarEgreso();
            } elseif ($tab === 'ingresos' && $accion === 'actualizar') {
                [$error, $exito] = $this->actualizarIngreso();
            } elseif ($tab === 'ingresos' && $accion === 'eliminar') {
                [$error, $exito] = $this->eliminarIngreso();
            } elseif ($accion === 'eliminar_seleccionados') {
                [$error, $exito] = $this->eliminarSeleccionados($tab);
            } elseif ($accion === 'duplicar_seleccionados') {
                [$error, $exito] = $this->duplicarSeleccionados($tab);
            } else {
                [$error, $exito] = $tab === 'ingresos' ? $this->guardarIngreso() : $this->guardarEgreso();
            }
        }

        $lineas = $this->modeloLinea->obtenerTodas();
        $motores = $this->modeloMotor->obtenerTodos();
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $rubros = $this->modeloRubro->obtenerActivosPorCategoria('autogestion');
        $contratosComunes = $this->modeloContratoComun->obtenerActivos();
        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $sedes = $this->modeloSede->obtenerTodas();
        $categoriasEgreso = self::CATEGORIAS_EGRESO;
        $roles = $this->modeloRol->obtenerTodos();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;
        $dependenciaUsuario = $dependenciaUsuarioId !== null ? $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId) : null;
        $dependenciaUsuarioEsRaiz = $dependenciaUsuario !== null && !empty($dependenciaUsuario['es_raiz_superadmin']);

        $dependenciasSugeridas = [];
        $tieneHijas = false;
        $dependenciaPorDefecto = null;

        if ($dependenciaUsuario !== null) {
            $descendientes = $this->modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId);
            $tieneHijas = !empty($descendientes);

            if (!$dependenciaUsuarioEsRaiz) {
                $dependenciasSugeridas[] = $dependenciaUsuario['nombre'];
                $dependenciaPorDefecto = $dependenciaUsuario['nombre'];
            }

            foreach ($descendientes as $descendiente) {
                if (!empty($descendiente['es_raiz_superadmin'])) {
                    continue;
                }

                $dependenciasSugeridas[] = $descendiente['nombre'];
            }
        }

        if ($dependenciaPorDefecto === null && in_array('UNIDAD DE SALUD', $dependenciasSugeridas, true)) {
            $dependenciaPorDefecto = 'UNIDAD DE SALUD';
        }

        $anioSeleccionadoId = 0;

        if (!empty($aniosActivos)) {
            $anioSeleccionadoId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) $aniosActivos[0]['id'];

            $idsValidos = array_map('intval', array_column($aniosActivos, 'id'));
            if (!in_array($anioSeleccionadoId, $idsValidos, true)) {
                $anioSeleccionadoId = (int) $aniosActivos[0]['id'];
            }
        }

        $gastosEgresos = $anioSeleccionadoId > 0 ? $this->modeloGasto->obtenerPorAnio($anioSeleccionadoId) : [];

        if ($tab === 'ingresos') {
            $gastos = $anioSeleccionadoId > 0 ? $this->modeloIngreso->obtenerPorAnio($anioSeleccionadoId) : [];
            $gastos = $this->filtrarPorPropietarioODestinatario($gastos, $usuarioActual, $dependenciasSugeridas);
        } else {
            $gastos = $this->filtrarEgresosVisibles($gastosEgresos, $usuarioActual, $dependenciasSugeridas);
        }

        $dependenciasTodas = $this->modeloDependencia->obtenerActivasParaEnvio();

        $egresoParaEditar = null;
        $ingresoParaEditar = null;
        if (isset($_GET['editar_id']) && ctype_digit((string) $_GET['editar_id'])) {
            if ($tab === 'egresos') {
                $egresoParaEditar = $this->modeloGasto->obtenerPorId((int) $_GET['editar_id']);
            } else {
                $ingresoParaEditar = $this->modeloIngreso->obtenerPorId((int) $_GET['editar_id']);
            }
        }
        $volverEdicion = $_GET['volver'] ?? '';

        $anioSeleccionado = null;
        foreach ($aniosActivos as $anioFila) {
            if ((int) $anioFila['id'] === $anioSeleccionadoId) {
                $anioSeleccionado = $anioFila;
                break;
            }
        }

        $totalGastado = array_sum(array_map(static fn (array $g): float => (float) $g['valor_total'], $gastos));
        $totalEjecutado = array_sum(array_map(static fn (array $g): float => (float) $g['valor_total'], $gastosEgresos));
        $presupuestoAnio = $anioSeleccionadoId > 0
            ? $this->modeloIngreso->obtenerTotalPorAnioYDependencias($anioSeleccionadoId, $dependenciasSugeridas)
            : 0.0;
        $porcentajeGastado = $presupuestoAnio > 0 ? min(100, ($totalEjecutado / $presupuestoAnio) * 100) : 0.0;
        $puedeEnviarTodo = $presupuestoAnio > 0 && abs($presupuestoAnio - $totalEjecutado) < 0.01;

        $totalCostos = 0.0;
        $totalInversion = 0.0;
        $totalExcedentes = 0.0;

        foreach ($gastosEgresos as $gasto) {
            $valor = (float) $gasto['valor_total'];
            $categoria = $gasto['categoria'];

            if ($categoria === 'Gastos' || str_starts_with($categoria, 'Costos')) {
                $totalCostos += $valor;
            } elseif ($categoria === 'Inversiones' || str_starts_with($categoria, 'Inversión')) {
                $totalInversion += $valor;
            } elseif (str_starts_with($categoria, 'Excedentes')) {
                $totalExcedentes += $valor;
            }
        }

        $pctCostos = $presupuestoAnio > 0 ? min(100, ($totalCostos / $presupuestoAnio) * 100) : 0.0;
        $pctInversion = $presupuestoAnio > 0 ? min(100, ($totalInversion / $presupuestoAnio) * 100) : 0.0;
        $pctExcedentes = $presupuestoAnio > 0 ? min(100, ($totalExcedentes / $presupuestoAnio) * 100) : 0.0;

        $diaActual = (int) date('z') + 1;
        $totalDiasAnio = date('L') ? 366 : 365;

        require __DIR__ . '/../vista/unisalud/index.php';
    }

    private function guardarEgreso(): array
    {
        [$datos, $error] = $this->validarDatosEgreso();

        if ($error !== '') {
            return [$error, ''];
        }

        [, $dependenciasPermitidas] = $this->obtenerDependenciasVisiblesUsuarioActual();
        $ingresosDisponibles = $this->modeloIngreso->obtenerTotalPorAnioYDependencias($datos['anio_presupuestal_id'], $dependenciasPermitidas);
        $egresosActuales = $this->modeloGasto->obtenerTotalPorAnioYDependencias($datos['anio_presupuestal_id'], $dependenciasPermitidas);
        $nuevoValor = $datos['cantidad'] * $datos['costo_unitario'];

        if ($egresosActuales + $nuevoValor > $ingresosDisponibles) {
            $disponible = max(0, $ingresosDisponibles - $egresosActuales);

            return ['Este egreso supera los ingresos disponibles de este año. Disponible: ' . number_format($disponible, 2) . '.', ''];
        }

        $errorCategoria = $this->validarLimiteCategoria($datos['anio_presupuestal_id'], $datos['categoria'], $nuevoValor, $ingresosDisponibles, 0.0, $dependenciasPermitidas);

        if ($errorCategoria !== '') {
            return [$errorCategoria, ''];
        }

        try {
            $this->modeloGasto->crear($datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar el egreso. Verifica el año, la sede, la línea, el motor, el proyecto y el rubro seleccionados.', ''];
        }

        return ['', 'Egreso registrado correctamente.'];
    }

    private function actualizarEgreso(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloGasto->obtenerPorId($id) : null;

        if ($existente === null || $existente['tipo_automatico'] !== null) {
            return ['El egreso que intentas editar no existe.', ''];
        }

        [$datos, $error] = $this->validarDatosEgreso();

        if ($error !== '') {
            return [$error, ''];
        }

        [, $dependenciasPermitidas] = $this->obtenerDependenciasVisiblesUsuarioActual();
        $ingresosDisponibles = $this->modeloIngreso->obtenerTotalPorAnioYDependencias($datos['anio_presupuestal_id'], $dependenciasPermitidas);
        $egresosActuales = $this->modeloGasto->obtenerTotalPorAnioYDependencias($datos['anio_presupuestal_id'], $dependenciasPermitidas) - (float) $existente['valor_total'];
        $nuevoValor = $datos['cantidad'] * $datos['costo_unitario'];

        if ($egresosActuales + $nuevoValor > $ingresosDisponibles) {
            $disponible = max(0, $ingresosDisponibles - $egresosActuales);

            return ['Este egreso supera los ingresos disponibles de este año. Disponible: ' . number_format($disponible, 2) . '.', ''];
        }

        $valorExcluidoCategoria = $existente['categoria'] === $datos['categoria'] ? (float) $existente['valor_total'] : 0.0;
        $errorCategoria = $this->validarLimiteCategoria($datos['anio_presupuestal_id'], $datos['categoria'], $nuevoValor, $ingresosDisponibles, $valorExcluidoCategoria, $dependenciasPermitidas);

        if ($errorCategoria !== '') {
            return [$errorCategoria, ''];
        }

        try {
            $this->modeloGasto->actualizar($id, $datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar el egreso. Verifica el año, la sede, la línea, el motor, el proyecto y el rubro seleccionados.', ''];
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen('gasto_unisalud', $id, $nuevoValor, $datos['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=unisalud&tab=egresos&anio_id=' . $datos['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function validarLimiteCategoria(int $anioPresupuestalId, string $categoria, float $nuevoValor, float $totalIngresos, float $valorExcluido = 0.0, array $dependenciasPermitidas = []): string
    {
        $mapaCategoriaPorcentaje = ['Gastos' => 'costos', 'Inversiones' => 'inversiones'];
        $clavePorcentaje = $mapaCategoriaPorcentaje[$categoria] ?? null;

        if ($clavePorcentaje === null) {
            return '';
        }

        $porcentajes = $this->modeloPorcentaje->obtenerPorModulo('unisalud');

        if ($porcentajes[$clavePorcentaje] === null) {
            return '';
        }

        $limiteCategoria = round($totalIngresos * (float) $porcentajes[$clavePorcentaje] / 100, 2);
        $totalCategoriaActual = $this->modeloGasto->obtenerTotalPorAnioYCategoriaYDependencias($anioPresupuestalId, $categoria, $dependenciasPermitidas) - $valorExcluido;

        if ($totalCategoriaActual + $nuevoValor > $limiteCategoria) {
            $disponibleCategoria = max(0, $limiteCategoria - $totalCategoriaActual);

            return "Este egreso supera el porcentaje disponible para {$categoria}. Disponible: " . number_format($disponibleCategoria, 2) . '.';
        }

        return '';
    }

    private function enviarTodo(): array
    {
        $anioId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $dependenciaDestinoNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if ($anioId <= 0 || $dependenciaDestinoNombre === '' || $rolDestinatarioId <= 0) {
            return ['Selecciona a quién se enviará y el rol al que se enviarán los ingresos y egresos.', ''];
        }

        $rol = $this->modeloRol->obtenerPorId($rolDestinatarioId);

        if ($rol === null) {
            return ['El rol seleccionado no existe.', ''];
        }

        $dependenciaDestino = $this->modeloDependencia->obtenerPorNombre($dependenciaDestinoNombre);

        if ($dependenciaDestino === null) {
            return ['La dependencia destino seleccionada no existe.', ''];
        }

        $destinatarios = $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependenciaDestino['id'], $rolDestinatarioId);

        if (count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        [, $dependenciasPermitidasEnvio] = $this->obtenerDependenciasVisiblesUsuarioActual();
        $totalIngresos = $this->modeloIngreso->obtenerTotalPorAnioYDependencias($anioId, $dependenciasPermitidasEnvio);
        $totalEgresos = $this->modeloGasto->obtenerTotalPorAnioYDependencias($anioId, $dependenciasPermitidasEnvio);

        if ($totalIngresos <= 0 || abs($totalIngresos - $totalEgresos) >= 0.01) {
            return ['Solo puedes enviar cuando el total de egresos sea igual al total de ingresos de este año.', ''];
        }

        $enviadosIngresos = $this->modeloIngreso->enviarTodosBorrador($anioId, $dependenciaDestinoNombre, $rolDestinatarioId, $dependenciasPermitidasEnvio);
        $enviadosEgresos = $this->modeloGasto->enviarTodosBorrador($anioId, $dependenciaDestinoNombre, $rolDestinatarioId, $dependenciasPermitidasEnvio);

        if ($enviadosIngresos === 0 && $enviadosEgresos === 0) {
            return ['No hay ingresos ni egresos en borrador para enviar.', ''];
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear(
                $remitenteId,
                (int) $destinatario['id'],
                'Unisalud enviado',
                'Se enviaron ' . $enviadosIngresos . ' ingreso(s) y ' . $enviadosEgresos . ' egreso(s) de Unisalud para tu revisión.'
            );
        }

        if (empty($destinatarios)) {
            return ['', 'Se enviaron ' . $enviadosIngresos . ' ingreso(s) y ' . $enviadosEgresos . ' egreso(s), pero no se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoNombre . '" para notificar.'];
        }

        return ['', 'Se enviaron ' . $enviadosIngresos . ' ingreso(s) y ' . $enviadosEgresos . ' egreso(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaDestinoNombre . '").'];
    }

    private function eliminarEgreso(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloGasto->obtenerPorId($id) : null;

        if ($existente === null || $existente['tipo_automatico'] !== null) {
            return ['El egreso que intentas eliminar no existe.', ''];
        }

        $this->modeloGasto->eliminar($id);

        return ['', 'Egreso eliminado correctamente.'];
    }

    private function validarDatosEgreso(): array
    {
        $datos = [];

        foreach ($_POST as $campo => $valor) {
            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        foreach (self::CAMPOS_REQUERIDOS_EGRESO as $campo) {
            if (($datos[$campo] ?? '') === '') {
                return [[], 'Todos los campos son obligatorios.'];
            }
        }

        if (!in_array($datos['categoria'], self::CATEGORIAS_EGRESO, true)) {
            return [[], 'Selecciona una categoría válida.'];
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

    private function validarDatosIngreso(): array
    {
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $dependencia = trim($_POST['dependencia'] ?? '');
        $conceptoAdicional = trim($_POST['concepto_adicional'] ?? '');
        $valorAdicional = is_numeric($_POST['valor_adicional'] ?? '') ? (float) $_POST['valor_adicional'] : 0.0;

        if ($anioPresupuestalId <= 0 || $dependencia === '') {
            return [[], [], 'El año presupuestal y la dependencia son obligatorios.'];
        }

        if ($valorAdicional < 0) {
            return [[], [], 'El valor del concepto adicional no puede ser negativo.'];
        }

        $nombresConcepto = $_POST['concepto'] ?? [];
        $cantidades = $_POST['cantidad_concepto'] ?? [];
        $valores = $_POST['valor_concepto'] ?? [];

        $conceptos = [];
        $totalFilas = max(count($nombresConcepto), count($cantidades), count($valores));

        for ($indice = 0; $indice < $totalFilas; $indice++) {
            $nombre = trim($nombresConcepto[$indice] ?? '');
            $cantidad = $cantidades[$indice] ?? '';
            $valor = $valores[$indice] ?? '';

            if ($nombre === '' && $cantidad === '' && $valor === '') {
                continue;
            }

            if ($nombre === '' || !is_numeric($cantidad) || (int) $cantidad <= 0 || !is_numeric($valor) || (float) $valor < 0) {
                return [[], [], 'Cada concepto necesita un nombre, una cantidad mayor a 0 y un valor válido.'];
            }

            $conceptos[] = [
                'concepto' => $nombre,
                'cantidad' => (int) $cantidad,
                'valor' => (float) $valor,
            ];
        }

        if (empty($conceptos) && $valorAdicional <= 0) {
            return [[], [], 'Agrega al menos un concepto o un concepto adicional con valor.'];
        }

        $subtotalConceptos = array_sum(array_map(
            static fn (array $c): float => $c['cantidad'] * $c['valor'],
            $conceptos
        ));
        $valorTotal = $subtotalConceptos + $valorAdicional;

        $cabecera = [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia' => $dependencia,
            'concepto_adicional' => $conceptoAdicional,
            'valor_adicional' => $valorAdicional,
            'valor_total' => $valorTotal,
            'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
        ];

        return [$cabecera, $conceptos, ''];
    }

    private function guardarIngreso(): array
    {
        [$cabecera, $conceptos, $error] = $this->validarDatosIngreso();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $ingresoId = $this->modeloIngreso->crear($cabecera, $conceptos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar el ingreso. Verifica el año presupuestal seleccionado.', ''];
        }

        $this->generarEgresosAutomaticos($ingresoId, $cabecera['anio_presupuestal_id'], $cabecera['dependencia']);

        return ['', 'Ingreso registrado correctamente.'];
    }

    private function actualizarIngreso(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloIngreso->obtenerPorId($id) : null;

        if ($existente === null) {
            return ['El ingreso que intentas editar no existe.', ''];
        }

        [$cabecera, $conceptos, $error] = $this->validarDatosIngreso();

        if ($error !== '') {
            return [$error, ''];
        }

        try {
            $this->modeloIngreso->actualizar($id, $cabecera, $conceptos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar el ingreso. Verifica el año presupuestal seleccionado.', ''];
        }

        $this->generarEgresosAutomaticos($id, $cabecera['anio_presupuestal_id'], $cabecera['dependencia']);

        (new PeticionArchivada())->sincronizarDesdeOrigen('ingreso_unisalud', $id, $cabecera['valor_total'], $cabecera['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=unisalud&tab=ingresos&anio_id=' . $cabecera['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function eliminarIngreso(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloIngreso->obtenerPorId($id) : null;

        if ($existente === null) {
            return ['El ingreso que intentas eliminar no existe.', ''];
        }

        $this->modeloIngreso->eliminar($id);
        $this->generarEgresosAutomaticos(
            null,
            (int) $existente['anio_presupuestal_id'],
            (string) $existente['dependencia']
        );

        return ['', 'Ingreso eliminado correctamente.'];
    }

    private function eliminarSeleccionados(string $tab): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $eliminados = 0;

        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }

            if ($tab === 'egresos') {
                $existente = $this->modeloGasto->obtenerPorId($id);

                if ($existente !== null && $existente['tipo_automatico'] === null) {
                    $this->modeloGasto->eliminar($id);
                    $eliminados++;
                }
            } else {
                $existente = $this->modeloIngreso->obtenerPorId($id);

                if ($existente !== null) {
                    $this->modeloIngreso->eliminar($id);
                    $this->generarEgresosAutomaticos(
                        null,
                        (int) $existente['anio_presupuestal_id'],
                        (string) $existente['dependencia']
                    );
                    $eliminados++;
                }
            }
        }

        if ($eliminados === 0) {
            return ['No se eliminó ningún elemento.', ''];
        }

        return ['', 'Se eliminaron ' . $eliminados . ' elemento(s).'];
    }

    private function duplicarSeleccionados(string $tab): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $db = Conexion::obtener();
        $duplicados = 0;

        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }

            if ($tab === 'egresos') {
                $existente = $this->modeloGasto->obtenerPorId($id);

                if ($existente !== null && $existente['tipo_automatico'] === null && DuplicadorFilas::duplicarFila($db, 'gastos_unisalud', $id) !== null) {
                    $duplicados++;
                }
            } else {
                $nuevoId = DuplicadorFilas::duplicarFila($db, 'ingresos_unisalud', $id);

                if ($nuevoId !== null) {
                    DuplicadorFilas::duplicarFilasHijo($db, 'ingresos_unisalud_conceptos', 'ingreso_id', $id, $nuevoId);
                    $duplicados++;

                    $nuevo = $this->modeloIngreso->obtenerPorId($nuevoId);
                    if ($nuevo !== null) {
                        $this->generarEgresosAutomaticos(
                            null,
                            (int) $nuevo['anio_presupuestal_id'],
                            (string) $nuevo['dependencia']
                        );
                    }
                }
            }
        }

        if ($duplicados === 0) {
            return ['No se duplicó ningún elemento.', ''];
        }

        return ['', 'Se duplicaron ' . $duplicados . ' elemento(s).'];
    }

    private function generarEgresosAutomaticos(?int $ingresoId, int $anioPresupuestalId, string $dependencia): void
    {
        $porcentajes = $this->modeloPorcentaje->obtenerPorModulo('unisalud');
        // Acotado a la propia dependencia del ingreso: antes usaba obtenerTotalPorAnio() (todo el
        // año, todas las dependencias), así que el egreso automático de cada dependencia se
        // calculaba sobre el total de TODA la universidad, no sobre lo que esa dependencia
        // realmente ingresó.
        $totalIngresos = $this->modeloIngreso->obtenerTotalPorAnioYDependencias($anioPresupuestalId, [$dependencia]);

        $lineas = [];

        if ($porcentajes['excedentes'] !== null) {
            $lineas['excedentes'] = ['porcentaje' => (float) $porcentajes['excedentes'], 'etiqueta' => 'Excedentes nivel central'];
        }

        $this->modeloGasto->eliminarAutomaticosDistintosDe($anioPresupuestalId, array_keys($lineas));

        foreach ($lineas as $tipo => $info) {
            $valor = round($totalIngresos * $info['porcentaje'] / 100, 2);
            $porcentajeTexto = rtrim(rtrim(number_format($info['porcentaje'], 2), '0'), '.');

            $categoria = $info['etiqueta'] . ' (' . $porcentajeTexto . '%)';

            $datos = [
                'sede_id' => self::AUTOMATICO_SEDE_ID,
                'anio_presupuestal_id' => $anioPresupuestalId,
                'categoria' => $categoria,
                'dependencia' => $dependencia,
                'linea_id' => self::AUTOMATICO_LINEA_ID,
                'motor_id' => self::AUTOMATICO_MOTOR_ID,
                'proyecto_id' => self::AUTOMATICO_PROYECTO_ID,
                'objeto_proyecto_paa' => self::AUTOMATICO_OBJETO_PROYECTO_PAA,
                'actividad' => self::AUTOMATICO_ACTIVIDAD,
                'rubro_texto' => self::AUTOMATICO_RUBRO_TEXTO,
                'ingreso_id' => $ingresoId,
                'tipo_automatico' => $tipo,
                'insumo' => self::AUTOMATICO_INSUMO,
                'cantidad' => 1,
                'costo_unitario' => $valor,
                'valor_total' => $valor,
                'meses' => (string) self::AUTOMATICO_MES,
            ];

            $existente = $this->modeloGasto->obtenerAutomaticoPorTipo($anioPresupuestalId, $tipo);

            if ($existente !== null) {
                $this->modeloGasto->actualizarAutomatico((int) $existente['id'], $datos);
            } else {
                $this->modeloGasto->crearAutomatico($datos);
            }
        }
    }

    /**
     * Un ingreso o egreso solo debe ser visible, en su propio listado, para quien lo creó o para
     * quien coincide exactamente con la dependencia y el rol al que fue enviado — nadie más lo ve,
     * ni siquiera un nivel superior de la jerarquía, hasta que se le envía explícitamente.
     * Los registros anteriores a este control (sin usuario_id registrado) se mantienen visibles
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
     * Igual que filtrarPorPropietarioODestinatario, pero preserva siempre las líneas automáticas,
     * que son cifras agregadas administradas de forma centralizada y no pertenecen a la
     * dependencia que originó el ingreso que las generó.
     */
    private function filtrarEgresosVisibles(array $items, ?array $usuarioActual, array $dependenciasPermitidas): array
    {
        $automaticos = array_values(array_filter($items, static fn (array $item): bool => $item['tipo_automatico'] !== null));
        $manuales = array_values(array_filter($items, static fn (array $item): bool => $item['tipo_automatico'] === null));

        return array_merge($automaticos, $this->filtrarPorPropietarioODestinatario($manuales, $usuarioActual, $dependenciasPermitidas));
    }

    /**
     * Dependencias que el usuario de la sesión actual puede ver (la suya propia + sus
     * descendientes), igual que las que ya calcula index() para filtrar la tabla — pero
     * reutilizable desde los métodos privados de guardado/validación, que no reciben ese
     * contexto. Usarla para acotar cualquier total de presupuesto (ingresos/egresos disponibles),
     * en vez de sumar sin filtro por dependencia.
     */
    private function obtenerDependenciasVisiblesUsuarioActual(): array
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;
        $dependenciaUsuario = $dependenciaUsuarioId !== null ? $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId) : null;
        $dependenciaUsuarioEsRaiz = $dependenciaUsuario !== null && !empty($dependenciaUsuario['es_raiz_superadmin']);

        $dependenciasSugeridas = [];

        if ($dependenciaUsuario !== null) {
            if (!$dependenciaUsuarioEsRaiz) {
                $dependenciasSugeridas[] = $dependenciaUsuario['nombre'];
            }

            foreach ($this->modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId) as $descendiente) {
                if (!empty($descendiente['es_raiz_superadmin'])) {
                    continue;
                }

                $dependenciasSugeridas[] = $descendiente['nombre'];
            }
        }

        return [$usuarioActual, $dependenciasSugeridas];
    }
}
