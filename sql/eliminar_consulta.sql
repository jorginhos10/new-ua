-- Se retira la página "Consulta" del rol Consejo Superior: la cubren las pestañas de Análisis.
-- Borra las claves de menú 'consulta' de las plantillas y de las personalizaciones.
-- Se puede correr más de una vez.
DELETE FROM tipo_dependencia_menu WHERE menu_key = 'consulta';
DELETE FROM usuario_menu WHERE menu_key = 'consulta';
