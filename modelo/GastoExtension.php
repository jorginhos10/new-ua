<?php

require_once __DIR__ . '/../config/conexion.php';

class GastoExtension
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT g.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion,
                    a.nombre AS autogestion_nombre
             FROM gastos_extension g
             JOIN sedes s ON s.id = g.sede_id
             LEFT JOIN rubros r ON r.id = g.rubro_id
             LEFT JOIN autogestion_items a ON a.id = g.autogestion_id
             WHERE g.anio_presupuestal_id = :anio_presupuestal_id
             ORDER BY g.creado_en DESC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorAnioYAutogestion(int $anioPresupuestalId, int $autogestionId): array
    {
        $consulta = $this->db->prepare(
            'SELECT g.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM gastos_extension g
             JOIN sedes s ON s.id = g.sede_id
             JOIN lineas l ON l.id = g.linea_id
             JOIN motores m ON m.id = g.motor_id
             JOIN proyectos p ON p.id = g.proyecto_id
             LEFT JOIN rubros r ON r.id = g.rubro_id
             WHERE g.anio_presupuestal_id = :anio_presupuestal_id AND g.autogestion_id = :autogestion_id
             ORDER BY
                CASE g.tipo_automatico
                    WHEN \'costos_inversiones\' THEN 1
                    WHEN \'costos\' THEN 1
                    WHEN \'inversiones\' THEN 2
                    WHEN \'excedentes\' THEN 3
                    ELSE 4
                END,
                g.creado_en DESC'
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
        ]);

        return $consulta->fetchAll();
    }

    public function obtenerDetallePorId(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT g.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion,
                    a.nombre AS autogestion_nombre
             FROM gastos_extension g
             JOIN sedes s ON s.id = g.sede_id
             JOIN lineas l ON l.id = g.linea_id
             JOIN motores m ON m.id = g.motor_id
             JOIN proyectos p ON p.id = g.proyecto_id
             LEFT JOIN rubros r ON r.id = g.rubro_id
             LEFT JOIN autogestion_items a ON a.id = g.autogestion_id
             WHERE g.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerTotalPorAnioYAutogestion(int $anioPresupuestalId, int $autogestionId): float
    {
        $consulta = $this->db->prepare(
            'SELECT COALESCE(SUM(valor_total), 0) FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id'
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
        ]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerTotalPorAnioAutogestionYCategoria(int $anioPresupuestalId, int $autogestionId, string $categoria): float
    {
        $consulta = $this->db->prepare(
            'SELECT COALESCE(SUM(valor_total), 0) FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id AND categoria = :categoria'
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
            'categoria' => $categoria,
        ]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerDependenciasBorrador(int $anioPresupuestalId, int $autogestionId): array
    {
        $consulta = $this->db->prepare(
            "SELECT DISTINCT dependencia FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id AND estado = 'borrador'"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
        ]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function enviarTodosBorrador(int $anioPresupuestalId, int $autogestionId, string $dependenciaDestinoNombre, int $rolDestinatarioId): int
    {
        $consulta = $this->db->prepare(
            "UPDATE gastos_extension
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id, dependencia = :dependencia
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND autogestion_id = :autogestion_id
                AND estado = 'borrador'"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
            'dependencia' => $dependenciaDestinoNombre,
            'rol_destinatario_id' => $rolDestinatarioId,
        ]);

        return $consulta->rowCount();
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO gastos_extension
                (sede_id, anio_presupuestal_id, categoria, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_id, autogestion_id, insumo, cantidad, costo_unitario, valor_total, meses)
             VALUES
                (:sede_id, :anio_presupuestal_id, :categoria, :dependencia, :linea_id, :motor_id, :proyecto_id, :objeto_proyecto_paa, :actividad, :rubro_id, :autogestion_id, :insumo, :cantidad, :costo_unitario, :valor_total, :meses)'
        );

        return $consulta->execute([
            'sede_id' => $datos['sede_id'],
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'categoria' => $datos['categoria'],
            'dependencia' => $datos['dependencia'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'objeto_proyecto_paa' => $datos['objeto_proyecto_paa'],
            'actividad' => $datos['actividad'],
            'rubro_id' => $datos['rubro_id'],
            'autogestion_id' => $datos['autogestion_id'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['cantidad'] * $datos['costo_unitario'],
            'meses' => $datos['meses'],
        ]);
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM gastos_extension WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE gastos_extension SET
                sede_id = :sede_id, anio_presupuestal_id = :anio_presupuestal_id, categoria = :categoria,
                dependencia = :dependencia, linea_id = :linea_id, motor_id = :motor_id, proyecto_id = :proyecto_id,
                objeto_proyecto_paa = :objeto_proyecto_paa, actividad = :actividad, rubro_id = :rubro_id,
                autogestion_id = :autogestion_id, insumo = :insumo, cantidad = :cantidad,
                costo_unitario = :costo_unitario, valor_total = :valor_total, meses = :meses
             WHERE id = :id AND tipo_automatico IS NULL"
        );

        return $consulta->execute([
            'id' => $id,
            'sede_id' => $datos['sede_id'],
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'categoria' => $datos['categoria'],
            'dependencia' => $datos['dependencia'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'objeto_proyecto_paa' => $datos['objeto_proyecto_paa'],
            'actividad' => $datos['actividad'],
            'rubro_id' => $datos['rubro_id'],
            'autogestion_id' => $datos['autogestion_id'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['cantidad'] * $datos['costo_unitario'],
            'meses' => $datos['meses'],
        ]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM gastos_extension WHERE id = :id AND tipo_automatico IS NULL');

        return $consulta->execute(['id' => $id]);
    }


    public function obtenerAutomaticoPorTipo(int $anioPresupuestalId, int $autogestionId, string $tipo): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id AND tipo_automatico = :tipo
             LIMIT 1'
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
            'tipo' => $tipo,
        ]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crearAutomatico(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO gastos_extension
                (sede_id, anio_presupuestal_id, categoria, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_texto, autogestion_id, ingreso_id, tipo_automatico, insumo, cantidad, costo_unitario, valor_total, meses)
             VALUES
                (:sede_id, :anio_presupuestal_id, :categoria, :dependencia, :linea_id, :motor_id, :proyecto_id, :objeto_proyecto_paa, :actividad, :rubro_texto, :autogestion_id, :ingreso_id, :tipo_automatico, :insumo, :cantidad, :costo_unitario, :valor_total, :meses)'
        );

        return $consulta->execute([
            'sede_id' => $datos['sede_id'],
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'categoria' => $datos['categoria'],
            'dependencia' => $datos['dependencia'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'objeto_proyecto_paa' => $datos['objeto_proyecto_paa'],
            'actividad' => $datos['actividad'],
            'rubro_texto' => $datos['rubro_texto'],
            'autogestion_id' => $datos['autogestion_id'],
            'ingreso_id' => $datos['ingreso_id'],
            'tipo_automatico' => $datos['tipo_automatico'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['valor_total'],
            'meses' => $datos['meses'],
        ]);
    }

    public function eliminarAutomaticosDistintosDe(int $anioPresupuestalId, int $autogestionId, array $tiposValidos): bool
    {
        if (empty($tiposValidos)) {
            $consulta = $this->db->prepare(
                'DELETE FROM gastos_extension
                 WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id AND tipo_automatico IS NOT NULL'
            );

            return $consulta->execute([
                'anio_presupuestal_id' => $anioPresupuestalId,
                'autogestion_id' => $autogestionId,
            ]);
        }

        $marcadores = implode(',', array_fill(0, count($tiposValidos), '?'));
        $consulta = $this->db->prepare(
            "DELETE FROM gastos_extension
             WHERE anio_presupuestal_id = ? AND autogestion_id = ? AND tipo_automatico IS NOT NULL AND tipo_automatico NOT IN ($marcadores)"
        );

        return $consulta->execute(array_merge([$anioPresupuestalId, $autogestionId], $tiposValidos));
    }

    public function actualizarAutomatico(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE gastos_extension
             SET categoria = :categoria, dependencia = :dependencia, ingreso_id = :ingreso_id,
                 costo_unitario = :costo_unitario, valor_total = :valor_total
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'categoria' => $datos['categoria'],
            'dependencia' => $datos['dependencia'],
            'ingreso_id' => $datos['ingreso_id'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['valor_total'],
        ]);
    }
}
