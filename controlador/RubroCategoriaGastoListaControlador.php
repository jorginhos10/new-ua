<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/RubroCategoriaGasto.php';
require_once __DIR__ . '/../modelo/CategoriaGasto.php';

/**
 * Configuraciones > Listas > Rubros por categoría (?ruta=lista-rubros-categorias-gasto): reglas
 * que le dicen al motor de categorización qué categoría(s) corresponden a cada prefijo de código
 * de rubro (tabla rubro_categoria_gasto). Un mismo prefijo puede tener varias categorías: la de
 * "Por defecto" gana cuando el texto de actividad/insumo no decide, y el peso (0 a 1) baja la
 * preferencia de las alternativas. "No aplica" deja ese rubro fuera de las categorías.
 */
class RubroCategoriaGastoListaControlador extends ControladorListaCatalogo
{
    private RubroCategoriaGasto $modeloRegla;

    public function __construct()
    {
        $this->modeloRegla = new RubroCategoriaGasto();
    }

    protected function modelo(): object
    {
        return $this->modeloRegla;
    }

    protected function tabla(): string
    {
        return 'rubro_categoria_gasto';
    }

    protected function ruta(): string
    {
        return 'lista-rubros-categorias-gasto';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/lista-rubros-categorias-gasto/index.php';
    }

    protected function registros(): array
    {
        return array_map(static function (array $fila): array {
            $fila['peso'] = number_format((float) $fila['peso'], 2, '.', '');
            // "accion" ya lo usa ControladorListaCatalogo para despachar el POST (crear/actualizar/...).
            $fila['accion_regla'] = $fila['accion'];
            $fila['rubro_descripcion'] = $fila['rubro_descripcion'] ?? '(prefijo: aplica a todos los rubros que empiezan así)';

            return $fila;
        }, $this->modeloRegla->obtenerTodos());
    }

    protected function datosExtra(): array
    {
        $opcionesCategoria = array_map(
            static fn (array $categoria): array => ['value' => $categoria['id'], 'label' => $categoria['id'] . ' · ' . $categoria['subcategoria']],
            (new CategoriaGasto())->obtenerCatalogo()
        );

        return ['opcionesCategoria' => $opcionesCategoria];
    }

    protected function crear(): array
    {
        [$datos, $error] = $this->leerCampos(null);
        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloRegla->crear(...$datos);

        return ['', 'Regla para el rubro "' . $datos[0] . '" agregada.'];
    }

    protected function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || $this->modeloRegla->obtenerPorId($id) === null) {
            return ['La regla que intentas editar no existe.', ''];
        }

        [$datos, $error] = $this->leerCampos($id);
        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloRegla->actualizar($id, ...$datos);

        return ['', 'Regla para el rubro "' . $datos[0] . '" actualizada.'];
    }

    /** @return array{0: array, 1: string} [prefijo, accion, categoria_id, es_defecto, peso] y error */
    private function leerCampos(?int $id): array
    {
        $prefijo = trim((string) ($_POST['prefijo_codigo'] ?? ''));
        $accion = (string) ($_POST['accion_regla'] ?? 'categoria');
        $categoriaId = trim((string) ($_POST['categoria_id'] ?? ''));
        $esDefecto = ($_POST['es_defecto'] ?? '0') === '1' ? 1 : 0;
        $pesoTexto = str_replace(',', '.', trim((string) ($_POST['peso'] ?? '1')));

        if (!preg_match('/^\d+(\.\d+)*$/', $prefijo) || strlen($prefijo) > 30) {
            return [[], 'El prefijo del rubro debe ser un código numérico como 2.02.01 (máx. 30 caracteres).'];
        }
        if (!in_array($accion, ['categoria', 'no_aplica'], true)) {
            return [[], 'Selecciona si el rubro tiene categoría o no aplica.'];
        }
        if ($accion === 'categoria') {
            if ($categoriaId === '' || !(new CategoriaGasto())->existeCategoria($categoriaId)) {
                return [[], 'Selecciona una categoría activa para esta regla.'];
            }
        } else {
            $categoriaId = '';
        }
        if (!is_numeric($pesoTexto) || (float) $pesoTexto <= 0 || (float) $pesoTexto > 1) {
            return [[], 'El peso debe ser un número mayor que 0 y hasta 1 (ej. 0.80).'];
        }

        $categoriaFinal = $categoriaId === '' ? null : $categoriaId;
        if ($this->modeloRegla->existeDuplicado($prefijo, $accion, $categoriaFinal, $id)) {
            return [[], 'Ya existe una regla igual para el rubro "' . $prefijo . '".'];
        }

        return [[$prefijo, $accion, $categoriaFinal, $esDefecto, round((float) $pesoTexto, 2)], ''];
    }
}
