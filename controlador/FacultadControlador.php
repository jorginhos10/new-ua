<?php

require_once __DIR__ . '/../modelo/Facultad.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class FacultadControlador
{
    private Facultad $modeloFacultad;

    public function __construct()
    {
        $this->modeloFacultad = new Facultad();
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

        $facultades = $this->modeloFacultad->obtenerTodas();

        require __DIR__ . '/../vista/facultades/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $f): array => [$f['nombre'], $f['estado']],
            $this->modeloFacultad->obtenerTodas()
        );

        CsvConfiguracion::exportar('facultades.csv', ['nombre', 'estado'], $filas);
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloFacultad->sincronizarDesdeCsv($filas);

        $mensaje = 'Se crearon ' . $resultado['creados'] . ', se actualizaron ' . $resultado['actualizados']
            . ' y se desactivaron ' . $resultado['desactivados'] . ' facultad(es).';

        return ['', $mensaje];
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloFacultad->cambiarEstado($id);
        }

        header('Location: index.php?ruta=facultades');
        exit;
    }

    private function guardar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        if ($this->modeloFacultad->existeNombre($nombre)) {
            return ['Esa facultad ya existe.', ''];
        }

        $this->modeloFacultad->crear($nombre);

        return ['', 'Facultad agregada correctamente.'];
    }
}
