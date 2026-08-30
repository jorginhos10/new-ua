<?php

require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class RolControlador
{
    private Rol $modeloRol;

    public function __construct()
    {
        $this->modeloRol = new Rol();
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

        $roles = $this->modeloRol->obtenerTodos();

        require __DIR__ . '/../vista/roles/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $r): array => [$r['nombre'], $r['orden']],
            $this->modeloRol->obtenerTodos()
        );

        CsvConfiguracion::exportar('roles.csv', ['nombre', 'orden'], $filas);
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloRol->sincronizarDesdeCsv($filas);

        $mensaje = 'Se crearon ' . $resultado['creados'] . ', se actualizaron ' . $resultado['actualizados']
            . ' y se eliminaron ' . $resultado['eliminados'] . ' rol(es).';

        if ($resultado['omitidos'] > 0) {
            $mensaje .= ' ' . $resultado['omitidos'] . ' no se pudieron eliminar porque siguen en uso en otra parte del sistema.';
        }

        return ['', $mensaje];
    }

    private function eliminar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloRol->eliminar($id);
        }

        header('Location: index.php?ruta=roles');
        exit;
    }

    private function guardar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $ordenTexto = trim($_POST['orden'] ?? '');

        if ($nombre === '') {
            return ['El nombre del rol es obligatorio.', ''];
        }

        if ($this->modeloRol->existeRol($nombre)) {
            return ['Ese rol ya existe.', ''];
        }

        $orden = $ordenTexto !== '' ? (int) $ordenTexto : $this->modeloRol->obtenerSiguienteOrden();

        $this->modeloRol->crear($nombre, $orden);

        return ['', 'Rol agregado correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $ordenTexto = trim($_POST['orden'] ?? '');

        if ($id <= 0 || $this->modeloRol->obtenerPorId($id) === null) {
            return ['El rol que intentas editar no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre del rol es obligatorio.', ''];
        }

        if ($this->modeloRol->existeRol($nombre, $id)) {
            return ['Ese rol ya existe.', ''];
        }

        $orden = $ordenTexto !== '' ? (int) $ordenTexto : 0;

        $this->modeloRol->actualizar($id, $nombre, $orden);

        return ['', 'Rol actualizado correctamente.'];
    }
}
