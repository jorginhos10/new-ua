-- Inicio de Consejo Superior: reloj propio y mensajes globales propios.
-- 1) Tabla del reloj del Consejo Superior (mismo formato que reloj_arena_configuracion y _formulador).
-- 2) Audiencia de cada mensaje global: los existentes quedan para administradores.
-- Se puede correr más de una vez.

CREATE TABLE IF NOT EXISTS reloj_arena_consejo (
    id INT PRIMARY KEY DEFAULT 1,
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE mensajes_globales
    ADD COLUMN IF NOT EXISTS audiencia ENUM('administrador', 'consejo_superior') NOT NULL DEFAULT 'administrador' AFTER contenido;
