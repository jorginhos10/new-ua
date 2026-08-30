-- Tablas de gastos para las secciones de Autogestión: Extensión, Postgrado y Unidad de Salud.
-- Mismo esquema final que la tabla "gastos" (ver sql/gastos.sql y sus migraciones).
USE new_ua;

CREATE TABLE IF NOT EXISTS gastos_extension (
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
    CONSTRAINT fk_gasto_extension_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id),
    CONSTRAINT fk_gasto_extension_sede FOREIGN KEY (sede_id) REFERENCES sedes(id),
    CONSTRAINT fk_gasto_extension_linea FOREIGN KEY (linea_id) REFERENCES lineas(id),
    CONSTRAINT fk_gasto_extension_motor FOREIGN KEY (motor_id) REFERENCES motores(id),
    CONSTRAINT fk_gasto_extension_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    CONSTRAINT fk_gasto_extension_rubro FOREIGN KEY (rubro_id) REFERENCES rubros(id)
);

CREATE TABLE IF NOT EXISTS gastos_postgrado (
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
    CONSTRAINT fk_gasto_postgrado_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id),
    CONSTRAINT fk_gasto_postgrado_sede FOREIGN KEY (sede_id) REFERENCES sedes(id),
    CONSTRAINT fk_gasto_postgrado_linea FOREIGN KEY (linea_id) REFERENCES lineas(id),
    CONSTRAINT fk_gasto_postgrado_motor FOREIGN KEY (motor_id) REFERENCES motores(id),
    CONSTRAINT fk_gasto_postgrado_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    CONSTRAINT fk_gasto_postgrado_rubro FOREIGN KEY (rubro_id) REFERENCES rubros(id)
);

CREATE TABLE IF NOT EXISTS gastos_unisalud (
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
    CONSTRAINT fk_gasto_unisalud_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id),
    CONSTRAINT fk_gasto_unisalud_sede FOREIGN KEY (sede_id) REFERENCES sedes(id),
    CONSTRAINT fk_gasto_unisalud_linea FOREIGN KEY (linea_id) REFERENCES lineas(id),
    CONSTRAINT fk_gasto_unisalud_motor FOREIGN KEY (motor_id) REFERENCES motores(id),
    CONSTRAINT fk_gasto_unisalud_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    CONSTRAINT fk_gasto_unisalud_rubro FOREIGN KEY (rubro_id) REFERENCES rubros(id)
);
