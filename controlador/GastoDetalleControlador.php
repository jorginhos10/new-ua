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

        require __DIR__ . '/../vista/peticiones/detalle-gasto.php';
    }

    private function visibilidad(array $registro): bool
    {
        $rolDestinatarioId = !empty($registro['rol_destinatario_id']) ? (int) $registro['rol_destinatario_id'] : null;

        if ($rolDestinatarioId === null) {
            return false;
        }

        $usuarioActualId = (int) ($_SESSION['usuario_id'] ?? 0);

        // Si se guardó un destinatario específico (porque había más de uno con ese rol en la
        // dependencia), solo esa persona puede abrir este detalle directamente por URL — si no
        // (envíos antiguos, o cuando había un único destinatario), se mantiene el chequeo de
        // siempre por rol+dependencia.
        $usuarioDestinatarioId = !empty($registro['usuario_destinatario_id']) ? (int) $registro['usuario_destinatario_id'] : null;

        if ($usuarioDestinatarioId !== null && $usuarioDestinatarioId !== $usuarioActualId) {
            return false;
        }

        $usuarioActual = (new Usuario())->obtenerPorId($usuarioActualId);
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
