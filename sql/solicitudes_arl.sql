-- Modulo "Solicitudes" (2.2), item 1: ARL - OPL, Vicedamin y sus dependencias.
-- Tabla de proyeccion anual de estudiantes/practicantes por facultad y nivel de riesgo (I-V).
USE new_ua;

CREATE TABLE IF NOT EXISTS solicitudes_arl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    facultad VARCHAR(150) NOT NULL,
    riesgo1_estudiantes INT NOT NULL DEFAULT 0,
    riesgo1_valor DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    riesgo2_estudiantes INT NOT NULL DEFAULT 0,
    riesgo2_valor DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    riesgo3_estudiantes INT NOT NULL DEFAULT 0,
    riesgo3_valor DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    riesgo4_estudiantes INT NOT NULL DEFAULT 0,
    riesgo4_valor DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    riesgo5_estudiantes INT NOT NULL DEFAULT 0,
    riesgo5_valor DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_arl_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
