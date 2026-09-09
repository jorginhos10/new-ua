<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Log de auditoría append-only del botón "Historial" de Peticiones. Registra eventos sobre ítems
 * que ya llegaron a Peticiones (aprobar/archivar/editar/redireccionar/rechazar/duplicar/eliminar);
 * no registra el momento "Enviado" en los 7 módulos de origen.
 */
class PeticionHistorial
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function registrar(string $origen, int $origenId, string $accion, string $detalle): void
    {
        $consulta = $this->db->prepare(
            'INSERT INTO peticiones_historial (origen, origen_id, usuario_id, accion, detalle)
             VALUES (:origen, :origen_id, :usuario_id, :accion, :detalle)'
        );
        $consulta->execute([
            'origen' => $origen,
            'origen_id' => $origenId,
            'usuario_id' => !empty($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null,
            'accion' => $accion,
            'detalle' => $detalle,
        ]);
    }

    public function obtenerPorOrigenYId(string $origen, int $origenId): array
    {
        $consulta = $this->db->prepare(
            'SELECT h.*, u.nombre AS usuario_nombre
             FROM peticiones_historial h
             LEFT JOIN usuarios u ON u.id = h.usuario_id
             WHERE h.origen = :origen AND h.origen_id = :origen_id
             ORDER BY h.creado_en ASC, h.id ASC'
        );
        $consulta->execute(['origen' => $origen, 'origen_id' => $origenId]);

        return $consulta->fetchAll();
    }
}
