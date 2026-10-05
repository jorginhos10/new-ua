-- Tope por proyecto de la convocatoria. NULL = valor libre (sin tope); un monto = el valor de cada
-- proyecto no puede superarlo.
-- EJECUTAR UNA SOLA VEZ, después de sql/convocatorias_proyectos.sql.

ALTER TABLE convocatorias ADD COLUMN tope_por_proyecto DECIMAL(15,2) NULL AFTER fecha_cierre;
