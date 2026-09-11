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
require_once __DIR__ . '/../modelo/GeneradorXlsx.php';
require_once __DIR__ . '/../modelo/LectorXlsx.php';

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
            } elseif ($accion === 'importar') {
                [$error, $exito, $erroresImportacion] = $this->importar();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $erroresImportacion = $erroresImportacion ?? [];

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

    public function exportarPlantilla(): void
    {
        if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=login');
            exit;
        }

        $catalogos = $this->construirCatalogos();

        $encabezados = [
            'Año presupuestal *', 'Sede *', 'Dependencia *', 'Proyecto PDI *', 'Contratos comunes',
            'Actividad *', 'Rubro *', 'Insumo *', 'Cantidad *', 'Costo unitario *', 'Meses de ejecución * (ej: 1,3,5)',
        ];

        $listas = [
            'Años' => array_map(static fn (array $a): string => (string) $a['anio'], $catalogos['aniosActivos']),
            'Sedes' => array_map(static fn (array $s): string => $s['codigo'] . ' - ' . $s['nombre'], $catalogos['sedes']),
            'Dependencias' => $catalogos['dependenciasSugeridas'],
            'Proyectos' => array_map(static fn (array $p): string => self::textoProyecto($p), $catalogos['proyectos']),
            'Contratos' => array_map(static fn (array $c): string => $c['codigo'], $catalogos['contratosComunes']),
            'Rubros' => array_map(static fn (array $r): string => $r['codigo'] . ' - ' . $r['descripcion'], $catalogos['rubros']),
        ];

        $columnasConLista = [
            0 => 'Años',
            1 => 'Sedes',
            2 => 'Dependencias',
            3 => 'Proyectos',
            4 => 'Contratos',
            6 => 'Rubros',
        ];

        $filaEjemplo = [
            $listas['Años'][0] ?? '',
            $listas['Sedes'][0] ?? '',
            $listas['Dependencias'][0] ?? '',
            $listas['Proyectos'][0] ?? '',
            '',
            'Ejemplo: compra de equipos de laboratorio',
            $listas['Rubros'][0] ?? '',
            'Ejemplo: computadores portátiles',
            '1',
            '1000000',
            '1,2,3',
        ];

        $metadatos = [
            'plantilla' => 'gastos',
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'usuario_nombre' => $catalogos['usuarioActual']['nombre'] ?? '',
        ];

        GeneradorXlsx::descargar('plantilla_gastos.xlsx', $encabezados, $columnasConLista, $listas, $filaEjemplo, $metadatos);
        exit;
    }

    private static function textoProyecto(array $proyecto): string
    {
        return $proyecto['linea_codigo'] . ' · ' . $proyecto['motor_codigo'] . ' · ' . $proyecto['codigo'] . ' - ' . $proyecto['nombre'];
    }

    /**
     * Catálogos y alcance de dependencias del usuario actual, iguales a los que usa index()
     * para el formulario manual. Reutilizados por exportarPlantilla() e importar().
     */
    private function construirCatalogos(): array
    {
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $rubros = $this->modeloRubro->obtenerActivosPorCategoria('egresos');
        $contratosComunes = $this->modeloContratoComun->obtenerActivos();
        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $sedes = $this->modeloSede->obtenerTodas();

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependenciasSugeridas = $this->obtenerDependenciasPermitidas();

        return [
            'proyectos' => $proyectos,
            'rubros' => $rubros,
            'contratosComunes' => $contratosComunes,
            'aniosActivos' => $aniosActivos,
            'sedes' => $sedes,
            'usuarioActual' => $usuarioActual,
            'dependenciasSugeridas' => $dependenciasSugeridas,
        ];
    }

    /**
     * Importa gastos en borrador desde un archivo .xlsx (plantilla generada por exportarPlantilla()).
     * Todo o nada: si alguna fila falla cualquier validación (incluido el techo presupuestal,
     * verificado de forma acumulada entre las filas del propio archivo), no se importa ninguna.
     *
     * @return array{0: string, 1: string, 2: string[]} [error general, éxito, lista de errores por fila]
     */
    private function importar(): array
    {
        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            return ['Selecciona un archivo .xlsx válido para importar.', '', []];
        }

        try {
            $metadatos = LectorXlsx::leerMetadatos($_FILES['archivo']['tmp_name']);
        } catch (Throwable $excepcion) {
            return ['No se pudo leer el archivo: ' . $excepcion->getMessage(), '', []];
        }

        if (($metadatos['SPPI_Origen'] ?? '') !== GeneradorXlsx::FIRMA_PLATAFORMA || ($metadatos['SPPI_Plantilla'] ?? '') !== 'gastos') {
            return ['Este archivo no parece haber sido descargado desde la plataforma. Usa el botón "Exportar plantilla" para descargar una plantilla nueva y diligénciala sin quitarle sus metadatos.', '', []];
        }

        try {
            $filas = LectorXlsx::leerPrimeraHoja($_FILES['archivo']['tmp_name']);
        } catch (Throwable $excepcion) {
            return ['No se pudo leer el archivo: ' . $excepcion->getMessage(), '', []];
        }

        array_shift($filas);
        $filas = array_values(array_filter(
            $filas,
            static fn (array $fila): bool => trim(implode('', $fila)) !== ''
        ));

        if (empty($filas)) {
            return ['El archivo no contiene filas para importar.', '', []];
        }

        $catalogos = $this->construirCatalogos();
        $dependenciasPermitidas = $catalogos['dependenciasSugeridas'];

        $mapaAnios = [];
        foreach ($catalogos['aniosActivos'] as $anio) {
            $mapaAnios[(string) $anio['anio']] = (int) $anio['id'];
        }

        $mapaSedes = [];
        foreach ($catalogos['sedes'] as $sede) {
            $mapaSedes[$sede['codigo'] . ' - ' . $sede['nombre']] = (int) $sede['id'];
        }

        $mapaProyectos = [];
        foreach ($catalogos['proyectos'] as $proyecto) {
            $mapaProyectos[self::textoProyecto($proyecto)] = $proyecto;
        }

        $mapaRubros = [];
        foreach ($catalogos['rubros'] as $rubro) {
            $mapaRubros[$rubro['codigo'] . ' - ' . $rubro['descripcion']] = (int) $rubro['id'];
        }

        $codigosContratos = array_column($catalogos['contratosComunes'], 'codigo');

        $errores = [];
        $filasValidas = [];
        $totalesAcumulados = [];

        foreach ($filas as $indice => $fila) {
            $numeroFilaExcel = $indice + 3;

            [$datos, $errorFila] = $this->validarFilaImportacion(
                $fila,
                $mapaAnios,
                $mapaSedes,
                $dependenciasPermitidas,
                $mapaProyectos,
                $codigosContratos,
                $mapaRubros
            );

            if ($errorFila !== '') {
                $errores[] = "Fila $numeroFilaExcel: $errorFila";
                continue;
            }

            $dependenciaNombre = $datos['dependencia'];
            $dependenciaObjetivo = $this->modeloDependencia->obtenerPorNombre($dependenciaNombre);

            if ($dependenciaObjetivo !== null && empty($dependenciaObjetivo['es_raiz_superadmin'])) {
                $presupuestos = $this->modeloPresupuestoDependencia->obtenerPorAnio($datos['anio_presupuestal_id']);
                $resuelto = $this->resolverDependenciaConTecho($dependenciaObjetivo, $presupuestos);

                if ($resuelto !== null) {
                    $claveAcumulado = $resuelto['dependencia']['id'] . ':' . $datos['anio_presupuestal_id'];

                    if (!array_key_exists($claveAcumulado, $totalesAcumulados)) {
                        $gastadoPorDependencia = $this->modeloGasto->obtenerTotalesEjecutadosPorDependencia($datos['anio_presupuestal_id']);
                        $totalesAcumulados[$claveAcumulado] = $this->calcularGastadoConHerencia($resuelto['dependencia'], $presupuestos, $gastadoPorDependencia);
                    }

                    $nuevoValor = $datos['cantidad'] * $datos['costo_unitario'];

                    if ($totalesAcumulados[$claveAcumulado] + $nuevoValor > $resuelto['techo']) {
                        $disponible = max(0, $resuelto['techo'] - $totalesAcumulados[$claveAcumulado]);
                        $errores[] = "Fila $numeroFilaExcel: supera el techo presupuestal de \"" . $resuelto['dependencia']['nombre'] . "\". Disponible: " . number_format($disponible, 2) . '.';
                        continue;
                    }

                    $totalesAcumulados[$claveAcumulado] += $nuevoValor;
                }
            }

            $filasValidas[] = $datos;
        }

        if (!empty($errores)) {
            return ['No se importó ningún registro porque se encontraron errores:', '', $errores];
        }

        if (empty($filasValidas)) {
            return ['No hay filas válidas para importar.', '', []];
        }

        $db = Conexion::obtener();
        $db->beginTransaction();

        try {
            foreach ($filasValidas as $datos) {
                $this->modeloGasto->crear($datos);
            }
            $db->commit();
        } catch (PDOException $excepcion) {
            $db->rollBack();
            return ['No se pudo importar el archivo. Verifica los datos e inténtalo de nuevo.', '', []];
        }

        return ['', count($filasValidas) . ' gasto(s) importado(s) correctamente como borrador.', []];
    }

    /**
     * @return array{0: array|null, 1: string} [datos listos para Gasto::crear(), mensaje de error]
     */
    private function validarFilaImportacion(
        array $fila,
        array $mapaAnios,
        array $mapaSedes,
        array $dependenciasPermitidas,
        array $mapaProyectos,
        array $codigosContratos,
        array $mapaRubros
    ): array {
        $anioTexto = trim($fila[0] ?? '');
        $sedeTexto = trim($fila[1] ?? '');
        $dependenciaTexto = trim($fila[2] ?? '');
        $proyectoTexto = trim($fila[3] ?? '');
        $contratoTexto = trim($fila[4] ?? '');
        $actividad = trim($fila[5] ?? '');
        $rubroTexto = trim($fila[6] ?? '');
        $insumo = trim($fila[7] ?? '');
        $cantidadTexto = trim($fila[8] ?? '');
        $costoTexto = trim($fila[9] ?? '');
        $mesesTexto = trim($fila[10] ?? '');

        if ($anioTexto === '' || $sedeTexto === '' || $dependenciaTexto === '' || $proyectoTexto === ''
            || $actividad === '' || $rubroTexto === '' || $insumo === '' || $cantidadTexto === ''
            || $costoTexto === '' || $mesesTexto === ''
        ) {
            return [null, 'todos los campos obligatorios (*) deben estar diligenciados.'];
        }

        if (!isset($mapaAnios[$anioTexto])) {
            return [null, "el año \"$anioTexto\" no es válido. Usa el desplegable de la columna."];
        }

        if (!isset($mapaSedes[$sedeTexto])) {
            return [null, "la sede \"$sedeTexto\" no es válida. Usa el desplegable de la columna."];
        }

        if (!in_array($dependenciaTexto, $dependenciasPermitidas, true)) {
            return [null, "la dependencia \"$dependenciaTexto\" no está disponible para tu usuario."];
        }

        if (!isset($mapaProyectos[$proyectoTexto])) {
            return [null, "el proyecto PDI \"$proyectoTexto\" no es válido. Usa el desplegable de la columna."];
        }

        if ($contratoTexto !== '' && !in_array($contratoTexto, $codigosContratos, true)) {
            return [null, "el contrato común \"$contratoTexto\" no es válido. Usa el desplegable de la columna."];
        }

        if (!isset($mapaRubros[$rubroTexto])) {
            return [null, "el rubro \"$rubroTexto\" no es válido. Usa el desplegable de la columna."];
        }

        if (!is_numeric($cantidadTexto) || (int) $cantidadTexto <= 0) {
            return [null, 'la cantidad debe ser un número entero mayor a 0.'];
        }

        if (!is_numeric($costoTexto) || (float) $costoTexto < 0) {
            return [null, 'el costo unitario debe ser un número válido.'];
        }

        $meses = array_filter(
            array_map('intval', array_map('trim', explode(',', $mesesTexto))),
            static fn (int $mes): bool => $mes >= 1 && $mes <= 12
        );

        if (empty($meses)) {
            return [null, 'los meses de ejecución deben ser números entre 1 y 12 separados por coma (ej: 1,2,3).'];
        }

        $meses = array_unique($meses);
        sort($meses);

        $proyecto = $mapaProyectos[$proyectoTexto];

        $datos = [
            'anio_presupuestal_id' => $mapaAnios[$anioTexto],
            'sede_id' => $mapaSedes[$sedeTexto],
            'dependencia' => $dependenciaTexto,
            'proyecto_id' => (int) $proyecto['id'],
            'motor_id' => (int) $proyecto['motor_id'],
            'linea_id' => (int) $proyecto['linea_id'],
            'objeto_proyecto_paa' => $contratoTexto,
            'actividad' => $actividad,
            'rubro_id' => $mapaRubros[$rubroTexto],
            'insumo' => $insumo,
            'cantidad' => (int) $cantidadTexto,
            'costo_unitario' => (float) $costoTexto,
            'meses' => implode(',', $meses),
            'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
        ];

        return [$datos, ''];
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

            // Si se guardó un destinatario específico (porque había más de uno con ese rol en la
            // dependencia), solo esa persona lo ve — si no (envíos antiguos, o cuando había un
            // único destinatario), se mantiene la visibilidad por rol+dependencia de siempre.
            return $item['estado'] === 'enviado'
                && $rolUsuarioId !== null
                && (int) ($item['rol_destinatario_id'] ?? 0) === $rolUsuarioId
                && $item['dependencia_destino'] === $dependenciaUsuarioNombre
                && (empty($item['usuario_destinatario_id']) || (int) $item['usuario_destinatario_id'] === $usuarioActualId);
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
        $dependenciaFila = $this->modeloDependencia->obtenerPorNombre($datos['dependencia']);

        if ($dependenciaFila === null) {
            return '';
        }

        $presupuestosDependencia = $this->modeloPresupuestoDependencia->obtenerPorAnio($datos['anio_presupuestal_id']);
        $resuelto = $this->resolverDependenciaConTecho($dependenciaFila, $presupuestosDependencia);

        if ($resuelto === null) {
            return '';
        }

        $gastadoPorDependencia = $this->modeloGasto->obtenerTotalesEjecutadosPorDependencia($datos['anio_presupuestal_id']);
        $totalActual = $this->calcularGastadoConHerencia($resuelto['dependencia'], $presupuestosDependencia, $gastadoPorDependencia);

        if ($idExcluir > 0) {
            $existente = $this->modeloGasto->obtenerPorId($idExcluir);

            if ($existente !== null) {
                $totalActual -= (float) $existente['valor_total'];
            }
        }

        $nuevoValor = $datos['cantidad'] * $datos['costo_unitario'];

        if ($totalActual + $nuevoValor > $resuelto['techo']) {
            $disponible = max(0, $resuelto['techo'] - $totalActual);

            return 'Este gasto supera el techo presupuestal de "' . $resuelto['dependencia']['nombre'] . '". Disponible: ' . number_format($disponible, 2) . '.';
        }

        return '';
    }

    /**
     * Encuentra la dependencia contra la que debe validarse el techo para una fila de gasto: la
     * propia dependencia de la fila si tiene techo propio (> 0) — un presupuesto delegado de
     * forma independiente —, o si no, la dependencia del USUARIO que está enviando el gasto (no
     * un ancestro cualquiera de la jerarquía). Así, un Gestor puede, dentro de su propia tabla de
     * gastos, atribuir una fila a una dependencia hija suya sin techo propio (se cuenta contra su
     * propio techo); pero si quien envía no tiene techo propio asignado, no puede registrar
     * gastos en absoluto, sin importar si algún ancestro más arriba en la jerarquía sí lo tiene —
     * el techo de un ancestro lejano no es del usuario que envía. validarDependenciaPermitida()
     * ya garantiza, antes de llegar aquí, que la dependencia de la fila es la propia del usuario
     * o una descendiente suya.
     */
    private function resolverDependenciaConTecho(array $dependenciaFila, array $presupuestosDependencia): ?array
    {
        if (!empty($dependenciaFila['es_raiz_superadmin'])) {
            return null;
        }

        $techoPropio = $presupuestosDependencia[(int) $dependenciaFila['id']]['techo'] ?? null;

        if ($techoPropio !== null && (float) $techoPropio > 0) {
            return ['dependencia' => $dependenciaFila, 'techo' => (float) $techoPropio];
        }

        $dependenciaUsuario = $this->obtenerDependenciaUsuarioActual();

        if ($dependenciaUsuario === null || !empty($dependenciaUsuario['es_raiz_superadmin'])) {
            return null;
        }

        $techoUsuario = $presupuestosDependencia[(int) $dependenciaUsuario['id']]['techo'] ?? null;

        if ($techoUsuario !== null && (float) $techoUsuario > 0) {
            return ['dependencia' => $dependenciaUsuario, 'techo' => (float) $techoUsuario];
        }

        return null;
    }

    private function obtenerDependenciaUsuarioActual(): ?array
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        return $dependenciaUsuarioId !== null ? $this->modeloDependencia->obtenerPorId($dependenciaUsuarioId) : null;
    }

    /**
     * Total ya ejecutado contra el techo de $dependencia: lo gastado directamente en ella más lo
     * gastado en cualquier descendiente que no tenga techo propio (esos descendientes "heredan"
     * el techo del padre, así que su gasto cuenta contra el mismo límite) — mismo criterio que
     * TechosControlador::calcularAsignadoArbol(), aplicado solo a la rama de $dependencia.
     */
    private function calcularGastadoConHerencia(array $dependencia, array $presupuestosDependencia, array $gastadoPorDependencia): float
    {
        $total = $gastadoPorDependencia[$dependencia['nombre']] ?? 0.0;

        return $total + $this->sumarGastadoDescendientesSinTecho(
            $this->modeloDependencia->construirArbolDescendientes((int) $dependencia['id']),
            $presupuestosDependencia,
            $gastadoPorDependencia
        );
    }

    private function sumarGastadoDescendientesSinTecho(array $nodos, array $presupuestosDependencia, array $gastadoPorDependencia): float
    {
        $total = 0.0;

        foreach ($nodos as $nodo) {
            $techoNodo = $presupuestosDependencia[(int) $nodo['dependencia']['id']]['techo'] ?? null;
            $gastadoRama = ($gastadoPorDependencia[$nodo['dependencia']['nombre']] ?? 0.0)
                + $this->sumarGastadoDescendientesSinTecho($nodo['hijos'], $presupuestosDependencia, $gastadoPorDependencia);

            if ($techoNodo === null || (float) $techoNodo <= 0) {
                $total += $gastadoRama;
            }
        }

        return $total;
    }

    /**
     * Recolecta los nombres de las dependencias descendientes que no tienen techo propio
     * asignado: sus gastos se atribuyen presupuestalmente a la rama del ancestro con techo
     * (ver resolverDependenciaConTecho()), así que deben incluirse junto con la dependencia
     * remitente al enviar o consultar sus borradores. Una dependencia con techo propio se
     * gestiona y envía de forma independiente, así que su rama se detiene ahí.
     */
    private function recolectarDependenciasSinTecho(array $nodos, array $presupuestosDependencia): array
    {
        $nombres = [];

        foreach ($nodos as $nodo) {
            $techoNodo = $presupuestosDependencia[(int) $nodo['dependencia']['id']]['techo'] ?? null;

            if ($techoNodo !== null && (float) $techoNodo > 0) {
                continue;
            }

            $nombres[] = $nodo['dependencia']['nombre'];
            $nombres = array_merge($nombres, $this->recolectarDependenciasSinTecho($nodo['hijos'], $presupuestosDependencia));
        }

        return $nombres;
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
        $nombresSinTechoPropio = [];
        if ($dependenciaObjetivo !== null) {
            foreach ($this->modeloDependencia->obtenerHijasDirectas((int) $dependenciaObjetivo['id']) as $hija) {
                if (($hija['tipo'] ?? '') === 'Dumi') {
                    $nombresDumi[] = $hija['nombre'];
                }
            }

            // Además de las "Dumi", también se envían los gastos de cualquier dependencia
            // descendiente que no tenga techo propio asignado: esos gastos se atribuyen a una
            // hija solo para categorizarlos, pero presupuestalmente son del remitente (ver
            // validarLimiteTecho()), así que deben salir en el mismo "Enviar todo". Una hija con
            // techo propio se gestiona y se envía de forma independiente, así que no se incluye.
            $presupuestosDependencia = $this->modeloPresupuestoDependencia->obtenerPorAnio($anioId);
            $nombresSinTechoPropio = $this->recolectarDependenciasSinTecho(
                $this->modeloDependencia->construirArbolDescendientes((int) $dependenciaObjetivo['id']),
                $presupuestosDependencia
            );
        }

        $nombresAdicionales = array_values(array_unique(array_merge($nombresDumi, $nombresSinTechoPropio)));

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

        // Una vez resuelto (por ser el único con ese rol, o por desambiguación arriba), se guarda
        // quién es exactamente el destinatario — si no, cualquiera con ese rol en la dependencia
        // vería la petición en Pendientes, no solo la persona elegida.
        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;

        $enviados = $this->modeloGasto->enviarTodosBorrador($anioId, $dependenciaNombre, $rolDestinatarioId, $nombresAdicionales, $usuarioDestinatarioResuelto);

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
