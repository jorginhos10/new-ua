-- Elimina el catálogo `facultades` (página Configuraciones > Facultades, ya retirada).
-- Ninguna otra tabla la referencia (sin claves foráneas) ni pantalla la lee. Las facultades que
-- usan los filtros, el registro y la gestión de invitados son las dependencias con tipo 'Facultad'.
DROP TABLE IF EXISTS facultades;
