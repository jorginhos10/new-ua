<?php

require_once __DIR__ . '/../config/conexion.php';

class Acta
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO actas (anio_presupuestal_id, dependencia_id, remitente_id, destinatario_id, nombre_archivo, nombre_almacenado, tamano_bytes)
             VALUES (:anio_presupuestal_id, :dependencia_id, :remitente_id, :destinatario_id, :nombre_archivo, :nombre_almacenado, :tamano_bytes)'
        );

        return $consulta->execute([
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia_id' => $datos['dependencia_id'],
            'remitente_id' => $datos['remitente_id'],
            'destinatario_id' => $datos['destinatario_id'],
            'nombre_archivo' => $datos['nombre_archivo'],
            'nombre_almacenado' => $datos['nombre_almacenado'],
            'tamano_bytes' => $datos['tamano_bytes'],
        ]);
    }

    public function obtenerRecibidas(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT a.*, d.nombre AS dependencia_nombre, an.anio, u.nombre AS remitente_nombre
             FROM actas a
             JOIN dependencias d ON d.id = a.dependencia_id
             JOIN anios_presupuestales an ON an.id = a.anio_presupuestal_id
             JOIN usuarios u ON u.id = a.remitente_id
             WHERE a.destinatario_id = :usuario_id
             ORDER BY a.creado_en DESC'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchAll();
    }

    public function obtenerEnviadas(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT a.*, d.nombre AS dependencia_nombre, an.anio, u.nombre AS destinatario_nombre
             FROM actas a
             JOIN dependencias d ON d.id = a.dependencia_id
             JOIN anios_presupuestales an ON an.id = a.anio_presupuestal_id
             JOIN usuarios u ON u.id = a.destinatario_id
             WHERE a.remitente_id = :usuario_id
             ORDER BY a.creado_en DESC'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM actas WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function marcarLeido(int $id, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE actas SET leido = 1 WHERE id = :id AND destinatario_id = :usuario_id'
        );

        return $consulta->execute(['id' => $id, 'usuario_id' => $usuarioId]);
    }

    public function eliminar(int $id, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'DELETE FROM actas WHERE id = :id AND (remitente_id = :usuario_id OR destinatario_id = :usuario_id2)'
        );

        return $consulta->execute(['id' => $id, 'usuario_id' => $usuarioId, 'usuario_id2' => $usuarioId]);
    }
}
