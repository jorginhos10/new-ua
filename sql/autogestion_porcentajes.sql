-- Porcentajes (Costos / Inversiones / Excedentes) por cada módulo de Autogestión
USE new_ua;

CREATE TABLE IF NOT EXISTS autogestion_porcentajes (
    modulo VARCHAR(20) PRIMARY KEY,
    costos DECIMAL(5, 2) NOT NULL DEFAULT 0,
    inversiones DECIMAL(5, 2) NOT NULL DEFAULT 0,
    excedentes DECIMAL(5, 2) NOT NULL DEFAULT 0,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO autogestion_porcentajes (modulo) VALUES
('extension'), ('postgrado'), ('unisalud'), ('sin-excedentes');
