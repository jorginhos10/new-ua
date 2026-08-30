-- Los ítems de Autogestión pasan a ser propios de cada módulo
-- (Extensión, Postgrado, Unidad de Salud, Sin excedentes), no un catálogo global.
USE new_ua;

ALTER TABLE autogestion_items ADD COLUMN modulo VARCHAR(20) NOT NULL DEFAULT 'extension' AFTER nombre;
ALTER TABLE autogestion_items DROP INDEX nombre;
ALTER TABLE autogestion_items ADD UNIQUE KEY uq_autogestion_modulo_nombre (modulo, nombre);
