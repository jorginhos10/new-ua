-- Presupuesto institucional completo (nómina, obligaciones, inversión y demás capítulos que hoy
-- no capturaba la plataforma), cargado por plantilla Excel para la pestaña "Programación
-- presupuestal" de ?ruta=analisis (solo SA). Estructura jerárquica propia (código de puntos,
-- ej. '1.0'/'1.1.0'/'1.1.1.0'...), independiente de Línea/Motor/Proyecto — dos lados (Ingresos y
-- Egresos), cada uno numerado desde su propio '1.0'. Ver plan el-techo-no-deberia-kind-candle.
CREATE TABLE IF NOT EXISTS presupuesto_institucional_lineas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    es_total BOOLEAN NOT NULL DEFAULT 0,
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    orden INT NOT NULL DEFAULT 0,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    actualizado_por INT NULL,
    UNIQUE KEY uniq_tipo_codigo (tipo, codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS presupuesto_institucional_valores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    linea_id INT NOT NULL,
    anio INT NOT NULL,
    valor_final DECIMAL(14,2) NOT NULL DEFAULT 0,
    valor_corte DECIMAL(14,2) NULL,
    fecha_corte DATE NULL,
    UNIQUE KEY uniq_linea_anio (linea_id, anio),
    FOREIGN KEY (linea_id) REFERENCES presupuesto_institucional_lineas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS presupuesto_institucional_linea_proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    linea_id INT NOT NULL,
    proyecto_id INT NOT NULL,
    UNIQUE KEY uniq_linea_proyecto (linea_id, proyecto_id),
    FOREIGN KEY (linea_id) REFERENCES presupuesto_institucional_lineas(id) ON DELETE CASCADE,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
