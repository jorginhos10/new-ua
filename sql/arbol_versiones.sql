-- Versiones ligeras del árbol PDI (Línea/Motor/Proyecto/Gasto), para el modo "Repositorio" de las
-- pestañas Articulación PDI y Programación presupuestal de la página ?ruta=analisis (solo SA).
-- A diferencia de modelo/Snapshot.php (copia TODA la base de datos, usado por ?ruta=repositorios),
-- esta guarda solo las tablas que el árbol necesita (gastos, lineas, motores, proyectos,
-- anios_presupuestales) — mucho más liviano. Cada pestaña (Articulación PDI / Programación
-- presupuestal) tiene su propia lista de versiones vía la columna `pestana`, aunque comparten esta
-- misma tabla/mecanismo. Ver plan aprobado el-techo-no-deberia-kind-candle.
CREATE TABLE IF NOT EXISTS arbol_versiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pestana ENUM('articulacion_pdi', 'programacion_presupuestal') NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    creado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pestana (pestana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS arbol_versiones_datos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    tabla VARCHAR(60) NOT NULL,
    datos LONGTEXT NOT NULL,
    FOREIGN KEY (version_id) REFERENCES arbol_versiones(id) ON DELETE CASCADE,
    INDEX idx_version_tabla (version_id, tabla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
