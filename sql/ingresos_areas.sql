-- Ingresos de Extensión, Postgrado y Unidad de Salud.
-- Estructura: cabecera (dependencia, concepto adicional, total) + varios conceptos por ingreso.
USE new_ua;

DROP TABLE IF EXISTS ingresos_extension_conceptos;
DROP TABLE IF EXISTS ingresos_extension;
DROP TABLE IF EXISTS ingresos_postgrado_conceptos;
DROP TABLE IF EXISTS ingresos_postgrado;
DROP TABLE IF EXISTS ingresos_unisalud_conceptos;
DROP TABLE IF EXISTS ingresos_unisalud;

CREATE TABLE ingresos_extension (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    concepto_adicional VARCHAR(200) NOT NULL DEFAULT '',
    valor_adicional DECIMAL(15, 2) NOT NULL DEFAULT 0,
    valor_total DECIMAL(15, 2) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ingreso_extension_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id)
);

CREATE TABLE ingresos_extension_conceptos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    CONSTRAINT fk_ingreso_extension_concepto FOREIGN KEY (ingreso_id) REFERENCES ingresos_extension(id) ON DELETE CASCADE
);

CREATE TABLE ingresos_postgrado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    concepto_adicional VARCHAR(200) NOT NULL DEFAULT '',
    valor_adicional DECIMAL(15, 2) NOT NULL DEFAULT 0,
    valor_total DECIMAL(15, 2) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ingreso_postgrado_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id)
);

CREATE TABLE ingresos_postgrado_conceptos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    CONSTRAINT fk_ingreso_postgrado_concepto FOREIGN KEY (ingreso_id) REFERENCES ingresos_postgrado(id) ON DELETE CASCADE
);

CREATE TABLE ingresos_unisalud (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    concepto_adicional VARCHAR(200) NOT NULL DEFAULT '',
    valor_adicional DECIMAL(15, 2) NOT NULL DEFAULT 0,
    valor_total DECIMAL(15, 2) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ingreso_unisalud_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id)
);

CREATE TABLE ingresos_unisalud_conceptos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    CONSTRAINT fk_ingreso_unisalud_concepto FOREIGN KEY (ingreso_id) REFERENCES ingresos_unisalud(id) ON DELETE CASCADE
);
