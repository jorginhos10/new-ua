<?php

require_once __DIR__ . '/AnioPresupuestal.php';
require_once __DIR__ . '/Linea.php';
require_once __DIR__ . '/Motor.php';
require_once __DIR__ . '/Proyecto.php';
require_once __DIR__ . '/Gasto.php';
require_once __DIR__ . '/GastoExtension.php';
require_once __DIR__ . '/GastoPostgrado.php';
require_once __DIR__ . '/GastoUnisalud.php';
require_once __DIR__ . '/Dependencia.php';
require_once __DIR__ . '/VersionArbol.php';
require_once __DIR__ . '/PeticionArchivada.php';

/**
 * Fuente de datos única para el árbol PDI (página ?ruta=analisis), que abstrae los 3 modos
 * (Tiempo real / Repositorio / Usuario) detrás de los mismos métodos que ya usan los modelos
 * reales — el árbol nunca necesita saber si una fila vino de la base de datos en vivo, de una
 * versión guardada, o filtrada a una dependencia puntual. Ver plan el-techo-no-deberia-kind-candle.
 */
class FuenteDatosAnalisis
{
    private AnioPresupuestal $modeloAnio;
    private Linea $modeloLinea;
    private Motor $modeloMotor;
    private Proyecto $modeloProyecto;
    private Gasto $modeloGasto;
    private GastoExtension $modeloGastoExtension;
    private GastoPostgrado $modeloGastoPostgrado;
    private GastoUnisalud $modeloGastoUnisalud;
    private Dependencia $modeloDependencia;
    private VersionArbol $modeloVersion;
    private PeticionArchivada $modeloArchivada;

    private ?int $versionId;
    private ?string $dependenciaFiltro;

    /** Cache de una sola instancia (ver filtrarActivos()) — una sola consulta a peticiones_archivadas. */
    private ?array $cacheAccionesPorClave = null;

    /**
     * @param int|null $versionId Si no es null, todo se lee de esa versión guardada en vez de las
     *        tablas en vivo (modo "repositorio").
     * @param string|null $dependenciaFiltro Si no es null, los gastos se acotan a esta dependencia
     *        y sus descendientes (modo "usuario") — combinable con $versionId.
     */
    public function __construct(?int $versionId = null, ?string $dependenciaFiltro = null)
    {
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloLinea = new Linea();
        $this->modeloMotor = new Motor();
        $this->modeloProyecto = new Proyecto();
        $this->modeloGasto = new Gasto();
        $this->modeloGastoExtension = new GastoExtension();
        $this->modeloGastoPostgrado = new GastoPostgrado();
        $this->modeloGastoUnisalud = new GastoUnisalud();
        $this->modeloDependencia = new Dependencia();
        $this->modeloVersion = new VersionArbol();
        $this->modeloArchivada = new PeticionArchivada();
        $this->versionId = $versionId;
        $this->dependenciaFiltro = $dependenciaFiltro;
    }

    private function esRepositorio(): bool
    {
        return $this->versionId !== null;
    }

    public function obtenerAniosActivos(): array
    {
        if (!$this->esRepositorio()) {
            return $this->modeloAnio->obtenerActivos();
        }

        $filas = $this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'anios_presupuestales');
        $activos = array_values(array_filter($filas, static fn (array $a): bool => ($a['estado'] ?? '') === 'activo'));
        usort($activos, static fn (array $a, array $b): int => (int) $b['anio'] - (int) $a['anio']);

