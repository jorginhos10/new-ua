-- Postgrado pasa a tener ítems de Autogestión propios, igual que ya tiene Extensión (selector en
-- el sidebar, % de Costos/Inversiones/Excedentes/Contribución a posgrado por ítem en vez de uno
-- solo por módulo). Se crea un ítem "General" para no perder continuidad con lo que ya existía
-- (todo lo registrado hasta ahora en Postgrado queda bajo ese ítem, con el % que tenía el módulo).
USE new_ua;

ALTER TABLE autogestion_items
    ADD COLUMN contribucion_postgrado DECIMAL(5, 2) NULL AFTER excedentes;

INSERT INTO autogestion_items (nombre, modulo, costos, inversiones, excedentes, contribucion_postgrado)
SELECT 'General', 'postgrado', ap.costos, ap.inversiones, ap.excedentes, ap.contribucion_postgrado
FROM autogestion_porcentajes ap
WHERE ap.modulo = 'postgrado';

-- ingresos_postgrado: autogestion_id en la misma posición relativa que ingresos_extension
-- (justo después de anio_presupuestal_id).
ALTER TABLE ingresos_postgrado
    ADD COLUMN autogestion_id INT NULL AFTER anio_presupuestal_id;

UPDATE ingresos_postgrado
SET autogestion_id = (SELECT id FROM autogestion_items WHERE modulo = 'postgrado' AND nombre = 'General' LIMIT 1);

ALTER TABLE ingresos_postgrado
    MODIFY COLUMN autogestion_id INT NOT NULL,
    ADD CONSTRAINT fk_ingreso_postgrado_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);

-- gastos_postgrado: autogestion_id en la misma posición relativa que gastos_extension (justo
-- después de rubro_texto, antes de ingreso_id).
ALTER TABLE gastos_postgrado
    ADD COLUMN autogestion_id INT NULL AFTER rubro_texto;

UPDATE gastos_postgrado
SET autogestion_id = (SELECT id FROM autogestion_items WHERE modulo = 'postgrado' AND nombre = 'General' LIMIT 1);

ALTER TABLE gastos_postgrado
    MODIFY COLUMN autogestion_id INT NOT NULL,
    ADD CONSTRAINT fk_gasto_postgrado_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);
