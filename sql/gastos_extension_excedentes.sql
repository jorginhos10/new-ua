-- Soporta el egreso automático de "Excedentes nivel central" generado al crear un ingreso:
-- categoria pasa a texto libre (ya no solo Gastos/Inversiones), rubro puede ser texto libre
-- (no todos los conceptos existen en el catálogo de rubros), y se guarda el ingreso que lo originó.
USE new_ua;

ALTER TABLE gastos_extension MODIFY COLUMN categoria VARCHAR(100) NOT NULL;
ALTER TABLE gastos_extension MODIFY COLUMN rubro_id INT NULL;
ALTER TABLE gastos_extension ADD COLUMN rubro_texto VARCHAR(255) NULL AFTER rubro_id;
ALTER TABLE gastos_extension ADD COLUMN ingreso_id INT NULL AFTER autogestion_id;
ALTER TABLE gastos_extension ADD CONSTRAINT fk_gasto_extension_ingreso FOREIGN KEY (ingreso_id) REFERENCES ingresos_extension(id) ON DELETE SET NULL;
