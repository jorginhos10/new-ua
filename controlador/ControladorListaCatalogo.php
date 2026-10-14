<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelo/CsvConfiguracion.php';

/**
 * Base de los 10 controladores del botón "Listas" de Configuraciones (Línea/Motor/Proyecto PDI,
 * Estamentos, Rubros, Sedes, Contratos comunes, Líneas/Sublíneas de inversión, Fuentes de
 * financiación) — centraliza lo que era boilerplate idéntico en cada uno (guard de sesión,
 * dispatch GET/POST, exportar/importar CSV, cambiar_estado, eliminar con guardia de uso). Cada
 * subclase concreta solo aporta lo que de verdad le pertenece: su modelo, su tabla (para el
 * guardia de eliminar), sus columnas de CSV, y los campos que lee en crear()/actualizar() —
 * porque esos campos sí difieren de un catálogo a otro.
 */
abstract class ControladorListaCatalogo
{
    abstract protected function modelo(): object;
    /** Nombre de la tabla real (para el guardia de referencias antes de eliminar). */
    abstract protected function tabla(): string;
    /** Ruta de index.php?ruta=... de este módulo (para redirects y action= de los formularios). */
    abstract protected function ruta(): string;
    /** Ruta absoluta a vista/<modulo>/index.php. */
    abstract protected function vista(): string;

    /** Nombres de columnas del CSV, en orden — ver filaCsv(). Vacío = este módulo no tiene CSV. */
    protected function columnasCsv(): array
    {
        return [];
    }

    /** Convierte una fila de modelo()->obtenerTodos() en el array de valores de una línea CSV. */
    protected function filaCsv(array $registro): array
    {
        return [];
    }

    protected function crear(): array
    {
        return ['Acción no implementada.', ''];
    }

    protected function actualizar(): array
    {
        return ['Acción no implementada.', ''];
    }

    /**
     * Filas a mostrar en la tabla — por defecto, todas sin más. Las subclases con columnas FK
     * (Motor/Proyecto/Sublínea de inversión) o un conteo calculado (Fuentes de financiación)
     * sobreescriben esto para devolver las filas ya enriquecidas con esas columnas extra.
     */
    protected function registros(): array
    {
        return $this->modelo()->obtenerTodos();
    }

    /**
     * Variables extra que necesite la vista además de $registros (ej. las opciones de un <select>
     * de FK) — se extraen al scope de la vista por nombre de clave. Vacío en la mayoría.
     */
    protected function datosExtra(): array
    {
        return [];
    }

    public function index(): void
    {
        $this->requerirAdministrador();

        if (($_GET['accion_csv'] ?? '') === 'plantilla') {
            $this->exportarCsv();
        }

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminarFila();
            } elseif ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } elseif ($accion === 'importar_csv') {
                [$error, $exito] = $this->importarCsv();
            } else {
                [$error, $exito] = $this->crear();
            }
        }

        $registros = $this->registros();
        extract($this->datosExtra());

        require $this->vista();
    }

    private function requerirAdministrador(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }

    private function exportarCsv(): void
    {
        $columnas = $this->columnasCsv();
        $filas = array_map(
            fn (array $registro): array => $this->filaCsv($registro),
            $this->registros()
        );

        CsvConfiguracion::exportar($this->ruta() . '.csv', $columnas, $filas);
    }

    private function importarCsv(): array
    {
        $filas = CsvConfiguracion::leerArchivoSubido($_FILES['archivo_csv'] ?? []);

        if ($filas === null) {
            return ['No se pudo leer el archivo CSV. Verifica que el archivo tenga el formato correcto.', ''];
        }

        $resultado = $this->modelo()->sincronizarDesdeCsv($filas);

        return ['', $this->mensajeImportacion($resultado)];
    }

    /**
     * Mensaje genérico a partir del contrato estándar de sincronizarDesdeCsv():
     * ['creados', 'actualizados', 'desactivados', 'ignorados'] (ver modelo/Rubro.php como plantilla).
     */
    protected function mensajeImportacion(array $resultado): string
    {
        $mensaje = 'Se crearon ' . ($resultado['creados'] ?? 0) . ', se actualizaron ' . ($resultado['actualizados'] ?? 0)
            . ' y se desactivaron ' . ($resultado['desactivados'] ?? 0) . ' registro(s).';

        if (!empty($resultado['ignorados'])) {
            $mensaje .= ' ' . $resultado['ignorados'] . ' fila(s) del archivo se ignoraron por datos inválidos o referencias inexistentes.';
        }

        return $mensaje;
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modelo()->cambiarEstado($id);
        }

        header('Location: index.php?ruta=' . $this->ruta());
        exit;
    }

    private function eliminarFila(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modelo()->obtenerPorId($id) === null) {
            return ['El registro que intentas eliminar no existe.', ''];
        }

        if (CsvConfiguracion::tieneReferencias(Conexion::obtener(), $this->tabla(), $id)) {
            return ['No se puede eliminar: todavía está en uso en otra parte del sistema. Desactívalo en su lugar.', ''];
        }

        $this->modelo()->eliminar($id);

        return ['', 'Registro eliminado correctamente.'];
    }
}
