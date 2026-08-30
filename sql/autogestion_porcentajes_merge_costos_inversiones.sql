-- Costos e Inversiones dejan de ser dos porcentajes separados: se unen en uno solo
-- (los egresos que salen de esa bolsa se etiquetan como Gastos o Inversiones
-- individualmente, vía el campo Categoría). Excedentes sigue aparte.
USE new_ua;

ALTER TABLE autogestion_porcentajes ADD COLUMN costos_inversiones DECIMAL(5, 2) NULL AFTER modulo;

UPDATE autogestion_porcentajes
SET costos_inversiones = CASE
    WHEN costos IS NULL AND inversiones IS NULL THEN NULL
    ELSE COALESCE(costos, 0) + COALESCE(inversiones, 0)
END;

ALTER TABLE autogestion_porcentajes DROP COLUMN costos;
ALTER TABLE autogestion_porcentajes DROP COLUMN inversiones;
