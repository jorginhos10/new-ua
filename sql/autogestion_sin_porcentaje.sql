-- Quita el porcentaje del catálogo de Autogestión
USE new_ua;

ALTER TABLE autogestion_items DROP COLUMN porcentaje;
