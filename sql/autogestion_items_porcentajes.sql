-- El % de Costos/Inversiones/Excedentes de Extensión deja de ser global por módulo (tabla
-- autogestion_porcentajes) y pasa a configurarse por cada ítem de Autogestión, para que cada uno
-- reparta su propio ingreso de forma distinta. Se inicializan con el % que ya tenía el módulo
-- "extension", para no romper nada en curso: el administrador los ajusta ítem por ítem después.
USE new_ua;

ALTER TABLE autogestion_items
    ADD COLUMN costos DECIMAL(5, 2) NULL AFTER tope,
    ADD COLUMN inversiones DECIMAL(5, 2) NULL AFTER costos,
    ADD COLUMN excedentes DECIMAL(5, 2) NULL AFTER inversiones;

UPDATE autogestion_items ai
JOIN autogestion_porcentajes ap ON ap.modulo = ai.modulo
SET ai.costos = ap.costos, ai.inversiones = ap.inversiones, ai.excedentes = ap.excedentes
WHERE ai.modulo = 'extension';
