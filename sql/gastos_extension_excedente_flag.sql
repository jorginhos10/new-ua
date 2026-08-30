-- Marca la fila de excedentes generada automáticamente, para poder ubicarla y actualizarla
-- (una sola fila por año+ítem de autogestión, en vez de una por cada ingreso).
USE new_ua;

ALTER TABLE gastos_extension ADD COLUMN es_excedente_automatico TINYINT(1) NOT NULL DEFAULT 0 AFTER ingreso_id;
