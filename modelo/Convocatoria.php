<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/Dependencia.php';

/**
 * Convocatoria de Perfil de proyectos: periodo (reloj), audiencia (quién formula), fuentes habilitadas y
 * alcance por dependencias. Sin dependencias marcadas, la convocatoria no restringe por dependencia.
 */
class Convocatoria
{
    public const AUDIENCIAS = ['invitado', 'administrador', 'ambos'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query(
            'SELECT c.*, COUNT(n.id) AS cantidad_proyectos, COALESCE(SUM(n.valor), 0) AS valor_total
             FROM convocatorias c
             LEFT JOIN necesidades_academicas n ON n.convocatoria_id = c.id
             GROUP BY c.id
             ORDER BY c.vigencia DESC, c.fecha_inicio DESC, c.id DESC'
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM convocatorias WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(array $datos, int $usuarioId): int
    {
        $consulta = $this->db->prepare(
            'INSERT INTO convocatorias (nombre, vigencia, audiencia, fecha_inicio, fecha_cierre, tope_por_proyecto, activa, creado_por)
             VALUES (:nombre, :vigencia, :audiencia, :fecha_inicio, :fecha_cierre, :tope_por_proyecto, 1, :creado_por)'
        );
        $consulta->execute([
            'nombre' => $datos['nombre'],
            'vigencia' => $datos['vigencia'],
            'audiencia' => $datos['audiencia'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_cierre' => $datos['fecha_cierre'],
            'tope_por_proyecto' => $datos['tope_por_proyecto'],
            'creado_por' => $usuarioId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE convocatorias SET nombre = :nombre, vigencia = :vigencia, audiencia = :audiencia,
                fecha_inicio = :fecha_inicio, fecha_cierre = :fecha_cierre, tope_por_proyecto = :tope_por_proyecto
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'nombre' => $datos['nombre'],
            'vigencia' => $datos['vigencia'],
            'audiencia' => $datos['audiencia'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_cierre' => $datos['fecha_cierre'],
            'tope_por_proyecto' => $datos['tope_por_proyecto'],
        ]);
    }

    /** Cerrar o reabrir la convocatoria sin tocar sus fechas. */
    public function cambiarActiva(int $id): bool
    {
        $consulta = $this->db->prepare('UPDATE convocatorias SET activa = IF(activa = 1, 0, 1) WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /** Fuentes habilitadas de la convocatoria, solo las del catálogo activo. */
    public function fuentesHabilitadas(int $id): array
    {
        $consulta = $this->db->prepare(
            "SELECT f.id, f.nombre FROM convocatoria_fuentes cf
             JOIN fuentes_financiacion f ON f.id = cf.fuente_id
             WHERE cf.convocatoria_id = :id AND f.estado = 'activo'
             ORDER BY f.nombre"
        );
        $consulta->execute(['id' => $id]);

        return $consulta->fetchAll();
    }

    public function fuenteIdsHabilitadas(int $id): array
    {
        return array_map(static fn (array $f): int => (int) $f['id'], $this->fuentesHabilitadas($id));
    }

    public function guardarFuentes(int $id, array $fuenteIds): void
    {
        $this->db->prepare('DELETE FROM convocatoria_fuentes WHERE convocatoria_id = :id')->execute(['id' => $id]);

        $insertar = $this->db->prepare('INSERT IGNORE INTO convocatoria_fuentes (convocatoria_id, fuente_id) VALUES (:id, :fuente_id)');

        foreach (array_unique(array_map('intval', $fuenteIds)) as $fuenteId) {
            if ($fuenteId > 0) {
                $insertar->execute(['id' => $id, 'fuente_id' => $fuenteId]);
            }
        }
    }

    /** Dependencias marcadas, con el indicador de incluir sus descendientes. */
    public function dependenciasMarcadas(int $id): array
    {
        $consulta = $this->db->prepare(
            'SELECT cd.dependencia_id, cd.incluir_descendientes, d.nombre
             FROM convocatoria_dependencias cd
             JOIN dependencias d ON d.id = cd.dependencia_id
             WHERE cd.convocatoria_id = :id
             ORDER BY d.nombre'
        );
        $consulta->execute(['id' => $id]);

        return $consulta->fetchAll();
    }

    /** @param array<int, array{dependencia_id: int, incluir_descendientes: bool}> $filas */
    public function guardarDependencias(int $id, array $filas): void
    {
        $this->db->prepare('DELETE FROM convocatoria_dependencias WHERE convocatoria_id = :id')->execute(['id' => $id]);

        $insertar = $this->db->prepare(
            'INSERT INTO convocatoria_dependencias (convocatoria_id, dependencia_id, incluir_descendientes)
             VALUES (:id, :dependencia_id, :incluir)'
        );

        foreach ($filas as $fila) {
            if ((int) $fila['dependencia_id'] > 0) {
                $insertar->execute([
                    'id' => $id,
                    'dependencia_id' => (int) $fila['dependencia_id'],
                    'incluir' => !empty($fila['incluir_descendientes']) ? 1 : 0,
                ]);
            }
        }
    }

    /**
     * Ids de dependencias que pueden formular en la convocatoria (marcadas y, si se pidió, sus
     * descendientes). Null = sin restricción: la convocatoria no tiene dependencias marcadas.
     */
    public function alcanceDependenciaIds(int $id): ?array
    {
        $marcadas = $this->dependenciasMarcadas($id);

        if ($marcadas === []) {
            return null;
        }

        $dependencias = new Dependencia();
        $ids = [];

        foreach ($marcadas as $marca) {
            $ids[] = (int) $marca['dependencia_id'];

            if ((int) $marca['incluir_descendientes'] === 1) {
                foreach ($dependencias->obtenerDescendientesPlano((int) $marca['dependencia_id'], true) as $descendiente) {
                    $ids[] = (int) $descendiente['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function permiteDependencia(int $id, ?int $dependenciaId): bool
    {
        $alcance = $this->alcanceDependenciaIds($id);

        if ($alcance === null) {
            return true;
        }

        return $dependenciaId !== null && in_array($dependenciaId, $alcance, true);
    }

    /**
     * Convocatorias que este usuario puede ver: la audiencia debe incluir su rol (invitado o
     * administrador) y su dependencia debe estar en el alcance. Incluye las cerradas o inactivas, para
     * consultar lo ya formulado; formular solo se permite dentro de la ventana (ver dentroDeVentana()).
     */
    public function visiblesPara(string $rol, ?int $dependenciaId): array
    {
        $audiencias = match ($rol) {
            'invitado' => ['invitado', 'ambos'],
            'administrador' => ['administrador', 'ambos'],
            default => [],
        };

        if ($audiencias === []) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($audiencias), '?'));
        $consulta = $this->db->prepare(
            "SELECT * FROM convocatorias WHERE audiencia IN ($marcadores) ORDER BY vigencia DESC, fecha_inicio DESC, id DESC"
        );
        $consulta->execute($audiencias);

        return array_values(array_filter(
            $consulta->fetchAll(),
            fn (array $c): bool => $this->permiteDependencia((int) $c['id'], $dependenciaId)
        ));
    }

    /** Consolidado de la convocatoria: cantidad y valor por dependencia y fuente. */
    public function consolidadoPorDependenciaYFuente(int $id): array
    {
        $consulta = $this->db->prepare(
            "SELECT COALESCE(d.nombre, n.dependencia) AS dependencia,
                    COALESCE(f.nombre, n.fuente_financiacion) AS fuente,
                    COUNT(*) AS cantidad,
                    SUM(n.valor) AS valor
             FROM necesidades_academicas n
             LEFT JOIN dependencias d ON d.id = n.dependencia_id
             LEFT JOIN fuentes_financiacion f ON f.id = n.fuente_financiacion_id
             WHERE n.convocatoria_id = :id
             GROUP BY dependencia, fuente
             ORDER BY dependencia, fuente"
        );
        $consulta->execute(['id' => $id]);

        return $consulta->fetchAll();
    }

    /** Dentro del periodo, con la fecha de hoy en Colombia (no la del servidor). */
    public static function dentroDeVentana(array $convocatoria): bool
    {
        if ((int) ($convocatoria['activa'] ?? 0) !== 1) {
            return false;
        }

        $hoy = (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d');

        return $hoy >= $convocatoria['fecha_inicio'] && $hoy <= $convocatoria['fecha_cierre'];
    }
}
