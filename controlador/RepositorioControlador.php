<?php

require_once __DIR__ . '/../modelo/Snapshot.php';
require_once __DIR__ . '/../modelo/VersionArbol.php';
require_once __DIR__ . '/../modelo/PresupuestoInstitucional.php';
require_once __DIR__ . '/../modelo/VersionTechos.php';
require_once __DIR__ . '/../modelo/VersionDocumentos.php';
require_once __DIR__ . '/../modelo/Usuario.php';

/**
 * ?ruta=repositorios (solo `usuarios.es_super_admin`, gate ya existente — no confundir con
 * `es_raiz_superadmin` que usa ?ruta=analisis): un solo lugar para CREAR cualquier tipo de
 * snapshot/versión que la plataforma sepa congelar — el snapshot de BD completa de siempre
 * (Snapshot), el Árbol PDI (VersionArbol), el presupuesto institucional de Programación
 * presupuestal y Proyectos (PresupuestoInstitucional, con tipo 'egreso'/'ingreso'/'proyecto'),
 * Techos y Metas (VersionTechos) y Documentos (VersionDocumentos) — 7 mecanismos en total, cada
 * uno con su propia tabla/modelo, totalmente independientes entre sí (a propósito: ninguno
 * comparte código con otro, aunque tengan forma casi idéntica). ?ruta=analisis ya NO tiene botón
 * para crear versiones — solo el selector de cuál usar para mostrar la información; crearlas se
 * hace exclusivamente aquí. La tabla resumen de arriba de la página permite cambiar la activa de
 * cualquiera de los 7 sin entrar a su sección de detalle (ver cambiarActiva()).
 */
class RepositorioControlador
{
    private const PESTANA_ARBOL_PDI = 'articulacion_pdi';

    private Snapshot $modeloSnapshot;
    private VersionArbol $modeloVersionArbol;
    private PresupuestoInstitucional $modeloPresupuesto;
    private VersionTechos $modeloVersionTechos;
    private VersionDocumentos $modeloVersionDocumentos;

    public function __construct()
    {
        $this->modeloSnapshot = new Snapshot();
        $this->modeloVersionArbol = new VersionArbol();
        $this->modeloPresupuesto = new PresupuestoInstitucional();
        $this->modeloVersionTechos = new VersionTechos();
        $this->modeloVersionDocumentos = new VersionDocumentos();
    }

