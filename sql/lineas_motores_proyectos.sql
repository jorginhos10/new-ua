-- Catálogo jerárquico: Línea -> Motor -> Proyecto
USE new_ua;

CREATE TABLE IF NOT EXISTS lineas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS motores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    linea_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_motor_linea FOREIGN KEY (linea_id) REFERENCES lineas(id),
    UNIQUE KEY uq_motor_linea_codigo (linea_id, codigo)
);

CREATE TABLE IF NOT EXISTS proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    motor_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_proyecto_motor FOREIGN KEY (motor_id) REFERENCES motores(id),
    UNIQUE KEY uq_proyecto_motor_codigo (motor_id, codigo)
);
