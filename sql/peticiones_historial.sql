-- Log de auditoría append-only para el botón "Historial" de Peticiones. Sin FK real (origen_id
-- apunta a 13 tablas distintas según `origen`), mismo patrón que `peticiones_archivadas`.
CREATE TABLE peticiones_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(30) NOT NULL,
    origen_id INT NOT NULL,
    usuario_id INT NULL,
    accion VARCHAR(30) NOT NULL,
    detalle VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_origen (origen, origen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
