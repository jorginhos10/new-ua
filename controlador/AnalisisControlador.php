<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/AccesoAnalisis.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/GastoExtension.php';
require_once __DIR__ . '/../modelo/GastoPostgrado.php';
require_once __DIR__ . '/../modelo/IngresoExtension.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/GastoUnisalud.php';
require_once __DIR__ . '/../modelo/SolicitudMonitor.php';
require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/Snapshot.php';
require_once __DIR__ . '/../modelo/VersionArbol.php';
require_once __DIR__ . '/../modelo/FuenteDatosAnalisis.php';
require_once __DIR__ . '/../modelo/PresupuestoInstitucional.php';
require_once __DIR__ . '/../modelo/AnalisisArbolConfiguracion.php';
require_once __DIR__ . '/../modelo/TechosMetas.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/IngresoUnisalud.php';
require_once __DIR__ . '/../modelo/Acta.php';
require_once __DIR__ . '/../modelo/GeneradorXlsx.php';
require_once __DIR__ . '/../modelo/LectorXlsx.php';
require_once __DIR__ . '/PeticionesControlador.php';

/**
 * Página "Análisis" (?ruta=analisis): solo para el superadmin de la dependencia raíz
 * (es_raiz_superadmin, ver DevControlador::verificarAcceso() — mismo patrón copiado aquí).
 * 4 pestañas (Articulación PDI, Programación presupuestal, Análisis de distribución, Proyectos)
 * alternables con 3 modos de datos (Tiempo real / Repositorio / Usuario) — ver plan
 * el-techo-no-deberia-kind-candle.
 */
class AnalisisControlador
{
    private const TABS_VALIDAS = ['pdi', 'programacion', 'analisis', 'proyectos', 'techos', 'actas'];

    /** Pestaña Actas: solo dependencias de tipo Facultad, menos estas (tipo Facultad pero no son facultades). */
    private const DEPENDENCIAS_EXCLUIDAS_ACTAS = ['DIRECTIVAS'];

    private const CARPETA_ACTAS = __DIR__ . '/../almacenamiento/actas/';

    private const VISTAS_VALIDAS = ['tiempo_real', 'repositorio', 'usuario'];

    private const ORIGENES_ANALISIS = ['gasto_principal', 'gasto_extension', 'ingreso_extension', 'gasto_postgrado', 'ingreso_postgrado', 'gasto_unisalud', 'monitores', 'arl'];

    /**
     * Módulos de Autogestión con ingresos y egresos separados: una sola tarjeta en el selector
     * (con ambos totales) y pestañas Egresos/Ingresos sobre la tabla — mismo par que usa
     * PeticionesControlador::PARES_GASTO_INGRESO para peticiones-tipo-detalle.
     */
    private const PARES_GASTO_INGRESO = [
        'gasto_extension' => 'ingreso_extension',
        'gasto_postgrado' => 'ingreso_postgrado',
    ];

    private const TABLAS_POR_ORIGEN = [
        'gasto_principal' => 'gastos',
        'gasto_extension' => 'gastos_extension',
        'ingreso_extension' => 'ingresos_extension',
        'gasto_postgrado' => 'gastos_postgrado',
        'ingreso_postgrado' => 'ingresos_postgrado',
        'gasto_unisalud' => 'gastos_unisalud',
        // Solo para el % de asignación de la tarjeta Unisalud del selector; no es un origen
        // seleccionable (no está en ORIGENES_ANALISIS).
        'ingreso_unisalud' => 'ingresos_unisalud',
        'monitores' => 'solicitudes_monitores',
        'arl' => 'solicitudes_arl',
    ];

    private const ETIQUETAS_ORIGEN = [
        'gasto_principal' => 'Gasto',
        'gasto_extension' => 'Extensión',
        'ingreso_extension' => 'Extensión',
        'gasto_postgrado' => 'Postgrado',
        'ingreso_postgrado' => 'Postgrado',
        'gasto_unisalud' => 'Unisalud',
        'monitores' => 'Monitores',
        'arl' => 'ARL',
    ];

    /** Riesgos ARL 1-5: sin una sola columna 'valor_total', se suman aquí para pills/gráficas. */
    private const CLAVES_RIESGO_ARL = ['riesgo1_valor', 'riesgo2_valor', 'riesgo3_valor', 'riesgo4_valor', 'riesgo5_valor'];

    public function index(): void
    {
        $acceso = $this->resolverAcceso();
        AccesoAnalisis::establecer($acceso);

        if ($acceso['clase'] !== 'superadmin') {
            $this->aplicarRestriccionesNoSuperadmin($acceso);
        }

        $tab =in_array($_GET['tab'] ?? '', self::TABS_VALIDAS, true) ? $_GET['tab'] : 'programacion';
        $vista = in_array($_GET['vista'] ?? '', self::VISTAS_VALIDAS, true) ? $_GET['vista'] : 'tiempo_real';
        $pestanaArbol = $tab === 'programacion' ? 'programacion_presupuestal' : 'articulacion_pdi';
        $lado = ($_GET['lado'] ?? '') === 'egresos' ? 'egresos' : 'ingresos';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'configurar_expansion_arbol') {
            $this->guardarExpansionArbol($tab);

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarPost($tab, $vista);

            return;
        }

        if ($tab === 'programacion' && $vista === 'tiempo_real' && ($_GET['accion'] ?? '') === 'exportar_plantilla_presupuesto') {
            $this->exportarPlantillaPresupuesto();

            return;
        }

        if ($tab === 'proyectos' && $vista === 'tiempo_real' && ($_GET['accion'] ?? '') === 'exportar_plantilla_proyectos') {
            $this->exportarPlantillaProyectos();

            return;
        }

        // Exportar de "Análisis de distribución": en los 3 modos (exporta lo que se está viendo).
        if ($tab === 'analisis' && ($_GET['accion'] ?? '') === 'exportar_analisis') {
            $this->exportarAnalisis($vista);

            return;
        }

        if ($tab === 'actas' && ($_GET['accion'] ?? '') === 'descargar_acta') {
            $this->descargarActa($acceso);

            return;
        }

        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioActivo = $aniosActivos[0] ?? null;
        $dependenciasTodas = (new Dependencia())->obtenerTodas();

        if ($tab === 'actas') {
            $this->renderizarActas($vista, $acceso, $dependenciasTodas);

            return;
        }

        // Compartidas por las 4 pestañas — ver vista/analisis/index.php.
        $rolVista = $vista === 'tiempo_real' ? 'admin' : 'consulta';

        if ($tab === 'techos') {
            $this->renderizarTechosMetas($vista, $dependenciasTodas);

            return;
        }

        if ($tab === 'pdi' || $tab === 'programacion' || $tab === 'proyectos') {
            $this->renderizarArbol($tab, $vista, $pestanaArbol, $rolVista, $dependenciasTodas, $lado);

            return;
        }

