<?php

require_once __DIR__ . '/../config/conexion.php';

class SolicitudPeticion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM solicitudes_peticiones WHERE anio_presupuestal_id = :anio_presupuestal_id ORDER BY creado_en DESC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerEnviadasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT * FROM solicitudes_peticiones WHERE anio_presupuestal_id = :anio_presupuestal_id AND estado = 'enviada' ORDER BY creado_en DESC"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM solicitudes_peticiones WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO solicitudes_peticiones
                (anio_presupuestal_id, concepto, rol_destinatario_id, usuario_id, semestre1, valor_s1, semestre2, valor_s2)
             VALUES
                (:anio_presupuestal_id, :concepto, :rol_destinatario_id, :usuario_id, :semestre1, :valor_s1, :semestre2, :valor_s2)'
        );

        $parametros = $this->parametros($datos);
        $parametros['usuario_id'] = $datos['usuario_id'] ?? null;

        return $consulta->execute($parametros);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE solicitudes_peticiones SET
                anio_presupuestal_id = :anio_presupuestal_id,
                concepto = :concepto,
                rol_destinatario_id = :rol_destinatario_id,
                semestre1 = :semestre1,
                valor_s1 = :valor_s1,
                semestre2 = :semestre2,
                valor_s2 = :valor_s2
             WHERE id = :id'
        );

        return $consulta->execute(array_merge($this->parametros($datos), ['id' => $id]));
    }

    private function parametros(array $datos): array
    {
        return [
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'concepto' => $datos['concepto'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'semestre1' => $datos['semestre1'],
            'valor_s1' => $datos['valor_s1'],
            'semestre2' => $datos['semestre2'],
            'valor_s2' => $datos['valor_s2'],
        ];
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM solicitudes_peticiones WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function enviar(int $id, string $dependenciaDestino, ?int $usuarioDestinatarioId = null): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE solicitudes_peticiones SET estado = 'enviada', enviada_a = :enviada_a, usuario_destinatario_id = :usuario_destinatario_id WHERE id = :id"
        );

        return $consulta->execute(['id' => $id, 'enviada_a' => $dependenciaDestino, 'usuario_destinatario_id' => $usuarioDestinatarioId]);
    }
}
