-- Enlaces de la pestaña "Presentación" de ?ruta=analisis: lista de documentos de OneDrive/SharePoint
-- (presentación, Word, Excel o PDF) que el superadmin administra, con nombre visible y orden propio.
-- Reemplaza la versión anterior de esta tabla (una sola fila, solo un enlace sin nombre).
-- EJECUTAR UNA SOLA VEZ: el DROP borra cualquier enlace ya guardado con la versión anterior.

DROP TABLE IF EXISTS analisis_presentacion;

CREATE TABLE analisis_presentacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    url VARCHAR(2000) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    actualizado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_analisis_presentacion_orden (orden),
    CONSTRAINT fk_analisis_presentacion_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
