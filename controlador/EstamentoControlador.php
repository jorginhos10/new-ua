<?php

require_once __DIR__ . '/../modelo/Estamento.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class EstamentoControlador
{
    private Estamento $modeloEstamento;

    public function __construct()
    {
        $this->modeloEstamento = new Estamento();
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

            if ($accion === 'eliminar') {
                $this->eliminar();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } elseif ($accion === 'importar_csv') {
                [$error, $exito] = $this->importarCsv();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $estamentos = $this->modeloEstamento->obtenerTodos();

        require __DIR__ . '/../vista/estamentos/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $e): array => [$e['nombre']],
            $this->modeloEstamento->obtenerTodos()
        );

        CsvConfiguracion::exportar('estamentos.csv', ['nombre'], $filas);
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloEstamento->sincronizarDesdeCsv($filas);

        $mensaje = 'Se crearon ' . $resultado['creados'] . ' y se eliminaron ' . $resultado['eliminados'] . ' estamento(s).';

        if ($resultado['omitidos'] > 0) {
            $mensaje .= ' ' . $resultado['omitidos'] . ' no se pudieron eliminar porque siguen en uso en otra parte del sistema.';
        }

        return ['', $mensaje];
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloEstamento->eliminar($id);
        }

        header('Location: index.php?ruta=estamentos');
        exit;
    }

    private function guardar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre del estamento es obligatorio.', ''];
        }

        if ($this->modeloEstamento->existeEstamento($nombre)) {
            return ['Ese estamento ya existe.', ''];
        }

        $this->modeloEstamento->crear($nombre);

        return ['', 'Estamento agregado correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $this->modeloEstamento->obtenerPorId($id) === null) {
            return ['El estamento que intentas editar no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre del estamento es obligatorio.', ''];
        }

        if ($this->modeloEstamento->existeEstamento($nombre, $id)) {
            return ['Ese estamento ya existe.', ''];
        }

        $this->modeloEstamento->actualizar($id, $nombre);

        return ['', 'Estamento actualizado correctamente.'];
    }
}
