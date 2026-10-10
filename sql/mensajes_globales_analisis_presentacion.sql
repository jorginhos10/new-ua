-- Agrega 'analisis_presentacion' a las audiencias de mensajes_globales: el mensaje editable (con la
-- misma estructura de Mensajes globales — Configuraciones) que explica qué hay en la pestaña
-- "Presentación" de ?ruta=analisis. Se administra desde la misma pantalla de Mensajes globales,
-- con una pestaña nueva.
-- EJECUTAR UNA SOLA VEZ.

ALTER TABLE mensajes_globales
    MODIFY COLUMN audiencia ENUM('administrador', 'consejo_superior', 'analisis_presentacion') NOT NULL DEFAULT 'administrador';
