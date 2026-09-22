-- Estas 5 tablas las usan modelo/ContratoComun.php, modelo/RubroCategoria.php,
-- modelo/PresupuestoVersion.php y modelo/RelojArenaFormulador.php, pero nunca tuvieron un
-- archivo .sql propio en este directorio (no se llegaron a versionar cuando se crearon).
USE new_ua;

CREATE TABLE IF NOT EXISTS contratos_comunes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rubro_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cap VARCHAR(5) NOT NULL,
    seccion VARCHAR(5) NOT NULL,
    autogestion TINYINT(1) NOT NULL DEFAULT 0,
    egresos TINYINT(1) NOT NULL DEFAULT 0,
    proyectos TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rubro_categoria_cap_seccion (cap, seccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS presupuesto_version (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    usuario_id INT NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_presupuesto_version_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id),
    CONSTRAINT fk_presupuesto_version_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS presupuesto_version_cambio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    dependencia_id INT NOT NULL,
    minimo_anterior DECIMAL(15, 2) NULL,
    minimo_nuevo DECIMAL(15, 2) NULL,
    techo_anterior DECIMAL(15, 2) NULL,
    techo_nuevo DECIMAL(15, 2) NULL,
    CONSTRAINT fk_presupuesto_version_cambio_version FOREIGN KEY (version_id) REFERENCES presupuesto_version(id) ON DELETE CASCADE,
    CONSTRAINT fk_presupuesto_version_cambio_dependencia FOREIGN KEY (dependencia_id) REFERENCES dependencias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reloj_arena_formulador (
    id INT PRIMARY KEY DEFAULT 1,
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
