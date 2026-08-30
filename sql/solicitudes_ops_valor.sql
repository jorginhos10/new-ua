-- Agrega el valor unitario a las solicitudes OPS, para multiplicarlo por la cantidad.
USE new_ua;

ALTER TABLE solicitudes_ops ADD COLUMN valor DECIMAL(14, 2) NOT NULL DEFAULT 0.00 AFTER perfil;
