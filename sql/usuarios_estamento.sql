ALTER TABLE usuarios
  ADD COLUMN estamento_id INT NULL AFTER facultad,
  ADD CONSTRAINT fk_usuarios_estamento FOREIGN KEY (estamento_id) REFERENCES estamentos(id) ON DELETE SET NULL;