        $this->renderizarAnalisis($vista, $rolVista, $anioActivo, $dependenciasTodas);
    }

    /** Acceso a Análisis del usuario actual (ver AccesoAnalisis). Sin acceso, al dashboard. */
    private function resolverAcceso(): array
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $acceso = $usuarioActual !== null
            ? AccesoAnalisis::resolver($usuarioActual, (string) ($_SESSION['usuario_rol'] ?? ''))
            : null;

        if ($acceso === null) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        return $acceso;
    }

    /**
     * Quien no es superadmin solo lee: sin POST ni acciones que cambian datos, y solo sus pestañas
     * y su vista. Si la URL pide otra cosa (o no pide nada), redirige a la primera permitida; en la
     * vista Usuario, la dependencia queda limitada a su subárbol.
     */
    private function aplicarRestriccionesNoSuperadmin(array $acceso): void
    {
        $accionesDeEscritura = ['exportar_plantilla_presupuesto', 'exportar_plantilla_proyectos', 'configurar_expansion_arbol'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || in_array($_GET['accion'] ?? '', $accionesDeEscritura, true)) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $tab = in_array($_GET['tab'] ?? '', $acceso['pestanas'], true) ? $_GET['tab'] : $acceso['pestanas'][0];
        $vista = in_array($_GET['vista'] ?? '', $acceso['vistas'], true) ? $_GET['vista'] : $acceso['vistas'][0];

        $dependencia = trim((string) ($_GET['dependencia'] ?? ''));
        if ($acceso['dependencias'] !== null && !in_array($dependencia, $acceso['dependencias'], true)) {
            $dependencia = $acceso['dependencia'];
        }

        $correcta = $tab === ($_GET['tab'] ?? null)
            && $vista === ($_GET['vista'] ?? null)
            && ($vista !== 'usuario' || $dependencia === ($_GET['dependencia'] ?? null));

        if ($correcta) {
            return;
        }

        $parametros = array_merge($_GET, ['ruta' => 'analisis', 'tab' => $tab, 'vista' => $vista]);
        if ($vista === 'usuario') {
            $parametros['dependencia'] = $dependencia;
        }

        header('Location: index.php?' . http_build_query($parametros));
        exit;
    }

    /** `usuarios.es_super_admin` (el SA), no la dependencia raíz que ya exige verificarAcceso(). */
    private function esSuperAdmin(): bool
    {
        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);

        return $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;
    }

    /**
     * POST por fetch desde el botón "Listas desplegadas" del árbol (solo SA): guarda si las listas
     * de esta pestaña salen desplegadas o recogidas para todos. Responde JSON, sin redirigir.
     */
    private function guardarExpansionArbol(string $tab): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->esSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Solo el SA puede cambiar esta configuración.']);

            return;
        }

        if (!in_array($tab, ['pdi', 'programacion', 'proyectos'], true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Pestaña no válida.']);

            return;
        }

        $expandido = ($_POST['expandido'] ?? '') === '1';
        $guardado = (new AnalisisArbolConfiguracion())->guardarExpandido($tab, $expandido, (int) $_SESSION['usuario_id']);

        echo json_encode(['ok' => $guardado, 'expandido' => $expandido]);
    }

    private function procesarPost(string $tab, string $vista): void
    {
        $error = '';
        $exito = '';

        if ($tab === 'analisis' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'eliminar_celda') {
            $error = $this->procesarEliminarModulo((string) ($_GET['origen'] ?? ''));
        } elseif ($tab === 'programacion' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'importar_presupuesto') {
            $error = $this->importarPresupuestoInstitucional();
            $exito = $error === '' ? 'Presupuesto institucional importado correctamente.' : '';
        } elseif ($tab === 'proyectos' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'importar_proyectos') {
            $error = $this->importarPresupuestoProyectos();
            $exito = $error === '' ? 'Proyectos importado correctamente.' : '';
        }

        if ($error !== '') {
            $_SESSION['analisis_flash_error'] = $error;
        } elseif ($exito !== '') {
            $_SESSION['analisis_flash_exito'] = $exito;
        }

        $destino = 'index.php?ruta=analisis&tab=' . urlencode($tab) . '&vista=' . urlencode($vista);
        if ($tab === 'analisis' && isset($_GET['origen'])) {
            $destino .= '&origen=' . urlencode((string) $_GET['origen']);
        }
        if ($tab === 'programacion') {
            $destino .= '&lado=' . urlencode($lado);
        }

        header('Location: ' . $destino);
        exit;
    }

    private function procesarEliminarModulo(string $origen): string
    {
        if (!in_array($origen, self::ORIGENES_ANALISIS, true)) {
            return 'Origen inválido.';
        }

        $id = (int) ($_POST['origen_id'] ?? 0);
        if ($id <= 0) {
            return 'Ítem inválido.';
        }

        $modelo = $this->obtenerModeloModulo($origen);

        // Igual que Extensión/Postgrado al eliminar un ingreso: primero sus gastos automáticos
        // (por ingreso_id) y LUEGO el ingreso — al revés, la FK ON DELETE SET NULL deja esos
        // gastos huérfanos para siempre.
        $origenGastoPar = array_search($origen, self::PARES_GASTO_INGRESO, true);
        if ($origenGastoPar !== false) {
            $this->obtenerModeloModulo($origenGastoPar)->eliminarAutomaticosPorIngreso($id);
        }

        $modelo->eliminar($id);

        return '';
    }

    /**
     * Pestañas 1, 2 y 4 (Articulación PDI / Programación presupuestal / Proyectos). Articulación
     * PDI sigue siendo el árbol Línea > Motor > Proyecto de siempre (portado de
     * vista/dev/pruebas/arbol.php, ver construirArbolPdi()); Programación presupuestal y
     * Proyectos muestran el presupuesto institucional (código jerárquico propio — ver
     * construirArbolPresupuesto()), cada una con su propio `tipo` ('ingreso'/'egreso' según
     * $lado, o 'proyecto' — nunca se mezclan, ver PresupuestoInstitucional). Las 3 comparten la
     * cáscara (franja superior, toggle Tiempo real/Repositorio/Usuario, columnas de año) — ver
     * vista/analisis/parciales/arbol.php, que solo conoce id/etiqueta/nivel/valores/hijos y no
     * sabe cuál árbol está dibujando.
     */
    private function renderizarArbol(string $tab, string $vista, string $pestanaArbol, string $rolVista, array $dependenciasTodas, string $lado): void
    {
        $tipoPresupuesto = $lado === 'ingresos' ? 'ingreso' : 'egreso';
        $esArbolPresupuesto = $tab === 'programacion' || $tab === 'proyectos';
        $tipoVersion = $tab === 'proyectos' ? 'proyecto' : $tipoPresupuesto;

        // Cada pestaña tiene su propio mecanismo de versiones, cada una con su propia lista:
        // Articulación PDI sigue usando VersionArbol (gastos/lineas/motores/proyectos);
        // Programación presupuestal y Proyectos usan PresupuestoInstitucional::obtenerVersiones(),
        // acotada por `tipo` ('egreso'/'ingreso' o 'proyecto') — nunca comparten lista de
        // versiones entre sí.
        $modeloVersiones = $esArbolPresupuesto ? new PresupuestoInstitucional() : new VersionArbol();
        $versiones = $esArbolPresupuesto
            ? $modeloVersiones->obtenerVersiones($tipoVersion)
            : $modeloVersiones->obtenerTodos($pestanaArbol);

        $versionIdActual = null;
        $dependenciaFiltroActual = null;

        // Si "Repositorio" está activo pero esta pestaña/lado todavía no tiene ninguna versión
        // guardada, el árbol debe quedar vacío — sin esto, se caería de vuelta a los datos en
        // vivo y se mostrarían con la etiqueta "Repositorio" puesta encima, dando cifras que no
        // corresponden a ninguna versión congelada.
        $sinDatosRepositorio = false;

        if ($vista === 'repositorio') {
            $versionIdSolicitada = (int) ($_GET['version_id'] ?? 0);

            // Solo el superadmin elige la versión; los demás ven la que él dejó activa.
            if ($versionIdSolicitada > 0 && AccesoAnalisis::actual()['clase'] === 'superadmin') {
                // El admin acaba de elegir, desde el selector, cuál versión ver — esa elección
                // queda como la activa para todos hasta que él la cambie de nuevo (no solo
                // para esta visita ni solo mientras la URL conserve version_id).
                if ($esArbolPresupuesto) {
                    $modeloVersiones->marcarVersionActiva($versionIdSolicitada, $tipoVersion);
                } else {
                    $modeloVersiones->marcarActiva($versionIdSolicitada, $pestanaArbol);
                }
                $versionIdActual = $versionIdSolicitada;
            } else {
                $versionActiva = $esArbolPresupuesto
                    ? $modeloVersiones->obtenerVersionActiva($tipoVersion)
                    : $modeloVersiones->obtenerActiva($pestanaArbol);
                $versionIdActual = $versionActiva !== null ? (int) $versionActiva['id'] : 0;
            }

            if ($versionIdActual <= 0) {
                $versionIdActual = null;
                $sinDatosRepositorio = true;
            }
        } elseif ($vista === 'usuario') {
            $dependenciaFiltroActual = trim((string) ($_GET['dependencia'] ?? ''));
            if ($dependenciaFiltroActual === '' && !empty($dependenciasTodas)) {
                $dependenciaFiltroActual = $dependenciasTodas[0]['nombre'];
            }
        }

        $modeloAnio = new AnioPresupuestal();
        $aniosTodos = $modeloAnio->obtenerTodos();
        $aniosPorNumero = [];
        foreach ($aniosTodos as $filaAnio) {
            $aniosPorNumero[(int) $filaAnio['anio']] = $filaAnio;
        }

        $aniosActivos = $modeloAnio->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? ($aniosTodos[0] ?? null);
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');
        $anioAnteriorNumero = $anioVigenteNumero - 1;

        $modoColumnas = $esArbolPresupuesto ? 'completo' : 'estructura';

        // La fecha de corte es netamente de la página (una etiqueta para la columna "a corte",
        // elegida con el <input type="date"> del topbar) — nunca viene de la plantilla, ni se
        // guarda en BD; por eso ambas pestañas la resuelven igual, desde la URL.
        $fechaCorteDefecto = $anioAnteriorNumero . date('-m-d');
        $fechaCorte = (string) ($_GET['corte'] ?? $fechaCorteDefecto);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCorte)) {
            $fechaCorte = $fechaCorteDefecto;
        }

        $columnasAnios = [
            ['clave' => 'vigente', 'etiqueta' => (string) $anioVigenteNumero, 'registro' => $anioVigenteRegistro, 'corte' => null, 'grupo' => 'vigente'],
        ];

        if ($modoColumnas === 'completo') {
            $columnasAnios[] = ['clave' => 'anterior_total', 'etiqueta' => $anioAnteriorNumero . ' (Final)', 'registro' => $aniosPorNumero[$anioAnteriorNumero] ?? null, 'corte' => null, 'grupo' => 'anterior'];
            $columnasAnios[] = ['clave' => 'anterior_corte', 'etiqueta' => $anioAnteriorNumero . ' (' . date('d/m/Y', strtotime($fechaCorte)) . ')', 'registro' => $aniosPorNumero[$anioAnteriorNumero] ?? null, 'corte' => $fechaCorte, 'grupo' => 'anterior'];

            for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
                $anioHistorico = $anioVigenteNumero - $desplazamiento;
                $columnasAnios[] = [
                    'clave' => 'historico_' . $anioHistorico,
                    'etiqueta' => (string) $anioHistorico,
                    'registro' => $aniosPorNumero[$anioHistorico] ?? null,
                    'corte' => null,
                    'grupo' => 'historico',
                ];
            }
        }

        $clavesColumnas = array_column($columnasAnios, 'clave');

        $columnasExtra = [];
        $etiquetaColumnaArbol = 'Línea / Motor / Proyecto';
        $cuadreProyectos = [];
        if ($tab === 'programacion') {
            [$arbolDatos, $totalesGenerales] = $this->construirArbolPresupuesto($tipoPresupuesto, $clavesColumnas, $anioVigenteNumero, $versionIdActual, $sinDatosRepositorio);
            $columnasExtra = [
                ['clave' => 'codigo', 'etiqueta' => 'Código'],
                ['clave' => 'proyectosPdi', 'etiqueta' => 'Proyecto(s) PDI'],
            ];
            $etiquetaColumnaArbol = 'Descripción';
        } elseif ($tab === 'proyectos') {
            [$arbolDatos, $totalesGenerales] = $this->construirArbolPresupuesto('proyecto', $clavesColumnas, $anioVigenteNumero, $versionIdActual, $sinDatosRepositorio);
            $columnasExtra = [
                ['clave' => 'codigo', 'etiqueta' => 'Código'],
            ];
            $etiquetaColumnaArbol = 'Descripción';
            if ($vista === 'tiempo_real') {
                $cuadreProyectos = $this->calcularCuadreProyectos($arbolDatos, $anioVigenteNumero);
            }
        } else {
            $fuenteDatos = new FuenteDatosAnalisis($versionIdActual, $dependenciaFiltroActual);
            [$arbolDatos, $totalesGenerales] = $this->construirArbolPdi($fuenteDatos, $columnasAnios, $clavesColumnas, $sinDatosRepositorio, $vista === 'usuario', $vista === 'tiempo_real');
        }

        $error = $_SESSION['analisis_flash_error'] ?? '';
        $exito = $_SESSION['analisis_flash_exito'] ?? '';
        unset($_SESSION['analisis_flash_error'], $_SESSION['analisis_flash_exito']);

        $tituloPaginaPorTab = [
            'programacion' => 'Análisis · Programación presupuestal ' . $anioVigenteNumero,
            'proyectos' => 'Análisis · Proyectos',
        ];
        $tituloPagina = $tituloPaginaPorTab[$tab] ?? 'Análisis · Articulación PDI';

        $esSuperAdmin = $this->esSuperAdmin();
        $arbolExpandidoPorDefecto = (new AnalisisArbolConfiguracion())->obtenerExpandido($tab);

        // Exportar (GET, las 3 pestañas de árbol, en los 3 modos): se resuelve aquí, después de
        // armar el árbol, para que el archivo salga exactamente con lo que la página mostraría
        // (misma versión, dependencia, lado y fecha de corte).
        if (($_GET['accion'] ?? '') === 'exportar_arbol') {
            $this->exportarArbol($tab, $vista, $lado, $arbolDatos, $totalesGenerales, $columnasAnios, $columnasExtra, $etiquetaColumnaArbol, $versiones, $versionIdActual, $dependenciaFiltroActual, $anioVigenteNumero, $fechaCorte, $modoColumnas);

            return;
        }

        require __DIR__ . '/../vista/analisis/index.php';
    }

    /**
     * .xlsx de solo lectura con el árbol tal como se ve: columnas extra (Código / Proyecto(s) PDI),
     * descripción sangrada por nivel, todas las columnas de año disponibles (también las que la
     * página tiene plegadas con "Año anterior"/"Últimos 5 años") y la fila Total al final.
     */
    private function exportarArbol(string $tab, string $vista, string $lado, array $arbolDatos, array $totalesGenerales, array $columnasAnios, array $columnasExtra, string $etiquetaColumnaArbol, array $versiones, ?int $versionIdActual, ?string $dependenciaFiltroActual, int $anioVigenteNumero, string $fechaCorte, string $modoColumnas): void
    {
        $nombreHoja = match ($tab) {
            'programacion' => $lado === 'ingresos' ? 'Ingresos' : 'Egresos',
            'proyectos' => 'Proyectos',
            default => 'Articulación PDI',
        };
        $titulo = match ($tab) {
            'programacion' => 'Programación presupuestal ' . $anioVigenteNumero . ' — ' . $nombreHoja,
            'proyectos' => 'Proyectos',
            default => 'Articulación PDI',
        };

        $descripcionModo = 'Tiempo real';
        if ($vista === 'repositorio') {
            $nombreVersion = 'sin versiones guardadas';
            foreach ($versiones as $version) {
                if ((int) $version['id'] === (int) $versionIdActual) {
                    $nombreVersion = $version['nombre'] . ' (' . date('d/m/Y H:i', strtotime($version['creado_en'])) . ')';
                    break;
                }
            }
            $descripcionModo = 'Repositorio — ' . $nombreVersion;
        } elseif ($vista === 'usuario') {
            $descripcionModo = 'Usuario — ' . ($dependenciaFiltroActual ?? '');
        }

        $encabezados = array_merge(
            array_column($columnasExtra, 'etiqueta'),
            [$etiquetaColumnaArbol],
            array_column($columnasAnios, 'etiqueta')
        );

        $filas = [];
        $this->aplanarArbolParaExportar($arbolDatos, 0, $columnasAnios, $columnasExtra, $filas);

        if (!empty($filas)) {
            $filaTotal = array_fill(0, count($columnasExtra), ['valor' => '', 'estilo' => 3]);
            $filaTotal[] = ['valor' => 'Total', 'estilo' => 3];
            foreach ($columnasAnios as $columna) {
                $filaTotal[] = ['valor' => $this->formatoMonedaExportar((float) ($totalesGenerales[$columna['clave']] ?? 0.0)), 'estilo' => 3];
            }
            $filas[] = $filaTotal;
        }

        $filasPrevias = [
            [['valor' => $titulo, 'estilo' => 4]],
            ['Modo', $descripcionModo],
        ];
        if ($modoColumnas === 'completo') {
            $filasPrevias[] = ['Fecha de corte', date('d/m/Y', strtotime($fechaCorte))];
        }
        $filasPrevias[] = ['Generado', date('d/m/Y H:i')];
        $filasPrevias[] = [];

        $prefijoArchivo = match ($tab) {
            'programacion' => 'programacion_' . ($lado === 'ingresos' ? 'ingresos' : 'egresos'),
            'proyectos' => 'proyectos',
            default => 'articulacion_pdi',
        };

        GeneradorXlsx::descargarHojas($prefijoArchivo . '_' . $anioVigenteNumero . '_' . $vista . '.xlsx', [[
            'nombre' => $nombreHoja,
            'filasPrevias' => $filasPrevias,
            'encabezados' => $encabezados,
            'filas' => empty($filas) ? [['No hay datos para mostrar.']] : $filas,
        ]]);
        exit;
    }

    /** Padre antes que sus hijos; la profundidad sangra la descripción y las raíces/totales van en negrita. */
    private function aplanarArbolParaExportar(array $nodos, int $profundidad, array $columnasAnios, array $columnasExtra, array &$filas): void
    {
        foreach ($nodos as $nodo) {
            $enNegrita = $profundidad === 0 || !empty($nodo['esTotal']);
            $celda = static fn (string $texto): array|string => $enNegrita ? ['valor' => $texto, 'estilo' => 4] : $texto;

            $fila = [];
            foreach ($columnasExtra as $columnaExtra) {
                $fila[] = $celda((string) ($nodo[$columnaExtra['clave']] ?? ''));
            }
            $fila[] = $celda(str_repeat('    ', $profundidad) . $nodo['etiqueta']);
            foreach ($columnasAnios as $columna) {
                $fila[] = $celda($this->formatoMonedaExportar((float) ($nodo['valores'][$columna['clave']] ?? 0.0)));
            }
            $filas[] = $fila;

            $this->aplanarArbolParaExportar($nodo['hijos'] ?? [], $profundidad + 1, $columnasAnios, $columnasExtra, $filas);
        }
    }

    /** Árbol Línea > Motor > Proyecto de siempre — portado de vista/dev/pruebas/arbol.php. */
    private function construirArbolPdi(FuenteDatosAnalisis $fuenteDatos, array $columnasAnios, array $clavesColumnas, bool $sinDatosRepositorio, bool $modoUsuario, bool $tiempoReal): array
    {
        // En tiempo real, la asignación de Articulación PDI es solo gasto principal más programación
        // presupuestal: no suma los gastos de Autogestión (extensión, postgrado, unisalud).
        $totalesPorColumna = [];
        foreach ($columnasAnios as $columna) {
            $totalesPorColumna[$columna['clave']] = $this->totalesPorProyecto($columna['registro'], $columna['corte'], $fuenteDatos, !$tiempoReal);
        }

        // El presupuesto institucional (líneas de Egresos con Proyecto(s) PDI) solo aporta al año
        // VIGENTE — y solo en tiempo real/repositorio: no tiene dependencia, así que en modo
        // "usuario" no se le puede atribuir a una dependencia puntual.
        $columnaVigente = $columnasAnios[0];
        if (!$modoUsuario && $columnaVigente['registro'] !== null) {
            // obtenerAportePorProyecto() espera el AÑO CALENDARIO real (2027), no el id de
            // anios_presupuestales — presupuesto_institucional_valores.anio guarda ese mismo
            // año calendario, nunca el id (ver PresupuestoInstitucional::obtenerArbolConValores()).
            $aporte = (new PresupuestoInstitucional())->obtenerAportePorProyecto((int) $columnaVigente['registro']['anio']);
            foreach ($aporte as $proyectoId => $valor) {
                $totalesPorColumna[$columnaVigente['clave']][$proyectoId] = ($totalesPorColumna[$columnaVigente['clave']][$proyectoId] ?? 0.0) + $valor;
            }
        }

        $lineas = $sinDatosRepositorio ? [] : $fuenteDatos->obtenerLineas();
        $motores = $sinDatosRepositorio ? [] : $fuenteDatos->obtenerMotores();
        $proyectos = $sinDatosRepositorio ? [] : $fuenteDatos->obtenerProyectos();

        $arbolDatos = [];
        foreach ($lineas as $linea) {
            $nodoLinea = [
                'id' => 'linea-' . $linea['id'],
                'etiqueta' => $linea['codigo'] . ' - ' . $linea['nombre'],
                'nivel' => 0,
                'valores' => array_fill_keys($clavesColumnas, 0.0),
                'hijos' => [],
            ];

            foreach ($motores as $motor) {
                if ((int) $motor['linea_id'] !== (int) $linea['id']) {
                    continue;
                }

                $nodoMotor = [
                    'id' => 'motor-' . $motor['id'],
                    'etiqueta' => $motor['codigo'] . ' - ' . $motor['nombre'],
                    'nivel' => 1,
                    'valores' => array_fill_keys($clavesColumnas, 0.0),
                    'hijos' => [],
                ];

                foreach ($proyectos as $proyecto) {
                    if ((int) $proyecto['motor_id'] !== (int) $motor['id']) {
                        continue;
                    }

                    $valoresProyecto = [];
                    foreach ($clavesColumnas as $clave) {
                        $valor = $totalesPorColumna[$clave][(int) $proyecto['id']] ?? 0.0;
                        $valoresProyecto[$clave] = $valor;
                        $nodoMotor['valores'][$clave] += $valor;
                    }

                    if ($modoUsuario && !$this->tieneDinero($valoresProyecto)) {
                        continue;
                    }

                    $nodoMotor['hijos'][] = [
                        'id' => 'proyecto-' . $proyecto['id'],
                        'etiqueta' => $proyecto['codigo'] . ' - ' . $proyecto['nombre'],
                        'nivel' => 2,
                        'valores' => $valoresProyecto,
                        'hijos' => [],
                    ];
                }

                if ($modoUsuario && !$this->tieneDinero($nodoMotor['valores'])) {
                    continue;
                }

                foreach ($clavesColumnas as $clave) {
                    $nodoLinea['valores'][$clave] += $nodoMotor['valores'][$clave];
                }
                $nodoLinea['hijos'][] = $nodoMotor;
            }

            if ($modoUsuario && !$this->tieneDinero($nodoLinea['valores'])) {
                continue;
            }

            $arbolDatos[] = $nodoLinea;
        }

        $totalesGenerales = array_fill_keys($clavesColumnas, 0.0);
        foreach ($arbolDatos as $nodoLineaTotal) {
            foreach ($clavesColumnas as $clave) {
                $totalesGenerales[$clave] += $nodoLineaTotal['valores'][$clave];
            }
        }

        return [$arbolDatos, $totalesGenerales];
    }

    /**
     * Pestaña "Techos y Metas": Tiempo real o Usuario (Repositorio no aplica — no hay versiones de
     * techos). Un bloque a la vez: gasto principal (techo vs asignación) o un módulo de
     * autogestión (tope vs ingresos).
     */
    private function renderizarTechosMetas(string $vista, array $dependenciasTodas): void
    {
        $vista = $vista === 'usuario' ? 'usuario' : 'tiempo_real';
        $bloque = in_array($_GET['bloque'] ?? '', TechosMetas::BLOQUES, true) ? (string) $_GET['bloque'] : 'gastos';

        $dependenciaFiltroActual = null;
        if ($vista === 'usuario') {
            $dependenciaFiltroActual = trim((string) ($_GET['dependencia'] ?? ''));
            if ($dependenciaFiltroActual === '' && !empty($dependenciasTodas)) {
                $dependenciaFiltroActual = $dependenciasTodas[0]['nombre'];
            }
        }

        $modeloAnio = new AnioPresupuestal();
        $aniosTodos = $modeloAnio->obtenerTodos();
        $aniosActivos = $modeloAnio->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? ($aniosTodos[0] ?? null);
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');
        $anioVigenteId = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['id'] : 0;

        $idPorAnio = [];
        foreach ($aniosTodos as $filaAnio) {
            $idPorAnio[(int) $filaAnio['anio']] = (int) $filaAnio['id'];
        }
        $aniosVentana = [];
        for ($anio = $anioVigenteNumero - 4; $anio <= $anioVigenteNumero; $anio++) {
            $aniosVentana[$anio] = $idPorAnio[$anio] ?? null;
        }

        $datos = (new TechosMetas())->construirDatos($bloque, $dependenciaFiltroActual, $anioVigenteId, $aniosVentana);

        $tab = 'techos';
        $error = '';
        $exito = '';
        $tituloPagina = 'Análisis · Techos y Metas';

        require __DIR__ . '/../vista/analisis/index.php';
    }

    /**
     * Pestaña Actas: una fila por facultad activa (tipo Facultad, sin DIRECTIVAS) con las actas que cargó en el
     * módulo Actas. Solo lectura y sin modos de datos; quien no es superadmin solo ve las de su
     * subárbol de dependencias (mismo alcance que la vista Usuario).
     */
    private function renderizarActas(string $vista, array $acceso, array $dependenciasTodas): void
    {
        $actasPorDependencia = [];
        foreach ((new Acta())->obtenerTodasConDetalle() as $acta) {
            $actasPorDependencia[(int) $acta['dependencia_id']][] = $acta;
        }

        $dependenciasActas = [];
        foreach ($dependenciasTodas as $dependencia) {
            $esListable = ($dependencia['tipo'] ?? '') === 'Facultad'
                && !in_array($dependencia['nombre'], self::DEPENDENCIAS_EXCLUIDAS_ACTAS, true)
                && ($dependencia['estado'] ?? '') === 'activo'
                && empty($dependencia['no_listar']);

            if (!$esListable) {
                continue;
            }
            if ($acceso['dependencias'] !== null && !in_array($dependencia['nombre'], $acceso['dependencias'], true)) {
                continue;
            }

            $dependenciasActas[] = $dependencia + ['actas' => $actasPorDependencia[(int) $dependencia['id']] ?? []];
        }

        $tab = 'actas';
        $error = '';
        $exito = '';
        $tituloPagina = 'Análisis · Actas';

        require __DIR__ . '/../vista/analisis/index.php';
    }

    /** Descarga (en línea) un acta, si su dependencia está dentro del alcance del usuario. */
    private function descargarActa(array $acceso): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $acta = $id > 0 ? (new Acta())->obtenerPorId($id) : null;
        $dependencia = $acta !== null ? (new Dependencia())->obtenerPorId((int) $acta['dependencia_id']) : null;

        $permitida = $acta !== null
            && ($acceso['dependencias'] === null || ($dependencia !== null && in_array($dependencia['nombre'], $acceso['dependencias'], true)));
        $rutaArchivo = $permitida ? self::CARPETA_ACTAS . basename($acta['nombre_almacenado']) : '';

        if (!$permitida || !is_file($rutaArchivo)) {
            http_response_code(404);
            exit('Acta no encontrada.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', basename($acta['nombre_archivo'])) . '"');
        header('Content-Length: ' . filesize($rutaArchivo));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($rutaArchivo);
        exit;
    }

    private function tieneDinero(array $valores): bool
    {
        foreach ($valores as $valor) {
            if (abs((float) $valor) > 0.005) {
                return true;
            }
        }

        return false;
    }

    /**
     * Árbol del presupuesto institucional (código jerárquico propio) para Programación
     * presupuestal — reemplaza ahí a Línea/Motor/Proyecto. $tipo ya es 'ingreso'/'egreso' (no
     * 'ingresos'/'egresos' — esa es la forma de $_GET['lado'], ver resolverAniosPorClave()).
     */
    private function construirArbolPresupuesto(string $tipo, array $clavesColumnas, int $anioVigenteNumero, ?int $versionId, bool $sinDatosRepositorio): array
    {
        $anioPorClave = $this->resolverAniosPorClave($anioVigenteNumero);
        $anios = array_values(array_unique(array_values($anioPorClave)));

        $modeloPresupuesto = new PresupuestoInstitucional();

        if ($sinDatosRepositorio) {
            $arbolCrudo = [];
        } elseif ($versionId !== null) {
            $arbolCrudo = $modeloPresupuesto->obtenerArbolConValoresDeVersion($versionId, $anios);
        } else {
            $arbolCrudo = $modeloPresupuesto->obtenerArbolConValores($tipo, $anios);
        }

        $arbolDatos = $this->remapArbolPresupuesto($arbolCrudo, $anioPorClave);

        $totalesGenerales = array_fill_keys($clavesColumnas, 0.0);
        foreach ($arbolDatos as $nodoRaiz) {
            foreach ($clavesColumnas as $clave) {
                $totalesGenerales[$clave] += $nodoRaiz['valores'][$clave] ?? 0.0;
            }
        }

        return [$arbolDatos, $totalesGenerales];
    }

    /**
     * Cuadre por código raíz entre "Proyectos" (tipo 'proyecto') y Programación presupuestal
     * Egresos — solo informativo (nunca bloquea guardar/importar), y solo por nivel raíz (no
     * línea a línea): cuando un código de capítulo (ej. '1.6.0') existe en ambos árboles, se
     * compara el total del año vigente de cada lado. $arbolDatos ya viene remapeado por clave
     * ('vigente', no el año literal — ver remapArbolPresupuesto()); Egresos se lee crudo (por
     * año literal) porque aquí solo hace falta el año vigente, no todo el remapeo de columnas.
     */
    private function calcularCuadreProyectos(array $arbolDatos, int $anioVigenteNumero): array
    {
        $raicesEgresos = (new PresupuestoInstitucional())->obtenerArbolConValores('egreso', [$anioVigenteNumero]);
        $totalesEgresosPorCodigo = [];
        foreach ($raicesEgresos as $raiz) {
            $totalesEgresosPorCodigo[$raiz['codigo']] = (float) ($raiz['valores'][$anioVigenteNumero] ?? 0.0);
        }

        $cuadre = [];
        foreach ($arbolDatos as $raizProyecto) {
            $totalPresupuesto = $totalesEgresosPorCodigo[$raizProyecto['codigo']] ?? null;
            $totalProyectos = (float) ($raizProyecto['valores']['vigente'] ?? 0.0);
            $cuadre[] = [
                'codigo' => $raizProyecto['codigo'],
                'descripcion' => $raizProyecto['etiqueta'],
                'totalProyectos' => $totalProyectos,
                'totalPresupuesto' => $totalPresupuesto,
                'diferencia' => $totalPresupuesto !== null ? $totalProyectos - $totalPresupuesto : null,
            ];
        }

        return $cuadre;
    }

    /** clave de columna de año ('vigente'/'anterior_total'/...) => año calendario real. */
    private function resolverAniosPorClave(int $anioVigenteNumero): array
    {
        $anioAnteriorNumero = $anioVigenteNumero - 1;
        $anioPorClave = [
            'vigente' => $anioVigenteNumero,
            'anterior_total' => $anioAnteriorNumero,
            'anterior_corte' => $anioAnteriorNumero,
        ];

        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $anioPorClave['historico_' . ($anioVigenteNumero - $desplazamiento)] = $anioVigenteNumero - $desplazamiento;
        }

        return $anioPorClave;
    }

    /** Convierte 'valores'[año] en 'valores'[clave] en cada nodo, recursivamente. */
    private function remapArbolPresupuesto(array $nodos, array $anioPorClave): array
    {
        $resultado = [];
        foreach ($nodos as $nodo) {
            $valoresPorClave = [];
            foreach ($anioPorClave as $clave => $anio) {
                $valoresPorClave[$clave] = $clave === 'anterior_corte'
                    ? (float) ($nodo['valorCorte'] ?? 0.0)
                    : (float) ($nodo['valores'][$anio] ?? 0.0);
            }
            $nodo['valores'] = $valoresPorClave;
            $nodo['hijos'] = $this->remapArbolPresupuesto($nodo['hijos'], $anioPorClave);
            $resultado[] = $nodo;
        }

        return $resultado;
    }

    /**
     * Igual que vista/dev/pruebas/arbol.php::totalesPorProyectoArbol(), vía FuenteDatosAnalisis.
     * Suma gasto_principal y, si $incluirAutogestion, también los gastos de autogestión.
     */
    private function totalesPorProyecto(?array $registroAnio, ?string $fechaCorte, FuenteDatosAnalisis $fuenteDatos, bool $incluirAutogestion = true): array
    {
        if ($registroAnio === null) {
            return [];
        }

        $anioId = (int) $registroAnio['id'];
        $gastos = $fuenteDatos->obtenerGastosPorAnio($anioId);
        if ($incluirAutogestion) {
            $gastos = array_merge($gastos, $fuenteDatos->obtenerGastosAutogestionPorAnio($anioId));
        }
        $totales = [];

        foreach ($gastos as $gasto) {
            if ($fechaCorte !== null && ($gasto['creado_en'] ?? '') > $fechaCorte . ' 23:59:59') {
                continue;
            }
            $idProyecto = (int) ($gasto['proyecto_id'] ?? 0);
            if ($idProyecto === 0) {
                continue;
            }
            $totales[$idProyecto] = ($totales[$idProyecto] ?? 0.0) + (float) ($gasto['valor_total'] ?? 0);
        }

        return $totales;
    }

    /**
     * Pestaña 3 (Análisis de distribución): tabla-real + selector de 4 módulos de gasto — ver
     * vista/analisis/parciales/tabla-modulo.php y selector-modulos.php.
     */
    private function renderizarAnalisis(string $vista, string $rolVista, ?array $anioActivo, array $dependenciasTodas): void
    {
        $origenActivo = in_array($_GET['origen'] ?? '', self::ORIGENES_ANALISIS, true) ? $_GET['origen'] : 'gasto_principal';
        $anioPresupuestalId = $anioActivo !== null ? (int) $anioActivo['id'] : 0;

        $snapshots = (new Snapshot())->obtenerTodos();
        [$snapshotIdActual, $dependenciaFiltroActual] = $this->resolverFiltrosAnalisis($vista, $snapshots, $dependenciasTodas);

        $error = $_SESSION['analisis_flash_error'] ?? '';
        unset($_SESSION['analisis_flash_error']);

        $totalesPorModulo = [];
        foreach (self::ORIGENES_ANALISIS as $origenModulo) {
            $totalesPorModulo[$origenModulo] = $anioPresupuestalId > 0
                ? $this->obtenerTotalModulo($origenModulo, $anioPresupuestalId, $vista, $snapshotIdActual, $dependenciaFiltroActual)
                : 0.0;
        }

        [$techosPorModulo, $asignacionPorModulo, $distribucionPorModulo] = $this->obtenerIndicadoresSelector(
            $anioActivo, $vista, $snapshotIdActual, $dependenciaFiltroActual, $dependenciasTodas, $totalesPorModulo
        );

        $resultado = $anioPresupuestalId > 0
            ? $this->obtenerFilasAnalisis($origenActivo, $anioPresupuestalId, $vista, $snapshotIdActual, $dependenciaFiltroActual)
            : ['columnas' => [], 'claves' => [], 'filas' => []];

        $columnas = $resultado['columnas'];
        $clavesFila = $resultado['claves'];
        $filasCompletas = $resultado['filas'];

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

        $clavesOcultasPorDefecto = ['linea', 'motor', 'cantidad', 'costo_unitario', 'techo'];
        $indicesOcultosPorDefecto = array_values(array_intersect_key(
            array_flip($clavesFila),
            array_flip($clavesOcultasPorDefecto)
        ));

        // Pestañas Egresos/Ingresos (null si el módulo activo no tiene ingresos separados).
        $origenGastoActivo = isset(self::PARES_GASTO_INGRESO[$origenActivo])
            ? $origenActivo
            : (array_search($origenActivo, self::PARES_GASTO_INGRESO, true) ?: null);
        $pestanasGastoIngreso = $origenGastoActivo !== null
            ? [
                'egresos' => $origenGastoActivo,
                'ingresos' => self::PARES_GASTO_INGRESO[$origenGastoActivo],
                'activo' => $origenActivo === $origenGastoActivo ? 'egresos' : 'ingresos',
            ]
            : null;
        $paresGastoIngreso = self::PARES_GASTO_INGRESO;

        $tituloPagina = 'Análisis · Análisis de distribución — ' . self::ETIQUETAS_ORIGEN[$origenActivo]
            . ($pestanasGastoIngreso !== null ? ($pestanasGastoIngreso['activo'] === 'ingresos' ? ' · Ingresos' : ' · Egresos') : '');
        $tab = 'analisis';

        require __DIR__ . '/../vista/analisis/index.php';
    }

    /** Autogestión: módulo de egresos de la tarjeta => su origen de ingresos. */
    private const INGRESOS_AUTOGESTION_SELECTOR = [
        'gasto_extension' => 'ingreso_extension',
        'gasto_postgrado' => 'ingreso_postgrado',
        'gasto_unisalud' => 'ingreso_unisalud',
    ];

    /**
     * Indicadores de las tarjetas del selector de módulos (ver selector-modulos.php):
     * - techos: barra de Gasto = egresos contra el presupuesto del año activo (el mismo
     *   denominador de "Resumen de gastos" del Dashboard); en modo Usuario, contra el techo de esa
     *   dependencia — salvo la raíz superadmin, que abarca todas y usa el presupuesto del año.
     *   Sin techo configurado = sin barra (la vista lo indica con "Sin techo").
     * - asignación (% de la esquina): Gasto = egresos / techo; Autogestión (Extensión, Postgrado,
     *   Unisalud) = egresos / ingresos del módulo. Sin denominador = sin %.
     * - distribución: barra de Autogestión = egresos repartidos por categoría (Gastos /
     *   Inversiones / Excedentes, y "Otros" para cualquier otra, ej. "Contribución a posgrado").
     *
     * @return array{0: array<string, array{avance: float, techo: float, etiqueta: string}>, 1: array<string, array{porcentaje: float, titulo: string}>, 2: array<string, array<string, float>>}
     */
    private function obtenerIndicadoresSelector(?array $anioActivo, string $vista, ?int $snapshotId, ?string $dependenciaFiltro, array $dependenciasTodas, array $totalesPorModulo): array
    {
        if ($anioActivo === null) {
            return [[], [], []];
        }

        $anioId = (int) $anioActivo['id'];
        $monedaCorta = static fn (float $valor): string => '$' . number_format($valor, 0, ',', '.');
        $techos = [];
        $asignacion = [];
        $distribucion = [];

        $techoGasto = (float) ($anioActivo['presupuesto'] ?? 0);
        $etiquetaGasto = 'presupuesto ' . $anioActivo['anio'];
        if ($vista === 'usuario') {
            $techoGasto = 0.0;
            foreach ($dependenciasTodas as $dependencia) {
                if ($dependencia['nombre'] === $dependenciaFiltro) {
                    if (!empty($dependencia['es_raiz_superadmin'])) {
                        $techoGasto = (float) ($anioActivo['presupuesto'] ?? 0);
                    } else {
                        $techoGasto = (float) ((new PresupuestoDependencia())->obtenerPorAnio($anioId)[(int) $dependencia['id']]['techo'] ?? 0);
                        $etiquetaGasto = 'techo de ' . $dependencia['nombre'];
                    }
                    break;
                }
            }
        }
        if ($techoGasto > 0) {
            $egresosGasto = (float) ($totalesPorModulo['gasto_principal'] ?? 0);
            $techos['gasto_principal'] = ['avance' => $egresosGasto, 'techo' => $techoGasto, 'etiqueta' => $etiquetaGasto];
            $asignacion['gasto_principal'] = [
                'porcentaje' => $egresosGasto / $techoGasto * 100,
                'titulo' => 'Egresos asignados del ' . $etiquetaGasto . ': ' . $monedaCorta($egresosGasto) . ' de ' . $monedaCorta($techoGasto),
            ];
        }

        foreach (self::INGRESOS_AUTOGESTION_SELECTOR as $origenEgresos => $origenIngresos) {
            $egresos = (float) ($totalesPorModulo[$origenEgresos] ?? 0);
            $ingresos = $totalesPorModulo[$origenIngresos]
                ?? $this->obtenerTotalModulo($origenIngresos, $anioId, $vista, $snapshotId, $dependenciaFiltro);
            if ($ingresos > 0) {
                $asignacion[$origenEgresos] = [
                    'porcentaje' => $egresos / $ingresos * 100,
                    'titulo' => 'Egresos asignados de los ingresos: ' . $monedaCorta($egresos) . ' de ' . $monedaCorta($ingresos),
                ];
            }

            $porCategoria = ['Gastos' => 0.0, 'Inversiones' => 0.0, 'Excedentes' => 0.0, 'Otros' => 0.0];
            foreach ($this->obtenerFilasCrudasModulo($origenEgresos, $anioId, $vista, $snapshotId, $dependenciaFiltro) as $fila) {
                $categoria = (string) ($fila['categoria'] ?? '');
                $grupo = 'Otros';
                foreach (['Gastos', 'Inversiones', 'Excedentes'] as $prefijo) {
                    if (stripos($categoria, $prefijo) === 0) {
                        $grupo = $prefijo;
                        break;
                    }
                }
                $porCategoria[$grupo] += $this->obtenerValorFilaModulo($origenEgresos, $fila);
            }
            if (array_sum($porCategoria) > 0) {
                $distribucion[$origenEgresos] = $porCategoria;
            }
        }

        return [$techos, $asignacion, $distribucion];
    }

    /**
     * [snapshot_id, dependencia] de "Análisis de distribución" según el modo: Repositorio → el
     * snapshot de la URL (o el más reciente); Usuario → la dependencia de la URL (o la primera).
     * Compartido por la página y por su Exportar, para que el archivo sea exactamente lo que se ve.
     */
    private function resolverFiltrosAnalisis(string $vista, array $snapshots, array $dependenciasTodas): array
    {
        $snapshotId = null;
        $dependenciaFiltro = null;

        if ($vista === 'repositorio') {
            // Solo el superadmin elige el snapshot; los demás ven el más reciente.
            $snapshotSolicitado = AccesoAnalisis::actual()['clase'] === 'superadmin' ? ($_GET['snapshot_id'] ?? null) : null;
            $snapshotId = (int) ($snapshotSolicitado ?? ($snapshots[0]['id'] ?? 0));
            if ($snapshotId <= 0) {
                $snapshotId = null;
            }
        } elseif ($vista === 'usuario') {
            $dependenciaFiltro = trim((string) ($_GET['dependencia'] ?? ''));
            if ($dependenciaFiltro === '' && !empty($dependenciasTodas)) {
                $dependenciaFiltro = $dependenciasTodas[0]['nombre'];
            }
        }

        return [$snapshotId, $dependenciaFiltro];
    }

    /**
     * Exportar (GET, botón "Exportar" de Análisis de distribución): un .xlsx de solo lectura con
     * una hoja "Resumen" (registros y total por módulo) y una hoja por módulo con todas sus
     * columnas — Extensión y Postgrado en dos hojas (Egresos/Ingresos). Respeta el modo activo:
     * Tiempo real, el snapshot elegido en Repositorio o la dependencia elegida en Usuario.
     */
    private function exportarAnalisis(string $vista): void
    {
        $anioActivo = (new AnioPresupuestal())->obtenerActivos()[0] ?? null;
        $anioPresupuestalId = $anioActivo !== null ? (int) $anioActivo['id'] : 0;
        $anioTexto = $anioActivo !== null ? (string) $anioActivo['anio'] : date('Y');

        $snapshots = (new Snapshot())->obtenerTodos();
        [$snapshotId, $dependenciaFiltro] = $this->resolverFiltrosAnalisis($vista, $snapshots, (new Dependencia())->obtenerTodas());

        $descripcionModo = 'Tiempo real';
        if ($vista === 'repositorio') {
            $nombreSnapshot = 'sin snapshots guardados';
            foreach ($snapshots as $snapshot) {
                if ((int) $snapshot['id'] === $snapshotId) {
                    $nombreSnapshot = $snapshot['nombre'] . ' (' . date('d/m/Y H:i', strtotime($snapshot['creado_en'])) . ')';
                    break;
                }
            }
            $descripcionModo = 'Repositorio — ' . $nombreSnapshot;
        } elseif ($vista === 'usuario') {
            $descripcionModo = 'Usuario — ' . ($dependenciaFiltro ?? '');
        }

        $filasResumen = [];
        $hojasModulo = [];
        $totalEgresos = 0.0;
        $totalIngresos = 0.0;

        foreach (self::ORIGENES_ANALISIS as $origen) {
            $nombreHoja = $this->nombreHojaAnalisis($origen);
            $resultado = $anioPresupuestalId > 0
                ? $this->obtenerFilasAnalisis($origen, $anioPresupuestalId, $vista, $snapshotId, $dependenciaFiltro)
                : ['columnas' => [], 'claves' => [], 'filas' => []];
            $totalModulo = $anioPresupuestalId > 0
                ? $this->obtenerTotalModulo($origen, $anioPresupuestalId, $vista, $snapshotId, $dependenciaFiltro)
                : 0.0;

            if (in_array($origen, self::PARES_GASTO_INGRESO, true)) {
                $totalIngresos += $totalModulo;
            } else {
                $totalEgresos += $totalModulo;
            }

            $filasResumen[] = [$nombreHoja, (string) count($resultado['filas']), $this->formatoMonedaExportar($totalModulo)];
            $hojasModulo[] = $this->construirHojaAnalisis($nombreHoja, $resultado);
        }

        // Egresos e Ingresos nunca se suman entre sí: dos totales separados.
        $filasResumen[] = [
            ['valor' => 'Total egresos', 'estilo' => 3],
            ['valor' => '', 'estilo' => 3],
            ['valor' => $this->formatoMonedaExportar($totalEgresos), 'estilo' => 3],
        ];
        $filasResumen[] = [
            ['valor' => 'Total ingresos', 'estilo' => 3],
            ['valor' => '', 'estilo' => 3],
            ['valor' => $this->formatoMonedaExportar($totalIngresos), 'estilo' => 3],
        ];

        $hojaResumen = [
            'nombre' => 'Resumen',
            'filasPrevias' => [
                [['valor' => 'Análisis de distribución', 'estilo' => 4]],
                ['Año presupuestal', $anioTexto],
                ['Modo', $descripcionModo],
                ['Generado', date('d/m/Y H:i')],
                [],
            ],
            'encabezados' => ['Módulo', 'Registros', 'Total'],
            'filas' => $filasResumen,
        ];

        GeneradorXlsx::descargarHojas('analisis_distribucion_' . $anioTexto . '_' . $vista . '.xlsx', array_merge([$hojaResumen], $hojasModulo));
        exit;
    }

    /** "Gasto", "Extensión - Egresos", "Extensión - Ingresos"... (nombre de hoja y fila del Resumen). */
    private function nombreHojaAnalisis(string $origen): string
    {
        $etiqueta = self::ETIQUETAS_ORIGEN[$origen];
        if (isset(self::PARES_GASTO_INGRESO[$origen])) {
            return $etiqueta . ' - Egresos';
        }
        if (in_array($origen, self::PARES_GASTO_INGRESO, true)) {
            return $etiqueta . ' - Ingresos';
        }

        return $etiqueta;
    }

    /**
     * Una hoja con las mismas columnas y valores que la tabla de la página (incluidas las que
     * vienen ocultas por defecto) y una fila de total al final para las columnas de valor.
     */
    private function construirHojaAnalisis(string $nombreHoja, array $resultado): array
    {
        $claves = $resultado['claves'];
        $sumasPorClave = array_fill_keys($claves, 0.0);
        $clavesNumericas = array_fill_keys($claves, false);

        $filas = [];
        foreach ($resultado['filas'] as $filaCompleta) {
            $fila = [];
            foreach ($claves as $clave) {
                $valor = $filaCompleta[$clave] ?? null;
                if (is_float($valor)) {
                    $sumasPorClave[$clave] += $valor;
                    $clavesNumericas[$clave] = true;
                    $fila[] = $this->formatoMonedaExportar($valor);
                } else {
                    $fila[] = $valor === null ? '—' : (string) $valor;
                }
            }
            $filas[] = $fila;
        }

        // Cantidad/Costo unitario/Techo también pueden venir como float, pero sumarlos no
        // significa nada: solo se totalizan las columnas de valor.
        if (!empty($filas)) {
            $filaTotal = [];
            foreach ($claves as $indice => $clave) {
                $esSumable = $clavesNumericas[$clave] && !in_array($clave, ['cantidad', 'costo_unitario', 'techo'], true);
                $texto = $indice === 0 ? 'Total' : ($esSumable ? $this->formatoMonedaExportar($sumasPorClave[$clave]) : '');
                $filaTotal[] = ['valor' => $texto, 'estilo' => 3];
            }
            $filas[] = $filaTotal;
        }

        return [
            'nombre' => $nombreHoja,
            'encabezados' => !empty($resultado['columnas']) ? $resultado['columnas'] : ['Sin registros'],
            'filas' => $filas,
        ];
    }

    private function formatoMonedaExportar(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    /** Filas crudas (SELECT * equivalente) de un módulo, ya resueltas según el modo de datos. */
    private function obtenerFilasCrudasModulo(string $origen, int $anioPresupuestalId, string $vista, ?int $snapshotId, ?string $dependenciaFiltro): array
    {
        if ($vista === 'repositorio' && $snapshotId === null) {
            // "Repositorio" sin ningún snapshot guardado todavía: queda vacío, nunca cae de
            // vuelta a los datos en vivo (eso mostraría cifras reales con la etiqueta
            // "Repositorio" puesta encima, sin corresponder a ningún snapshot congelado).
            return [];
        }

        if ($vista === 'repositorio') {
            $filas = (new Snapshot())->obtenerTablaDeSnapshot($snapshotId, self::TABLAS_POR_ORIGEN[$origen]);
            $filas = array_values(array_filter(
                $filas,
                static fn (array $fila): bool => (int) ($fila['anio_presupuestal_id'] ?? 0) === $anioPresupuestalId
            ));

            if ($origen === 'gasto_principal') {
                $filas = array_values(array_filter(
                    $filas,
                    static fn (array $fila): bool => ($fila['tipo_automatico'] ?? null) !== 'techo_hijo'
                ));
            }
        } else {
            $filas = $this->obtenerModeloModulo($origen)->obtenerPorAnio($anioPresupuestalId);
        }

        if ($vista === 'usuario' && $dependenciaFiltro !== null) {
            $permitidas = $this->obtenerDependenciasPermitidas($dependenciaFiltro);
            $filas = array_values(array_filter(
                $filas,
                fn (array $fila): bool => in_array($this->obtenerDependenciaFilaModulo($origen, $fila), $permitidas, true)
            ));
        }

        return $filas;
    }

    /** Suma del valor de un módulo — usado por las pills del selector (sin joins). */
    private function obtenerTotalModulo(string $origen, int $anioPresupuestalId, string $vista, ?int $snapshotId, ?string $dependenciaFiltro): float
    {
        $total = 0.0;
        foreach ($this->obtenerFilasCrudasModulo($origen, $anioPresupuestalId, $vista, $snapshotId, $dependenciaFiltro) as $fila) {
            $total += $this->obtenerValorFilaModulo($origen, $fila);
        }

        return $total;
    }

    /**
     * Cada módulo guarda su valor en una columna distinta: los 4 de gasto usan 'valor_total',
     * Monitores usa 'valor' (numero_de_monitores * 2 * SMMLV, ver SolicitudMonitor::crear()) y
     * ARL no tiene una sola columna — se suman sus 5 niveles de riesgo.
     */
    private function obtenerValorFilaModulo(string $origen, array $fila): float
    {
        if ($origen === 'arl') {
            $total = 0.0;
            foreach (self::CLAVES_RIESGO_ARL as $clave) {
                $total += (float) ($fila[$clave] ?? 0);
            }

            return $total;
        }

        if ($origen === 'monitores') {
            return (float) ($fila['valor'] ?? 0);
        }

        return (float) ($fila['valor_total'] ?? 0);
    }

    /** ARL guarda la dependencia de origen como 'facultad', no 'dependencia' como los demás. */
    private function obtenerDependenciaFilaModulo(string $origen, array $fila): ?string
    {
        return $origen === 'arl' ? ($fila['facultad'] ?? null) : ($fila['dependencia'] ?? null);
    }

    private function obtenerFilasAnalisis(string $origen, int $anioPresupuestalId, string $vista, ?int $snapshotId, ?string $dependenciaFiltro): array
    {
        $filas = $this->obtenerFilasCrudasModulo($origen, $anioPresupuestalId, $vista, $snapshotId, $dependenciaFiltro);

        $itemsCrudos = [];
        $registrosPrecargados = [];

        foreach ($filas as $fila) {
            $id = (int) $fila['id'];
            $itemsCrudos[] = [
                'origen' => $origen,
                'origen_id' => $id,
                'tipo' => self::ETIQUETAS_ORIGEN[$origen],
                'detalle' => $this->obtenerDependenciaFilaModulo($origen, $fila) ?? '',
                'cantidad' => $fila['cantidad'] ?? null,
                'valor' => $this->obtenerValorFilaModulo($origen, $fila),
                'ruta_origen' => 'index.php?ruta=analisis&tab=analisis',
            ];
            $registrosPrecargados[$origen . ':' . $id] = $fila;
        }

        $peticiones = new PeticionesControlador();
        $peticiones->establecerRegistrosPrecargados($registrosPrecargados);
        $resultado = $peticiones->construirFilasPorOrigen($origen, $itemsCrudos, $anioPresupuestalId, 'analisis');

        if ($vista !== 'tiempo_real') {
            foreach ($resultado['filas'] as &$fila) {
                $fila['puede_editar'] = false;
            }
            unset($fila);
        }

        return $resultado;
    }

    private function obtenerModeloModulo(string $origen)
    {
        return match ($origen) {
            'gasto_principal' => new Gasto(),
            'gasto_extension' => new GastoExtension(),
            'ingreso_extension' => new IngresoExtension(),
            'gasto_postgrado' => new GastoPostgrado(),
            'ingreso_postgrado' => new IngresoPostgrado(),
            'gasto_unisalud' => new GastoUnisalud(),
            'ingreso_unisalud' => new IngresoUnisalud(),
            'monitores' => new SolicitudMonitor(),
            'arl' => new SolicitudArl(),
        };
    }

    /** La dependencia elegida en modo "Usuario", más todas sus descendientes. */
    private function obtenerDependenciasPermitidas(string $dependenciaNombre): array
    {
        $modeloDependencia = new Dependencia();
        $dependencia = $modeloDependencia->obtenerPorNombre($dependenciaNombre);

        if ($dependencia === null) {
            return [$dependenciaNombre];
        }

        $permitidas = [$dependencia['nombre']];
        foreach ($modeloDependencia->obtenerDescendientesPlano((int) $dependencia['id']) as $descendiente) {
            $permitidas[] = $descendiente['nombre'];
        }

        return $permitidas;
    }

    /**
     * Plantilla del presupuesto institucional (GET, botón "Plantilla" — solo Programación
     * presupuestal, Tiempo real): un archivo con 2 hojas ("Egresos"/"Ingresos"), prellenadas con
     * lo ya guardado en cada lado (ver PresupuestoInstitucional::obtenerArbolConValores()).
     */
    private function exportarPlantillaPresupuesto(): void
    {
        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? null;
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');
        $anioPorClave = $this->resolverAniosPorClave($anioVigenteNumero);
        $anios = array_values(array_unique(array_values($anioPorClave)));

        $encabezados = ['Código', 'Descripción', 'Proyecto(s) PDI', (string) $anioVigenteNumero, 'Año anterior (Final)', 'Año anterior (a corte)'];
        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $encabezados[] = (string) ($anioVigenteNumero - $desplazamiento);
        }

        $modeloPresupuesto = new PresupuestoInstitucional();
        $hojas = [];

        foreach (['egreso' => 'Egresos', 'ingreso' => 'Ingresos'] as $tipo => $nombreHoja) {
            $planas = $this->aplanarArbolPresupuesto($modeloPresupuesto->obtenerArbolConValores($tipo, $anios));

            $filas = [];
            foreach ($planas as $nodo) {
                $fila = [
                    $nodo['codigo'],
                    $nodo['etiqueta'],
                    $nodo['esTotal'] ? '' : ($nodo['proyectosPdiNits'] ?? ''),
                    $this->formatoNumeroExportar($nodo['valores'][$anioPorClave['vigente']] ?? 0.0),
                    $this->formatoNumeroExportar($nodo['valores'][$anioPorClave['anterior_total']] ?? 0.0),
                    $nodo['valorCorte'] !== null ? $this->formatoNumeroExportar($nodo['valorCorte']) : '',
                ];
                for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
                    $fila[] = $this->formatoNumeroExportar($nodo['valores'][$anioVigenteNumero - $desplazamiento] ?? 0.0);
                }
                $filas[] = $fila;
            }

            $hojas[] = ['nombre' => $nombreHoja, 'encabezados' => $encabezados, 'filas' => $filas];
        }

        GeneradorXlsx::descargarPlantillaMultihoja('presupuesto_institucional.xlsx', $hojas, [
            'plantilla' => 'presupuesto_institucional',
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'usuario_nombre' => $usuarioActual['nombre'] ?? '',
        ]);
        exit;
    }

    private function formatoNumeroExportar(float $valor): string
    {
        return number_format($valor, 2, '.', '');
    }

    /** Aplana el árbol (padre antes que sus hijos) para escribirlo fila por fila en la plantilla. */
    private function aplanarArbolPresupuesto(array $nodos): array
    {
        $resultado = [];
        foreach ($nodos as $nodo) {
            $resultado[] = $nodo;
            $resultado = array_merge($resultado, $this->aplanarArbolPresupuesto($nodo['hijos']));
        }

        return $resultado;
    }

    /**
     * Importa el presupuesto institucional desde el archivo subido (POST, botón "Importar" —
     * solo Programación presupuestal, Tiempo real). Todo o nada entre las 2 hojas: si cualquier
     * fila de Egresos o Ingresos falla una validación, no se guarda nada de ninguna de las dos.
     */
    private function importarPresupuestoInstitucional(): string
    {
        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            return 'Selecciona un archivo .xlsx válido para importar.';
        }

        $ruta = $_FILES['archivo']['tmp_name'];

        try {
            $metadatos = LectorXlsx::leerMetadatos($ruta);
        } catch (Throwable $excepcion) {
            return 'No se pudo leer el archivo: ' . $excepcion->getMessage();
        }

        if (($metadatos['SPPI_Origen'] ?? '') !== GeneradorXlsx::FIRMA_PLATAFORMA || ($metadatos['SPPI_Plantilla'] ?? '') !== 'presupuesto_institucional') {
            return 'Este archivo no parece haber sido descargado desde la plataforma. Usa el botón "Plantilla" para descargar una plantilla nueva y diligénciala sin quitarle sus metadatos.';
        }

        try {
            $filasEgresos = LectorXlsx::leerHojaPorNombre($ruta, 'Egresos');
            $filasIngresos = LectorXlsx::leerHojaPorNombre($ruta, 'Ingresos');
        } catch (Throwable $excepcion) {
            return 'No se pudo leer el archivo: ' . $excepcion->getMessage();
        }

        // Quita la fila de encabezado (fila 1) de cada hoja.
        array_shift($filasEgresos);
        array_shift($filasIngresos);

        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? null;
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');

        $errores = [];
        $lineasEgreso = $this->validarFilasPresupuesto('Egresos', $filasEgresos, true, $anioVigenteNumero, $errores);
        $lineasIngreso = $this->validarFilasPresupuesto('Ingresos', $filasIngresos, false, $anioVigenteNumero, $errores);

        if (!empty($errores)) {
            return "No se importó nada porque se encontraron errores:\n" . implode("\n", $errores);
        }

        if (empty($lineasEgreso) && empty($lineasIngreso)) {
            return 'No hay filas válidas para importar.';
        }

        try {
            (new PresupuestoInstitucional())->guardarLote($lineasIngreso, $lineasEgreso, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return 'No se pudo importar el archivo. Verifica los datos e inténtalo de nuevo.';
        }

        return '';
    }

    /**
     * Valida las filas de una hoja (Egresos o Ingresos) y las convierte a la forma que espera
     * PresupuestoInstitucional::guardarLote(). Acumula los errores en $errores (por referencia,
     * compartido entre las 2 hojas) en vez de devolverlos, para poder acumular los de ambas antes
     * de decidir si se guarda algo.
     */
    private function validarFilasPresupuesto(string $nombreHoja, array $filas, bool $permitirProyectos, int $anioVigenteNumero, array &$errores): array
    {
        $anioAnteriorNumero = $anioVigenteNumero - 1;
        $modeloProyecto = new Proyecto();
        $lineasValidas = [];

        // 6 columnas fijas (índices 0-5: Código, Descripción, Proyecto(s) PDI, vigente, Año
        // anterior Final, Año anterior a corte) — los históricos empiezan justo después, en el
        // índice 6, no en el 7 (antes de quitar "Fecha de corte" había 7 columnas fijas, pero el
        // desplazamiento seguía sumando como si hubiera 8 — un año histórico se leía siempre una
        // columna corrida hacia la derecha de la que en verdad le correspondía).
        $indicesHistoricos = [];
        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $indicesHistoricos[4 + $desplazamiento] = $anioVigenteNumero - $desplazamiento;
        }

        foreach ($filas as $indice => $fila) {
            $numeroFilaExcel = $indice + 2;

            $codigo = trim((string) ($fila[0] ?? ''));
            $descripcion = trim((string) ($fila[1] ?? ''));
            $proyectosTexto = trim((string) ($fila[2] ?? ''));

            if ($codigo === '' && $descripcion === '') {
                continue;
            }

            if (!preg_match('/^\d+(\.\d+)*$/', $codigo)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: código inválido \"$codigo\".";
                continue;
            }

            $esTotal = PresupuestoInstitucional::esCodigoTotal($codigo);
            $proyectosIds = [];
            $filaValida = true;

            if ($proyectosTexto !== '') {
                if (!$permitirProyectos) {
                    $errores[] = "$nombreHoja, fila $numeroFilaExcel: Ingresos no admite Proyecto(s) PDI.";
                    $filaValida = false;
                } elseif ($esTotal) {
                    $errores[] = "$nombreHoja, fila $numeroFilaExcel: una fila total (termina en \".0\") no puede tener Proyecto(s) PDI.";
                    $filaValida = false;
                } else {
                    foreach (explode(';', $proyectosTexto) as $nit) {
                        $nit = trim($nit);
                        if ($nit === '') {
                            continue;
                        }
                        $proyecto = $modeloProyecto->obtenerPorNit($nit);
                        if ($proyecto === null) {
                            $errores[] = "$nombreHoja, fila $numeroFilaExcel: no existe un proyecto con NIT \"$nit\".";
                            $filaValida = false;
                            continue;
                        }
                        $proyectosIds[] = (int) $proyecto['id'];
                    }
                }
            }

            $valores = [];

            $crudoVigente = trim((string) ($fila[3] ?? ''));
            if ($crudoVigente !== '' && !is_numeric($crudoVigente)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"$anioVigenteNumero\" no es un número válido.";
                $filaValida = false;
            }
            $valores[$anioVigenteNumero] = ['valor_final' => $crudoVigente !== '' && is_numeric($crudoVigente) ? (float) $crudoVigente : 0.0];

            $crudoAnteriorFinal = trim((string) ($fila[4] ?? ''));
            $crudoAnteriorCorte = trim((string) ($fila[5] ?? ''));

            if ($crudoAnteriorFinal !== '' && !is_numeric($crudoAnteriorFinal)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"Año anterior (Final)\" no es un número válido.";
                $filaValida = false;
            }
            if ($crudoAnteriorCorte !== '' && !is_numeric($crudoAnteriorCorte)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"Año anterior (a corte)\" no es un número válido.";
                $filaValida = false;
            }

            $valores[$anioAnteriorNumero] = [
                'valor_final' => $crudoAnteriorFinal !== '' && is_numeric($crudoAnteriorFinal) ? (float) $crudoAnteriorFinal : 0.0,
                'valor_corte' => $crudoAnteriorCorte !== '' && is_numeric($crudoAnteriorCorte) ? (float) $crudoAnteriorCorte : null,
            ];

            foreach ($indicesHistoricos as $indiceColumna => $anioHistorico) {
                $crudo = trim((string) ($fila[$indiceColumna] ?? ''));
                if ($crudo !== '' && !is_numeric($crudo)) {
                    $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"$anioHistorico\" no es un número válido.";
                    $filaValida = false;
                }
                $valores[$anioHistorico] = ['valor_final' => $crudo !== '' && is_numeric($crudo) ? (float) $crudo : 0.0];
            }

            if (!$filaValida) {
                continue;
            }

            $lineasValidas[] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'proyectos_ids' => $proyectosIds,
                'valores' => $valores,
            ];
        }

        return $lineasValidas;
    }

    /**
     * Plantilla de "Proyectos" (GET, botón "Plantilla" — solo Tiempo real): una sola hoja
     * "Proyectos", sin columna Proyecto(s) PDI (esta pestaña no se asocia a la tabla `proyectos`
     * real) ni Fecha de corte (esa es siempre de la página, ver renderizarArbol()).
     */
    private function exportarPlantillaProyectos(): void
    {
        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? null;
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');
        $anioPorClave = $this->resolverAniosPorClave($anioVigenteNumero);
        $anios = array_values(array_unique(array_values($anioPorClave)));

        $encabezados = ['Código', 'Descripción', (string) $anioVigenteNumero, 'Año anterior (Final)', 'Año anterior (a corte)'];
        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $encabezados[] = (string) ($anioVigenteNumero - $desplazamiento);
        }

        $planas = $this->aplanarArbolPresupuesto((new PresupuestoInstitucional())->obtenerArbolConValores('proyecto', $anios));

        $filas = [];
        foreach ($planas as $nodo) {
            $fila = [
                $nodo['codigo'],
                $nodo['etiqueta'],
                $this->formatoNumeroExportar($nodo['valores'][$anioPorClave['vigente']] ?? 0.0),
                $this->formatoNumeroExportar($nodo['valores'][$anioPorClave['anterior_total']] ?? 0.0),
                $nodo['valorCorte'] !== null ? $this->formatoNumeroExportar($nodo['valorCorte']) : '',
            ];
            for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
                $fila[] = $this->formatoNumeroExportar($nodo['valores'][$anioVigenteNumero - $desplazamiento] ?? 0.0);
            }
            $filas[] = $fila;
        }

        GeneradorXlsx::descargarPlantillaMultihoja('proyectos.xlsx', [
            ['nombre' => 'Proyectos', 'encabezados' => $encabezados, 'filas' => $filas],
        ], [
            'plantilla' => 'presupuesto_proyectos',
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'usuario_nombre' => $usuarioActual['nombre'] ?? '',
        ]);
        exit;
    }

    /**
     * Importa "Proyectos" desde el archivo subido (POST, botón "Importar" — solo Tiempo real).
     * Una sola hoja, todo o nada — mismo patrón que importarPresupuestoInstitucional() pero sin
     * la pareja Egresos/Ingresos.
     */
    private function importarPresupuestoProyectos(): string
    {
        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            return 'Selecciona un archivo .xlsx válido para importar.';
        }

        $ruta = $_FILES['archivo']['tmp_name'];

        try {
            $metadatos = LectorXlsx::leerMetadatos($ruta);
        } catch (Throwable $excepcion) {
            return 'No se pudo leer el archivo: ' . $excepcion->getMessage();
        }

        if (($metadatos['SPPI_Origen'] ?? '') !== GeneradorXlsx::FIRMA_PLATAFORMA || ($metadatos['SPPI_Plantilla'] ?? '') !== 'presupuesto_proyectos') {
            return 'Este archivo no parece haber sido descargado desde la plataforma. Usa el botón "Plantilla" para descargar una plantilla nueva y diligénciala sin quitarle sus metadatos.';
        }

        try {
            $filas = LectorXlsx::leerHojaPorNombre($ruta, 'Proyectos');
        } catch (Throwable $excepcion) {
            return 'No se pudo leer el archivo: ' . $excepcion->getMessage();
        }

        array_shift($filas);

        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? null;
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');

        $errores = [];
        $lineas = $this->validarFilasProyectos($filas, $anioVigenteNumero, $errores);

        if (!empty($errores)) {
            return "No se importó nada porque se encontraron errores:\n" . implode("\n", $errores);
        }

        if (empty($lineas)) {
            return 'No hay filas válidas para importar.';
        }

        try {
            (new PresupuestoInstitucional())->guardarLoteUnico('proyecto', $lineas, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return 'No se pudo importar el archivo. Verifica los datos e inténtalo de nuevo.';
        }

        return '';
    }

    /**
     * Igual espíritu que validarFilasPresupuesto(), pero para la hoja única de "Proyectos": 5
     * columnas fijas (índices 0-4: Código, Descripción, vigente, Año anterior Final, Año
     * anterior a corte — sin Proyecto(s) PDI), históricos en los índices 3+$desplazamiento
     * (5,6,7,8) — verificado a mano contra exportarPlantillaProyectos().
     */
    private function validarFilasProyectos(array $filas, int $anioVigenteNumero, array &$errores): array
    {
        $anioAnteriorNumero = $anioVigenteNumero - 1;
        $lineasValidas = [];

        $indicesHistoricos = [];
        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $indicesHistoricos[3 + $desplazamiento] = $anioVigenteNumero - $desplazamiento;
        }

        foreach ($filas as $indice => $fila) {
            $numeroFilaExcel = $indice + 2;

            $codigo = trim((string) ($fila[0] ?? ''));
            $descripcion = trim((string) ($fila[1] ?? ''));

            if ($codigo === '' && $descripcion === '') {
                continue;
            }

            if (!preg_match('/^\d+(\.\d+)*$/', $codigo)) {
                $errores[] = "Proyectos, fila $numeroFilaExcel: código inválido \"$codigo\".";
                continue;
            }

            $filaValida = true;
            $valores = [];

            $crudoVigente = trim((string) ($fila[2] ?? ''));
            if ($crudoVigente !== '' && !is_numeric($crudoVigente)) {
                $errores[] = "Proyectos, fila $numeroFilaExcel: \"$anioVigenteNumero\" no es un número válido.";
                $filaValida = false;
            }
            $valores[$anioVigenteNumero] = ['valor_final' => $crudoVigente !== '' && is_numeric($crudoVigente) ? (float) $crudoVigente : 0.0];

            $crudoAnteriorFinal = trim((string) ($fila[3] ?? ''));
            $crudoAnteriorCorte = trim((string) ($fila[4] ?? ''));

            if ($crudoAnteriorFinal !== '' && !is_numeric($crudoAnteriorFinal)) {
                $errores[] = "Proyectos, fila $numeroFilaExcel: \"Año anterior (Final)\" no es un número válido.";
                $filaValida = false;
            }
            if ($crudoAnteriorCorte !== '' && !is_numeric($crudoAnteriorCorte)) {
                $errores[] = "Proyectos, fila $numeroFilaExcel: \"Año anterior (a corte)\" no es un número válido.";
                $filaValida = false;
            }

            $valores[$anioAnteriorNumero] = [
                'valor_final' => $crudoAnteriorFinal !== '' && is_numeric($crudoAnteriorFinal) ? (float) $crudoAnteriorFinal : 0.0,
                'valor_corte' => $crudoAnteriorCorte !== '' && is_numeric($crudoAnteriorCorte) ? (float) $crudoAnteriorCorte : null,
            ];

            foreach ($indicesHistoricos as $indiceColumna => $anioHistorico) {
                $crudo = trim((string) ($fila[$indiceColumna] ?? ''));
                if ($crudo !== '' && !is_numeric($crudo)) {
                    $errores[] = "Proyectos, fila $numeroFilaExcel: \"$anioHistorico\" no es un número válido.";
                    $filaValida = false;
                }
                $valores[$anioHistorico] = ['valor_final' => $crudo !== '' && is_numeric($crudo) ? (float) $crudo : 0.0];
            }

            if (!$filaValida) {
                continue;
            }

            $lineasValidas[] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'proyectos_ids' => [],
                'valores' => $valores,
            ];
        }

        return $lineasValidas;
    }
}
