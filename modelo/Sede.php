<?php

require_once __DIR__ . '/../config/conexion.php';

class Sede
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, nombre, creado_en FROM sedes ORDER BY nombre');

        return $consulta->fetchAll();
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM sedes WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO sedes (codigo, nombre) VALUES (:codigo, :nombre)');

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre]);
    }
}
