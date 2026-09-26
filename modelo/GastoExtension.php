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
            "SELECT COALESCE(SUM(valor_total), 0) FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id
                AND NOT EXISTS (
                    SELECT 1 FROM peticiones_archivadas pa
                    WHERE pa.origen = 'gasto_extension' AND pa.origen_id = gastos_extension.id AND pa.accion = 'expediente'
                )"
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
            "SELECT COALESCE(SUM(valor_total), 0) FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id AND categoria = :categoria
                AND NOT EXISTS (
                    SELECT 1 FROM peticiones_archivadas pa
                    WHERE pa.origen = 'gasto_extension' AND pa.origen_id = gastos_extension.id AND pa.accion = 'expediente'
                )"
        );
        $consulta->execute([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
            'categoria' => $categoria,
        ]);

        return (float) $consulta->fetchColumn();
    }

    /**
     * Igual que obtenerTotalPorAnioYAutogestion(), pero acotado a un conjunto de dependencias —
     * evita sumar egresos de dependencias que la persona que consulta no debería ver.
     */
    public function obtenerTotalPorAnioYAutogestionYDependencias(int $anioPresupuestalId, int $autogestionId, array $dependencias): float
    {
        if (empty($dependencias)) {
            return 0.0;
        }

        $parametros = ['anio_presupuestal_id' => $anioPresupuestalId, 'autogestion_id' => $autogestionId];
        $marcadores = [];
        foreach (array_values($dependencias) as $indice => $dependencia) {
            $clave = 'dep' . $indice;
            $marcadores[] = ':' . $clave;
            $parametros[$clave] = $dependencia;
        }

        $consulta = $this->db->prepare(
            "SELECT COALESCE(SUM(valor_total), 0) FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id
                AND dependencia IN (" . implode(', ', $marcadores) . ")
                AND NOT EXISTS (
                    SELECT 1 FROM peticiones_archivadas pa
                    WHERE pa.origen = 'gasto_extension' AND pa.origen_id = gastos_extension.id AND pa.accion = 'expediente'
                )"
        );
        $consulta->execute($parametros);

        return (float) $consulta->fetchColumn();
    }

    /**
     * Igual que obtenerTotalPorAnioAutogestionYCategoria(), pero acotado a un conjunto de
     * dependencias.
     */
    public function obtenerTotalPorAnioAutogestionYCategoriaYDependencias(int $anioPresupuestalId, int $autogestionId, string $categoria, array $dependencias): float
    {
        if (empty($dependencias)) {
            return 0.0;
        }

        $parametros = ['anio_presupuestal_id' => $anioPresupuestalId, 'autogestion_id' => $autogestionId, 'categoria' => $categoria];
        $marcadores = [];
        foreach (array_values($dependencias) as $indice => $dependencia) {
            $clave = 'dep' . $indice;
            $marcadores[] = ':' . $clave;
            $parametros[$clave] = $dependencia;
        }

        $consulta = $this->db->prepare(
            "SELECT COALESCE(SUM(valor_total), 0) FROM gastos_extension
             WHERE anio_presupuestal_id = :anio_presupuestal_id AND autogestion_id = :autogestion_id AND categoria = :categoria
                AND dependencia IN (" . implode(', ', $marcadores) . ")
                AND NOT EXISTS (
                    SELECT 1 FROM peticiones_archivadas pa
                    WHERE pa.origen = 'gasto_extension' AND pa.origen_id = gastos_extension.id AND pa.accion = 'expediente'
                )"
        );
        $consulta->execute($parametros);

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

    public function enviarTodosBorrador(int $anioPresupuestalId, int $autogestionId, string $dependenciaDestinoNombre, int $rolDestinatarioId, array $dependenciasOrigen, ?int $usuarioDestinatarioId = null): int
    {
        if (empty($dependenciasOrigen)) {
            return 0;
        }

        $parametros = [
            'anio_presupuestal_id' => $anioPresupuestalId,
            'autogestion_id' => $autogestionId,
            'dependencia' => $dependenciaDestinoNombre,
            'rol_destinatario_id' => $rolDestinatarioId,
            'usuario_destinatario_id' => $usuarioDestinatarioId,
        ];
        $marcadores = [];
        foreach (array_values($dependenciasOrigen) as $indice => $dependenciaOrigen) {
            $clave = 'depOrigen' . $indice;
            $marcadores[] = ':' . $clave;
            $parametros[$clave] = $dependenciaOrigen;
        }

        // El IN de dependencia acota a solo las dependencias que el remitente puede ver — sin
        // esto, "Enviar todo" marcaba como enviados los borradores de CUALQUIER dependencia que
        // compartiera año + ítem de Autogestión, no solo los del usuario que envía.
        $consulta = $this->db->prepare(
            "UPDATE gastos_extension
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id, usuario_destinatario_id = :usuario_destinatario_id, dependencia_destino = :dependencia
             WHERE anio_presupuestal_id = :anio_presupuestal_id
                AND autogestion_id = :autogestion_id
                AND estado = 'borrador'
                AND dependencia IN (" . implode(', ', $marcadores) . ')'
        );
        $consulta->execute($parametros);

        return $consulta->rowCount();
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO gastos_extension
                (sede_id, anio_presupuestal_id, categoria, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_id, autogestion_id, insumo, cantidad, costo_unitario, valor_total, meses, usuario_id, capitulo_control)
             VALUES
                (:sede_id, :anio_presupuestal_id, :categoria, :dependencia, :linea_id, :motor_id, :proyecto_id, :objeto_proyecto_paa, :actividad, :rubro_id, :autogestion_id, :insumo, :cantidad, :costo_unitario, :valor_total, :meses, :usuario_id, :capitulo_control)'
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
            'usuario_id' => $datos['usuario_id'] ?? null,
            'capitulo_control' => $datos['capitulo_control'] ?? null,
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

    /**
     * Convierte una fila automática en un egreso manual normal (mismo patrón que ya existía en
     * GastoPostgrado::convertirContribucionAManual()/convertirExcedentesAManual(), generalizado a
     * cualquier tipo_automatico): pone tipo_automatico = NULL, así que de ahí en adelante se edita/
     * borra como cualquier fila manual y ya NO se toca si el ingreso_id que la originó cambia
     * después (regenerarAutomaticosDeIngreso() solo reemplaza filas con tipo_automatico IS NOT NULL).
     * Solo debe llamarse desde el controlador tras confirmar que quien edita es el superadmin raíz o
     * tiene permiso delegado sobre ese tipo (ver AutogestionAutomaticoPermiso).
     */
    public function convertirAutomaticoAManual(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE gastos_extension SET
                sede_id = :sede_id, anio_presupuestal_id = :anio_presupuestal_id, categoria = :categoria,
                dependencia = :dependencia, linea_id = :linea_id, motor_id = :motor_id, proyecto_id = :proyecto_id,
                objeto_proyecto_paa = :objeto_proyecto_paa, actividad = :actividad, rubro_id = :rubro_id,
                autogestion_id = :autogestion_id, insumo = :insumo, cantidad = :cantidad,
                costo_unitario = :costo_unitario, valor_total = :valor_total, meses = :meses, tipo_automatico = NULL
             WHERE id = :id AND tipo_automatico IS NOT NULL"
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

    /**
     * Igual que eliminar(), pero sin el guard "AND tipo_automatico IS NULL" — mismo requisito de
     * llamada que convertirAutomaticoAManual().
     */
    public function eliminarForzado(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM gastos_extension WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }


    /**
     * Crea una fila de gasto automático (ej. "Excedentes nivel central") siempre ligada a UN
     * ingreso concreto vía ingreso_id, y con el mismo usuario_id de ese ingreso — nunca se calcula
     * como agregado de "todos los ingresos de la dependencia" (eso mezclaba, bajo una sola fila
     * por año+ítem, los ingresos de distintos usuarios/dependencias que comparten el mismo ítem de
     * Autogestión: ver eliminarAutomaticosPorIngreso()).
     */
    public function crearAutomatico(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO gastos_extension
                (sede_id, anio_presupuestal_id, categoria, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_id, rubro_texto, autogestion_id, ingreso_id, tipo_automatico, usuario_id, insumo, cantidad, costo_unitario, valor_total, meses)
             VALUES
                (:sede_id, :anio_presupuestal_id, :categoria, :dependencia, :linea_id, :motor_id, :proyecto_id, :objeto_proyecto_paa, :actividad, :rubro_id, :rubro_texto, :autogestion_id, :ingreso_id, :tipo_automatico, :usuario_id, :insumo, :cantidad, :costo_unitario, :valor_total, :meses)'
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
            'rubro_id' => $datos['rubro_id'] ?? null,
            'rubro_texto' => $datos['rubro_texto'] ?? null,
            'autogestion_id' => $datos['autogestion_id'],
            'ingreso_id' => $datos['ingreso_id'],
            'tipo_automatico' => $datos['tipo_automatico'],
            'usuario_id' => $datos['usuario_id'],
            'insumo' => $datos['insumo'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $datos['costo_unitario'],
            'valor_total' => $datos['valor_total'],
            'meses' => $datos['meses'],
        ]);
    }

    /**
     * Borra TODAS las filas automáticas ligadas a un ingreso puntual (por ingreso_id) — se llama
     * antes de recrearlas cada vez que ese ingreso se crea, edita o elimina, así que no queda
     * ninguna fila "huérfana" ni se toca la de otro ingreso (antes se buscaba/actualizaba una
     * única fila compartida por año+ítem sin importar el ingreso/dependencia/usuario real, lo que
     * hacía que el ingreso de un usuario pisara o heredara el excedente calculado de otro).
     */
    public function eliminarAutomaticosPorIngreso(int $ingresoId): bool
    {
        $consulta = $this->db->prepare('DELETE FROM gastos_extension WHERE ingreso_id = :ingreso_id AND tipo_automatico IS NOT NULL');

        return $consulta->execute(['ingreso_id' => $ingresoId]);
    }
}
