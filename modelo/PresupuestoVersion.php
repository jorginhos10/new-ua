<?php

require_once __DIR__ . '/../config/conexion.php';

class PresupuestoVersion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Crea una versión (commit) con la lista de cambios de dependencias que tuvo un guardado.
     * $cambios: array de ['dependencia_id' => int, 'minimo_anterior' => ?float, 'minimo_nuevo' => ?float,
     *                      'techo_anterior' => ?float, 'techo_nuevo' => ?float]
     */
    public function crearVersion(int $anioId, int $usuarioId, array $cambios): ?int
    {
        if (empty($cambios)) {
            return null;
        }

        $this->db->beginTransaction();

        $insertarVersion = $this->db->prepare(
            'INSERT INTO presupuesto_version (anio_presupuestal_id, usuario_id) VALUES (:anio_id, :usuario_id)'
        );
        $insertarVersion->execute(['anio_id' => $anioId, 'usuario_id' => $usuarioId]);
        $versionId = (int) $this->db->lastInsertId();

        $insertarCambio = $this->db->prepare(
            'INSERT INTO presupuesto_version_cambio
                (version_id, dependencia_id, minimo_anterior, minimo_nuevo, techo_anterior, techo_nuevo)
             VALUES (:version_id, :dependencia_id, :minimo_anterior, :minimo_nuevo, :techo_anterior, :techo_nuevo)'
        );

        foreach ($cambios as $cambio) {
            $insertarCambio->execute([
                'version_id' => $versionId,
                'dependencia_id' => $cambio['dependencia_id'],
                'minimo_anterior' => $cambio['minimo_anterior'],
                'minimo_nuevo' => $cambio['minimo_nuevo'],
                'techo_anterior' => $cambio['techo_anterior'],
                'techo_nuevo' => $cambio['techo_nuevo'],
            ]);
        }

        $this->db->commit();

        return $versionId;
    }

    public function obtenerVersionesPorAnio(int $anioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT v.id, v.creado_en, u.nombre AS usuario_nombre,
                    (SELECT COUNT(*) FROM presupuesto_version_cambio c WHERE c.version_id = v.id) AS total_cambios
             FROM presupuesto_version v
             JOIN usuarios u ON u.id = v.usuario_id
             WHERE v.anio_presupuestal_id = :anio_id
             ORDER BY v.creado_en DESC, v.id DESC'
        );
        $consulta->execute(['anio_id' => $anioId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $versionId): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT v.id, v.anio_presupuestal_id, v.creado_en, u.nombre AS usuario_nombre
             FROM presupuesto_version v
             JOIN usuarios u ON u.id = v.usuario_id
             WHERE v.id = :id'
        );
        $consulta->execute(['id' => $versionId]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerCambiosPorVersion(int $versionId): array
    {
        $consulta = $this->db->prepare(
            'SELECT c.dependencia_id, c.minimo_anterior, c.minimo_nuevo, c.techo_anterior, c.techo_nuevo,
                    d.nombre AS dependencia_nombre
             FROM presupuesto_version_cambio c
             JOIN dependencias d ON d.id = c.dependencia_id
             WHERE c.version_id = :version_id
             ORDER BY d.nombre'
        );
        $consulta->execute(['version_id' => $versionId]);

        return $consulta->fetchAll();
    }
}
