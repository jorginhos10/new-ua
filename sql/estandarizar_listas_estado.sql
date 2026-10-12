-- Agrega la columna estado (activo/inactivo) a los 5 catálogos del botón "Listas" que todavía no
-- la tenían, para que activar/desactivar quede disponible en los 10 por igual. Todas las filas
-- existentes quedan 'activo' por el DEFAULT, así que no cambia nada de lo que ya se veía.
ALTER TABLE lineas ADD COLUMN estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo' AFTER nombre;
ALTER TABLE motores ADD COLUMN estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo' AFTER linea_id;
ALTER TABLE proyectos ADD COLUMN estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo' AFTER motor_id;
ALTER TABLE estamentos ADD COLUMN estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo' AFTER nombre;
ALTER TABLE sedes ADD COLUMN estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo' AFTER nombre;
