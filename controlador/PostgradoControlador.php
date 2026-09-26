<?php

require_once __DIR__ . '/../modelo/GastoPostgrado.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/DuplicadorFilas.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Rubro.php';
require_once __DIR__ . '/../modelo/ContratoComun.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/AutogestionItem.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/PeticionArchivada.php';
require_once __DIR__ . '/../modelo/GeneradorXlsx.php';
require_once __DIR__ . '/../modelo/LectorXlsx.php';
require_once __DIR__ . '/../modelo/AutogestionAutomaticoPermiso.php';
require_once __DIR__ . '/../modelo/AutogestionAutomaticoDefinicion.php';
require_once __DIR__ . '/../modelo/EnvioLote.php';
require_once __DIR__ . '/../modelo/VistaLoteEnvio.php';
require_once __DIR__ . '/../modelo/PeticionHistorial.php';

class PostgradoControlador
{
    private GastoPostgrado $modeloGasto;
    private IngresoPostgrado $modeloIngreso;
    private Linea $modeloLinea;
    private Motor $modeloMotor;
    private Proyecto $modeloProyecto;
    private Rubro $modeloRubro;
    private ContratoComun $modeloContratoComun;
    private AnioPresupuestal $modeloAnio;
    private Sede $modeloSede;
    private AutogestionItem $modeloAutogestion;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;
    private AutogestionAutomaticoPermiso $modeloPermisoAutomatico;
    private AutogestionAutomaticoDefinicion $modeloDefinicionAutomatico;
    private EnvioLote $modeloEnvioLote;
    private PeticionHistorial $modeloHistorial;

    private const MODULO_AUTOGESTION = 'postgrado';

    private const CAMPOS_REQUERIDOS_EGRESO = [
        'sede_id',
        'anio_presupuestal_id',
        'categoria',
        'dependencia',
        'proyecto_id',
        'actividad',
        'rubro_id',
        'autogestion_id',
        'insumo',
        'cantidad',
        'costo_unitario',
    ];

    /**
     * Nombre legible de cada campo requerido, usado para armar mensajes de error específicos en
     * vez de un genérico "todos los campos son obligatorios" que no dice cuál falta.
     */
    private const ETIQUETAS_CAMPOS = [
        'sede_id' => 'la sede',
        'anio_presupuestal_id' => 'el año presupuestal',
        'categoria' => 'la categoría',
        'dependencia' => 'la dependencia',
        'proyecto_id' => 'el proyecto PDI',
        'actividad' => 'la actividad',
        'rubro_id' => 'el rubro',
        'autogestion_id' => 'el ítem de Autogestión (selector del sidebar)',
        'insumo' => 'el insumo',
        'cantidad' => 'la cantidad',
        'costo_unitario' => 'el costo unitario',
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
        $this->modeloGasto = new GastoPostgrado();
        $this->modeloIngreso = new IngresoPostgrado();
        $this->modeloLinea = new Linea();
        $this->modeloMotor = new Motor();
        $this->modeloProyecto = new Proyecto();
        $this->modeloRubro = new Rubro();
        $this->modeloContratoComun = new ContratoComun();
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloSede = new Sede();
        $this->modeloAutogestion = new AutogestionItem();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
        $this->modeloPermisoAutomatico = new AutogestionAutomaticoPermiso();
        $this->modeloDefinicionAutomatico = new AutogestionAutomaticoDefinicion();
        $this->modeloEnvioLote = new EnvioLote();
        $this->modeloHistorial = new PeticionHistorial();
    }

