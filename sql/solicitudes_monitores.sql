-- Modulo "Solicitudes" (2.2), item 2: Monitores - solo bienestar y sus dependencias.
-- Registro de monitores solicitados por dependencia, tipo y semestre.
USE new_ua;

CREATE TABLE IF NOT EXISTS solicitudes_monitores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    tipo ENUM('solidarios', 'academicos', 'deportivos', 'culturales', 'administrativos') NOT NULL,
    monitores_semestre1 INT NOT NULL DEFAULT 0,
    monitores_semestre2 INT NOT NULL DEFAULT 0,
    estado ENUM('borrador', 'enviada') NOT NULL DEFAULT 'borrador',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_monitor_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
