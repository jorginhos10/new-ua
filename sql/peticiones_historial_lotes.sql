-- Historial por lote: agrupa el registro de auditoría de una acción masiva (aprobar/archivar/
-- consolidar/duplicar/enviar/restaurar N ítems a la vez) en un solo evento, en vez de dejar N filas
-- casi idénticas en `peticiones_historial` sin ninguna relación entre sí. Ver plan aprobado
-- el-techo-no-deberia-kind-candle.
CREATE TABLE IF NOT EXISTS peticiones_historial_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL,
    accion VARCHAR(30) NOT NULL,
    detalle VARCHAR(255) NOT NULL,
    cantidad_items INT NOT NULL,
    usuario_id INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE peticiones_historial
    ADD COLUMN lote_id INT NULL AFTER id,
    ADD INDEX idx_lote (lote_id);
