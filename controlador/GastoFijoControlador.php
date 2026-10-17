<?php

require_once __DIR__ . '/../modelo/GastoFijo.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Rubro.php';
require_once __DIR__ . '/../modelo/ContratoComun.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/CategoriaGasto.php';
require_once __DIR__ . '/../modelo/GeneradorXlsx.php';
require_once __DIR__ . '/../modelo/LectorXlsx.php';

/**
 * ?ruta=gastos-fijos (Configuraciones, solo `usuarios.es_super_admin`): CRUD + plantilla /
 * importar / exportar de los gastos fijos (modelo GastoFijo). Mismo formulario, mismos campos y
 * misma validación de campos que Gastos (ver GastoControlador::validarDatos() /
 * validarFilaImportacion()), pero SIN techo presupuestal, sin restricción de dependencia por
 * usuario y sin estado/envío/historial: los gastos fijos solo se reflejan en Análisis de
 * distribución (módulo Gasto), nunca en Gastos, Techos, Techos y Metas, Dashboard ni Articulación PDI.
 */
class GastoFijoControlador
{
    private const CAMPOS_REQUERIDOS = [
        'sede_id', 'anio_presupuestal_id', 'dependencia', 'proyecto_id', 'actividad', 'rubro_id',
        'insumo', 'cantidad', 'costo_unitario',
    ];

    private GastoFijo $modelo;

    public function __construct()
    {
        $this->modelo = new GastoFijo();
    }

    public function index(): void
    {
        $this->requerirSuperAdmin();

        $catalogos = $this->construirCatalogos();
        $anioSeleccionadoId = $this->resolverAnio($catalogos['aniosActivos']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? 'crear';
            $erroresFila = [];

            if ($accion === 'eliminar') {
                $id = (int) ($_POST['id'] ?? 0);
                [$error, $exito] = $id > 0 && $this->modelo->obtenerPorId($id) !== null
                    ? (($this->modelo->eliminar($id)) ? ['', 'Gasto fijo eliminado.'] : ['No se pudo eliminar el gasto fijo.', ''])
                    : ['El gasto fijo ya no existe.', ''];
            } elseif ($accion === 'importar') {
                [$error, $exito, $erroresFila] = $this->importar($catalogos);
            } else {
                [$datos, $error] = $this->validarDatos($_POST, $catalogos);
                $exito = '';
                if ($error === '') {
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($accion === 'actualizar' && $id > 0) {
                        $this->modelo->actualizar($id, $datos);
                        $exito = 'Gasto fijo actualizado.';
                    } else {
                        $this->modelo->crear($datos);
                        $exito = 'Gasto fijo registrado.';
                    }
                    $anioSeleccionadoId = (int) $datos['anio_presupuestal_id'];
                }
            }

            $_SESSION['gastos_fijos_flash'] = ['error' => $error, 'exito' => $exito, 'errores_fila' => $erroresFila];
            header('Location: index.php?ruta=gastos-fijos&anio_id=' . $anioSeleccionadoId);
            exit;
        }

        $flash = $_SESSION['gastos_fijos_flash'] ?? ['error' => '', 'exito' => '', 'errores_fila' => []];
        unset($_SESSION['gastos_fijos_flash']);
        $error = $flash['error'];
        $exito = $flash['exito'];
        $erroresFila = $flash['errores_fila'];

        $gastosFijos = $anioSeleccionadoId > 0 ? $this->modelo->obtenerPorAnio($anioSeleccionadoId) : [];
        $gastoFijoParaEditar = isset($_GET['editar_id']) ? $this->modelo->obtenerPorId((int) $_GET['editar_id']) : null;
        $categoriaGasto = new CategoriaGasto();
        $catalogoCategoriasGasto = $categoriaGasto->obtenerCatalogo();
        $mapeoCategoriasGasto = $categoriaGasto->obtenerMapeo();
        extract($catalogos);

        require __DIR__ . '/../vista/gastos-fijos/index.php';
    }

