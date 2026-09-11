<?php

require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/GastoExtension.php';
require_once __DIR__ . '/../modelo/GastoPostgrado.php';
require_once __DIR__ . '/../modelo/GastoUnisalud.php';
require_once __DIR__ . '/../modelo/GastoSinExcedentes.php';
require_once __DIR__ . '/../modelo/IngresoExtension.php';
require_once __DIR__ . '/../modelo/IngresoPostgrado.php';
require_once __DIR__ . '/../modelo/IngresoUnisalud.php';
require_once __DIR__ . '/../modelo/IngresoSinExcedentes.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';

/**
 * Landing individual de solo lectura para un gasto/ingreso enviado como petición. Existe porque
 * el destinatario (avalador) no tiene por qué tener acceso a la vista del módulo de origen
 * (Extensión, Postgrado, Unisalud, Sin excedentes) — esa vista está pensada para quien gestiona
 * la dependencia emisora, no para quien la recibe.
 */
class GastoDetalleControlador
{
    private const ORIGENES = [
        'gasto_principal' => ['modelo' => 'Gasto', 'titulo' => 'Egreso — Gasto', 'esGasto' => true],
        'gasto_extension' => ['modelo' => 'GastoExtension', 'titulo' => 'Egreso — Extensión', 'esGasto' => true],
        'gasto_postgrado' => ['modelo' => 'GastoPostgrado', 'titulo' => 'Egreso — Postgrado', 'esGasto' => true],
        'gasto_unisalud' => ['modelo' => 'GastoUnisalud', 'titulo' => 'Egreso — Unidad de Salud', 'esGasto' => true],
        'gasto_sin_excedentes' => ['modelo' => 'GastoSinExcedentes', 'titulo' => 'Egreso — Sin excedentes', 'esGasto' => true],
        'ingreso_extension' => ['modelo' => 'IngresoExtension', 'titulo' => 'Ingreso — Extensión', 'esGasto' => false],
        'ingreso_postgrado' => ['modelo' => 'IngresoPostgrado', 'titulo' => 'Ingreso — Postgrado', 'esGasto' => false],
        'ingreso_unisalud' => ['modelo' => 'IngresoUnisalud', 'titulo' => 'Ingreso — Unidad de Salud', 'esGasto' => false],
        'ingreso_sin_excedentes' => ['modelo' => 'IngresoSinExcedentes', 'titulo' => 'Ingreso — Sin excedentes', 'esGasto' => false],
    ];

    /**
     * A dónde vuelve el botón azul de esta pantalla: siempre al módulo de origen del registro
     * (donde quien lo envió puede editarlo), no a Peticiones — esta pantalla es solo una parada
     * de lectura para el destinatario, no el lugar "anterior" en el flujo de quien la abrió.
     */
    private const RUTA_VOLVER = [
        'gasto_principal' => ['ruta' => 'index.php?ruta=gastos', 'etiqueta' => 'Volver a Gastos'],
        'gasto_extension' => ['ruta' => 'index.php?ruta=extension', 'etiqueta' => 'Volver a Extensión'],
        'gasto_postgrado' => ['ruta' => 'index.php?ruta=postgrado', 'etiqueta' => 'Volver a Postgrado'],
        'gasto_unisalud' => ['ruta' => 'index.php?ruta=unisalud', 'etiqueta' => 'Volver a Unidad de Salud'],
        'gasto_sin_excedentes' => ['ruta' => 'index.php?ruta=sin-excedentes', 'etiqueta' => 'Volver a Sin excedentes'],
        'ingreso_extension' => ['ruta' => 'index.php?ruta=extension', 'etiqueta' => 'Volver a Extensión'],
        'ingreso_postgrado' => ['ruta' => 'index.php?ruta=postgrado', 'etiqueta' => 'Volver a Postgrado'],
        'ingreso_unisalud' => ['ruta' => 'index.php?ruta=unisalud', 'etiqueta' => 'Volver a Unidad de Salud'],
        'ingreso_sin_excedentes' => ['ruta' => 'index.php?ruta=sin-excedentes', 'etiqueta' => 'Volver a Sin excedentes'],
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

        $origen = $_GET['origen'] ?? '';
        $id = (int) ($_GET['id'] ?? 0);

        if (!isset(self::ORIGENES[$origen]) || $id <= 0) {
            header('Location: index.php?ruta=peticiones');
            exit;
        }

        $definicion = self::ORIGENES[$origen];
        $claseModelo = $definicion['modelo'];
        $modelo = new $claseModelo();
        $esGasto = $definicion['esGasto'];

        $registro = $esGasto ? $modelo->obtenerDetallePorId($id) : $modelo->obtenerPorId($id);

        if ($registro === null) {
            header('Location: index.php?ruta=peticiones');
            exit;
        }

        if (!$this->visibilidad($registro)) {
            header('Location: index.php?ruta=peticiones');
            exit;
        }

        $tituloPagina = $definicion['titulo'];

        $destinoVolver = self::RUTA_VOLVER[$origen] ?? ['ruta' => 'index.php?ruta=peticiones', 'etiqueta' => 'Volver a Peticiones'];
        $rutaVolver = $destinoVolver['ruta'];
        $etiquetaVolver = $destinoVolver['etiqueta'];

        require __DIR__ . '/../vista/peticiones/detalle-gasto.php';
    }

    private function visibilidad(array $registro): bool
    {
        $rolDestinatarioId = !empty($registro['rol_destinatario_id']) ? (int) $registro['rol_destinatario_id'] : null;

        if ($rolDestinatarioId === null) {
            return false;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $rolUsuarioId = !empty($usuarioActual['rol_id']) ? (int) $usuarioActual['rol_id'] : null;

        if ($rolUsuarioId !== $rolDestinatarioId) {
            return false;
        }

        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;

        if ($dependenciaUsuarioId === null) {
            return false;
        }

        $dependenciaUsuario = (new Dependencia())->obtenerPorId($dependenciaUsuarioId);

        return $dependenciaUsuario !== null && $dependenciaUsuario['nombre'] === $registro['dependencia'];
    }
}