        return $activos;
    }

    public function obtenerAniosTodos(): array
    {
        if (!$this->esRepositorio()) {
            return $this->modeloAnio->obtenerTodos();
        }

        $filas = $this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'anios_presupuestales');
        usort($filas, static fn (array $a, array $b): int => (int) $b['anio'] - (int) $a['anio']);

        return $filas;
    }

    public function obtenerLineas(): array
    {
        if (!$this->esRepositorio()) {
            return $this->modeloLinea->obtenerTodas();
        }

        $filas = $this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'lineas');
        usort($filas, static fn (array $a, array $b): int => strcmp((string) $a['codigo'], (string) $b['codigo']));

        return $filas;
    }

    public function obtenerMotores(): array
    {
        if (!$this->esRepositorio()) {
            return $this->modeloMotor->obtenerTodos();
        }

        $lineasPorId = [];
        foreach ($this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'lineas') as $linea) {
            $lineasPorId[(int) $linea['id']] = $linea;
        }

        $motores = $this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'motores');
        foreach ($motores as &$motor) {
            $linea = $lineasPorId[(int) $motor['linea_id']] ?? null;
            $motor['linea_codigo'] = $linea['codigo'] ?? '';
            $motor['linea_nombre'] = $linea['nombre'] ?? '';
        }
        unset($motor);
        usort($motores, static fn (array $a, array $b): int => strcmp((string) $a['codigo'], (string) $b['codigo']));

        return $motores;
    }

    public function obtenerProyectos(): array
    {
        if (!$this->esRepositorio()) {
            return $this->modeloProyecto->obtenerTodos();
        }

        $lineasPorId = [];
        foreach ($this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'lineas') as $linea) {
            $lineasPorId[(int) $linea['id']] = $linea;
        }
        $motoresPorId = [];
        foreach ($this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'motores') as $motor) {
            $motoresPorId[(int) $motor['id']] = $motor;
        }

        $proyectos = $this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'proyectos');
        foreach ($proyectos as &$proyecto) {
            $motor = $motoresPorId[(int) $proyecto['motor_id']] ?? null;
            $linea = $motor !== null ? ($lineasPorId[(int) $motor['linea_id']] ?? null) : null;
            $proyecto['motor_codigo'] = $motor['codigo'] ?? '';
            $proyecto['motor_nombre'] = $motor['nombre'] ?? '';
            $proyecto['linea_id'] = $motor['linea_id'] ?? null;
            $proyecto['linea_codigo'] = $linea['codigo'] ?? '';
            $proyecto['linea_nombre'] = $linea['nombre'] ?? '';
        }
        unset($proyecto);

        return $proyectos;
    }

    /**
     * Filas de `gastos` para un año — mismas columnas mínimas que necesita el árbol
     * (proyecto_id, valor_total, creado_en, dependencia), acotadas a la dependencia + hijas
     * cuando el modo "usuario" está activo.
     */
    public function obtenerGastosPorAnio(int $anioPresupuestalId): array
    {
        if ($this->esRepositorio()) {
            $filas = $this->modeloVersion->obtenerTablaDeVersion($this->versionId, 'gastos');
            $gastos = array_values(array_filter(
                $filas,
                static fn (array $g): bool => (int) $g['anio_presupuestal_id'] === $anioPresupuestalId
            ));
        } else {
            // Datos en vivo (nunca Repositorio — un snapshot congelado ya es su propia verdad
            // histórica): Articulación PDI debe mostrar solo lo activo — ni borrador (todavía no
            // es definitivo) ni lo que ya se archivó o se mandó a Expediente en Peticiones.
            $gastos = $this->filtrarActivos('gasto_principal', $this->modeloGasto->obtenerPorAnio($anioPresupuestalId));
        }

        return $this->aplicarFiltroDependencia($gastos);
    }

    /**
     * Gastos de los 3 módulos de autogestión (Extensión/Postgrado/Unisalud)
     * para un año, ya combinados en una sola lista — usado por
     * Articulación PDI para sumarlos junto a gasto_principal en el total de cada proyecto (antes
     * de este método, ese total solo incluía gasto_principal). Respeta tiempo_real/repositorio/
     * usuario igual que obtenerGastosPorAnio().
     */
    public function obtenerGastosAutogestionPorAnio(int $anioPresupuestalId): array
    {
        if ($this->esRepositorio()) {
            $gastos = array_merge(
                $this->filtrarPorAnioDeVersion('gastos_extension', $anioPresupuestalId),
                $this->filtrarPorAnioDeVersion('gastos_postgrado', $anioPresupuestalId),
                $this->filtrarPorAnioDeVersion('gastos_unisalud', $anioPresupuestalId)
            );
        } else {
            $gastos = array_merge(
                $this->filtrarActivos('gasto_extension', $this->modeloGastoExtension->obtenerPorAnio($anioPresupuestalId)),
                $this->filtrarActivos('gasto_postgrado', $this->modeloGastoPostgrado->obtenerPorAnio($anioPresupuestalId)),
                $this->filtrarActivos('gasto_unisalud', $this->modeloGastoUnisalud->obtenerPorAnio($anioPresupuestalId))
            );
        }

        return $this->aplicarFiltroDependencia($gastos);
    }

    /**
     * "Solo lo activo", el mismo criterio que usa AnalisisControlador::filtrarSoloActivos() para
     * Análisis de distribución: nunca borrador (su dueño lo puede cambiar todavía, no es
     * definitivo) ni lo que ya se archivó o se mandó a Expediente en Peticiones. $origen es una
     * clave de PeticionesControlador::TABLAS_ORIGEN (ej. 'gasto_principal').
     */
    private function filtrarActivos(string $origen, array $filas): array
    {
        $filas = array_values(array_filter(
            $filas,
            static fn (array $fila): bool => ($fila['estado'] ?? null) !== 'borrador'
        ));

        if ($this->cacheAccionesPorClave === null) {
            $this->cacheAccionesPorClave = $this->modeloArchivada->obtenerAccionesPorClave();
        }

        return array_values(array_filter($filas, function (array $fila) use ($origen): bool {
            $accion = $this->cacheAccionesPorClave[$origen . ':' . $fila['id']] ?? null;

            return !in_array($accion, ['archivada', 'expediente'], true);
        }));
    }

    private function filtrarPorAnioDeVersion(string $tabla, int $anioPresupuestalId): array
    {
        $filas = $this->modeloVersion->obtenerTablaDeVersion($this->versionId, $tabla);

        return array_values(array_filter(
            $filas,
            static fn (array $g): bool => (int) $g['anio_presupuestal_id'] === $anioPresupuestalId
        ));
    }

    /** La dependencia elegida en modo "usuario" (+ descendientes) — sin cambios en tiempo_real/repositorio. */
    private function aplicarFiltroDependencia(array $filas): array
    {
        if ($this->dependenciaFiltro === null) {
            return $filas;
        }

        $dependenciasPermitidas = $this->obtenerDependenciasPermitidas();

        return array_values(array_filter(
            $filas,
            static fn (array $g): bool => in_array($g['dependencia'] ?? null, $dependenciasPermitidas, true)
        ));
    }

    /** La dependencia elegida en modo "usuario", más todas sus descendientes. */
    private function obtenerDependenciasPermitidas(): array
    {
        $dependencia = $this->modeloDependencia->obtenerPorNombre((string) $this->dependenciaFiltro);

        if ($dependencia === null) {
            return [(string) $this->dependenciaFiltro];
        }

        $permitidas = [$dependencia['nombre']];
        foreach ($this->modeloDependencia->obtenerDescendientesPlano((int) $dependencia['id']) as $descendiente) {
            $permitidas[] = $descendiente['nombre'];
        }

        return $permitidas;
    }
}
