-- Versiones/snapshot de las pestañas "Techos y Metas" y "Documentos" de Análisis — cada una con su
-- propio mecanismo independiente, mismo patrón que arbol_versiones/arbol_versiones_datos (ver
-- VersionArbol.php). Nunca comparten tabla ni lista con el árbol PDI ni entre sí.
-- EJECUTAR UNA SOLA VEZ.

CREATE TABLE IF NOT EXISTS techos_versiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Una fila por bloque de TechosMetas::BLOQUES ('gastos'/'extension'/'postgrado'/'unisalud').
-- `datos` es un JSON {"arbol": [...árbol institucional de dependencias, sin podar...],
-- "nodosPorAnio": {"2027": [...nodos ya calculados del año vigente...], "2026": [...], ...hasta 5
-- años}, "tope": número|null} — todo institucional (sin filtro de dependencia), exactamente como
-- lo arma construirDatos() antes de aplicar subarbol()/podar(). Con esto, VersionTechos puede
-- reconstruir tanto la vista institucional como cualquier filtro por dependencia después, sin
-- volver a consultar la base de datos (ver VersionTechos::obtenerBloqueDeVersion()).
CREATE TABLE IF NOT EXISTS techos_versiones_datos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    bloque VARCHAR(20) NOT NULL,
    datos LONGTEXT NOT NULL,
    KEY idx_techos_version_bloque (version_id, bloque),
    CONSTRAINT fk_techos_versiones_datos_version FOREIGN KEY (version_id) REFERENCES techos_versiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos_versiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Una sola fila por versión: la lista completa de analisis_presentacion (sin la fila de Actas,
-- que no es un documento) tal como estaba al crear la versión.
CREATE TABLE IF NOT EXISTS documentos_versiones_datos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    datos LONGTEXT NOT NULL,
    CONSTRAINT fk_documentos_versiones_datos_version FOREIGN KEY (version_id) REFERENCES documentos_versiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
