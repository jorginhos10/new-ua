-- Asocia cada gasto a un año presupuestal
USE new_ua;

ALTER TABLE gastos ADD COLUMN anio_presupuestal_id INT NOT NULL AFTER sede;
ALTER TABLE gastos ADD CONSTRAINT fk_gasto_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id);
