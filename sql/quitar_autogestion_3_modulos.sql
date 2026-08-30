-- Postgrado, Unidad de Salud y Sin excedentes NO se dividen en ítems de Autogestión
-- (eso es exclusivo de Extensión). Cada uno de estos 3 módulos es una sola unidad,
-- por lo que se quita autogestion_id; el resto (categoría, egresos automáticos,
-- porcentajes, tope de presupuesto) queda a nivel de año.
USE new_ua;

ALTER TABLE gastos_postgrado DROP FOREIGN KEY fk_gasto_postgrado_autogestion;
ALTER TABLE gastos_postgrado DROP COLUMN autogestion_id;
ALTER TABLE ingresos_postgrado DROP FOREIGN KEY fk_ingreso_postgrado_autogestion;
ALTER TABLE ingresos_postgrado DROP COLUMN autogestion_id;

ALTER TABLE gastos_unisalud DROP FOREIGN KEY fk_gasto_unisalud_autogestion;
ALTER TABLE gastos_unisalud DROP COLUMN autogestion_id;
ALTER TABLE ingresos_unisalud DROP FOREIGN KEY fk_ingreso_unisalud_autogestion;
ALTER TABLE ingresos_unisalud DROP COLUMN autogestion_id;

ALTER TABLE gastos_sin_excedentes DROP FOREIGN KEY fk_gasto_sinexc_autogestion;
ALTER TABLE gastos_sin_excedentes DROP COLUMN autogestion_id;
ALTER TABLE ingresos_sin_excedentes DROP FOREIGN KEY fk_ingreso_sinexc_autogestion;
ALTER TABLE ingresos_sin_excedentes DROP COLUMN autogestion_id;
