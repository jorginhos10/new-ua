-- Convocatorias de proyectos (Perfil de proyectos).
-- EJECUTAR UNA SOLA VEZ, después de sql/fuentes_financiacion.sql.
--
-- Reglas que deja la carga inicial:
--  * Una convocatoria por vigencia existente. Si no había reloj de formulador configurado, el rango
--    es amplio (2026-01-01 a 2027-12-31) para no cerrar lo que estaba abierto; el administrador lo ajusta.
--  * Sin dependencias marcadas, la convocatoria no restringe por dependencia.
--  * Todas las fuentes activas quedan habilitadas en cada convocatoria inicial.
--  * fuente_financiacion (texto) se conserva; fuente_financiacion_id y dependencia_id se llenan por nombre.

CREATE TABLE convocatorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    vigencia INT NOT NULL,
    audiencia ENUM('invitado','administrador','ambos') NOT NULL DEFAULT 'ambos',
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_convocatorias_creado_por FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE convocatoria_dependencias (
    convocatoria_id INT NOT NULL,
    dependencia_id INT NOT NULL,
    incluir_descendientes TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (convocatoria_id, dependencia_id),
    CONSTRAINT fk_conv_dep_convocatoria FOREIGN KEY (convocatoria_id) REFERENCES convocatorias (id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_dep_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE convocatoria_fuentes (
    convocatoria_id INT NOT NULL,
    fuente_id INT NOT NULL,
    PRIMARY KEY (convocatoria_id, fuente_id),
    CONSTRAINT fk_conv_fuente_convocatoria FOREIGN KEY (convocatoria_id) REFERENCES convocatorias (id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_fuente_fuente FOREIGN KEY (fuente_id) REFERENCES fuentes_financiacion (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE necesidades_academicas
    ADD COLUMN convocatoria_id INT NULL AFTER id,
    ADD COLUMN fuente_financiacion_id INT NULL AFTER fuente_financiacion,
    ADD COLUMN dependencia_id INT NULL AFTER dependencia,
    ADD CONSTRAINT fk_necesidades_convocatoria FOREIGN KEY (convocatoria_id) REFERENCES convocatorias (id),
    ADD CONSTRAINT fk_necesidades_fuente FOREIGN KEY (fuente_financiacion_id) REFERENCES fuentes_financiacion (id),
    ADD CONSTRAINT fk_necesidades_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencias (id);

-- 1) Una convocatoria por vigencia existente.
INSERT INTO convocatorias (nombre, vigencia, audiencia, fecha_inicio, fecha_cierre, activa)
SELECT CONCAT('Convocatoria ', v.vigencia), v.vigencia, 'ambos', '2026-01-01', '2027-12-31', 1
FROM (SELECT DISTINCT vigencia FROM necesidades_academicas WHERE vigencia IS NOT NULL) v;

-- 2) Cada proyecto a la convocatoria de su vigencia.
UPDATE necesidades_academicas n
JOIN convocatorias c ON c.vigencia = n.vigencia AND c.nombre = CONCAT('Convocatoria ', n.vigencia)
SET n.convocatoria_id = c.id
WHERE n.convocatoria_id IS NULL;

-- 3) Todas las fuentes activas habilitadas en cada convocatoria inicial.
INSERT INTO convocatoria_fuentes (convocatoria_id, fuente_id)
SELECT c.id, f.id
FROM convocatorias c
CROSS JOIN fuentes_financiacion f
WHERE f.estado = 'activo';

-- 4) Fuente y dependencia por nombre (el texto de fuente se conserva).
UPDATE necesidades_academicas n
JOIN fuentes_financiacion f ON f.nombre = TRIM(n.fuente_financiacion)
SET n.fuente_financiacion_id = f.id
WHERE n.fuente_financiacion_id IS NULL;

UPDATE necesidades_academicas n
JOIN dependencias d ON d.nombre = n.dependencia
SET n.dependencia_id = d.id
WHERE n.dependencia_id IS NULL;
