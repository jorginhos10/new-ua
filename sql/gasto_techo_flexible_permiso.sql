-- Permiso "validación flexible de techo" para Gastos (ver plan aprobado
-- el-techo-no-deberia-kind-candle). Tri-estado por usuario (NULL = hereda el default de su
-- tipo+rol, 1 = forzado activo, 0 = forzado inactivo) + una tabla de defaults por (tipo, rol),
-- mismo espíritu que tipo_dependencia_menu pero para este único booleano.
ALTER TABLE usuarios
    ADD COLUMN techo_flexible TINYINT(1) NULL DEFAULT NULL AFTER menu_personalizado;

CREATE TABLE IF NOT EXISTS tipo_dependencia_techo_flexible (
    id INT(11) NOT NULL AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL,
    rol_id INT(11) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tipo_dependencia_techo_flexible (tipo, rol_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
