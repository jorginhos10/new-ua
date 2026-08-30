-- Vincula cada egreso de Extensión/Postgrado/Unidad de Salud a un ítem de Autogestión
USE new_ua;

ALTER TABLE gastos_extension ADD COLUMN autogestion_id INT NOT NULL AFTER rubro_id;
ALTER TABLE gastos_extension ADD CONSTRAINT fk_gasto_extension_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);

ALTER TABLE gastos_postgrado ADD COLUMN autogestion_id INT NOT NULL AFTER rubro_id;
ALTER TABLE gastos_postgrado ADD CONSTRAINT fk_gasto_postgrado_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);

ALTER TABLE gastos_unisalud ADD COLUMN autogestion_id INT NOT NULL AFTER rubro_id;
ALTER TABLE gastos_unisalud ADD CONSTRAINT fk_gasto_unisalud_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);