    /** Plantilla .xlsx igual a la de Gastos pero sin la fila de techo (encabezados en la fila 1). */
    public function exportarPlantilla(): void
    {
        $this->requerirSuperAdmin();
        $catalogos = $this->construirCatalogos();

        $encabezados = [
            'Año presupuestal *', 'Sede *', 'Dependencia *', 'Proyecto PDI *', 'Contratos comunes',
            'Actividad *', 'Rubro *', 'Insumo *', 'Cantidad *', 'Costo unitario *', 'Valor total',
            'Meses de ejecución * (ej: 1,3,5)',
        ];
        $listas = [
            'Años' => array_map(static fn (array $a): string => (string) $a['anio'], $catalogos['aniosActivos']),
            'Sedes' => array_map(static fn (array $s): string => $s['codigo'] . ' - ' . $s['nombre'], $catalogos['sedes']),
            'Dependencias' => $catalogos['dependenciasOpciones'],
            'Proyectos' => array_map(static fn (array $p): string => self::textoProyecto($p), $catalogos['proyectos']),
            'Contratos' => array_map(static fn (array $c): string => $c['codigo'], $catalogos['contratosComunes']),
            'Rubros' => array_map(static fn (array $r): string => $r['codigo'] . ' - ' . $r['descripcion'], $catalogos['rubros']),
        ];
        $columnasConLista = [0 => 'Años', 1 => 'Sedes', 2 => 'Dependencias', 3 => 'Proyectos', 4 => 'Contratos', 6 => 'Rubros'];
        $filaEjemplo = [
            $listas['Años'][0] ?? '', $listas['Sedes'][0] ?? '', $listas['Dependencias'][0] ?? '', $listas['Proyectos'][0] ?? '', '',
            'Ejemplo: servicio de vigilancia', $listas['Rubros'][0] ?? '', 'Ejemplo: contrato anual', '1', '1000000', '', '1,2,3',
        ];
        $metadatos = [
            'plantilla' => 'gastos_fijos',
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'usuario_nombre' => $_SESSION['usuario_nombre'] ?? '',
        ];

        GeneradorXlsx::descargar('plantilla_gastos_fijos.xlsx', $encabezados, $columnasConLista, $listas, $filaEjemplo, $metadatos, null);
        exit;
    }

    /** Exporta los gastos fijos del año en una hoja, con las mismas columnas que la exportación de Gastos. */
    public function exportar(): void
    {
        $this->requerirSuperAdmin();
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioSeleccionadoId = $this->resolverAnio($aniosActivos);
        $gastosFijos = $anioSeleccionadoId > 0 ? $this->modelo->obtenerPorAnio($anioSeleccionadoId) : [];

        $nombresMeses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        $encabezados = [
            'Sede', 'Dependencia', 'Proyecto PDI', 'Contratos comunes', 'Actividad', 'Rubro',
            'Insumo', 'Cantidad', 'Costo unitario', 'Valor total', 'Meses',
            'Línea estratégica', 'Motor de desarrollo',
        ];
        $filas = array_map(static function (array $gasto) use ($nombresMeses): array {
            $meses = $gasto['meses'] !== ''
                ? array_map(static fn ($mes) => $nombresMeses[(int) $mes] ?? $mes, explode(',', $gasto['meses']))
                : [];

            return [
                $gasto['sede_codigo'] . ' - ' . $gasto['sede_nombre'],
                $gasto['dependencia'],
                $gasto['proyecto_codigo'] . ' - ' . $gasto['proyecto_nombre'],
                (string) ($gasto['objeto_proyecto_paa'] ?? ''),
                $gasto['actividad'],
                $gasto['rubro_id'] !== null ? $gasto['rubro_codigo'] . ' - ' . $gasto['rubro_descripcion'] : '—',
                $gasto['insumo'],
                (string) (int) $gasto['cantidad'],
                number_format((float) $gasto['costo_unitario'], 2, ',', '.'),
                number_format((float) $gasto['valor_total'], 2, ',', '.'),
                implode(', ', $meses),
                $gasto['linea_codigo'] . ' - ' . $gasto['linea_nombre'],
                $gasto['motor_codigo'] . ' - ' . $gasto['motor_nombre'],
            ];
        }, $gastosFijos);

        $anioTexto = (string) $anioSeleccionadoId;
        foreach ($aniosActivos as $anioFila) {
            if ((int) $anioFila['id'] === $anioSeleccionadoId) {
                $anioTexto = (string) $anioFila['anio'];
                break;
            }
        }

        GeneradorXlsx::descargarHojas('gastos_fijos_' . $anioTexto . '.xlsx', [['nombre' => 'Gastos fijos', 'encabezados' => $encabezados, 'filas' => $filas]]);
        exit;
    }

