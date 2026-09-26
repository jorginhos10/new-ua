-- Permisos delegados sobre filas automáticas (Excedentes/Contribución a posgrado) de Autogestión:
-- por defecto solo el superadmin raíz puede editar/borrar una fila automática (tipo_automatico NOT
-- NULL); esta tabla deja que el superadmin le otorgue ese mismo poder a usuarios puntuales, acotado
-- a un (modulo, tipo) concreto — ej. para que el administrador de Posgrado pueda reagrupar entre
-- dependencias todas las filas de "Contribución a posgrado". Empieza vacía. Ver plan aprobado
-- el-techo-no-deberia-kind-candle.
CREATE TABLE IF NOT EXISTS autogestion_automatico_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo VARCHAR(20) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    usuario_id INT NOT NULL,
    otorgado_por INT NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_modulo_tipo_usuario (modulo, tipo, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
