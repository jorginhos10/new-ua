<?php

require_once __DIR__ . '/../config/conexion.php';

class Rol
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, nombre, orden, creado_en FROM roles ORDER BY orden ASC, id ASC');

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, orden, creado_en FROM roles WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeRol(string $nombre, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->db->prepare('SELECT id FROM roles WHERE nombre = :nombre AND id != :id LIMIT 1');
            $consulta->execute(['nombre' => $nombre, 'id' => $ignorarId]);
        } else {
            $consulta = $this->db->prepare('SELECT id FROM roles WHERE nombre = :nombre LIMIT 1');
            $consulta->execute(['nombre' => $nombre]);
        }

        return $consulta->fetch() !== false;
    }

    public function obtenerSiguienteOrden(): int
    {
        $consulta = $this->db->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM roles');

        return (int) $consulta->fetchColumn();
    }

    public function crear(string $nombre, int $orden): bool
    {
        $consulta = $this->db->prepare('INSERT INTO roles (nombre, orden) VALUES (:nombre, :orden)');

        return $consulta->execute(['nombre' => $nombre, 'orden' => $orden]);
    }

    public function actualizar(int $id, string $nombre, int $orden): bool
    {
        $consulta = $this->db->prepare('UPDATE roles SET nombre = :nombre, orden = :orden WHERE id = :id');

        return $consulta->execute(['id' => $id, 'nombre' => $nombre, 'orden' => $orden]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM roles WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }
}
