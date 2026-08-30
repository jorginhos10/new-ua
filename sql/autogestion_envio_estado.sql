-- Agrega el flujo de "Enviar todo" (borrador/enviado) a ingresos y gastos de los 4 módulos
-- de autogestión (Extensión, Postgrado, Sin excedentes, Unisalud), igual que ya existe en `gastos`.
USE new_ua;

ALTER TABLE gastos_extension ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;
ALTER TABLE gastos_postgrado ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;
ALTER TABLE gastos_sin_excedentes ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;
ALTER TABLE gastos_unisalud ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;

ALTER TABLE ingresos_extension ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;
ALTER TABLE ingresos_extension ADD COLUMN rol_destinatario_id INT NULL AFTER estado;

ALTER TABLE ingresos_postgrado ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;
ALTER TABLE ingresos_postgrado ADD COLUMN rol_destinatario_id INT NULL AFTER estado;

ALTER TABLE ingresos_sin_excedentes ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;
ALTER TABLE ingresos_sin_excedentes ADD COLUMN rol_destinatario_id INT NULL AFTER estado;

ALTER TABLE ingresos_unisalud ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER creado_en;
ALTER TABLE ingresos_unisalud ADD COLUMN rol_destinatario_id INT NULL AFTER estado;
