-- Replica en Postgrado, Unidad de Salud y Sin excedentes el mismo esquema de
-- Categoría + Autogestión + egresos automáticos que ya tiene Extensión.
USE new_ua;

-- Postgrado
ALTER TABLE gastos_postgrado
    ADD COLUMN categoria VARCHAR(100) NOT NULL AFTER anio_presupuestal_id,
    MODIFY COLUMN rubro_id INT NULL,
    ADD COLUMN rubro_texto VARCHAR(255) NULL AFTER rubro_id,
    ADD COLUMN autogestion_id INT NOT NULL AFTER rubro_texto,
    ADD COLUMN ingreso_id INT NULL AFTER autogestion_id,
    ADD COLUMN tipo_automatico VARCHAR(20) NULL AFTER ingreso_id;
ALTER TABLE gastos_postgrado ADD CONSTRAINT fk_gasto_postgrado_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);
ALTER TABLE gastos_postgrado ADD CONSTRAINT fk_gasto_postgrado_ingreso FOREIGN KEY (ingreso_id) REFERENCES ingresos_postgrado(id) ON DELETE SET NULL;

ALTER TABLE ingresos_postgrado ADD COLUMN autogestion_id INT NOT NULL AFTER anio_presupuestal_id;
ALTER TABLE ingresos_postgrado ADD CONSTRAINT fk_ingreso_postgrado_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);

-- Unidad de Salud
ALTER TABLE gastos_unisalud
    ADD COLUMN categoria VARCHAR(100) NOT NULL AFTER anio_presupuestal_id,
    MODIFY COLUMN rubro_id INT NULL,
    ADD COLUMN rubro_texto VARCHAR(255) NULL AFTER rubro_id,
    ADD COLUMN autogestion_id INT NOT NULL AFTER rubro_texto,
    ADD COLUMN ingreso_id INT NULL AFTER autogestion_id,
    ADD COLUMN tipo_automatico VARCHAR(20) NULL AFTER ingreso_id;
ALTER TABLE gastos_unisalud ADD CONSTRAINT fk_gasto_unisalud_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);
ALTER TABLE gastos_unisalud ADD CONSTRAINT fk_gasto_unisalud_ingreso FOREIGN KEY (ingreso_id) REFERENCES ingresos_unisalud(id) ON DELETE SET NULL;

ALTER TABLE ingresos_unisalud ADD COLUMN autogestion_id INT NOT NULL AFTER anio_presupuestal_id;
ALTER TABLE ingresos_unisalud ADD CONSTRAINT fk_ingreso_unisalud_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);

-- Sin excedentes
ALTER TABLE gastos_sin_excedentes
    ADD COLUMN categoria VARCHAR(100) NOT NULL AFTER anio_presupuestal_id,
    MODIFY COLUMN rubro_id INT NULL,
    ADD COLUMN rubro_texto VARCHAR(255) NULL AFTER rubro_id,
    ADD COLUMN autogestion_id INT NOT NULL AFTER rubro_texto,
    ADD COLUMN ingreso_id INT NULL AFTER autogestion_id,
    ADD COLUMN tipo_automatico VARCHAR(20) NULL AFTER ingreso_id;
ALTER TABLE gastos_sin_excedentes ADD CONSTRAINT fk_gasto_sinexc_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);
ALTER TABLE gastos_sin_excedentes ADD CONSTRAINT fk_gasto_sinexc_ingreso FOREIGN KEY (ingreso_id) REFERENCES ingresos_sin_excedentes(id) ON DELETE SET NULL;

ALTER TABLE ingresos_sin_excedentes ADD COLUMN autogestion_id INT NOT NULL AFTER anio_presupuestal_id;
ALTER TABLE ingresos_sin_excedentes ADD CONSTRAINT fk_ingreso_sinexc_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id);
