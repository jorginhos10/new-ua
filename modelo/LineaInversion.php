<?php

require_once __DIR__ . '/../config/conexion.php';

class LineaInversion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, nombre, descripcion, estado, creado_en FROM lineas_inversion ORDER BY codigo');

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, nombre, descripcion FROM lineas_inversion WHERE estado = 'activo' ORDER BY codigo"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, descripcion, estado FROM lineas_inversion WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, descripcion FROM lineas_inversion WHERE codigo = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM lineas_inversion WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre, string $descripcion): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO lineas_inversion (codigo, nombre, descripcion) VALUES (:codigo, :nombre, :descripcion)'
        );

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE lineas_inversion SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }
}
