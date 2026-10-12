<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/FuenteFinanciacion.php';

class FuenteFinanciacionControlador extends ControladorListaCatalogo
{
    private FuenteFinanciacion $modeloFuenteFinanciacion;

    public function __construct()
    {
        $this->modeloFuenteFinanciacion = new FuenteFinanciacion();
    }

    protected function modelo(): object
    {
        return $this->modeloFuenteFinanciacion;
    }

    protected function tabla(): string
    {
        return 'fuentes_financiacion';
    }

    protected function ruta(): string
    {
        return 'fuentes-financiacion';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/fuentes-financiacion/index.php';
    }

    protected function registros(): array
    {
        return array_map(
            static function (array $fila): array {
                $fila['en_uso'] = (int) $fila['cantidad_proyectos'] > 0;

                return $fila;
            },
            $this->modeloFuenteFinanciacion->obtenerTodas()
        );
    }

    protected function columnasCsv(): array
    {
        return ['nombre', 'estado'];
    }

    protected function filaCsv(array $registro): array
    {
        return [$registro['nombre'], $registro['estado']];
    }

    protected function crear(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre de la fuente de financiación es obligatorio.', ''];
        }

        if ($this->modeloFuenteFinanciacion->existeNombre($nombre)) {
            return ['Esa fuente de financiación ya existe.', ''];
        }

        $this->modeloFuenteFinanciacion->crear($nombre);

        return ['', 'Fuente de financiación agregada correctamente.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $this->modeloFuenteFinanciacion->obtenerPorId($id) === null) {
            return ['La fuente de financiación que intentas editar no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre de la fuente de financiación es obligatorio.', ''];
        }

        if ($this->modeloFuenteFinanciacion->existeNombre($nombre, $id)) {
            return ['Esa fuente de financiación ya existe.', ''];
        }

        $this->modeloFuenteFinanciacion->actualizarNombre($id, $nombre);

        return ['', 'Fuente de financiación actualizada correctamente.'];
    }
}
