<?php

require_once __DIR__ . '/../config/conexion.php';

class Gasto
{
    private PDO $db;

    private const AUTOMATICO_SEDE_ID = 1;
    private const AUTOMATICO_LINEA_ID = 5;
    private const AUTOMATICO_MOTOR_ID = 1;
    private const AUTOMATICO_PROYECTO_ID = 1;
    private const AUTOMATICO_RUBRO_TEXTO = 'ASIGNACIÓN DE TECHO A DEPENDENCIA';
    private const AUTOMATICO_MES = 1;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Crea, actualiza o elimina el gasto automático que representa la asignación de un techo
     * de $dependenciaPadre hacia $dependenciaHija. Se usa tanto al guardar techos como al
     * restaurar una versión del historial.
     */
    public function sincronizarAutomaticoTechoHija(int $anioId, ?array $dependenciaPadre, array $dependenciaHija, ?float $techo): void
    {
        $dependenciaHijaId = (int) $dependenciaHija['id'];

        if ($techo === null || $techo <= 0 || $dependenciaPadre === null) {
            $this->eliminarAutomaticoPorHija($anioId, $dependenciaHijaId);

            return;
        }

        $datos = [
            'anio_presupuestal_id' => $anioId,
            'sede_id' => self::AUTOMATICO_SEDE_ID,
            'dependencia' => $dependenciaPadre['nombre'],
            'linea_id' => self::AUTOMATICO_LINEA_ID,
            'motor_id' => self::AUTOMATICO_MOTOR_ID,
            'proyecto_id' => self::AUTOMATICO_PROYECTO_ID,
            'objeto_proyecto_paa' => 'Asignación de techo presupuestal',
            'actividad' => 'Asignación de techo a ' . $dependenciaHija['nombre'],
            'rubro_texto' => self::AUTOMATICO_RUBRO_TEXTO,
            'insumo' => 'Techo asignado a ' . $dependenciaHija['nombre'],
            'cantidad' => 1,
            'costo_unitario' => $techo,
            'valor_total' => $techo,
            'meses' => (string) self::AUTOMATICO_MES,
            'dependencia_hija_id' => $dependenciaHijaId,
        ];

        $existente = $this->obtenerAutomaticoPorHija($anioId, $dependenciaHijaId);

        if ($existente !== null) {
            $this->actualizarAutomaticoTechoHija((int) $existente['id'], $datos);
        } else {
            $this->crearAutomaticoTechoHija($datos);
        }
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT g.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM gastos g
             JOIN sedes s ON s.id = g.sede_id
             JOIN lineas l ON l.id = g.linea_id
             JOIN motores m ON m.id = g.motor_id
             JOIN proyectos p ON p.id = g.proyecto_id
             LEFT JOIN rubros r ON r.id = g.rubro_id
             WHERE g.anio_presupuestal_id = :anio_presupuestal_id
                AND (g.tipo_automatico IS NULL OR g.tipo_automatico != \'techo_hijo\')
             ORDER BY g.creado_en DESC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function obtenerEnviadosPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT g.*,
                    s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM gastos g
             JOIN sedes s ON s.id = g.sede_id
             JOIN lineas l ON l.id = g.linea_id
             JOIN motores m ON m.id = g.motor_id
             JOIN proyectos p ON p.id = g.proyecto_id
             LEFT JOIN rubros r ON r.id = g.rubro_id
             WHERE g.anio_presupuestal_id = :anio_presupuestal_id
                AND g.estado = 'enviado'
                AND (g.tipo_automatico IS NULL OR g.tipo_automatico != 'techo_hijo')
             ORDER BY g.creado_en DESC"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return $consulta->fetchAll();
    }

    public function enviar(int $id): bool
    {
        $consulta = $this->db->prepare("UPDATE gastos SET estado = 'enviado' WHERE id = :id");

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Envía todos los gastos en borrador de $dependenciaNombre. Los gastos que hayan quedado
     * registrados bajo una dependencia tipo "Dumi" (una etiqueta interna sin usuarios propios,
     * ej. "CONCURSO DOCENTE" dentro de la distribución de Docencia) se envían también, pero su
     * dependencia se sustituye por la de quien realmente remite ($dependenciaNombre), porque una
     * dependencia Dumi no tiene a quién notificarle ni cómo hacerle seguimiento en Peticiones.
     */
    public function enviarTodosBorrador(int $anioPresupuestalId, string $dependenciaNombre, int $rolDestinatarioId, array $dependenciasDumi = []): int
    {
        $nombres = array_values(array_unique(array_merge([$dependenciaNombre], $dependenciasDumi)));
        $marcadores = [];
        $parametros = [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'rol_destinatario_id' => $rolDestinatarioId,
            'dependencia_final' => $dependenciaNombre,
        ];

        foreach ($nombres as $indice => $nombre) {
            $marcador = 'dep' . $indice;
            $marcadores[] = ':' . $marcador;
            $parametros[$marcador] = $nombre;
        }

        $consulta = $this->db->prepare(
            "UPDATE gastos
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id, dependencia = :dependencia_final
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND dependencia IN (" . implode(', ', $marcadores) . ")
                AND estado = 'borrador'
                AND (tipo_automatico IS NULL OR tipo_automatico != 'techo_hijo')"
        );
        $consulta->execute($parametros);

        return $consulta->rowCount();
    }


    public function obtenerTotalPorAnio(int $anioPresupuestalId): float
    {
        $consulta = $this->db->prepare(
            "SELECT COALESCE(SUM(valor_total), 0) FROM gastos
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND (tipo_automatico IS NULL OR tipo_automatico != 'techo_hijo')"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerTotalPorAnioYDependencia(int $anioPresupuestalId, string $dependenciaNombre): float
    {
        $consulta = $this->db->prepare(
            'SELECT COALESCE(SUM(valor_total), 0) FROM gastos
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND dependencia = :dependencia'
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia' => $dependenciaNombre,
        ]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerTotalesPorDependencia(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT dependencia, COALESCE(SUM(valor_total), 0) AS total FROM gastos
             WHERE anio_presupuestal_id = :anio_presupuestal_id
             GROUP BY dependencia'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        $totales = [];
        foreach ($consulta->fetchAll() as $fila) {
            $totales[$fila['dependencia']] = (float) $fila['total'];
        }

        return $totales;
    }

    /**
     * Igual que obtenerTotalesPorDependencia(), pero el monto de un techo asignado a una hija
     * (gasto automático tipo_automatico = 'techo_hijo') solo se cuenta como "ejecutado" mientras
     * esa hija ya tenga al menos un gasto real registrado. Un techo asignado que la hija todavía
     * no ha empezado a usar no debe descontarse del disponible.
     */
    public function obtenerTotalesEjecutadosPorDependencia(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT g.dependencia, COALESCE(SUM(
                    CASE
                        WHEN g.tipo_automatico = 'techo_hijo' THEN
                            CASE WHEN EXISTS (
                                SELECT 1 FROM gastos g2
                                WHERE g2.anio_presupuestal_id = g.anio_presupuestal_id
                                    AND g2.dependencia = dh.nombre
                                    AND (g2.tipo_automatico IS NULL OR g2.tipo_automatico != 'techo_hijo')
                            ) THEN g.valor_total ELSE 0 END
                        ELSE g.valor_total
                    END
                ), 0) AS total
             FROM gastos g
             LEFT JOIN dependencias dh ON dh.id = g.dependencia_hija_id
             WHERE g.anio_presupuestal_id = :anio_presupuestal_id
             GROUP BY g.dependencia"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        $totales = [];
        foreach ($consulta->fetchAll() as $fila) {
            $totales[$fila['dependencia']] = (float) $fila['total'];
        }

        return $totales;
    }

    /**
     * Versión por dependencia única de obtenerTotalesEjecutadosPorDependencia(): un techo
     * asignado a una hija solo cuenta como ejecutado si esa hija ya tiene gastos reales.
     */
    public function obtenerTotalEjecutadoPorAnioYDependencia(int $anioPresupuestalId, string $dependenciaNombre): float
    {
        $consulta = $this->db->prepare(
            "SELECT COALESCE(SUM(
                    CASE
                        WHEN g.tipo_automatico = 'techo_hijo' THEN
                            CASE WHEN EXISTS (
                                SELECT 1 FROM gastos g2
                                WHERE g2.anio_presupuestal_id = g.anio_presupuestal_id
                                    AND g2.dependencia = dh.nombre
                                    AND (g2.tipo_automatico IS NULL OR g2.tipo_automatico != 'techo_hijo')
                            ) THEN g.valor_total ELSE 0 END
                        ELSE g.valor_total
                    END
                ), 0)
             FROM gastos g
             LEFT JOIN dependencias dh ON dh.id = g.dependencia_hija_id
             WHERE g.anio_presupuestal_id = :anio_presupuestal_id AND g.dependencia = :dependencia"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia' => $dependenciaNombre,
        ]);

        return (float) $consulta->fetchColumn();
    }

    public function obtenerDependenciasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT DISTINCT dependencia FROM gastos
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND (tipo_automatico IS NULL OR tipo_automatico != 'techo_hijo')"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_column($consulta->fetchAll(), 'dependencia');
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO gastos
                (sede_id, anio_presupuestal_id, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_id, insumo, cantidad, costo_unitario, valor_total, meses, usuario_id)
             VALUES
                (:sede_id, :anio_presupuestal_id, :dependencia, :linea_id, :motor_id, :proyecto_id, :objeto_proyecto_paa, :actividad, :rubro_id, :insumo, :cantidad, :costo_unitario, :valor_total, :meses, :usuario_id)'
        );

        return $consulta->execute([
            'sede_id' => $datos['sede_id'],
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia' => $datos['dependencia'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'objeto_proyecto_paa' => $datos['objeto_proyecto_paa'],
            'actividad' => $datos['actividad'],
            'rubro_id' => $datos['rubro_id'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['cantidad'] * $datos['costo_unitario'],
            'meses' => $datos['meses'],
            'usuario_id' => $datos['usuario_id'] ?? null,
        ]);
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM gastos WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE gastos SET
                sede_id = :sede_id, anio_presupuestal_id = :anio_presupuestal_id, dependencia = :dependencia,
                linea_id = :linea_id, motor_id = :motor_id, proyecto_id = :proyecto_id,
                objeto_proyecto_paa = :objeto_proyecto_paa, actividad = :actividad, rubro_id = :rubro_id,
                insumo = :insumo, cantidad = :cantidad, costo_unitario = :costo_unitario,
                valor_total = :valor_total, meses = :meses
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'sede_id' => $datos['sede_id'],
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia' => $datos['dependencia'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'objeto_proyecto_paa' => $datos['objeto_proyecto_paa'],
            'actividad' => $datos['actividad'],
            'rubro_id' => $datos['rubro_id'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['cantidad'] * $datos['costo_unitario'],
            'meses' => $datos['meses'],
        ]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare("DELETE FROM gastos WHERE id = :id AND (tipo_automatico IS NULL OR tipo_automatico != 'techo_hijo')");

        return $consulta->execute(['id' => $id]);
    }

    public function obtenerAutomaticoPorHija(int $anioPresupuestalId, int $dependenciaHijaId): ?array
    {
        $consulta = $this->db->prepare(
            "SELECT * FROM gastos
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND dependencia_hija_id = :dependencia_hija_id
                AND tipo_automatico = 'techo_hijo'
             LIMIT 1"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia_hija_id' => $dependenciaHijaId,
        ]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crearAutomaticoTechoHija(array $datos): bool
    {
        $consulta = $this->db->prepare(
            "INSERT INTO gastos
                (sede_id, anio_presupuestal_id, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_texto, insumo, cantidad, costo_unitario, valor_total, meses, tipo_automatico, dependencia_hija_id)
             VALUES
                (:sede_id, :anio_presupuestal_id, :dependencia, :linea_id, :motor_id, :proyecto_id, :objeto_proyecto_paa, :actividad, :rubro_texto, :insumo, :cantidad, :costo_unitario, :valor_total, :meses, 'techo_hijo', :dependencia_hija_id)"
        );

        return $consulta->execute([
            'sede_id' => $datos['sede_id'],
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia' => $datos['dependencia'],
            'linea_id' => $datos['linea_id'],
            'motor_id' => $datos['motor_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'objeto_proyecto_paa' => $datos['objeto_proyecto_paa'],
            'actividad' => $datos['actividad'],
            'rubro_texto' => $datos['rubro_texto'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['valor_total'],
            'meses' => $datos['meses'],
            'dependencia_hija_id' => $datos['dependencia_hija_id'],
        ]);
    }

    public function actualizarAutomaticoTechoHija(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE gastos
             SET dependencia = :dependencia, actividad = :actividad, insumo = :insumo,
                 costo_unitario = :costo_unitario, valor_total = :valor_total
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'dependencia' => $datos['dependencia'],
            'actividad' => $datos['actividad'],
            'insumo' => $datos['insumo'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['valor_total'],
        ]);
    }

    public function eliminarAutomaticoPorHija(int $anioPresupuestalId, int $dependenciaHijaId): bool
    {
        $consulta = $this->db->prepare(
            "DELETE FROM gastos
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND dependencia_hija_id = :dependencia_hija_id
                AND tipo_automatico = 'techo_hijo'"
        );

        return $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia_hija_id' => $dependenciaHijaId,
        ]);
    }
}
