ALTER TABLE arbol_versiones
    ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 0 AFTER nombre;

ALTER TABLE presupuesto_institucional_versiones
    ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 0 AFTER nombre;

-- Al migrar, deja marcada como activa la más reciente de cada grupo, para que el
-- comportamiento no cambie de golpe (antes, sin version_id en la URL, siempre se caía a la
-- más reciente).
UPDATE arbol_versiones v
JOIN (
    SELECT pestana, MAX(id) AS id_mas_reciente
    FROM arbol_versiones
    GROUP BY pestana
) ultimos ON ultimos.id_mas_reciente = v.id
SET v.activa = 1;

UPDATE presupuesto_institucional_versiones v
JOIN (
    SELECT tipo, MAX(id) AS id_mas_reciente
    FROM presupuesto_institucional_versiones
    GROUP BY tipo
) ultimos ON ultimos.id_mas_reciente = v.id
SET v.activa = 1;
