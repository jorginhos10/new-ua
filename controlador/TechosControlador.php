<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/PresupuestoVersion.php';

class TechosControlador
{
    private Usuario $modeloUsuario;
    private Dependencia $modeloDependencia;
    private AnioPresupuestal $modeloAnio;
    private PresupuestoDependencia $modeloPresupuestoDependencia;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;
    private Gasto $modeloGasto;
    private PresupuestoVersion $modeloVersion;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
        $this->modeloDependencia = new Dependencia();
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloPresupuestoDependencia = new PresupuestoDependencia();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
        $this->modeloGasto = new Gasto();
        $this->modeloVersion = new PresupuestoVersion();
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

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        $error = '';
        $exito = '';

        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $anioSeleccionadoId = 0;

        if (!empty($aniosActivos)) {
            $anioSeleccionadoId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) $aniosActivos[0]['id'];

            $idsValidos = array_map('intval', array_column($aniosActivos, 'id'));
            if (!in_array($anioSeleccionadoId, $idsValidos, true)) {
                $anioSeleccionadoId = (int) $aniosActivos[0]['id'];
            }
        }

        $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dependenciaUsuarioId !== null && $anioSeleccionadoId > 0) {
            $accion = $_POST['accion'] ?? '';
            [$error, $exito] = $this->guardarTechos($dependenciaUsuarioId, $anioSeleccionadoId, $accion === 'notificar', $esSuperAdmin);
        }

        $arbolHijas = $dependenciaUsuarioId !== null ? $this->modeloDependencia->construirArbolDescendientes($dependenciaUsuarioId) : [];
        $presupuestosActuales = $anioSeleccionadoId > 0 ? $this->modeloPresupuestoDependencia->obtenerPorAnio($anioSeleccionadoId) : [];
        $gastadoPorDependencia = $anioSeleccionadoId > 0 ? $this->modeloGasto->obtenerTotalesEjecutadosPorDependencia($anioSeleccionadoId) : [];

        $asignadoPorId = [];
        foreach ($arbolHijas as $nodoRaiz) {
            $this->calcularAsignadoArbol($nodoRaiz, $presupuestosActuales, $gastadoPorDependencia, $asignadoPorId);
        }

        $totalMinimo = 0.0;
        $totalTecho = 0.0;
        $totalAsignado = 0.0;

        foreach ($arbolHijas as $nodoRaiz) {
            $dependenciaRaiz = $nodoRaiz['dependencia'];
            $valoresRaiz = $presupuestosActuales[$dependenciaRaiz['id']] ?? ['minimo' => null, 'techo' => null];

            $totalMinimo += $valoresRaiz['minimo'] !== null ? (float) $valoresRaiz['minimo'] : 0.0;
            $totalTecho += $valoresRaiz['techo'] !== null ? (float) $valoresRaiz['techo'] : 0.0;
            $totalAsignado += $asignadoPorId[(int) $dependenciaRaiz['id']] ?? 0.0;
        }

        $totalRestante = $totalTecho - $totalAsignado;

        require __DIR__ . '/../vista/techos/index.php';
    }

    private const COLORES_GRAFICA = [
        '#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6',
        '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16',
    ];

    public function resumen(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $anioSeleccionadoId = 0;

        if (!empty($aniosActivos)) {
            $anioSeleccionadoId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) $aniosActivos[0]['id'];

            $idsValidos = array_map('intval', array_column($aniosActivos, 'id'));
            if (!in_array($anioSeleccionadoId, $idsValidos, true)) {
                $anioSeleccionadoId = (int) $aniosActivos[0]['id'];
            }
        }

        $dependenciasHijas = $dependenciaUsuarioId !== null ? $this->modeloDependencia->obtenerHijasDirectas($dependenciaUsuarioId) : [];
        $presupuestosActuales = $anioSeleccionadoId > 0 ? $this->modeloPresupuestoDependencia->obtenerPorAnio($anioSeleccionadoId) : [];

        $segmentos = [];
        $totalTechos = 0.0;

        foreach ($dependenciasHijas as $indice => $dependencia) {
            $techo = $presupuestosActuales[$dependencia['id']]['techo'] ?? null;

            if ($techo === null || (float) $techo <= 0) {
                continue;
            }

            $segmentos[] = [
                'nombre' => $dependencia['nombre'],
                'techo' => (float) $techo,
                'color' => self::COLORES_GRAFICA[$indice % count(self::COLORES_GRAFICA)],
            ];
            $totalTechos += (float) $techo;
        }

        $circunferencia = 2 * M_PI * 40;
        $acumulado = 0.0;

        foreach ($segmentos as &$segmento) {
            $porcentaje = $totalTechos > 0 ? $segmento['techo'] / $totalTechos : 0;
            $largo = $porcentaje * $circunferencia;

            $segmento['porcentaje'] = $porcentaje * 100;
            $segmento['dasharray'] = $largo . ' ' . ($circunferencia - $largo);
            $segmento['dashoffset'] = -$acumulado;

            $acumulado += $largo;
        }
        unset($segmento);

        require __DIR__ . '/../vista/techos/resumen.php';
    }

    public function alternarBloqueo(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['usuario_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false]);
            exit;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);
        $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;

        if (!$esSuperAdmin) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'mensaje' => 'Solo el administrador puede bloquear o desbloquear un techo.']);
            exit;
        }

        $anioId = (int) ($_POST['anio_id'] ?? 0);
        $dependenciaId = (int) ($_POST['dependencia_id'] ?? 0);

        if ($anioId <= 0 || $dependenciaId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false]);
            exit;
        }

        $this->modeloPresupuestoDependencia->alternarBloqueo($anioId, $dependenciaId);
        $bloqueado = $this->modeloPresupuestoDependencia->obtenerBloqueo($anioId, $dependenciaId);

        echo json_encode(['ok' => true, 'bloqueado' => $bloqueado]);
    }

    private function guardarTechos(int $dependenciaUsuarioId, int $anioId, bool $notificar, bool $esSuperAdmin): array
    {
        $anio = $this->modeloAnio->obtenerPorId($anioId);

        if ($anio === null) {
            return ['El año presupuestal no existe.', ''];
        }

        $arbol = $this->modeloDependencia->construirArbolDescendientes($dependenciaUsuarioId);
        $nodosPlanos = $this->aplanarArbol($arbol);

        $todas = $this->modeloDependencia->obtenerTodas();
        $porId = [];
        foreach ($todas as $dependencia) {
            $porId[(int) $dependencia['id']] = $dependencia;
        }

        $minimos = $_POST['minimo'] ?? [];
        $techos = $_POST['techo'] ?? [];

        $techosAnteriores = $this->modeloPresupuestoDependencia->obtenerPorAnio($anioId);

        $techoEfectivoPorId = [];
        foreach ($techosAnteriores as $depId => $valores) {
            $techoEfectivoPorId[$depId] = $valores['techo'] !== null ? (float) $valores['techo'] : null;
        }

        $dependenciasConTechoNuevo = [];
        $dependenciasBloqueadas = [];
        $dependenciasConCandado = [];
        $dependenciasSobreTecho = [];
        $dependenciasBajoMinimo = [];
        $comprometidoPorPadreId = [];
        $cambiosVersion = [];

        foreach ($nodosPlanos as $dependencia) {
            $dependenciaId = (int) $dependencia['id'];

            $minimoTexto = trim((string) ($minimos[$dependenciaId] ?? ''));
            $techoTexto = trim((string) ($techos[$dependenciaId] ?? ''));

            $minimo = $minimoTexto !== '' && is_numeric($minimoTexto) ? (float) $minimoTexto : null;
            $techo = $techoTexto !== '' && is_numeric($techoTexto) ? (float) $techoTexto : null;

            if ($minimo === null && $techo === null) {
                continue;
            }

            $padreId = !empty($dependencia['flujo_id']) ? (int) $dependencia['flujo_id'] : null;
            $techoAnterior = isset($techosAnteriores[$dependenciaId]['techo']) ? (float) $techosAnteriores[$dependenciaId]['techo'] : null;
            $minimoAnterior = isset($techosAnteriores[$dependenciaId]['minimo']) ? (float) $techosAnteriores[$dependenciaId]['minimo'] : null;

            if (!$esSuperAdmin) {
                // El formulario de un no-superadmin no incluye el campo mínimo: conservar el valor
                // ya guardado para no borrarlo al guardar solo el techo.
                $minimo = $minimoAnterior;
            }

            if ($techo !== null && !$esSuperAdmin) {
                $estaBloqueado = !empty($techosAnteriores[$dependenciaId]['bloqueado']);

                if ($estaBloqueado && $techo !== $techoAnterior) {
                    $dependenciasConCandado[] = $dependencia['nombre'];
                    $techo = $techoAnterior;
                }

                if ($minimo !== null && $techo !== null && $techo < $minimo && $techo !== $techoAnterior) {
                    $dependenciasBajoMinimo[] = $dependencia['nombre'];
                    $techo = $techoAnterior;
                }

                $techoPadre = $padreId !== null ? ($techoEfectivoPorId[$padreId] ?? null) : null;

                if ($padreId === null || $techoPadre === null || $techoPadre <= 0) {
                    $dependenciasBloqueadas[] = $dependencia['nombre'];
                    continue;
                }

                if ($techo !== null && $techo > 0 && $techo !== $techoAnterior) {
                    if (!array_key_exists($padreId, $comprometidoPorPadreId)) {
                        $dependenciaPadreDatos = $porId[$padreId] ?? null;
                        $comprometidoPorPadreId[$padreId] = $dependenciaPadreDatos !== null
                            ? $this->modeloGasto->obtenerTotalPorAnioYDependencia($anioId, $dependenciaPadreDatos['nombre'])
                            : 0.0;
                    }

                    $proyectado = $comprometidoPorPadreId[$padreId] - ($techoAnterior ?? 0.0) + $techo;

                    if ($proyectado > $techoPadre) {
                        $dependenciasSobreTecho[] = $dependencia['nombre'];
                        $techo = $techoAnterior;
                    } else {
                        $comprometidoPorPadreId[$padreId] = $proyectado;
                    }
                }
            }

            if ($minimo === $minimoAnterior && $techo === $techoAnterior) {
                continue;
            }

            if ($techo !== null && $techo !== $techoAnterior) {
                $dependenciasConTechoNuevo[] = ['dependencia' => $dependencia, 'techo' => $techo];
            }

            $this->modeloPresupuestoDependencia->guardar($anioId, $dependenciaId, $minimo, $techo);

            $cambiosVersion[] = [
                'dependencia_id' => $dependenciaId,
                'minimo_anterior' => $minimoAnterior,
                'minimo_nuevo' => $minimo,
                'techo_anterior' => $techoAnterior,
                'techo_nuevo' => $techo,
            ];

            $dependenciaPadre = $padreId !== null ? ($porId[$padreId] ?? null) : null;
            $this->modeloGasto->sincronizarAutomaticoTechoHija($anioId, $dependenciaPadre, $dependencia, $techo);

            $techoEfectivoPorId[$dependenciaId] = $techo ?? ($techoEfectivoPorId[$dependenciaId] ?? null);
        }

        if (!empty($cambiosVersion)) {
            $this->modeloVersion->crearVersion($anioId, (int) ($_SESSION['usuario_id'] ?? 0), $cambiosVersion);
        }

        $huboCambios = !empty($cambiosVersion);
        $mensajesError = [];

        if (!empty($dependenciasBajoMinimo)) {
            $mensajesError[] = 'No se pudo asignar techo a: ' . implode(', ', $dependenciasBajoMinimo)
                . '. El techo no puede ser menor al mínimo presupuestal de la dependencia.';
        }

        if (!empty($dependenciasBloqueadas)) {
            $mensajesError[] = 'No se pudo asignar techo a: ' . implode(', ', $dependenciasBloqueadas)
                . '. Su dependencia superior todavía no tiene techo asignado.';
        }

        if (!empty($dependenciasConCandado)) {
            $mensajesError[] = 'No se pudo modificar el techo de: ' . implode(', ', $dependenciasConCandado)
                . '. El administrador lo bloqueó.';
        }

        if (!empty($dependenciasSobreTecho)) {
            $mensajesError[] = 'No se pudo asignar techo a: ' . implode(', ', $dependenciasSobreTecho)
                . '. La suma de los techos asignados superaría el techo de la dependencia superior.';
        }

        $error = implode(' ', $mensajesError);

        if (!$notificar) {
            $exito = $huboCambios ? 'Techos actualizados correctamente.' : '';

            return [$error, $exito];
        }

        if (empty($dependenciasConTechoNuevo)) {
            $exito = $huboCambios
                ? 'Techos actualizados. No había techos nuevos o modificados para notificar.'
                : '';

            return [$error, $exito];
        }

        $notificados = $this->notificarAvaladores($anio, $dependenciasConTechoNuevo);

        if ($notificados === 0) {
            return [$error, 'Techos actualizados, pero no se encontró ningún avalador asignado a las dependencias modificadas.'];
        }

        return [$error, 'Techos actualizados y se notificó a ' . $notificados . ' avalador(es).'];
    }

    /**
     * Calcula el monto asignado de un nodo: lo que se le ha comprometido directamente
     * (gastos propios o techos delegados a sus hijos) más, para los hijos que no tienen
     * un techo propio asignado, lo comprometido más abajo en su rama (para que un
     * compromiso no se pierda cuando se salta un nivel intermedio sin techo).
     */
    private function calcularAsignadoArbol(array $nodo, array $presupuestosActuales, array $gastadoPorDependencia, array &$mapa): float
    {
        $dependencia = $nodo['dependencia'];
        $asignado = $gastadoPorDependencia[$dependencia['nombre']] ?? 0.0;

        foreach ($nodo['hijos'] as $nodoHijo) {
            $techoHijo = $presupuestosActuales[$nodoHijo['dependencia']['id']]['techo'] ?? null;
            $asignadoHijo = $this->calcularAsignadoArbol($nodoHijo, $presupuestosActuales, $gastadoPorDependencia, $mapa);

            if ($techoHijo === null || (float) $techoHijo <= 0) {
                $asignado += $asignadoHijo;
            }
        }

        $mapa[(int) $dependencia['id']] = $asignado;

        return $asignado;
    }

    private function aplanarArbol(array $arbol): array
    {
        $plano = [];

        foreach ($arbol as $nodo) {
            $plano[] = $nodo['dependencia'];
            $plano = array_merge($plano, $this->aplanarArbol($nodo['hijos']));
        }

        return $plano;
    }

    private function notificarAvaladores(array $anio, array $dependenciasConTechoNuevo): int
    {
        $rolAvaladorId = null;
        foreach ($this->modeloRol->obtenerTodos() as $rol) {
            if ($rol['nombre'] === 'Avalador') {
                $rolAvaladorId = (int) $rol['id'];
                break;
            }
        }

        if ($rolAvaladorId === null) {
            return 0;
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);
        $notificados = 0;

        foreach ($dependenciasConTechoNuevo as $item) {
            $avaladores = $this->modeloUsuario->obtenerPorDependenciaYRol((int) $item['dependencia']['id'], $rolAvaladorId);

            foreach ($avaladores as $avalador) {
                $asunto = 'Techo presupuestal actualizado — ' . $item['dependencia']['nombre'];
                $cuerpo = 'Se definió un nuevo techo presupuestal para "' . $item['dependencia']['nombre']
                    . '" en el año ' . $anio['anio'] . ': $' . number_format($item['techo'], 2) . '.';

                $this->modeloMensaje->crear($remitenteId, (int) $avalador['id'], $asunto, $cuerpo);
                $notificados++;
            }
        }

        return $notificados;
    }
}
