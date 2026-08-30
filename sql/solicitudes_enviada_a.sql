-- Al enviar una solicitud (cualquier tipo) se debe capturar a que dependencia se envia.
USE new_ua;

ALTER TABLE solicitudes_arl ADD COLUMN enviada_a VARCHAR(150) NULL AFTER estado;
ALTER TABLE solicitudes_monitores ADD COLUMN enviada_a VARCHAR(150) NULL AFTER estado;
ALTER TABLE solicitudes_ops ADD COLUMN enviada_a VARCHAR(150) NULL AFTER estado;
ALTER TABLE solicitudes_peticiones ADD COLUMN enviada_a VARCHAR(150) NULL AFTER estado;
