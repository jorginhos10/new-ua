-- Conecta gastos.rubro (texto libre) al catálogo de rubros
USE new_ua;

DELETE FROM gastos;

ALTER TABLE gastos DROP COLUMN rubro;
ALTER TABLE gastos ADD COLUMN rubro_id INT NOT NULL AFTER actividad;
ALTER TABLE gastos ADD CONSTRAINT fk_gasto_rubro FOREIGN KEY (rubro_id) REFERENCES rubros(id);
