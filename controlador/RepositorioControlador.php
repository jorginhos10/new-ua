<?php

require_once __DIR__ . '/../modelo/Snapshot.php';
require_once __DIR__ . '/../modelo/VersionArbol.php';
require_once __DIR__ . '/../modelo/PresupuestoInstitucional.php';
require_once __DIR__ . '/../modelo/Usuario.php';

/**
 * ?ruta=repositorios (solo `usuarios.es_super_admin`, gate ya existente — no confundir con
 * `es_raiz_superadmin` que usa ?ruta=analisis): un solo lugar para CREAR cualquier tipo de
 * snapshot/versión que la plataforma sepa congelar — el snapshot de BD completa de siempre
 * (Snapshot), más el Árbol PDI (VersionArbol) y el presupuesto institucional de Programación
 * presupuestal y Proyectos (PresupuestoInstitucional, con tipo 'egreso'/'ingreso'/'proyecto'),
 * estos con una lista de versiones aparte cada uno. ?ruta=analisis ya NO tiene botón para crear
 * versiones — solo el selector de cuál usar para mostrar la información; crearlas se hace
 * exclusivamente aquí.
 */
class RepositorioControlador
{
    private const PESTANA_ARBOL_PDI = 'articulacion_pdi';

    private Snapshot $modeloSnapshot;
    private VersionArbol $modeloVersionArbol;
    private PresupuestoInstitucional $modeloPresupuesto;

    public function __construct()
    {
        $this->modeloSnapshot = new Snapshot();
        $this->modeloVersionArbol = new VersionArbol();
        $this->modeloPresupuesto = new PresupuestoInstitucional();
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
            } elseif ($accion === 'crear_arbol') {
                [$error, $exito] = $this->crearVersionArbol();
            } elseif ($accion === 'eliminar_arbol') {
                [$error, $exito] = $this->eliminarVersionArbol();
            } elseif ($accion === 'crear_presupuesto') {
                [$error, $exito] = $this->crearVersionPresupuesto();
            } elseif ($accion === 'eliminar_presupuesto') {
                [$error, $exito] = $this->eliminarVersionPresupuesto();
            } elseif ($accion === 'crear_proyectos_presupuesto') {
                [$error, $exito] = $this->crearVersionProyectos();
            } elseif ($accion === 'eliminar_proyectos_presupuesto') {
                [$error, $exito] = $this->eliminarVersionProyectos();
            }
        }

        $snapshots = $this->modeloSnapshot->obtenerTodos();
        $versionesArbol = $this->modeloVersionArbol->obtenerTodos(self::PESTANA_ARBOL_PDI);
        $versionesEgresos = $this->modeloPresupuesto->obtenerVersiones('egreso');
        $versionesIngresos = $this->modeloPresupuesto->obtenerVersiones('ingreso');
        $versionesProyectos = $this->modeloPresupuesto->obtenerVersiones('proyecto');

        require __DIR__ . '/../vista/repositorios/index.php';
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
