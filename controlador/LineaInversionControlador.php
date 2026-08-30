<?php

require_once __DIR__ . '/../modelo/LineaInversion.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class LineaInversionControlador
{
    private LineaInversion $modeloLineaInversion;

    public function __construct()
    {
        $this->modeloLineaInversion = new LineaInversion();
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

        if (($_GET['accion_csv'] ?? '') === 'plantilla') {
            $this->exportarCsv();
        }

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'importar_csv') {
                [$error, $exito] = $this->importarCsv();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $lineasInversion = $this->modeloLineaInversion->obtenerTodas();

        require __DIR__ . '/../vista/lineas-inversion/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $l): array => [$l['codigo'], $l['nombre'], $l['descripcion'], $l['estado']],
            $this->modeloLineaInversion->obtenerTodas()
        );

        CsvConfiguracion::exportar('lineas_inversion.csv', ['codigo', 'nombre', 'descripcion', 'estado'], $filas);
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloLineaInversion->sincronizarDesdeCsv($filas);

        $mensaje = 'Se crearon ' . $resultado['creados'] . ', se actualizaron ' . $resultado['actualizados']
            . ' y se desactivaron ' . $resultado['desactivados'] . ' línea(s) de inversión.';

        return ['', $mensaje];
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloLineaInversion->cambiarEstado($id);
        }

        header('Location: index.php?ruta=lineas-inversion');
        exit;
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($codigo === '' || $nombre === '' || $descripcion === '') {
            return ['El código, el nombre y la descripción son obligatorios.', ''];
        }

        if ($this->modeloLineaInversion->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloLineaInversion->crear($codigo, $nombre, $descripcion);

        return ['', 'Línea de inversión agregada correctamente.'];
    }
}
