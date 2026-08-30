<?php

require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class SedeControlador
{
    private Sede $modeloSede;

    public function __construct()
    {
        $this->modeloSede = new Sede();
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
            if (($_POST['accion'] ?? '') === 'importar_csv') {
                [$error, $exito] = $this->importarCsv();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $sedes = $this->modeloSede->obtenerTodas();

        require __DIR__ . '/../vista/sedes/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $s): array => [$s['codigo'], $s['nombre']],
            $this->modeloSede->obtenerTodas()
        );

        CsvConfiguracion::exportar('sedes.csv', ['codigo', 'nombre'], $filas);
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloSede->sincronizarDesdeCsv($filas);

        $mensaje = 'Se crearon ' . $resultado['creados'] . ', se actualizaron ' . $resultado['actualizados']
            . ' y se eliminaron ' . $resultado['eliminados'] . ' sede(s).';

        if ($resultado['omitidos'] > 0) {
            $mensaje .= ' ' . $resultado['omitidos'] . ' no se pudieron eliminar porque siguen en uso en otra parte del sistema.';
        }

        return ['', $mensaje];
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');

        if ($codigo === '' || $nombre === '') {
            return ['El código y el nombre son obligatorios.', ''];
        }

        if ($this->modeloSede->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloSede->crear($codigo, $nombre);

        return ['', 'Sede agregada correctamente.'];
    }
}
