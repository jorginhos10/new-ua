-- Corrige el catalogo de tipos de monitor a 4 opciones (se fusiona solidarios+academicos).
USE new_ua;

ALTER TABLE solicitudes_monitores
    MODIFY COLUMN tipo ENUM('solidario_academico', 'deportivo', 'cultural', 'administrativo') NOT NULL;
