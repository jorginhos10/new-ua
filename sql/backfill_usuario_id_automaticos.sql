-- Backfill de usuario_id en filas automáticas de Autogestión (Excedentes, Contribución a
-- posgrado) que quedaron con usuario_id NULL — generadas antes de que
-- regenerarAutomaticosDeIngreso() empezara a copiar el usuario_id del ingreso que las origina.
-- Sin usuario_id, esas filas caían en el filtro de respaldo "visible a un ancestro de su
-- dependencia" (filtrarPorPropietarioODestinatario()), mostrándolas a cualquier usuario cuya
-- dependencia fuera ancestro de la dueña (ej. el superadmin raíz), sin importar permisos
-- delegados ni el modo Auditar — y sumándolas de más en la barra de presupuesto/techo del
-- landing de quien las veía.
--
-- Resuelve el usuario_id copiándolo del ingreso que generó la fila automática (mismo ingreso_id).
-- Es seguro correr más de una vez: solo toca filas con usuario_id IS NULL.

UPDATE gastos_extension g
JOIN ingresos_extension i ON g.ingreso_id = i.id
SET g.usuario_id = i.usuario_id
WHERE g.usuario_id IS NULL AND g.tipo_automatico IS NOT NULL AND i.usuario_id IS NOT NULL;

UPDATE gastos_postgrado g
JOIN ingresos_postgrado i ON g.ingreso_id = i.id
SET g.usuario_id = i.usuario_id
WHERE g.usuario_id IS NULL AND g.tipo_automatico IS NOT NULL AND i.usuario_id IS NOT NULL;

UPDATE gastos_unisalud g
JOIN ingresos_unisalud i ON g.ingreso_id = i.id
SET g.usuario_id = i.usuario_id
WHERE g.usuario_id IS NULL AND g.tipo_automatico IS NOT NULL AND i.usuario_id IS NOT NULL;

UPDATE gastos_sin_excedentes g
JOIN ingresos_sin_excedentes i ON g.ingreso_id = i.id
SET g.usuario_id = i.usuario_id
WHERE g.usuario_id IS NULL AND g.tipo_automatico IS NOT NULL AND i.usuario_id IS NOT NULL;

-- Después de correr lo anterior, revisar manualmente si queda alguna fila automática con
-- usuario_id NULL (huérfana, sin ingreso_id o con el ingreso ya eliminado):
--   SELECT id, dependencia, autogestion_id, tipo_automatico, valor_total
--   FROM gastos_extension WHERE usuario_id IS NULL AND tipo_automatico IS NOT NULL;
--   -- (repetir para gastos_postgrado / gastos_unisalud / gastos_sin_excedentes)
-- y asignarle el usuario_id de otro ingreso de la misma dependencia + autogestion_id, si hay uno
-- consistente (mismo criterio usado en local para el caso de FACULTAD DE ARQUITECTURA).
