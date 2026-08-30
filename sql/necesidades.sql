-- Tabla para el formulario de recolección de necesidades de la comunidad académica
USE new_ua;

CREATE TABLE IF NOT EXISTS necesidades_academicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    linea_inversion VARCHAR(150) NOT NULL,
    sublinea_inversion VARCHAR(150) NOT NULL,
    detalle_inversion VARCHAR(150),
    sede VARCHAR(100) NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    programa_academico VARCHAR(150),
    proyecto_pdi VARCHAR(50),
    articulacion_plan VARCHAR(200),
    espacio_intervenir VARCHAR(100),
    requisitos_normativos VARCHAR(200),
    valor DECIMAL(15, 2) NOT NULL,
    fuente_financiacion VARCHAR(150) NOT NULL,
    responsable VARCHAR(150) NOT NULL,
    observaciones TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_necesidad_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
