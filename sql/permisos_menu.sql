CREATE TABLE tipo_dependencia_menu (
  id INT NOT NULL AUTO_INCREMENT,
  tipo VARCHAR(50) NOT NULL,
  menu_key VARCHAR(60) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY tipo_menu (tipo, menu_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_menu (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  menu_key VARCHAR(60) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY usuario_menu_unico (usuario_id, menu_key),
  CONSTRAINT fk_usuario_menu_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios
  ADD COLUMN menu_personalizado TINYINT(1) NOT NULL DEFAULT 0 AFTER dependencia_id;
