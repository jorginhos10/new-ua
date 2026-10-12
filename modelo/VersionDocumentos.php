<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Versiones congeladas de "Documentos" (pestaña de ?ruta=analisis, antes "Presentación") —
 * independiente de VersionArbol/VersionTechos/PresupuestoInstitucional/Snapshot, mismo molde
 * (tabla propia + `activa` + crear/eliminar/marcarActiva/obtenerActiva) sin compartir código con
 * ellas. Mucho más simple que VersionTechos: una sola tabla (analisis_presentacion), sin cálculos
 * — una versión es, literalmente, la lista de enlaces tal como estaba al crearla.
 */
class VersionDocumentos
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT v.id, v.nombre, v.activa, v.creado_en, u.nombre AS creado_por_nombre
             FROM documentos_versiones v
             LEFT JOIN usuarios u ON u.id = v.creado_por
             ORDER BY v.creado_en DESC'
        );

        return $consulta->fetchAll();
    }

    public function obtenerActiva(): ?array
    {
        $consulta = $this->db->query(
            'SELECT id, nombre, activa, creado_en, creado_por
             FROM documentos_versiones
             ORDER BY activa DESC, creado_en DESC, id DESC
             LIMIT 1'
        );
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, activa, creado_en, creado_por FROM documentos_versiones WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function marcarActiva(int $id): void
    {
        $consultaVerificar = $this->db->prepare('SELECT id FROM documentos_versiones WHERE id = :id');
        $consultaVerificar->execute(['id' => $id]);
        if ($consultaVerificar->fetch() === false) {
            return;
        }

        $this->db->beginTransaction();

        try {
            $this->db->exec('UPDATE documentos_versiones SET activa = 0');
            $this->db->prepare('UPDATE documentos_versiones SET activa = 1 WHERE id = :id')->execute(['id' => $id]);
            $this->db->commit();
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    public function crear(string $nombre, int $usuarioId): int
    {
        $filas = $this->db->query('SELECT * FROM analisis_presentacion ORDER BY orden, id')->fetchAll();

        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare('INSERT INTO documentos_versiones (nombre, creado_por) VALUES (:nombre, :creado_por)');
            $consulta->execute(['nombre' => $nombre, 'creado_por' => $usuarioId]);
            $versionId = (int) $this->db->lastInsertId();

            $this->db->prepare('INSERT INTO documentos_versiones_datos (version_id, datos) VALUES (:version_id, :datos)')
                ->execute(['version_id' => $versionId, 'datos' => json_encode($filas, JSON_UNESCAPED_UNICODE)]);

            $this->db->commit();

            return $versionId;
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    public function eliminar(int $id): bool
    {
        $fila = $this->obtenerPorId($id);
        if ($fila === null) {
            return false;
        }

        $consulta = $this->db->prepare('DELETE FROM documentos_versiones WHERE id = :id');
        $resultado = $consulta->execute(['id' => $id]);

        if ($resultado && !empty($fila['activa'])) {
            $siguiente = $this->obtenerActiva();
            if ($siguiente !== null) {
                $this->marcarActiva((int) $siguiente['id']);
            }
        }

        return $resultado;
    }

    /** Filas de analisis_presentacion dentro de una versión guardada, mismo shape que obtenerTodos() en vivo. */
    public function obtenerDocumentosDeVersion(int $versionId): array
    {
        $consulta = $this->db->prepare('SELECT datos FROM documentos_versiones_datos WHERE version_id = :version_id');
        $consulta->execute(['version_id' => $versionId]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return [];
        }

        return json_decode($fila['datos'], true) ?? [];
    }
}
