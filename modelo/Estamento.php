<?php

require_once __DIR__ . '/../config/conexion.php';

class Estamento
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, nombre, creado_en FROM estamentos ORDER BY creado_en DESC');

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, creado_en FROM estamentos WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeEstamento(string $nombre, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->db->prepare('SELECT id FROM estamentos WHERE nombre = :nombre AND id != :id LIMIT 1');
            $consulta->execute(['nombre' => $nombre, 'id' => $ignorarId]);
        } else {
            $consulta = $this->db->prepare('SELECT id FROM estamentos WHERE nombre = :nombre LIMIT 1');
            $consulta->execute(['nombre' => $nombre]);
        }

        return $consulta->fetch() !== false;
    }

    public function crear(string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO estamentos (nombre) VALUES (:nombre)');

        return $consulta->execute(['nombre' => $nombre]);
    }

    public function actualizar(int $id, string $nombre): bool
    {
        $consulta = $this->db->prepare('UPDATE estamentos SET nombre = :nombre WHERE id = :id');

        return $consulta->execute(['id' => $id, 'nombre' => $nombre]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM estamentos WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }
}
