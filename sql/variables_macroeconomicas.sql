-- Catalogo de variables macroeconomicas de referencia (IPC, SMLV, UVT, tasa de cambio, etc.)
-- usadas por OPL para la planeacion presupuestal, con un valor por año presupuestal.
USE new_ua;

CREATE TABLE IF NOT EXISTS variables_macroeconomicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    anio_presupuestal_id INT NOT NULL,
    valor DECIMAL(15, 4) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_variable_macro_nombre_anio (nombre, anio_presupuestal_id),
    CONSTRAINT fk_variable_macro_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
