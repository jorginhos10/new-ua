<?php

require_once __DIR__ . '/../config/conexion.php';

class AutogestionItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(string $modulo): array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, tope, estado, creado_en FROM autogestion_items WHERE modulo = :modulo ORDER BY nombre');
        $consulta->execute(['modulo' => $modulo]);

        return $consulta->fetchAll();
    }

    public function obtenerActivos(string $modulo): array
    {
        $consulta = $this->db->prepare(
            "SELECT id, nombre FROM autogestion_items WHERE modulo = :modulo AND estado = 'activo' ORDER BY nombre"
        );
        $consulta->execute(['modulo' => $modulo]);

        return $consulta->fetchAll();
    }

    public function existeNombre(string $nombre, string $modulo, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT id FROM autogestion_items WHERE nombre = :nombre AND modulo = :modulo';
        $parametros = ['nombre' => $nombre, 'modulo' => $modulo];

        if ($ignorarId !== null) {
            $sql .= ' AND id != :ignorar_id';
            $parametros['ignorar_id'] = $ignorarId;
        }

        $consulta = $this->db->prepare($sql . ' LIMIT 1');
        $consulta->execute($parametros);

        return $consulta->fetch() !== false;
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, tope, modulo, estado, creado_en FROM autogestion_items WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(string $nombre, string $modulo, ?float $tope = null): bool
    {
        $consulta = $this->db->prepare('INSERT INTO autogestion_items (nombre, modulo, tope) VALUES (:nombre, :modulo, :tope)');

        return $consulta->execute(['nombre' => $nombre, 'modulo' => $modulo, 'tope' => $tope]);
    }

    public function actualizar(int $id, string $nombre): bool
    {
        $consulta = $this->db->prepare('UPDATE autogestion_items SET nombre = :nombre WHERE id = :id');

        return $consulta->execute(['id' => $id, 'nombre' => $nombre]);
    }

    public function actualizarTope(int $id, ?float $tope): bool
    {
        $consulta = $this->db->prepare('UPDATE autogestion_items SET tope = :tope WHERE id = :id');

        return $consulta->execute(['id' => $id, 'tope' => $tope]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE autogestion_items SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Suma de los topes activos de los módulos indicados — usada en el Dashboard para la tarjeta
     * de Autogestión (Extensión + Sin excedentes), en vez del presupuesto general del año.
     */
    public function obtenerSumaTope(array $modulos): float
    {
        if (empty($modulos)) {
            return 0.0;
        }

        $marcadores = implode(',', array_fill(0, count($modulos), '?'));
        $consulta = $this->db->prepare(
            "SELECT COALESCE(SUM(tope), 0) FROM autogestion_items WHERE estado = 'activo' AND modulo IN ($marcadores)"
        );
        $consulta->execute(array_values($modulos));

        return (float) $consulta->fetchColumn();
    }
}
