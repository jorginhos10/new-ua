-- Modulo "Jerarquias": arbol de centros de costo (OPL -> Vicerrectoria -> Despacho/Departamentos/
-- Facultades -> Admin.Fac/Programas) con un techo presupuestal opcional por nodo, que debe
-- respetarse: la suma de techos de los hijos activos de un nodo no puede superar su propio techo.
USE new_ua;

CREATE TABLE IF NOT EXISTS jerarquias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    padre_id INT NULL,
    techo DECIMAL(15, 2) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jerarquia_padre FOREIGN KEY (padre_id) REFERENCES jerarquias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
