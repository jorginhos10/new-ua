-- Elimina el campo dependencia_destino de las peticiones (ya no se solicita).
USE new_ua;

ALTER TABLE solicitudes_peticiones DROP COLUMN dependencia_destino;
