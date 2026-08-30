CREATE TABLE IF NOT EXISTS peticiones_archivadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(30) NOT NULL,
    origen_id INT NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    detalle VARCHAR(255) NOT NULL,
    cantidad VARCHAR(50) NULL,
    valor DECIMAL(14,2) NULL,
    ruta_ver VARCHAR(255) NOT NULL,
    archivado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_origen (origen, origen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
