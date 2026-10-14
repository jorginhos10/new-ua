-- URL de imagen opcional para la miniatura de cada tarjeta de documento (pestaña "Documentos" de
-- Análisis): si el admin la pone, se usa esa imagen tal cual (hecho en el frontend, con loading
-- lazy/decoding async y recorte por CSS a las dimensiones de la miniatura — no hay redimensión ni
-- descarga en el servidor); si no, sigue el ícono por tipo de archivo. Nunca aplica a la fila de
-- Actas (ver AnalisisPresentacion::URL_ACTAS).
-- EJECUTAR UNA SOLA VEZ.

ALTER TABLE analisis_presentacion
    ADD COLUMN miniatura_url VARCHAR(2000) NULL AFTER url;
