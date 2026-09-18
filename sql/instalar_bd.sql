DROP DATABASE IF EXISTS new_ua;
CREATE DATABASE IF NOT EXISTS new_ua CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE new_ua;

-- 1) Catálogos base
SOURCE C:/xampp/htdocs/new-ua/sql/roles.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/roles_orden.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/dependencias.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/dependencias_tipo.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/dependencias_flujo_manual.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/dependencias_datos.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/facultades.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/estamentos.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/sedes.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/rubros.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/jerarquias.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/jerarquias_tipo.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/tipo_dependencia_roles.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/lineas_motores_proyectos.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/lineas_sublineas_inversion.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/lineas_sublineas_inversion_descripcion_ampliada.sql;

-- 2) Usuarios y permisos
-- (usuarios_correo.sql NO se incluye: usuarios.sql ya crea la columna `correo` directamente)
SOURCE C:/xampp/htdocs/new-ua/sql/usuarios.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/usuarios_facultad.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/usuarios_estamento.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/usuarios_rol_dependencia.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/usuarios_ultimo_acceso.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/consejo_superior_rol.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/permisos_menu.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/tipo_dependencia_menu_rol_id.sql;

-- 3) Presupuesto / macroeconómicas
SOURCE C:/xampp/htdocs/new-ua/sql/anios_presupuestales.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/variables_macroeconomicas.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/presupuesto_dependencia.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/contratos_presupuesto_version_faltantes.sql;

-- 4) Autogestión (catálogo de ítems y porcentajes)
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_items_modulo.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_items_tope.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_sin_porcentaje.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_porcentajes.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_porcentajes_merge_costos_inversiones.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_porcentajes_split_costos_inversiones.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_porcentajes_nullable.sql;

-- 5) Gastos e ingresos (Extensión / Postgrado / Unidad de Salud / Sin excedentes)
SOURCE C:/xampp/htdocs/new-ua/sql/gastos.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_anio.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_sede_id.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_rubro_id.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_meses.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_areas.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_areas_autogestion_id.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/ingresos_areas.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/ingresos_extension_autogestion_id.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_extension_categoria.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_extension_categoria_sin_excedentes.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_extension_excedentes.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_extension_excedente_flag.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/gastos_extension_tipo_automatico.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_revert_postgrado_unisalud.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/sin_excedentes.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/categoria_peticion_sin_excedentes.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/autogestion_envio_estado.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/replicar_autogestion_3_modulos.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/quitar_autogestion_3_modulos.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/usuario_id_autogestion.sql;

-- 6) Actas, mensajes
-- (actas.sql y actas_menu.sql NO se incluyen: actas_completo.sql ya combina ambos, idempotente)
SOURCE C:/xampp/htdocs/new-ua/sql/actas_completo.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/mensajes.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/mensaje_global.sql;

-- 7) Necesidades académicas (Perfil de proyectos)
SOURCE C:/xampp/htdocs/new-ua/sql/necesidades.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/necesidades_academicas_rediseno.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/necesidades_envio_estado.sql;

SOURCE C:/xampp/htdocs/new-ua/sql/dependencia_destino.sql;

-- 8) Solicitudes (ARL, Monitores, OPS, Petición) y Peticiones
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_arl.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_arl_estado.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_monitores.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_monitores_4tipos.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_ops.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_ops_valor.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_peticiones.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_peticiones_quitar_dependencia.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/solicitudes_enviada_a.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/peticiones_archivadas.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/peticiones_archivadas_accion.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/peticiones_historial.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/rol_destinatario_id_faltante.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/usuario_destinatario_especifico.sql;

-- 9) Varios
SOURCE C:/xampp/htdocs/new-ua/sql/reloj_arena.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/codigos_recuperacion_password.sql;
SOURCE C:/xampp/htdocs/new-ua/sql/snapshots.sql;

CREATE TABLE IF NOT EXISTS `test_db_ok` (id INT PRIMARY KEY);

SELECT 'Base de datos new_ua creada correctamente.' AS estado;
