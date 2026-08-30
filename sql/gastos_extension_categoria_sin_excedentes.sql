-- Quita "Excedentes nivel central" de las categorías de egreso en Extensión
USE new_ua;

ALTER TABLE gastos_extension
    MODIFY COLUMN categoria ENUM('Gastos', 'Inversiones') NOT NULL;
