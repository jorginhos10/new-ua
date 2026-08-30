<?php

require_once __DIR__ . '/../config/conexion.php';

class SublineaInversion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query(
            'SELECT s.id, s.codigo, s.nombre, s.descripcion, s.linea_inversion_id, s.estado, s.creado_en,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre
             FROM sublineas_inversion s
             JOIN lineas_inversion l ON l.id = s.linea_inversion_id
             ORDER BY s.codigo'
        );

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT s.id, s.codigo, s.nombre, s.descripcion, s.linea_inversion_id, l.codigo AS linea_codigo
             FROM sublineas_inversion s
             JOIN lineas_inversion l ON l.id = s.linea_inversion_id
             WHERE s.estado = 'activo'
             ORDER BY s.codigo"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, descripcion, linea_inversion_id FROM sublineas_inversion WHERE codigo = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM sublineas_inversion WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre, string $descripcion, int $lineaInversionId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO sublineas_inversion (codigo, nombre, descripcion, linea_inversion_id) VALUES (:codigo, :nombre, :descripcion, :linea_inversion_id)'
        );

        return $consulta->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'linea_inversion_id' => $lineaInversionId,
        ]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE sublineas_inversion SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }
}
