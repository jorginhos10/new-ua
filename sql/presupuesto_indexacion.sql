-- Columna "Indexación" por línea de Programación presupuestal: el criterio con el que se proyecta
-- esa línea (ej. "IPC", "SMMLV", "ICES") — solo hojas, se diligencia desde la plantilla Excel y se
-- ve como un punto de color (con el texto como tooltip) junto al valor del año vigente.
-- EJECUTAR UNA SOLA VEZ.

ALTER TABLE presupuesto_institucional_lineas
    ADD COLUMN indexacion VARCHAR(50) NULL AFTER descripcion;
