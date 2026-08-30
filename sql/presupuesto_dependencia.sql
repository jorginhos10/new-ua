CREATE TABLE presupuesto_dependencia (
  id INT NOT NULL AUTO_INCREMENT,
  anio_presupuestal_id INT NOT NULL,
  dependencia_id INT NOT NULL,
  minimo DECIMAL(15,2) NULL,
  techo DECIMAL(15,2) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY anio_dependencia (anio_presupuestal_id, dependencia_id),
  CONSTRAINT fk_presupuesto_dep_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id) ON DELETE CASCADE,
  CONSTRAINT fk_presupuesto_dep_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
