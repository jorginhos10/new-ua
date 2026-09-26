<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Plantilla de campos (sede/proyecto/rubro/actividad/insumo/meses) a usar al generar la fila
 * automática de Excedentes/Contribución a posgrado de un ítem+dependencia — reemplaza las
 * constantes fijas `AUTOMATICO_*` de cada controlador cuando existe una definición aplicable.
 * `dependencia = NULL` es un default global (solo el superadmin puede crear uno); un usuario normal
 * que deja "Dependencia" en blanco al definir queda fijado a la suya (resuelto por el controlador,
 * no aquí).
 */
class AutogestionAutomaticoDefinicion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Busca, en orden: 1) definición exacta para esa dependencia, 2) definición default (sin
     * dependencia, solo la puede crear el SA), 3) null (el controlador cae a las constantes fijas).
     */
    public function buscarConFallback(string $modulo, ?int $autogestionId, string $tipo, string $dependencia): ?array
    {
        $condicionItem = $autogestionId !== null ? 'autogestion_id = :autogestion_id' : 'autogestion_id IS NULL';

        $consulta = $this->db->prepare(
            "SELECT * FROM autogestion_automatico_definiciones
             WHERE modulo = :modulo AND $condicionItem AND tipo = :tipo AND dependencia = :dependencia
             LIMIT 1"
        );
        $parametros = ['modulo' => $modulo, 'tipo' => $tipo, 'dependencia' => $dependencia];
        if ($autogestionId !== null) {
            $parametros['autogestion_id'] = $autogestionId;
        }
        $consulta->execute($parametros);
        $fila = $consulta->fetch();

        if ($fila !== false) {
            return $fila;
        }

        $consultaDefault = $this->db->prepare(
            "SELECT * FROM autogestion_automatico_definiciones
             WHERE modulo = :modulo AND $condicionItem AND tipo = :tipo AND dependencia IS NULL
             LIMIT 1"
        );
        $parametrosDefault = ['modulo' => $modulo, 'tipo' => $tipo];
        if ($autogestionId !== null) {
            $parametrosDefault['autogestion_id'] = $autogestionId;
        }
        $consultaDefault->execute($parametrosDefault);
        $filaDefault = $consultaDefault->fetch();

        return $filaDefault !== false ? $filaDefault : null;
    }

    /**
     * @return array<int, array{id:int,dependencia:?string,tipo:string,sede_nombre:string,rubro_codigo:string,rubro_descripcion:string,proyecto_nombre:string,actividad:string,insumo:string,meses:string}>
     */
    public function listarPorItem(string $modulo, ?int $autogestionId, string $tipo): array
    {
        $condicionItem = $autogestionId !== null ? 'd.autogestion_id = :autogestion_id' : 'd.autogestion_id IS NULL';

        $consulta = $this->db->prepare(
            "SELECT d.*, s.nombre AS sede_nombre, p.nombre AS proyecto_nombre, r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion
             FROM autogestion_automatico_definiciones d
             LEFT JOIN sedes s ON s.id = d.sede_id
             LEFT JOIN proyectos p ON p.id = d.proyecto_id
             LEFT JOIN rubros r ON r.id = d.rubro_id
             WHERE d.modulo = :modulo AND $condicionItem AND d.tipo = :tipo
             ORDER BY d.dependencia IS NULL, d.dependencia"
        );
        $parametros = ['modulo' => $modulo, 'tipo' => $tipo];
        if ($autogestionId !== null) {
            $parametros['autogestion_id'] = $autogestionId;
        }
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM autogestion_automatico_definiciones WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * @param array{modulo:string,autogestion_id:?int,tipo:string,dependencia:?string,sede_id:int,proyecto_id:int,rubro_id:int,actividad:string,insumo:string,meses:string,creado_por:int} $datos
     */
    public function crear(array $datos): int
    {
        $consulta = $this->db->prepare(
            'INSERT INTO autogestion_automatico_definiciones
                (modulo, autogestion_id, tipo, dependencia, sede_id, proyecto_id, rubro_id, actividad, insumo, meses, creado_por)
             VALUES
                (:modulo, :autogestion_id, :tipo, :dependencia, :sede_id, :proyecto_id, :rubro_id, :actividad, :insumo, :meses, :creado_por)'
        );
        $consulta->execute([
            'modulo' => $datos['modulo'],
            'autogestion_id' => $datos['autogestion_id'],
            'tipo' => $datos['tipo'],
            'dependencia' => $datos['dependencia'],
            'sede_id' => $datos['sede_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'rubro_id' => $datos['rubro_id'],
            'actividad' => $datos['actividad'],
            'insumo' => $datos['insumo'],
            'meses' => $datos['meses'],
            'creado_por' => $datos['creado_por'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE autogestion_automatico_definiciones SET
                dependencia = :dependencia, sede_id = :sede_id, proyecto_id = :proyecto_id, rubro_id = :rubro_id,
                actividad = :actividad, insumo = :insumo, meses = :meses
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'dependencia' => $datos['dependencia'],
            'sede_id' => $datos['sede_id'],
            'proyecto_id' => $datos['proyecto_id'],
            'rubro_id' => $datos['rubro_id'],
            'actividad' => $datos['actividad'],
            'insumo' => $datos['insumo'],
            'meses' => $datos['meses'],
        ]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM autogestion_automatico_definiciones WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Existe ya una definición para exactamente ese (modulo, autogestion_id, tipo, dependencia) —
     * usado para decidir crear() vs. avisar "ya existe, edítala" en vez de duplicar filas.
     */
    public function buscarExacta(string $modulo, ?int $autogestionId, string $tipo, ?string $dependencia): ?array
    {
        $condicionItem = $autogestionId !== null ? 'autogestion_id = :autogestion_id' : 'autogestion_id IS NULL';
        $condicionDependencia = $dependencia !== null ? 'dependencia = :dependencia' : 'dependencia IS NULL';

        $consulta = $this->db->prepare(
            "SELECT * FROM autogestion_automatico_definiciones
             WHERE modulo = :modulo AND $condicionItem AND tipo = :tipo AND $condicionDependencia
             LIMIT 1"
        );
        $parametros = ['modulo' => $modulo, 'tipo' => $tipo];
        if ($autogestionId !== null) {
            $parametros['autogestion_id'] = $autogestionId;
        }
        if ($dependencia !== null) {
            $parametros['dependencia'] = $dependencia;
        }
        $consulta->execute($parametros);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
