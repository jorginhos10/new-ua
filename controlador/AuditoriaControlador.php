<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';

/**
 * "Auditar": toggle de sesión (no de URL) que activa, en todos los landings, el mismo bypass que
 * ya existía solo dentro de Peticiones ("Ver todo lo que tienes por debajo") — ver todo lo que
 * cae en el árbol de dependencias del superadmin, sin exigir que cada ítem esté dirigido
 * exactamente a él (rol+dependencia+usuario). Ver $_SESSION['modo_auditoria'], leído por
 * GastoControlador/ExtensionControlador/PostgradoControlador/UnisaludControlador/
 * SinExcedentesControlador/PerfilProyectosControlador/SolicitudControlador/PeticionesControlador.
 */
class AuditoriaControlador
{
    public function alternar(): void
    {
        $destino = $this->resolverDestino();

        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;
        $dependenciaUsuario = $dependenciaUsuarioId !== null ? (new Dependencia())->obtenerPorId($dependenciaUsuarioId) : null;
        $esDependenciaRaiz = $dependenciaUsuario !== null && !empty($dependenciaUsuario['es_raiz_superadmin']);

        if ($esDependenciaRaiz) {
            $_SESSION['modo_auditoria'] = empty($_SESSION['modo_auditoria']);
        }

        header('Location: ' . $destino);
        exit;
    }

    /**
     * Solo acepta volver a una ruta interna de la propia aplicación (evita usarse como redirector
     * abierto) — si no viene una válida, usa el referer o el dashboard.
     */
    private function resolverDestino(): string
    {
        $volver = $_GET['volver'] ?? $_POST['volver'] ?? '';

        if (is_string($volver) && $volver !== '' && str_starts_with($volver, 'index.php?') && !str_contains($volver, '://')) {
            return $volver;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && str_starts_with(basename(parse_url($referer, PHP_URL_PATH) ?? ''), 'index.php')) {
            return $referer;
        }

        return 'index.php?ruta=dashboard';
    }
}
