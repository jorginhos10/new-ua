-- Snapshot congelado de un "Enviar": cada clic de Enviar en Gastos o en Autogestión
-- (Extensión/Postgrado/Unisalud/SinExcedentes) crea un lote versionado (v1, v2... por
-- dependencia/año/origen) con una foto de las filas enviadas en ese momento, más el total del lote
-- y el techo presupuestal vigente en ese momento (fijo para Gastos, o el desglose por categoría para
-- Autogestión). Las ediciones posteriores de esas filas NO modifican esta foto. Ver plan aprobado
-- el-techo-no-deberia-kind-candle.
CREATE TABLE IF NOT EXISTS envios_lote (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(30) NOT NULL,
    anio_presupuestal_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    -- Sub-alcance de la numeración de versiones dentro de un mismo módulo/dependencia/año:
    -- 'autogestion:<id>' en Extensión/Postgrado (cada ítem de autogestión se envía por separado y
    -- lleva su propio v1, v2...); NULL en Gastos/Unisalud/SinExcedentes.
    ambito VARCHAR(60) NULL,
    version INT NOT NULL,
    enviado_por INT NULL,
    enviado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rol_destinatario_id INT NULL,
    usuario_destinatario_id INT NULL,
    total_lote DECIMAL(14,2) NOT NULL DEFAULT 0,
    techo_numero DECIMAL(14,2) NULL,
    techo_flexible_activo TINYINT(1) NOT NULL DEFAULT 0,
    techo_categorias JSON NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    INDEX idx_origen_dependencia_anio (origen, dependencia, anio_presupuestal_id, ambito)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS envios_lote_filas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lote_id INT NOT NULL,
    origen_fila_id INT NOT NULL,
    datos_json LONGTEXT NOT NULL,
    FOREIGN KEY (lote_id) REFERENCES envios_lote(id) ON DELETE CASCADE,
    INDEX idx_origen_fila (origen_fila_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
