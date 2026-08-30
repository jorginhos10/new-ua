<?php

require_once __DIR__ . '/../modelo/SolicitudArl.php';
require_once __DIR__ . '/../modelo/SolicitudMonitor.php';
require_once __DIR__ . '/../modelo/SolicitudOps.php';
require_once __DIR__ . '/../modelo/SolicitudPeticion.php';

class SolicitudDetalleControlador
{
    private const TIPOS_MONITOR = [
        'solidario_academico' => 'Solidario (académico)',
        'deportivo' => 'Deportivo',
        'cultural' => 'Cultural',
        'administrativo' => 'Administrativo',
    ];

    private const PERFILES_OPS = [
        'profesional_especializado' => 'Profesional especializado',
        'profesional_universitario' => 'Profesional universitario',
        'tecnico_administrativo' => 'Técnico administrativo',
        'asesor' => 'Asesor',
        'auxiliares_administrativos' => 'Auxiliares administrativos',
    ];

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

        $tipo = $_GET['tipo'] ?? '';
        $id = (int) ($_GET['id'] ?? 0);

        $tabsPorTipo = ['arl' => 'arl', 'monitores' => 'monitores', 'ops' => 'ops', 'otros' => 'otros'];

        if (!isset($tabsPorTipo[$tipo]) || $id <= 0) {
            header('Location: index.php?ruta=solicitudes');
            exit;
        }

        $tiposMonitor = self::TIPOS_MONITOR;
        $perfilesOps = self::PERFILES_OPS;

        $volverSolicitado = $_GET['volver'] ?? '';
        $rutaVolver = 'index.php?ruta=solicitudes&tab=' . $tabsPorTipo[$tipo];
        $etiquetaVolver = 'Volver a Solicitudes';

        if (str_starts_with($volverSolicitado, 'index.php?') && !str_contains($volverSolicitado, '://')) {
            $rutaVolver = $volverSolicitado;
            $etiquetaVolver = str_contains($volverSolicitado, 'ruta=peticiones') ? 'Volver a Peticiones' : 'Volver a Solicitudes';
        }

        switch ($tipo) {
            case 'arl':
                $registro = (new SolicitudArl())->obtenerPorId($id);
                break;
            case 'monitores':
                $registro = (new SolicitudMonitor())->obtenerPorId($id);
                break;
            case 'ops':
                $registro = (new SolicitudOps())->obtenerDetallePorId($id);
                break;
            default:
                $registro = (new SolicitudPeticion())->obtenerPorId($id);
                break;
        }

        if ($registro === null) {
            header('Location: index.php?ruta=solicitudes&tab=' . $tabsPorTipo[$tipo]);
            exit;
        }

        require __DIR__ . '/../vista/solicitudes/detalle.php';
    }
}
