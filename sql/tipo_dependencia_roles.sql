CREATE TABLE tipo_dependencia_roles (
  id INT NOT NULL AUTO_INCREMENT,
  tipo VARCHAR(50) NOT NULL,
  rol_id INT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY tipo_rol (tipo, rol_id),
  CONSTRAINT fk_tipo_dep_rol_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
