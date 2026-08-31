<?php

require_once __DIR__ . '/../config/conexion.php';

class Mensaje
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerRecientesRecibidos(int $usuarioId, int $limite = 5): array
    {
        $consulta = $this->db->prepare(
            'SELECT m.id, m.remitente_id, m.asunto, m.cuerpo, m.leido, m.creado_en, u.nombre AS remitente_nombre
             FROM mensajes m
             JOIN usuarios u ON u.id = m.remitente_id
             WHERE m.destinatario_id = :usuario_id
             ORDER BY m.creado_en DESC
             LIMIT :limite'
        );
        $consulta->bindValue('usuario_id', $usuarioId, PDO::PARAM_INT);
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function contarNoLeidos(int $usuarioId): int
    {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*) FROM mensajes WHERE destinatario_id = :usuario_id AND leido = 0'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return (int) $consulta->fetchColumn();
    }

    public function obtenerRecibidos(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT m.id, m.remitente_id, m.asunto, m.cuerpo, m.leido, m.creado_en, u.nombre AS remitente_nombre, u.correo AS remitente_correo
             FROM mensajes m
             JOIN usuarios u ON u.id = m.remitente_id
             WHERE m.destinatario_id = :usuario_id
             ORDER BY m.creado_en DESC'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchAll();
    }

    public function obtenerEnviados(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT m.id, m.destinatario_id, m.asunto, m.cuerpo, m.leido, m.creado_en, u.nombre AS destinatario_nombre, u.correo AS destinatario_correo
             FROM mensajes m
             JOIN usuarios u ON u.id = m.destinatario_id
             WHERE m.remitente_id = :usuario_id
             ORDER BY m.creado_en DESC'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT m.*, ur.nombre AS remitente_nombre, ud.nombre AS destinatario_nombre
             FROM mensajes m
             JOIN usuarios ur ON ur.id = m.remitente_id
             JOIN usuarios ud ON ud.id = m.destinatario_id
             WHERE m.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(int $remitenteId, int $destinatarioId, string $asunto, string $cuerpo): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO mensajes (remitente_id, destinatario_id, asunto, cuerpo) VALUES (:remitente_id, :destinatario_id, :asunto, :cuerpo)'
        );

        return $consulta->execute([
            'remitente_id' => $remitenteId,
            'destinatario_id' => $destinatarioId,
            'asunto' => $asunto,
            'cuerpo' => $cuerpo,
        ]);
    }

    public function marcarLeido(int $id, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE mensajes SET leido = 1 WHERE id = :id AND destinatario_id = :usuario_id'
        );

        return $consulta->execute(['id' => $id, 'usuario_id' => $usuarioId]);
    }

    public function eliminar(int $id, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'DELETE FROM mensajes WHERE id = :id AND (remitente_id = :usuario_id OR destinatario_id = :usuario_id2)'
        );

        return $consulta->execute(['id' => $id, 'usuario_id' => $usuarioId, 'usuario_id2' => $usuarioId]);
    }
}
