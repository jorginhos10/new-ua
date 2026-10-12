-- Snapshot activo (Análisis de distribución, modo Repositorio): hasta ahora Consejo Superior
-- siempre veía el snapshot más reciente por fecha, sin que el admin pudiera elegir uno distinto
-- ni quedara registrado cuál es "el oficial" — a diferencia de Programación presupuestal/Proyectos/
-- Articulación PDI, que sí tienen esa marca (columna `activa` en sus propias tablas de versiones).
-- Mismo mecanismo aquí: Snapshot::marcarActiva()/obtenerActiva().
-- EJECUTAR UNA SOLA VEZ.

ALTER TABLE snapshots
    ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 0 AFTER nombre;
