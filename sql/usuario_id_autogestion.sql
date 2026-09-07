-- El código (crear() de cada modelo) inserta usuario_id en estas tablas desde hace tiempo,
-- pero la columna nunca llegó a producción (no existía sql/ para ella). Confirmado con el error
-- real: "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'usuario_id' in 'field list'"
-- al crear un ingreso en ingresos_extension.
ALTER TABLE gastos ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE gastos_extension ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE ingresos_extension ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE gastos_postgrado ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE ingresos_postgrado ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE gastos_unisalud ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE ingresos_unisalud ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE gastos_sin_excedentes ADD COLUMN usuario_id INT NULL DEFAULT NULL;
ALTER TABLE ingresos_sin_excedentes ADD COLUMN usuario_id INT NULL DEFAULT NULL;
