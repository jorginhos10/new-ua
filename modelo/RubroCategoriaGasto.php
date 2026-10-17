<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Reglas rubro → categoría de gasto (tabla rubro_categoria_gasto) que usa el motor de
 * categorización (publico/js/categorizador-gastos.js, vía CategoriaGasto::obtenerMapeo()): para
 * cada prefijo de código de rubro, qué categoría(s) aplica (con su peso y cuál es la de por
 * defecto) o si ese rubro "no aplica" (queda fuera de las categorías de actividad). La regla con
 * el prefijo más largo que coincide con el código del rubro es la que manda.
 * Se administra en Configuraciones > Listas > Rubros por categoría.
 */
class RubroCategoriaGasto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** Todas las reglas, con la subcategoría y (si el prefijo es un rubro exacto) su descripción. */
    public function obtenerTodos(): array
    {
        return $this->db->query(
            'SELECT rc.id, rc.prefijo_codigo, rc.accion, rc.categoria_id, rc.es_defecto, rc.peso,
                    c.subcategoria,
                    (SELECT MIN(r.descripcion) FROM rubros r WHERE r.codigo = rc.prefijo_codigo) AS rubro_descripcion
             FROM rubro_categoria_gasto rc
             LEFT JOIN categorias_gasto c ON c.id = rc.categoria_id
             ORDER BY rc.prefijo_codigo, rc.es_defecto DESC, rc.peso DESC, rc.id'
        )->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM rubro_categoria_gasto WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /** ¿Ya existe otra regla igual (mismo prefijo + acción + categoría)? — clave única de la tabla. */
    public function existeDuplicado(string $prefijo, string $accion, ?string $categoriaId, ?int $excluirId): bool
    {
        $consulta = $this->db->prepare(
            'SELECT 1 FROM rubro_categoria_gasto
             WHERE prefijo_codigo = :prefijo AND accion = :accion AND categoria_id <=> :categoria
               AND (:excluir IS NULL OR id <> :excluir2)
             LIMIT 1'
        );
        $consulta->execute([
            'prefijo' => $prefijo,
            'accion' => $accion,
            'categoria' => $categoriaId,
            'excluir' => $excluirId,
            'excluir2' => $excluirId,
        ]);

        return $consulta->fetchColumn() !== false;
    }

    public function crear(string $prefijo, string $accion, ?string $categoriaId, int $esDefecto, float $peso): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO rubro_categoria_gasto (prefijo_codigo, accion, categoria_id, es_defecto, peso)
             VALUES (:prefijo, :accion, :categoria, :defecto, :peso)'
        );

        return $consulta->execute(['prefijo' => $prefijo, 'accion' => $accion, 'categoria' => $categoriaId, 'defecto' => $esDefecto, 'peso' => $peso]);
    }

    public function actualizar(int $id, string $prefijo, string $accion, ?string $categoriaId, int $esDefecto, float $peso): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE rubro_categoria_gasto
             SET prefijo_codigo = :prefijo, accion = :accion, categoria_id = :categoria, es_defecto = :defecto, peso = :peso
             WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'prefijo' => $prefijo, 'accion' => $accion, 'categoria' => $categoriaId, 'defecto' => $esDefecto, 'peso' => $peso]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM rubro_categoria_gasto WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }
}
