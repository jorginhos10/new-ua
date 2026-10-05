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
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'importar_csv') {
                [$error, $exito] = $this->importarCsv();
            } elseif ($accion === 'editar') {
                [$error, $exito] = $this->editar();
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
            static fn (array $s): array => [$s['codigo'], $s['nombre'], $s['nit']],
            $this->modeloSede->obtenerTodas()
        );

        CsvConfiguracion::exportar('sedes.csv', ['codigo', 'nombre', 'nit'], $filas);
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

        if ($resultado['invalidas'] > 0) {
            $mensaje .= ' ' . $resultado['invalidas'] . ' fila(s) se omitieron por NIT inválido (dos dígitos, de 00 a 09).';
        }

        if ($resultado['omitidos'] > 0) {
            $mensaje .= ' ' . $resultado['omitidos'] . ' no se pudieron eliminar porque siguen en uso en otra parte del sistema.';
        }

        return ['', $mensaje];
    }

    /** Alta de sede. Devuelve [error, exito]. */
    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');

        $error = $this->validar($codigo, $nombre, $nit, null);

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloSede->crear($codigo, $nombre, $nit);

        return ['', 'Sede agregada correctamente.'];
    }

    /** Edición de código, nombre y NIT de una sede existente. */
    private function editar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $sede = $id > 0 ? $this->modeloSede->obtenerPorId($id) : null;

        if ($sede === null) {
            return ['La sede no existe.', ''];
        }

        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $nit = trim($_POST['nit'] ?? '');

        $error = $this->validar($codigo, $nombre, $nit, $id);

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloSede->actualizar($id, $codigo, $nombre, $nit);

        return ['', 'Sede "' . $nombre . '" actualizada correctamente.'];
    }

    private function validar(string $codigo, string $nombre, string $nit, ?int $excluirId): string
    {
        if ($codigo === '' || $nombre === '' || $nit === '') {
            return 'El código, el nombre y el NIT son obligatorios.';
        }

        if (!preg_match(Sede::PATRON_NIT, $nit)) {
            return 'El NIT debe tener dos dígitos y empezar por 0 (de 00 a 09).';
        }

        if ($this->modeloSede->existeCodigo($codigo, $excluirId)) {
            return 'Ese código ya existe.';
        }

        if ($this->modeloSede->existeNit($nit, $excluirId)) {
            return 'Ese NIT ya lo tiene otra sede.';
        }

        return '';
    }
}
