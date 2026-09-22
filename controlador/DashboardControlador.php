<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/VariableMacroeconomica.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/IngresoExtension.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/RelojArenaConfiguracion.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/MensajeGlobal.php';
require_once __DIR__ . '/../modelo/AutogestionPorcentaje.php';
require_once __DIR__ . '/../modelo/RelojArenaFormulador.php';

class DashboardControlador
{
    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $nombreUsuario = $_SESSION['usuario_nombre'];
        $rolUsuario = $_SESSION['usuario_rol'];

        if ($rolUsuario === 'administrador') {
            $modeloUsuario = new Usuario();
            $usuarioActual = $modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
            [$dependenciaIdsPermitidos, $dependenciaNombresPermitidos] = $this->obtenerAlcanceDependencia($usuarioActual);

            $usuariosAsociados = $modeloUsuario->obtenerRecientesPorDependencias($dependenciaIdsPermitidos, 5);
            $solicitudes = $this->obtenerSolicitudesRecientes(5, $dependenciaNombresPermitidos);
            $variablesMacro = array_values(array_filter(
                (new VariableMacroeconomica())->obtenerTodas(),
                static fn (array $variable): bool => $variable['estado'] === 'activo'
            ));

            // Las 4 tarjetas del mini-slider (Resumen de gastos, Autogestión, Postgrado, Mensaje
            // global) son visibles para cualquier administrador, no solo para el superadmin.
            $resumenCostos = $this->obtenerResumenCostos();
            $modeloPorcentajeAutogestion = new AutogestionPorcentaje();
            $topeExtension = $modeloPorcentajeAutogestion->obtenerTopePorModulo('extension');
            $resumenAutogestion = $this->obtenerResumenIngresos(new IngresoExtension(), $topeExtension);
            $topePostgrado = $modeloPorcentajeAutogestion->obtenerTopePorModulo('postgrado');
            $resumenPostgrado = $this->obtenerResumenIngresos(new IngresoPostgrado(), $topePostgrado);
            $mensajeGlobal = (new MensajeGlobal())->obtener()['contenido'] ?? '';

            $relojArena = $this->obtenerRelojArena();

            require __DIR__ . '/../vista/dashboard/administrador.php';
            return;
        }

        // Formulador (invitado) tiene su propio inicio: antes compartía este mismo placeholder
        // genérico ("Bienvenido, Rol: invitado") con Consejo Superior y cualquier otro rol, sin
        // decirle nada útil — en particular, si el plazo para formular necesidades no está
        // habilitado ahora mismo, necesita saber cuándo sí lo estará.
        if ($rolUsuario === 'invitado') {
            $modeloNecesidad = new Necesidad();
            $modeloRelojFormulador = new RelojArenaFormulador();

            $dentroDeVentana = $modeloRelojFormulador->estaDentroDeVentana();
            $configuracionFormulador = $modeloRelojFormulador->obtener();
            $totalNecesidades = count($modeloNecesidad->obtenerPorUsuario((int) $_SESSION['usuario_id']));

            require __DIR__ . '/../vista/dashboard/invitado.php';
            return;
        }

