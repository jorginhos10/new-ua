-- Deja de sobreescribir la columna `dependencia` al enviar (perdía la dependencia emisora
-- original). A partir de ahora `enviarTodosBorrador()` escribe el destino en esta columna nueva,
-- y `dependencia` conserva siempre la dependencia que originó la fila.
ALTER TABLE gastos ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE gastos_extension ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE gastos_postgrado ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE gastos_unisalud ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE gastos_sin_excedentes ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE ingresos_extension ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE ingresos_postgrado ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE ingresos_unisalud ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE ingresos_sin_excedentes ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE necesidades_academicas ADD COLUMN dependencia_destino VARCHAR(150) NULL DEFAULT NULL;
