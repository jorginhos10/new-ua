<?php

require_once __DIR__ . '/../config/conexion.php';

class SolicitudArl
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM solicitudes_arl WHERE anio_presupuestal_id = :anio_presupuestal_id ORDER BY facultad ASC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerEnviadasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT * FROM solicitudes_arl WHERE anio_presupuestal_id = :anio_presupuestal_id AND estado = 'enviada' ORDER BY facultad ASC"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerRecientes(int $limite = 5): array
    {
        $consulta = $this->db->prepare('SELECT * FROM solicitudes_arl ORDER BY creado_en DESC LIMIT :limite');
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM solicitudes_arl WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO solicitudes_arl
                (anio_presupuestal_id, facultad, rol_destinatario_id, usuario_id,
                 riesgo1_estudiantes, riesgo1_valor, riesgo2_estudiantes, riesgo2_valor,
                 riesgo3_estudiantes, riesgo3_valor, riesgo4_estudiantes, riesgo4_valor,
                 riesgo5_estudiantes, riesgo5_valor)
             VALUES
                (:anio_presupuestal_id, :facultad, :rol_destinatario_id, :usuario_id,
                 :riesgo1_estudiantes, :riesgo1_valor, :riesgo2_estudiantes, :riesgo2_valor,
                 :riesgo3_estudiantes, :riesgo3_valor, :riesgo4_estudiantes, :riesgo4_valor,
                 :riesgo5_estudiantes, :riesgo5_valor)'
        );

        return $consulta->execute([
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'facultad' => $datos['facultad'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'usuario_id' => $datos['usuario_id'] ?? null,
            'riesgo1_estudiantes' => $datos['riesgo1_estudiantes'],
            'riesgo1_valor' => $datos['riesgo1_valor'],
            'riesgo2_estudiantes' => $datos['riesgo2_estudiantes'],
            'riesgo2_valor' => $datos['riesgo2_valor'],
            'riesgo3_estudiantes' => $datos['riesgo3_estudiantes'],
            'riesgo3_valor' => $datos['riesgo3_valor'],
            'riesgo4_estudiantes' => $datos['riesgo4_estudiantes'],
            'riesgo4_valor' => $datos['riesgo4_valor'],
            'riesgo5_estudiantes' => $datos['riesgo5_estudiantes'],
            'riesgo5_valor' => $datos['riesgo5_valor'],
        ]);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE solicitudes_arl SET
                anio_presupuestal_id = :anio_presupuestal_id,
                facultad = :facultad,
                rol_destinatario_id = :rol_destinatario_id,
                riesgo1_estudiantes = :riesgo1_estudiantes, riesgo1_valor = :riesgo1_valor,
                riesgo2_estudiantes = :riesgo2_estudiantes, riesgo2_valor = :riesgo2_valor,
                riesgo3_estudiantes = :riesgo3_estudiantes, riesgo3_valor = :riesgo3_valor,
                riesgo4_estudiantes = :riesgo4_estudiantes, riesgo4_valor = :riesgo4_valor,
                riesgo5_estudiantes = :riesgo5_estudiantes, riesgo5_valor = :riesgo5_valor
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'facultad' => $datos['facultad'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'riesgo1_estudiantes' => $datos['riesgo1_estudiantes'],
            'riesgo1_valor' => $datos['riesgo1_valor'],
            'riesgo2_estudiantes' => $datos['riesgo2_estudiantes'],
            'riesgo2_valor' => $datos['riesgo2_valor'],
            'riesgo3_estudiantes' => $datos['riesgo3_estudiantes'],
            'riesgo3_valor' => $datos['riesgo3_valor'],
            'riesgo4_estudiantes' => $datos['riesgo4_estudiantes'],
            'riesgo4_valor' => $datos['riesgo4_valor'],
            'riesgo5_estudiantes' => $datos['riesgo5_estudiantes'],
            'riesgo5_valor' => $datos['riesgo5_valor'],
        ]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM solicitudes_arl WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function enviar(int $id, string $dependenciaDestino, ?int $usuarioDestinatarioId = null): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE solicitudes_arl SET estado = 'enviada', enviada_a = :enviada_a, usuario_destinatario_id = :usuario_destinatario_id WHERE id = :id"
        );

        return $consulta->execute(['id' => $id, 'enviada_a' => $dependenciaDestino, 'usuario_destinatario_id' => $usuarioDestinatarioId]);
    }
}