        require __DIR__ . '/../vista/dashboard/index.php';
    }

    private function obtenerAlcanceDependencia(?array $usuarioActual): array
    {
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaUsuarioId === null) {
            return [[], []];
        }

        $modeloDependencia = new Dependencia();
        $dependenciaUsuario = $modeloDependencia->obtenerPorId($dependenciaUsuarioId);

        if ($dependenciaUsuario === null) {
            return [[], []];
        }

        $ids = [$dependenciaUsuarioId];
        $nombres = [$dependenciaUsuario['nombre']];

        foreach ($modeloDependencia->obtenerDescendientesPlano($dependenciaUsuarioId) as $descendiente) {
            $ids[] = (int) $descendiente['id'];
            $nombres[] = $descendiente['nombre'];
        }

        return [$ids, $nombres];
    }

    private function obtenerResumenCostos(): array
    {
        $modeloGasto = new Gasto();
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $totalDependencias = (new Dependencia())->contarMonetizablesActivas();

        $resumen = [];

        foreach ($aniosActivos as $anioFila) {
            $anioId = (int) $anioFila['id'];
            $totalGastado = $modeloGasto->obtenerTotalPorAnio($anioId);
            $presupuestoAnio = (float) $anioFila['presupuesto'];
            $porcentaje = $presupuestoAnio > 0 ? min(100, ($totalGastado / $presupuestoAnio) * 100) : 0.0;
            $dependenciasConDato = count($modeloGasto->obtenerDependenciasPorAnio($anioId));

            $resumen[] = [
                'anio' => $anioFila['anio'],
                'total_gastado' => $totalGastado,
                'presupuesto' => $presupuestoAnio,
                'porcentaje' => $porcentaje,
                'dependencias_con_dato' => $dependenciasConDato,
                'dependencias_total' => $totalDependencias,
            ];
        }

        return $resumen;
    }

    /**
     * Tarjetas de Autogestión/Postgrado del Dashboard: el denominador es SIEMPRE el tope único
     * configurado en Autogestión para ese módulo (ver AutogestionPorcentaje::obtenerTopePorModulo()
     * — un solo número por módulo, no por ítem) — nunca el presupuesto institucional del año, que
     * se configura por un formulario aparte (Año presupuestal) sin relación con estos módulos. Si
     * nadie ha configurado ningún tope todavía, $tope llega en 0 y la tarjeta lo indica en vez de
     * mostrar un porcentaje.
     *
     * El numerador es el total de ingresos del año (todos los ítems, todas las dependencias) que
     * no hayan sido archivados (rechazados/descartados) en Peticiones — ver
     * obtenerTotalPorAnioSinArchivados() en cada modelo de ingreso.
     */
    private function obtenerResumenIngresos(object $modeloIngreso, float $tope): array
    {
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $totalDependencias = (new Dependencia())->contarMonetizablesActivas();

        $resumen = [];

        foreach ($aniosActivos as $anioFila) {
            $anioId = (int) $anioFila['id'];
            $totalIngresos = $modeloIngreso->obtenerTotalPorAnioSinArchivados($anioId);
            $dependenciasConDato = $modeloIngreso->obtenerDependenciasPorAnio($anioId);
            $porcentaje = $tope > 0 ? min(100, ($totalIngresos / $tope) * 100) : 0.0;

            $resumen[] = [
                'anio' => $anioFila['anio'],
                'total_ingresos' => $totalIngresos,
                'presupuesto' => $tope,
                'porcentaje' => $porcentaje,
                'dependencias_con_dato' => count(array_unique($dependenciasConDato)),
                'dependencias_total' => $totalDependencias,
            ];
        }

        return $resumen;
    }

    private function obtenerRelojArena(): array
    {
        $configuracion = (new RelojArenaConfiguracion())->obtener();

        if ($configuracion === null) {
            return ['configurado' => false];
        }

        $inicio = new DateTime($configuracion['fecha_inicio']);
        $cierre = new DateTime($configuracion['fecha_cierre']);
        $hoy = new DateTime('today');

        $diasTotales = (int) $inicio->diff($cierre)->days;

        if ($hoy < $inicio) {
            $diasTranscurridos = 0;
            $diasFaltantes = $diasTotales;
        } elseif ($hoy > $cierre) {
            $diasTranscurridos = $diasTotales;
            $diasFaltantes = 0;
        } else {
            $diasTranscurridos = (int) $inicio->diff($hoy)->days;
            $diasFaltantes = (int) $hoy->diff($cierre)->days;
        }

        $porcentajeTranscurrido = $diasTotales > 0 ? min(100, ($diasTranscurridos / $diasTotales) * 100) : 100.0;

        return [
            'configurado' => true,
            'fecha_inicio' => $inicio->format('d/m/Y'),
            'fecha_cierre' => $cierre->format('d/m/Y'),
            'dias_totales' => $diasTotales,
            'dias_faltantes' => $diasFaltantes,
            'porcentaje_transcurrido' => $porcentajeTranscurrido,
        ];
    }

    private function obtenerSolicitudesRecientes(int $limite, array $dependenciasPermitidas): array
    {
        if (empty($dependenciasPermitidas)) {
            return [];
        }

        $necesidades = array_values(array_filter(
            (new Necesidad())->obtenerRecientes($limite * 4),
            static fn (array $necesidad): bool => in_array($necesidad['dependencia'], $dependenciasPermitidas, true)
        ));
        $solicitudesArl = array_values(array_filter(
            (new SolicitudArl())->obtenerRecientes($limite * 4),
            static fn (array $solicitud): bool => in_array($solicitud['facultad'], $dependenciasPermitidas, true)
        ));

        $solicitudes = [];

        foreach ($necesidades as $necesidad) {
            $solicitudes[] = [
                'tipo' => 'Necesidad de proyecto',
                'origen' => $necesidad['nombre_solicitante'],
                'valor' => (float) $necesidad['valor'],
                'fecha' => $necesidad['creado_en'],
            ];
        }

        foreach ($solicitudesArl as $solicitudArl) {
            $valorTotal = (float) $solicitudArl['riesgo1_valor'] + (float) $solicitudArl['riesgo2_valor']
                + (float) $solicitudArl['riesgo3_valor'] + (float) $solicitudArl['riesgo4_valor']
                + (float) $solicitudArl['riesgo5_valor'];

            $solicitudes[] = [
                'tipo' => 'Solicitud ARL',
                'origen' => $solicitudArl['facultad'],
                'valor' => $valorTotal,
                'fecha' => $solicitudArl['creado_en'],
            ];
        }

        usort($solicitudes, static fn (array $a, array $b): int => strcmp($b['fecha'], $a['fecha']));

        return array_slice($solicitudes, 0, $limite);
    }
}
