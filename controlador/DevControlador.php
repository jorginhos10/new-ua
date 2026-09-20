<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';

/**
 * Zona de pruebas visible solo para usuarios de la dependencia "Superadmin" (es_raiz_superadmin).
 * Las vistas que se registren en VISTAS_PRUEBA pueden tomar datos reales de la plataforma como
 * referencia, pero viven aparte (vista/dev/pruebas/) y no reemplazan ni actualizan ningún
 * componente real del sistema hasta que se pida explícitamente.
 */
class DevControlador
{
    private const VISTAS_PRUEBA = [
        'tabla' => [
            'titulo' => 'Tabla',
            'descripcion' => 'Nueva versión de la tarjeta de trabajo: topbar con grupos de acciones colapsables, y tabla con encabezados fijos, orden, filtro y selección de filas. Referencia: Gastos.',
        ],
        'tabla-v1' => [
            'titulo' => 'Tabla v1',
            'descripcion' => 'Diseño de tabla ya existente en el sistema (la página "Consulta" del rol Consejo Superior), para comparar contra el nuevo prototipo "Tabla".',
            'href' => 'index.php?ruta=consulta',
        ],
    ];

    public function index(): void
    {
        $this->verificarAcceso();

        $vistas = self::VISTAS_PRUEBA;

        require __DIR__ . '/../vista/dev/index.php';
    }

    public function vista(): void
    {
        $this->verificarAcceso();

        $slug = (string) ($_GET['v'] ?? '');
        $archivo = __DIR__ . '/../vista/dev/pruebas/' . basename($slug) . '.php';

        if (!isset(self::VISTAS_PRUEBA[$slug]) || !is_file($archivo)) {
            header('Location: index.php?ruta=dev');
            exit;
        }

        $vistaActual = self::VISTAS_PRUEBA[$slug];

        require $archivo;
    }

    private function verificarAcceso(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependencia = !empty($usuarioActual['dependencia_id'])
            ? (new Dependencia())->obtenerPorId((int) $usuarioActual['dependencia_id'])
            : null;

        $esDependenciaSuperadmin = $dependencia !== null && !empty($dependencia['es_raiz_superadmin']);

        if (!$esDependenciaSuperadmin) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }
}
