-- Listado de gastos, ligado a la jerarquía Línea -> Motor -> Proyecto
USE new_ua;

CREATE TABLE IF NOT EXISTS gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sede VARCHAR(100) NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    linea_id INT NOT NULL,
    motor_id INT NOT NULL,
    proyecto_id INT NOT NULL,
    objeto_proyecto_paa VARCHAR(200) NOT NULL,
    actividad VARCHAR(255) NOT NULL,
    rubro VARCHAR(255) NOT NULL,
    insumo VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(15, 2) NOT NULL,
    valor_total DECIMAL(15, 2) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gasto_linea FOREIGN KEY (linea_id) REFERENCES lineas(id),
    CONSTRAINT fk_gasto_motor FOREIGN KEY (motor_id) REFERENCES motores(id),
    CONSTRAINT fk_gasto_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos(id)
);
