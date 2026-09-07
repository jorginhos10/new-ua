-- Actas: tabla + habilitación del menú para Facultad/Vicerrectoría
-- Combina sql/actas.sql + sql/actas_menu.sql. Seguro de correr más de una vez.
-- (tipo_dependencia_menu no tiene llave única, por eso el INSERT usa
-- WHERE NOT EXISTS en vez de INSERT IGNORE, para no duplicar filas si ya
-- se corrió antes).

CREATE TABLE IF NOT EXISTS actas (
  id INT NOT NULL AUTO_INCREMENT,
  anio_presupuestal_id INT NOT NULL,
  dependencia_id INT NOT NULL,
  remitente_id INT NOT NULL,
  destinatario_id INT NOT NULL,
  nombre_archivo VARCHAR(255) NOT NULL,
  nombre_almacenado VARCHAR(255) NOT NULL,
  tamano_bytes INT NOT NULL,
  leido TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_actas_destinatario (destinatario_id),
  KEY idx_actas_remitente (remitente_id),
  KEY idx_actas_anio (anio_presupuestal_id),
  KEY idx_actas_dependencia (dependencia_id),
  CONSTRAINT fk_actas_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id),
  CONSTRAINT fk_actas_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencias(id),
  CONSTRAINT fk_actas_remitente FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_actas_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tipo_dependencia_menu (tipo, rol_id, menu_key)
SELECT 'Facultad', NULL, 'actas'
WHERE NOT EXISTS (SELECT 1 FROM tipo_dependencia_menu WHERE tipo = 'Facultad' AND rol_id IS NULL AND menu_key = 'actas');

INSERT INTO tipo_dependencia_menu (tipo, rol_id, menu_key)
SELECT 'Facultad', 1, 'actas'
WHERE NOT EXISTS (SELECT 1 FROM tipo_dependencia_menu WHERE tipo = 'Facultad' AND rol_id = 1 AND menu_key = 'actas');

INSERT INTO tipo_dependencia_menu (tipo, rol_id, menu_key)
SELECT 'Vicerrectoria', NULL, 'actas'
WHERE NOT EXISTS (SELECT 1 FROM tipo_dependencia_menu WHERE tipo = 'Vicerrectoria' AND rol_id IS NULL AND menu_key = 'actas');

INSERT INTO tipo_dependencia_menu (tipo, rol_id, menu_key)
SELECT 'Vicerrectoria', 1, 'actas'
WHERE NOT EXISTS (SELECT 1 FROM tipo_dependencia_menu WHERE tipo = 'Vicerrectoria' AND rol_id = 1 AND menu_key = 'actas');
