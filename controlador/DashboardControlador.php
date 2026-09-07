<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/VariableMacroeconomica.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/IngresoExtension.php';
require_once __DIR__ . '/../modelo/IngresoSinExcedentes.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/RelojArenaConfiguracion.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/MensajeGlobal.php';

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
            $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;
            [$dependenciaIdsPermitidos, $dependenciaNombresPermitidos] = $this->obtenerAlcanceDependencia($usuarioActual);

            $usuariosAsociados = $modeloUsuario->obtenerRecientesPorDependencias($dependenciaIdsPermitidos, 5);
            $solicitudes = $this->obtenerSolicitudesRecientes(5, $dependenciaNombresPermitidos);
            $variablesMacro = array_values(array_filter(
                (new VariableMacroeconomica())->obtenerTodas(),
                static fn (array $variable): bool => $variable['estado'] === 'activo'
            ));

            if ($esSuperAdmin) {
                $resumenCostos = $this->obtenerResumenCostos();
                $resumenAutogestion = $this->obtenerResumenIngresos([new IngresoExtension(), new IngresoSinExcedentes()]);
                $resumenPostgrado = $this->obtenerResumenIngresos([new IngresoPostgrado()]);
                $mensajeGlobal = '';
            } else {
                $resumenCostos = [];
                $resumenAutogestion = [];
                $resumenPostgrado = [];
                $mensajeGlobal = (new MensajeGlobal())->obtener()['contenido'] ?? '';
            }

            $relojArena = $this->obtenerRelojArena();

            require __DIR__ . '/../vista/dashboard/administrador.php';
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
                'detalle_dependencias' => $this->obtenerDetalleCostosPorDependencia($anioId, $modeloGasto),
            ];
        }

        return $resumen;
    }

    /**
     * Gasto ejecutado de cada dependencia frente a su propio techo asignado para el año,
     * de mayor a menor porcentaje ejecutado. Solo incluye dependencias con techo > 0.
     */
    private function obtenerDetalleCostosPorDependencia(int $anioId, Gasto $modeloGasto): array
    {
        $techos = (new PresupuestoDependencia())->obtenerPorAnio($anioId);

        if (empty($techos)) {
            return [];
        }

        $modeloDependencia = new Dependencia();
        $ejecutados = $modeloGasto->obtenerTotalesEjecutadosPorDependencia($anioId);

        $detalle = [];

        foreach ($techos as $dependenciaId => $info) {
            $techo = (float) ($info['techo'] ?? 0);

            if ($techo <= 0) {
                continue;
            }

            $dependencia = $modeloDependencia->obtenerPorId($dependenciaId);

            if ($dependencia === null) {
                continue;
            }

            $gastado = $ejecutados[$dependencia['nombre']] ?? 0.0;

            $detalle[] = [
                'nombre' => $dependencia['nombre'],
                'gastado' => $gastado,
                'techo' => $techo,
                'porcentaje' => min(100, ($gastado / $techo) * 100),
            ];
        }

        usort($detalle, static fn (array $a, array $b): int => $b['porcentaje'] <=> $a['porcentaje']);

        return $detalle;
    }

    private function obtenerResumenIngresos(array $modelosIngreso): array
    {
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $totalDependencias = (new Dependencia())->contarMonetizablesActivas();

        $resumen = [];

        foreach ($aniosActivos as $anioFila) {
            $anioId = (int) $anioFila['id'];
            $totalIngresos = 0.0;
            $dependenciasConDato = [];

            foreach ($modelosIngreso as $modeloIngreso) {
                $totalIngresos += $modeloIngreso->obtenerTotalPorAnio($anioId);
                $dependenciasConDato = array_merge($dependenciasConDato, $modeloIngreso->obtenerDependenciasPorAnio($anioId));
            }

            $presupuestoAnio = (float) $anioFila['presupuesto'];
            $porcentaje = $presupuestoAnio > 0 ? min(100, ($totalIngresos / $presupuestoAnio) * 100) : 0.0;

            $resumen[] = [
                'anio' => $anioFila['anio'],
                'total_ingresos' => $totalIngresos,
                'presupuesto' => $presupuestoAnio,
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
