-- Corrige la fuga de visibilidad en Peticiones: cuando hay más de un usuario con el mismo rol
-- en una dependencia destino, hoy solo se guarda "rol + dependencia" al enviar, así que TODOS
-- los usuarios con ese rol ven la petición en Pendientes, no solo el destinatario elegido.
-- Estas columnas guardan al destinatario específico (cuando se conoce) para que la visibilidad
-- se pueda restringir a esa persona exacta.

ALTER TABLE gastos ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE gastos_extension ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE gastos_postgrado ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE gastos_unisalud ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE gastos_sin_excedentes ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;

ALTER TABLE ingresos_extension ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE ingresos_postgrado ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE ingresos_unisalud ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE ingresos_sin_excedentes ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;

ALTER TABLE necesidades_academicas ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;

ALTER TABLE solicitudes_arl ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE solicitudes_monitores ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE solicitudes_ops ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
ALTER TABLE solicitudes_peticiones ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;

-- peticiones_archivadas (ítems redirigidos desde Consolidado/Archivados/Enviadas) hoy no tiene
-- NI SIQUIERA rol_destinatario_id — un ítem redirigido es visible para cualquiera en la
-- dependencia destino, sin filtrar por rol. Se agregan ambas columnas.
ALTER TABLE peticiones_archivadas ADD COLUMN rol_destinatario_id INT NULL DEFAULT NULL AFTER redireccionado_a_dependencia;
ALTER TABLE peticiones_archivadas ADD COLUMN usuario_destinatario_id INT NULL DEFAULT NULL AFTER rol_destinatario_id;
