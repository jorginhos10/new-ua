<?php

require_once __DIR__ . '/../modelo/VariableMacroeconomica.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';

class VariableMacroeconomicaControlador
{
    private VariableMacroeconomica $modeloVariable;
    private AnioPresupuestal $modeloAnio;

    public function __construct()
    {
        $this->modeloVariable = new VariableMacroeconomica();
        $this->modeloAnio = new AnioPresupuestal();
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
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminar();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $variables = $this->modeloVariable->obtenerTodas();

        require __DIR__ . '/../vista/variables-macroeconomicas/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloVariable->cambiarEstado($id);
        }

        header('Location: index.php?ruta=variables-macroeconomicas');
        exit;
    }

    private function validarDatos(?int $idExcluir = null): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $valor = trim($_POST['valor'] ?? '');

        if ($nombre === '') {
            return [[], 'El nombre es obligatorio.'];
        }

        if ($anioPresupuestalId <= 0) {
            return [[], 'Selecciona un año presupuestal.'];
        }

        if ($valor === '' || !is_numeric($valor)) {
            return [[], 'El valor es obligatorio y debe ser un número válido.'];
        }

        if ($this->modeloVariable->existeNombreEnAnio($nombre, $anioPresupuestalId, $idExcluir)) {
            return [[], 'Esa variable ya existe para el año seleccionado.'];
        }

        return [[
            'nombre' => $nombre,
            'anio_presupuestal_id' => $anioPresupuestalId,
            'valor' => (float) $valor,
        ], ''];
    }

    private function guardar(): array
    {
        [$datos, $error] = $this->validarDatos();

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloVariable->crear($datos['nombre'], $datos['anio_presupuestal_id'], $datos['valor']);

        return ['', 'Variable macroeconómica agregada correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloVariable->obtenerPorId($id) === null) {
            return ['La variable que intentas editar no existe.', ''];
        }

        [$datos, $error] = $this->validarDatos($id);

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloVariable->actualizar($id, $datos['nombre'], $datos['anio_presupuestal_id'], $datos['valor']);

        return ['', 'Variable macroeconómica actualizada correctamente.'];
    }

    private function eliminar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloVariable->obtenerPorId($id) === null) {
            return ['La variable que intentas eliminar no existe.', ''];
        }

        $this->modeloVariable->eliminar($id);

        return ['', 'Variable macroeconómica eliminada correctamente.'];
    }
}
