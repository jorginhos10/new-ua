-- tipo_dependencia_menu se creó sin `rol_id` (solo tipo + menu_key), pero modelo/MenuPermiso.php
-- y sql/actas_completo.sql ya lo usan (tipo, rol_id, menu_key) desde hace tiempo. Sin esta columna
-- la instalación limpia fallaba con "Unknown column 'rol_id'".
-- La UNIQUE KEY (tipo, menu_key) original de permisos_menu.sql ya no aplica: con rol_id, dos filas
-- pueden compartir tipo+menu_key (una para "cualquier rol" con rol_id NULL, otra para un rol
-- puntual). sql/actas_completo.sql ya asume que no existe esa llave única (ver su comentario) y
-- usa WHERE NOT EXISTS para evitar duplicados a nivel de aplicación.
USE new_ua;

ALTER TABLE tipo_dependencia_menu ADD COLUMN rol_id INT NULL AFTER tipo;
ALTER TABLE tipo_dependencia_menu DROP INDEX tipo_menu;
