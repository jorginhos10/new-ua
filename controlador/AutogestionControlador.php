<?php

require_once __DIR__ . '/../modelo/AutogestionItem.php';
require_once __DIR__ . '/../modelo/AutogestionPorcentaje.php';

class AutogestionControlador
{
    private AutogestionItem $modeloAutogestion;
    private AutogestionPorcentaje $modeloPorcentaje;

    private const MODULOS = [
        'extension' => 'Extensión',
        'postgrado' => 'Postgrado',
        'unisalud' => 'Unidad de Salud',
        'sin-excedentes' => 'Sin excedentes',
    ];

    public function __construct()
    {
        $this->modeloAutogestion = new AutogestionItem();
        $this->modeloPorcentaje = new AutogestionPorcentaje();
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

        $modulos = self::MODULOS;
        $moduloActivo = isset($_GET['tab']) && array_key_exists($_GET['tab'], $modulos) ? $_GET['tab'] : array_key_first($modulos);

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $moduloPost = $_POST['modulo'] ?? $moduloActivo;

            if (array_key_exists($moduloPost, $modulos)) {
                $moduloActivo = $moduloPost;
            }

            if ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'guardar_porcentaje') {
                [$error, $exito] = $this->guardarPorcentaje($moduloActivo);
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar($moduloActivo);
            } else {
                [$error, $exito] = $this->guardar($moduloActivo);
            }
        }

        $items = $this->modeloAutogestion->obtenerTodos($moduloActivo);
        $porcentajes = $this->modeloPorcentaje->obtenerPorModulo($moduloActivo);

        require __DIR__ . '/../vista/autogestion/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloAutogestion->cambiarEstado($id);
        }

        $tab = $_POST['modulo'] ?? '';
        $destino = array_key_exists($tab, self::MODULOS) ? 'index.php?ruta=autogestion&tab=' . $tab : 'index.php?ruta=autogestion';

        header('Location: ' . $destino);
        exit;
    }

    private function guardar(string $modulo): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        if ($this->modeloAutogestion->existeNombre($nombre, $modulo)) {
            return ['Ese ítem ya existe.', ''];
        }

        $this->modeloAutogestion->crear($nombre, $modulo);

        return ['', 'Ítem de autogestión agregado correctamente.'];
    }

    private function actualizar(string $modulo): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $this->modeloAutogestion->obtenerPorId($id) === null) {
            return ['El ítem que intentas editar no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        if ($this->modeloAutogestion->existeNombre($nombre, $modulo, $id)) {
            return ['Ese ítem ya existe.', ''];
        }

        $this->modeloAutogestion->actualizar($id, $nombre);

        return ['', 'Ítem de autogestión actualizado correctamente.'];
    }

    private function guardarPorcentaje(string $modulo): array
    {
        $campos = ['costos', 'inversiones', 'excedentes'];

        if ($modulo === 'postgrado') {
            $campos[] = 'contribucion_postgrado';
        }

        $valores = [];

        foreach ($campos as $campo) {
            if (($_POST['no_aplica_' . $campo] ?? '') === '1') {
                $valores[$campo] = null;
                continue;
            }

            $valor = $_POST[$campo] ?? '';

            if (!is_numeric($valor) || (float) $valor < 0 || (float) $valor > 100) {
                return ['Cada porcentaje debe ser un número entre 0 y 100, o marcarse como No aplica.', ''];
            }

            $valores[$campo] = (float) $valor;
        }

        $suma = array_sum(array_filter($valores, static fn ($valor) => $valor !== null));

        if ($suma > 100) {
            $nombreCampos = $modulo === 'postgrado'
                ? 'Costos, Inversiones, Excedentes y Contribución a posgrado'
                : 'Costos, Inversiones y Excedentes';

            return ['La suma de ' . $nombreCampos . ' no puede superar el 100%.', ''];
        }

        $this->modeloPorcentaje->guardar(
            $modulo,
            $valores['costos'],
            $valores['inversiones'],
            $valores['excedentes'],
            $valores['contribucion_postgrado'] ?? null
        );

        return ['', 'Porcentajes actualizados correctamente.'];
    }
}
