<?php

require_once __DIR__ . '/../config/conexion.php';

class SolicitudMonitor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM solicitudes_monitores WHERE anio_presupuestal_id = :anio_presupuestal_id ORDER BY dependencia ASC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerEnviadasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT * FROM solicitudes_monitores WHERE anio_presupuestal_id = :anio_presupuestal_id AND estado = 'enviada' ORDER BY dependencia ASC"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM solicitudes_monitores WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO solicitudes_monitores
                (anio_presupuestal_id, dependencia, rol_destinatario_id, usuario_id, tipo, monitores_semestre1, monitores_semestre2)
             VALUES
                (:anio_presupuestal_id, :dependencia, :rol_destinatario_id, :usuario_id, :tipo, :monitores_semestre1, :monitores_semestre2)'
        );

        return $consulta->execute([
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia' => $datos['dependencia'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'usuario_id' => $datos['usuario_id'] ?? null,
            'tipo' => $datos['tipo'],
            'monitores_semestre1' => $datos['monitores_semestre1'],
            'monitores_semestre2' => $datos['monitores_semestre2'],
        ]);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE solicitudes_monitores SET
                anio_presupuestal_id = :anio_presupuestal_id,
                dependencia = :dependencia,
                rol_destinatario_id = :rol_destinatario_id,
                tipo = :tipo,
                monitores_semestre1 = :monitores_semestre1,
                monitores_semestre2 = :monitores_semestre2
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia' => $datos['dependencia'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'tipo' => $datos['tipo'],
            'monitores_semestre1' => $datos['monitores_semestre1'],
            'monitores_semestre2' => $datos['monitores_semestre2'],
        ]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM solicitudes_monitores WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function enviar(int $id, string $dependenciaDestino, ?int $usuarioDestinatarioId = null): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE solicitudes_monitores SET estado = 'enviada', enviada_a = :enviada_a, usuario_destinatario_id = :usuario_destinatario_id WHERE id = :id"
        );

        return $consulta->execute(['id' => $id, 'enviada_a' => $dependenciaDestino, 'usuario_destinatario_id' => $usuarioDestinatarioId]);
    }
}
