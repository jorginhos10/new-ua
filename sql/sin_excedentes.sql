-- "Sin excedentes": copia de Unidad de Salud (Ingresos + Egresos)
USE new_ua;

CREATE TABLE IF NOT EXISTS gastos_sin_excedentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    sede_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    linea_id INT NOT NULL,
    motor_id INT NOT NULL,
    proyecto_id INT NOT NULL,
    objeto_proyecto_paa VARCHAR(200) NOT NULL,
    actividad VARCHAR(255) NOT NULL,
    rubro_id INT NOT NULL,
    insumo VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(15, 2) NOT NULL,
    valor_total DECIMAL(15, 2) NOT NULL,
    meses VARCHAR(150) NOT NULL DEFAULT '',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gasto_sinexc_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id),
    CONSTRAINT fk_gasto_sinexc_sede FOREIGN KEY (sede_id) REFERENCES sedes(id),
    CONSTRAINT fk_gasto_sinexc_linea FOREIGN KEY (linea_id) REFERENCES lineas(id),
    CONSTRAINT fk_gasto_sinexc_motor FOREIGN KEY (motor_id) REFERENCES motores(id),
    CONSTRAINT fk_gasto_sinexc_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    CONSTRAINT fk_gasto_sinexc_rubro FOREIGN KEY (rubro_id) REFERENCES rubros(id)
);

CREATE TABLE IF NOT EXISTS ingresos_sin_excedentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    concepto_adicional VARCHAR(200) NOT NULL DEFAULT '',
    valor_adicional DECIMAL(15, 2) NOT NULL DEFAULT 0,
    valor_total DECIMAL(15, 2) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ingreso_sinexc_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id)
);

CREATE TABLE IF NOT EXISTS ingresos_sin_excedentes_conceptos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    CONSTRAINT fk_ingreso_sinexc_concepto FOREIGN KEY (ingreso_id) REFERENCES ingresos_sin_excedentes(id) ON DELETE CASCADE
);
