-- Agrega estado (borrador/enviada) a las solicitudes ARL para poder marcarlas como enviadas.
USE new_ua;

ALTER TABLE solicitudes_arl ADD COLUMN estado ENUM('borrador', 'enviada') NOT NULL DEFAULT 'borrador' AFTER facultad;
