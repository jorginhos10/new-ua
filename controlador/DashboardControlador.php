<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/VariableMacroeconomica.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/IngresoExtension.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/RelojArenaConfiguracion.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/MensajeGlobal.php';
require_once __DIR__ . '/../modelo/AutogestionPorcentaje.php';
require_once __DIR__ . '/../modelo/RelojArenaFormulador.php';
require_once __DIR__ . '/../modelo/RelojArenaConsejo.php';

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
            $correoUsuario = $usuarioActual['correo'] ?? '';
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
            $mensajesGlobales = (new MensajeGlobal())->listar('administrador');

            $relojArena = $this->obtenerRelojArena((new RelojArenaConfiguracion())->obtener());

            require __DIR__ . '/../vista/dashboard/administrador.php';
            return;
        }

        // Consejo Superior: bienvenida, variables, su propio reloj y sus propios mensajes globales.
        if ($rolUsuario === 'consejo_superior') {
            $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
            $correoUsuario = $usuarioActual['correo'] ?? '';
            $variablesMacro = array_values(array_filter(
                (new VariableMacroeconomica())->obtenerTodas(),
                static fn (array $variable): bool => $variable['estado'] === 'activo'
            ));
            $relojArena = $this->obtenerRelojArena((new RelojArenaConsejo())->obtenerConDefecto());
            $mensajesGlobales = (new MensajeGlobal())->listar('consejo_superior');

            require __DIR__ . '/../vista/dashboard/consejo.php';
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

    /**
     * Resumen de gastos con la misma cuenta que Techos (TechosControlador): el techo total son los
     * techos de las unidades directas de la raíz (los techos anidados ya salen de los de su padre,
     * no se suman otra vez), y lo asignado es lo que calcula calcularAsignadoArbol() sobre
     * presupuesto_dependencia y el total ejecutado de Gasto.
     */
    private function obtenerResumenCostos(): array
    {
        $modeloGasto = new Gasto();
        $modeloDependencia = new Dependencia();
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();

        $raiz = null;
        foreach ($modeloDependencia->obtenerTodas() as $dependencia) {
            if (!empty($dependencia['es_raiz_superadmin'])) {
                $raiz = $dependencia;
                break;
            }
        }

        $resumen = [];

        foreach ($aniosActivos as $anioFila) {
            $anioId = (int) $anioFila['id'];
            $techos = (new PresupuestoDependencia())->obtenerPorAnio($anioId);
            $ejecutado = $modeloGasto->obtenerTotalesEjecutadosPorDependencia($anioId);

            $techoTotal = 0.0;
            $asignado = 0.0;
            $conTecho = 0;
            $conAsignacion = 0;
            $sobreTecho = 0;

            if ($raiz !== null) {
                $mapa = [];
                foreach ($modeloDependencia->construirArbolDescendientes((int) $raiz['id']) as $nodo) {
                    $asignado += $this->calcularAsignadoArbol($nodo, $techos, $ejecutado, $mapa);

                    // Techo total: solo las unidades directas, porque los techos anidados salen del de su padre.
                    $techoTotal += (float) ($techos[(int) $nodo['dependencia']['id']]['techo'] ?? 0);
                }

                // Conteo: todas las dependencias con techo, también las anidadas.
                foreach ($techos as $dependenciaId => $fila) {
                    $techo = (float) ($fila['techo'] ?? 0);
                    if ($techo <= 0 || !isset($mapa[(int) $dependenciaId])) {
                        continue;
                    }

                    $conTecho++;
                    if ($mapa[(int) $dependenciaId] > 0) {
                        $conAsignacion++;
                    }
                    if ($mapa[(int) $dependenciaId] > $techo) {
                        $sobreTecho++;
                    }
                }
            }

            $trimestres = $this->pacPorTrimestre($anioId);
            $proyectosTotal = count((new Proyecto())->obtenerTodos());
            $proyectosConPresupuesto = (new Gasto())->contarProyectosConPresupuesto($anioId);

            $resumen[] = [
                'anio' => $anioFila['anio'],
                'proyectos_total' => $proyectosTotal,
                'proyectos_con_presupuesto' => $proyectosConPresupuesto,
                'trimestres' => $trimestres,
                'techo_total' => $techoTotal,
                'gastado' => $asignado,
                'disponible' => $techoTotal - $asignado,
                'porcentaje' => $techoTotal > 0 ? min(100, ($asignado / $techoTotal) * 100) : 0.0,
                'dependencias_con_techo' => $conTecho,
                'dependencias_con_gasto' => $conAsignacion,
                'dependencias_sobre_techo' => $sobreTecho,
            ];
        }

        return $resumen;
    }

    /**
     * PAC por trimestre (% del total programado). El gasto de cada ítem se reparte en partes iguales
     * entre sus meses, igual que el gráfico de PAC del análisis.
     */
    private function pacPorTrimestre(int $anioId): array
    {
        $filas = (new Gasto())->obtenerPropiosParaResumen($anioId);

        $porTrimestre = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];

        foreach ($filas as $fila) {
            $valor = (float) $fila['valor_total'];

            $meses = array_values(array_filter(
                array_map('intval', explode(',', (string) $fila['meses'])),
                static fn (int $mes): bool => $mes >= 1 && $mes <= 12
            ));
            if ($meses !== []) {
                $parte = $valor / count($meses);
                foreach ($meses as $mes) {
                    $porTrimestre[intdiv($mes - 1, 3) + 1] += $parte;
                }
            }
        }

        $totalPac = array_sum($porTrimestre);
        $trimestres = [];
        foreach ($porTrimestre as $numero => $monto) {
            $trimestres[] = [
                'etiqueta' => 'T' . $numero,
                'porcentaje' => $totalPac > 0 ? ($monto / $totalPac) * 100 : 0.0,
            ];
        }

        return $trimestres;
    }

    /**
     * Misma regla que TechosControlador::calcularAsignadoArbol(): lo ejecutado del nodo más lo de
     * los hijos que no tienen techo propio. Un hijo con techo queda fuera de la suma del padre.
     */
    private function calcularAsignadoArbol(array $nodo, array $techos, array $ejecutado, array &$mapa): float
    {
        $dependencia = $nodo['dependencia'];
        $asignado = $ejecutado[$dependencia['nombre']] ?? 0.0;

        foreach ($nodo['hijos'] as $nodoHijo) {
            $techoHijo = $techos[(int) $nodoHijo['dependencia']['id']]['techo'] ?? null;
            $asignadoHijo = $this->calcularAsignadoArbol($nodoHijo, $techos, $ejecutado, $mapa);

            if ($techoHijo === null || (float) $techoHijo <= 0) {
                $asignado += $asignadoHijo;
            }
        }

        $mapa[(int) $dependencia['id']] = $asignado;

        return $asignado;
    }

    /** @return array<int, string> nombre de cada dependencia por id. */
    private function nombresPorId(Dependencia $modeloDependencia): array
    {
        $nombres = [];
        foreach ($modeloDependencia->obtenerTodas() as $dependencia) {
            $nombres[(int) $dependencia['id']] = $dependencia['nombre'];
        }

        return $nombres;
    }

    /**
     * Tarjetas de Autogestión/Postgrado del Dashboard: el denominador es SIEMPRE el tope único
     * configurado en Autogestión para ese módulo (ver AutogestionPorcentaje::obtenerTopePorModulo()
     * — un solo número por módulo, no por ítem) — nunca el presupuesto institucional del año, que
     * se configura por un formulario aparte (Año presupuestal) sin relación con estos módulos. Si
     * nadie ha configurado ningún tope todavía, $tope llega en 0 y la tarjeta lo indica en vez de
     * mostrar un porcentaje.
     *
     * El numerador son los ingresos del año (sin archivados) de las dependencias con techo mayor a 0,
     * igual que el resumen de gastos: no todas las dependencias tienen techo.
     */
    private function obtenerResumenIngresos(object $modeloIngreso, float $tope): array
    {
        $aniosActivos = (new AnioPresupuestal())->obtenerActivos();
        $nombrePorId = $this->nombresPorId(new Dependencia());

        $resumen = [];

        foreach ($aniosActivos as $anioFila) {
            $anioId = (int) $anioFila['id'];
            $techos = (new PresupuestoDependencia())->obtenerPorAnio($anioId);
            $ingresoPorDependencia = $modeloIngreso->obtenerTotalesPorDependencia($anioId);

            $ingresos = 0.0;
            $conTecho = 0;
            $conIngreso = 0;

            foreach ($techos as $dependenciaId => $fila) {
                if ((float) ($fila['techo'] ?? 0) <= 0 || !isset($nombrePorId[(int) $dependenciaId])) {
                    continue;
                }

                $ingresoDependencia = (float) ($ingresoPorDependencia[$nombrePorId[(int) $dependenciaId]] ?? 0.0);
                $ingresos += $ingresoDependencia;
                $conTecho++;
                if ($ingresoDependencia > 0) {
                    $conIngreso++;
                }
            }

            $resumen[] = [
                'anio' => $anioFila['anio'],
                'tope' => $tope,
                'ingresos' => $ingresos,
                'disponible' => $tope - $ingresos,
                'porcentaje' => $tope > 0 ? min(100, ($ingresos / $tope) * 100) : 0.0,
                'dependencias_con_techo' => $conTecho,
                'dependencias_con_ingreso' => $conIngreso,
            ];
        }

        return $resumen;
    }

    /** Reloj del inicio a partir de sus fechas configuradas (null si no hay ninguna). */
    private function obtenerRelojArena(?array $configuracion): array
    {
        if ($configuracion === null) {
            return ['configurado' => false];
        }

        // Cuenta desde las 00:00 del día de inicio hasta las 23:59:59 del día de cierre (es decir,
        // hasta las 00:00 del día siguiente). Con menos de dos días faltantes, la cuenta pasa a horas.
        // Los días son los de Colombia, sin depender de la zona horaria configurada en el servidor.
        $zona = new DateTimeZone('America/Bogota');
        $inicio = new DateTimeImmutable($configuracion['fecha_inicio'] . ' 00:00:00', $zona);
        $fin = (new DateTimeImmutable($configuracion['fecha_cierre'] . ' 00:00:00', $zona))->modify('+1 day');
        $ahora = new DateTimeImmutable('now', $zona);

        $segundosTotales = max(1, $fin->getTimestamp() - $inicio->getTimestamp());

        if ($ahora < $inicio) {
            $segundosTranscurridos = 0;
            $segundosFaltantes = $segundosTotales;
        } elseif ($ahora >= $fin) {
            $segundosTranscurridos = $segundosTotales;
            $segundosFaltantes = 0;
        } else {
            $segundosTranscurridos = $ahora->getTimestamp() - $inicio->getTimestamp();
            $segundosFaltantes = $fin->getTimestamp() - $ahora->getTimestamp();
        }

        if ($segundosFaltantes < 2 * 86400) {
            $faltante = intdiv($segundosFaltantes, 3600);
            $unidad = $faltante === 1 ? 'hora' : 'horas';
        } else {
            $faltante = intdiv($segundosFaltantes, 86400);
            $unidad = $faltante === 1 ? 'día' : 'días';
        }

        return [
            'configurado' => true,
            'fecha_inicio' => $inicio->format('d/m/Y'),
            'fecha_cierre' => (new DateTimeImmutable($configuracion['fecha_cierre']))->format('d/m/Y'),
            'faltante' => $faltante,
            'unidad' => $unidad,
            'porcentaje_transcurrido' => min(100, ($segundosTranscurridos / $segundosTotales) * 100),
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
