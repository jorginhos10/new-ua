-- El tope deja de ser por ítem de Autogestión y pasa a ser UN SOLO campo por módulo (uno para
-- Extensión, uno para Postgrado): la tarjeta de Autogestión/Postgrado del Dashboard debe medir
-- cumplimiento contra un único tope configurado una vez, no contra la suma de topes sueltos por
-- ítem. autogestion_porcentajes ya tiene una fila por módulo — se le agrega la columna tope ahí.
USE new_ua;

ALTER TABLE autogestion_porcentajes
    ADD COLUMN tope DECIMAL(15, 2) NULL AFTER modulo;

-- Semilla: si algún ítem ya tenía un tope configurado, se preserva como punto de partida sumando
-- todos los topes de items activos de ese módulo (mismo comportamiento que ya tenía
-- obtenerSumaTope() antes de este cambio) — normalmente será NULL/0 si nadie había configurado
-- nada todavía.
UPDATE autogestion_porcentajes ap
SET ap.tope = (
    SELECT NULLIF(SUM(ai.tope), 0) FROM autogestion_items ai
    WHERE ai.modulo = ap.modulo AND ai.estado = 'activo'
)
WHERE ap.modulo IN ('extension', 'postgrado');

ALTER TABLE autogestion_items
    DROP COLUMN tope;
