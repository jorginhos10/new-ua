-- NIT de sede: dos dígitos, empieza por 0 (00 a 09), único por sede.
-- EJECUTAR UNA SOLA VEZ.
-- Valores iniciales: 01 a 06 según el orden de creación de las sedes existentes (id). Se corrigen
-- desde Configuraciones > Sedes, que ahora permite editar código, nombre y NIT.

ALTER TABLE sedes ADD COLUMN nit CHAR(2) NULL AFTER codigo;

UPDATE sedes SET nit = '01' WHERE id = 1;
UPDATE sedes SET nit = '02' WHERE id = 2;
UPDATE sedes SET nit = '03' WHERE id = 4;
UPDATE sedes SET nit = '04' WHERE id = 5;
UPDATE sedes SET nit = '05' WHERE id = 6;
UPDATE sedes SET nit = '06' WHERE id = 8;

-- Cualquier sede creada después de la carga inicial sin NIT quedaría vacía: el código de la app exige el NIT,
-- así que no debe haber sedes sin valor antes de aplicar estas restricciones.
ALTER TABLE sedes
    MODIFY COLUMN nit CHAR(2) NOT NULL,
    ADD UNIQUE KEY uq_sedes_nit (nit),
    ADD CONSTRAINT chk_sedes_nit CHECK (nit REGEXP '^0[0-9]$');
