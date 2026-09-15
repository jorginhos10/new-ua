<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Un "snapshot" es una copia completa del estado actual de la base de datos (todas las tablas,
 * fila por fila, como JSON) tomada en un momento dado por un superadmin. Sirve como fuente de
 * datos congelada — a futuro, una herramienta de visualización de datos podrá elegir un snapshot
 * y consultar solo esos datos, sin verse afectada por cambios posteriores en las tablas reales.
 */
class Snapshot
{
    private PDO $db;

    /** Tablas del propio mecanismo de snapshots: nunca se incluyen dentro de un snapshot. */
    private const TABLAS_EXCLUIDAS = ['snapshots', 'snapshots_datos'];

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT s.id, s.nombre, s.creado_en, u.nombre AS creado_por_nombre,
                    (SELECT COUNT(*) FROM snapshots_datos sd WHERE sd.snapshot_id = s.id) AS total_tablas
             FROM snapshots s
             LEFT JOIN usuarios u ON u.id = s.creado_por
             ORDER BY s.creado_en DESC'
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, creado_en, creado_por FROM snapshots WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Copia todas las tablas de la base de datos (excepto las del propio mecanismo de snapshots)
     * dentro de una única transacción: si algo falla a mitad de camino, no queda un snapshot a medias.
     */
    public function crear(string $nombre, int $usuarioId): int
    {
        $tablas = $this->obtenerNombresTablas();

        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare('INSERT INTO snapshots (nombre, creado_por) VALUES (:nombre, :creado_por)');
            $consulta->execute(['nombre' => $nombre, 'creado_por' => $usuarioId]);
            $snapshotId = (int) $this->db->lastInsertId();

            $insertarDatos = $this->db->prepare(
                'INSERT INTO snapshots_datos (snapshot_id, tabla, datos) VALUES (:snapshot_id, :tabla, :datos)'
            );

            foreach ($tablas as $tabla) {
                $filas = $this->db->query('SELECT * FROM `' . $tabla . '`')->fetchAll();
                $insertarDatos->execute([
                    'snapshot_id' => $snapshotId,
                    'tabla' => $tabla,
                    'datos' => json_encode($filas, JSON_UNESCAPED_UNICODE),
                ]);
            }

            $this->db->commit();

            return $snapshotId;
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM snapshots WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Nombres de todas las tablas base de la propia base de datos (no vistas), excluyendo las del
     * mecanismo de snapshots. Se leen de INFORMATION_SCHEMA en vez de mantener una lista fija a
     * mano, para que un snapshot siempre incluya cualquier tabla nueva que se agregue después.
     */
    private function obtenerNombresTablas(): array
    {
        $consulta = $this->db->query(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
        );
        $tablas = array_column($consulta->fetchAll(), 'TABLE_NAME');

        return array_values(array_diff($tablas, self::TABLAS_EXCLUIDAS));
    }
}
