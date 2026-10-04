<?php

require_once __DIR__ . '/../modelo/CategoriaGasto.php';

/**
 * ?ruta=categorias-gasto (Configuraciones, mismo permiso de menú). Página con los dos lotes
 * ("Calcular vacíos" y "Recalcular todo") y dos endpoints JSON: `datos` (catálogo, mapeo y gastos
 * a clasificar) y `guardar` (recibe el resultado del motor y lo valida antes de escribir).
 */
class CategoriasGastoControlador
{
    private const MAX_ITEMS_POR_LOTE = 500;

    private CategoriaGasto $modelo;

    public function __construct()
    {
        $this->modelo = new CategoriaGasto();
    }

    public function index(): void
    {
        $accion = $_GET['accion'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar') {
            $this->guardar();

            return;
        }

        if ($accion === 'datos') {
            $this->datos();

            return;
        }

        $tituloPagina = 'Configuraciones · Categoría de gasto';
        require __DIR__ . '/../vista/categorias-gasto/index.php';
    }

    private function datos(): void
    {
        $modo = ($_GET['modo'] ?? '') === 'todo' ? 'todo' : 'vacios';

        $this->responderJson([
            'catalogo' => $this->modelo->obtenerCatalogo(),
            'mapeo' => $this->modelo->obtenerMapeo(),
            'gastos' => $this->modelo->gastosParaClasificar($modo),
            'resumen' => ['manuales' => $this->modelo->contarManuales(), 'modo' => $modo],
        ]);
    }

    private function guardar(): void
    {
        $cuerpo = json_decode((string) file_get_contents('php://input'), true);
        $items = is_array($cuerpo['items'] ?? null) ? $cuerpo['items'] : [];

        if (count($items) === 0 || count($items) > self::MAX_ITEMS_POR_LOTE) {
            $this->responderJson(['ok' => false, 'error' => 'El lote debe tener entre 1 y ' . self::MAX_ITEMS_POR_LOTE . ' gastos.'], 400);

            return;
        }

        $validados = [];
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $origen = $item['origen'] ?? '';
            $categoriaId = $item['categoria_id'] ?? null;
            $confianza = $item['confianza'] ?? null;

            if ($id <= 0 || !in_array($origen, ['automatico', 'no_aplica'], true)) {
                $this->responderJson(['ok' => false, 'error' => 'Hay un gasto con datos inválidos.'], 400);

                return;
            }

            if ($origen === 'no_aplica') {
                $categoriaId = null;
            } elseif (!is_string($categoriaId) || !$this->modelo->existeCategoria($categoriaId)) {
                $this->responderJson(['ok' => false, 'error' => 'Una categoría no existe en el catálogo.'], 400);

                return;
            }

            if ($confianza !== null && (!is_numeric($confianza) || (float) $confianza < 0 || (float) $confianza > 1)) {
                $this->responderJson(['ok' => false, 'error' => 'La confianza debe estar entre 0 y 1.'], 400);

                return;
            }

            $validados[] = [
                'id' => $id,
                'categoria_gasto_id' => $categoriaId,
                'categoria_origen' => $origen,
                'categoria_confianza' => $confianza === null ? null : round((float) $confianza, 3),
            ];
        }

        try {
            $guardados = $this->modelo->guardarClasificacion($validados);
        } catch (Throwable $excepcion) {
            $this->responderJson(['ok' => false, 'error' => 'No se pudo guardar el lote.'], 500);

            return;
        }

        $this->responderJson(['ok' => true, 'guardados' => $guardados]);
    }

    private function responderJson(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
