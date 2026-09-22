-- Amplía descripcion (VARCHAR(500) se quedaba corto con los textos institucionales reales de
-- líneas/sublíneas de inversión, ej. L6 y SLI303/SLI601 superan los 500 caracteres).
USE new_ua;

ALTER TABLE lineas_inversion
    MODIFY COLUMN descripcion VARCHAR(1000) NOT NULL;

ALTER TABLE sublineas_inversion
    MODIFY COLUMN descripcion VARCHAR(1000) NOT NULL;
