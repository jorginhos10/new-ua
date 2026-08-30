<?php

require_once __DIR__ . '/../config/conexion.php';

class SolicitudOps
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT o.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM solicitudes_ops o
             JOIN sedes s ON s.id = o.sede_id
             JOIN lineas l ON l.id = o.linea_id
             JOIN motores m ON m.id = o.motor_id
             JOIN proyectos p ON p.id = o.proyecto_id
             JOIN rubros r ON r.id = o.rubro_id
             WHERE o.anio_presupuestal_id = :anio_presupuestal_id
             ORDER BY o.creado_en DESC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerEnviadasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT o.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM solicitudes_ops o
             JOIN sedes s ON s.id = o.sede_id
             JOIN lineas l ON l.id = o.linea_id
             JOIN motores m ON m.id = o.motor_id
             JOIN proyectos p ON p.id = o.proyecto_id
             JOIN rubros r ON r.id = o.rubro_id
             WHERE o.anio_presupuestal_id = :anio_presupuestal_id AND o.estado = 'enviada'
             ORDER BY o.creado_en DESC"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM solicitudes_ops WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerDetallePorId(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT o.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM solicitudes_ops o
             JOIN sedes s ON s.id = o.sede_id
             JOIN lineas l ON l.id = o.linea_id
             JOIN motores m ON m.id = o.motor_id
             JOIN proyectos p ON p.id = o.proyecto_id
             JOIN rubros r ON r.id = o.rubro_id
             WHERE o.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO solicitudes_ops
                (anio_presupuestal_id, sede_id, linea_id, motor_id, proyecto_id, dependencia, rol_destinatario_id, usuario_id, rubro_id, perfil, valor, cantidad, observaciones)
             VALUES
                (:anio_presupuestal_id, :sede_id, :linea_id, :motor_id, :proyecto_id, :dependencia, :rol_destinatario_id, :usuario_id, :rubro_id, :perfil, :valor, :cantidad, :observaciones)'
        );

        $parametros = $this->parametros($datos);
        $parametros['usuario_id'] = $datos['usuario_id'] ?? null;

        return $consulta->execute($parametros);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE solicitudes_ops SET
                anio_presupuestal_id = :anio_presupuestal_id,
                sede_id = :sede_id,
                linea_id = :linea_id,
                motor_id = :motor_id,
                proyecto_id = :proyecto_id,
                dependencia = :dependencia,
                rol_destinatario_id = :rol_destinatario_id,
                rubro_id = :rubro_id,
                perfil = :perfil,
                valor = :valor,
                cantidad = :cantidad,
                observaciones = :observaciones
             WHERE id = :id'
        );

        return $consulta->execute(array_merge($this->parametros($datos), ['id' => $id]));
    }

    private function parametros(array $datos): array
    {
        return [
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'sede_id' => $datos['sede_id'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'dependencia' => $datos['dependencia'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'rubro_id' => $datos['rubro_id'],
            'perfil' => $datos['perfil'],
            'valor' => $datos['valor'],
            'cantidad' => $datos['cantidad'],
            'observaciones' => $datos['observaciones'],
        ];
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM solicitudes_ops WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function enviar(int $id, string $dependenciaDestino): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE solicitudes_ops SET estado = 'enviada', enviada_a = :enviada_a WHERE id = :id"
        );

        return $consulta->execute(['id' => $id, 'enviada_a' => $dependenciaDestino]);
    }
}
