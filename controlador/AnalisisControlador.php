<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/AccesoAnalisis.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Linea.php';
require_once __DIR__ . '/../modelo/Motor.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/AutogestionItem.php';
require_once __DIR__ . '/../modelo/GastoExtension.php';
require_once __DIR__ . '/../modelo/GastoPostgrado.php';
require_once __DIR__ . '/../modelo/IngresoExtension.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/GastoUnisalud.php';
require_once __DIR__ . '/../modelo/SolicitudMonitor.php';
require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/Snapshot.php';
require_once __DIR__ . '/../modelo/GastoFijo.php';
require_once __DIR__ . '/../modelo/EdicionMasivaProyecto.php';
require_once __DIR__ . '/../modelo/VersionArbol.php';
require_once __DIR__ . '/../modelo/FuenteDatosAnalisis.php';
require_once __DIR__ . '/../modelo/PresupuestoInstitucional.php';
require_once __DIR__ . '/../modelo/AnalisisArbolConfiguracion.php';
require_once __DIR__ . '/../modelo/AnalisisPdiAjuste.php';
require_once __DIR__ . '/../modelo/TechosMetas.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/IngresoUnisalud.php';
require_once __DIR__ . '/../modelo/Acta.php';
require_once __DIR__ . '/../modelo/AnalisisPresentacion.php';
require_once __DIR__ . '/../modelo/MensajeGlobal.php';
require_once __DIR__ . '/../modelo/VersionTechos.php';
require_once __DIR__ . '/../modelo/VersionDocumentos.php';
require_once __DIR__ . '/../modelo/GeneradorXlsx.php';
require_once __DIR__ . '/../modelo/LectorXlsx.php';
require_once __DIR__ . '/../modelo/PeticionArchivada.php';
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
    private const TABS_VALIDAS = ['pdi', 'programacion', 'comparativo', 'analisis', 'proyectos', 'techos', 'actas', 'presentacion'];

    /** Pestaña Actas: solo dependencias de tipo Facultad, menos estas (tipo Facultad pero no son facultades). */
    private const DEPENDENCIAS_EXCLUIDAS_ACTAS = ['DIRECTIVAS'];

    private const CARPETA_ACTAS = __DIR__ . '/../almacenamiento/actas/';

    private const VISTAS_VALIDAS = ['tiempo_real', 'repositorio', 'usuario'];

    private const ORIGENES_ANALISIS = ['gasto_principal', 'gasto_extension', 'ingreso_extension', 'gasto_postgrado', 'ingreso_postgrado', 'gasto_unisalud', 'ingreso_unisalud', 'monitores', 'arl'];

    /** Egresos de Autogestión que pertenecen a un ítem de autogestion_items => su `modulo`. */
    private const MODULO_AUTOGESTION_POR_EGRESO = [
        'gasto_extension' => 'extension',
        'gasto_postgrado' => 'postgrado',
    ];

    /**
     * Módulos de Autogestión con ingresos y egresos separados: una sola tarjeta en el selector
     * (con ambos totales) y pestañas Egresos/Ingresos sobre la tabla — mismo par que usa
     * PeticionesControlador::PARES_GASTO_INGRESO para peticiones-tipo-detalle.
     */
    private const PARES_GASTO_INGRESO = [
        'gasto_extension' => 'ingreso_extension',
        'gasto_postgrado' => 'ingreso_postgrado',
        'gasto_unisalud' => 'ingreso_unisalud',
    ];

    /**
     * Egresos de tipo "gasto" de Extensión/Postgrado que también se cargan en el landing de Gastos
     * (ver obtenerGastosAutogestionTipoGasto()). En esa tabla su id lleva este desplazamiento para no
     * chocar con los ids de `gastos` (los gastos fijos usan ids negativos) — ver decodificarIdTablaReal().
     */
    private const DESPLAZAMIENTO_ID_GASTO_AUTOGESTION = [
        'gasto_extension' => 1000000000,
        'gasto_postgrado' => 2000000000,
    ];

    /** Columna "Año anterior (a corte)" del árbol: oculta por ahora (true la vuelve a mostrar). */
    private const MOSTRAR_COLUMNA_CORTE = false;

    private const TABLAS_POR_ORIGEN = [
        'gasto_principal' => 'gastos',
        'gasto_extension' => 'gastos_extension',
        'ingreso_extension' => 'ingresos_extension',
        'gasto_postgrado' => 'gastos_postgrado',
        'ingreso_postgrado' => 'ingresos_postgrado',
        'gasto_unisalud' => 'gastos_unisalud',
        'ingreso_unisalud' => 'ingresos_unisalud',
        'monitores' => 'solicitudes_monitores',
        'arl' => 'solicitudes_arl',
    ];

    private const ETIQUETAS_ORIGEN = [
        'gasto_principal' => 'Gastos',
        'gasto_extension' => 'Extensión',
        'ingreso_extension' => 'Extensión',
        'gasto_postgrado' => 'Postgrado',
        'ingreso_postgrado' => 'Postgrado',
        'gasto_unisalud' => 'Unisalud',
        'ingreso_unisalud' => 'Unisalud',
        'monitores' => 'Monitores',
        'arl' => 'ARL',
    ];

    /** Riesgos ARL 1-5: sin una sola columna 'valor_total', se suman aquí para pills/gráficas. */
    private const CLAVES_RIESGO_ARL = ['riesgo1_valor', 'riesgo2_valor', 'riesgo3_valor', 'riesgo4_valor', 'riesgo5_valor'];

    /** Cache de una sola petición HTTP (ver obtenerIdsArchivadosOExpediente()) — evita repetir la
     * misma consulta a peticiones_archivadas por cada uno de los 6 módulos del selector. */
    private ?array $cacheAccionesPorClave = null;

    public function index(): void
    {
        $acceso = $this->resolverAcceso();
        AccesoAnalisis::establecer($acceso);

        if ($acceso['clase'] !== 'superadmin') {
            $this->aplicarRestriccionesNoSuperadmin($acceso);
        }

        $tab =in_array($_GET['tab'] ?? '', self::TABS_VALIDAS, true) ? $_GET['tab'] : 'presentacion';
        $vista = in_array($_GET['vista'] ?? '', self::VISTAS_VALIDAS, true) ? $_GET['vista'] : 'tiempo_real';
        $pestanaArbol = match ($tab) {
            'programacion' => 'programacion_presupuestal',
            'comparativo' => 'comparativo_vigencia',
            default => 'articulacion_pdi',
        };
        $lado = ($_GET['lado'] ?? '') === 'egresos' ? 'egresos' : 'ingresos';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'configurar_expansion_arbol') {
            $this->guardarExpansionArbol($tab);

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_codigo_ingresos_pp') {
            $this->guardarCodigoIngresosPp($tab);

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_fecha_corte') {
            $this->guardarFechaCorte($tab, $vista);

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_ajuste_pdi') {
            $this->guardarAjustePdi($tab, $vista);

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['accion'] ?? '', ['editar_valor_arbol', 'renombrar_linea_arbol'], true)) {
            $this->editarArbolPresupuesto($tab, $vista, $lado);

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

        if ($tab === 'presentacion') {
            $this->renderizarPresentacion($vista, $acceso);

            return;
        }

        // Compartidas por las 4 pestañas — ver vista/analisis/index.php.
        $rolVista = $vista === 'tiempo_real' ? 'admin' : 'consulta';

        if ($tab === 'techos') {
            $this->renderizarTechosMetas($vista, $dependenciasTodas);

            return;
        }

        if (in_array($tab, ['pdi', 'programacion', 'comparativo', 'proyectos'], true)) {
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

    /** Nombre por defecto del snapshot de cada pestaña (el SA lo puede cambiar al crearlo). */
    public static function etiquetaSnapshotPestana(string $tab, string $lado): ?string
    {
        return match ($tab) {
            'presentacion' => 'Documentos',
            'programacion' => 'Programación presupuestal — ' . ($lado === 'egresos' ? 'Egresos' : 'Ingresos'),
            'proyectos' => 'Proyectos de Inversión',
            'pdi' => 'Articulación PDI',
            'techos' => 'Techos y Metas',
            'analisis' => 'Análisis de distribución',
            default => null,
        };
    }

    /**
     * Páginas que se congelan con "Todas las páginas" del modal de snapshot: [pestaña, lado]. El
     * Comparativo vigencia inicial usa las mismas versiones de PP, así que no tiene snapshot propio.
     */
    private const PAGINAS_SNAPSHOT = [
        ['presentacion', 'ingresos'],
        ['programacion', 'ingresos'],
        ['programacion', 'egresos'],
        ['proyectos', 'ingresos'],
        ['pdi', 'ingresos'],
        ['techos', 'ingresos'],
        ['analisis', 'ingresos'],
    ];

    /**
     * Botón de cámara de la franja superior (solo SA, solo Tiempo real): el modal pregunta si se
     * congela solo la página actual ('alcance' = 'pagina') o todas ('todas'), con el mismo mecanismo de
     * versiones de cada pestaña — así aparece luego en su selector de Repositorio. Cada snapshot creado
     * queda como el ACTIVO de su pestaña (el que se ve por defecto en Repositorio).
     */
    private function crearSnapshotPestana(string $tab, string $lado): array
    {
        if (!$this->esSuperAdmin()) {
            return ['Solo el SA puede crear snapshots.', ''];
        }

        $todas = ($_POST['alcance'] ?? '') === 'todas';
        $paginas = $todas ? self::PAGINAS_SNAPSHOT : [[$tab, $lado]];
        if (!$todas && self::etiquetaSnapshotPestana($tab, $lado) === null) {
            return ['Esta pestaña no tiene snapshots.', ''];
        }

        $nombreBase = mb_substr(trim((string) ($_POST['nombre'] ?? '')), 0, 120);
        $usuarioId = (int) $_SESSION['usuario_id'];
        $creados = [];
        $errores = [];

        foreach ($paginas as [$tabPagina, $ladoPagina]) {
            $etiqueta = (string) self::etiquetaSnapshotPestana($tabPagina, $ladoPagina);
            $nombre = $nombreBase === '' ? $etiqueta . ' ' . date('Y-m-d H:i:s') : ($todas ? $nombreBase . ' — ' . $etiqueta : $nombreBase);
            $nombre = mb_substr($nombre, 0, 150);

            try {
                $this->crearYActivarSnapshot($tabPagina, $ladoPagina, $nombre, $usuarioId);
                $creados[] = $etiqueta;
            } catch (Throwable $excepcion) {
                $errores[] = $etiqueta . ': ' . $excepcion->getMessage();
            }
        }

        if (empty($creados)) {
            return ['No se pudo crear el snapshot. ' . implode(' · ', $errores), ''];
        }

        $exito = count($creados) === 1
            ? 'Snapshot de ' . $creados[0] . ' creado y marcado como activo en Repositorio.'
            : count($creados) . ' snapshots creados y marcados como activos en Repositorio (' . implode(', ', $creados) . ').';

        return empty($errores) ? ['', $exito] : [$exito . ' Pero algunos no se pudieron crear: ' . implode(' · ', $errores), ''];
    }

    /** Crea la versión de una página y la deja como la activa de esa pestaña (lado, en PP). */
    private function crearYActivarSnapshot(string $tab, string $lado, string $nombre, int $usuarioId): void
    {
        $tipoPresupuesto = $lado === 'egresos' ? 'egreso' : 'ingreso';

        match ($tab) {
            'presentacion' => (function () use ($nombre, $usuarioId): void {
                $modelo = new VersionDocumentos();
                $modelo->marcarActiva($modelo->crear($nombre, $usuarioId));
            })(),
            'programacion', 'proyectos' => (function () use ($tab, $tipoPresupuesto, $nombre, $usuarioId): void {
                $modelo = new PresupuestoInstitucional();
                $tipo = $tab === 'proyectos' ? 'proyecto' : $tipoPresupuesto;
                $modelo->marcarVersionActiva($modelo->crearVersion($tipo, $nombre, $usuarioId), $tipo);
            })(),
            'pdi' => (function () use ($nombre, $usuarioId): void {
                $modelo = new VersionArbol();
                $modelo->marcarActiva($modelo->crear('articulacion_pdi', $nombre, $usuarioId), 'articulacion_pdi');
            })(),
            'techos' => (function () use ($nombre, $usuarioId): void {
                $modelo = new VersionTechos();
                $modelo->marcarActiva($modelo->crear($nombre, $usuarioId));
            })(),
            'analisis' => (function () use ($nombre, $usuarioId): void {
                $modelo = new Snapshot();
                $modelo->marcarActiva($modelo->crear($nombre, $usuarioId));
            })(),
        };
    }

    /**
     * POST por fetch desde el doble clic del árbol de Programación presupuestal / Proyectos (solo
     * SA, solo Tiempo real): guarda en la BD el valor o el nombre editado de una línea. $_POST:
     * 'id' (data-id de la fila, "<tipo>-<código>"), y 'clave' + 'valor' (editar_valor_arbol) o
     * 'descripcion' (renombrar_linea_arbol). Responde JSON, sin redirigir.
     */
    private function editarArbolPresupuesto(string $tab, string $vista, string $lado): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $responder = static function (int $codigoHttp, string $error): void {
            http_response_code($codigoHttp);
            echo json_encode(['ok' => $error === '', 'error' => $error]);
        };

        if (!$this->esSuperAdmin()) {
            $responder(403, 'Solo el SA puede editar el árbol.');

            return;
        }

        if (!in_array($tab, ['programacion', 'comparativo', 'proyectos'], true) || $vista !== 'tiempo_real') {
            $responder(400, 'Esta vista no se puede editar.');

            return;
        }

        $tipo = $tab === 'proyectos' ? 'proyecto' : ($lado === 'egresos' ? 'egreso' : 'ingreso');
        $idFila = (string) ($_POST['id'] ?? '');
        if (!str_starts_with($idFila, $tipo . '-')) {
            $responder(400, 'Fila no válida.');

            return;
        }
        $codigo = substr($idFila, strlen($tipo) + 1);
        $usuarioId = (int) $_SESSION['usuario_id'];
        $modelo = new PresupuestoInstitucional();

        try {
            if ($_POST['accion'] === 'renombrar_linea_arbol') {
                $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
                if ($descripcion === '' || mb_strlen($descripcion) > 255) {
                    $responder(400, 'El nombre no puede quedar vacío ni pasar de 255 caracteres.');

                    return;
                }
                $error = $modelo->renombrarLinea($tipo, $codigo, $descripcion, $usuarioId);
            } else {
                $anioVigente = (new AnioPresupuestal())->obtenerActivos()[0]['anio'] ?? (int) date('Y');
                $anioPorClave = $this->resolverAniosPorClave((int) $anioVigente);
                $clave = (string) ($_POST['clave'] ?? '');
                $valor = $_POST['valor'] ?? '';
                if (!isset($anioPorClave[$clave]) || !is_numeric($valor)) {
                    $responder(400, 'Columna o valor no válido.');

                    return;
                }
                $campo = match ($clave) {
                    'anterior_corte' => 'valor_corte',
                    'anterior_inicial' => 'valor_inicial',
                    default => 'valor_final',
                };
                $error = $modelo->actualizarValorLinea($tipo, $codigo, $anioPorClave[$clave], $campo, round((float) $valor, 2), $usuarioId);
            }
        } catch (Throwable $excepcion) {
            $responder(500, 'No se pudo guardar en la base de datos.');

            return;
        }

        $responder($error === '' ? 200 : 400, $error);
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

        if (!in_array($tab, ['pdi', 'programacion', 'comparativo', 'proyectos'], true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Pestaña no válida.']);

            return;
        }

        $expandido = ($_POST['expandido'] ?? '') === '1';
        $guardado = (new AnalisisArbolConfiguracion())->guardarExpandido($tab, $expandido, (int) $_SESSION['usuario_id']);

        echo json_encode(['ok' => $guardado, 'expandido' => $expandido]);
    }

    /**
     * POST por fetch desde el modal del botón Ingresos de Análisis de distribución (solo SA): liga ese
     * botón a un código de Ingresos de PP. 'codigo' vacío = quitar la relación. Responde JSON.
     */
    private function guardarCodigoIngresosPp(string $tab): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $responder = static function (int $codigoHttp, string $error): void {
            http_response_code($codigoHttp);
            echo json_encode(['ok' => $error === '', 'error' => $error]);
        };

        if (!$this->esSuperAdmin()) {
            $responder(403, 'Solo el SA puede cambiar el código de Ingresos.');

            return;
        }

        if ($tab !== 'analisis') {
            $responder(400, 'Esta vista no tiene botón Ingresos.');

            return;
        }

        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        if ($codigo !== '') {
            $anioRegistro = (new AnioPresupuestal())->obtenerActivos()[0] ?? null;
            $anio = $anioRegistro !== null ? (int) $anioRegistro['anio'] : (int) date('Y');
            if (!isset((new PresupuestoInstitucional())->resumenPorCodigo('ingreso', $anio)['lineas'][$codigo])) {
                $responder(400, 'Ese código no existe en Programación presupuestal (Ingresos).');

                return;
            }
        }

        try {
            (new AnalisisArbolConfiguracion())->guardarCodigoPp('distribucion', $codigo !== '' ? $codigo : null, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            $responder(500, 'No se pudo guardar en la base de datos (¿ya se ejecutó instalar.php?).');

            return;
        }

        $responder(200, '');
    }

    /**
     * Tarjetas Ingresos e Inversiones de la sección Presupuesto del selector de Análisis de
     * distribución. Leen la MISMA fuente que su landing: Tiempo real/Usuario, PP en vivo; Repositorio,
     * la versión activa de PP de ese tipo ('ingreso' / 'proyecto'). 'url' es null si el usuario no
     * tiene permiso a esa pestaña (la tarjeta se muestra sin enlace).
     */
    private function obtenerTarjetasPresupuesto(string $vista, ?array $anioActivo): array
    {
        $anio = $anioActivo !== null ? (int) $anioActivo['anio'] : (int) date('Y');
        $pestanasPermitidas = AccesoAnalisis::actual()['pestanas'] ?? [];
        $modelo = new PresupuestoInstitucional();

        $resumen = static function (string $tipo) use ($vista, $modelo, $anio): ?array {
            $versionId = null;
            if ($vista === 'repositorio') {
                $version = $modelo->obtenerVersionActiva($tipo);
                if ($version === null) {
                    return null;
                }
                $versionId = (int) $version['id'];
            }

            return $modelo->resumenPorCodigo($tipo, $anio, $versionId);
        };
        $url = static fn (string $pestana, array $extra = []): ?string => in_array($pestana, $pestanasPermitidas, true)
            ? 'index.php?' . http_build_query(['ruta' => 'analisis', 'tab' => $pestana, 'vista' => $vista] + $extra)
            : null;

        $codigo = (new AnalisisArbolConfiguracion())->obtenerCodigoPp('distribucion');
        $resumenIngresos = $resumen('ingreso');
        $lineaIngreso = ($codigo !== null && $resumenIngresos !== null) ? ($resumenIngresos['lineas'][$codigo] ?? null) : null;
        $resumenProyectos = $resumen('proyecto');

        return [
            'anio' => $anio,
            'ingresos' => [
                'codigo' => $codigo,
                'nombre' => $lineaIngreso['descripcion'] ?? null,
                'valor' => $lineaIngreso['valor'] ?? null,
                'estado' => $codigo === null ? 'sin_configurar' : ($resumenIngresos === null ? 'sin_version' : ($lineaIngreso === null ? 'no_existe' : 'ok')),
                'url' => $url('programacion', ['lado' => 'ingresos']),
            ],
            'inversiones' => [
                'valor' => $resumenProyectos['total'] ?? null,
                'estado' => $resumenProyectos === null ? 'sin_version' : 'ok',
                'url' => $url('proyectos'),
            ],
        ];
    }

    /**
     * Id de una fila de la tabla real => [origen real, id real]. En el landing de Gastos: id negativo
     * = gasto fijo; id con desplazamiento = egreso de Extensión/Postgrado (ver
     * DESPLAZAMIENTO_ID_GASTO_AUTOGESTION). En cualquier otro landing el id es el propio.
     */
    private function decodificarIdTablaReal(int $id, string $origenPagina): array
    {
        if ($origenPagina === 'gasto_principal') {
            if ($id < 0) {
                return ['gasto_fijo', -$id];
            }
            foreach (array_reverse(self::DESPLAZAMIENTO_ID_GASTO_AUTOGESTION, true) as $origenReal => $desplazamiento) {
                if ($id > $desplazamiento) {
                    return [$origenReal, $id - $desplazamiento];
                }
            }
        }

        return [$origenPagina, $id];
    }

    /**
     * POST por fetch desde el <input type="date"> "Corte" del árbol (solo SA, Tiempo real): guarda
     * la fecha de corte de la pestaña para todos los usuarios. 'fecha' = Y-m-d. Responde JSON.
     */
    private function guardarFechaCorte(string $tab, string $vista): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $responder = static function (int $codigoHttp, string $error): void {
            http_response_code($codigoHttp);
            echo json_encode(['ok' => $error === '', 'error' => $error]);
        };

        if (!$this->esSuperAdmin()) {
            $responder(403, 'Solo el SA puede cambiar la fecha de corte.');

            return;
        }

        if (!in_array($tab, ['programacion', 'comparativo'], true) || $vista !== 'tiempo_real') {
            $responder(400, 'Esta vista no tiene fecha de corte.');

            return;
        }

        $fecha = (string) ($_POST['fecha'] ?? '');
        $partes = explode('-', $fecha);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
            $responder(400, 'La fecha no es válida.');

            return;
        }

        try {
            // Una sola fecha de corte para PP y su Comparativo (fila 'programacion').
            $guardado = (new AnalisisArbolConfiguracion())->guardarFechaCorte('programacion', $fecha, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            $responder(500, 'No se pudo guardar en la base de datos (¿ya se ejecutó instalar.php?).');

            return;
        }

        $responder($guardado ? 200 : 500, $guardado ? '' : 'No se pudo guardar la fecha.');
    }

    /**
     * POST por fetch desde el modal de ajustes de Articulación PDI (solo SA, Tiempo real/Repositorio):
     * 'tipo' = 'absorbe' (proyecto_id: a quién se le suma/resta la diferencia contra PP),
     * 'quitar_absorbe' (vuelve al de por defecto), 'valor' (proyecto_id + valor: valor a mostrar) o
     * 'quitar_valor' (proyecto_id). Responde JSON, sin redirigir.
     */
    private function guardarAjustePdi(string $tab, string $vista): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $responder = static function (int $codigoHttp, string $error): void {
            http_response_code($codigoHttp);
            echo json_encode(['ok' => $error === '', 'error' => $error]);
        };

        if (!$this->esSuperAdmin()) {
            $responder(403, 'Solo el SA puede cambiar estos ajustes.');

            return;
        }

        if ($tab !== 'pdi' || $vista === 'usuario') {
            $responder(400, 'Esta vista no admite ajustes.');

            return;
        }

        $modeloAnio = new AnioPresupuestal();
        $anioRegistro = $modeloAnio->obtenerActivos()[0] ?? ($modeloAnio->obtenerTodos()[0] ?? null);
        if ($anioRegistro === null) {
            $responder(400, 'No hay un año presupuestal configurado.');

            return;
        }
        $anio = (int) $anioRegistro['anio'];

        $tipo = (string) ($_POST['tipo'] ?? '');
        $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
        $usuarioId = (int) $_SESSION['usuario_id'];
        $modelo = new AnalisisPdiAjuste();

        try {
            if ($tipo === 'quitar_absorbe') {
                $modelo->quitarAbsorbe($anio);
                $responder(200, '');

                return;
            }

            if (!in_array($tipo, ['absorbe', 'valor', 'quitar_valor'], true) || (new Proyecto())->obtenerPorId($proyectoId) === null) {
                $responder(400, 'Proyecto o acción no válidos.');

                return;
            }

            if ($tipo === 'absorbe') {
                $modelo->guardarAbsorbe($anio, $proyectoId, $usuarioId);
            } elseif ($tipo === 'quitar_valor') {
                $modelo->quitarValor($anio, $proyectoId);
            } else {
                $valor = $_POST['valor'] ?? '';
                // Admite negativos (p. ej. para restar un ajuste a un proyecto).
                if (!is_numeric($valor)) {
                    $responder(400, 'El valor debe ser un número.');

                    return;
                }
                $modelo->guardarValor($anio, $proyectoId, (float) $valor, $usuarioId);
            }
        } catch (Throwable $excepcion) {
            $responder(500, 'No se pudo guardar en la base de datos (¿ya se ejecutó instalar.php?).');

            return;
        }

        $responder(200, '');
    }

    private function procesarPost(string $tab, string $vista): void
    {
        $error = '';
        $exito = '';
        $lado = ($_GET['lado'] ?? '') === 'egresos' ? 'egresos' : 'ingresos';

        if (in_array($tab, ['programacion', 'proyectos'], true) && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'limpiar_arbol') {
            [$error, $exito] = $this->limpiarArbolPresupuesto($tab, $lado);
        }

        if ($vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'crear_snapshot_pestana') {
            [$error, $exito] = $this->crearSnapshotPestana($tab, $lado);
        }

        if ($tab === 'analisis' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'editar_proyecto_masivo') {
            [$error, $exito] = $this->procesarEdicionMasivaProyecto((string) ($_GET['origen'] ?? ''));
        }

        if ($tab === 'analisis' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'restaurar_edicion_masiva') {
            [$error, $exito] = $this->procesarRestaurarEdicionMasiva((string) ($_GET['origen'] ?? ''));
        }

        if ($tab === 'analisis' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'eliminar_celda') {
            [$error, $exito] = $this->procesarEliminarModulo((string) ($_GET['origen'] ?? ''));
        } elseif ($tab === 'programacion' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'importar_presupuesto') {
            $error = $this->importarPresupuestoInstitucional();
            $exito = $error === '' ? 'Presupuesto institucional importado correctamente.' : '';
        } elseif ($tab === 'proyectos' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'importar_proyectos') {
            $error = $this->importarPresupuestoProyectos();
            $exito = $error === '' ? 'Proyectos importado correctamente.' : '';
        } elseif ($tab === 'presentacion' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'guardar_enlace_presentacion') {
            $esNuevo = (int) ($_POST['id'] ?? 0) <= 0;
            $error = $this->guardarEnlacePresentacion();
            $exito = $error === '' ? ($esNuevo ? 'Enlace agregado.' : 'Enlace actualizado.') : '';
        } elseif ($tab === 'presentacion' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'eliminar_enlace_presentacion') {
            $error = $this->eliminarEnlacePresentacion();
            $exito = $error === '' ? 'Enlace eliminado.' : '';
        } elseif ($tab === 'presentacion' && $vista === 'tiempo_real' && ($_POST['accion'] ?? '') === 'mover_enlace_presentacion') {
            $error = $this->moverEnlacePresentacion();
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
        if ($tab === 'programacion' || $tab === 'comparativo') {
            $destino .= '&lado=' . urlencode($lado);
        }

        header('Location: ' . $destino);
        exit;
    }

    /**
     * "Limpiar" (solo SA): borra el árbol en vivo del lado que se está viendo — Ingresos o Egresos
     * en Programación presupuestal, o el de Proyectos de inversión — para un cargue en limpio.
     * Antes guarda una versión de respaldo de ese lado (recuperable desde Repositorio).
     *
     * @return array{0: string, 1: string} [error, exito]
     */
    private function limpiarArbolPresupuesto(string $tab, string $lado): array
    {
        if (!$this->esSuperAdmin()) {
            return ['Solo el SA puede limpiar el árbol.', ''];
        }

        $tipo = $tab === 'proyectos' ? 'proyecto' : ($lado === 'egresos' ? 'egreso' : 'ingreso');
        $nombreLado = ['ingreso' => 'Ingresos', 'egreso' => 'Egresos', 'proyecto' => 'Proyectos de inversión'][$tipo];
        $modelo = new PresupuestoInstitucional();

        if (empty($modelo->obtenerArbolConValores($tipo, []))) {
            return ['El árbol de ' . $nombreLado . ' ya está vacío.', ''];
        }

        try {
            $modelo->crearVersion($tipo, 'Respaldo antes de limpiar ' . date('d/m/Y H:i'), (int) $_SESSION['usuario_id']);
            $borradas = $modelo->limpiar($tipo);
        } catch (Throwable $excepcion) {
            return ['No se pudo limpiar el árbol: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Árbol de ' . $nombreLado . ' limpio (' . $borradas . ' línea(s) borradas). Se guardó una versión de respaldo en Repositorio; ya puedes importar en limpio.'];
    }

    /** @return array{0: string, 1: string} [error, exito] */
    /**
     * Edición masiva del Proyecto PDI desde la tabla de Análisis de distribución (modal de
     * parciales/modal-edicion-masiva-proyecto.php): sobrescribe solo ese campo en las filas
     * seleccionadas. Ids negativos = gastos fijos (ver obtenerGastosFijosParaAnalisis()), solo SA;
     * el resto, si la fila es del usuario o es SA (mismo criterio que la eliminación en Peticiones).
     */
    private function procesarEdicionMasivaProyecto(string $origen): array
    {
        if (!in_array($origen, self::ORIGENES_ANALISIS, true) || !EdicionMasivaProyecto::admite($origen)) {
            return ['Este módulo no permite editar el Proyecto PDI.', ''];
        }

        $proyecto = EdicionMasivaProyecto::proyecto((int) ($_POST['proyecto_id'] ?? 0));
        if ($proyecto === null) {
            return ['Selecciona un Proyecto PDI válido.', ''];
        }

        $ids = $this->leerIdsSeleccionadosTablaReal();
        if (empty($ids)) {
            return ['No seleccionaste ninguna fila.', ''];
        }

        $edicion = new EdicionMasivaProyecto();
        $esSuperAdmin = $this->esSuperAdmin();
        $usuarioId = (int) $_SESSION['usuario_id'];
        $permitidas = [];
        $omitidos = 0;

        foreach ($ids as $id) {
            [$origenFila, $idFila] = $this->decodificarIdTablaReal($id, $origen);
            $registro = $idFila > 0 ? $edicion->obtenerRegistro($origenFila, $idFila) : null;
            $permitido = $registro !== null && ($esSuperAdmin
                || ($origenFila !== 'gasto_fijo' && (int) ($registro['usuario_id'] ?? 0) === $usuarioId));

            if ($permitido) {
                $permitidas[] = [$origenFila, $idFila];
            } else {
                $omitidos++;
            }
        }

        // Guarda un respaldo del estado previo antes de modificar (ver EdicionMasivaProyecto::aplicar()).
        $resultado = $edicion->aplicar($origen, $permitidas, $proyecto, $usuarioId);
        if ($resultado['error'] !== '') {
            return [$resultado['error'], ''];
        }
        $actualizados = $resultado['actualizados'];
        $omitidos += $resultado['omitidos'];

        if ($actualizados === 0) {
            return ['No se actualizó ninguna fila (no existen, ya tenían ese Proyecto PDI o no tienes permiso para editarlas).', ''];
        }

        $exito = 'Proyecto PDI actualizado a "' . $proyecto['codigo'] . ' - ' . $proyecto['nombre'] . '" en ' . $actualizados . ($actualizados === 1 ? ' fila.' : ' filas.');
        if ($omitidos > 0) {
            $exito .= ' ' . $omitidos . ($omitidos === 1 ? ' fila se omitió.' : ' filas se omitieron.');
        }

        return ['', $exito];
    }

    /**
     * Revierte una edición masiva de Proyecto PDI desde su respaldo (modal de edición masiva). Solo
     * superadmin. @return array{0: string, 1: string} [error, exito]
     */
    private function procesarRestaurarEdicionMasiva(string $origen): array
    {
        if (!$this->esSuperAdmin()) {
            return ['Solo el superadmin puede restaurar una edición masiva.', ''];
        }
        if (!in_array($origen, self::ORIGENES_ANALISIS, true) || !EdicionMasivaProyecto::admite($origen)) {
            return ['Este módulo no permite restaurar el Proyecto PDI.', ''];
        }

        $resultado = (new EdicionMasivaProyecto())->restaurar((int) ($_POST['respaldo_id'] ?? 0), $origen, (int) $_SESSION['usuario_id']);
        if ($resultado['error'] !== '') {
            return [$resultado['error'], ''];
        }

        $exito = 'Edición masiva restaurada: ' . $resultado['restauradas'] . ($resultado['restauradas'] === 1 ? ' fila.' : ' filas.');
        if ($resultado['omitidas'] > 0) {
            $exito .= ' ' . $resultado['omitidas'] . ($resultado['omitidas'] === 1 ? ' fila se omitió' : ' filas se omitieron') . ' (ya se habían cambiado o no existen).';
        }

        return ['', $exito];
    }

    private function procesarEliminarModulo(string $origen): array
    {
        if (!in_array($origen, self::ORIGENES_ANALISIS, true)) {
            return ['Origen inválido.', ''];
        }

        $ids = $this->leerIdsSeleccionadosTablaReal();
        if (empty($ids)) {
            return ['No seleccionaste ningún ítem para eliminar.', ''];
        }

        $modelo = $this->obtenerModeloModulo($origen);

        // Gastos fijos (id negativo, solo en Gasto): se borran de su propia tabla y solo el SA;
        // nunca se mandan al modelo Gasto.
        $eliminadosFijos = 0;
        if ($origen === 'gasto_principal') {
            $idsFijos = array_filter($ids, static fn (int $id): bool => $id < 0);
            if (!empty($idsFijos) && $this->esSuperAdmin()) {
                $modeloFijo = new GastoFijo();
                foreach ($idsFijos as $idFijo) {
                    if ($modeloFijo->obtenerPorId(-$idFijo) !== null && $modeloFijo->eliminar(-$idFijo)) {
                        $eliminadosFijos++;
                    }
                }
            }
        }
        // Egresos de Extensión/Postgrado cargados en el landing de Gastos (id con desplazamiento): se
        // borran con su propio modelo, nunca con el de Gasto (ver decodificarIdTablaReal()).
        if ($origen === 'gasto_principal') {
            foreach ($ids as $idCodificado) {
                [$origenReal, $idReal] = $this->decodificarIdTablaReal($idCodificado, $origen);
                if (!isset(self::DESPLAZAMIENTO_ID_GASTO_AUTOGESTION[$origenReal])) {
                    continue;
                }
                $modeloReal = $this->obtenerModeloModulo($origenReal);
                if ($modeloReal->obtenerPorId($idReal) !== null && $modeloReal->eliminar($idReal)) {
                    $eliminadosFijos++;
                }
            }
        }
        $limiteIdPropio = min(self::DESPLAZAMIENTO_ID_GASTO_AUTOGESTION);
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0 && ($origen !== 'gasto_principal' || $id <= $limiteIdPropio)));

        // Igual que Extensión/Postgrado al eliminar un ingreso: primero sus gastos automáticos
        // (por ingreso_id) y LUEGO el ingreso — al revés, la FK ON DELETE SET NULL deja esos
        // gastos huérfanos para siempre.
        $origenGastoPar = array_search($origen, self::PARES_GASTO_INGRESO, true);

        $eliminados = $eliminadosFijos;
        foreach ($ids as $id) {
            if (!method_exists($modelo, 'obtenerPorId') || $modelo->obtenerPorId($id) === null) {
                continue;
            }

            if ($origenGastoPar !== false) {
                $this->obtenerModeloModulo($origenGastoPar)->eliminarAutomaticosPorIngreso($id);
            }
            $modelo->eliminar($id);
            $eliminados++;
        }

        if ($eliminados === 0) {
            return ['Ningún ítem seleccionado existe ya.', ''];
        }

        return ['', $eliminados === 1 ? 'Ítem eliminado.' : $eliminados . ' ítems eliminados.'];
    }

    /**
     * IDs seleccionados en la tabla real (checkboxes, ver publico/js/tabla-real/tabla.js) — un
     * solo campo JSON en vez de origen_id[] paralelos, para no toparse con max_input_vars cuando
     * se selecciona "todo" en una tabla grande (mismo criterio que ya usa Peticiones para sus
     * acciones masivas, ver PeticionesControlador::leerItemsJson()).
     */
    private function leerIdsSeleccionadosTablaReal(): array
    {
        $json = $_POST['origen_ids_json'] ?? '';
        $decodificado = is_string($json) && $json !== '' ? json_decode($json, true) : null;

        if (!is_array($decodificado)) {
            return [];
        }

        $ids = array_map('intval', $decodificado);
        // Los ids negativos son gastos fijos (ver obtenerGastosFijosParaAnalisis()).
        $ids = array_filter($ids, static fn (int $id): bool => $id !== 0);

        return array_values(array_unique($ids));
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
        $esArbolPresupuesto = in_array($tab, ['programacion', 'comparativo', 'proyectos'], true);
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

        // "Proyectos" solo muestra el año vigente (sin año anterior ni históricos).
        $modoColumnas = $tab === 'programacion' ? 'completo' : 'estructura';

        // La fecha de corte es la que eligió el admin con el <input type="date"> del topbar y queda
        // guardada en BD (analisis_arbol_configuracion.fecha_corte, por pestaña) para todos; solo
        // sin ninguna elegida se usa el mismo día del año anterior. No viene de la plantilla ni de la URL.
        $fechaCorteDefecto = $anioAnteriorNumero . date('-m-d');
        $fechaCorte = (new AnalisisArbolConfiguracion())->obtenerFechaCorte('programacion') ?? $fechaCorteDefecto;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCorte)) {
            $fechaCorte = $fechaCorteDefecto;
        }

        $columnasAnios = [
            ['clave' => 'vigente', 'etiqueta' => (string) $anioVigenteNumero, 'registro' => $anioVigenteRegistro, 'corte' => null, 'grupo' => 'vigente'],
        ];

        // Comparativo vigencia inicial: 2027 frente a 2026 Final, 2026 Corte (fecha) y 2026 Inicial,
        // siempre visibles (grupo propio, sin el plegado de "Año anterior") y sin históricos.
        if ($tab === 'comparativo') {
            $modoColumnas = 'comparativo';
            $registroAnterior = $aniosPorNumero[$anioAnteriorNumero] ?? null;
            $columnasAnios[] = ['clave' => 'anterior_total', 'etiqueta' => $anioAnteriorNumero . ' Final', 'registro' => $registroAnterior, 'corte' => null, 'grupo' => 'comparativo'];
            $columnasAnios[] = ['clave' => 'anterior_corte', 'etiqueta' => $anioAnteriorNumero . ' Corte (' . date('d/m/Y', strtotime($fechaCorte)) . ')', 'registro' => $registroAnterior, 'corte' => $fechaCorte, 'grupo' => 'comparativo'];
            $columnasAnios[] = ['clave' => 'anterior_inicial', 'etiqueta' => $anioAnteriorNumero . ' Inicial', 'registro' => $registroAnterior, 'corte' => null, 'grupo' => 'comparativo'];
        }

        if ($modoColumnas === 'completo') {
            $columnasAnios[] = ['clave' => 'anterior_total', 'etiqueta' => $anioAnteriorNumero . ' (Final)', 'registro' => $aniosPorNumero[$anioAnteriorNumero] ?? null, 'corte' => null, 'grupo' => 'anterior'];
            // Columna "a corte" OCULTA por ahora en Programación presupuestal (se reutilizará en otro
            // landing): no se borra nada — ni la fecha guardada, ni valor_corte, ni la plantilla.
            if (self::MOSTRAR_COLUMNA_CORTE) {
                $columnasAnios[] = ['clave' => 'anterior_corte', 'etiqueta' => $anioAnteriorNumero . ' (' . date('d/m/Y', strtotime($fechaCorte)) . ')', 'registro' => $aniosPorNumero[$anioAnteriorNumero] ?? null, 'corte' => $fechaCorte, 'grupo' => 'anterior'];
            }

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
        $infoAjustePdi = null;
        $comparativoVigencia = null;
        if ($tab === 'comparativo') {
            [$arbolDatos, $totalesGenerales] = $this->construirArbolPresupuesto($tipoPresupuesto, $clavesColumnas, $anioVigenteNumero, $versionIdActual, $sinDatosRepositorio);
            $columnasExtra = [
                ['clave' => 'codigo', 'etiqueta' => 'Código'],
            ];
            $etiquetaColumnaArbol = 'Descripción';
            $comparativoVigencia = $this->calcularComparativoVigencia($arbolDatos, $fechaCorte, $anioAnteriorNumero, $lado);
        } elseif ($tab === 'programacion') {
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
            // Código PP solo para el superadmin en Tiempo real (es para el cuadre, no de consulta).
            if ($vista === 'tiempo_real' && AccesoAnalisis::actual()['clase'] === 'superadmin') {
                $columnasExtra[] = ['clave' => 'codigoPp', 'etiqueta' => 'Código PP'];
            }
            // 'despues': van a la derecha de Descripción (ver arbol.php y exportarArbol()).
            $columnasExtra[] = ['clave' => 'detalle', 'etiqueta' => 'Detalle', 'despues' => true];
            $columnasExtra[] = ['clave' => 'fuentesFinanciacion', 'etiqueta' => 'Fuentes de financiación', 'despues' => true];
            $etiquetaColumnaArbol = 'Descripción';
            // El cuadre muestra los Códigos PP: mismo alcance que esa columna (SA, Tiempo real).
            if ($vista === 'tiempo_real' && AccesoAnalisis::actual()['clase'] === 'superadmin') {
                $cuadreProyectos = $this->calcularCuadreProyectos($arbolDatos, $anioVigenteNumero);
            }
        } else {
            $fuenteDatos = new FuenteDatosAnalisis($versionIdActual, $dependenciaFiltroActual);
            // Las tres vistas siguen la lógica de Tiempo real: solo gasto principal (sin autogestión) y
            // sin borradores, archivados ni Expediente. Usuario, además, sin el aporte de PP (no se puede
            // atribuir a una dependencia).
            [$arbolDatos, $totalesGenerales, $infoAjustePdi] = $this->construirArbolPdi($fuenteDatos, $columnasAnios, $clavesColumnas, $sinDatosRepositorio, $vista === 'usuario', true);
        }

        $error = $_SESSION['analisis_flash_error'] ?? '';
        $exito = $_SESSION['analisis_flash_exito'] ?? '';
        unset($_SESSION['analisis_flash_error'], $_SESSION['analisis_flash_exito']);

        $tituloPaginaPorTab = [
            'programacion' => 'Análisis · Programación presupuestal ' . $anioVigenteNumero,
            'comparativo' => 'Análisis · Comparativo vigencia inicial ' . $anioVigenteNumero,
            'proyectos' => 'Análisis · Proyectos de Inversión',
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
            'programacion', 'comparativo' => $lado === 'ingresos' ? 'Ingresos' : 'Egresos',
            'proyectos' => 'Proyectos de Inversión',
            default => 'Articulación PDI',
        };
        $titulo = match ($tab) {
            'programacion' => 'Programación presupuestal ' . $anioVigenteNumero . ' — ' . $nombreHoja,
            'comparativo' => 'Comparativo vigencia inicial ' . $anioVigenteNumero . ' — ' . $nombreHoja,
            'proyectos' => 'Proyectos de Inversión',
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

        // Igual que en la plantilla (ver exportarPlantillaPresupuesto()): "Indexación" solo en
        // Programación presupuestal, siempre al final, nunca en medio de las otras columnas.
        $incluirIndexacion = $tab === 'programacion';

        // Columnas extra a la izquierda de la descripción y las marcadas 'despues' a su derecha.
        $columnasAntes = array_values(array_filter($columnasExtra, static fn (array $c): bool => empty($c['despues'])));
        $columnasDespues = array_values(array_filter($columnasExtra, static fn (array $c): bool => !empty($c['despues'])));

        $encabezados = array_merge(
            array_column($columnasAntes, 'etiqueta'),
            [$etiquetaColumnaArbol],
            array_column($columnasDespues, 'etiqueta'),
            array_column($columnasAnios, 'etiqueta'),
            $incluirIndexacion ? ['Indexación'] : []
        );

        $filas = [];
        // Articulación PDI se muestra y exporta sin decimales.
        $decimales = $tab === 'pdi' ? 0 : 2;
        $this->aplanarArbolParaExportar($arbolDatos, 0, $columnasAnios, $columnasExtra, $incluirIndexacion, $filas, $decimales);

        if (!empty($filas)) {
            $filaTotal = array_fill(0, count($columnasAntes), ['valor' => '', 'estilo' => 3]);
            $filaTotal[] = ['valor' => 'Total', 'estilo' => 3];
            foreach ($columnasDespues as $columnaDespues) {
                $filaTotal[] = ['valor' => '', 'estilo' => 3];
            }
            foreach ($columnasAnios as $columna) {
                $filaTotal[] = ['valor' => $this->formatoMonedaExportar((float) ($totalesGenerales[$columna['clave']] ?? 0.0), $decimales), 'estilo' => 3];
            }
            if ($incluirIndexacion) {
                $filaTotal[] = ['valor' => '', 'estilo' => 3];
            }
            $filas[] = $filaTotal;
        }

        $filasPrevias = [
            [['valor' => $titulo, 'estilo' => 4]],
            ['Modo', $descripcionModo],
        ];
        if (in_array('anterior_corte', array_column($columnasAnios, 'clave'), true)) {
            $filasPrevias[] = ['Fecha de corte', date('d/m/Y', strtotime($fechaCorte))];
        }
        $filasPrevias[] = ['Generado', date('d/m/Y H:i')];
        $filasPrevias[] = [];

        $prefijoArchivo = match ($tab) {
            'programacion' => 'programacion_' . ($lado === 'ingresos' ? 'ingresos' : 'egresos'),
            'comparativo' => 'comparativo_vigencia_' . ($lado === 'ingresos' ? 'ingresos' : 'egresos'),
            'proyectos' => 'proyectos',
            default => 'articulacion_pdi',
        };

        $hojas = [[
            'nombre' => $nombreHoja,
            'filasPrevias' => $filasPrevias,
            'encabezados' => $encabezados,
            'filas' => empty($filas) ? [['No hay datos para mostrar.']] : $filas,
        ]];
        if ($tab === 'comparativo') {
            $hojas[] = $this->construirHojaIndicadoresComparativo($this->calcularComparativoVigencia($arbolDatos, $fechaCorte, $anioVigenteNumero - 1, $lado), $anioVigenteNumero);
        }

        GeneradorXlsx::descargarHojas($prefijoArchivo . '_' . $anioVigenteNumero . '_' . $vista . '.xlsx', $hojas);
        exit;
    }

    /**
     * Indicadores del Comparativo vigencia inicial, por sección raíz y total: proyección (2027) contra
     * 2026 Inicial (aprobado), Final (definitivo), Corte (ejecutado a la fecha) y el cierre estimado
     * (corte llevado al año completo). Ritmo = % ejecutado − % del año transcurrido a la fecha de
     * corte. En Ingresos la "ejecución" es recaudo. Los % sin base (división por 0) quedan null.
     *
     * @return array{lado: string, fechaCorte: string, porcentajeTiempo: float, secciones: array<int, array>, total: array}
     */
    private function calcularComparativoVigencia(array $arbolDatos, string $fechaCorte, int $anioAnterior, string $lado): array
    {
        $marca = strtotime($fechaCorte);
        $anioFecha = (int) date('Y', $marca);
        if ($anioFecha < $anioAnterior) {
            $porcentajeTiempo = 0.0;
        } elseif ($anioFecha > $anioAnterior) {
            $porcentajeTiempo = 100.0;
        } else {
            $porcentajeTiempo = ((int) date('z', $marca) + 1) / ((int) date('L', $marca) === 1 ? 366 : 365) * 100;
        }

        $variacion = static fn (float $proyeccion, ?float $base): ?float => ($base !== null && abs($base) > 0.0) ? ($proyeccion - $base) / abs($base) * 100 : null;

        $armar = static function (string $id, string $codigo, string $etiqueta, array $valores) use ($porcentajeTiempo, $variacion): array {
            $proyeccion = (float) ($valores['vigente'] ?? 0.0);
            $inicial = (float) ($valores['anterior_inicial'] ?? 0.0);
            $final = (float) ($valores['anterior_total'] ?? 0.0);
            $corte = (float) ($valores['anterior_corte'] ?? 0.0);
            $cierre = ($porcentajeTiempo > 0.0 && $corte > 0.0) ? $corte / ($porcentajeTiempo / 100) : null;
            $ejecucion = $final > 0.0 ? $corte / $final * 100 : null;

            return [
                'id' => $id,
                'codigo' => $codigo,
                'etiqueta' => $etiqueta,
                'proyeccion' => $proyeccion,
                'inicial' => $inicial,
                'final' => $final,
                'corte' => $corte,
                'cierre' => $cierre,
                'variacion' => [
                    'inicial' => ['valor' => $proyeccion - $inicial, 'porcentaje' => $variacion($proyeccion, $inicial)],
                    'final' => ['valor' => $proyeccion - $final, 'porcentaje' => $variacion($proyeccion, $final)],
                    'corte' => ['valor' => $proyeccion - $corte, 'porcentaje' => $variacion($proyeccion, $corte)],
                    'cierre' => ['valor' => $cierre !== null ? $proyeccion - $cierre : null, 'porcentaje' => $variacion($proyeccion, $cierre)],
                ],
                'modificacion' => $final - $inicial,
                'modificacionPorcentaje' => $variacion($final, $inicial),
                'ejecucion' => $ejecucion,
                'saldo' => $final - $corte,
                'ritmo' => $ejecucion !== null ? $ejecucion - $porcentajeTiempo : null,
            ];
        };

        $totales = ['vigente' => 0.0, 'anterior_inicial' => 0.0, 'anterior_total' => 0.0, 'anterior_corte' => 0.0];
        foreach ($arbolDatos as $raiz) {
            foreach ($totales as $clave => $acumulado) {
                $totales[$clave] = $acumulado + (float) ($raiz['valores'][$clave] ?? 0.0);
            }
        }

        $secciones = [];
        foreach ($arbolDatos as $raiz) {
            $seccion = $armar((string) $raiz['id'], (string) ($raiz['codigo'] ?? ''), (string) $raiz['etiqueta'], $raiz['valores']);
            $seccion['participacionProyeccion'] = $totales['vigente'] != 0.0 ? $seccion['proyeccion'] / $totales['vigente'] * 100 : null;
            $seccion['participacionFinal'] = $totales['anterior_total'] != 0.0 ? $seccion['final'] / $totales['anterior_total'] * 100 : null;
            $seccion['cambioParticipacion'] = ($seccion['participacionProyeccion'] !== null && $seccion['participacionFinal'] !== null)
                ? $seccion['participacionProyeccion'] - $seccion['participacionFinal']
                : null;
            $secciones[] = $seccion;
        }

        return [
            'lado' => $lado,
            'fechaCorte' => $fechaCorte,
            'porcentajeTiempo' => $porcentajeTiempo,
            'secciones' => $secciones,
            'total' => $armar('total', '', 'Total', $totales),
        ];
    }

    /** Hoja "Indicadores" del Excel del Comparativo: una fila por sección y el total. */
    private function construirHojaIndicadoresComparativo(array $comparativo, int $anioVigente): array
    {
        $anioAnterior = $anioVigente - 1;
        $moneda = fn (?float $valor): string => $valor === null ? '—' : $this->formatoMonedaExportar($valor);
        $porcentaje = static fn (?float $valor): string => $valor === null ? '—' : number_format($valor, 1, ',', '.') . '%';
        $nombreEjecucion = $comparativo['lado'] === 'ingresos' ? 'Recaudo' : 'Ejecución';

        $filas = [];
        foreach (array_merge($comparativo['secciones'], [$comparativo['total']]) as $fila) {
            $esTotal = $fila['id'] === 'total';
            $celdas = [
                trim($fila['codigo'] . ' ' . $fila['etiqueta']),
                $moneda($fila['proyeccion']),
                $moneda($fila['inicial']), $moneda($fila['final']), $moneda($fila['corte']), $moneda($fila['cierre']),
                $porcentaje($fila['variacion']['inicial']['porcentaje']), $porcentaje($fila['variacion']['final']['porcentaje']),
                $porcentaje($fila['variacion']['corte']['porcentaje']), $porcentaje($fila['variacion']['cierre']['porcentaje']),
                $moneda($fila['modificacion']), $porcentaje($fila['modificacionPorcentaje']),
                $porcentaje($fila['ejecucion']), $moneda($fila['saldo']),
                $fila['ritmo'] === null ? '—' : number_format($fila['ritmo'], 1, ',', '.') . ' pp',
                $esTotal ? '' : $porcentaje($fila['participacionFinal'] ?? null),
                $esTotal ? '' : $porcentaje($fila['participacionProyeccion'] ?? null),
                $esTotal || ($fila['cambioParticipacion'] ?? null) === null ? '' : number_format($fila['cambioParticipacion'], 1, ',', '.') . ' pp',
            ];
            $filas[] = $esTotal ? array_map(static fn (string $c): array => ['valor' => $c, 'estilo' => 3], $celdas) : $celdas;
        }

        return [
            'nombre' => 'Indicadores',
            'filasPrevias' => [
                [['valor' => 'Indicadores — Comparativo vigencia inicial', 'estilo' => 4]],
                ['Fecha de corte', date('d/m/Y', strtotime($comparativo['fechaCorte']))],
                ['% del año ' . $anioAnterior . ' transcurrido', number_format($comparativo['porcentajeTiempo'], 1, ',', '.') . '%'],
                [],
            ],
            'encabezados' => [
                'Sección', (string) $anioVigente,
                $anioAnterior . ' Inicial', $anioAnterior . ' Final', $anioAnterior . ' Corte', 'Cierre estimado ' . $anioAnterior,
                'Var. % vs Inicial', 'Var. % vs Final', 'Var. % vs Corte', 'Var. % vs Cierre',
                'Modificación (Final − Inicial)', 'Modificación %',
                $nombreEjecucion . ' a corte %', 'Saldo por ' . mb_strtolower($nombreEjecucion === 'Recaudo' ? 'recaudar' : 'ejecutar'), 'Ritmo vs tiempo',
                'Participación ' . $anioAnterior . ' Final', 'Participación ' . $anioVigente, 'Cambio de participación',
            ],
            'filas' => $filas,
        ];
    }

    /** Padre antes que sus hijos; la profundidad sangra la descripción y las raíces/totales van en negrita. */
    private function aplanarArbolParaExportar(array $nodos, int $profundidad, array $columnasAnios, array $columnasExtra, bool $incluirIndexacion, array &$filas, int $decimales = 2): void
    {
        foreach ($nodos as $nodo) {
            $enNegrita = $profundidad === 0 || !empty($nodo['esTotal']);
            $celda = static fn (string $texto): array|string => $enNegrita ? ['valor' => $texto, 'estilo' => 4] : $texto;

            $fila = [];
            $filaDespues = [];
            foreach ($columnasExtra as $columnaExtra) {
                // Proyecto(s) PDI se exporta como los NITs separados por ";", no los nombres.
                $claveExportar = $columnaExtra['clave'] === 'proyectosPdi' ? 'proyectosPdiNits' : $columnaExtra['clave'];
                if (!empty($columnaExtra['despues'])) {
                    $filaDespues[] = $celda((string) ($nodo[$claveExportar] ?? ''));
                } else {
                    $fila[] = $celda((string) ($nodo[$claveExportar] ?? ''));
                }
            }
            $fila[] = $celda(str_repeat('    ', $profundidad) . $nodo['etiqueta']);
            $fila = array_merge($fila, $filaDespues);
            foreach ($columnasAnios as $columna) {
                $fila[] = $celda($this->formatoMonedaExportar((float) ($nodo['valores'][$columna['clave']] ?? 0.0), $decimales));
            }
            if ($incluirIndexacion) {
                $fila[] = $celda((string) ($nodo['indexacion'] ?? ''));
            }
            $filas[] = $fila;

            $this->aplanarArbolParaExportar($nodo['hijos'] ?? [], $profundidad + 1, $columnasAnios, $columnasExtra, $incluirIndexacion, $filas, $decimales);
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

        // Articulación PDI no muestra decimales: cada proyecto se redondea al peso y, en Tiempo
        // real/Repositorio, la diferencia contra Programación presupuestal se carga a un proyecto.
        $infoAjustePdi = $this->redondearYCuadrarTotalesPdi($totalesPorColumna, $proyectos, $columnaVigente, $clavesColumnas, $modoUsuario);

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

        return [$arbolDatos, $totalesGenerales, $infoAjustePdi];
    }

    /** Proyecto que absorbe por defecto la diferencia contra PP: línea 5, motor 3, proyecto 5 (NIT 050305). */
    private const PROYECTO_AJUSTE_PP = [5, 3, 5];

    /** Hasta este monto (pesos) la diferencia contra PP se carga sola al proyecto por defecto; más, la decide el SA. */
    private const UMBRAL_AJUSTE_AUTOMATICO = 50;

    /**
     * Pasos de Articulación PDI (columna vigente en Tiempo real/Repositorio):
     * 1) cada proyecto del catálogo se redondea al peso; 2) los valores fijados por el SA lo
     * reemplazan; 3) la diferencia contra el total de Egresos de PP se suma (o resta) al proyecto
     * absorbente — el elegido por el SA o, si |dif| <= UMBRAL_AJUSTE_AUTOMATICO, L5 M3 P5; con una
     * diferencia mayor y sin elección, no se fuerza. Modifica $totalesPorColumna por referencia y
     * devuelve el resumen para el modal de ajustes (null en modo Usuario o sin año vigente).
     */
    private function redondearYCuadrarTotalesPdi(array &$totalesPorColumna, array $proyectos, array $columnaVigente, array $clavesColumnas, bool $modoUsuario): ?array
    {
        $idsCatalogo = array_map(static fn (array $proyecto): int => (int) $proyecto['id'], $proyectos);

        foreach ($clavesColumnas as $clave) {
            foreach ($idsCatalogo as $idProyecto) {
                $totalesPorColumna[$clave][$idProyecto] = round((float) ($totalesPorColumna[$clave][$idProyecto] ?? 0.0), 0);
            }
        }

        if ($modoUsuario || $columnaVigente['registro'] === null || empty($idsCatalogo)) {
            return null;
        }

        $claveVigente = $columnaVigente['clave'];
        $anio = (int) $columnaVigente['registro']['anio'];

        try {
            $ajustes = (new AnalisisPdiAjuste())->obtener($anio);
        } catch (Throwable $excepcion) {
            // Tabla aún no creada (instalar.php): el árbol funciona igual, sin ajustes del SA.
            $ajustes = ['valores' => [], 'absorbe' => null];
        }

        $digitos = static fn ($codigo): int => (int) preg_replace('/\D/', '', (string) $codigo);
        $idPorDefecto = null;
        $infoProyectos = [];
        foreach ($proyectos as $proyecto) {
            $idProyecto = (int) $proyecto['id'];
            if ([$digitos($proyecto['linea_codigo'] ?? ''), $digitos($proyecto['motor_codigo'] ?? ''), $digitos($proyecto['codigo'] ?? '')] === self::PROYECTO_AJUSTE_PP) {
                $idPorDefecto = $idProyecto;
            }
            $infoProyectos[$idProyecto] = [
                'id' => $idProyecto,
                'etiqueta' => trim(($proyecto['linea_codigo'] ?? '') . ' ' . ($proyecto['motor_codigo'] ?? '') . ' ' . ($proyecto['codigo'] ?? '')) . ' · ' . $proyecto['nombre'],
                'calculado' => $totalesPorColumna[$claveVigente][$idProyecto],
                'fijado' => null,
            ];
        }

        foreach ($ajustes['valores'] as $idProyecto => $valor) {
            if (isset($infoProyectos[$idProyecto])) {
                $totalesPorColumna[$claveVigente][$idProyecto] = round($valor, 0);
                $infoProyectos[$idProyecto]['fijado'] = round($valor, 0);
            }
        }

        $suma = 0.0;
        foreach ($idsCatalogo as $idProyecto) {
            $suma += $totalesPorColumna[$claveVigente][$idProyecto];
        }
        $pp = round($this->totalEgresosPresupuesto($anio), 0);
        $diferencia = $pp - $suma;

        $absorbeElegido = ($ajustes['absorbe'] !== null && isset($infoProyectos[$ajustes['absorbe']])) ? $ajustes['absorbe'] : null;
        $absorbeId = $absorbeElegido ?? (abs($diferencia) <= self::UMBRAL_AJUSTE_AUTOMATICO ? $idPorDefecto : null);
        if ($absorbeId !== null && $diferencia != 0.0) {
            $totalesPorColumna[$claveVigente][$absorbeId] += $diferencia;
        }

        return [
            'anio' => $anio,
            'pp' => $pp,
            'suma' => $suma,
            'diferencia' => $diferencia,
            'absorbeId' => $absorbeId,
            'absorbeElegido' => $absorbeElegido,
            'idPorDefecto' => $idPorDefecto,
            'pendiente' => $absorbeId === null && $diferencia != 0.0,
            'proyectos' => array_values($infoProyectos),
        ];
    }

    /** Total general de Egresos de PP para un año: suma de las raíces del árbol (la fila Total de PP › Egresos). */
    private function totalEgresosPresupuesto(int $anio): float
    {
        $total = 0.0;
        foreach ((new PresupuestoInstitucional())->obtenerArbolConValores('egreso', [$anio]) as $raiz) {
            $total += (float) ($raiz['valores'][$anio] ?? 0.0);
        }

        return $total;
    }

    /**
     * Pestaña "Techos y Metas": Tiempo real, Usuario o Repositorio (versiones congeladas vía
     * VersionTechos, con filtro por dependencia también en Repositorio). Un bloque a la vez: gasto
     * principal (techo vs asignación) o un módulo de autogestión (tope vs ingresos).
     */
    private function renderizarTechosMetas(string $vista, array $dependenciasTodas): void
    {
        $vista = in_array($vista, ['usuario', 'repositorio'], true) ? $vista : 'tiempo_real';
        $bloque = in_array($_GET['bloque'] ?? '', TechosMetas::BLOQUES, true) ? (string) $_GET['bloque'] : 'gastos';

        $dependenciaFiltroActual = null;
        if ($vista === 'usuario' || $vista === 'repositorio') {
            $dependenciaFiltroActual = trim((string) ($_GET['dependencia'] ?? ''));
            if ($dependenciaFiltroActual === '' && !empty($dependenciasTodas)) {
                $dependenciaFiltroActual = $dependenciasTodas[0]['nombre'];
            }
        }

        $versiones = [];
        $versionIdActual = null;
        $sinDatosRepositorio = false;
        $modeloVersionTechos = new VersionTechos();

        if ($vista === 'repositorio') {
            $versiones = $modeloVersionTechos->obtenerTodos();
            $versionSolicitada = (int) ($_GET['version_id'] ?? 0);

            // Solo el superadmin elige la versión; los demás ven la que él dejó activa — mismo
            // mecanismo que renderizarArbol() para Árbol PDI/Programación/Proyectos.
            if ($versionSolicitada > 0 && AccesoAnalisis::actual()['clase'] === 'superadmin') {
                $modeloVersionTechos->marcarActiva($versionSolicitada);
                $versionIdActual = $versionSolicitada;
            } else {
                $versionActiva = $modeloVersionTechos->obtenerActiva();
                $versionIdActual = $versionActiva !== null ? (int) $versionActiva['id'] : 0;
            }

            if ($versionIdActual <= 0) {
                $versionIdActual = null;
                $sinDatosRepositorio = true;
            }
        }

        if ($sinDatosRepositorio) {
            $datos = ['nodos' => [], 'totales' => [], 'serie' => []];
        } elseif ($vista === 'repositorio') {
            $datos = $modeloVersionTechos->obtenerBloqueDeVersion($versionIdActual, $bloque, $dependenciaFiltroActual) ?? ['nodos' => [], 'totales' => [], 'serie' => []];
        } else {
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
        }

        $tab = 'techos';
        $error = $_SESSION['analisis_flash_error'] ?? '';
        $exito = $_SESSION['analisis_flash_exito'] ?? '';
        unset($_SESSION['analisis_flash_error'], $_SESSION['analisis_flash_exito']);
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

    /** Pestaña Documentos (antes "Presentación"): lista de enlaces de OneDrive/SharePoint. Solo el superadmin la administra. */
    private function renderizarPresentacion(string $vista, array $acceso): void
    {
        $vista = in_array($vista, ['usuario', 'repositorio'], true) ? $vista : 'tiempo_real';

        // Actas ya no es una pestaña aparte: su tarjeta vive sembrada como una fila más de esta
        // misma tabla (ver AnalisisPresentacion::URL_ACTAS), así se puede mover ▲▼ junto con los
        // documentos — pero solo para quien todavía tiene permiso de esa pestaña.
        $muestraBotonActas = in_array('actas', AccesoAnalisis::actual()['pestanas'], true);

        $versiones = [];
        $versionIdActual = null;
        $sinDatosRepositorio = false;
        $modeloVersionDocumentos = new VersionDocumentos();

        if ($vista === 'repositorio') {
            $versiones = $modeloVersionDocumentos->obtenerTodos();
            $versionSolicitada = (int) ($_GET['version_id'] ?? 0);

            if ($versionSolicitada > 0 && $acceso['clase'] === 'superadmin') {
                $modeloVersionDocumentos->marcarActiva($versionSolicitada);
                $versionIdActual = $versionSolicitada;
            } else {
                $versionActiva = $modeloVersionDocumentos->obtenerActiva();
                $versionIdActual = $versionActiva !== null ? (int) $versionActiva['id'] : 0;
            }

            if ($versionIdActual <= 0) {
                $versionIdActual = null;
                $sinDatosRepositorio = true;
            }

            $enlacesPresentacion = $sinDatosRepositorio ? [] : $modeloVersionDocumentos->obtenerDocumentosDeVersion($versionIdActual);
        } else {
            $enlacesPresentacion = (new AnalisisPresentacion())->obtenerTodos();
        }

        if (!$muestraBotonActas) {
            $enlacesPresentacion = array_values(array_filter(
                $enlacesPresentacion,
                static fn (array $enlace): bool => !AnalisisPresentacion::esActas($enlace)
            ));
        }
        $totalDocumentos = 0;
        foreach ($enlacesPresentacion as &$enlace) {
            if (AnalisisPresentacion::esActas($enlace)) {
                continue;
            }
            $enlace['url_descarga'] = AnalisisPresentacion::urlDescarga($enlace['url']);
            $enlace['tipo_documento'] = AnalisisPresentacion::tipoDocumento($enlace['url']);
            $totalDocumentos++;
        }
        unset($enlace);
        // "Usuario"/"Repositorio" son de solo lectura: sin controles de edición.
        $puedeEditarPresentacion = $acceso['clase'] === 'superadmin' && $vista === 'tiempo_real';
        // El mensaje que explica la sección: misma estructura que Mensajes globales (Configuraciones),
        // con su propia audiencia — se edita desde ahí, acá solo se muestra.
        $mensajesPresentacion = (new MensajeGlobal())->listar('analisis_presentacion');

        $tab = 'presentacion';
        $error = $_SESSION['analisis_flash_error'] ?? '';
        $exito = $_SESSION['analisis_flash_exito'] ?? '';
        unset($_SESSION['analisis_flash_error'], $_SESSION['analisis_flash_exito']);
        $tituloPagina = 'Análisis · Documentos';

        require __DIR__ . '/../vista/analisis/index.php';
    }

    /**
     * Lee un campo de enlace/miniatura del POST. La vista los manda en hexadecimal ("<campo>_hex")
     * porque el ModSecurity del hosting bloquea con 406 Not Acceptable, antes de que llegue a PHP,
     * un POST que trae el código <iframe> de "Insertar" o una URL de imagen en crudo — y también en
     * base64 (reconoce "aHR0cHM6Ly", "https://" codificado); en hex pasa. Si no viene codificado
     * (JS apagado), cae al campo tal cual.
     */
    private function campoPresentacionPost(string $campo): string
    {
        $hex = (string) ($_POST[$campo . '_hex'] ?? '');
        if ($hex !== '' && ctype_xdigit($hex) && strlen($hex) % 2 === 0) {
            return (string) hex2bin($hex);
        }

        return (string) ($_POST[$campo] ?? '');
    }

    /** POST del superadmin (los demás no llegan aquí: ver aplicarRestriccionesNoSuperadmin()). */
    private function guardarEnlacePresentacion(): string
    {
        $nombre = trim((string) ($_POST['nombre_enlace'] ?? ''));
        $url = AnalisisPresentacion::normalizarEnlace($this->campoPresentacionPost('url_enlace'));
        $id = (int) ($_POST['id'] ?? 0);

        if ($nombre === '') {
            return 'Ponle un nombre al enlace.';
        }

        if ($url === null) {
            return 'Pega un enlace de OneDrive válido (https://1drv.ms/..., https://onedrive.live.com/... o de SharePoint), o el código "Insertar" de OneDrive.';
        }

        // Miniatura: opcional, una imagen puesta a mano en vez del ícono por tipo — se muestra tal
        // cual en el navegador de quien la ve (sin tocar el servidor), por eso solo valida que sea
        // una URL https bien formada, no que el host esté en una lista como el enlace del documento.
        $miniaturaTexto = trim($this->campoPresentacionPost('miniatura_enlace'));
        $miniaturaUrl = null;
        if ($miniaturaTexto !== '') {
            $partesMiniatura = parse_url($miniaturaTexto);
            if (!$partesMiniatura || ($partesMiniatura['scheme'] ?? '') !== 'https' || empty($partesMiniatura['host'])) {
                return 'La URL de la miniatura debe ser una dirección https:// válida.';
            }
            $miniaturaUrl = $miniaturaTexto;
        }

        $modelo = new AnalisisPresentacion();

        if ($id > 0) {
            $existente = $modelo->obtenerPorId($id);
            if ($existente === null) {
                return 'El enlace que intentas editar ya no existe.';
            }
            // Defensa en profundidad: la vista no le dibuja campos editables a la tarjeta de Actas,
            // pero por si acaso llega un POST armado a mano.
            if (AnalisisPresentacion::esActas($existente)) {
                return 'La tarjeta de Actas no se edita desde aquí.';
            }

            $modelo->actualizar($id, $nombre, $url, $miniaturaUrl, (int) $_SESSION['usuario_id']);
        } else {
            $modelo->crear($nombre, $url, $miniaturaUrl, (int) $_SESSION['usuario_id']);
        }

        return '';
    }

    private function eliminarEnlacePresentacion(): string
    {
        $id = (int) ($_POST['id'] ?? 0);
        $modelo = new AnalisisPresentacion();
        $existente = $id > 0 ? $modelo->obtenerPorId($id) : null;

        if ($existente === null) {
            return 'El enlace que intentas eliminar ya no existe.';
        }
        if (AnalisisPresentacion::esActas($existente)) {
            return 'La tarjeta de Actas no se puede eliminar.';
        }

        $modelo->eliminar($id);

        return '';
    }

    private function moverEnlacePresentacion(): string
    {
        $id = (int) ($_POST['id'] ?? 0);
        $direccion = ($_POST['direccion'] ?? '') === 'arriba' ? 'arriba' : 'abajo';

        (new AnalisisPresentacion())->mover($id, $direccion);

        return '';
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
     * Cuadre por "Código PP" entre "Proyectos" (tipo 'proyecto') y Programación presupuestal
     * Egresos — solo informativo (nunca bloquea guardar/importar): las filas de Proyectos que
     * traen el mismo Código PP (ej. '1.6.0') se suman (año vigente) y se comparan contra la línea
     * de Egresos con ese código, en cualquier nivel. $arbolDatos ya viene remapeado por clave
     * ('vigente', no el año literal — ver remapArbolPresupuesto()); Egresos se lee crudo (por
     * año literal) porque aquí solo hace falta el año vigente, no todo el remapeo de columnas.
     */
    private function calcularCuadreProyectos(array $arbolDatos, int $anioVigenteNumero): array
    {
        $egresosPorCodigo = [];
        foreach ($this->aplanarArbolPresupuesto((new PresupuestoInstitucional())->obtenerArbolConValores('egreso', [$anioVigenteNumero])) as $nodoEgreso) {
            $egresosPorCodigo[$nodoEgreso['codigo']] = $nodoEgreso;
        }

        $totalProyectosPorCodigoPp = [];
        foreach ($this->aplanarArbolPresupuesto($arbolDatos) as $nodoProyecto) {
            $codigoPp = (string) ($nodoProyecto['codigoPp'] ?? '');
            if ($codigoPp === '') {
                continue;
            }
            $totalProyectosPorCodigoPp[$codigoPp] = ($totalProyectosPorCodigoPp[$codigoPp] ?? 0.0) + (float) ($nodoProyecto['valores']['vigente'] ?? 0.0);
        }

        $cuadre = [];
        foreach ($totalProyectosPorCodigoPp as $codigoPp => $totalProyectos) {
            $nodoEgreso = $egresosPorCodigo[$codigoPp] ?? null;
            $totalPresupuesto = $nodoEgreso !== null ? (float) ($nodoEgreso['valores'][$anioVigenteNumero] ?? 0.0) : null;
            $cuadre[] = [
                'codigo' => (string) $codigoPp,
                'descripcion' => $nodoEgreso['etiqueta'] ?? '',
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
            'anterior_inicial' => $anioAnteriorNumero,
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
                $valoresPorClave[$clave] = match ($clave) {
                    'anterior_corte' => (float) ($nodo['valorCorte'] ?? 0.0),
                    'anterior_inicial' => (float) ($nodo['valorInicial'] ?? 0.0),
                    default => (float) ($nodo['valores'][$anio] ?? 0.0),
                };
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
        $exito = $_SESSION['analisis_flash_exito'] ?? '';
        unset($_SESSION['analisis_flash_error'], $_SESSION['analisis_flash_exito']);

        $totalesPorModulo = [];
        foreach (self::ORIGENES_ANALISIS as $origenModulo) {
            $totalesPorModulo[$origenModulo] = $anioPresupuestalId > 0
                ? $this->obtenerTotalModulo($origenModulo, $anioPresupuestalId, $vista, $snapshotIdActual, $dependenciaFiltroActual)
                : 0.0;
        }

        [$techosPorModulo, $asignacionPorModulo, $distribucionPorModulo] = $this->obtenerIndicadoresSelector(
            $anioActivo, $vista, $snapshotIdActual, $dependenciaFiltroActual, $dependenciasTodas, $totalesPorModulo
        );

        // Sección Presupuesto del selector: Ingresos (código de PP elegido por el SA) e Inversiones.
        $tarjetasPresupuesto = $this->obtenerTarjetasPresupuesto($vista, $anioActivo);
        $puedeConfigurarIngresosPp = $this->esSuperAdmin() && $vista !== 'usuario';
        $lineasIngresosPp = $puedeConfigurarIngresosPp
            ? (new PresupuestoInstitucional())->resumenPorCodigo('ingreso', (int) $tarjetasPresupuesto['anio'])['lineas']
            : [];

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

        // Módulos de gasto: mismas 9 columnas visibles por defecto que en Peticiones
        // (PeticionesControlador::COLUMNAS_GASTO_VISIBLES); el resto (incluidas "Tipo" y
        // "Autogestión") queda oculto de entrada. Monitores/ARL no ocultan nada.
        $indicesOcultosPorDefecto = in_array($origenActivo, ['gasto_principal', 'gasto_extension', 'gasto_postgrado', 'gasto_unisalud'], true)
            ? PeticionesControlador::indicesOcultosGasto($clavesFila)
            : [];

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
        // Los gastos fijos (Configuraciones > Gastos fijos) van por fuera del techo: ya suman en el
        // avance del módulo Gasto (ver obtenerFilasCrudasModulo()), así que también amplían la base
        // del 100 % — si no, solo por tenerlos la tarjeta se pasaría del techo. Mismo alcance que el
        // avance: año, snapshot (Repositorio) y dependencia (Usuario).
        $fijosGasto = $this->obtenerGastosFijosParaAnalisis($anioId, $vista, $snapshotId);
        if ($vista === 'usuario' && $dependenciaFiltro !== null) {
            $permitidasFijos = $this->obtenerDependenciasPermitidas($dependenciaFiltro);
            $fijosGasto = array_filter($fijosGasto, static fn (array $fila): bool => in_array($fila['dependencia'] ?? null, $permitidasFijos, true));
        }
        $totalFijosGasto = array_sum(array_map(static fn (array $fila): float => (float) ($fila['valor_total'] ?? 0), $fijosGasto));
        $baseGasto = $techoGasto + $totalFijosGasto;

        if ($baseGasto > 0) {
            // La tarjeta muestra Gastos + los egresos tipo "gasto" de Extensión/Postgrado, pero estos se
            // financian con los ingresos de su módulo, no con este techo: la barra y el % solo miden lo
            // propio de Gastos (gasto principal + gastos fijos).
            $porcionAutogestion = $this->totalGastoAutogestionEnGastos($anioId, $vista, $snapshotId, $dependenciaFiltro);
            $egresosGasto = (float) ($totalesPorModulo['gasto_principal'] ?? 0) - $porcionAutogestion;
            $etiquetaBase = $totalFijosGasto > 0 ? $etiquetaGasto . ' + gastos fijos' : $etiquetaGasto;
            $techos['gasto_principal'] = ['avance' => $egresosGasto, 'techo' => $baseGasto, 'etiqueta' => $etiquetaBase];
            $asignacion['gasto_principal'] = [
                'porcentaje' => $egresosGasto / $baseGasto * 100,
                // Se compara al centavo, no el porcentaje: sumar muchos montos en float deja
                // residuos (ej. 100,0000000001 %) que pintaban en rojo un 100,0 exacto.
                'excedido' => round($egresosGasto, 2) > round($baseGasto, 2),
                'titulo' => 'Egresos asignados del ' . $etiquetaBase . ': ' . $monedaCorta($egresosGasto) . ' de ' . $monedaCorta($baseGasto)
                    . ($totalFijosGasto > 0 ? ' (' . $etiquetaGasto . ' ' . $monedaCorta($techoGasto) . ' + gastos fijos ' . $monedaCorta($totalFijosGasto) . ')' : '')
                    . ($porcionAutogestion > 0 ? '. No incluye ' . $monedaCorta($porcionAutogestion) . ' de gastos de Extensión/Postgrado (van con los ingresos de su módulo)' : ''),
            ];
        }

        foreach (self::INGRESOS_AUTOGESTION_SELECTOR as $origenEgresos => $origenIngresos) {
            $egresos = (float) ($totalesPorModulo[$origenEgresos] ?? 0);
            $ingresos = $totalesPorModulo[$origenIngresos]
                ?? $this->obtenerTotalModulo($origenIngresos, $anioId, $vista, $snapshotId, $dependenciaFiltro);
            if ($ingresos > 0) {
                $asignacion[$origenEgresos] = [
                    'porcentaje' => $egresos / $ingresos * 100,
                    'excedido' => round($egresos, 2) > round((float) $ingresos, 2),
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
            $snapshotSolicitado = (int) ($_GET['snapshot_id'] ?? 0);

            // Solo el superadmin elige el snapshot; los demás ven el que él dejó marcado como
            // activo (o el más reciente, si todavía no ha marcado ninguno) — mismo mecanismo que
            // las versiones de Programación presupuestal/Proyectos/Articulación PDI.
            if ($snapshotSolicitado > 0 && AccesoAnalisis::actual()['clase'] === 'superadmin') {
                (new Snapshot())->marcarActiva($snapshotSolicitado);
                $snapshotId = $snapshotSolicitado;
            } else {
                $snapshotActivo = (new Snapshot())->obtenerActiva();
                $snapshotId = $snapshotActivo !== null ? (int) $snapshotActivo['id'] : 0;
            }

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
            // Monitores y ARL ya no tienen tarjeta en el selector: tampoco van en el Excel.
            if (in_array($origen, ['monitores', 'arl'], true)) {
                continue;
            }
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

            // Gastos ya incluye los egresos tipo "gasto" de Extensión/Postgrado, que también suman en
            // sus propias hojas: en el total general se cuentan una sola vez.
            if ($origen === 'gasto_principal' && $anioPresupuestalId > 0) {
                $totalEgresos -= $this->totalGastoAutogestionEnGastos($anioPresupuestalId, $vista, $snapshotId, $dependenciaFiltro);
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

    private function formatoMonedaExportar(float $valor, int $decimales = 2): string
    {
        return number_format($valor, $decimales, ',', '.');
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
            // Mismo criterio que Tiempo real (filtrarSoloActivos()), pero con lo congelado en el snapshot:
            // sin borradores ni lo que en ese momento estaba archivado o en Expediente.
            $filas = $this->filtrarSoloActivosDeSnapshot($origen, $snapshotId, $filas);

            if ($origen === 'gasto_principal') {
                $filas = array_values(array_filter(
                    $filas,
                    static fn (array $fila): bool => ($fila['tipo_automatico'] ?? null) !== 'techo_hijo'
                ));
            }

            // Monitores: las filas crudas del snapshot pueden traer `valor` vacío (solicitudes de antes
            // de esa columna); en vivo SolicitudMonitor::obtenerPorAnio() lo recalcula y aquí no pasaba
            // por ahí, así que el módulo salía en $0 / "Sin registros". Mismo respaldo que en vivo.
            if ($origen === 'monitores') {
                $modeloMonitor = new SolicitudMonitor();
                $filas = array_map(static fn (array $fila): array => $modeloMonitor->aplicarRespaldoValor($fila), $filas);
            }
        } else {
            // Datos en vivo (Tiempo real/Usuario, nunca Repositorio — un snapshot congelado ya es
            // su propia verdad histórica): ver filtrarSoloActivos().
            $filas = $this->filtrarSoloActivos($origen, $this->obtenerModeloModulo($origen)->obtenerPorAnio($anioPresupuestalId));
        }

        // Solo aquí (Análisis de distribución) entran los gastos fijos, dentro del módulo Gasto.
        if ($origen === 'gasto_principal') {
            $filas = array_merge($filas, $this->obtenerGastosFijosParaAnalisis($anioPresupuestalId, $vista, $snapshotId));
            // Y los egresos de tipo "gasto" de Extensión y Postgrado (marcados con __origen).
            $filas = array_merge($filas, $this->obtenerGastosAutogestionTipoGasto($anioPresupuestalId, $vista, $snapshotId));
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

    /** Suma de los egresos de Extensión/Postgrado cargados dentro de Gastos (ver obtenerGastosAutogestionTipoGasto()). */
    private function totalGastoAutogestionEnGastos(int $anioPresupuestalId, string $vista, ?int $snapshotId, ?string $dependenciaFiltro): float
    {
        $total = 0.0;
        foreach ($this->obtenerFilasCrudasModulo('gasto_principal', $anioPresupuestalId, $vista, $snapshotId, $dependenciaFiltro) as $fila) {
            if (isset($fila['__origen'])) {
                $total += $this->obtenerValorFilaModulo('gasto_principal', $fila);
            }
        }

        return $total;
    }

    /**
     * Egresos de Extensión y Postgrado de tipo "gasto" (categoría "Gastos" o que empieza por
     * "Costos", el mismo criterio de esos módulos) para cargarlos también en el landing de Gastos, por
     * la misma ruta de cada vista (en vivo solo lo activo; snapshot en Repositorio). El filtro de
     * dependencia (Usuario) lo aplica quien llama. Cada fila lleva '__origen' con su módulo real.
     */
    private function obtenerGastosAutogestionTipoGasto(int $anioPresupuestalId, string $vista, ?int $snapshotId): array
    {
        $filas = [];
        foreach (array_keys(self::DESPLAZAMIENTO_ID_GASTO_AUTOGESTION) as $origenAutogestion) {
            foreach ($this->obtenerFilasCrudasModulo($origenAutogestion, $anioPresupuestalId, $vista, $snapshotId, null) as $fila) {
                $categoria = (string) ($fila['categoria'] ?? '');
                if ($categoria === 'Gastos' || str_starts_with($categoria, 'Costos')) {
                    $fila['__origen'] = $origenAutogestion;
                    $filas[] = $fila;
                }
            }
        }

        return $filas;
    }

    /**
     * "Solo lo activo", el mismo criterio en todas las pestañas que leen Gasto/Autogestión en vivo
     * (Análisis de distribución, Articulación PDI, Techos y Metas): nunca borrador (su dueño
     * todavía lo puede cambiar, no es definitivo) ni lo que ya se archivó o se mandó a Expediente
     * en Peticiones. $origen es una clave de PeticionesControlador::TABLAS_ORIGEN (ej.
     * 'gasto_principal', 'gasto_extension') — null si las filas no pasan por Peticiones.
     */
    private function filtrarSoloActivos(?string $origen, array $filas): array
    {
        $filas = array_values(array_filter(
            $filas,
            static fn (array $fila): bool => ($fila['estado'] ?? null) !== 'borrador'
        ));

        if ($origen === null) {
            return $filas;
        }

        $idsExcluidos = $this->obtenerIdsArchivadosOExpediente($origen);
        if (empty($idsExcluidos)) {
            return $filas;
        }

        return array_values(array_filter(
            $filas,
            static fn (array $fila): bool => !isset($idsExcluidos[(int) $fila['id']])
        ));
    }

    /** Cache por snapshot de sus acciones de Peticiones ("origen:id" => acción). */
    private array $cacheAccionesSnapshot = [];

    /**
     * filtrarSoloActivos() para filas de un snapshot (Repositorio): sin borradores ni lo que estaba
     * "archivada"/"expediente" según la tabla peticiones_archivadas congelada en ESE snapshot.
     */
    private function filtrarSoloActivosDeSnapshot(string $origen, int $snapshotId, array $filas): array
    {
        $filas = array_values(array_filter(
            $filas,
            static fn (array $fila): bool => ($fila['estado'] ?? null) !== 'borrador'
        ));

        if (!isset($this->cacheAccionesSnapshot[$snapshotId])) {
            $acciones = [];
            foreach ((new Snapshot())->obtenerTablaDeSnapshot($snapshotId, 'peticiones_archivadas') as $archivada) {
                $acciones[($archivada['origen'] ?? '') . ':' . ($archivada['origen_id'] ?? '')] = $archivada['accion'] ?? '';
            }
            $this->cacheAccionesSnapshot[$snapshotId] = $acciones;
        }
        $acciones = $this->cacheAccionesSnapshot[$snapshotId];

        return array_values(array_filter(
            $filas,
            static fn (array $fila): bool => !in_array($acciones[$origen . ':' . ($fila['id'] ?? '')] ?? null, ['archivada', 'expediente'], true)
        ));
    }

    /**
     * IDs (origen_id) de un origen que ya están "archivada" o "expediente" en Peticiones — ver
     * filtrarSoloActivos(). Una sola consulta por request (vía $this->cacheAccionesPorClave),
     * reutilizada por todas las pestañas/módulos que la necesiten.
     */
    private function obtenerIdsArchivadosOExpediente(string $origen): array
    {
        if ($this->cacheAccionesPorClave === null) {
            $this->cacheAccionesPorClave = (new PeticionArchivada())->obtenerAccionesPorClave();
        }

        $ids = [];
        foreach ($this->cacheAccionesPorClave as $clave => $accion) {
            if (!in_array($accion, ['archivada', 'expediente'], true)) {
                continue;
            }

            [$claveOrigen, $claveId] = explode(':', $clave, 2);
            if ($claveOrigen === $origen) {
                $ids[(int) $claveId] = true;
            }
        }

        return $ids;
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
            // En Gastos, los egresos de Extensión/Postgrado van con su origen real (rutas, categoría...).
            $origenFila = $fila['__origen'] ?? $origen;
            $itemsCrudos[] = [
                'origen' => $origenFila,
                'origen_id' => $id,
                'tipo' => self::ETIQUETAS_ORIGEN[$origenFila],
                'detalle' => $this->obtenerDependenciaFilaModulo($origenFila, $fila) ?? '',
                'cantidad' => $fila['cantidad'] ?? null,
                'valor' => $this->obtenerValorFilaModulo($origenFila, $fila),
                'ruta_origen' => 'index.php?ruta=analisis&tab=analisis',
            ];
            $registrosPrecargados[$origenFila . ':' . $id] = $fila;
        }

        $peticiones = new PeticionesControlador();
        $peticiones->establecerRegistrosPrecargados($registrosPrecargados);
        $resultado = $peticiones->construirFilasPorOrigen($origen, $itemsCrudos, $anioPresupuestalId, 'analisis');

        // Solo en Análisis de distribución: los egresos de Autogestión llevan además a qué
        // autogestión pertenecen (Alquiler, Educación continua...), justo después de Dependencia
        // — los ingresos ya se ven por ella. Se resuelve por autogestion_id contra la lista
        // completa (incluye inactivas), porque las filas de un snapshot (Repositorio) no traen el
        // nombre unido como sí lo trae obtenerPorAnio() en vivo.
        if (isset(self::MODULO_AUTOGESTION_POR_EGRESO[$origen])) {
            $nombresAutogestion = array_column((new AutogestionItem())->obtenerTodos(self::MODULO_AUTOGESTION_POR_EGRESO[$origen]), 'nombre', 'id');
            $posicion = array_search('dependencia', $resultado['claves'], true);
            $posicion = $posicion === false ? 0 : $posicion + 1;

            array_splice($resultado['columnas'], $posicion, 0, ['Autogestión']);
            array_splice($resultado['claves'], $posicion, 0, ['autogestion']);

            foreach ($resultado['filas'] as &$fila) {
                $registro = $registrosPrecargados[$origen . ':' . (int) $fila['origen_id']] ?? [];
                $fila['autogestion'] = $registro['autogestion_nombre']
                    ?? $nombresAutogestion[(int) ($registro['autogestion_id'] ?? 0)]
                    ?? '—';
            }
            unset($fila);
        }

        // Gasto: columna "Tipo" (Gasto / Gasto fijo) justo después de Dependencia. Un gasto fijo
        // se edita en Configuraciones > Gastos fijos (solo SA), nunca en el módulo Gastos.
        if ($origen === 'gasto_principal') {
            $posicion = array_search('dependencia', $resultado['claves'], true);
            $posicion = $posicion === false ? 0 : $posicion + 1;
            array_splice($resultado['columnas'], $posicion, 0, ['Tipo']);
            array_splice($resultado['claves'], $posicion, 0, ['tipo_gasto']);
            $puedeEditarFijos = $this->esSuperAdmin();

            foreach ($resultado['filas'] as &$fila) {
                $origenFila = (string) ($fila['origen'] ?? $origen);
                if (isset(self::DESPLAZAMIENTO_ID_GASTO_AUTOGESTION[$origenFila])) {
                    // Egreso de Extensión/Postgrado: Tipo = su módulo; id desplazado para la tabla real.
                    $fila['tipo_gasto'] = self::ETIQUETAS_ORIGEN[$origenFila];
                    $fila['origen_id'] = self::DESPLAZAMIENTO_ID_GASTO_AUTOGESTION[$origenFila] + (int) $fila['origen_id'];
                    continue;
                }
                $registro = $registrosPrecargados[$origen . ':' . (int) $fila['origen_id']] ?? [];
                $esFijo = !empty($registro['es_gasto_fijo']);
                $fila['tipo_gasto'] = $esFijo ? 'Gasto fijo' : 'Gasto';
                if ($esFijo) {
                    $fila['ruta_editar'] = 'index.php?ruta=gastos-fijos&anio_id=' . $anioPresupuestalId . '&editar_id=' . (int) $registro['id_gasto_fijo'];
                    $fila['puede_editar'] = $puedeEditarFijos;
                }
            }
            unset($fila);
        }

        if ($vista !== 'tiempo_real') {
            foreach ($resultado['filas'] as &$fila) {
                $fila['puede_editar'] = false;
            }
            unset($fila);
        }

        return $resultado;
    }

    /**
     * Gastos fijos del año (Configuraciones > Gastos fijos) como filas del módulo Gasto, SOLO para
     * Análisis de distribución. En vivo salen de GastoFijo; en Repositorio, de la tabla
     * gastos_fijos congelada en el snapshot. Llevan id NEGATIVO (−id) para no chocar con los ids de
     * `gastos` en la caché "gasto_principal:<id>", Editar y Eliminar; `id_gasto_fijo` guarda el real.
     * Si la tabla todavía no existe (instalar.php pendiente), no aporta filas en vez de romper.
     */
    private function obtenerGastosFijosParaAnalisis(int $anioPresupuestalId, string $vista, ?int $snapshotId): array
    {
        if ($vista === 'repositorio') {
            $filas = $snapshotId !== null
                ? array_values(array_filter(
                    (new Snapshot())->obtenerTablaDeSnapshot($snapshotId, 'gastos_fijos'),
                    static fn (array $fila): bool => (int) ($fila['anio_presupuestal_id'] ?? 0) === $anioPresupuestalId
                ))
                : [];
        } else {
            try {
                $filas = (new GastoFijo())->obtenerPorAnio($anioPresupuestalId);
            } catch (PDOException $excepcion) {
                $filas = [];
            }
        }

        return array_map(static function (array $fila): array {
            $fila['id_gasto_fijo'] = (int) $fila['id'];
            $fila['id'] = -(int) $fila['id'];
            $fila['es_gasto_fijo'] = true;

            return $fila;
        }, $filas);
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

        // "Año anterior (Inicial)" va junto a Final y a corte. La importación ubica esa columna por su
        // encabezado (ver ubicarColumnaInicialPlantilla()), así que las plantillas descargadas antes
        // (sin ella, o con ella al final) se siguen leyendo bien.
        $encabezados = ['Código', 'Descripción', 'Proyecto(s) PDI', (string) $anioVigenteNumero, 'Año anterior (Final)', 'Año anterior (a corte)', 'Año anterior (Inicial)'];
        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $encabezados[] = (string) ($anioVigenteNumero - $desplazamiento);
        }
        $encabezados[] = 'Indexación';

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
                    $nodo['valorInicial'] !== null ? $this->formatoNumeroExportar($nodo['valorInicial']) : '',
                ];
                for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
                    $fila[] = $this->formatoNumeroExportar($nodo['valores'][$anioVigenteNumero - $desplazamiento] ?? 0.0);
                }
                $fila[] = $nodo['esTotal'] ? '' : ($nodo['indexacion'] ?? '');
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
        $encabezadoEgresos = array_shift($filasEgresos) ?? [];
        $encabezadoIngresos = array_shift($filasIngresos) ?? [];

        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? null;
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');

        $errores = [];
        $lineasEgreso = $this->validarFilasPresupuesto('Egresos', $filasEgresos, true, $anioVigenteNumero, $errores, $encabezadoEgresos);
        $lineasIngreso = $this->validarFilasPresupuesto('Ingresos', $filasIngresos, false, $anioVigenteNumero, $errores, $encabezadoIngresos);

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
    /**
     * Índice de la columna "Año anterior (Inicial)" en el encabezado de una hoja de la plantilla de PP,
     * o null si la plantilla no la trae (descargada antes de existir esa columna).
     */
    private function ubicarColumnaInicialPlantilla(array $encabezado): ?int
    {
        foreach ($encabezado as $indice => $titulo) {
            if (trim((string) $titulo) === 'Año anterior (Inicial)') {
                return (int) $indice;
            }
        }

        return null;
    }

    private function validarFilasPresupuesto(string $nombreHoja, array $filas, bool $permitirProyectos, int $anioVigenteNumero, array &$errores, array $encabezado = []): array
    {
        $anioAnteriorNumero = $anioVigenteNumero - 1;
        $modeloProyecto = new Proyecto();
        $lineasValidas = [];

        // 6 columnas fijas (índices 0-5: Código, Descripción, Proyecto(s) PDI, vigente, Año
        // anterior Final, Año anterior a corte) — los históricos empiezan justo después, en el
        // índice 6, no en el 7 (antes de quitar "Fecha de corte" había 7 columnas fijas, pero el
        // desplazamiento seguía sumando como si hubiera 8 — un año histórico se leía siempre una
        // columna corrida hacia la derecha de la que en verdad le correspondía).
        // "Año anterior (Inicial)" se ubica por su encabezado: en la plantilla actual va junto a
        // Final y a corte (índice 6) y corre una posición los históricos y la Indexación; en una
        // plantilla anterior no está (o está al final, después de Indexación) y no corre nada.
        // Vacía = no cambia el inicial ya guardado.
        $indiceInicial = $this->ubicarColumnaInicialPlantilla($encabezado);
        $corrimiento = $indiceInicial === 6 ? 1 : 0;
        $indicesHistoricos = [];
        for ($desplazamiento = 2; $desplazamiento <= 5; $desplazamiento++) {
            $indicesHistoricos[4 + $corrimiento + $desplazamiento] = $anioVigenteNumero - $desplazamiento;
        }
        // Después de los 4 históricos: el criterio de indexación (texto libre — "IPC", "SMMLV",
        // "ICES"...), solo para hojas (nunca una fila total).
        $indiceIndexacion = 6 + $corrimiento + count($indicesHistoricos);

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

            $crudoInicial = $indiceInicial !== null ? trim((string) ($fila[$indiceInicial] ?? '')) : '';
            if ($crudoInicial !== '' && !is_numeric($crudoInicial)) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: \"Año anterior (Inicial)\" no es un número válido.";
                $filaValida = false;
            }
            $valores[$anioAnteriorNumero]['valor_inicial'] = $crudoInicial !== '' && is_numeric($crudoInicial) ? (float) $crudoInicial : null;

            $indexacion = trim((string) ($fila[$indiceIndexacion] ?? ''));
            if ($indexacion !== '' && $esTotal) {
                $errores[] = "$nombreHoja, fila $numeroFilaExcel: una fila total (termina en \".0\") no puede tener Indexación.";
                $filaValida = false;
            }

            if (!$filaValida) {
                continue;
            }

            $lineasValidas[] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'proyectos_ids' => $proyectosIds,
                'indexacion' => $indexacion !== '' ? $indexacion : null,
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
        // Solo el año vigente (sin año anterior ni históricos). Fuentes de financiación: códigos
        // de Egresos de Programación presupuestal separados por ";". Código PP: código de
        // Egresos (ej. '1.6.0') contra el que cuadra esta fila (ver calcularCuadreProyectos()).
        $encabezados = ['Código', 'Descripción', 'Detalle', 'Fuentes de financiación', (string) $anioVigenteNumero, 'Código PP'];

        $planas = $this->aplanarArbolPresupuesto((new PresupuestoInstitucional())->obtenerArbolConValores('proyecto', [$anioVigenteNumero]));

        $filas = [];
        foreach ($planas as $nodo) {
            $filas[] = [
                $nodo['codigo'],
                $nodo['etiqueta'],
                $nodo['detalle'] ?? '',
                $nodo['fuentesCodigos'] ?? '',
                $this->formatoNumeroExportar($nodo['valores'][$anioVigenteNumero] ?? 0.0),
                $nodo['codigoPp'] ?? '',
            ];
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

        // Una plantilla anterior (con año anterior e históricos) leería un valor como Código PP.
        $encabezado = array_shift($filas) ?? [];
        if (trim((string) ($encabezado[5] ?? '')) !== 'Código PP') {
            return 'Esta plantilla es de un formato anterior. Usa el botón "Plantilla" para descargar una plantilla nueva.';
        }

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
     * Igual espíritu que validarFilasPresupuesto(), pero para la hoja única de "Proyectos": 6
     * columnas (índices 0-5: Código, Descripción, Detalle, Fuentes de financiación, vigente,
     * Código PP — sin Proyecto(s) PDI ni años anteriores) — verificado a mano contra
     * exportarPlantillaProyectos(). Solo toca el año vigente: los valores de otros años que ya
     * estén guardados no se modifican.
     */
    private function validarFilasProyectos(array $filas, int $anioVigenteNumero, array &$errores): array
    {
        $lineasValidas = [];
        $codigosEgreso = (new PresupuestoInstitucional())->nombresPorCodigo('egreso');

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

            $detalle = trim((string) ($fila[2] ?? ''));

            $fuentes = [];
            foreach (explode(';', (string) ($fila[3] ?? '')) as $codigoFuente) {
                $codigoFuente = trim($codigoFuente);
                if ($codigoFuente === '') {
                    continue;
                }
                if (!isset($codigosEgreso[$codigoFuente])) {
                    $errores[] = "Proyectos, fila $numeroFilaExcel: la fuente de financiación \"$codigoFuente\" no existe en Programación presupuestal (Egresos).";
                    $filaValida = false;
                    continue;
                }
                $fuentes[] = $codigoFuente;
            }

            $crudoVigente = trim((string) ($fila[4] ?? ''));
            if ($crudoVigente !== '' && !is_numeric($crudoVigente)) {
                $errores[] = "Proyectos, fila $numeroFilaExcel: \"$anioVigenteNumero\" no es un número válido.";
                $filaValida = false;
            }
            $valores[$anioVigenteNumero] = ['valor_final' => $crudoVigente !== '' && is_numeric($crudoVigente) ? (float) $crudoVigente : 0.0];

            $codigoPp = trim((string) ($fila[5] ?? ''));
            if ($codigoPp !== '' && !preg_match('/^\d+(\.\d+)*$/', $codigoPp)) {
                $errores[] = "Proyectos, fila $numeroFilaExcel: Código PP inválido \"$codigoPp\".";
                $filaValida = false;
            }

            if (!$filaValida) {
                continue;
            }

            $lineasValidas[] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'proyectos_ids' => [],
                'codigo_pp' => $codigoPp !== '' ? $codigoPp : null,
                'detalle' => $detalle !== '' ? $detalle : null,
                'fuentes_financiacion' => !empty($fuentes) ? implode(';', $fuentes) : null,
                'valores' => $valores,
            ];
        }

        return $lineasValidas;
    }
}
