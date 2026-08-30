-- Generaliza la marca de fila automática de Excedentes para también cubrir Costos e Inversiones:
-- cada tipo tiene su propia línea (actualizada, no duplicada) por año + ítem de autogestión.
USE new_ua;

ALTER TABLE gastos_extension ADD COLUMN tipo_automatico VARCHAR(20) NULL AFTER es_excedente_automatico;
UPDATE gastos_extension SET tipo_automatico = 'excedentes' WHERE es_excedente_automatico = 1;