    private function esSuperAdmin(): bool
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        return $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;
    }

    private function ocultarLote(): array
    {
        if (!$this->esSuperAdmin()) {
            return ['Solo el superadministrador puede ocultar un snapshot de envío.', ''];
        }

        $loteId = (int) ($_POST['lote_id'] ?? 0);

        if ($loteId <= 0 || !$this->modeloEnvioLote->ocultar($loteId)) {
            return ['No se pudo ocultar ese snapshot.', ''];
        }

        return ['', 'Snapshot ocultado.'];
    }

    private function eliminarLote(): array
    {
        if (!$this->esSuperAdmin()) {
            return ['Solo el superadministrador puede eliminar un snapshot de envío.', ''];
        }

        $loteId = (int) ($_POST['lote_id'] ?? 0);

        if ($loteId <= 0 || !$this->modeloEnvioLote->eliminar($loteId)) {
            return ['No se pudo eliminar ese snapshot.', ''];
        }

        return ['', 'Snapshot eliminado.'];
    }

    /**
     * Techo (% de Costos/Inversiones/Excedentes) vigente para las categorías presentes en las
     * filas que se están enviando — se congela en el lote junto con las filas. A diferencia de
     * Gastos (un techo fijo en pesos), aquí puede haber varias categorías en el mismo lote.
     */
    private function construirTechoCategorias(int $autogestionId, float $totalIngresos, array $filasGasto): ?array
    {
        $item = $this->modeloAutogestion->obtenerPorId($autogestionId);

        if ($item === null) {
            return null;
        }

        $mapaPorcentaje = ['Excedentes' => 'excedentes', 'Gastos' => 'costos', 'Inversiones' => 'inversiones'];
        $categorias = array_unique(array_column($filasGasto, 'categoria'));
        $resultado = [];

        foreach ($categorias as $categoria) {
            $clave = $mapaPorcentaje[$categoria] ?? null;

            if ($clave === null || $item[$clave] === null) {
                continue;
            }

            $porcentaje = (float) $item[$clave];
            $resultado[$categoria] = [
                'porcentaje' => $porcentaje,
                'limite' => round($totalIngresos * $porcentaje / 100, 2),
            ];
        }

        return empty($resultado) ? null : $resultado;
    }

    /**
     * SA = quien pertenece a la dependencia raíz (`es_raiz_superadmin`) — mismo criterio ya usado
     * en toda la sesión para "Auditar"/visibilidad en Peticiones, NO el flag distinto
     * `usuarios.es_super_admin` que ya usa el mecanismo legado puedeAdministrarExcedentes()/
     * puedeAdministrarContribucion() de este mismo controlador (que se dejan intactos).
     */
    private function esUsuarioActualSuperAdminRaiz(): bool
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        if ($usuarioActual === null || empty($usuarioActual['dependencia_id'])) {
            return false;
        }

        $dependencia = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);

        return $dependencia !== null && !empty($dependencia['es_raiz_superadmin']);
    }

    /**
     * SA siempre puede; además, cualquier usuario a quien el SA le haya otorgado el permiso
     * delegado para este (módulo, tipo) — ver AutogestionAutomaticoPermiso.
     */
    private function puedeGestionarAutomaticos(?string $tipoAutomatico): bool
    {
        if ($tipoAutomatico === null) {
            return false;
        }

        if ($this->esUsuarioActualSuperAdminRaiz()) {
            return true;
        }

        $usuarioActualId = (int) ($_SESSION['usuario_id'] ?? 0);

        return $usuarioActualId > 0 && $this->modeloPermisoAutomatico->tienePermiso(self::MODULO_AUTOGESTION, $tipoAutomatico, $usuarioActualId);
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
            } elseif ($accion === 'ocultar_lote') {
                [$error, $exito] = $this->ocultarLote();
            } elseif ($accion === 'eliminar_lote') {
                [$error, $exito] = $this->eliminarLote();
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
        $autogestionItems = $this->modeloAutogestion->obtenerActivos('postgrado');
        $categoriasEgreso = self::CATEGORIAS_EGRESO;
        $roles = $this->modeloRol->obtenerTodos();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();
        $puedeAdministrarContribucion = $this->puedeAdministrarContribucion();
        $puedeAdministrarExcedentes = $this->puedeAdministrarExcedentes();

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

        $autogestionSeleccionadoId = 0;

        if (!empty($autogestionItems)) {
            $autogestionSeleccionadoId = isset($_GET['autogestion_id']) ? (int) $_GET['autogestion_id'] : (int) $autogestionItems[0]['id'];

            $idsValidosAutogestion = array_map('intval', array_column($autogestionItems, 'id'));
            if (!in_array($autogestionSeleccionadoId, $idsValidosAutogestion, true)) {
                $autogestionSeleccionadoId = (int) $autogestionItems[0]['id'];
            }
        }

        if ($anioSeleccionadoId > 0 && $autogestionSeleccionadoId > 0) {
            // Filtrados por dependencia/propietario de una vez aquí: $gastosEgresos e $ingresosTotal
            // alimentan tanto la tabla como la barra de resumen (total ingresos, % asignado,
            // desglose Costos+Inversión/Excedentes) — si no se filtran aquí, la barra de resumen
            // termina sumando ingresos/egresos de OTRAS dependencias que comparten el mismo ítem
            // de Autogestión, y una dependencia ve los totales de otra.
            $gastosEgresos = $this->filtrarPorPropietarioODestinatario(
                $this->modeloGasto->obtenerPorAnioYAutogestion($anioSeleccionadoId, $autogestionSeleccionadoId),
                $usuarioActual,
                $dependenciasSugeridas
            );
            $ingresosTotal = $this->filtrarPorPropietarioODestinatario(
                $this->modeloIngreso->obtenerPorAnioYAutogestion($anioSeleccionadoId, $autogestionSeleccionadoId),
                $usuarioActual,
                $dependenciasSugeridas
            );

            $gastos = $tab === 'ingresos' ? $ingresosTotal : $gastosEgresos;
        } else {
            $ingresosTotal = [];
            $gastos = [];
            $gastosEgresos = [];
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
        $presupuestoAnio = array_sum(array_map(static fn (array $i): float => (float) $i['valor_total'], $ingresosTotal));
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
            } elseif ($categoria === 'Inversiones' || str_starts_with($categoria, 'Inversión') || str_starts_with($categoria, 'Contribución')) {
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

        $origenLote = $tab === 'ingresos' ? 'ingreso_postgrado' : 'gasto_postgrado';
        $lotesCrudos = $this->modeloEnvioLote->obtenerActivosPorFilas($origenLote, array_column($gastos, 'id'));
        $lotesEnviados = (new VistaLoteEnvio())->construirLotes($origenLote, $lotesCrudos);
        $esSuperAdmin = $this->esSuperAdmin();

        // Por tipo presente en la tabla (normalmente solo 'excedentes'), si el usuario actual
        // puede editar/eliminar esa fila automática (SA o permiso delegado) — la vista lo usa para
        // decidir si muestra Editar/Eliminar en vez de solo la etiqueta "Automático".
        $permisosAutomaticosPorTipo = [];
        foreach (array_unique(array_filter(array_column($gastosEgresos, 'tipo_automatico'))) as $tipoAutomatico) {
            $permisosAutomaticosPorTipo[$tipoAutomatico] = $this->puedeGestionarAutomaticos($tipoAutomatico);
        }

        require __DIR__ . '/../vista/postgrado/index.php';
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
            'Items' => array_map(static fn (array $i): string => $i['nombre'], $catalogos['autogestionItems']),
            'Proyectos' => array_map(static fn (array $p): string => self::textoProyecto($p), $catalogos['proyectos']),
            'Contratos' => array_map(static fn (array $c): string => $c['codigo'], $catalogos['contratosComunes']),
            'Categorias' => self::CATEGORIAS_EGRESO,
            'Rubros' => array_map(static fn (array $r): string => $r['codigo'] . ' - ' . $r['descripcion'], $catalogos['rubros']),
        ];

        $hojaIngresos = [
            'encabezados' => ['Año presupuestal *', 'Dependencia *', 'Ítem de autogestión *', 'Concepto *', 'Cantidad *', 'Valor unitario *', 'Valor total'],
            'columnasConLista' => [0 => 'Años', 1 => 'Dependencias', 2 => 'Items'],
            'filaEjemplo' => [
                $listasComunes['Años'][0] ?? '',
                $listasComunes['Dependencias'][0] ?? '',
                $listasComunes['Items'][0] ?? '',
                'Ejemplo: matrícula programa de posgrado',
                '1',
                '1000000',
                '',
            ],
            'columnaCantidad' => 4,
            'columnaValorUnitario' => 5,
            'columnaValorTotal' => 6,
        ];

        $hojaGastos = [
            'encabezados' => [
                'Año presupuestal *', 'Sede *', 'Dependencia *', 'Ítem de autogestión *', 'Proyecto PDI *',
                'Contratos comunes', 'Categoría *', 'Actividad *', 'Rubro *', 'Insumo *', 'Cantidad *',
                'Costo unitario *', 'Valor total', 'Meses de ejecución * (ej: 1,3,5)',
            ],
            'columnasConLista' => [0 => 'Años', 1 => 'Sedes', 2 => 'Dependencias', 3 => 'Items', 4 => 'Proyectos', 5 => 'Contratos', 6 => 'Categorias', 8 => 'Rubros'],
            'filaEjemplo' => [
                $listasComunes['Años'][0] ?? '',
                $listasComunes['Sedes'][0] ?? '',
                $listasComunes['Dependencias'][0] ?? '',
                $listasComunes['Items'][0] ?? '',
                $listasComunes['Proyectos'][0] ?? '',
                '',
                $listasComunes['Categorias'][0] ?? '',
                'Ejemplo: pago de docentes',
                $listasComunes['Rubros'][0] ?? '',
                'Ejemplo: honorarios docentes',
                '1',
                '1000000',
                '',
                '1,2,3',
            ],
            'columnaCantidad' => 10,
            'columnaValorUnitario' => 11,
            'columnaValorTotal' => 12,
            'columnaCategoria' => 6,
        ];

        // El % de Costos/Inversiones/Excedentes/Contribución a posgrado se configura por ítem de
        // Autogestión (ver autogestion_items.costos/inversiones/excedentes/contribucion_postgrado),
        // no por módulo — y esta plantilla mezcla filas de distintos ítems en una sola hoja. Por eso
        // $validacionPorcentajes solo aporta las etiquetas/orden de fila; el % real se lleva en
        // $porcentajesPorItem, que GeneradorXlsx usa para construir la hoja oculta "Porcentaje"
        // como tabla de búsqueda por ítem y agregar el desplegable de "ítem a validar" (ver
        // descargarPlantillaAutogestion()).
        $validacionPorcentajes = array_map(static fn (string $etiqueta): array => ['etiqueta' => $etiqueta, 'porcentaje' => null], self::CATEGORIAS_VALIDACION_PLANTILLA);
        $porcentajesPorItem = array_map(static fn (array $item): array => [
            'nombre' => $item['nombre'],
            'excedentes' => $item['excedentes'] !== null ? (float) $item['excedentes'] : null,
            'costos' => $item['costos'] !== null ? (float) $item['costos'] : null,
            'inversiones' => $item['inversiones'] !== null ? (float) $item['inversiones'] : null,
        ], $catalogos['autogestionItems']);

        $metadatos = [
            'plantilla' => 'autogestion-postgrado',
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'usuario_nombre' => $catalogos['usuarioActual']['nombre'] ?? '',
        ];

        GeneradorXlsx::descargarPlantillaAutogestion('plantilla_postgrado.xlsx', $hojaIngresos, $hojaGastos, $validacionPorcentajes, $listasComunes, $metadatos, $porcentajesPorItem);
        exit;
    }

    /**
     * Exporta a .xlsx los ingresos y egresos visibles para el usuario actual en el año
     * presupuestal indicado, en dos hojas ("Ingresos" y "Gastos"), con el mismo alcance de
     * dependencias/propietario que se ve en pantalla (ver index()).
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
        $autogestionItems = $this->modeloAutogestion->obtenerActivos('postgrado');

        $mapaItems = [];
        foreach ($autogestionItems as $item) {
            $mapaItems[(int) $item['id']] = $item['nombre'];
        }

        $ingresos = [];
        $gastos = [];

        if ($anioSeleccionadoId > 0) {
            foreach ($autogestionItems as $item) {
                $itemId = (int) $item['id'];
                $ingresos = array_merge($ingresos, $this->filtrarPorPropietarioODestinatario(
                    $this->modeloIngreso->obtenerPorAnioYAutogestion($anioSeleccionadoId, $itemId),
                    $usuarioActual,
                    $dependenciasPermitidas
                ));
                $gastos = array_merge($gastos, $this->filtrarPorPropietarioODestinatario(
                    $this->modeloGasto->obtenerPorAnioYAutogestion($anioSeleccionadoId, $itemId),
                    $usuarioActual,
                    $dependenciasPermitidas
                ));
            }
        }

        $filaIngreso = static function (array $ingreso) use ($mapaItems): array {
            return [
                $mapaItems[(int) $ingreso['autogestion_id']] ?? '',
                $ingreso['dependencia'],
                (string) ($ingreso['concepto_adicional'] ?? ''),
                number_format((float) $ingreso['valor_adicional'], 2, ',', '.'),
                number_format((float) $ingreso['valor_total'], 2, ',', '.'),
                $ingreso['estado'],
            ];
        };

        $filaGasto = static function (array $gasto) use ($mapaItems): array {
            return [
                $mapaItems[(int) $gasto['autogestion_id']] ?? '',
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
            ['nombre' => 'Ingresos', 'encabezados' => ['Ítem de autogestión', 'Dependencia', 'Concepto adicional', 'Valor adicional', 'Valor total', 'Estado'], 'filas' => array_map($filaIngreso, $ingresos)],
            ['nombre' => 'Gastos', 'encabezados' => ['Ítem de autogestión', 'Dependencia', 'Categoría', 'Insumo', 'Cantidad', 'Costo unitario', 'Valor total', 'Estado'], 'filas' => array_map($filaGasto, $gastos)],
        ];

        $vistaLotes = new VistaLoteEnvio();
        $lotesGasto = $vistaLotes->construirLotes('gasto_postgrado', $this->modeloEnvioLote->obtenerActivosPorFilas('gasto_postgrado', array_column($gastos, 'id')));
        $lotesIngreso = $vistaLotes->construirLotes('ingreso_postgrado', $this->modeloEnvioLote->obtenerActivosPorFilas('ingreso_postgrado', array_column($ingresos, 'id')));
        $nombresReservados = array_column($hojas, 'nombre');
        $hojas = array_merge($hojas, $vistaLotes->hojasExcel($lotesGasto, $nombresReservados), $vistaLotes->hojasExcel($lotesIngreso, $nombresReservados));

        $anioTexto = (string) $anioSeleccionadoId;
        foreach ($aniosActivos as $anioFila) {
            if ((int) $anioFila['id'] === $anioSeleccionadoId) {
                $anioTexto = (string) $anioFila['anio'];
                break;
            }
        }

        GeneradorXlsx::descargarHojas('postgrado_' . $anioTexto . '.xlsx', $hojas);
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
        $autogestionItems = $this->modeloAutogestion->obtenerActivos('postgrado');

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        [, $dependenciasSugeridas] = $this->obtenerDependenciasVisiblesUsuarioActual();

        return [
            'proyectos' => $proyectos,
            'rubros' => $rubros,
            'contratosComunes' => $contratosComunes,
            'aniosActivos' => $aniosActivos,
            'sedes' => $sedes,
            'autogestionItems' => $autogestionItems,
            'usuarioActual' => $usuarioActual,
            'dependenciasSugeridas' => $dependenciasSugeridas,
        ];
    }

    /**
     * Importa ingresos y egresos en borrador desde un archivo .xlsx (plantilla generada por
     * exportarPlantilla()): la hoja "Ingresos" se lee y se agrupa PRIMERO (por año+ítem+dependencia,
     * en una sola cabecera con varios conceptos), porque el total de cada grupo es el que se usa
     * después para validar el balance de la hoja "Gastos" (ver más abajo). Todo o nada: si
     * cualquier fila de cualquiera de las dos hojas falla una validación, no se importa nada.
     *
     * El presupuesto disponible y el % por categoría NO se validan fila por fila: se acumula
     * primero el total de TODAS las filas de Gastos válidas por cada año+ítem+dependencia y recién
     * al final se compara ese total contra lo disponible — así, si el archivo no cabe, el error dice
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

        if (($metadatos['SPPI_Origen'] ?? '') !== GeneradorXlsx::FIRMA_PLATAFORMA || ($metadatos['SPPI_Plantilla'] ?? '') !== 'autogestion-postgrado') {
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

        $mapaItems = [];
        foreach ($catalogos['autogestionItems'] as $item) {
            $mapaItems[$item['nombre']] = (int) $item['id'];
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

        // --- 1) Ingresos primero: se agrupan por año + ítem + dependencia en una sola cabecera con
        // varios conceptos. Una fila con más de 4 de sus campos obligatorios en blanco se ignora en
        // silencio (fila sin usar; puede traer, por ejemplo, un "0" residual en la columna
        // calculada "Valor total" tras abrir la plantilla en Excel) en vez de reportarse como error. ---
        $gruposIngreso = [];

        foreach ($filasIngresos as $indice => $fila) {
            $numeroFilaExcel = $indice + $filaEncabezadoPlantilla + 1;

            $anioTexto = trim($fila[0] ?? '');
            $dependenciaTexto = trim($fila[1] ?? '');
            $itemTexto = trim($fila[2] ?? '');
            $conceptoTexto = trim($fila[3] ?? '');
            $cantidadTexto = trim($fila[4] ?? '');
            $valorTexto = trim($fila[5] ?? '');

            $vacios = count(array_filter(
                [$anioTexto, $dependenciaTexto, $itemTexto, $conceptoTexto, $cantidadTexto, $valorTexto],
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

            if (!isset($mapaItems[$itemTexto])) {
                $errores[] = "Ingresos, fila $numeroFilaExcel: el ítem de autogestión \"$itemTexto\" no es válido. Usa el desplegable de la columna.";
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
            $itemId = $mapaItems[$itemTexto];
            $clave = $anioId . ':' . $itemId . ':' . $dependenciaTexto;

            if (!isset($gruposIngreso[$clave])) {
                $gruposIngreso[$clave] = [
                    'anio_presupuestal_id' => $anioId,
                    'autogestion_id' => $itemId,
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
            $itemTexto = trim($fila[3] ?? '');
            $proyectoTexto = trim($fila[4] ?? '');
            $contratoTexto = trim($fila[5] ?? '');
            $categoriaTexto = trim($fila[6] ?? '');
            $actividad = trim($fila[7] ?? '');
            $rubroTexto = trim($fila[8] ?? '');
            $insumo = trim($fila[9] ?? '');
            $cantidadTexto = trim($fila[10] ?? '');
            $costoTexto = trim($fila[11] ?? '');
            $mesesTexto = trim($fila[13] ?? '');

            $vacios = count(array_filter(
                [$anioTexto, $sedeTexto, $dependenciaTexto, $itemTexto, $proyectoTexto, $categoriaTexto, $actividad, $rubroTexto, $insumo, $cantidadTexto, $costoTexto, $mesesTexto],
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

            if (!isset($mapaItems[$itemTexto])) {
                $errores[] = "Gastos, fila $numeroFilaExcel: el ítem de autogestión \"$itemTexto\" no es válido. Usa el desplegable de la columna.";
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
            $itemId = $mapaItems[$itemTexto];

            $filasGastoCandidatas[] = [
                'claveGrupo' => $anioId . ':' . $itemId . ':' . $dependenciaTexto,
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
                    'autogestion_id' => $itemId,
                    'insumo' => $insumo,
                    'cantidad' => (int) $cantidadTexto,
                    'costo_unitario' => (float) $costoTexto,
                    'meses' => implode(',', $meses),
                    'usuario_id' => (int) ($_SESSION['usuario_id'] ?? 0),
                    // Marca de control: Colombia clasifica funcionamiento con capítulo "2" e
                    // inversión con "4", pero el catálogo de rubros todavía no tiene códigos "4.xx"
                    // — se guarda "4" aquí (sin tocar rubro_id ni el catálogo) cuando la fila se
                    // clasificó como Inversiones con un rubro de capítulo "2", para que un reporte
                    // futuro pueda distinguirla. No aplica a Gastos/Excedentes (esos sí son capítulo
                    // "2" real).
                    'capitulo_control' => ($categoriaTexto === 'Inversiones' && str_starts_with($rubroTexto, '2.')) ? '4' : null,
                ],
            ];
        }

        // --- 3) Presupuesto y % por categoría: sobre el TOTAL de lo que se intenta importar por
        // cada año+ítem+dependencia, no fila por fila. ---
        $totalesPorGrupo = [];
        foreach ($filasGastoCandidatas as $candidata) {
            $clave = $candidata['claveGrupo'];

            if (!isset($totalesPorGrupo[$clave])) {
                $totalesPorGrupo[$clave] = [
                    'anio_presupuestal_id' => $candidata['datos']['anio_presupuestal_id'],
                    'autogestion_id' => $candidata['datos']['autogestion_id'],
                    'dependencia' => $candidata['datos']['dependencia'],
                    'total' => 0.0,
                    'categorias' => [],
                ];
            }

            $totalesPorGrupo[$clave]['total'] += $candidata['nuevoValor'];
            $totalesPorGrupo[$clave]['categorias'][$candidata['categoria']]
                = ($totalesPorGrupo[$clave]['categorias'][$candidata['categoria']] ?? 0.0) + $candidata['nuevoValor'];
        }

        $mapaCategoriaPorcentaje = ['Excedentes' => 'excedentes', 'Gastos' => 'costos', 'Inversiones' => 'inversiones'];

        foreach ($totalesPorGrupo as $clave => $grupo) {
            $anioId = $grupo['anio_presupuestal_id'];
            $itemId = $grupo['autogestion_id'];
            $dependenciaTexto = $grupo['dependencia'];
            $itemTexto = array_search($itemId, $mapaItems, true);
            $itemTexto = $itemTexto !== false ? $itemTexto : '';
            // El % es propio de cada ítem (autogestion_items.costos/inversiones/excedentes), no del
            // módulo — se busca por ítem, no una sola vez para todo el archivo.
            $porcentajesItem = $this->modeloAutogestion->obtenerPorId($itemId) ?? [];

            // Se busca entre $dependenciasPermitidas (la propia dependencia de quien importa + TODOS
            // sus descendientes) y no solo [$dependenciaTexto]: el Departamento de Postgrados nunca
            // registra ingresos propios (los genera cada programa), así que su disponibilidad se
            // respalda con el total ya registrado de sus programas — mismo criterio que ya usa
            // guardarEgreso()/actualizarEgreso() para el formulario manual. Si el Departamento algún
            // día tuviera ingresos propios reales, esos se manejarían por el módulo de Gastos, no por
            // Autogestión, así que no hay caso real donde esto mezcle ingresos de dependencias sin
            // relación entre sí.
            $ingresosExistentes = $this->modeloIngreso->obtenerTotalPorAnioYAutogestionYDependencias($anioId, $itemId, $dependenciasPermitidas);

            // Igual que $ingresosExistentes arriba: los ingresos NUEVOS de este mismo archivo se
            // agrupan por (año, ítem, dependencia) exacta, así que si el mismo archivo trae a la vez
            // los ingresos nuevos de los programas y los gastos nuevos del Departamento, hay que
            // sumar todas las claves de ese mismo año+ítem cuya dependencia caiga en el mismo pool
            // ($dependenciasPermitidas) — no solo la clave exacta de esta fila de Gastos.
            $ingresosNuevos = 0.0;
            foreach ($gruposIngreso as $claveIngreso => $grupoIngreso) {
                if ((int) $grupoIngreso['anio_presupuestal_id'] === (int) $anioId
                    && (int) $grupoIngreso['autogestion_id'] === (int) $itemId
                    && in_array($grupoIngreso['dependencia'], $dependenciasPermitidas, true)
                ) {
                    $ingresosNuevos += $totalIngresosPorGrupo[$claveIngreso] ?? 0.0;
                }
            }

            $ingresosDisponibles = $ingresosExistentes + $ingresosNuevos;
            $totalExistente = $this->modeloGasto->obtenerTotalPorAnioYAutogestionYDependencias($anioId, $itemId, $dependenciasPermitidas);

            if ($totalExistente + $grupo['total'] > $ingresosDisponibles) {
                $disponible = max(0, $ingresosDisponibles - $totalExistente);
                $errores[] = "Gastos, \"$itemTexto\" en \"$dependenciaTexto\": el valor total de gastos que intentas importar ("
                    . number_format($grupo['total'], 2, ',', '.') . ') excede el disponible (' . number_format($disponible, 2, ',', '.') . '). '
                    . 'Ingresos de "' . $itemTexto . '" en "' . $dependenciaTexto . '" para este año: ' . number_format($ingresosExistentes, 2, ',', '.') . ' ya registrados + '
                    . number_format($ingresosNuevos, 2, ',', '.') . ' nuevos en la hoja "Ingresos" de este archivo.'
                    . ($ingresosNuevos <= 0 ? ' No se detectó ninguna fila de Ingresos para este ítem y dependencia en este archivo: revisa que el ítem de autogestión, la dependencia y el año coincidan exactamente (elegidos del desplegable) en ambas hojas.' : '');
                continue;
            }

            foreach ($grupo['categorias'] as $categoria => $totalCategoria) {
                $clavePorcentaje = $mapaCategoriaPorcentaje[$categoria] ?? null;

                if ($clavePorcentaje === null || ($porcentajesItem[$clavePorcentaje] ?? null) === null) {
                    continue;
                }

                $valorEsperadoCategoria = round($ingresosDisponibles * (float) $porcentajesItem[$clavePorcentaje] / 100, 2);

                // Excedentes se calcula SOLO a partir del ingreso de esta importación (% × ingresos
                // disponibles de este ítem+dependencia): no se compara contra lo que ya exista en la
                // base de datos para "Excedentes" ahí, porque puede tener registros de otras
                // personas o de pruebas anteriores que no son parte de este archivo — si no hay
                // ingreso, el excedente esperado es 0, sin importar qué otro valor exista ya. Para
                // Gastos/Inversiones sí se sigue acumulando contra lo existente, porque esas
                // categorías sí son de cupo compartido con el resto del año.
                if ($categoria === 'Excedentes') {
                    // Las filas de Excedentes reemplazan la línea automática del ingreso de esta
                    // misma clave (año:ítem:dependencia) — solo funciona si ese ingreso también
                    // viene en este mismo archivo (ver importar(), más abajo); si no, se rechaza
                    // en vez de dejarlas caer silenciosamente sin insertarse en ningún lado.
                    if (!isset($gruposIngreso[$clave])) {
                        $errores[] = "Gastos, \"$itemTexto\" en \"$dependenciaTexto\", categoría Excedentes: para reemplazar el excedente calculado, este mismo archivo debe incluir también la fila de Ingresos de \"$itemTexto\" en \"$dependenciaTexto\" para este año.";
                        continue;
                    }

                    if (abs(round($totalCategoria, 2) - $valorEsperadoCategoria) > 0.01) {
                        $porcentajeTexto = rtrim(rtrim(number_format((float) $porcentajesItem[$clavePorcentaje], 2), '0'), '.');
                        $errores[] = "Gastos, \"$itemTexto\" en \"$dependenciaTexto\", categoría Excedentes: el valor que intentas importar (" . number_format($totalCategoria, 2, ',', '.')
                            . ") debe corresponder exactamente al {$porcentajeTexto}% de los ingresos de este ítem y dependencia (" . number_format($valorEsperadoCategoria, 2, ',', '.') . ').';
                    }
                    continue;
                }

                $totalExistenteCategoria = $this->modeloGasto->obtenerTotalPorAnioAutogestionYCategoriaYDependencias($anioId, $itemId, $categoria, $dependenciasPermitidas);
                $totalRealCategoria = round($totalExistenteCategoria + $totalCategoria, 2);

                if ($totalRealCategoria > $valorEsperadoCategoria) {
                    $disponibleCategoria = max(0, $valorEsperadoCategoria - $totalExistenteCategoria);
                    $errores[] = "Gastos, \"$itemTexto\" en \"$dependenciaTexto\", categoría $categoria: el valor total que intentas importar ("
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

        // Las filas "Excedentes" NO se insertan como gasto manual suelto: reemplazan la línea
        // automática calculada del ingreso de esa misma clave (año:ítem:dependencia) — su suma ya
        // se validó arriba contra el % esperado.
        $excedentesPersonalizadosPorClave = [];

        try {
            $gruposAfectados = [];

            foreach ($gruposIngreso as $clave => $grupo) {
                $conceptos = $grupo['conceptos'];
                unset($grupo['conceptos']);
                $grupo['valor_total'] = $totalIngresosPorGrupo[$clave] ?? 0.0;
                $grupo['id'] = $this->modeloIngreso->crear($grupo, $conceptos);
                $gruposAfectados[] = $grupo;
            }

            foreach ($filasGastoCandidatas as $candidata) {
                if ($candidata['categoria'] === 'Excedentes') {
                    $excedentesPersonalizadosPorClave[$candidata['claveGrupo']][] = [
                        'sede_id' => $candidata['datos']['sede_id'],
                        'proyecto_id' => $candidata['datos']['proyecto_id'],
                        'rubro_id' => $candidata['datos']['rubro_id'],
                        'actividad' => $candidata['datos']['actividad'],
                        'insumo' => $candidata['datos']['insumo'],
                        'meses' => $candidata['datos']['meses'],
                        'valor_total' => $candidata['nuevoValor'],
                    ];
                    continue;
                }

                $this->modeloGasto->crear($candidata['datos']);
            }

            $db->commit();
        } catch (PDOException $excepcion) {
            $db->rollBack();
            return ['No se pudo importar el archivo: ' . $excepcion->getMessage(), '', []];
        }

        foreach ($gruposAfectados as $grupo) {
            $clave = $grupo['anio_presupuestal_id'] . ':' . $grupo['autogestion_id'] . ':' . $grupo['dependencia'];
            $this->regenerarAutomaticosDeIngreso($grupo, ['excedentes' => $excedentesPersonalizadosPorClave[$clave] ?? []]);
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
        $ingresosDisponibles = $this->modeloIngreso->obtenerTotalPorAnioYAutogestionYDependencias($datos['anio_presupuestal_id'], $datos['autogestion_id'], $dependenciasPermitidas);
        $egresosActuales = $this->modeloGasto->obtenerTotalPorAnioYAutogestionYDependencias($datos['anio_presupuestal_id'], $datos['autogestion_id'], $dependenciasPermitidas);
        $nuevoValor = $datos['cantidad'] * $datos['costo_unitario'];

        if ($egresosActuales + $nuevoValor > $ingresosDisponibles) {
            $disponible = max(0, $ingresosDisponibles - $egresosActuales);

            return ['Este egreso supera los ingresos disponibles de este ítem de autogestión. Disponible: ' . number_format($disponible, 2, ',', '.') . '.', ''];
        }

        $errorCategoria = $this->validarLimiteCategoria($datos['anio_presupuestal_id'], (int) $datos['autogestion_id'], $datos['categoria'], $nuevoValor, $ingresosDisponibles, 0.0, $dependenciasPermitidas);

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

    private function puedeAdministrarContribucion(): bool
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        if ($usuarioActual === null) {
            return false;
        }

        if ((int) ($usuarioActual['es_super_admin'] ?? 0) === 1) {
            return true;
        }

        $rolId = !empty($usuarioActual['rol_id']) ? (int) $usuarioActual['rol_id'] : null;

        if ($rolId === null) {
            return false;
        }

        $rol = $this->modeloRol->obtenerPorId($rolId);

        if ($rol === null || !in_array($rol['nombre'], ['Avalador', 'Gestor'], true)) {
            return false;
        }

        $dependenciaId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaId === null) {
            return false;
        }

        $dependencia = $this->modeloDependencia->obtenerPorId($dependenciaId);

        return $dependencia !== null
            && $dependencia['nombre'] === 'DEPARTAMENTO DE POSTGRADOS'
            && ($dependencia['tipo'] ?? null) === 'Departamento';
    }

    private function puedeAdministrarExcedentes(): bool
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        return $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;
    }

    private function actualizarEgreso(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloGasto->obtenerPorId($id) : null;

        if ($existente === null) {
            return ['El egreso que intentas editar no existe.', ''];
        }

        // Sin restricción de estado (igual que Extensión/Unisalud/SinExcedentes): una fila
        // automática ya enviada sigue siendo editable por quien tiene el permiso, para poder
        // corregirla desde "Ver en Peticiones" → "Editar" incluso después de que se envió junto
        // con el resto del lote de su dependencia.
        $esConversionContribucion = $existente['tipo_automatico'] === 'contrib_postgrado'
            && $this->puedeAdministrarContribucion();

        $esConversionExcedentes = $existente['tipo_automatico'] === 'excedentes'
            && $this->puedeAdministrarExcedentes();

        // Mecanismo nuevo (permisos delegados por SA, ver AutogestionAutomaticoPermiso), además del
        // mecanismo legado de arriba (solo SA/Departamento de Postgrados) — cualquiera de los dos
        // habilita la edición de una fila automática.
        $esConversionPorPermiso = $existente['tipo_automatico'] !== null
            && !$esConversionContribucion
            && !$esConversionExcedentes
            && $this->puedeGestionarAutomaticos($existente['tipo_automatico']);

        if ($existente['tipo_automatico'] !== null && !$esConversionContribucion && !$esConversionExcedentes && !$esConversionPorPermiso) {
            return ['El egreso que intentas editar no existe.', ''];
        }

        [$datos, $error] = $this->validarDatosEgreso();

        if ($error !== '') {
            return [$error, ''];
        }

        [, $dependenciasPermitidas] = $this->obtenerDependenciasVisiblesUsuarioActual();
        $ingresosDisponibles = $this->modeloIngreso->obtenerTotalPorAnioYAutogestionYDependencias($datos['anio_presupuestal_id'], $datos['autogestion_id'], $dependenciasPermitidas);
        $egresosActuales = $this->modeloGasto->obtenerTotalPorAnioYAutogestionYDependencias($datos['anio_presupuestal_id'], $datos['autogestion_id'], $dependenciasPermitidas) - (float) $existente['valor_total'];
        $nuevoValor = $datos['cantidad'] * $datos['costo_unitario'];

        if ($egresosActuales + $nuevoValor > $ingresosDisponibles) {
            $disponible = max(0, $ingresosDisponibles - $egresosActuales);

            return ['Este egreso supera los ingresos disponibles de este ítem de autogestión. Disponible: ' . number_format($disponible, 2, ',', '.') . '.', ''];
        }

        $valorExcluidoCategoria = $existente['categoria'] === $datos['categoria'] ? (float) $existente['valor_total'] : 0.0;
        $errorCategoria = $this->validarLimiteCategoria($datos['anio_presupuestal_id'], (int) $datos['autogestion_id'], $datos['categoria'], $nuevoValor, $ingresosDisponibles, $valorExcluidoCategoria, $dependenciasPermitidas);

        if ($errorCategoria !== '') {
            return [$errorCategoria, ''];
        }

        try {
            if ($esConversionContribucion) {
                $this->modeloGasto->convertirContribucionAManual($id, $datos);
            } elseif ($esConversionExcedentes) {
                $this->modeloGasto->convertirExcedentesAManual($id, $datos);
            } elseif ($esConversionPorPermiso) {
                $this->modeloGasto->convertirAutomaticoAManual($id, $datos);
            } else {
                $this->modeloGasto->actualizar($id, $datos);
            }
        } catch (PDOException $excepcion) {
            return ['No se pudo actualizar el egreso: ' . $excepcion->getMessage(), ''];
        }

        if ($existente['estado'] === 'enviado') {
            $this->modeloHistorial->registrar('gasto_postgrado', $id, 'editado_tras_enviar', 'Editado después de enviarse.');
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen('gasto_postgrado', $id, $nuevoValor, $datos['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=postgrado&tab=egresos&anio_id=' . $datos['anio_presupuestal_id'] . '&autogestion_id=' . $datos['autogestion_id'];
        header('Location: ' . $destino);
        exit;
    }

    private function validarLimiteCategoria(int $anioPresupuestalId, int $autogestionId, string $categoria, float $nuevoValor, float $totalIngresos, float $valorExcluido = 0.0, array $dependenciasPermitidas = []): string
    {
        $mapaCategoriaPorcentaje = ['Excedentes' => 'excedentes', 'Gastos' => 'costos', 'Inversiones' => 'inversiones'];
        $clavePorcentaje = $mapaCategoriaPorcentaje[$categoria] ?? null;

        if ($clavePorcentaje === null) {
            return '';
        }

        $item = $this->modeloAutogestion->obtenerPorId($autogestionId);

        if ($item === null || $item[$clavePorcentaje] === null) {
            return '';
        }

        $limiteCategoria = round($totalIngresos * (float) $item[$clavePorcentaje] / 100, 2);
        $totalCategoriaActual = $this->modeloGasto->obtenerTotalPorAnioAutogestionYCategoriaYDependencias($anioPresupuestalId, $autogestionId, $categoria, $dependenciasPermitidas) - $valorExcluido;

        if ($totalCategoriaActual + $nuevoValor > $limiteCategoria) {
            $disponibleCategoria = max(0, $limiteCategoria - $totalCategoriaActual);

            return "Este egreso supera el porcentaje disponible para {$categoria}. Disponible: " . number_format($disponibleCategoria, 2, ',', '.') . '.';
        }

        return '';
    }

    private function enviarTodo(): array
    {
        $anioId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $autogestionId = (int) ($_POST['autogestion_id'] ?? 0);
        $dependenciaDestinoNombre = trim($_POST['dependencia_destino'] ?? '');
        // Solo para mostrar en los mensajes de abajo — $dependenciaDestinoNombre sigue siendo el
        // nombre real (necesario para obtenerPorNombre()/enviarTodosBorrador()).
        $dependenciaDestinoVisible = Dependencia::nombreVisible($dependenciaDestinoNombre);
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if ($anioId <= 0 || $autogestionId <= 0 || $dependenciaDestinoNombre === '' || $rolDestinatarioId <= 0) {
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
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoVisible . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        [, $dependenciasPermitidasEnvio] = $this->obtenerDependenciasVisiblesUsuarioActual();
        $totalIngresos = $this->modeloIngreso->obtenerTotalPorAnioYAutogestionYDependencias($anioId, $autogestionId, $dependenciasPermitidasEnvio);
        $totalEgresos = $this->modeloGasto->obtenerTotalPorAnioYAutogestionYDependencias($anioId, $autogestionId, $dependenciasPermitidasEnvio);

        if ($totalIngresos <= 0 || abs($totalIngresos - $totalEgresos) >= 0.01) {
            return ['Solo puedes enviar cuando el total de egresos sea igual al total de ingresos de este ítem.', ''];
        }

        // Una vez resuelto (único con ese rol, o desambiguado arriba), se guarda quién es
        // exactamente el destinatario — si no, cualquiera con ese rol en la dependencia vería la
        // petición en Pendientes, no solo la persona elegida.
        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);

        $filasIngreso = $this->modeloIngreso->enviarTodosBorrador($anioId, $autogestionId, $dependenciaDestinoNombre, $rolDestinatarioId, $dependenciasPermitidasEnvio, $usuarioDestinatarioResuelto);
        $filasGasto = $this->modeloGasto->enviarTodosBorrador($anioId, $autogestionId, $dependenciaDestinoNombre, $rolDestinatarioId, $dependenciasPermitidasEnvio, $usuarioDestinatarioResuelto);
        $enviadosIngresos = count($filasIngreso);
        $enviadosEgresos = count($filasGasto);

        if ($enviadosIngresos === 0 && $enviadosEgresos === 0) {
            return ['No hay ingresos ni egresos en borrador para enviar.', ''];
        }

        $ambitoLote = 'autogestion:' . $autogestionId;
        $techoCategorias = $this->construirTechoCategorias($autogestionId, $totalIngresos, $filasGasto);
        $historial = $this->modeloHistorial;

        if (!empty($filasGasto)) {
            $loteGasto = $this->modeloEnvioLote->crear('gasto_postgrado', [
                'anio_presupuestal_id' => $anioId,
                'dependencia' => $dependenciaDestinoNombre,
                'ambito' => $ambitoLote,
                'enviado_por' => $remitenteId,
                'rol_destinatario_id' => $rolDestinatarioId,
                'usuario_destinatario_id' => $usuarioDestinatarioResuelto,
                'total_lote' => array_sum(array_map(static fn (array $f): float => (float) $f['valor_total'], $filasGasto)),
                'techo_categorias' => $techoCategorias,
            ], $filasGasto);
            $detalleGasto = 'Enviado como lote v' . $loteGasto['version'];
            $loteHistorialGasto = $historial->registrarLote('gasto_postgrado', 'enviado', $detalleGasto, $enviadosEgresos);
            foreach ($filasGasto as $fila) {
                $historial->registrar('gasto_postgrado', (int) $fila['id'], 'enviado', $detalleGasto, $loteHistorialGasto);
            }
        }

        if (!empty($filasIngreso)) {
            $loteIngreso = $this->modeloEnvioLote->crear('ingreso_postgrado', [
                'anio_presupuestal_id' => $anioId,
                'dependencia' => $dependenciaDestinoNombre,
                'ambito' => $ambitoLote,
                'enviado_por' => $remitenteId,
                'rol_destinatario_id' => $rolDestinatarioId,
                'usuario_destinatario_id' => $usuarioDestinatarioResuelto,
                'total_lote' => array_sum(array_map(static fn (array $f): float => (float) $f['valor_total'], $filasIngreso)),
            ], $filasIngreso);
            $detalleIngreso = 'Enviado como lote v' . $loteIngreso['version'];
            $loteHistorialIngreso = $historial->registrarLote('ingreso_postgrado', 'enviado', $detalleIngreso, $enviadosIngresos);
            foreach ($filasIngreso as $fila) {
                $historial->registrar('ingreso_postgrado', (int) $fila['id'], 'enviado', $detalleIngreso, $loteHistorialIngreso);
            }
        }

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear(
                $remitenteId,
                (int) $destinatario['id'],
                'Postgrado enviado',
                'Se enviaron ' . $enviadosIngresos . ' ingreso(s) y ' . $enviadosEgresos . ' egreso(s) de Postgrado para tu revisión.'
            );
        }

        if (empty($destinatarios)) {
            return ['', 'Se enviaron ' . $enviadosIngresos . ' ingreso(s) y ' . $enviadosEgresos . ' egreso(s), pero no se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoVisible . '" para notificar.'];
        }

        return ['', 'Se enviaron ' . $enviadosIngresos . ' ingreso(s) y ' . $enviadosEgresos . ' egreso(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaDestinoVisible . '").'];
    }

    private function eliminarEgreso(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloGasto->obtenerPorId($id) : null;

        if ($existente === null || ($existente['tipo_automatico'] !== null && !$this->puedeGestionarAutomaticos($existente['tipo_automatico']))) {
            return ['El egreso que intentas eliminar no existe.', ''];
        }

        if ($existente['tipo_automatico'] !== null) {
            $this->modeloGasto->eliminarForzado($id);
        } else {
            $this->modeloGasto->eliminar($id);
        }

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
                $etiqueta = self::ETIQUETAS_CAMPOS[$campo] ?? $campo;

                return [[], 'Falta ' . $etiqueta . '. Ese campo es obligatorio para registrar el egreso.'];
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
        $datos['autogestion_id'] = (int) $datos['autogestion_id'];
        $datos['cantidad'] = (int) $datos['cantidad'];
        $datos['costo_unitario'] = (float) $datos['costo_unitario'];
        $datos['usuario_id'] = (int) ($_SESSION['usuario_id'] ?? 0);

        return [$datos, ''];
    }

    private function validarDatosIngreso(): array
    {
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $autogestionId = (int) ($_POST['autogestion_id'] ?? 0);
        $dependencia = trim($_POST['dependencia'] ?? '');
        $conceptoAdicional = trim($_POST['concepto_adicional'] ?? '');
        $valorAdicional = is_numeric($_POST['valor_adicional'] ?? '') ? (float) $_POST['valor_adicional'] : 0.0;

        if ($anioPresupuestalId <= 0) {
            return [[], [], 'Falta el año presupuestal. Ese campo es obligatorio para registrar el ingreso.'];
        }

        if ($autogestionId <= 0) {
            return [[], [], 'Falta el ítem de Autogestión (selector del sidebar). Ese campo es obligatorio para registrar el ingreso. Si el selector aparece vacío, pide a un administrador que active un ítem en Configuraciones > Autogestión.'];
        }

        if ($dependencia === '') {
            return [[], [], 'Falta la dependencia. Ese campo es obligatorio para registrar el ingreso.'];
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
            'autogestion_id' => $autogestionId,
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

        $cabecera['id'] = $ingresoId;
        $this->regenerarAutomaticosDeIngreso($cabecera);

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

        $cabecera['id'] = $id;
        $cabecera['usuario_id'] = $existente['usuario_id'];
        $this->regenerarAutomaticosDeIngreso($cabecera);

        if ($existente['estado'] === 'enviado') {
            $this->modeloHistorial->registrar('ingreso_postgrado', $id, 'editado_tras_enviar', 'Editado después de enviarse.');
        }

        (new PeticionArchivada())->sincronizarDesdeOrigen('ingreso_postgrado', $id, $cabecera['valor_total'], $cabecera['dependencia']);

        $destino = !empty($_POST['volver'])
            ? $_POST['volver']
            : 'index.php?ruta=postgrado&tab=ingresos&anio_id=' . $cabecera['anio_presupuestal_id'] . '&autogestion_id=' . $cabecera['autogestion_id'];
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

        // Primero se borran los automáticos ligados a este ingreso_id y LUEGO el ingreso: al revés,
        // la FK fk_gasto_postgrado_ingreso (ON DELETE SET NULL) pone ingreso_id a NULL en cuanto se
        // borra el ingreso, y la búsqueda "WHERE ingreso_id = :id" ya no encontraría esas filas —
        // quedarían huérfanas para siempre en vez de borrarse.
        $this->modeloGasto->eliminarAutomaticosPorIngreso($id);
        $this->modeloIngreso->eliminar($id);

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
                } elseif ($existente !== null && $this->puedeGestionarAutomaticos($existente['tipo_automatico'])) {
                    $this->modeloGasto->eliminarForzado($id);
                    $eliminados++;
                }
            } else {
                $existente = $this->modeloIngreso->obtenerPorId($id);

                if ($existente !== null) {
                    $this->modeloGasto->eliminarAutomaticosPorIngreso($id);
                    $this->modeloIngreso->eliminar($id);
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

                if ($existente !== null && $existente['tipo_automatico'] === null && DuplicadorFilas::duplicarFila($db, 'gastos_postgrado', $id) !== null) {
                    $duplicados++;
                }
            } else {
                $nuevoId = DuplicadorFilas::duplicarFila($db, 'ingresos_postgrado', $id);

                if ($nuevoId !== null) {
                    DuplicadorFilas::duplicarFilasHijo($db, 'ingresos_postgrado_conceptos', 'ingreso_id', $id, $nuevoId);
                    $duplicados++;

                    $nuevo = $this->modeloIngreso->obtenerPorId($nuevoId);
                    if ($nuevo !== null) {
                        $this->regenerarAutomaticosDeIngreso($nuevo);
                    }
                }
            }
        }

        if ($duplicados === 0) {
            return ['No se duplicó ningún elemento.', ''];
        }

        return ['', 'Se duplicaron ' . $duplicados . ' elemento(s).'];
    }

    /**
     * (Re)calcula las filas automáticas (Excedentes nivel central, Contribución a posgrado) de UN
     * ingreso puntual, a partir de su propio valor_total y su propio usuario_id — nunca de un
     * agregado de "todos los ingresos de la dependencia para este ítem+año". Antes se
     * buscaba/actualizaba una única fila compartida por (año, ítem, tipo), sin filtrar por
     * dependencia ni usuario: si dos dependencias (o dos usuarios de la misma dependencia) usaban
     * el mismo ítem de Autogestión, la acción de uno podía pisar/heredar la fila del otro —
     * mostrando excedentes ajenos, o borrando el excedente real al recalcularlo en $0 sobre datos
     * que no eran los suyos. Ahora cada ingreso tiene sus propias filas automáticas, ligadas 1:1
     * por ingreso_id: se borran y se vuelven a crear desde cero en cada llamada, así que no hay
     * estado previo que reconciliar ni fila que puede pertenecer a otro ingreso/usuario/dependencia.
     *
     * @param array{id: int, anio_presupuestal_id: int, autogestion_id: int, dependencia: string, usuario_id: ?int, valor_total: float} $ingreso
     * @param array<string, array<int, array{sede_id:int,proyecto_id:int,rubro_id:int,actividad:string,insumo:string,meses:string,valor_total:float}>> $lineasPersonalizadas
     *        Clave = tipo ('excedentes' — no aplica a 'contrib_postgrado', fuera del alcance del
     *        reemplazo por plantilla). Ver el mismo mecanismo en ExtensionControlador.
     */
    private function regenerarAutomaticosDeIngreso(array $ingreso, array $lineasPersonalizadas = []): void
    {
        $ingresoId = (int) $ingreso['id'];
        $this->modeloGasto->eliminarAutomaticosPorIngreso($ingresoId);

        $valorIngreso = (float) $ingreso['valor_total'];

        if ($valorIngreso <= 0) {
            return;
        }

        $item = $this->modeloAutogestion->obtenerPorId((int) $ingreso['autogestion_id']);
        $usuarioId = $ingreso['usuario_id'] !== null ? (int) $ingreso['usuario_id'] : null;
        $dependencia = (string) $ingreso['dependencia'];
        $anioId = (int) $ingreso['anio_presupuestal_id'];
        $autogestionId = (int) $ingreso['autogestion_id'];

        $lineas = [];

        if ($item !== null && $item['excedentes'] !== null) {
            $lineas['excedentes'] = ['porcentaje' => (float) $item['excedentes'], 'etiqueta' => 'Excedentes nivel central'];
        }

        if ($item !== null && $item['contribucion_postgrado'] !== null) {
            $lineas['contrib_postgrado'] = ['porcentaje' => (float) $item['contribucion_postgrado'], 'etiqueta' => 'Contribución a posgrado'];
        }

        foreach ($lineas as $tipo => $info) {
            $valor = round($valorIngreso * $info['porcentaje'] / 100, 2);

            if ($valor <= 0) {
                continue;
            }

            if (!empty($lineasPersonalizadas[$tipo])) {
                foreach ($lineasPersonalizadas[$tipo] as $lineaPersonalizada) {
                    $this->modeloGasto->crearAutomatico([
                        'sede_id' => $lineaPersonalizada['sede_id'],
                        'anio_presupuestal_id' => $anioId,
                        'categoria' => 'Excedentes',
                        'dependencia' => $dependencia,
                        'linea_id' => self::AUTOMATICO_LINEA_ID,
                        'motor_id' => self::AUTOMATICO_MOTOR_ID,
                        'proyecto_id' => $lineaPersonalizada['proyecto_id'],
                        'objeto_proyecto_paa' => '',
                        'actividad' => $lineaPersonalizada['actividad'],
                        'rubro_id' => $lineaPersonalizada['rubro_id'],
                        'autogestion_id' => $autogestionId,
                        'ingreso_id' => $ingresoId,
                        'tipo_automatico' => $tipo,
                        'usuario_id' => $usuarioId,
                        'insumo' => $lineaPersonalizada['insumo'],
                        'cantidad' => 1,
                        'costo_unitario' => $lineaPersonalizada['valor_total'],
                        'valor_total' => $lineaPersonalizada['valor_total'],
                        'meses' => $lineaPersonalizada['meses'],
                    ]);
                }
                continue;
            }

            $porcentajeTexto = rtrim(rtrim(number_format($info['porcentaje'], 2), '0'), '.');
            $categoria = $info['etiqueta'] . ' (' . $porcentajeTexto . '%)';
            $definicion = $this->modeloDefinicionAutomatico->buscarConFallback(self::MODULO_AUTOGESTION, $autogestionId, $tipo, $dependencia);

            $datos = $definicion !== null
                ? [
                    'sede_id' => (int) $definicion['sede_id'],
                    'anio_presupuestal_id' => $anioId,
                    'categoria' => $categoria,
                    'dependencia' => $dependencia,
                    'linea_id' => self::AUTOMATICO_LINEA_ID,
                    'motor_id' => self::AUTOMATICO_MOTOR_ID,
                    'proyecto_id' => (int) $definicion['proyecto_id'],
                    'objeto_proyecto_paa' => '',
                    'actividad' => $definicion['actividad'],
                    'rubro_id' => (int) $definicion['rubro_id'],
                    'autogestion_id' => $autogestionId,
                    'ingreso_id' => $ingresoId,
                    'tipo_automatico' => $tipo,
                    'usuario_id' => $usuarioId,
                    'insumo' => $definicion['insumo'],
                    'cantidad' => 1,
                    'costo_unitario' => $valor,
                    'valor_total' => $valor,
                    'meses' => $definicion['meses'],
                ]
                : [
                    'sede_id' => self::AUTOMATICO_SEDE_ID,
                    'anio_presupuestal_id' => $anioId,
                    'categoria' => $categoria,
                    'dependencia' => $dependencia,
                    'linea_id' => self::AUTOMATICO_LINEA_ID,
                    'motor_id' => self::AUTOMATICO_MOTOR_ID,
                    'proyecto_id' => self::AUTOMATICO_PROYECTO_ID,
                    'objeto_proyecto_paa' => self::AUTOMATICO_OBJETO_PROYECTO_PAA,
                    'actividad' => self::AUTOMATICO_ACTIVIDAD,
                    'rubro_texto' => self::AUTOMATICO_RUBRO_TEXTO,
                    'autogestion_id' => $autogestionId,
                    'ingreso_id' => $ingresoId,
                    'tipo_automatico' => $tipo,
                    'usuario_id' => $usuarioId,
                    'insumo' => self::AUTOMATICO_INSUMO,
                    'cantidad' => 1,
                    'costo_unitario' => $valor,
                    'valor_total' => $valor,
                    'meses' => (string) self::AUTOMATICO_MES,
                ];

            $this->modeloGasto->crearAutomatico($datos);
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
        $dependenciaFila = null;

        if (!empty($usuarioActual['dependencia_id'])) {
            $dependenciaFila = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);
            $dependenciaUsuarioNombre = $dependenciaFila['nombre'] ?? null;
        }

        // "Auditar" (toggle global de la headerbar, solo para la dependencia raíz): en vez de
        // exigir ser dueño o destinatario exacto de cada ítem, se ve todo lo que cae en el árbol
        // de dependencias — mismo bypass que ya usa Peticiones en modo jerarquía.
        if (!empty($_SESSION['modo_auditoria']) && $dependenciaFila !== null && !empty($dependenciaFila['es_raiz_superadmin'])) {
            return array_values(array_filter($items, static fn (array $item): bool =>
                in_array($item['dependencia'] ?? $item['dependencia_destino'] ?? null, $dependenciasPermitidas, true)
            ));
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
