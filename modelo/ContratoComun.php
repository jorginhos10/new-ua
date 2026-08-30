<?php

require_once __DIR__ . '/../config/conexion.php';

class ContratoComun
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, descripcion, estado, creado_en FROM contratos_comunes ORDER BY codigo');

        return $consulta->fetchAll();
    }

    public function obtenerActivos(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, descripcion FROM contratos_comunes WHERE estado = 'activo' ORDER BY codigo"
        );

        return $consulta->fetchAll();
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM contratos_comunes WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $descripcion): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO contratos_comunes (codigo, descripcion) VALUES (:codigo, :descripcion)'
        );

        return $consulta->execute(['codigo' => $codigo, 'descripcion' => $descripcion]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE contratos_comunes SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }
}