    /**
     * Igual que GastoControlador::importar(), sin techo: todo o nada (si alguna fila falla no se
     * importa ninguna). @return array{0: string, 1: string, 2: string[]}
     */
    private function importar(array $catalogos): array
    {
        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            return ['Selecciona un archivo .xlsx válido para importar.', '', []];
        }

        try {
            $metadatos = LectorXlsx::leerMetadatos($_FILES['archivo']['tmp_name']);
            $filas = LectorXlsx::leerPrimeraHoja($_FILES['archivo']['tmp_name']);
        } catch (Throwable $excepcion) {
            return ['No se pudo leer el archivo: ' . $excepcion->getMessage(), '', []];
        }

        if (($metadatos['SPPI_Origen'] ?? '') !== GeneradorXlsx::FIRMA_PLATAFORMA || ($metadatos['SPPI_Plantilla'] ?? '') !== 'gastos_fijos') {
            return ['Este archivo no es una plantilla de Gastos fijos. Descarga la plantilla desde esta pantalla y diligénciala sin quitarle sus metadatos.', '', []];
        }

        // Sin fila de techo: solo se descarta la fila de encabezados.
        array_shift($filas);
        $filas = array_values($filas);

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
        foreach ($filas as $indice => $fila) {
            $numeroFilaExcel = $indice + 2;
            [$datos, $errorFila] = $this->validarFilaImportacion($fila, $mapaAnios, $mapaSedes, $catalogos['dependenciasOpciones'], $mapaProyectos, $codigosContratos, $mapaRubros);
            if ($datos === null) {
                if ($errorFila !== '') {
                    $errores[] = "Fila $numeroFilaExcel: $errorFila";
                }
                continue;
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
                $this->modelo->crear($datos);
            }
            $db->commit();
        } catch (PDOException $excepcion) {
            $db->rollBack();

            return ['No se pudo importar el archivo. Verifica los datos e inténtalo de nuevo.', '', []];
        }

