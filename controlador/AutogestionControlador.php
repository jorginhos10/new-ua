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
        'sin-excedentes' => 'Convenios',
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
            } elseif ($accion === 'guardar_tope') {
                [$error, $exito] = $this->guardarTope($moduloActivo);
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar($moduloActivo);
            } elseif ($accion === 'actualizar_porcentaje_item') {
                [$error, $exito] = $this->actualizarPorcentajeItem();
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

    /**
     * Tope único del módulo (Extensión, Postgrado): un solo número que sirve de denominador para
     * la tarjeta correspondiente del Dashboard — a diferencia de Costos/Inversiones/Excedentes,
     * que sí se configuran por ítem (ver actualizarPorcentajeItem()), el tope NO es por ítem ni se
     * suma entre ítems.
     */
    private function guardarTope(string $modulo): array
    {
        $tope = trim($_POST['tope'] ?? '');

        if ($tope === '') {
            $this->modeloPorcentaje->guardarTope($modulo, null);

            return ['', 'Tope actualizado correctamente.'];
        }

        if (!is_numeric($tope) || (float) $tope < 0) {
            return ['El tope debe ser un número mayor o igual a 0, o dejarse vacío ("Sin tope").', ''];
        }

        $this->modeloPorcentaje->guardarTope($modulo, (float) $tope);

        return ['', 'Tope actualizado correctamente.'];
    }

    /**
     * Guarda el Costos/Inversiones/Excedentes (y, si el ítem es de Postgrado, también Contribución
     * a posgrado) de UN ítem — a diferencia de guardarPorcentaje(), que sigue siendo el % global
     * por módulo, usado por Unidad de Salud/Convenios, que no tienen ítems propios.
     */
    private function actualizarPorcentajeItem(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $item = $id > 0 ? $this->modeloAutogestion->obtenerPorId($id) : null;

        if ($item === null) {
            return ['El ítem no existe.', ''];
        }

        $campos = ['costos', 'inversiones', 'excedentes'];

        if ($item['modulo'] === 'postgrado') {
            $campos[] = 'contribucion_postgrado';
        }

        $valores = [];

        foreach ($campos as $campo) {
            [$errorCampo, $valor] = $this->leerPorcentajeCampo($campo);

            if ($errorCampo !== '') {
                return [$errorCampo, ''];
            }

            $valores[$campo] = $valor;
        }

        $suma = array_sum(array_filter($valores, static fn ($valor) => $valor !== null));

        if ($suma > 100) {
            $nombreCampos = $item['modulo'] === 'postgrado'
                ? 'Costos, Inversiones, Excedentes y Contribución a posgrado'
                : 'Costos, Inversiones y Excedentes';

            return ['La suma de ' . $nombreCampos . ' de este ítem no puede superar el 100%.', ''];
        }

        $this->modeloAutogestion->actualizarPorcentajes(
            $id,
            $valores['costos'],
            $valores['inversiones'],
            $valores['excedentes'],
            $valores['contribucion_postgrado'] ?? null
        );

        return ['', 'Porcentajes del ítem actualizados correctamente.'];
    }

    /**
     * El % es opcional por campo: vacío significa "No aplica" (igual que el tope). Si viene
     * diligenciado, debe ser un número entre 0 y 100.
     *
     * @return array{0: string, 1: ?float} [mensaje de error, valor o null]
     */
    private function leerPorcentajeCampo(string $campo): array
    {
        $valor = trim($_POST[$campo] ?? '');

        if ($valor === '') {
            return ['', null];
        }

        if (!is_numeric($valor) || (float) $valor < 0 || (float) $valor > 100) {
            return ['Cada porcentaje debe ser un número entre 0 y 100, o dejarse vacío ("No aplica").', null];
        }

        return ['', (float) $valor];
    }

    /**
     * % global por módulo — ya solo aplica a Unidad de Salud/Convenios (Extensión y Postgrado
     * tienen ítems propios, ver actualizarPorcentajeItem()).
     */
    private function guardarPorcentaje(string $modulo): array
    {
        $campos = ['costos', 'inversiones', 'excedentes'];
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
            return ['La suma de Costos, Inversiones y Excedentes no puede superar el 100%.', ''];
        }

        $this->modeloPorcentaje->guardar($modulo, $valores['costos'], $valores['inversiones'], $valores['excedentes']);

        return ['', 'Porcentajes actualizados correctamente.'];
    }
}
