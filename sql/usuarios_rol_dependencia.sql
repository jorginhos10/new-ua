ALTER TABLE usuarios
  ADD COLUMN rol_id INT NULL AFTER rol,
  ADD COLUMN dependencia_id INT NULL AFTER rol_id,
  ADD CONSTRAINT fk_usuarios_rol_catalogo FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_usuarios_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencias(id) ON DELETE SET NULL;
