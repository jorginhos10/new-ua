<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/GastoExtension.php';
require_once __DIR__ . '/../modelo/GastoPostgrado.php';
require_once __DIR__ . '/../modelo/GastoUnisalud.php';
require_once __DIR__ . '/../modelo/SolicitudMonitor.php';
require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/Snapshot.php';
require_once __DIR__ . '/../modelo/VersionArbol.php';
require_once __DIR__ . '/../modelo/FuenteDatosAnalisis.php';
require_once __DIR__ . '/../modelo/PresupuestoInstitucional.php';
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
    private const TABS_VALIDAS = ['pdi', 'programacion', 'analisis', 'proyectos'];

    private const VISTAS_VALIDAS = ['tiempo_real', 'repositorio', 'usuario'];

    private const ORIGENES_ANALISIS = ['gasto_principal', 'gasto_extension', 'gasto_postgrado', 'gasto_unisalud', 'monitores', 'arl'];

    private const TABLAS_POR_ORIGEN = [
        'gasto_principal' => 'gastos',
        'gasto_extension' => 'gastos_extension',
        'gasto_postgrado' => 'gastos_postgrado',
        'gasto_unisalud' => 'gastos_unisalud',
        'monitores' => 'solicitudes_monitores',
        'arl' => 'solicitudes_arl',
    ];

    private const ETIQUETAS_ORIGEN = [
        'gasto_principal' => 'Gasto',
        'gasto_extension' => 'Extensión',
        'gasto_postgrado' => 'Postgrado',
        'gasto_unisalud' => 'Unisalud',
        'monitores' => 'Monitores',
        'arl' => 'ARL',
    ];

    /** Riesgos ARL 1-5: sin una sola columna 'valor_total', se suman aquí para pills/gráficas. */
    private const CLAVES_RIESGO_ARL = ['riesgo1_valor', 'riesgo2_valor', 'riesgo3_valor', 'riesgo4_valor', 'riesgo5_valor'];

    public function index(): void
    {
        $this->verificarAcceso();

        $tab = in_array($_GET['tab'] ?? '', self::TABS_VALIDAS, true) ? $_GET['tab'] : 'pdi';
        $vista = in_array($_GET['vista'] ?? '', self::VISTAS_VALIDAS, true) ? $_GET['vista'] : 'tiempo_real';
        $pestanaArbol = $tab === 'programacion' ? 'programacion_presupuestal' : 'articulacion_pdi';
        $lado = ($_GET['lado'] ?? '') === 'ingresos' ? 'ingresos' : 'egresos';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarPost($tab, $vista, $pestanaArbol, $lado);

            return;
        }

        if ($tab === 'programacion' && $vista === 'tiempo_real' && ($_GET['accion'] ?? '') === 'exportar_plantilla_presupuesto') {
            $this->exportarPlantillaPresupuesto();

            return;
        }

        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioActivo = $aniosActivos[0] ?? null;
        $dependenciasTodas = (new Dependencia())->obtenerTodas();

        // Compartidas por las 4 pestañas — ver vista/analisis/index.php.
        $rolVista = $vista === 'tiempo_real' ? 'admin' : 'consulta';

        if ($tab === 'pdi' || $tab === 'programacion') {
            $this->renderizarArbol($tab, $vista, $pestanaArbol, $rolVista, $dependenciasTodas, $lado);

            return;
        }

        if ($tab === 'analisis') {
            $this->renderizarAnalisis($vista, $rolVista, $anioActivo, $dependenciasTodas);

            return;
        }

        $this->renderizarProyectos($tab, $vista);
    }

    private function verificarAcceso(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if (($_SESSION['usuario_rol'] ?? '') !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependencia = !empty($usuarioActual['dependencia_id'])
            ? (new Dependencia())->obtenerPorId((int) $usuarioActual['dependencia_id'])
            : null;

        $esDependenciaSuperadmin = $dependencia !== null && !empty($dependencia['es_raiz_superadmin']);

        if (!$esDependenciaSuperadmin) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }

    private function procesarPost(string $tab, string $vista, string $pestanaArbol, string $lado): void
    {
        $error = '';
        $exito = '';

        if ($tab === 'analisis' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'eliminar_celda') {
            $error = $this->procesarEliminarModulo((string) ($_GET['origen'] ?? ''));
        } elseif (in_array($tab, ['pdi', 'programacion'], true) && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'guardar_version') {
            $this->procesarGuardarVersion($pestanaArbol);
            $exito = 'Versión guardada correctamente.';
        } elseif ($tab === 'programacion' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'importar_presupuesto') {
            $error = $this->importarPresupuestoInstitucional();
            $exito = $error === '' ? 'Presupuesto institucional importado correctamente.' : '';
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
        $modelo->eliminar($id);

        return '';
    }

    private function procesarGuardarVersion(string $pestana): void
    {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            $etiquetaPestana = $pestana === 'programacion_presupuestal' ? 'Programación presupuestal' : 'Articulación PDI';
            $nombre = $etiquetaPestana . ' — ' . date('d/m/Y H:i');
        }

        (new VersionArbol())->crear($pestana, $nombre, (int) $_SESSION['usuario_id']);
    }

    /**
     * Pestañas 1 y 2 (Articulación PDI / Programación presupuestal). Articulación PDI sigue
     * siendo el árbol Línea > Motor > Proyecto de siempre (portado de vista/dev/pruebas/arbol.php,
     * ver construirArbolPdi()); Programación presupuestal ahora muestra el presupuesto
     * institucional (código jerárquico propio, dos lados Egresos/Ingresos — ver
     * construirArbolPresupuesto()) en su lugar. Ambos comparten la cáscara (franja superior,
     * toggle Tiempo real/Repositorio/Usuario, columnas de año) — ver
     * vista/analisis/parciales/arbol.php, que solo conoce id/etiqueta/nivel/valores/hijos y no
     * sabe cuál de los dos árboles está dibujando.
     */
    private function renderizarArbol(string $tab, string $vista, string $pestanaArbol, string $rolVista, array $dependenciasTodas, string $lado): void
    {
        $modeloVersion = new VersionArbol();
        $versiones = $modeloVersion->obtenerTodos($pestanaArbol);

        $versionIdActual = null;
        $dependenciaFiltroActual = null;

        // Si "Repositorio" está activo pero esta pestaña todavía no tiene ninguna versión
        // guardada, el árbol debe quedar vacío — sin esto, FuenteDatosAnalisis (versionId=null)
        // caería de vuelta a los datos en vivo y los mostraría con la etiqueta "Repositorio"
        // puesta encima, dando cifras que no corresponden a ninguna versión congelada. Solo
        // aplica al árbol PDI (VersionArbol) — el presupuesto institucional no se congela (fuera
        // de alcance) y se ve igual en los 3 modos.
        $sinDatosRepositorio = false;

        if ($vista === 'repositorio') {
            $versionIdActual = (int) ($_GET['version_id'] ?? ($versiones[0]['id'] ?? 0));
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

        $modoColumnas = $tab === 'programacion' ? 'completo' : 'estructura';
        $tipoPresupuesto = $lado === 'ingresos' ? 'ingreso' : 'egreso';

        if ($tab === 'programacion') {
            // La fecha de corte ya no se elige con un selector: es la que quedó guardada al
            // importar (ver vista/analisis/parciales/arbol.php, sin el <input type="date"> de PDI).
            $fechaCorteGuardada = (new PresupuestoInstitucional())->obtenerFechaCorteReferencia($tipoPresupuesto, $anioAnteriorNumero);
            $fechaCorte = $fechaCorteGuardada ?? ($anioAnteriorNumero . date('-m-d'));
        } else {
            $fechaCorteDefecto = $anioAnteriorNumero . date('-m-d');
            $fechaCorte = (string) ($_GET['corte'] ?? $fechaCorteDefecto);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCorte)) {
                $fechaCorte = $fechaCorteDefecto;
            }
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
        if ($tab === 'programacion') {
            [$arbolDatos, $totalesGenerales] = $this->construirArbolPresupuesto($tipoPresupuesto, $clavesColumnas, $anioVigenteNumero);
            $columnasExtra = [
                ['clave' => 'codigo', 'etiqueta' => 'Código'],
                ['clave' => 'proyectosPdi', 'etiqueta' => 'Proyecto(s) PDI'],
            ];
            $etiquetaColumnaArbol = 'Descripción';
        } else {
            $fuenteDatos = new FuenteDatosAnalisis($versionIdActual, $dependenciaFiltroActual);
            [$arbolDatos, $totalesGenerales] = $this->construirArbolPdi($fuenteDatos, $columnasAnios, $clavesColumnas, $sinDatosRepositorio);
        }

        $error = $_SESSION['analisis_flash_error'] ?? '';
        $exito = $_SESSION['analisis_flash_exito'] ?? '';
        unset($_SESSION['analisis_flash_error'], $_SESSION['analisis_flash_exito']);

        $tituloPagina = 'Análisis · ' . ($tab === 'programacion' ? 'Programación presupuestal' : 'Articulación PDI');
        if ($tab === 'programacion') {
            $tituloPagina .= ' ' . $anioVigenteNumero;
        }

        require __DIR__ . '/../vista/analisis/index.php';
    }

    /** Árbol Línea > Motor > Proyecto de siempre — portado de vista/dev/pruebas/arbol.php. */
    private function construirArbolPdi(FuenteDatosAnalisis $fuenteDatos, array $columnasAnios, array $clavesColumnas, bool $sinDatosRepositorio): array
    {
        $totalesPorColumna = [];
        foreach ($columnasAnios as $columna) {
            $totalesPorColumna[$columna['clave']] = $this->totalesPorProyecto($columna['registro'], $columna['corte'], $fuenteDatos);
        }

        // El presupuesto institucional (líneas de Egresos con Proyecto(s) PDI) solo aporta al año
        // VIGENTE — Articulación PDI no muestra otras columnas de todas formas.
        $columnaVigente = $columnasAnios[0];
        if ($columnaVigente['registro'] !== null) {
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

                    $nodoMotor['hijos'][] = [
                        'id' => 'proyecto-' . $proyecto['id'],
                        'etiqueta' => $proyecto['codigo'] . ' - ' . $proyecto['nombre'],
                        'nivel' => 2,
                        'valores' => $valoresProyecto,
                        'hijos' => [],
                    ];
                }

                foreach ($clavesColumnas as $clave) {
                    $nodoLinea['valores'][$clave] += $nodoMotor['valores'][$clave];
                }
                $nodoLinea['hijos'][] = $nodoMotor;
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
     * Árbol del presupuesto institucional (código jerárquico propio) para Programación
     * presupuestal — reemplaza ahí a Línea/Motor/Proyecto. $tipo ya es 'ingreso'/'egreso' (no
     * 'ingresos'/'egresos' — esa es la forma de $_GET['lado'], ver resolverAniosPorClave()).
     */
    private function construirArbolPresupuesto(string $tipo, array $clavesColumnas, int $anioVigenteNumero): array
    {
        $anioPorClave = $this->resolverAniosPorClave($anioVigenteNumero);
        $anios = array_values(array_unique(array_values($anioPorClave)));

        $arbolCrudo = (new PresupuestoInstitucional())->obtenerArbolConValores($tipo, $anios);
        $arbolDatos = $this->remapArbolPresupuesto($arbolCrudo, $anioPorClave);

        $totalesGenerales = array_fill_keys($clavesColumnas, 0.0);
        foreach ($arbolDatos as $nodoRaiz) {
            foreach ($clavesColumnas as $clave) {
                $totalesGenerales[$clave] += $nodoRaiz['valores'][$clave] ?? 0.0;
            }
        }

        return [$arbolDatos, $totalesGenerales];
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
     * Igual que vista/dev/pruebas/arbol.php::totalesPorProyectoArbol(), vía FuenteDatosAnalisis —
     * suma gasto_principal Y los 3 gastos de autogestión (antes solo sumaba gasto_principal).
     */
    private function totalesPorProyecto(?array $registroAnio, ?string $fechaCorte, FuenteDatosAnalisis $fuenteDatos): array
    {
        if ($registroAnio === null) {
            return [];
        }

        $anioId = (int) $registroAnio['id'];
        $gastos = array_merge(
            $fuenteDatos->obtenerGastosPorAnio($anioId),
            $fuenteDatos->obtenerGastosAutogestionPorAnio($anioId)
        );
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

        $modeloSnapshot = new Snapshot();
        $snapshots = $modeloSnapshot->obtenerTodos();

        $snapshotIdActual = null;
        $dependenciaFiltroActual = null;

        if ($vista === 'repositorio') {
            $snapshotIdActual = (int) ($_GET['snapshot_id'] ?? ($snapshots[0]['id'] ?? 0));
            if ($snapshotIdActual <= 0) {
                $snapshotIdActual = null;
            }
        } elseif ($vista === 'usuario') {
            $dependenciaFiltroActual = trim((string) ($_GET['dependencia'] ?? ''));
            if ($dependenciaFiltroActual === '' && !empty($dependenciasTodas)) {
                $dependenciaFiltroActual = $dependenciasTodas[0]['nombre'];
            }
        }

        $error = $_SESSION['analisis_flash_error'] ?? '';
        unset($_SESSION['analisis_flash_error']);

        $totalesPorModulo = [];
        foreach (self::ORIGENES_ANALISIS as $origenModulo) {
            $totalesPorModulo[$origenModulo] = $anioPresupuestalId > 0
                ? $this->obtenerTotalModulo($origenModulo, $anioPresupuestalId, $vista, $snapshotIdActual, $dependenciaFiltroActual)
                : 0.0;
        }

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

        $tituloPagina = 'Análisis · Análisis de distribución — ' . self::ETIQUETAS_ORIGEN[$origenActivo];
        $tab = 'analisis';

        require __DIR__ . '/../vista/analisis/index.php';
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
            'gasto_postgrado' => new GastoPostgrado(),
            'gasto_unisalud' => new GastoUnisalud(),
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

        $encabezados = ['Código', 'Descripción', 'Proyecto(s) PDI', (string) $anioVigenteNumero, 'Año anterior (Final)', 'Año anterior (a corte)', 'Fecha de corte'];
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
                    $nodo['fechaCorte'] ?? '',
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

        $indicesHistoricos = [];
        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $indicesHistoricos[6 + $desplazamiento] = $anioVigenteNumero - $desplazamiento;
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
            $fechaCorte = trim((string) ($fila[6] ?? ''));

            if ($crudoAnteriorFinal !== '' && !is_numeric($crudoAnteriorFinal)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"Año anterior (Final)\" no es un número válido.";
                $filaValida = false;
            }
            if ($crudoAnteriorCorte !== '' && !is_numeric($crudoAnteriorCorte)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"Año anterior (a corte)\" no es un número válido.";
                $filaValida = false;
            }
            if ($fechaCorte !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCorte)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"Fecha de corte\" debe tener formato AAAA-MM-DD.";
                $filaValida = false;
            }

            $valores[$anioAnteriorNumero] = [
                'valor_final' => $crudoAnteriorFinal !== '' && is_numeric($crudoAnteriorFinal) ? (float) $crudoAnteriorFinal : 0.0,
                'valor_corte' => $crudoAnteriorCorte !== '' && is_numeric($crudoAnteriorCorte) ? (float) $crudoAnteriorCorte : null,
                'fecha_corte' => $fechaCorte !== '' ? $fechaCorte : null,
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

    /** Pestaña 4: en blanco "hasta nuevo aviso" (instrucción explícita del usuario). */
    private function renderizarProyectos(string $tab, string $vista): void
    {
        $tituloPagina = 'Análisis · Proyectos';

        require __DIR__ . '/../vista/analisis/index.php';
    }
}
