-- Versiones (snapshots) del presupuesto institucional, una lista independiente por lado
-- (Egresos/Ingresos nunca comparten versiones) — pestaña "Programación presupuestal" en modo
-- Repositorio de ?ruta=analisis. Mismo espíritu que arbol_versiones.sql (VersionArbol) pero
-- acotado a las 3 tablas presupuesto_institucional_* en vez de gastos/lineas/motores/proyectos.
-- Ver plan el-techo-no-deberia-kind-candle.
CREATE TABLE IF NOT EXISTS presupuesto_institucional_versiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    creado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS presupuesto_institucional_versiones_datos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    datos LONGTEXT NOT NULL,
    FOREIGN KEY (version_id) REFERENCES presupuesto_institucional_versiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
