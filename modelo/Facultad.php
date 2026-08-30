<?php

require_once __DIR__ . '/../config/conexion.php';

class Facultad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, nombre, estado, creado_en FROM facultades ORDER BY nombre');

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query("SELECT id, nombre FROM facultades WHERE estado = 'activo' ORDER BY nombre");

        return $consulta->fetchAll();
    }

    public function existeNombre(string $nombre): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM facultades WHERE nombre = :nombre LIMIT 1');
        $consulta->execute(['nombre' => $nombre]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO facultades (nombre) VALUES (:nombre)');

        return $consulta->execute(['nombre' => $nombre]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE facultades SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }
}
