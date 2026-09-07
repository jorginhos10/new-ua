<?php

require_once __DIR__ . '/../config/conexion.php';

class IngresoPostgrado
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM ingresos_postgrado
             WHERE anio_presupuestal_id = :anio_presupuestal_id
             ORDER BY creado_en DESC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);
        $ingresos = $consulta->fetchAll();

        if (empty($ingresos)) {
            return [];
        }

        $ids = array_column($ingresos, 'id');
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $consultaConceptos = $this->db->prepare(
            "SELECT * FROM ingresos_postgrado_conceptos WHERE ingreso_id IN ($marcadores) ORDER BY id ASC"
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

    public function obtenerTotalPorAnio(int $anioPresupuestalId): float
    {
        $consulta = $this->db->prepare(
            'SELECT COALESCE(SUM(valor_total), 0) FROM ingresos_postgrado
             WHERE anio_presupuestal_id = :anio_presupuestal_id'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerDependenciasBorrador(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT DISTINCT dependencia FROM ingresos_postgrado
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND estado = 'borrador'"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function enviarTodosBorrador(int $anioPresupuestalId, string $dependenciaDestinoNombre, int $rolDestinatarioId): int
    {
        $consulta = $this->db->prepare(
            "UPDATE ingresos_postgrado
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id, dependencia_destino = :dependencia
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND estado = 'borrador'"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia' => $dependenciaDestinoNombre,
            'rol_destinatario_id' => $rolDestinatarioId,
        ]);

        return $consulta->rowCount();
    }

    public function obtenerDependenciasSugeridas(): array
    {
        $consulta = $this->db->query(
            'SELECT DISTINCT dependencia FROM ingresos_postgrado ORDER BY dependencia ASC'
        );

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function obtenerDependenciasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT DISTINCT dependencia FROM ingresos_postgrado WHERE anio_presupuestal_id = :anio_presupuestal_id'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM ingresos_postgrado WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $ingreso = $consulta->fetch();

        if ($ingreso === false) {
            return null;
        }

        $consultaConceptos = $this->db->prepare(
            'SELECT * FROM ingresos_postgrado_conceptos WHERE ingreso_id = :ingreso_id ORDER BY id ASC'
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
                'UPDATE ingresos_postgrado SET
                    anio_presupuestal_id = :anio_presupuestal_id,
                    dependencia = :dependencia,
                    concepto_adicional = :concepto_adicional,
                    valor_adicional = :valor_adicional,
                    valor_total = :valor_total
                 WHERE id = :id'
            );
            $consulta->execute([
                'anio_presupuestal_id' => $cabecera['anio_presupuestal_id'],
                'dependencia' => $cabecera['dependencia'],
                'concepto_adicional' => $cabecera['concepto_adicional'],
                'valor_adicional' => $cabecera['valor_adicional'],
                'valor_total' => $cabecera['valor_total'],
                'id' => $id,
            ]);

            $this->db->prepare('DELETE FROM ingresos_postgrado_conceptos WHERE ingreso_id = :ingreso_id')
                ->execute(['ingreso_id' => $id]);

            $consultaConcepto = $this->db->prepare(
                'INSERT INTO ingresos_postgrado_conceptos (ingreso_id, concepto, cantidad, valor)
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
        $consulta = $this->db->prepare('DELETE FROM ingresos_postgrado WHERE id = :id');
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }

    public function crear(array $cabecera, array $conceptos): int
    {
        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare(
                'INSERT INTO ingresos_postgrado
                    (anio_presupuestal_id, dependencia, concepto_adicional, valor_adicional, valor_total, usuario_id)
                 VALUES
                    (:anio_presupuestal_id, :dependencia, :concepto_adicional, :valor_adicional, :valor_total, :usuario_id)'
            );
            $consulta->execute([
                'anio_presupuestal_id' => $cabecera['anio_presupuestal_id'],
                'dependencia' => $cabecera['dependencia'],
                'concepto_adicional' => $cabecera['concepto_adicional'],
                'valor_adicional' => $cabecera['valor_adicional'],
                'valor_total' => $cabecera['valor_total'],
                'usuario_id' => $cabecera['usuario_id'] ?? null,
            ]);

            $ingresoId = (int) $this->db->lastInsertId();

            $consultaConcepto = $this->db->prepare(
                'INSERT INTO ingresos_postgrado_conceptos (ingreso_id, concepto, cantidad, valor)
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
