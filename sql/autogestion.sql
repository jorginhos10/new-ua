-- Catálogo de ítems de Autogestión (porcentajes de gastos, excedentes e inversiones)
USE new_ua;

CREATE TABLE IF NOT EXISTS autogestion_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    porcentaje DECIMAL(5, 2) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
