<?php

require_once __DIR__ . '/../config/conexion.php';

class IngresoSinExcedentes
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM ingresos_sin_excedentes
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
            "SELECT * FROM ingresos_sin_excedentes_conceptos WHERE ingreso_id IN ($marcadores) ORDER BY id ASC"
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
            'SELECT COALESCE(SUM(valor_total), 0) FROM ingresos_sin_excedentes
             WHERE anio_presupuestal_id = :anio_presupuestal_id'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return (float) $consulta->fetchColumn();
    }

    /**
     * Igual que obtenerTotalPorAnio(), pero acotado a un conjunto de dependencias — evita sumar
     * ingresos de dependencias que la persona que consulta no debería ver (usarlo siempre que el
     * total se vaya a mostrar o a validar contra un techo, en vez de obtenerTotalPorAnio()).
     */
    public function obtenerTotalPorAnioYDependencias(int $anioPresupuestalId, array $dependencias): float
    {
        if (empty($dependencias)) {
            return 0.0;
        }

        $parametros = ['anio_presupuestal_id' => $anioPresupuestalId];
        $marcadores = [];
        foreach (array_values($dependencias) as $indice => $dependencia) {
            $clave = 'dep' . $indice;
            $marcadores[] = ':' . $clave;
            $parametros[$clave] = $dependencia;
        }

        $consulta = $this->db->prepare(
            'SELECT COALESCE(SUM(valor_total), 0) FROM ingresos_sin_excedentes
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND dependencia IN (' . implode(', ', $marcadores) . ')'
        );
        $consulta->execute($parametros);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerDependenciasBorrador(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT DISTINCT dependencia FROM ingresos_sin_excedentes
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND estado = 'borrador'"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function enviarTodosBorrador(int $anioPresupuestalId, string $dependenciaDestinoNombre, int $rolDestinatarioId, string $categoriaPeticion, array $dependenciasOrigen, ?int $usuarioDestinatarioId = null): int
    {
        if (empty($dependenciasOrigen)) {
            return 0;
        }

        $parametros = [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia' => $dependenciaDestinoNombre,
            'rol_destinatario_id' => $rolDestinatarioId,
            'usuario_destinatario_id' => $usuarioDestinatarioId,
            'categoria_peticion' => $categoriaPeticion,
        ];
        $marcadores = [];
        foreach (array_values($dependenciasOrigen) as $indice => $dependenciaOrigen) {
            $clave = 'depOrigen' . $indice;
            $marcadores[] = ':' . $clave;
            $parametros[$clave] = $dependenciaOrigen;
        }

        // El IN de dependencia acota a solo las dependencias que el remitente puede ver — sin
        // esto, "Enviar todo" marcaba como enviados los borradores de CUALQUIER dependencia del
        // año, no solo los del usuario que envía.
        $consulta = $this->db->prepare(
            "UPDATE ingresos_sin_excedentes
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id, usuario_destinatario_id = :usuario_destinatario_id, dependencia_destino = :dependencia, categoria_peticion = :categoria_peticion
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND estado = 'borrador'
                AND dependencia IN (" . implode(', ', $marcadores) . ')'
        );
        $consulta->execute($parametros);

        return $consulta->rowCount();
    }

    public function obtenerDependenciasSugeridas(): array
    {
        $consulta = $this->db->query(
            'SELECT DISTINCT dependencia FROM ingresos_sin_excedentes ORDER BY dependencia ASC'
        );

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function obtenerDependenciasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT DISTINCT dependencia FROM ingresos_sin_excedentes WHERE anio_presupuestal_id = :anio_presupuestal_id'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM ingresos_sin_excedentes WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $ingreso = $consulta->fetch();

        if ($ingreso === false) {
            return null;
        }

        $consultaConceptos = $this->db->prepare(
            'SELECT * FROM ingresos_sin_excedentes_conceptos WHERE ingreso_id = :ingreso_id ORDER BY id ASC'
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
                'UPDATE ingresos_sin_excedentes SET
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

            $this->db->prepare('DELETE FROM ingresos_sin_excedentes_conceptos WHERE ingreso_id = :ingreso_id')
                ->execute(['ingreso_id' => $id]);

            $consultaConcepto = $this->db->prepare(
                'INSERT INTO ingresos_sin_excedentes_conceptos (ingreso_id, concepto, cantidad, valor)
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
        $consulta = $this->db->prepare('DELETE FROM ingresos_sin_excedentes WHERE id = :id');
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }

    public function crear(array $cabecera, array $conceptos): int
    {
        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare(
                'INSERT INTO ingresos_sin_excedentes
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
                'INSERT INTO ingresos_sin_excedentes_conceptos (ingreso_id, concepto, cantidad, valor)
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
