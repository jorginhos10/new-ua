<?php

require_once __DIR__ . '/../modelo/ContratoComun.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

class ContratoComunControlador
{
    private ContratoComun $modeloContratoComun;

    public function __construct()
    {
        $this->modeloContratoComun = new ContratoComun();
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

        $contratosComunes = $this->modeloContratoComun->obtenerTodos();

        require __DIR__ . '/../vista/contratos-comunes/index.php';
    }

    private function exportarCsv(): void
    {
        $filas = array_map(
            static fn (array $c): array => [$c['codigo'], $c['descripcion'], $c['estado']],
            $this->modeloContratoComun->obtenerTodos()
        );

        CsvConfiguracion::exportar('contratos_comunes.csv', ['codigo', 'descripcion', 'estado'], $filas);
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modeloContratoComun->sincronizarDesdeCsv($filas);

        $mensaje = 'Se crearon ' . $resultado['creados'] . ', se actualizaron ' . $resultado['actualizados']
            . ' y se desactivaron ' . $resultado['desactivados'] . ' contrato(s) común(es).';

        return ['', $mensaje];
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloContratoComun->cambiarEstado($id);
        }

        header('Location: index.php?ruta=contratos-comunes');
        exit;
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($codigo === '' || $descripcion === '') {
            return ['El código y la descripción son obligatorios.', ''];
        }

        if ($this->modeloContratoComun->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloContratoComun->crear($codigo, $descripcion);

        return ['', 'Contrato común agregado correctamente.'];
    }
}
