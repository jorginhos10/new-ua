-- Plantillas "Definir" para las filas automáticas de Excedentes/Contribución a posgrado: en vez de
-- las constantes fijas (AUTOMATICO_SEDE_ID, AUTOMATICO_PROYECTO_ID, AUTOMATICO_RUBRO_TEXTO, etc.)
-- compartidas hoy por todo el sistema, cada ítem de Autogestión (Extensión/Postgrado) o el módulo
-- completo (Unisalud, sin ítems propios) puede tener su propia definición, por dependencia. Si
-- `dependencia` es NULL, es un default global que solo el superadmin puede crear/reasignar; un
-- usuario normal que la deja en blanco al definir queda fijado a su propia dependencia (resuelto en
-- el controlador, no aquí). Sin UNIQUE KEY por los NULLs de autogestion_id/dependencia — la
-- unicidad la valida el controlador con un SELECT explícito antes de insertar/actualizar. Ver plan
-- aprobado el-techo-no-deberia-kind-candle.
CREATE TABLE IF NOT EXISTS autogestion_automatico_definiciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo VARCHAR(20) NOT NULL,
    autogestion_id INT NULL,
    tipo VARCHAR(20) NOT NULL,
    dependencia VARCHAR(150) NULL,
    sede_id INT NOT NULL,
    proyecto_id INT NOT NULL,
    rubro_id INT NOT NULL,
    actividad VARCHAR(255) NOT NULL,
    insumo VARCHAR(200) NOT NULL,
    meses VARCHAR(150) NOT NULL,
    creado_por INT NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lookup (modulo, autogestion_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
