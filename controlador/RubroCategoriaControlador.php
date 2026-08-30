<?php

require_once __DIR__ . '/../modelo/RubroCategoria.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class RubroCategoriaControlador
{
    private RubroCategoria $modeloRubroCategoria;

    public function __construct()
    {
        $this->modeloRubroCategoria = new RubroCategoria();
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

        $this->modeloRubroCategoria->sincronizarPrefijos();

        if (($_GET['accion_csv'] ?? '') === 'plantilla') {
            $this->exportarCsv();
        }

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['accion'] ?? '') === 'importar_csv') {
                [$error, $exito] = $this->importarCsv();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $categorias = $this->modeloRubroCategoria->obtenerTodos();

        require __DIR__ . '/../vista/rubro-categorias/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $c): array => [
                $c['cap'],
                $c['seccion'],
                $c['autogestion'] ? '1' : '0',
                $c['egresos'] ? '1' : '0',
                $c['proyectos'] ? '1' : '0',
            ],
            $this->modeloRubroCategoria->obtenerTodos()
        );

        CsvConfiguracion::exportar(
            'rubro_categorias.csv',
            ['cap', 'seccion', 'autogestion', 'egresos', 'proyectos'],
            $filas
        );
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloRubroCategoria->sincronizarDesdeCsv($filas);

        $mensaje = 'Se actualizaron ' . $resultado['actualizados'] . ' combinación(es) cap.sección.';

        if ($resultado['sin_coincidencia'] > 0) {
            $mensaje .= ' ' . $resultado['sin_coincidencia'] . ' fila(s) del archivo no coinciden con ningún cap.sección existente y se ignoraron.';
        }

        return ['', $mensaje];
    }

    private function guardar(): array
    {
        $marcadosAutogestion = array_map('intval', $_POST['autogestion'] ?? []);
        $marcadosEgresos = array_map('intval', $_POST['egresos'] ?? []);
        $marcadosProyectos = array_map('intval', $_POST['proyectos'] ?? []);

        foreach ($this->modeloRubroCategoria->obtenerTodos() as $categoria) {
            $id = (int) $categoria['id'];

            $this->modeloRubroCategoria->guardar(
                $id,
                in_array($id, $marcadosAutogestion, true),
                in_array($id, $marcadosEgresos, true),
                in_array($id, $marcadosProyectos, true)
            );
        }

        return ['', 'Categorías de rubros actualizadas correctamente.'];
    }
}
