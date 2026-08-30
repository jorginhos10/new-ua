-- Modulo "Solicitudes" (2.2), item 3: OPS - Servicios personales indirectos.
USE new_ua;

CREATE TABLE IF NOT EXISTS solicitudes_ops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    sede_id INT NOT NULL,
    linea_id INT NOT NULL,
    motor_id INT NOT NULL,
    proyecto_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    rubro_id INT NOT NULL,
    perfil ENUM('profesional_especializado', 'profesional_universitario', 'tecnico_administrativo', 'asesor', 'auxiliares_administrativos') NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    observaciones VARCHAR(255) NULL,
    estado ENUM('borrador', 'enviada') NOT NULL DEFAULT 'borrador',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_ops_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales (id),
    CONSTRAINT fk_solicitud_ops_sede FOREIGN KEY (sede_id) REFERENCES sedes (id),
    CONSTRAINT fk_solicitud_ops_linea FOREIGN KEY (linea_id) REFERENCES lineas (id),
    CONSTRAINT fk_solicitud_ops_motor FOREIGN KEY (motor_id) REFERENCES motores (id),
    CONSTRAINT fk_solicitud_ops_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
    CONSTRAINT fk_solicitud_ops_rubro FOREIGN KEY (rubro_id) REFERENCES rubros (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
