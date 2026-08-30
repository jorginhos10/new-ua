-- Catálogo de rubros (código, descripción, estado activo/inactivo)
USE new_ua;

DROP TABLE IF EXISTS rubros;

CREATE TABLE rubros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
