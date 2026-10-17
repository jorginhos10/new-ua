<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Categoría de gasto (37 subcategorías de actividad) para gastos principales. El motor que asigna
 * la categoría corre en el navegador (publico/js/categorizador-gastos.js) con el catálogo y el
 * mapeo que entrega este modelo; el servidor valida y guarda.
 */
class CategoriaGasto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerCatalogo(): array
    {
        $filas = $this->db->query(
            "SELECT id, macro, subcategoria, tokens FROM categorias_gasto WHERE estado = 'activo' ORDER BY orden"
        )->fetchAll();

        return array_map(static fn (array $fila): array => [
            'id' => $fila['id'],
            'macro' => $fila['macro'],
            'subcategoria' => $fila['subcategoria'],
            'tokens' => array_values(array_filter(array_map('trim', explode(',', $fila['tokens'])))),
        ], $filas);
    }

    public function obtenerMapeo(): array
    {
        return $this->db->query(
            'SELECT prefijo_codigo, accion, categoria_id, es_defecto, peso FROM rubro_categoria_gasto'
        )->fetchAll();
    }

    /** Subcategoría por id, también las inactivas: los gastos ya clasificados conservan su rótulo. */
    public function obtenerNombresPorId(): array
    {
        $nombres = [];
        foreach ($this->db->query('SELECT id, subcategoria FROM categorias_gasto')->fetchAll() as $fila) {
            $nombres[$fila['id']] = $fila['subcategoria'];
        }

        return $nombres;
    }

    // --- Administración (Configuraciones > Listas > Categorías de gasto) ---

    /** Todas, también las inactivas, con cuántos gastos/reglas de rubro las usan (para "en uso"). */
    public function obtenerTodos(): array
    {
        return $this->db->query(
            'SELECT c.id, c.macro, c.subcategoria, c.tokens, c.orden, c.estado,
                    (SELECT COUNT(*) FROM gastos g WHERE g.categoria_gasto_id = c.id)
                    + (SELECT COUNT(*) FROM rubro_categoria_gasto r WHERE r.categoria_id = c.id) AS usos
             FROM categorias_gasto c
             ORDER BY c.orden, c.id'
        )->fetchAll();
    }

    public function obtenerPorId(string $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM categorias_gasto WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(string $id, string $macro, string $subcategoria, string $tokens, int $orden): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO categorias_gasto (id, macro, subcategoria, tokens, orden) VALUES (:id, :macro, :subcategoria, :tokens, :orden)'
        );

        return $consulta->execute(['id' => $id, 'macro' => $macro, 'subcategoria' => $subcategoria, 'tokens' => $tokens, 'orden' => $orden]);
    }

    public function actualizar(string $id, string $macro, string $subcategoria, string $tokens, int $orden): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE categorias_gasto SET macro = :macro, subcategoria = :subcategoria, tokens = :tokens, orden = :orden WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'macro' => $macro, 'subcategoria' => $subcategoria, 'tokens' => $tokens, 'orden' => $orden]);
    }

    public function cambiarEstado(string $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE categorias_gasto SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(string $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM categorias_gasto WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function siguienteOrden(): int
    {
        return (int) $this->db->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM categorias_gasto')->fetchColumn();
    }

    public function existeCategoria(string $id): bool
    {
        $consulta = $this->db->prepare("SELECT 1 FROM categorias_gasto WHERE id = :id AND estado = 'activo'");
        $consulta->execute(['id' => $id]);

        return $consulta->fetchColumn() !== false;
    }

    /** modo 'vacios': solo gastos sin categoría. modo 'todo': todos (sobrescribe manuales). */
    public function gastosParaClasificar(string $modo): array
    {
        $condicion = $modo === 'vacios' ? 'WHERE g.categoria_gasto_id IS NULL' : '';

        return $this->db->query(
            "SELECT g.id, g.actividad, g.insumo, g.rubro_texto, g.tipo_automatico, g.categoria_gasto_id, g.categoria_origen,
                    r.codigo AS rubro_codigo
             FROM gastos g
             LEFT JOIN rubros r ON r.id = g.rubro_id
             $condicion
             ORDER BY g.id"
        )->fetchAll();
    }

    public function contarManuales(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM gastos WHERE categoria_origen = 'manual'")->fetchColumn();
    }

    /**
     * Guarda el resultado del motor. Solo acepta origen 'automatico' (con categoría existente) o
     * 'no_aplica' (sin categoría). Todo o nada.
     */
    public function guardarClasificacion(array $items): int
    {
        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare(
                'UPDATE gastos
                 SET categoria_gasto_id = :categoria_gasto_id, categoria_origen = :categoria_origen, categoria_confianza = :categoria_confianza
                 WHERE id = :id'
            );

            $guardados = 0;
            foreach ($items as $item) {
                $consulta->execute([
                    'id' => (int) $item['id'],
                    'categoria_gasto_id' => $item['categoria_gasto_id'],
                    'categoria_origen' => $item['categoria_origen'],
                    'categoria_confianza' => $item['categoria_confianza'],
                ]);
                $guardados++;
            }

            $this->db->commit();

            return $guardados;
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }
}
