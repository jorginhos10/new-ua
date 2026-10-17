<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Gastos fijos (tabla gastos_fijos): los carga el superadmin desde Configuraciones > Gastos fijos
 * con el mismo formulario/plantilla de Gastos, y SOLO se reflejan en Análisis > Análisis de
 * distribución, dentro del módulo Gasto (ver AnalisisControlador::obtenerFilasCrudasModulo()).
 * Viven en su propia tabla, nunca en `gastos`, para que no los vea ninguna suma de techos, envío,
 * Techos y Metas, Dashboard ni Articulación PDI. Mismas columnas de contenido que `gastos`, así el
 * armado de filas del análisis los lee igual.
 */
class GastoFijo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** Mismo shape que Gasto::obtenerPorAnio() (g.* + códigos/nombres resueltos). */
    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT g.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM gastos_fijos g
             JOIN sedes s ON s.id = g.sede_id
             JOIN lineas l ON l.id = g.linea_id
             JOIN motores m ON m.id = g.motor_id
             JOIN proyectos p ON p.id = g.proyecto_id
             LEFT JOIN rubros r ON r.id = g.rubro_id
             WHERE g.anio_presupuestal_id = :anio_presupuestal_id
             ORDER BY g.creado_en DESC, g.id DESC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM gastos_fijos WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO gastos_fijos
                (anio_presupuestal_id, sede_id, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_id, insumo, cantidad, costo_unitario, valor_total, meses, categoria_gasto_id, categoria_origen, categoria_confianza, usuario_id)
             VALUES
                (:anio_presupuestal_id, :sede_id, :dependencia, :linea_id, :motor_id, :proyecto_id, :objeto_proyecto_paa, :actividad, :rubro_id, :insumo, :cantidad, :costo_unitario, :valor_total, :meses, :categoria_gasto_id, :categoria_origen, :categoria_confianza, :usuario_id)'
        );

        return $consulta->execute($this->parametros($datos) + ['usuario_id' => $datos['usuario_id'] ?? null]);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE gastos_fijos SET
                anio_presupuestal_id = :anio_presupuestal_id, sede_id = :sede_id, dependencia = :dependencia,
                linea_id = :linea_id, motor_id = :motor_id, proyecto_id = :proyecto_id,
                objeto_proyecto_paa = :objeto_proyecto_paa, actividad = :actividad, rubro_id = :rubro_id,
                insumo = :insumo, cantidad = :cantidad, costo_unitario = :costo_unitario,
                valor_total = :valor_total, meses = :meses,
                categoria_gasto_id = :categoria_gasto_id, categoria_origen = :categoria_origen, categoria_confianza = :categoria_confianza
             WHERE id = :id'
        );

        return $consulta->execute($this->parametros($datos) + ['id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM gastos_fijos WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    private function parametros(array $datos): array
    {
        return [
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'sede_id' => $datos['sede_id'],
            'dependencia' => $datos['dependencia'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'objeto_proyecto_paa' => (string) ($datos['objeto_proyecto_paa'] ?? ''),
            'actividad' => $datos['actividad'],
            'rubro_id' => $datos['rubro_id'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['cantidad'] * $datos['costo_unitario'],
            'meses' => $datos['meses'],
            'categoria_gasto_id' => $datos['categoria_gasto_id'] ?? null,
            'categoria_origen' => $datos['categoria_origen'] ?? null,
            'categoria_confianza' => $datos['categoria_confianza'] ?? null,
        ];
    }
}
