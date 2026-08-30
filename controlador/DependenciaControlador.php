<?php

require_once __DIR__ . '/../modelo/Dependencia.php';

class DependenciaControlador
{
    private Dependencia $modeloDependencia;

    public function __construct()
    {
        $this->modeloDependencia = new Dependencia();
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
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'cambiar_no_monetizable') {
                $this->cambiarNoMonetizable();
            } elseif ($accion === 'cambiar_no_listar') {
                $this->cambiarNoListar();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminar();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $dependencias = $this->modeloDependencia->obtenerTodas();
        $flujoIdEfectivoPorId = $this->modeloDependencia->calcularFlujoEfectivo($dependencias);
        $familiaPorId = $this->modeloDependencia->calcularFamiliaPorId($dependencias);

        $porId = [];
        foreach ($dependencias as $dependencia) {
            $porId[$dependencia['id']] = $dependencia;
        }

        $flujoPorId = [];
        foreach ($dependencias as $dependencia) {
            $flujoId = $flujoIdEfectivoPorId[(int) $dependencia['id']] ?? null;
            $flujoPorId[$dependencia['id']] = $flujoId !== null ? ($porId[$flujoId]['nombre'] ?? null) : null;
        }

        $tagsFamilia = $this->calcularTagsFamilia($dependencias);

        require __DIR__ . '/../vista/dependencias/index.php';
    }

    private function calcularTagsFamilia(array $dependencias): array
    {
        $porCodigo = [];
        foreach ($dependencias as $dependencia) {
            $porCodigo[$dependencia['codigo']] = $dependencia;
        }

        $tags = [];
        foreach ($dependencias as $dependencia) {
            $codigo = $dependencia['codigo'];

            if ($codigo === '0' || strlen($codigo) !== 6) {
                continue;
            }

            $familia = substr($codigo, 0, 2);

            if (isset($tags[$familia])) {
                continue;
            }

            $codigoFamiliaRaiz = $familia . '0101';
            $tags[$familia] = $porCodigo[$codigoFamiliaRaiz]['nombre'] ?? ('Familia ' . $familia);
        }

        ksort($tags);

        return $tags;
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $dependencia = $this->modeloDependencia->obtenerPorId($id);

            if ($dependencia === null || (int) ($dependencia['es_raiz_superadmin'] ?? 0) !== 1) {
                $this->modeloDependencia->cambiarEstado($id);
            }
        }

        header('Location: index.php?ruta=dependencias');
        exit;
    }

    private function cambiarNoMonetizable(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloDependencia->cambiarNoMonetizable($id);
        }

        header('Location: index.php?ruta=dependencias');
        exit;
    }

    private function cambiarNoListar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloDependencia->cambiarNoListar($id);
        }

        header('Location: index.php?ruta=dependencias');
        exit;
    }

    private function eliminar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            return ['', ''];
        }

        $dependencia = $this->modeloDependencia->obtenerPorId($id);

        if ($dependencia !== null && (int) ($dependencia['es_raiz_superadmin'] ?? 0) === 1) {
            return ['No puedes eliminar la dependencia raíz del Superadmin.', ''];
        }

        $this->modeloDependencia->eliminar($id);

        return ['', 'Dependencia eliminada correctamente.'];
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');

        if ($codigo === '' || $nombre === '') {
            return ['El código y el nombre son obligatorios.', ''];
        }

        if ($this->modeloDependencia->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloDependencia->crear($codigo, $nombre, $tipo !== '' ? $tipo : null);

        return ['', 'Dependencia agregada correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $flujoIdTexto = trim($_POST['flujo_id'] ?? '');

        if ($id <= 0 || $this->modeloDependencia->obtenerPorId($id) === null) {
            return ['La dependencia que intentas editar no existe.', ''];
        }

        if ($codigo === '' || $nombre === '') {
            return ['El código y el nombre son obligatorios.', ''];
        }

        if ($this->modeloDependencia->existeCodigo($codigo, $id)) {
            return ['Ese código ya existe.', ''];
        }

        $flujoId = $flujoIdTexto !== '' ? (int) $flujoIdTexto : null;

        if ($flujoId === $id) {
            return ['Una dependencia no puede tener flujo hacia sí misma.', ''];
        }

        if ($flujoId !== null && $this->modeloDependencia->obtenerPorId($flujoId) === null) {
            return ['La dependencia de flujo seleccionada no existe.', ''];
        }

        $this->modeloDependencia->actualizar($id, $codigo, $nombre, $tipo !== '' ? $tipo : null, $flujoId);

        return ['', 'Dependencia actualizada correctamente.'];
    }
}
