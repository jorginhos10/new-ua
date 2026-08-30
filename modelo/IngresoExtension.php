<?php

require_once __DIR__ . '/../config/conexion.php';

class IngresoExtension
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT i.*, a.nombre AS autogestion_nombre
             FROM ingresos_extension i
             LEFT JOIN autogestion_items a ON a.id = i.autogestion_id
             WHERE i.anio_presupuestal_id = :anio_presupuestal_id
             ORDER BY i.creado_en DESC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorAnioYAutogestion(int $anioPresupuestalId, int $autogestionId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM ingresos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id
             ORDER BY creado_en DESC'
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
        ]);
        $ingresos = $consulta->fetchAll();

        if (empty($ingresos)) {
            return [];
        }

        $ids = array_column($ingresos, 'id');
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $consultaConceptos = $this->db->prepare(
            "SELECT * FROM ingresos_extension_conceptos WHERE ingreso_id IN ($marcadores) ORDER BY id ASC"
        );
        $consultaConceptos->execute($ids);
        $conceptos = $consultaConceptos->fetchAll();

        $conceptosPorIngreso = [];
        foreach ($conceptos as $concepto) {
            $conceptosPorIngreso[(int) $concepto['ingreso_id']][] = $concepto;
        }

        foreach ($ingresos as &$ingreso) {
            $ingreso['conceptos'] = $conceptosPorIngreso[(int) $ingreso['id']] ?? [];
        }

        return $ingresos;
    }

    public function obtenerTotalPorAnioYAutogestion(int $anioPresupuestalId, int $autogestionId): float
    {
        $consulta = $this->db->prepare(
            'SELECT COALESCE(SUM(valor_total), 0) FROM ingresos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id'
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
        ]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerTotalPorAnio(int $anioPresupuestalId): float
    {
        $consulta = $this->db->prepare(
            'SELECT COALESCE(SUM(valor_total), 0) FROM ingresos_extension WHERE anio_presupuestal_id = :anio_presupuestal_id'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerDependenciasBorrador(int $anioPresupuestalId, int $autogestionId): array
    {
        $consulta = $this->db->prepare(
            "SELECT DISTINCT dependencia FROM ingresos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id AND estado = 'borrador'"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
        ]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function enviarTodosBorrador(int $anioPresupuestalId, int $autogestionId, int $rolDestinatarioId): int
    {
        $consulta = $this->db->prepare(
            "UPDATE ingresos_extension
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND autogestion_id = :autogestion_id
                AND estado = 'borrador'"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
            'rol_destinatario_id' => $rolDestinatarioId,
        ]);

        return $consulta->rowCount();
    }

    public function obtenerDependenciasSugeridas(): array
    {
        $consulta = $this->db->query(
            'SELECT DISTINCT dependencia FROM ingresos_extension ORDER BY dependencia ASC'
        );

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function obtenerDependenciasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT DISTINCT dependencia FROM ingresos_extension WHERE anio_presupuestal_id = :anio_presupuestal_id'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM ingresos_extension WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $ingreso = $consulta->fetch();

        if ($ingreso === false) {
            return null;
        }

        $consultaConceptos = $this->db->prepare(
            'SELECT * FROM ingresos_extension_conceptos WHERE ingreso_id = :ingreso_id ORDER BY id ASC'
        );
        $consultaConceptos->execute(['ingreso_id' => $id]);
        $ingreso['conceptos'] = $consultaConceptos->fetchAll();

        return $ingreso;
    }

    public function actualizar(int $id, array $cabecera, array $conceptos): bool
    {
        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare(
                'UPDATE ingresos_extension SET
                    anio_presupuestal_id = :anio_presupuestal_id,
                    autogestion_id = :autogestion_id,
                    dependencia = :dependencia,
                    concepto_adicional = :concepto_adicional,
                    valor_adicional = :valor_adicional,
                    valor_total = :valor_total
                 WHERE id = :id'
            );
            $consulta->execute([
                'anio_presupuestal_id' => $cabecera['anio_presupuestal_id'],
                'autogestion_id' => $cabecera['autogestion_id'],
                'dependencia' => $cabecera['dependencia'],
                'concepto_adicional' => $cabecera['concepto_adicional'],
                'valor_adicional' => $cabecera['valor_adicional'],
                'valor_total' => $cabecera['valor_total'],
                'id' => $id,
            ]);

            $this->db->prepare('DELETE FROM ingresos_extension_conceptos WHERE ingreso_id = :ingreso_id')
                ->execute(['ingreso_id' => $id]);

            $consultaConcepto = $this->db->prepare(
                'INSERT INTO ingresos_extension_conceptos (ingreso_id, concepto, cantidad, valor)
                 VALUES (:ingreso_id, :concepto, :cantidad, :valor)'
            );

            foreach ($conceptos as $concepto) {
                $consultaConcepto->execute([
                    'ingreso_id' => $id,
                    'concepto' => $concepto['concepto'],
                    'cantidad' => $concepto['cantidad'],
                    'valor' => $concepto['valor'],
                ]);
            }

            $this->db->commit();

            return true;
        } catch (PDOException $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM ingresos_extension WHERE id = :id');
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }

    public function crear(array $cabecera, array $conceptos): int
    {
        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare(
                'INSERT INTO ingresos_extension
                    (anio_presupuestal_id, autogestion_id, dependencia, concepto_adicional, valor_adicional, valor_total)
                 VALUES
                    (:anio_presupuestal_id, :autogestion_id, :dependencia, :concepto_adicional, :valor_adicional, :valor_total)'
            );
            $consulta->execute([
                'anio_presupuestal_id' => $cabecera['anio_presupuestal_id'],
                'autogestion_id' => $cabecera['autogestion_id'],
                'dependencia' => $cabecera['dependencia'],
                'concepto_adicional' => $cabecera['concepto_adicional'],
                'valor_adicional' => $cabecera['valor_adicional'],
                'valor_total' => $cabecera['valor_total'],
            ]);

            $ingresoId = (int) $this->db->lastInsertId();

            $consultaConcepto = $this->db->prepare(
                'INSERT INTO ingresos_extension_conceptos (ingreso_id, concepto, cantidad, valor)
                 VALUES (:ingreso_id, :concepto, :cantidad, :valor)'
            );

            foreach ($conceptos as $concepto) {
                $consultaConcepto->execute([
                    'ingreso_id' => $ingresoId,
                    'concepto' => $concepto['concepto'],
                    'cantidad' => $concepto['cantidad'],
                    'valor' => $concepto['valor'],
                ]);
            }

            $this->db->commit();

            return $ingresoId;
        } catch (PDOException $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }
}
