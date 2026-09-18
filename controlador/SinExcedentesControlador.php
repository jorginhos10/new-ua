<?php

require_once __DIR__ . '/../modelo/GastoSinExcedentes.php';
require_once __DIR__ . '/../modelo/IngresoSinExcedentes.php';
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
require_once __DIR__ . '/../modelo/GeneradorXlsx.php';
require_once __DIR__ . '/../modelo/LectorXlsx.php';

class SinExcedentesControlador
{
    private GastoSinExcedentes $modeloGasto;
    private IngresoSinExcedentes $modeloIngreso;
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
        'Excedentes',
        'Gastos',
        'Inversiones',
    ];

    /**
     * Orden fijo de las categorías en el bloque de validación (SUMAR.SI) de la plantilla
     * importable/exportable, y desplazamiento de filas que ocupa ese bloque antes del encabezado
     * real de cada hoja (ver GeneradorXlsx::descargarPlantillaAutogestion()): 1 fila de título + 1
     * de encabezado del bloque + una por categoría = 2 + count(...). Debe coincidir con el mismo
     * cálculo usado allí para no desalinear la importación.
     */
    private const CATEGORIAS_VALIDACION_PLANTILLA = ['Excedentes', 'Gastos', 'Inversiones'];

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
        $this->modeloGasto = new GastoSinExcedentes();
        $this->modeloIngreso = new IngresoSinExcedentes();
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
            } elseif ($accion === 'importar') {
                [$error, $exito, $erroresImportacion] = $this->importar();
            } else {
                [$error, $exito] = $tab === 'ingresos' ? $this->guardarIngreso() : $this->guardarEgreso();
            }
        }

        $erroresImportacion = $erroresImportacion ?? [];

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

        require __DIR__ . '/../vista/sin-excedentes/index.php';
    }

    public function exportarPlantilla(): void
    {
        if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=login');
            exit;
        }

        $catalogos = $this->construirCatalogos();

        $listasComunes = [
            'Años' => array_map(static fn (array $a): string => (string) $a['anio'], $catalogos['aniosActivos']),
            'Sedes' => array_map(static fn (array $s): string => $s['codigo'] . ' - ' . $s['nombre'], $catalogos['sedes']),
            'Dependencias' => $catalogos['dependenciasSugeridas'],
            'Proyectos' => array_map(static fn (array $p): string => self::textoProyecto($p), $catalogos['proyectos']),
            'Contratos' => array_map(static fn (array $c): string => $c['codigo'], $catalogos['contratosComunes']),
            'Categorias' => self::CATEGORIAS_EGRESO,
            'Rubros' => array_map(static fn (array $r): string => $r['codigo'] . ' - ' . $r['descripcion'], $catalogos['rubros']),
        ];

        $hojaIngresos = [
            'encabezados' => ['Año presupuestal *', 'Dependencia *', 'Concepto *', 'Cantidad *', 'Valor unitario *', 'Valor total'],
            'columnasConLista' => [0 => 'Años', 1 => 'Dependencias'],
            'filaEjemplo' => [
                $listasComunes['Años'][0] ?? '',
                $listasComunes['Dependencias'][0] ?? '',
                'Ejemplo: convenio interinstitucional',
                '1',
                '1000000',
                '',
            ],
            'columnaCantidad' => 3,
            'columnaValorUnitario' => 4,
            'columnaValorTotal' => 5,
        ];

        $hojaGastos = [
            'encabezados' => [
                'Año presupuestal *', 'Sede *', 'Dependencia *', 'Proyecto PDI *', 'Contratos comunes',
                'Categoría *', 'Actividad *', 'Rubro *', 'Insumo *', 'Cantidad *', 'Costo unitario *',
                'Valor total', 'Meses de ejecución * (ej: 1,3,5)',
            ],
            'columnasConLista' => [0 => 'Años', 1 => 'Sedes', 2 => 'Dependencias', 3 => 'Proyectos', 4 => 'Contratos', 5 => 'Categorias', 7 => 'Rubros'],
            'filaEjemplo' => [
                $listasComunes['Años'][0] ?? '',
                $listasComunes['Sedes'][0] ?? '',
                $listasComunes['Dependencias'][0] ?? '',
                $listasComunes['Proyectos'][0] ?? '',
                '',
                $listasComunes['Categorias'][0] ?? '',
                'Ejemplo: ejecución del convenio',
                $listasComunes['Rubros'][0] ?? '',
                'Ejemplo: materiales del convenio',
                '1',
                '1000000',
                '',
                '1,2,3',
            ],
            'columnaCantidad' => 9,
            'columnaValorUnitario' => 10,
            'columnaValorTotal' => 11,
            'columnaCategoria' => 5,
        ];

        $porcentajesModulo = $this->modeloPorcentaje->obtenerPorModulo('sin-excedentes');
        $mapaCategoriaPorcentaje = ['Excedentes' => 'excedentes', 'Gastos' => 'costos', 'Inversiones' => 'inversiones'];
        $validacionPorcentajes = array_map(static function (string $etiqueta) use ($porcentajesModulo, $mapaCategoriaPorcentaje): array {
            $clave = $mapaCategoriaPorcentaje[$etiqueta];
            $valor = $porcentajesModulo[$clave] ?? null;

            return ['etiqueta' => $etiqueta, 'porcentaje' => $valor !== null ? (float) $valor : null];
        }, self::CATEGORIAS_VALIDACION_PLANTILLA);

        $metadatos = [
            'plantilla' => 'autogestion-sin-excedentes',
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'usuario_nombre' => $catalogos['usuarioActual']['nombre'] ?? '',
        ];

        GeneradorXlsx::descargarPlantillaAutogestion('plantilla_convenios.xlsx', $hojaIngresos, $hojaGastos, $validacionPorcentajes, $listasComunes, $metadatos);
        exit;
    }

    /**
     * Exporta a .xlsx los ingresos y egresos visibles para el usuario actual en el año
     * presupuestal indicado, en dos hojas ("Ingresos" y "Gastos").
     */
    public function exportar(): void
    {
        if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=login');
            exit;
        }

        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $anioSeleccionadoId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) ($aniosActivos[0]['id'] ?? 0);

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        [, $dependenciasPermitidas] = $this->obtenerDependenciasVisiblesUsuarioActual();

        $ingresos = $anioSeleccionadoId > 0 ? $this->modeloIngreso->obtenerPorAnio($anioSeleccionadoId) : [];
        $ingresos = $this->filtrarPorPropietarioODestinatario($ingresos, $usuarioActual, $dependenciasPermitidas);

        $gastos = $anioSeleccionadoId > 0 ? $this->modeloGasto->obtenerPorAnio($anioSeleccionadoId) : [];
        $gastos = $this->filtrarEgresosVisibles($gastos, $usuarioActual, $dependenciasPermitidas);

        $filaIngreso = static function (array $ingreso): array {
            return [
                $ingreso['dependencia'],
                (string) ($ingreso['concepto_adicional'] ?? ''),
                number_format((float) $ingreso['valor_adicional'], 2, ',', '.'),
                number_format((float) $ingreso['valor_total'], 2, ',', '.'),
                $ingreso['estado'],
            ];
        };

        $filaGasto = static function (array $gasto): array {
            return [
                $gasto['dependencia'],
                $gasto['categoria'],
                $gasto['insumo'],
                (string) (int) $gasto['cantidad'],
                number_format((float) $gasto['costo_unitario'], 2, ',', '.'),
                number_format((float) $gasto['valor_total'], 2, ',', '.'),
                $gasto['estado'],
            ];
        };

        $hojas = [
            ['nombre' => 'Ingresos', 'encabezados' => ['Dependencia', 'Concepto adicional', 'Valor adicional', 'Valor total', 'Estado'], 'filas' => array_map($filaIngreso, $ingresos)],
            ['nombre' => 'Gastos', 'encabezados' => ['Dependencia', 'Categoría', 'Insumo', 'Cantidad', 'Costo unitario', 'Valor total', 'Estado'], 'filas' => array_map($filaGasto, $gastos)],
        ];

        $anioTexto = (string) $anioSeleccionadoId;
        foreach ($aniosActivos as $anioFila) {
            if ((int) $anioFila['id'] === $anioSeleccionadoId) {
                $anioTexto = (string) $anioFila['anio'];
                break;
            }
        }

        GeneradorXlsx::descargarHojas('convenios_' . $anioTexto . '.xlsx', $hojas);
        exit;
    }

    private static function textoProyecto(array $proyecto): string
    {
        return $proyecto['linea_codigo'] . ' · ' . $proyecto['motor_codigo'] . ' · ' . $proyecto['codigo'] . ' - ' . $proyecto['nombre'];
    }

    /**
     * Catálogos y alcance de dependencias del usuario actual, reutilizados por exportarPlantilla()
     * e importar().
     */
    private function construirCatalogos(): array
    {
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $rubros = $this->modeloRubro->obtenerActivosPorCategoria('autogestion');
        $contratosComunes = $this->modeloContratoComun->obtenerActivos();
        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $sedes = $this->modeloSede->obtenerTodas();

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        [, $dependenciasSugeridas] = $this->obtenerDependenciasVisiblesUsuarioActual();

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
     * Importa ingresos y egresos en borrador desde un archivo .xlsx (plantilla generada por
     * exportarPlantilla()): la hoja "Ingresos" se lee y se agrupa PRIMERO (por año+dependencia, en
     * una sola cabecera con varios conceptos), porque el total de cada grupo es el que se usa
     * después para validar el balance de la hoja "Gastos" (ver más abajo). Todo o nada: si
     * cualquier fila de cualquiera de las dos hojas falla una validación, no se importa nada.
     *
     * El presupuesto disponible y el % por categoría NO se validan fila por fila: se acumula
     * primero el total de TODAS las filas de Gastos válidas por cada año+dependencia y recién al
     * final se compara ese total contra lo disponible — así, si el archivo no cabe, el error dice
     * que el valor total que se intentó importar excede lo permitido, en vez de señalar "la última
     * fila", que sería engañoso: como la importación es todo o nada, ninguna fila anterior se llegó
     * a importar tampoco.
     *
     * @return array{0: string, 1: string, 2: string[]} [error general, éxito, lista de errores por fila]
     */
    private function importar(): array
    {
        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            return ['Selecciona un archivo .xlsx válido para importar.', '', []];
        }

        $rutaArchivo = $_FILES['archivo']['tmp_name'];

        try {
            $metadatos = LectorXlsx::leerMetadatos($rutaArchivo);
        } catch (Throwable $excepcion) {
            return ['No se pudo leer el archivo: ' . $excepcion->getMessage(), '', []];
        }

        if (($metadatos['SPPI_Origen'] ?? '') !== GeneradorXlsx::FIRMA_PLATAFORMA || ($metadatos['SPPI_Plantilla'] ?? '') !== 'autogestion-sin-excedentes') {
            return ['Este archivo no parece haber sido descargado desde la plataforma. Usa el botón "Exportar plantilla" para descargar una plantilla nueva y diligénciala sin quitarle sus metadatos.', '', []];
        }

        $filaEncabezadoPlantilla = 4 + count(self::CATEGORIAS_VALIDACION_PLANTILLA);

        // Sheet 1 = Ingresos, sheet 2 = Gastos: se leen en ese orden a propósito, porque los
        // totales de Ingresos son los que se necesitan para validar el balance de Gastos.
        try {
            $filasIngresos = array_slice(LectorXlsx::leerHojaPorNombre($rutaArchivo, 'Ingresos'), $filaEncabezadoPlantilla);
            $filasGastos = array_slice(LectorXlsx::leerHojaPorNombre($rutaArchivo, 'Gastos'), $filaEncabezadoPlantilla);
        } catch (Throwable $excepcion) {
            return ['No se pudo leer el archivo: ' . $excepcion->getMessage(), '', []];
        }

        if (empty($filasIngresos) && empty($filasGastos)) {
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

        // --- 1) Ingresos primero: se agrupan por año + dependencia en una sola cabecera con varios
        // conceptos. Una fila con más de 4 de sus campos obligatorios en blanco se ignora en
        // silencio (fila sin usar; puede traer, por ejemplo, un "0" residual en la columna
        // calculada "Valor total" tras abrir la plantilla en Excel) en vez de reportarse como error. ---
        $gruposIngreso = [];

        foreach ($filasIngresos as $indice => $fila) {
            $numeroFilaExcel = $indice + $filaEncabezadoPlantilla + 1;

            $anioTexto = trim($fila[0] ?? '');
            $dependenciaTexto = trim($fila[1] ?? '');
            $conceptoTexto = trim($fila[2] ?? '');
            $cantidadTexto = trim($fila[3] ?? '');
            $valorTexto = trim($fila[4] ?? '');

            $vacios = count(array_filter(
                [$anioTexto, $dependenciaTexto, $conceptoTexto, $cantidadTexto, $valorTexto],
                static fn (string $valor): bool => $valor === ''
            ));

            if ($vacios > 4) {
                continue;
            }

            if ($vacios > 0) {
                $errores[] = "Ingresos, fila $numeroFilaExcel: todos los campos obligatorios (*) deben estar diligenciados.";
                continue;
            }

            if (!isset($mapaAnios[$anioTexto])) {
                $errores[] = "Ingresos, fila $numeroFilaExcel: el año \"$anioTexto\" no es válido. Usa el desplegable de la columna.";
                continue;
            }

            if (!in_array($dependenciaTexto, $dependenciasPermitidas, true)) {
                $errores[] = "Ingresos, fila $numeroFilaExcel: la dependencia \"$dependenciaTexto\" no está disponible para tu usuario.";
                continue;
            }

            if (!is_numeric($cantidadTexto) || (int) $cantidadTexto <= 0) {
                $errores[] = "Ingresos, fila $numeroFilaExcel: la cantidad debe ser un número entero mayor a 0.";
                continue;
            }

            if (!is_numeric($valorTexto) || (float) $valorTexto < 0) {
                $errores[] = "Ingresos, fila $numeroFilaExcel: el valor unitario debe ser un número válido.";
                continue;
            }

            $anioId = $mapaAnios[$anioTexto];
            $clave = $anioId . ':' . $dependenciaTexto;

            if (!isset($gruposIngreso[$clave])) {
                $gruposIngreso[$clave] = [
                    'anio_presupuestal_id' => $anioId,
                    'dependencia' => $dependenciaTexto,
                    'concepto_adicional' => '',
                    'valor_adicional' => 0.0,
                    'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
                    'conceptos' => [],
                ];
            }

            $gruposIngreso[$clave]['conceptos'][] = [
                'concepto' => $conceptoTexto,
                'cantidad' => (int) $cantidadTexto,
                'valor' => (float) $valorTexto,
            ];
        }

        $totalIngresosPorGrupo = [];
        foreach ($gruposIngreso as $clave => $grupo) {
            $totalIngresosPorGrupo[$clave] = array_sum(array_map(
                static fn (array $c): float => $c['cantidad'] * $c['valor'],
                $grupo['conceptos']
            ));
        }

        // --- 2) Gastos: se valida cada fila de forma individual (catálogos, numéricos, campos
        // obligatorios) y se guarda como candidata; el presupuesto y el % por categoría se validan
        // aparte, después, sobre el total acumulado de todas las candidatas (ver más abajo). ---
        $filasGastoCandidatas = [];

        foreach ($filasGastos as $indice => $fila) {
            $numeroFilaExcel = $indice + $filaEncabezadoPlantilla + 1;

            $anioTexto = trim($fila[0] ?? '');
            $sedeTexto = trim($fila[1] ?? '');
            $dependenciaTexto = trim($fila[2] ?? '');
            $proyectoTexto = trim($fila[3] ?? '');
            $contratoTexto = trim($fila[4] ?? '');
            $categoriaTexto = trim($fila[5] ?? '');
            $actividad = trim($fila[6] ?? '');
            $rubroTexto = trim($fila[7] ?? '');
            $insumo = trim($fila[8] ?? '');
            $cantidadTexto = trim($fila[9] ?? '');
            $costoTexto = trim($fila[10] ?? '');
            $mesesTexto = trim($fila[12] ?? '');

            $vacios = count(array_filter(
                [$anioTexto, $sedeTexto, $dependenciaTexto, $proyectoTexto, $categoriaTexto, $actividad, $rubroTexto, $insumo, $cantidadTexto, $costoTexto, $mesesTexto],
                static fn (string $valor): bool => $valor === ''
            ));

            if ($vacios > 4) {
                continue;
            }

            if ($vacios > 0) {
                $errores[] = "Gastos, fila $numeroFilaExcel: todos los campos obligatorios (*) deben estar diligenciados.";
                continue;
            }

            if (!isset($mapaAnios[$anioTexto])) {
                $errores[] = "Gastos, fila $numeroFilaExcel: el año \"$anioTexto\" no es válido. Usa el desplegable de la columna.";
                continue;
            }

            if (!isset($mapaSedes[$sedeTexto])) {
                $errores[] = "Gastos, fila $numeroFilaExcel: la sede \"$sedeTexto\" no es válida. Usa el desplegable de la columna.";
                continue;
            }

            if (!in_array($dependenciaTexto, $dependenciasPermitidas, true)) {
                $errores[] = "Gastos, fila $numeroFilaExcel: la dependencia \"$dependenciaTexto\" no está disponible para tu usuario.";
                continue;
            }

            if (!isset($mapaProyectos[$proyectoTexto])) {
                $errores[] = "Gastos, fila $numeroFilaExcel: el proyecto PDI \"$proyectoTexto\" no es válido. Usa el desplegable de la columna.";
                continue;
            }

            if ($contratoTexto !== '' && !in_array($contratoTexto, $codigosContratos, true)) {
                $errores[] = "Gastos, fila $numeroFilaExcel: el contrato común \"$contratoTexto\" no es válido. Usa el desplegable de la columna.";
                continue;
            }

            if (!in_array($categoriaTexto, self::CATEGORIAS_EGRESO, true)) {
                $errores[] = "Gastos, fila $numeroFilaExcel: la categoría \"$categoriaTexto\" no es válida. Usa el desplegable de la columna.";
                continue;
            }

            if (!isset($mapaRubros[$rubroTexto])) {
                $errores[] = "Gastos, fila $numeroFilaExcel: el rubro \"$rubroTexto\" no es válido. Usa el desplegable de la columna.";
                continue;
            }

            if (!is_numeric($cantidadTexto) || (int) $cantidadTexto <= 0) {
                $errores[] = "Gastos, fila $numeroFilaExcel: la cantidad debe ser un número entero mayor a 0.";
                continue;
            }

            if (!is_numeric($costoTexto) || (float) $costoTexto < 0) {
                $errores[] = "Gastos, fila $numeroFilaExcel: el costo unitario debe ser un número válido.";
                continue;
            }

            $meses = array_unique(array_filter(
                array_map('intval', array_map('trim', explode(',', $mesesTexto))),
                static fn (int $mes): bool => $mes >= 1 && $mes <= 12
            ));

            if (empty($meses)) {
                $errores[] = "Gastos, fila $numeroFilaExcel: los meses de ejecución deben ser números entre 1 y 12 separados por coma (ej: 1,2,3).";
                continue;
            }

            sort($meses);
            $proyecto = $mapaProyectos[$proyectoTexto];
            $anioId = $mapaAnios[$anioTexto];

            $filasGastoCandidatas[] = [
                'claveGrupo' => $anioId . ':' . $dependenciaTexto,
                'categoria' => $categoriaTexto,
                'nuevoValor' => (int) $cantidadTexto * (float) $costoTexto,
                'datos' => [
                    'anio_presupuestal_id' => $anioId,
                    'sede_id' => $mapaSedes[$sedeTexto],
                    'categoria' => $categoriaTexto,
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
                ],
            ];
        }

        // --- 3) Presupuesto y % por categoría: sobre el TOTAL de lo que se intenta importar por
        // cada año+dependencia, no fila por fila. ---
        $totalesPorGrupo = [];
        foreach ($filasGastoCandidatas as $candidata) {
            $clave = $candidata['claveGrupo'];

            if (!isset($totalesPorGrupo[$clave])) {
                $totalesPorGrupo[$clave] = [
                    'anio_presupuestal_id' => $candidata['datos']['anio_presupuestal_id'],
                    'dependencia' => $candidata['datos']['dependencia'],
                    'total' => 0.0,
                    'categorias' => [],
                ];
            }

            $totalesPorGrupo[$clave]['total'] += $candidata['nuevoValor'];
            $totalesPorGrupo[$clave]['categorias'][$candidata['categoria']]
                = ($totalesPorGrupo[$clave]['categorias'][$candidata['categoria']] ?? 0.0) + $candidata['nuevoValor'];
        }

        $porcentajesModulo = $this->modeloPorcentaje->obtenerPorModulo('sin-excedentes');
        $mapaCategoriaPorcentaje = ['Excedentes' => 'excedentes', 'Gastos' => 'costos', 'Inversiones' => 'inversiones'];

        foreach ($totalesPorGrupo as $clave => $grupo) {
            $anioId = $grupo['anio_presupuestal_id'];
            $dependenciaTexto = $grupo['dependencia'];

            $ingresosExistentes = $this->modeloIngreso->obtenerTotalPorAnioYDependencias($anioId, [$dependenciaTexto]);
            $ingresosNuevos = $totalIngresosPorGrupo[$clave] ?? 0.0;
            $ingresosDisponibles = $ingresosExistentes + $ingresosNuevos;
            $totalExistente = $this->modeloGasto->obtenerTotalPorAnioYDependencias($anioId, [$dependenciaTexto]);

            if ($totalExistente + $grupo['total'] > $ingresosDisponibles) {
                $disponible = max(0, $ingresosDisponibles - $totalExistente);
                $errores[] = "Gastos, \"$dependenciaTexto\": el valor total de gastos que intentas importar ("
                    . number_format($grupo['total'], 2, ',', '.') . ') excede el disponible (' . number_format($disponible, 2, ',', '.') . '). '
                    . 'Ingresos de "' . $dependenciaTexto . '" para este año: ' . number_format($ingresosExistentes, 2, ',', '.') . ' ya registrados + '
                    . number_format($ingresosNuevos, 2, ',', '.') . ' nuevos en la hoja "Ingresos" de este archivo.'
                    . ($ingresosNuevos <= 0 ? ' No se detectó ninguna fila de Ingresos para esta dependencia en este archivo: revisa que el nombre de la dependencia y el año coincidan exactamente (elegidos del desplegable) en ambas hojas.' : '');
                continue;
            }

            foreach ($grupo['categorias'] as $categoria => $totalCategoria) {
                $clavePorcentaje = $mapaCategoriaPorcentaje[$categoria] ?? null;

                if ($clavePorcentaje === null || $porcentajesModulo[$clavePorcentaje] === null) {
                    continue;
                }

                $valorEsperadoCategoria = round($ingresosDisponibles * (float) $porcentajesModulo[$clavePorcentaje] / 100, 2);

                // Excedentes se calcula SOLO a partir del ingreso de esta importación (% × ingresos
                // disponibles de esta dependencia): no se compara contra lo que ya exista en la base
                // de datos para "Excedentes" en esa dependencia, porque esa dependencia puede tener
                // registros de otras personas o de pruebas anteriores que no son parte de este
                // archivo — si no hay ingreso, el excedente esperado es 0, sin importar qué otro
                // valor exista ya. Para Gastos/Inversiones sí se sigue acumulando contra lo
                // existente, porque esas categorías sí son de cupo compartido con el resto del año.
                if ($categoria === 'Excedentes') {
                    if (abs(round($totalCategoria, 2) - $valorEsperadoCategoria) > 0.01) {
                        $porcentajeTexto = rtrim(rtrim(number_format((float) $porcentajesModulo[$clavePorcentaje], 2), '0'), '.');
                        $errores[] = "Gastos, \"$dependenciaTexto\", categoría Excedentes: el valor que intentas importar (" . number_format($totalCategoria, 2, ',', '.')
                            . ") debe corresponder exactamente al {$porcentajeTexto}% de los ingresos de esta dependencia (" . number_format($valorEsperadoCategoria, 2, ',', '.') . ').';
                    }
                    continue;
                }

                $totalExistenteCategoria = $this->modeloGasto->obtenerTotalPorAnioYCategoriaYDependencias($anioId, $categoria, [$dependenciaTexto]);
                $totalRealCategoria = round($totalExistenteCategoria + $totalCategoria, 2);

                if ($totalRealCategoria > $valorEsperadoCategoria) {
                    $disponibleCategoria = max(0, $valorEsperadoCategoria - $totalExistenteCategoria);
                    $errores[] = "Gastos, \"$dependenciaTexto\", categoría $categoria: el valor total que intentas importar ("
                        . number_format($totalCategoria, 2, ',', '.') . ') excede el % disponible (' . number_format($disponibleCategoria, 2, ',', '.') . ').';
                }
            }
        }

        if (!empty($errores)) {
            return ['No se importó ningún registro porque el valor total a importar excede lo permitido o se encontraron otros errores:', '', $errores];
        }

        if (empty($gruposIngreso) && empty($filasGastoCandidatas)) {
            return ['No hay filas válidas para importar.', '', []];
        }

        $db = Conexion::obtener();
        $db->beginTransaction();

        try {
            $gruposAfectados = [];

            foreach ($gruposIngreso as $clave => $grupo) {
                $conceptos = $grupo['conceptos'];
                unset($grupo['conceptos']);
                $grupo['valor_total'] = $totalIngresosPorGrupo[$clave] ?? 0.0;
                $this->modeloIngreso->crear($grupo, $conceptos);
                $gruposAfectados[$grupo['anio_presupuestal_id'] . ':' . $grupo['dependencia']] = $grupo;
            }

            foreach ($filasGastoCandidatas as $candidata) {
                $this->modeloGasto->crear($candidata['datos']);
            }

            $db->commit();
        } catch (PDOException $excepcion) {
            $db->rollBack();
            return ['No se pudo importar el archivo: ' . $excepcion->getMessage(), '', []];
        }

        foreach ($gruposAfectados as $grupo) {
            $this->generarEgresosAutomaticos(null, $grupo['anio_presupuestal_id'], $grupo['dependencia']);
        }

        $mensaje = count($gruposIngreso) . ' ingreso(s) y ' . count($filasGastoCandidatas) . ' gasto(s) importado(s) correctamente como borrador.';

        return ['', $mensaje, []];
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

            return ['Este egreso supera los ingresos disponibles de este año. Disponible: ' . number_format($disponible, 2, ',', '.') . '.', ''];
        }

        $errorCategoria = $this->validarLimiteCategoria($datos['anio_presupuestal_id'], $datos['categoria'], $nuevoValor, $ingresosDisponibles, 0.0, $dependenciasPermitidas);

        if ($errorCategoria !== '') {
            return [$errorCategoria, ''];
        }

        try {
            $this->modeloGasto->crear($datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo registrar el egreso: ' . $excepcion->getMessage(), ''];
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

            return ['Este egreso supera los ingresos disponibles de este año. Disponible: ' . number_format($disponible, 2, ',', '.') . '.', ''];
        }

        $valorExcluidoCategoria = $existente['categoria'] === $datos['categoria'] ? (float) $existente['valor_total'] : 0.0;
        $errorCategoria = $this->validarLimiteCategoria($datos['anio_presupuestal_id'], $datos['categoria'], $nuevoValor, $ingresosDisponibles, $valorExcluidoCategoria, $dependenciasPermitidas);

        if ($errorCategoria !== '') {
            return [$errorCategoria, ''];
        }

        try {
            $this->modeloGasto->actualizar($id, $datos);
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar el egreso: ' . $excepcion->getMessage(), ''];
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen('gasto_sin_excedentes', $id, $nuevoValor, $datos['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=sin-excedentes&tab=egresos&anio_id=' . $datos['anio_presupuestal_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function validarLimiteCategoria(int $anioPresupuestalId, string $categoria, float $nuevoValor, float $totalIngresos, float $valorExcluido = 0.0, array $dependenciasPermitidas = []): string
    {
        $mapaCategoriaPorcentaje = ['Excedentes' => 'excedentes', 'Gastos' => 'costos', 'Inversiones' => 'inversiones'];
        $clavePorcentaje = $mapaCategoriaPorcentaje[$categoria] ?? null;

        if ($clavePorcentaje === null) {
            return '';
        }

        $porcentajes = $this->modeloPorcentaje->obtenerPorModulo('sin-excedentes');

        if ($porcentajes[$clavePorcentaje] === null) {
            return '';
        }

        $limiteCategoria = round($totalIngresos * (float) $porcentajes[$clavePorcentaje] / 100, 2);
        $totalCategoriaActual = $this->modeloGasto->obtenerTotalPorAnioYCategoriaYDependencias($anioPresupuestalId, $categoria, $dependenciasPermitidas) - $valorExcluido;

        if ($totalCategoriaActual + $nuevoValor > $limiteCategoria) {
            $disponibleCategoria = max(0, $limiteCategoria - $totalCategoriaActual);

            return "Este egreso supera el porcentaje disponible para {$categoria}. Disponible: " . number_format($disponibleCategoria, 2, ',', '.') . '.';
        }

        return '';
    }

    private function enviarTodo(): array
    {
        $anioId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $dependenciaDestinoNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);
        $categoriaPeticion = trim($_POST['categoria_peticion'] ?? '');

        if ($anioId <= 0 || $dependenciaDestinoNombre === '' || $rolDestinatarioId <= 0) {
            return ['Selecciona a quién se enviará y el rol al que se enviarán los ingresos y egresos.', ''];
        }

        if (!in_array($categoriaPeticion, ['extension', 'postgrado'], true)) {
            return ['Selecciona a qué categoría de Peticiones pertenece este envío: Convenios y Asesorías o Convenios Postgrados.', ''];
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

        // Una vez resuelto (único con ese rol, o desambiguado arriba), se guarda quién es
        // exactamente el destinatario — si no, cualquiera con ese rol en la dependencia vería la
        // petición en Pendientes, no solo la persona elegida.
        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;

        $enviadosIngresos = $this->modeloIngreso->enviarTodosBorrador($anioId, $dependenciaDestinoNombre, $rolDestinatarioId, $categoriaPeticion, $dependenciasPermitidasEnvio, $usuarioDestinatarioResuelto);
        $enviadosEgresos = $this->modeloGasto->enviarTodosBorrador($anioId, $dependenciaDestinoNombre, $rolDestinatarioId, $categoriaPeticion, $dependenciasPermitidasEnvio, $usuarioDestinatarioResuelto);

        if ($enviadosIngresos === 0 && $enviadosEgresos === 0) {
            return ['No hay ingresos ni egresos en borrador para enviar.', ''];
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear(
                $remitenteId,
                (int) $destinatario['id'],
                'Convenios enviado',
                'Se enviaron ' . $enviadosIngresos . ' ingreso(s) y ' . $enviadosEgresos . ' egreso(s) de Convenios para tu revisión.'
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
            return ['No se pudo registrar el ingreso: ' . $excepcion->getMessage(), ''];
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
            return ['No se pudo actualizar el ingreso: ' . $excepcion->getMessage(), ''];
        }

        $this->generarEgresosAutomaticos($id, $cabecera['anio_presupuestal_id'], $cabecera['dependencia']);

        (new PeticionArchivada())->sincronizarDesdeOrigen('ingreso_sin_excedentes', $id, $cabecera['valor_total'], $cabecera['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=sin-excedentes&tab=ingresos&anio_id=' . $cabecera['anio_presupuestal_id'];
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

                if ($existente !== null && $existente['tipo_automatico'] === null && DuplicadorFilas::duplicarFila($db, 'gastos_sin_excedentes', $id) !== null) {
                    $duplicados++;
                }
            } else {
                $nuevoId = DuplicadorFilas::duplicarFila($db, 'ingresos_sin_excedentes', $id);

                if ($nuevoId !== null) {
                    DuplicadorFilas::duplicarFilasHijo($db, 'ingresos_sin_excedentes_conceptos', 'ingreso_id', $id, $nuevoId);
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
        $porcentajes = $this->modeloPorcentaje->obtenerPorModulo('sin-excedentes');
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
            $existente = $this->modeloGasto->obtenerAutomaticoPorTipo($anioPresupuestalId, $tipo);

            // Si ya no hay ingresos que la sustenten (p. ej. tras eliminar el último), la fila
            // automática desaparece en vez de quedar visible en $0.00.
            if ($valor <= 0) {
                if ($existente !== null) {
                    $this->modeloGasto->eliminarAutomaticoPorId((int) $existente['id']);
                }
                continue;
            }

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
