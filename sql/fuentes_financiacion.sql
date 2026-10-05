-- Catálogo de fuentes de financiación (Configuraciones > Listas). Reemplazará el texto libre de
-- necesidades_academicas.fuente_financiacion en las convocatorias de proyectos.
-- Carga inicial: las fuentes que ya usan los proyectos registrados (idempotente).

CREATE TABLE IF NOT EXISTS fuentes_financiacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fuentes_financiacion_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO fuentes_financiacion (nombre)
SELECT DISTINCT TRIM(fuente_financiacion)
FROM necesidades_academicas
WHERE TRIM(fuente_financiacion) <> '';
