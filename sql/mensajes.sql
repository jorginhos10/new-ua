CREATE TABLE mensajes (
  id INT NOT NULL AUTO_INCREMENT,
  remitente_id INT NOT NULL,
  destinatario_id INT NOT NULL,
  asunto VARCHAR(200) NOT NULL,
  cuerpo TEXT NOT NULL,
  leido TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mensajes_destinatario (destinatario_id),
  KEY idx_mensajes_remitente (remitente_id),
  CONSTRAINT fk_mensaje_remitente FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_mensaje_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
