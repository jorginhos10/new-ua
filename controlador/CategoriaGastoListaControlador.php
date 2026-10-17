<?php

require_once __DIR__ . '/ControladorListaCatalogo.php';
require_once __DIR__ . '/../modelo/CategoriaGasto.php';

/**
 * Configuraciones > Listas > Categorías de gasto (?ruta=lista-categorias-gasto): administra el
 * catálogo de categorías de actividad (tabla categorias_gasto) que usan el motor de categorización
 * y la pantalla "Categoría de gasto" (?ruta=categorias-gasto, que calcula/recalcula la
 * asignación). La clave es un código de texto (ej. "EST-01"): se fija al crear y no se edita,
 * porque los gastos ya clasificados y las reglas de rubro la referencian.
 */
class CategoriaGastoListaControlador extends ControladorListaCatalogo
{
    private CategoriaGasto $modeloCategoria;

    public function __construct()
    {
        $this->modeloCategoria = new CategoriaGasto();
    }

    protected function modelo(): object
    {
        return $this->modeloCategoria;
    }

    protected function tabla(): string
    {
        return 'categorias_gasto';
    }

    protected function ruta(): string
    {
        return 'lista-categorias-gasto';
    }

    protected function vista(): string
    {
        return __DIR__ . '/../vista/lista-categorias-gasto/index.php';
    }

    protected function idDesdePost(): int|string
    {
        return strtoupper(trim((string) ($_POST['id'] ?? '')));
    }

    protected function registros(): array
    {
        return array_map(static fn (array $fila): array => $fila + ['en_uso' => (int) $fila['usos'] > 0], $this->modeloCategoria->obtenerTodos());
    }

    protected function crear(): array
    {
        $id = strtoupper(trim((string) ($_POST['id'] ?? '')));
        [$macro, $subcategoria, $tokens, $orden, $error] = $this->leerCampos();

        if ($error === '' && !preg_match('/^[A-Z0-9]{2,5}-\d{1,3}$/', $id)) {
            $error = 'El código debe tener la forma LETRAS-NÚMERO (ej. PER-01).';
        }
        if ($error === '' && $this->modeloCategoria->obtenerPorId($id) !== null) {
            $error = 'Ya existe una categoría con el código "' . $id . '".';
        }
        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloCategoria->crear($id, $macro, $subcategoria, $tokens, $orden ?? $this->modeloCategoria->siguienteOrden());

        return ['', 'Categoría "' . $subcategoria . '" agregada.'];
    }

    protected function actualizar(): array
    {
        $id = $this->idDesdePost();
        if ($id === '' || $this->modeloCategoria->obtenerPorId($id) === null) {
            return ['La categoría que intentas editar no existe.', ''];
        }

        [$macro, $subcategoria, $tokens, $orden, $error] = $this->leerCampos();
        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloCategoria->actualizar($id, $macro, $subcategoria, $tokens, $orden ?? (int) $this->modeloCategoria->obtenerPorId($id)['orden']);

        return ['', 'Categoría "' . $subcategoria . '" actualizada.'];
    }

    /** @return array{0: string, 1: string, 2: string, 3: ?int, 4: string} macro, subcategoría, tokens normalizados, orden, error */
    private function leerCampos(): array
    {
        $macro = trim((string) ($_POST['macro'] ?? ''));
        $subcategoria = trim((string) ($_POST['subcategoria'] ?? ''));
        // Palabras clave: minúsculas, sin espacios sobrantes ni repetidas, separadas por coma.
        $tokens = implode(',', array_values(array_unique(array_filter(array_map(
            static fn (string $token): string => mb_strtolower(trim($token)),
            explode(',', (string) ($_POST['tokens'] ?? ''))
        )))));
        $ordenTexto = trim((string) ($_POST['orden'] ?? ''));

        if ($macro === '' || $subcategoria === '') {
            return [$macro, $subcategoria, $tokens, null, 'El grupo y el nombre de la categoría son obligatorios.'];
        }
        if (mb_strlen($macro) > 80 || mb_strlen($subcategoria) > 150) {
            return [$macro, $subcategoria, $tokens, null, 'El grupo admite hasta 80 caracteres y el nombre hasta 150.'];
        }
        if ($ordenTexto !== '' && !ctype_digit($ordenTexto)) {
            return [$macro, $subcategoria, $tokens, null, 'El orden debe ser un número entero.'];
        }

        return [$macro, $subcategoria, $tokens, $ordenTexto === '' ? null : (int) $ordenTexto, ''];
    }
}
