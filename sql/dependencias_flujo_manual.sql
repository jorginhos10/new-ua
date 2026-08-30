ALTER TABLE dependencias
  ADD COLUMN flujo_id INT NULL AFTER codigo,
  ADD CONSTRAINT fk_dependencia_flujo FOREIGN KEY (flujo_id) REFERENCES dependencias(id) ON DELETE SET NULL;
