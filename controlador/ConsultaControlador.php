<?php

require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/Dependencia.php';

/**
 * Landing de consulta de solo lectura: vista consolidada de Gastos de toda la universidad,
 * pensada para el rol Consejo Superior (sin dependencia propia asignada), con filtros por
 * año presupuestal, sede, dependencia, estado y texto libre.
 */
class ConsultaControlador
{
    private const NOMBRES_MESES = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $tipo = ($_GET['tipo'] ?? '') === 'ingresos' ? 'ingresos' : 'gastos';

        $anios = (new AnioPresupuestal())->obtenerActivos();
        $anioSeleccionadoId = (int) ($_GET['anio'] ?? 0);

        if ($anioSeleccionadoId <= 0 || !$this->existeAnio($anios, $anioSeleccionadoId)) {
            $anioSeleccionadoId = !empty($anios) ? (int) $anios[0]['id'] : 0;
        }

        $sedes = (new Sede())->obtenerTodas();
        $dependencias = array_values(array_filter(
            (new Dependencia())->obtenerActivas(),
            static fn (array $d): bool => (int) ($d['es_raiz_superadmin'] ?? 0) === 0
        ));

        $sedeFiltro = trim($_GET['sede'] ?? '');
        $dependenciaFiltro = trim($_GET['dependencia'] ?? '');
        $estadoFiltro = trim($_GET['estado'] ?? '');
        $textoFiltro = trim($_GET['buscar'] ?? '');

        $filasTabla = [];

        if ($tipo === 'gastos') {
            $gastos = $anioSeleccionadoId > 0 ? (new Gasto())->obtenerPorAnio($anioSeleccionadoId) : [];

            $gastos = array_values(array_filter($gastos, function (array $gasto) use ($sedeFiltro, $dependenciaFiltro, $estadoFiltro, $textoFiltro): bool {
                if ($sedeFiltro !== '' && $gasto['sede_nombre'] !== $sedeFiltro) {
                    return false;
                }

                if ($dependenciaFiltro !== '' && $gasto['dependencia'] !== $dependenciaFiltro) {
                    return false;
                }

                if ($estadoFiltro !== '' && $gasto['estado'] !== $estadoFiltro) {
                    return false;
                }

                if ($textoFiltro !== '') {
                    $texto = mb_strtolower($textoFiltro);
                    $campo = mb_strtolower(
                        ($gasto['actividad'] ?? '') . ' '
                        . ($gasto['rubro_descripcion'] ?? $gasto['rubro_texto'] ?? '') . ' '
                        . ($gasto['insumo'] ?? '')
                    );

                    if (mb_strpos($campo, $texto) === false) {
                        return false;
                    }
                }

                return true;
            }));

            $filasTabla = array_map(function (array $gasto): array {
                $mesesGasto = $gasto['meses'] !== ''
                    ? array_map(fn ($mes) => self::NOMBRES_MESES[(int) $mes] ?? $mes, explode(',', $gasto['meses']))
                    : [];

                return [
                    'actividad' => $gasto['actividad'],
                    'rubro' => $gasto['rubro_descripcion'] ?? $gasto['rubro_texto'] ?? '',
                    'insumo' => $gasto['insumo'],
                    'cantidad' => (int) $gasto['cantidad'],
                    'costo_unitario' => (float) $gasto['costo_unitario'],
                    'valor_total' => (float) $gasto['valor_total'],
                    'sede' => $gasto['sede_nombre'],
                    'dependencia' => $gasto['dependencia'],
                    'linea' => $gasto['linea_nombre'],
                    'motor' => $gasto['motor_nombre'],
                    'proyecto' => $gasto['proyecto_nombre'],
                    'estado' => $gasto['estado'] === 'enviado' ? 'Enviado' : 'Borrador',
                    'meses' => implode(', ', $mesesGasto),
                ];
            }, $gastos);
        }

        require __DIR__ . '/../vista/consulta/index.php';
    }

    private function existeAnio(array $anios, int $id): bool
    {
        foreach ($anios as $anio) {
            if ((int) $anio['id'] === $id) {
                return true;
            }
        }

        return false;
    }
}
