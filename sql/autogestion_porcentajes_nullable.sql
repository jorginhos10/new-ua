-- Permite marcar un módulo como "No aplica" (porcentajes en NULL)
USE new_ua;

ALTER TABLE autogestion_porcentajes
    MODIFY COLUMN costos DECIMAL(5, 2) NULL DEFAULT NULL,
    MODIFY COLUMN inversiones DECIMAL(5, 2) NULL DEFAULT NULL,
    MODIFY COLUMN excedentes DECIMAL(5, 2) NULL DEFAULT NULL;
