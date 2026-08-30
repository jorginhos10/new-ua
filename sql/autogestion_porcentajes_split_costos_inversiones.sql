-- Vuelve a separar Costos e Inversiones en dos campos independientes (con su propio
-- "No aplica" cada uno). Se restauran los valores reales que tenían antes de fusionarlos.
USE new_ua;

ALTER TABLE autogestion_porcentajes ADD COLUMN costos DECIMAL(5, 2) NULL AFTER modulo;
ALTER TABLE autogestion_porcentajes ADD COLUMN inversiones DECIMAL(5, 2) NULL AFTER costos;

UPDATE autogestion_porcentajes SET costos = 70.00, inversiones = 15.00 WHERE modulo = 'extension';
UPDATE autogestion_porcentajes SET costos = NULL, inversiones = NULL WHERE modulo = 'postgrado';
UPDATE autogestion_porcentajes SET costos = 100.00, inversiones = 0.00 WHERE modulo = 'sin-excedentes';
UPDATE autogestion_porcentajes SET costos = 100.00, inversiones = 0.00 WHERE modulo = 'unisalud';

ALTER TABLE autogestion_porcentajes DROP COLUMN costos_inversiones;