    public function index(): void
    {
        $this->requerirSuperAdmin();

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'crear') {
                [$error, $exito] = $this->crear();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminarSnapshot();
            } elseif ($accion === 'marcar_activa_snapshot') {
                [$error, $exito] = $this->marcarActivaSnapshot();
            } elseif ($accion === 'crear_arbol') {
                [$error, $exito] = $this->crearVersionArbol();
            } elseif ($accion === 'eliminar_arbol') {
                [$error, $exito] = $this->eliminarVersionArbol();
            } elseif ($accion === 'marcar_activa_arbol') {
                [$error, $exito] = $this->marcarActivaArbol();
            } elseif ($accion === 'crear_presupuesto') {
                [$error, $exito] = $this->crearVersionPresupuesto();
            } elseif ($accion === 'eliminar_presupuesto') {
                [$error, $exito] = $this->eliminarVersionPresupuesto();
            } elseif ($accion === 'marcar_activa_presupuesto') {
                [$error, $exito] = $this->marcarActivaPresupuesto();
            } elseif ($accion === 'crear_proyectos_presupuesto') {
                [$error, $exito] = $this->crearVersionProyectos();
            } elseif ($accion === 'eliminar_proyectos_presupuesto') {
                [$error, $exito] = $this->eliminarVersionProyectos();
            } elseif ($accion === 'marcar_activa_proyectos_presupuesto') {
                [$error, $exito] = $this->marcarActivaPresupuesto();
            } elseif ($accion === 'crear_techos') {
                [$error, $exito] = $this->crearVersionTechos();
            } elseif ($accion === 'eliminar_techos') {
                [$error, $exito] = $this->eliminarVersionTechos();
            } elseif ($accion === 'marcar_activa_techos') {
                [$error, $exito] = $this->marcarActivaTechos();
            } elseif ($accion === 'crear_documentos') {
                [$error, $exito] = $this->crearVersionDocumentos();
            } elseif ($accion === 'eliminar_documentos') {
                [$error, $exito] = $this->eliminarVersionDocumentos();
            } elseif ($accion === 'marcar_activa_documentos') {
                [$error, $exito] = $this->marcarActivaDocumentos();
            } elseif ($accion === 'cambiar_activa') {
                [$error, $exito] = $this->cambiarActiva();
            }
        }

        $snapshots = $this->modeloSnapshot->obtenerTodos();
        $versionesArbol = $this->modeloVersionArbol->obtenerTodos(self::PESTANA_ARBOL_PDI);
        $versionesEgresos = $this->modeloPresupuesto->obtenerVersiones('egreso');
        $versionesIngresos = $this->modeloPresupuesto->obtenerVersiones('ingreso');
        $versionesProyectos = $this->modeloPresupuesto->obtenerVersiones('proyecto');
        $versionesTechos = $this->modeloVersionTechos->obtenerTodos();
        $versionesDocumentos = $this->modeloVersionDocumentos->obtenerTodos();

        // Para la tabla resumen: tipo (etiqueta + clave para "cambiar_activa") + su lista de
        // versiones (para el <select>) — un solo lugar que arma esto, la vista no repite lógica.
        $resumenRepositorios = [
            ['clave' => 'snapshot', 'etiqueta' => 'Snapshots', 'versiones' => $snapshots],
            ['clave' => 'arbol', 'etiqueta' => 'Árbol PDI', 'versiones' => $versionesArbol],
            ['clave' => 'presupuesto_egreso', 'etiqueta' => 'Programación — Egresos', 'versiones' => $versionesEgresos],
            ['clave' => 'presupuesto_ingreso', 'etiqueta' => 'Programación — Ingresos', 'versiones' => $versionesIngresos],
            ['clave' => 'presupuesto_proyecto', 'etiqueta' => 'Proyectos', 'versiones' => $versionesProyectos],
            ['clave' => 'techos', 'etiqueta' => 'Techos y Metas', 'versiones' => $versionesTechos],
            ['clave' => 'documentos', 'etiqueta' => 'Documentos', 'versiones' => $versionesDocumentos],
        ];

        require __DIR__ . '/../vista/repositorios/index.php';
    }

    /**
     * Acción compartida de la tabla resumen (arriba de la página): cambia la activa de cualquiera
     * de los 7 tipos sin tener que entrar a su sección de detalle — despacha al mismo
     * marcarActiva*() que ya existe por tipo, no duplica lógica.
     */
    private function cambiarActiva(): array
    {
        $tipo = $_POST['tipo'] ?? '';
        $despacho = [
            'snapshot' => fn () => $this->marcarActivaSnapshot(),
            'arbol' => fn () => $this->marcarActivaArbol(),
            'presupuesto_egreso' => fn () => $this->marcarActivaPresupuesto(),
            'presupuesto_ingreso' => fn () => $this->marcarActivaPresupuesto(),
            'presupuesto_proyecto' => fn () => $this->marcarActivaPresupuesto(),
            'techos' => fn () => $this->marcarActivaTechos(),
            'documentos' => fn () => $this->marcarActivaDocumentos(),
        ];

        if (!isset($despacho[$tipo])) {
            return ['Tipo de repositorio no válido.', ''];
        }

        return $despacho[$tipo]();
    }

    private function crear(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $nombre = 'Snapshot ' . date('Y-m-d H:i:s');
        }

        try {
            $this->modeloSnapshot->crear($nombre, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return ['No se pudo crear el snapshot: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Snapshot "' . $nombre . '" creado correctamente.'];
    }

    private function eliminarSnapshot(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloSnapshot->obtenerPorId($id) === null) {
            return ['El snapshot que intentas eliminar no existe.', ''];
        }

        $this->modeloSnapshot->eliminar($id);

        return ['', 'Snapshot eliminado correctamente.'];
    }

    /**
     * Marcar activa desde aquí hace lo mismo que elegirla en el selector de ?ruta=analisis
     * (vista Repositorio) — mismo método del modelo, nada más que otra puerta de entrada, para no
     * tener que ir hasta Análisis solo para cambiar cuál ve Consejo Superior.
     */
    private function marcarActivaSnapshot(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $snapshot = $id > 0 ? $this->modeloSnapshot->obtenerPorId($id) : null;

        if ($snapshot === null) {
            return ['El snapshot que intentas marcar como activo ya no existe.', ''];
        }

        $this->modeloSnapshot->marcarActiva($id);

        return ['', 'Snapshot "' . $snapshot['nombre'] . '" marcado como activo.'];
    }

    private function crearVersionArbol(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $nombre = 'Árbol PDI ' . date('Y-m-d H:i:s');
        }

        try {
            $this->modeloVersionArbol->crear(self::PESTANA_ARBOL_PDI, $nombre, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return ['No se pudo crear la versión: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Versión de Árbol PDI "' . $nombre . '" creada correctamente.'];
    }

    private function eliminarVersionArbol(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloVersionArbol->obtenerPorId($id) === null) {
            return ['La versión que intentas eliminar no existe.', ''];
        }

        $this->modeloVersionArbol->eliminar($id);

        return ['', 'Versión eliminada correctamente.'];
    }

    private function marcarActivaArbol(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $version = $id > 0 ? $this->modeloVersionArbol->obtenerPorId($id) : null;

        if ($version === null) {
            return ['La versión que intentas marcar como activa ya no existe.', ''];
        }

        $this->modeloVersionArbol->marcarActiva($id, self::PESTANA_ARBOL_PDI);

        return ['', 'Versión "' . $version['nombre'] . '" marcada como activa.'];
    }

    private function crearVersionPresupuesto(): array
    {
        $tipo = ($_POST['tipo'] ?? '') === 'ingreso' ? 'ingreso' : 'egreso';
        $nombre = trim($_POST['nombre'] ?? '');
        $etiquetaLado = $tipo === 'ingreso' ? 'Ingresos' : 'Egresos';

        if ($nombre === '') {
            $nombre = $etiquetaLado . ' ' . date('Y-m-d H:i:s');
        }

        try {
            $this->modeloPresupuesto->crearVersion($tipo, $nombre, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return ['No se pudo crear la versión: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Versión de Programación presupuestal (' . $etiquetaLado . ') "' . $nombre . '" creada correctamente.'];
    }

    private function eliminarVersionPresupuesto(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloPresupuesto->obtenerVersionPorId($id) === null) {
            return ['La versión que intentas eliminar no existe.', ''];
        }

        $this->modeloPresupuesto->eliminarVersion($id);

        return ['', 'Versión eliminada correctamente.'];
    }

    /**
     * Sirve para Egresos, Ingresos y Proyectos (los tres son PresupuestoInstitucional, solo cambia
     * `tipo`) — el `tipo` sale de la propia versión, no hace falta mandarlo aparte en el formulario.
     */
    private function marcarActivaPresupuesto(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $version = $id > 0 ? $this->modeloPresupuesto->obtenerVersionPorId($id) : null;

        if ($version === null) {
            return ['La versión que intentas marcar como activa ya no existe.', ''];
        }

        $this->modeloPresupuesto->marcarVersionActiva($id, $version['tipo']);

        return ['', 'Versión "' . $version['nombre'] . '" marcada como activa.'];
    }

    /**
     * "Proyectos" reutiliza PresupuestoInstitucional con tipo='proyecto' (mismas tablas que
     * Egresos/Ingresos, propia numeración de código, propia lista de versiones) — no es un
     * tercer lado real del presupuesto institucional, ver PresupuestoInstitucional.
     */
    private function crearVersionProyectos(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $nombre = 'Proyectos ' . date('Y-m-d H:i:s');
        }

        try {
            $this->modeloPresupuesto->crearVersion('proyecto', $nombre, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return ['No se pudo crear la versión: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Versión de Proyectos "' . $nombre . '" creada correctamente.'];
    }

    private function eliminarVersionProyectos(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloPresupuesto->obtenerVersionPorId($id) === null) {
            return ['La versión que intentas eliminar no existe.', ''];
        }

        $this->modeloPresupuesto->eliminarVersion($id);

        return ['', 'Versión eliminada correctamente.'];
    }

    private function crearVersionTechos(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $nombre = 'Techos y Metas ' . date('Y-m-d H:i:s');
        }

        try {
            $this->modeloVersionTechos->crear($nombre, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return ['No se pudo crear la versión: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Versión de Techos y Metas "' . $nombre . '" creada correctamente.'];
    }

    private function eliminarVersionTechos(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloVersionTechos->obtenerPorId($id) === null) {
            return ['La versión que intentas eliminar no existe.', ''];
        }

        $this->modeloVersionTechos->eliminar($id);

        return ['', 'Versión eliminada correctamente.'];
    }

    private function marcarActivaTechos(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $version = $id > 0 ? $this->modeloVersionTechos->obtenerPorId($id) : null;

        if ($version === null) {
            return ['La versión que intentas marcar como activa ya no existe.', ''];
        }

        $this->modeloVersionTechos->marcarActiva($id);

        return ['', 'Versión "' . $version['nombre'] . '" marcada como activa.'];
    }

    private function crearVersionDocumentos(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $nombre = 'Documentos ' . date('Y-m-d H:i:s');
        }

        try {
            $this->modeloVersionDocumentos->crear($nombre, (int) $_SESSION['usuario_id']);
        } catch (Throwable $excepcion) {
            return ['No se pudo crear la versión: ' . $excepcion->getMessage(), ''];
        }

        return ['', 'Versión de Documentos "' . $nombre . '" creada correctamente.'];
    }

    private function eliminarVersionDocumentos(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloVersionDocumentos->obtenerPorId($id) === null) {
            return ['La versión que intentas eliminar no existe.', ''];
        }

        $this->modeloVersionDocumentos->eliminar($id);

        return ['', 'Versión eliminada correctamente.'];
    }

    private function marcarActivaDocumentos(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $version = $id > 0 ? $this->modeloVersionDocumentos->obtenerPorId($id) : null;

        if ($version === null) {
            return ['La versión que intentas marcar como activa ya no existe.', ''];
        }

        $this->modeloVersionDocumentos->marcarActiva($id);

        return ['', 'Versión "' . $version['nombre'] . '" marcada como activa.'];
    }

    private function requerirSuperAdmin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;

        if (!$esSuperAdmin) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }
}
