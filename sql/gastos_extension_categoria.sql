-- Categoría del egreso en Extensión
USE new_ua;

ALTER TABLE gastos_extension
    ADD COLUMN categoria ENUM('Gastos', 'Inversiones', 'Excedentes nivel central') NOT NULL AFTER anio_presupuestal_id;
