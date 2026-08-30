-- Conecta gastos.sede (texto libre) al catálogo de sedes
USE new_ua;

ALTER TABLE gastos DROP COLUMN sede;
ALTER TABLE gastos ADD COLUMN sede_id INT NOT NULL AFTER anio_presupuestal_id;
ALTER TABLE gastos ADD CONSTRAINT fk_gasto_sede FOREIGN KEY (sede_id) REFERENCES sedes(id);
