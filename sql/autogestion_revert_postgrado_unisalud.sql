-- Autogestión solo aplica a Extensión: se revierte de Postgrado y Unidad de Salud
USE new_ua;

ALTER TABLE gastos_postgrado DROP FOREIGN KEY fk_gasto_postgrado_autogestion;
ALTER TABLE gastos_postgrado DROP COLUMN autogestion_id;

ALTER TABLE gastos_unisalud DROP FOREIGN KEY fk_gasto_unisalud_autogestion;
ALTER TABLE gastos_unisalud DROP COLUMN autogestion_id;
