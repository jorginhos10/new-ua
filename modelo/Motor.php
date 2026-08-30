<?php

require_once __DIR__ . '/../config/conexion.php';

class Motor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT m.id, m.codigo, m.nombre, m.linea_id, m.creado_en, l.codigo AS linea_codigo, l.nombre AS linea_nombre
             FROM motores m
             JOIN lineas l ON l.id = m.linea_id
             ORDER BY m.codigo'
        );

        return $consulta->fetchAll();
    }

    public function crear(string $codigo, string $nombre, int $lineaId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO motores (codigo, nombre, linea_id) VALUES (:codigo, :nombre, :linea_id)'
        );

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre, 'linea_id' => $lineaId]);
    }
}
