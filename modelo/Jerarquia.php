<?php

require_once __DIR__ . '/../config/conexion.php';

class Jerarquia
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query(
            'SELECT j.*, p.nombre AS nombre_padre
             FROM jerarquias j
             LEFT JOIN jerarquias p ON p.id = j.padre_id
             ORDER BY j.nombre ASC'
        );

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT * FROM jerarquias WHERE estado = 'activo' ORDER BY nombre ASC"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM jerarquias WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerTechoDisponible(int $padreId): ?float
    {
        $padre = $this->obtenerPorId($padreId);

        if ($padre === null || $padre['techo'] === null) {
            return null;
        }

        $consulta = $this->db->prepare(
            "SELECT COALESCE(SUM(techo), 0) FROM jerarquias
             WHERE padre_id = :padre_id AND estado = 'activo' AND techo IS NOT NULL"
        );
        $consulta->execute(['padre_id' => $padreId]);
        $usado = (float) $consulta->fetchColumn();

        return (float) $padre['techo'] - $usado;
    }

    public function crear(string $nombre, ?int $padreId, ?float $techo, ?string $tipo = null): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO jerarquias (nombre, padre_id, techo, tipo) VALUES (:nombre, :padre_id, :techo, :tipo)'
        );

        return $consulta->execute([
            'nombre' => $nombre,
            'padre_id' => $padreId,
            'techo' => $techo,
            'tipo' => $tipo,
        ]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE jerarquias SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function actualizar(int $id, string $nombre, ?int $padreId, ?string $tipo = null): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE jerarquias SET nombre = :nombre, padre_id = :padre_id, tipo = :tipo WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'nombre' => $nombre,
            'padre_id' => $padreId,
            'tipo' => $tipo,
        ]);
    }

    public function tieneHijos(int $id): bool
    {
        $consulta = $this->db->prepare('SELECT COUNT(*) FROM jerarquias WHERE padre_id = :padre_id');
        $consulta->execute(['padre_id' => $id]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM jerarquias WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }
}
