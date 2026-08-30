<?php

require_once __DIR__ . '/../modelo/SublineaInversion.php';
require_once __DIR__ . '/../modelo/LineaInversion.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class SublineaInversionControlador
{
    private SublineaInversion $modeloSublineaInversion;
    private LineaInversion $modeloLineaInversion;

    public function __construct()
    {
        $this->modeloSublineaInversion = new SublineaInversion();
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

        $sublineasInversion = $this->modeloSublineaInversion->obtenerTodas();
        $lineasInversion = $this->modeloLineaInversion->obtenerActivas();

        require __DIR__ . '/../vista/sublineas-inversion/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $s): array => [$s['codigo'], $s['nombre'], $s['descripcion'], $s['linea_codigo'], $s['estado']],
            $this->modeloSublineaInversion->obtenerTodas()
        );

        CsvConfiguracion::exportar(
            'sublineas_inversion.csv',
            ['codigo', 'nombre', 'descripcion', 'linea_codigo', 'estado'],
            $filas
        );
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloSublineaInversion->sincronizarDesdeCsv($filas);

        $mensaje = 'Se crearon ' . $resultado['creados'] . ', se actualizaron ' . $resultado['actualizados']
            . ' y se desactivaron ' . $resultado['desactivados'] . ' sublínea(s) de inversión.';

        if ($resultado['ignorados'] > 0) {
            $mensaje .= ' ' . $resultado['ignorados'] . ' fila(s) se ignoraron porque su línea de inversión no existe.';
        }

        return ['', $mensaje];
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloSublineaInversion->cambiarEstado($id);
        }

        header('Location: index.php?ruta=sublineas-inversion');
        exit;
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $lineaInversionId = (int) ($_POST['linea_inversion_id'] ?? 0);

        if ($codigo === '' || $nombre === '' || $descripcion === '' || $lineaInversionId <= 0) {
            return ['El código, el nombre, la descripción y la línea de inversión son obligatorios.', ''];
        }

        if ($this->modeloSublineaInversion->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloSublineaInversion->crear($codigo, $nombre, $descripcion, $lineaInversionId);

        return ['', 'Sublínea de inversión agregada correctamente.'];
    }
}
