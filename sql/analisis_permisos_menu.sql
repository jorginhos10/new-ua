-- Pestañas de Análisis en los permisos de menú. Valor por defecto para lo que ya existe:
-- Articulación PDI y Techos y Metas (ver AccesoAnalisis::PESTANAS_POR_DEFECTO). Después se ajustan
-- desde Jerarquías (plantilla por tipo) y Usuarios > Permisos (personalizados).
-- No cambia el esquema. Se puede correr más de una vez: cada inserción comprueba que no exista.

INSERT INTO tipo_dependencia_menu (tipo, rol_id, menu_key)
SELECT p.tipo, p.rol_id, 'analisis_pdi'
FROM (SELECT DISTINCT tipo, rol_id FROM tipo_dependencia_menu) p
WHERE NOT EXISTS (
    SELECT 1 FROM tipo_dependencia_menu m
    WHERE m.tipo = p.tipo AND m.rol_id <=> p.rol_id AND m.menu_key = 'analisis_pdi'
);

INSERT INTO tipo_dependencia_menu (tipo, rol_id, menu_key)
SELECT p.tipo, p.rol_id, 'analisis_techos'
FROM (SELECT DISTINCT tipo, rol_id FROM tipo_dependencia_menu) p
WHERE NOT EXISTS (
    SELECT 1 FROM tipo_dependencia_menu m
    WHERE m.tipo = p.tipo AND m.rol_id <=> p.rol_id AND m.menu_key = 'analisis_techos'
);

INSERT INTO usuario_menu (usuario_id, menu_key)
SELECT u.id, 'analisis_pdi'
FROM usuarios u
WHERE u.menu_personalizado = 1
  AND NOT EXISTS (SELECT 1 FROM usuario_menu um WHERE um.usuario_id = u.id AND um.menu_key = 'analisis_pdi');

INSERT INTO usuario_menu (usuario_id, menu_key)
SELECT u.id, 'analisis_techos'
FROM usuarios u
WHERE u.menu_personalizado = 1
  AND NOT EXISTS (SELECT 1 FROM usuario_menu um WHERE um.usuario_id = u.id AND um.menu_key = 'analisis_techos');
