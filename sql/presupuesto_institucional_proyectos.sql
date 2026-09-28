ALTER TABLE presupuesto_institucional_lineas
    MODIFY COLUMN tipo ENUM('ingreso', 'egreso', 'proyecto') NOT NULL;

ALTER TABLE presupuesto_institucional_versiones
    MODIFY COLUMN tipo ENUM('ingreso', 'egreso', 'proyecto') NOT NULL;
