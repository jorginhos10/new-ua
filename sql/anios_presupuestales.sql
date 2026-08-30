-- Catálogo de años presupuestales
USE new_ua;

CREATE TABLE IF NOT EXISTS anios_presupuestales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio INT NOT NULL UNIQUE,
    presupuesto DECIMAL(15, 2) NOT NULL DEFAULT 0,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
