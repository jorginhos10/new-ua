ALTER TABLE roles
  ADD COLUMN orden INT NOT NULL DEFAULT 0 AFTER nombre;

UPDATE roles SET orden = 1 WHERE nombre = 'Gestor';
UPDATE roles SET orden = 2 WHERE nombre = 'Avalador';
UPDATE roles SET orden = 3 WHERE nombre = 'Formulador';
