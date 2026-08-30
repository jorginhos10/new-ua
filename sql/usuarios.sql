-- Base de datos para el sistema de login MVC
CREATE DATABASE IF NOT EXISTS new_ua CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE new_ua;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'invitado') NOT NULL DEFAULT 'invitado',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuario de prueba: usuario "admin" / contraseña "password"
-- El hash fue generado con password_hash('password', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre, usuario, password, rol) VALUES
('Administrador', 'admin', '$2y$10$U/TwCRC6lA/RZNrrdVbQ9ugN42pKopkdm6SIoB63FWv.xdZ60b/6S', 'administrador');
