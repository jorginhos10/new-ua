-- sql/usuario_destinatario_especifico.sql agrega `usuario_destinatario_id ... AFTER rol_destinatario_id`
-- en gastos/gastos_extension/gastos_postgrado/gastos_unisalud/gastos_sin_excedentes y en las 4
-- tablas de solicitudes, y `rol_destinatario_id ... AFTER redireccionado_a_dependencia` en
-- peticiones_archivadas. Ninguna de esas columnas base (`rol_destinatario_id` en esas 9 tablas,
-- `redireccionado_a_dependencia` en peticiones_archivadas) se crea en ningún otro archivo: solo
-- existía ya en producción, aplicada a mano, y nunca quedó un .sql para reproducirla en limpio.
-- Mismo patrón que ya usan ingresos_extension/ingresos_postgrado/ingresos_unisalud/
-- ingresos_sin_excedentes/necesidades_academicas (ver autogestion_envio_estado.sql y
-- necesidades_envio_estado.sql: `rol_destinatario_id INT NULL`).
USE new_ua;

ALTER TABLE gastos ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER dependencia_destino;
ALTER TABLE gastos_extension ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;
ALTER TABLE gastos_postgrado ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;
ALTER TABLE gastos_unisalud ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;
ALTER TABLE gastos_sin_excedentes ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;

ALTER TABLE solicitudes_arl ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;
ALTER TABLE solicitudes_monitores ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;
ALTER TABLE solicitudes_ops ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;
ALTER TABLE solicitudes_peticiones ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER estado;

ALTER TABLE peticiones_archivadas ADD COLUMN redireccionado_a_dependencia VARCHAR(150) NULL DEFAULT NULL AFTER ruta_origen;
