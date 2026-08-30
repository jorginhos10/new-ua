-- Modulo "Solicitudes" (2.2), item 4: Peticion (Otra) - remitida a cualquier dependencia.
USE new_ua;

CREATE TABLE IF NOT EXISTS solicitudes_peticiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    dependencia_destino VARCHAR(150) NOT NULL,
    semestre1 INT NOT NULL DEFAULT 0,
    valor_s1 DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    semestre2 INT NOT NULL DEFAULT 0,
    valor_s2 DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    estado ENUM('borrador', 'enviada') NOT NULL DEFAULT 'borrador',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_peticion_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
