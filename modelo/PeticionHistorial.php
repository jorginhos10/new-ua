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

    public function registrar(string $origen, int $origenId, string $accion, string $detalle, ?int $loteId = null): void
    {
        $consulta = $this->db->prepare(
            'INSERT INTO peticiones_historial (lote_id, origen, origen_id, usuario_id, accion, detalle)
             VALUES (:lote_id, :origen, :origen_id, :usuario_id, :accion, :detalle)'
        );
        $consulta->execute([
            'lote_id' => $loteId,
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

    /**
     * Un "lote" es el resumen de una sola acción masiva (aprobar/archivar/consolidar/duplicar/
     * enviar/restaurar N ítems a la vez de un mismo tipo) — evita que esa acción quede como N filas
     * de peticiones_historial casi idénticas y sin relación entre sí. Devuelve el id del lote creado,
     * para pasarlo a registrar() en cada fila de detalle de esa misma acción.
     */
    public function registrarLote(string $tipo, string $accion, string $detalle, int $cantidadItems): int
    {
        $consulta = $this->db->prepare(
            'INSERT INTO peticiones_historial_lotes (tipo, accion, detalle, cantidad_items, usuario_id)
             VALUES (:tipo, :accion, :detalle, :cantidad_items, :usuario_id)'
        );
        $consulta->execute([
            'tipo' => $tipo,
            'accion' => $accion,
            'detalle' => $detalle,
            'cantidad_items' => $cantidadItems,
            'usuario_id' => !empty($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function obtenerLotesPorTipo(string $tipo): array
    {
        $consulta = $this->db->prepare(
            'SELECT l.*, u.nombre AS usuario_nombre
             FROM peticiones_historial_lotes l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             WHERE l.tipo = :tipo
             ORDER BY l.creado_en DESC, l.id DESC'
        );
        $consulta->execute(['tipo' => $tipo]);

        return $consulta->fetchAll();
    }

    public function obtenerPorLote(int $loteId): array
    {
        $consulta = $this->db->prepare(
            'SELECT h.*, u.nombre AS usuario_nombre
             FROM peticiones_historial h
             LEFT JOIN usuarios u ON u.id = h.usuario_id
             WHERE h.lote_id = :lote_id
             ORDER BY h.creado_en ASC, h.id ASC'
        );
        $consulta->execute(['lote_id' => $loteId]);

        return $consulta->fetchAll();
    }
}
