-- Cada ingreso de Extensión también queda ligado a un ítem de Autogestión,
-- para que Ingresos y Egresos sean independientes por ítem.
USE new_ua;

ALTER TABLE ingresos_extension ADD COLUMN autogestion_id INT NOT NULL AFTER anio_presupuestal_id;
ALTER TABLE ingresos_extension ADD CONSTRAINT fk_ingreso_extension_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);