        return ['', count($filasValidas) . ' gasto(s) fijo(s) importado(s) correctamente.', []];
    }

    /** Misma validación por fila que GastoControlador::validarFilaImportacion() (sin techo). */
    private function validarFilaImportacion(array $fila, array $mapaAnios, array $mapaSedes, array $dependencias, array $mapaProyectos, array $codigosContratos, array $mapaRubros): array
    {
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
        $mesesTexto = trim($fila[11] ?? '');

        $vacios = count(array_filter(
            [$anioTexto, $sedeTexto, $dependenciaTexto, $proyectoTexto, $actividad, $rubroTexto, $insumo, $cantidadTexto, $costoTexto, $mesesTexto],
            static fn (string $valor): bool => $valor === ''
        ));
        if ($vacios > 4) {
            return [null, ''];
        }
        if ($vacios > 0) {
            return [null, 'todos los campos obligatorios (*) deben estar diligenciados.'];
        }
        if (!isset($mapaAnios[$anioTexto])) {
            return [null, "el año \"$anioTexto\" no es válido. Usa el desplegable de la columna."];
        }
        if (!isset($mapaSedes[$sedeTexto])) {
            return [null, "la sede \"$sedeTexto\" no es válida. Usa el desplegable de la columna."];
        }
        if (!in_array($dependenciaTexto, $dependencias, true)) {
            return [null, "la dependencia \"$dependenciaTexto\" no es válida. Usa el desplegable de la columna."];
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

        return [[
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
        ], ''];
    }

    /** Misma validación de campos que GastoControlador::validarDatos() — sin techo ni permiso de dependencia. */
    private function validarDatos(array $entrada, array $catalogos): array
    {
        $datos = [];
        foreach ($entrada as $campo => $valor) {
            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        foreach (self::CAMPOS_REQUERIDOS as $campo) {
            if (($datos[$campo] ?? '') === '') {
                return [[], 'Todos los campos son obligatorios.'];
            }
        }
        if (!in_array((int) $datos['anio_presupuestal_id'], array_map('intval', array_column($catalogos['aniosActivos'], 'id')), true)) {
            return [[], 'Selecciona un año presupuestal activo.'];
        }
        if (!in_array((int) $datos['sede_id'], array_map('intval', array_column($catalogos['sedes'], 'id')), true)) {
            return [[], 'Selecciona una sede válida.'];
        }
        if (!in_array($datos['dependencia'], $catalogos['dependenciasOpciones'], true)) {
            return [[], 'Selecciona una dependencia de la lista.'];
        }
        if (!in_array((int) $datos['rubro_id'], array_map('intval', array_column($catalogos['rubros'], 'id')), true)) {
            return [[], 'Selecciona un rubro de la lista.'];
        }
        if (!is_numeric($datos['cantidad']) || (int) $datos['cantidad'] <= 0) {
            return [[], 'La cantidad debe ser un número entero mayor a 0.'];
        }
        if (!is_numeric($datos['costo_unitario']) || (float) $datos['costo_unitario'] < 0) {
            return [[], 'El costo unitario debe ser un número válido.'];
        }

        $meses = array_filter(array_map('intval', (array) ($datos['meses'] ?? [])), static fn (int $mes): bool => $mes >= 1 && $mes <= 12);
        if (empty($meses)) {
            return [[], 'Selecciona al menos un mes de ejecución.'];
        }
        $meses = array_unique($meses);
        sort($meses);

        $proyectoPdi = (new Proyecto())->obtenerPorId((int) $datos['proyecto_id']);
        if ($proyectoPdi === null) {
            return [[], 'Selecciona un proyecto PDI válido.'];
        }

        $contrato = (string) ($datos['objeto_proyecto_paa'] ?? '');
        if ($contrato !== '' && !in_array($contrato, array_column($catalogos['contratosComunes'], 'codigo'), true)) {
            return [[], 'Selecciona un contrato común de la lista.'];
        }

        $limpio = [
            'anio_presupuestal_id' => (int) $datos['anio_presupuestal_id'],
            'sede_id' => (int) $datos['sede_id'],
            'dependencia' => $datos['dependencia'],
            'proyecto_id' => (int) $proyectoPdi['id'],
            'motor_id' => (int) $proyectoPdi['motor_id'],
            'linea_id' => (int) $proyectoPdi['linea_id'],
            'objeto_proyecto_paa' => $contrato,
            'actividad' => mb_substr((string) $datos['actividad'], 0, 255),
            'rubro_id' => (int) $datos['rubro_id'],
            'insumo' => mb_substr((string) $datos['insumo'], 0, 200),
            'cantidad' => (int) $datos['cantidad'],
            'costo_unitario' => (float) $datos['costo_unitario'],
            'meses' => implode(',', $meses),
            'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
            'categoria_gasto_id' => null,
            'categoria_origen' => null,
            'categoria_confianza' => null,
        ];

        $categoriaId = trim((string) ($datos['categoria_gasto_id'] ?? ''));
        if ($categoriaId !== '') {
            if (!(new CategoriaGasto())->existeCategoria($categoriaId)) {
                return [[], 'La categoría de gasto seleccionada no existe.'];
            }
            $origen = ($datos['categoria_origen'] ?? '') === 'automatico' ? 'automatico' : 'manual';
            $limpio['categoria_gasto_id'] = $categoriaId;
            $limpio['categoria_origen'] = $origen;
            $limpio['categoria_confianza'] = $origen === 'automatico' && is_numeric($datos['categoria_confianza'] ?? null)
                ? round(min(1.0, max(0.0, (float) $datos['categoria_confianza'])), 3)
                : null;
        }

        return [$limpio, ''];
    }

    private function construirCatalogos(): array
    {
        return [
            'aniosActivos' => (new AnioPresupuestal())->obtenerActivos(),
            'sedes' => (new Sede())->obtenerTodas(),
            'proyectos' => (new Proyecto())->obtenerTodos(),
            'rubros' => (new Rubro())->obtenerActivosPorCategoria('egresos'),
            'contratosComunes' => (new ContratoComun())->obtenerActivos(),
            // Todas las dependencias activas (el SA carga gastos fijos de cualquiera).
            'dependenciasOpciones' => array_column((new Dependencia())->obtenerActivas(), 'nombre'),
        ];
    }

    private function resolverAnio(array $aniosActivos): int
    {
        $ids = array_map('intval', array_column($aniosActivos, 'id'));
        $solicitado = (int) ($_GET['anio_id'] ?? 0);

        return in_array($solicitado, $ids, true) ? $solicitado : ($ids[0] ?? 0);
    }

    private static function textoProyecto(array $proyecto): string
    {
        return $proyecto['linea_codigo'] . ' · ' . $proyecto['motor_codigo'] . ' · ' . $proyecto['codigo'] . ' - ' . $proyecto['nombre'];
    }

    private function requerirSuperAdmin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        if ($usuarioActual === null || (int) ($usuarioActual['es_super_admin'] ?? 0) !== 1) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }
}
