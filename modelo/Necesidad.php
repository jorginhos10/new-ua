<?php

require_once __DIR__ . '/../config/conexion.php';

class Necesidad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function crear(array $datos, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO necesidades_academicas
                (usuario_id, linea_inversion, sublinea_inversion, detalle_inversion, sede, dependencia,
                 programa_academico, proyecto_pdi, articulacion_plan, espacio_intervenir, requisitos_normativos,
                 valor, fuente_financiacion, responsable, observaciones)
             VALUES
                (:usuario_id, :linea_inversion, :sublinea_inversion, :detalle_inversion, :sede, :dependencia,
                 :programa_academico, :proyecto_pdi, :articulacion_plan, :espacio_intervenir, :requisitos_normativos,
                 :valor, :fuente_financiacion, :responsable, :observaciones)'
        );

        return $consulta->execute([
            'usuario_id' => $usuarioId,
            'linea_inversion' => $datos['linea_inversion'],
            'sublinea_inversion' => $datos['sublinea_inversion'],
            'detalle_inversion' => $datos['detalle_inversion'] ?: null,
            'sede' => $datos['sede'],
            'dependencia' => $datos['dependencia'],
            'programa_academico' => $datos['programa_academico'] ?: null,
            'proyecto_pdi' => $datos['proyecto_pdi'] ?: null,
            'articulacion_plan' => $datos['articulacion_plan'] ?: null,
            'espacio_intervenir' => $datos['espacio_intervenir'] ?: null,
            'requisitos_normativos' => $datos['requisitos_normativos'] ?: null,
            'valor' => $datos['valor'],
            'fuente_financiacion' => $datos['fuente_financiacion'],
            'responsable' => $datos['responsable'],
            'observaciones' => $datos['observaciones'] ?: null,
        ]);
    }

    public function obtenerPorUsuario(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM necesidades_academicas WHERE usuario_id = :usuario_id ORDER BY creado_en DESC'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchAll();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->prepare(
            'SELECT n.*, u.nombre AS nombre_solicitante
             FROM necesidades_academicas n
             JOIN usuarios u ON u.id = n.usuario_id
             ORDER BY n.creado_en DESC'
        );
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function obtenerEnviadas(): array
    {
        $consulta = $this->db->prepare(
            "SELECT n.*, u.nombre AS nombre_solicitante
             FROM necesidades_academicas n
             JOIN usuarios u ON u.id = n.usuario_id
             WHERE n.estado = 'enviado'
             ORDER BY n.creado_en DESC"
        );
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function enviarTodosBorrador(int $rolDestinatarioId): int
    {
        $consulta = $this->db->prepare(
            "UPDATE necesidades_academicas
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id
             WHERE estado = 'borrador'"
        );
        $consulta->execute(['rol_destinatario_id' => $rolDestinatarioId]);

        return $consulta->rowCount();
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM necesidades_academicas WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function obtenerRecientes(int $limite = 5): array
    {
        $consulta = $this->db->prepare(
            'SELECT n.*, u.nombre AS nombre_solicitante
             FROM necesidades_academicas n
             JOIN usuarios u ON u.id = n.usuario_id
             ORDER BY n.creado_en DESC
             LIMIT :limite'
        );
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }
}
