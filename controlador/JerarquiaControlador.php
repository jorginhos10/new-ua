<?php

require_once __DIR__ . '/../modelo/Jerarquia.php';
require_once __DIR__ . '/../modelo/TipoDependenciaRol.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/MenuPermiso.php';

class JerarquiaControlador
{
    private Jerarquia $modeloJerarquia;
    private TipoDependenciaRol $modeloRolesPorTipo;
    private Rol $modeloRol;
    private MenuPermiso $modeloMenuPermiso;

    public function __construct()
    {
        $this->modeloJerarquia = new Jerarquia();
        $this->modeloRolesPorTipo = new TipoDependenciaRol();
        $this->modeloRol = new Rol();
        $this->modeloMenuPermiso = new MenuPermiso();
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

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? 'crear';

            if ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminar();
            } elseif ($accion === 'guardar_menu') {
                [$error, $exito] = $this->guardarMenu();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $padresDisponibles = $this->modeloJerarquia->obtenerActivas();
        $todasLasJerarquias = $this->modeloJerarquia->obtenerTodas();
        $jerarquias = $this->construirArbol($todasLasJerarquias);
        $jerarquiasAnidadas = $this->construirArbolAnidado($todasLasJerarquias);
        $tiposDependencia = $this->modeloRolesPorTipo->obtenerTiposDisponibles();
        $roles = $this->modeloRol->obtenerTodos();
        $rolesPorTipo = $this->modeloRolesPorTipo->obtenerMapaCompleto();
        $itemsMenu = require __DIR__ . '/../config/menu_items.php';
        $menuPorTipo = $this->modeloMenuPermiso->obtenerPlantillasCompletas();

        require __DIR__ . '/../vista/jerarquias/index.php';
    }

    private function guardarMenu(): array
    {
        $tipo = trim($_POST['tipo'] ?? '');
        $rolIdTexto = trim($_POST['rol_id'] ?? '');
        $rolId = $rolIdTexto !== '' ? (int) $rolIdTexto : null;
        $menuKeys = $_POST['menu'] ?? [];

        if ($tipo === '') {
            return ['El tipo es inválido.', ''];
        }

        $this->modeloMenuPermiso->guardarPlantillaPorTipo($tipo, $rolId, $menuKeys);

        $rolNombre = null;
        if ($rolId !== null) {
            foreach ($this->modeloRol->obtenerTodos() as $rolCatalogo) {
                if ((int) $rolCatalogo['id'] === $rolId) {
                    $rolNombre = $rolCatalogo['nombre'];
                    break;
                }
            }
        }

        $etiqueta = $rolNombre !== null ? $tipo . ' — ' . $rolNombre : $tipo . ' (general)';

        return ['', 'Plantilla de menú actualizada para "' . $etiqueta . '".'];
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloJerarquia->cambiarEstado($id);
        }

