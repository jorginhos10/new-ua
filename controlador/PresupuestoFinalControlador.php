<?php

require_once __DIR__ . '/../modelo/PresupuestoFinal.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/ExportadorExcel.php';

/** Exportación del presupuesto final a Excel (solo administrador). */
class PresupuestoFinalControlador
{
    private PresupuestoFinal $modeloPresupuesto;
    private AnioPresupuestal $modeloAnio;

    public function __construct()
    {
        $this->modeloPresupuesto = new PresupuestoFinal();
        $this->modeloAnio = new AnioPresupuestal();
    }

    public function index(): void
    {
        $this->verificarAdministrador();

        $anios = $this->modeloAnio->obtenerActivos();
        $anioSeleccionado = $this->anioDesdeRequest($anios);
        $resumen = $anioSeleccionado !== null
            ? $this->modeloPresupuesto->construir((int) $anioSeleccionado['id'], (int) $anioSeleccionado['anio'])
            : null;

        require __DIR__ . '/../vista/presupuesto-final/index.php';
    }

    public function exportar(): void
    {
        $this->verificarAdministrador();

        $anios = $this->modeloAnio->obtenerActivos();
        $anioSeleccionado = $this->anioDesdeRequest($anios);

        if ($anioSeleccionado === null) {
            header('Location: index.php?ruta=presupuesto-final');
            exit;
        }

        $resumen = $this->modeloPresupuesto->construir((int) $anioSeleccionado['id'], (int) $anioSeleccionado['anio']);

        ExportadorExcel::descargar(
            'presupuesto-final-' . $anioSeleccionado['anio'] . '.xlsx',
            PresupuestoFinal::ENCABEZADOS,
            $resumen['filas']
        );
        exit;
    }

    private function verificarAdministrador(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }

    /** Año pedido en la URL si está activo; si no, el primero activo. */
    private function anioDesdeRequest(array $anios): ?array
    {
        $idPedido = (int) ($_GET['anio_id'] ?? 0);

        foreach ($anios as $anio) {
            if ((int) $anio['id'] === $idPedido) {
                return $anio;
            }
        }

        return $anios[0] ?? null;
    }
}
