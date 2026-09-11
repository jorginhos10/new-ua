CREATE TABLE IF NOT EXISTS codigos_recuperacion_password (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo VARCHAR(9) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_codigo_recuperacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_codigo_recuperacion_usuario (usuario_id)
);