        header('Location: index.php?ruta=jerarquias');
        exit;
    }

    private function guardar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $padreId = (int) ($_POST['padre_id'] ?? 0);
        $techoTexto = trim($_POST['techo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $rolIds = array_map('intval', $_POST['roles'] ?? []);

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        $techo = null;

        if ($techoTexto !== '') {
            if (!is_numeric($techoTexto) || (float) $techoTexto < 0) {
                return ['El techo debe ser un número válido mayor o igual a 0.', ''];
            }

            $techo = (float) $techoTexto;
        }

        $padre = null;

        if ($padreId > 0) {
            $padre = $this->modeloJerarquia->obtenerPorId($padreId);

            if ($padre === null) {
                return ['La jerarquía padre seleccionada no existe.', ''];
            }
        }

        if ($padre !== null && $techo !== null) {
            $disponible = $this->modeloJerarquia->obtenerTechoDisponible($padreId);

            if ($disponible !== null && $techo > $disponible) {
                return [
                    'El techo supera lo disponible en "' . $padre['nombre'] . '". Disponible: ' . number_format($disponible, 2) . '.',
                    '',
                ];
            }
        }

        $this->modeloJerarquia->crear($nombre, $padreId > 0 ? $padreId : null, $techo, $tipo !== '' ? $tipo : null);

        if ($tipo !== '') {
            $this->modeloRolesPorTipo->guardarAsociaciones($tipo, $rolIds);
        }

        return ['', 'Jerarquía agregada correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $padreId = (int) ($_POST['padre_id'] ?? 0);
        $tipo = trim($_POST['tipo'] ?? '');
        $rolIds = array_map('intval', $_POST['roles'] ?? []);

        if ($id <= 0) {
            return ['Jerarquía no válida.', ''];
        }

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        $nodo = $this->modeloJerarquia->obtenerPorId($id);

        if ($nodo === null) {
            return ['La jerarquía que intentas editar no existe.', ''];
        }

        $padreIdFinal = $padreId > 0 ? $padreId : null;

        if ($padreIdFinal !== null) {
            $padre = $this->modeloJerarquia->obtenerPorId($padreIdFinal);

            if ($padre === null) {
                return ['La jerarquía padre seleccionada no existe.', ''];
            }

            if ($this->creaCiclo($id, $padreIdFinal)) {
                return ['No puedes asignar como padre a un nodo que está dentro de su propia rama.', ''];
            }
        }

        $this->modeloJerarquia->actualizar($id, $nombre, $padreIdFinal, $tipo !== '' ? $tipo : null);

        if ($tipo !== '') {
            $this->modeloRolesPorTipo->guardarAsociaciones($tipo, $rolIds);
        }

        return ['', 'Jerarquía actualizada correctamente.'];
    }

    private function creaCiclo(int $id, int $nuevoPadreId): bool
    {
        if ($nuevoPadreId === $id) {
            return true;
        }

        $actual = $this->modeloJerarquia->obtenerPorId($nuevoPadreId);

        while ($actual !== null) {
            if ($actual['padre_id'] === null) {
                return false;
            }

            if ((int) $actual['padre_id'] === $id) {
                return true;
            }

            $actual = $this->modeloJerarquia->obtenerPorId((int) $actual['padre_id']);
        }

        return false;
    }

    private function eliminar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            return ['Jerarquía no válida.', ''];
        }

        if ($this->modeloJerarquia->tieneHijos($id)) {
            return ['No puedes eliminar esta jerarquía porque tiene ramas debajo de ella. Elimínalas o reasígnalas primero.', ''];
        }

        $this->modeloJerarquia->eliminar($id);

        return ['', 'Jerarquía eliminada correctamente.'];
    }

    private function construirArbol(array $nodos): array
    {
        $porPadre = [];
        $sumaHijosPorPadre = [];

        foreach ($nodos as $nodo) {
            $padreId = $nodo['padre_id'] !== null ? (int) $nodo['padre_id'] : 0;
            $porPadre[$padreId][] = $nodo;

            if ($nodo['padre_id'] !== null && $nodo['estado'] === 'activo' && $nodo['techo'] !== null) {
                $sumaHijosPorPadre[(int) $nodo['padre_id']] = ($sumaHijosPorPadre[(int) $nodo['padre_id']] ?? 0.0) + (float) $nodo['techo'];
            }
        }

        $resultado = [];
        $this->agregarNivel($porPadre, $sumaHijosPorPadre, 0, 0, $resultado);

        return $resultado;
    }

    private function agregarNivel(array $porPadre, array $sumaHijosPorPadre, int $padreId, int $nivel, array &$resultado): void
    {
        foreach ($porPadre[$padreId] ?? [] as $nodo) {
            $nodo['nivel'] = $nivel;
            $nodo['techo_disponible'] = $nodo['techo'] !== null
                ? (float) $nodo['techo'] - ($sumaHijosPorPadre[(int) $nodo['id']] ?? 0.0)
                : null;
            $resultado[] = $nodo;
            $this->agregarNivel($porPadre, $sumaHijosPorPadre, (int) $nodo['id'], $nivel + 1, $resultado);
        }
    }

    private function construirArbolAnidado(array $nodos): array
    {
        $porPadre = [];

        foreach ($nodos as $nodo) {
            $padreId = $nodo['padre_id'] !== null ? (int) $nodo['padre_id'] : 0;
            $porPadre[$padreId][] = $nodo;
        }

        return $this->anidarHijos($porPadre, 0);
    }

    private function anidarHijos(array $porPadre, int $padreId): array
    {
        $ramas = [];

        foreach ($porPadre[$padreId] ?? [] as $nodo) {
            $nodo['hijos'] = $this->anidarHijos($porPadre, (int) $nodo['id']);
            $ramas[] = $nodo;
        }

        return $ramas;
    }
}
