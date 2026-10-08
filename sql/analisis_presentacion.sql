-- Presentación (.ppsx de OneDrive) de la pestaña "Presentación" de ?ruta=analisis.
-- Una sola fila (id = 1) con el enlace que pega el superadmin. Se puede correr más de una vez.

CREATE TABLE IF NOT EXISTS analisis_presentacion (
    id INT NOT NULL PRIMARY KEY DEFAULT 1,
    url VARCHAR(2000) NOT NULL,
    actualizado_por INT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
