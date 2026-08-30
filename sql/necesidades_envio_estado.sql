-- Agrega el flujo de "Enviar todo" (borrador/enviado) a Perfil de proyectos, igual que ya existe
-- en gastos/ingresos de autogestión: hasta que el admin no envíe explícitamente, la necesidad no
-- aparece en Peticiones.
USE new_ua;

ALTER TABLE necesidades_academicas ADD COLUMN estado ENUM('borrador', 'enviado') NOT NULL DEFAULT 'borrador' AFTER observaciones;
ALTER TABLE necesidades_academicas ADD COLUMN rol_destinatario_id INT NULL AFTER estado;
