<?php

require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/PresupuestoVersion.php';
require_once __DIR__ . '/../modelo/Gasto.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Mensaje.php';

class HistorialControlador
{
    private AnioPresupuestal $modeloAnio;
    private Dependencia $modeloDependencia;
    private PresupuestoDependencia $modeloPresupuestoDependencia;
    private PresupuestoVersion $modeloVersion;
    private Gasto $modeloGasto;
    private Rol $modeloRol;
    private Usuario $modeloUsuario;
    private Mensaje $modeloMensaje;

    public function __construct()
    {
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloDependencia = new Dependencia();
        $this->modeloPresupuestoDependencia = new PresupuestoDependencia();
        $this->modeloVersion = new PresupuestoVersion();
        $this->modeloGasto = new Gasto();
        $this->modeloRol = new Rol();
        $this->modeloUsuario = new Usuario();
        $this->modeloMensaje = new Mensaje();
    }

    public function versiones(): void
    {
        $this->requerirAdministrador();

        $error = '';
        $exito = '';
        $esSuperAdmin = $this->esSuperAdmin();

        $anios = $this->modeloAnio->obtenerTodos();
        $anioSeleccionadoId = $this->resolverAnioSeleccionado($anios);
        $anioSeleccionado = $this->buscarAnio($anios, $anioSeleccionadoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['accion'] ?? '', ['restaurar', 'restaurar_notificar'], true)) {
            if (!$esSuperAdmin) {
                $error = 'Solo el superadministrador puede restaurar una versión.';
            } else {
                [$error, $exito] = $this->restaurar($anioSeleccionadoId, ($_POST['accion'] ?? '') === 'restaurar_notificar');
            }
        }

        $versiones = $anioSeleccionadoId > 0 ? $this->modeloVersion->obtenerVersionesPorAnio($anioSeleccionadoId) : [];

        foreach ($versiones as $indice => &$version) {
            $version['cambios'] = $this->modeloVersion->obtenerCambiosPorVersion((int) $version['id']);
            // $versiones está ordenado de más reciente a más antiguo, así que la "anterior"
            // cronológicamente es la siguiente en el arreglo.
            $version['usuario_anterior'] = $versiones[$indice + 1]['usuario_nombre'] ?? null;
        }
        unset($version);

        require __DIR__ . '/../vista/historial/versiones.php';
    }

    private function restaurar(int $anioSeleccionadoId, bool $notificar): array
    {
        $versionId = (int) ($_POST['version_id'] ?? 0);
        $version = $versionId > 0 ? $this->modeloVersion->obtenerPorId($versionId) : null;

        if ($version === null || (int) $version['anio_presupuestal_id'] !== $anioSeleccionadoId) {
            return ['La versión que intentas restaurar no existe.', ''];
        }

        $cambiosVersion = $this->modeloVersion->obtenerCambiosPorVersion($versionId);

        if (empty($cambiosVersion)) {
            return ['Esa versión no tiene cambios para restaurar.', ''];
        }

        $porId = [];
        foreach ($this->modeloDependencia->obtenerTodas() as $dependencia) {
            $porId[(int) $dependencia['id']] = $dependencia;
        }

        $presupuestosActuales = $this->modeloPresupuestoDependencia->obtenerPorAnio($anioSeleccionadoId);
        $cambiosRestauracion = [];
        $dependenciasRestauradas = [];

        foreach ($cambiosVersion as $cambio) {
            $dependenciaId = (int) $cambio['dependencia_id'];
            $dependencia = $porId[$dependenciaId] ?? null;

            if ($dependencia === null) {
                continue;
            }

            $minimoActual = isset($presupuestosActuales[$dependenciaId]['minimo']) ? (float) $presupuestosActuales[$dependenciaId]['minimo'] : null;
            $techoActual = isset($presupuestosActuales[$dependenciaId]['techo']) ? (float) $presupuestosActuales[$dependenciaId]['techo'] : null;

            $minimoRestaurado = $cambio['minimo_nuevo'] !== null ? (float) $cambio['minimo_nuevo'] : null;
            $techoRestaurado = $cambio['techo_nuevo'] !== null ? (float) $cambio['techo_nuevo'] : null;

            if ($minimoRestaurado === $minimoActual && $techoRestaurado === $techoActual) {
                continue;
            }

            $this->modeloPresupuestoDependencia->guardar($anioSeleccionadoId, $dependenciaId, $minimoRestaurado, $techoRestaurado);

            $padreId = !empty($dependencia['flujo_id']) ? (int) $dependencia['flujo_id'] : null;
            $dependenciaPadre = $padreId !== null ? ($porId[$padreId] ?? null) : null;
            $this->modeloGasto->sincronizarAutomaticoTechoHija($anioSeleccionadoId, $dependenciaPadre, $dependencia, $techoRestaurado);

            $cambiosRestauracion[] = [
                'dependencia_id' => $dependenciaId,
                'minimo_anterior' => $minimoActual,
                'minimo_nuevo' => $minimoRestaurado,
                'techo_anterior' => $techoActual,
                'techo_nuevo' => $techoRestaurado,
            ];

            if ($techoRestaurado !== null && $techoRestaurado !== $techoActual) {
                $dependenciasRestauradas[] = ['dependencia' => $dependencia, 'techo' => $techoRestaurado];
            }
        }

        if (empty($cambiosRestauracion)) {
            return ['', 'Esa versión ya coincide con los valores actuales, no había nada que restaurar.'];
        }

        $this->modeloVersion->crearVersion($anioSeleccionadoId, (int) ($_SESSION['usuario_id'] ?? 0), $cambiosRestauracion);

        if (!$notificar || empty($dependenciasRestauradas)) {
            return ['', 'Se restauró la versión correctamente.'];
        }

        $anio = $this->modeloAnio->obtenerPorId($anioSeleccionadoId);
        $notificados = $this->notificarAvaladores($anio, $dependenciasRestauradas);

        if ($notificados === 0) {
            return ['', 'Se restauró la versión, pero no se encontró ningún avalador asignado a las dependencias restauradas.'];
        }

        return ['', 'Se restauró la versión y se notificó a ' . $notificados . ' avalador(es).'];
    }

    private function notificarAvaladores(?array $anio, array $dependenciasConTechoNuevo): int
    {
        if ($anio === null) {
            return 0;
        }

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
                $asunto = 'Techo presupuestal restaurado — ' . $item['dependencia']['nombre'];
                $cuerpo = 'Se restauró el techo presupuestal de "' . $item['dependencia']['nombre']
                    . '" en el año ' . $anio['anio'] . ' a: $' . number_format($item['techo'], 2) . '.';

                $this->modeloMensaje->crear($remitenteId, (int) $avalador['id'], $asunto, $cuerpo);
                $notificados++;
            }
        }

        return $notificados;
    }

    private function esSuperAdmin(): bool
    {
        $usuario = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        return $usuario !== null && (int) ($usuario['es_super_admin'] ?? 0) === 1;
    }

    private function requerirAdministrador(): void
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

    private function resolverAnioSeleccionado(array $anios): int
    {
        if (empty($anios)) {
            return 0;
        }

        $anioId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : (int) $anios[0]['id'];
        $idsValidos = array_map('intval', array_column($anios, 'id'));

        if (!in_array($anioId, $idsValidos, true)) {
            $anioId = (int) $anios[0]['id'];
        }

        return $anioId;
    }

    private function buscarAnio(array $anios, int $anioId): ?array
    {
        foreach ($anios as $anio) {
            if ((int) $anio['id'] === $anioId) {
                return $anio;
            }
        }

        return null;
    }
}
