-- =============================================================================================
-- Consolidado de los 5 cambios pendientes de aplicar en producción (ver
-- CONTROL_CAMBIOS_PENDIENTES.md para el detalle/motivo de cada uno). Aplica, en orden:
--   1) roles_color.sql                                    — color de icono por rol
--   2) usuarios_consulta_formulador_dependencias.sql      — rol_id/dependencia de Consulta/Formulador
--   3) usuarios_invitados_dependencia_facultad_real.sql   — Invitados: dependencia = su Facultad real
--   4) autogestion_items_porcentajes.sql                  — % Costos/Inversiones/Excedentes por ítem (Extensión)
--   5) postgrado_autogestion_items.sql                    — ítems de Autogestión propios para Postgrado
--   6) autogestion_tope_por_modulo.sql                    — tope único por módulo (ya no por ítem)
--
-- Escrito para poder ejecutarse de una sola vez sobre producción SIN saber de antemano si algún
-- paso ya quedó aplicado a medias (por ejemplo, si una corrida anterior se cortó a la mitad):
-- cada ALTER usa IF [NOT] EXISTS, y cada INSERT/UPDATE valida antes si hace falta, sin pisar
-- datos que ya se hayan corregido a mano (colores de rol editados en Configuraciones > Roles,
-- dependencias de Invitados corregidas en Usuarios > Formulador > Editar, etc. — ver el
-- comentario de cada paso). Aun así, se recomienda respaldar la base de datos antes de correrlo.
--
-- Los pasos 1-3 SÍ se pensaron para una corrida única de puesta al día (no para repetirse como
-- mantenimiento rutinario): son correctos de volver a correr si la corrida se interrumpe a medio
-- camino, pero no están pensados para ejecutarse una y otra vez indefinidamente una vez que
-- producción ya está al día y la gente empieza a editar esos datos a mano.
--
-- NO trae "USE <basededatos>;" a propósito — conéctate primero a la base de datos de producción
-- correcta y corre este archivo completo tal cual, en una sola sesión.
-- =============================================================================================


-- ------------------------------------------------------------------------------------------
-- 1) Color de icono por rol
-- ------------------------------------------------------------------------------------------

ALTER TABLE roles
    ADD COLUMN IF NOT EXISTS color VARCHAR(7) NOT NULL DEFAULT '#0071e3' AFTER orden;

-- Crea el rol "Consulta" si todavía no existe (en local se creó a mano desde Configuraciones >
-- Roles antes de que existiera este script — producción probablemente también lo necesita desde
-- cero). orden=0 para que quede primero en la cadena de aprobación, igual que ya lo pone el paso 2.
INSERT INTO roles (nombre, orden, color)
SELECT 'Consulta', 0, '#919191'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre = 'Consulta');

-- Colores de entrada distintos por rol — solo si el rol sigue en el azul por defecto recién
-- agregado (si ya se personalizó el color desde Configuraciones > Roles, no se toca). Valores
-- tomados de los que quedaron configurados en local a la fecha de este script, no de la semilla
-- original de roles_color.sql (que ya no aplica, ver la nota de esa entrada en
-- CONTROL_CAMBIOS_PENDIENTES.md).
UPDATE roles SET color = '#d17a00' WHERE nombre = 'Gestor' AND color = '#0071e3';
UPDATE roles SET color = '#9cabc4' WHERE nombre = 'Formulador' AND color = '#0071e3';


-- ------------------------------------------------------------------------------------------
-- 2) Consulta/Formulador: rol_id + dependencia para Consejo Superior e Invitados
-- ------------------------------------------------------------------------------------------

UPDATE roles SET orden = 0 WHERE nombre = 'Consulta';

INSERT INTO dependencias (codigo, nombre, tipo, flujo_id, no_monetizable, no_listar, es_raiz_superadmin)
SELECT 'CONSEJOSUP', 'CONSEJO SUPERIOR', 'Consejo Superior', NULL, 0, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM dependencias WHERE tipo = 'Consejo Superior');

INSERT INTO jerarquias (nombre, padre_id, tipo)
SELECT 'Consejo Superior', NULL, 'Consejo Superior'
WHERE NOT EXISTS (SELECT 1 FROM jerarquias WHERE tipo = 'Consejo Superior');

-- Saca el nodo de Formulador de debajo de Facultades y lo sube a su propia sección raíz del Mapa
-- — identificado por tipo, no por id (el id en local, 41, no tiene por qué coincidir en
-- producción). Ya es correcto de re-correr: la segunda vez padre_id ya está en NULL y no
-- selecciona ninguna fila.
UPDATE jerarquias SET padre_id = NULL WHERE tipo = 'Formulador' AND padre_id IS NOT NULL;

-- Solo llena rol_id/dependencia_id si todavía están vacíos, para no pisar una corrección manual
-- posterior (ver el formulario de Editar en Usuarios > Consulta/Formulador).
UPDATE usuarios SET rol_id = (SELECT id FROM roles WHERE nombre = 'Consulta')
    WHERE rol = 'consejo_superior' AND rol_id IS NULL;
UPDATE usuarios SET dependencia_id = (SELECT id FROM dependencias WHERE tipo = 'Consejo Superior' LIMIT 1)
    WHERE rol = 'consejo_superior' AND dependencia_id IS NULL;

UPDATE usuarios SET rol_id = (SELECT id FROM roles WHERE nombre = 'Formulador')
    WHERE rol = 'invitado' AND rol_id IS NULL;
UPDATE usuarios SET dependencia_id = (SELECT id FROM dependencias WHERE tipo = 'Formulador' LIMIT 1)
    WHERE rol = 'invitado' AND dependencia_id IS NULL;


-- ------------------------------------------------------------------------------------------
-- 3) Invitados: dependencia_id apunta a su Facultad real (columna de texto "facultad"), no a
--    la dependencia genérica compartida "FORMULADOR"
-- ------------------------------------------------------------------------------------------

-- Este UPDATE solo afecta filas donde el texto de "facultad" coincide EXACTO con una dependencia
-- tipo Facultad real — si un administrador ya corrigió a mano un caso que no coincidía (ver nota
-- de abajo), ese caso sigue sin coincidir aquí tampoco, así que no se toca. Seguro de re-correr.
UPDATE usuarios u
JOIN dependencias d ON d.nombre = u.facultad AND d.tipo = 'Facultad'
SET u.dependencia_id = d.id
WHERE u.rol = 'invitado';

-- Solo limpia a NULL si sigue en el placeholder genérico "Formulador" (nunca se corrigió a
-- mano) — si un administrador ya le asignó una dependencia real desde Usuarios > Formulador >
-- Editar (para los casos donde el texto de "facultad" no coincidió con ninguna dependencia
-- real), esta corrida no la revierte.
UPDATE usuarios
SET dependencia_id = NULL
WHERE rol = 'invitado'
    AND (facultad IS NULL OR facultad = '')
    AND dependencia_id = (SELECT id FROM dependencias WHERE tipo = 'Formulador' LIMIT 1);


-- ------------------------------------------------------------------------------------------
-- 4) % de Costos/Inversiones/Excedentes por ítem de Autogestión (Extensión)
-- ------------------------------------------------------------------------------------------

ALTER TABLE autogestion_items
    ADD COLUMN IF NOT EXISTS costos DECIMAL(5, 2) NULL AFTER tope,
    ADD COLUMN IF NOT EXISTS inversiones DECIMAL(5, 2) NULL AFTER costos,
    ADD COLUMN IF NOT EXISTS excedentes DECIMAL(5, 2) NULL AFTER inversiones;

-- Semilla: copia el % que tenía el módulo "extension" a cada uno de sus ítems, pero SOLO si
-- ese ítem todavía no tiene nada configurado (para no pisar ajustes ya hechos a mano si este
-- script se corre después de que alguien ya haya usado la pantalla de Autogestión).
UPDATE autogestion_items ai
JOIN autogestion_porcentajes ap ON ap.modulo = ai.modulo
SET ai.costos = ap.costos, ai.inversiones = ap.inversiones, ai.excedentes = ap.excedentes
WHERE ai.modulo = 'extension'
    AND ai.costos IS NULL AND ai.inversiones IS NULL AND ai.excedentes IS NULL;


-- ------------------------------------------------------------------------------------------
-- 5) Postgrado: ítems de Autogestión propios (clonado del patrón de Extensión)
-- ------------------------------------------------------------------------------------------

ALTER TABLE autogestion_items
    ADD COLUMN IF NOT EXISTS contribucion_postgrado DECIMAL(5, 2) NULL AFTER excedentes;

-- Ítem "General": solo se crea si Postgrado todavía no tiene ningún ítem (evita duplicarlo si
-- el script se corre dos veces, o si ya se creó alguno manualmente).
INSERT INTO autogestion_items (nombre, modulo, costos, inversiones, excedentes, contribucion_postgrado)
SELECT 'General', 'postgrado', ap.costos, ap.inversiones, ap.excedentes, ap.contribucion_postgrado
FROM autogestion_porcentajes ap
WHERE ap.modulo = 'postgrado'
    AND NOT EXISTS (SELECT 1 FROM autogestion_items ai WHERE ai.modulo = 'postgrado');

ALTER TABLE ingresos_postgrado
    ADD COLUMN IF NOT EXISTS autogestion_id INT NULL AFTER anio_presupuestal_id;

UPDATE ingresos_postgrado
SET autogestion_id = (SELECT id FROM autogestion_items WHERE modulo = 'postgrado' AND nombre = 'General' LIMIT 1)
WHERE autogestion_id IS NULL;

ALTER TABLE ingresos_postgrado
    MODIFY COLUMN autogestion_id INT NOT NULL;

SET @fk_ingreso_pg_existe = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'ingresos_postgrado'
        AND CONSTRAINT_NAME = 'fk_ingreso_postgrado_autogestion'
);
SET @sql_fk_ingreso_pg = IF(@fk_ingreso_pg_existe = 0,
    'ALTER TABLE ingresos_postgrado ADD CONSTRAINT fk_ingreso_postgrado_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id)',
    'SELECT 1'
);
PREPARE stmt_fk_ingreso_pg FROM @sql_fk_ingreso_pg;
EXECUTE stmt_fk_ingreso_pg;
DEALLOCATE PREPARE stmt_fk_ingreso_pg;

ALTER TABLE gastos_postgrado
    ADD COLUMN IF NOT EXISTS autogestion_id INT NULL AFTER rubro_texto;

UPDATE gastos_postgrado
SET autogestion_id = (SELECT id FROM autogestion_items WHERE modulo = 'postgrado' AND nombre = 'General' LIMIT 1)
WHERE autogestion_id IS NULL;

ALTER TABLE gastos_postgrado
    MODIFY COLUMN autogestion_id INT NOT NULL;

SET @fk_gasto_pg_existe = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'gastos_postgrado'
        AND CONSTRAINT_NAME = 'fk_gasto_postgrado_autogestion'
);
SET @sql_fk_gasto_pg = IF(@fk_gasto_pg_existe = 0,
    'ALTER TABLE gastos_postgrado ADD CONSTRAINT fk_gasto_postgrado_autogestion FOREIGN KEY (autogestion_id) REFERENCES autogestion_items(id)',
    'SELECT 1'
);
PREPARE stmt_fk_gasto_pg FROM @sql_fk_gasto_pg;
EXECUTE stmt_fk_gasto_pg;
DEALLOCATE PREPARE stmt_fk_gasto_pg;


-- ------------------------------------------------------------------------------------------
-- 6) Tope único por módulo (Extensión/Postgrado), ya no por ítem
-- ------------------------------------------------------------------------------------------

ALTER TABLE autogestion_porcentajes
    ADD COLUMN IF NOT EXISTS tope DECIMAL(15, 2) NULL AFTER modulo;

-- Semilla: suma de los topes que tuvieran los ítems activos de cada módulo en ese momento
-- (normalmente 0/NULL si nadie había configurado nada por ítem todavía). Solo si el módulo
-- todavía no tiene un tope propio configurado, para no pisar un valor ya guardado a mano. La
-- columna autogestion_items.tope se consulta con SQL dinámico (en vez de un UPDATE directo)
-- porque el propio paso de abajo la elimina: si este script se interrumpe justo después de
-- borrarla y se vuelve a correr desde el principio, un UPDATE directo fallaría con "columna
-- desconocida" — así, en cambio, ese paso simplemente se salta si la columna ya no existe.
SET @tope_col_existe = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'autogestion_items' AND COLUMN_NAME = 'tope'
);
SET @sql_seed_tope = IF(@tope_col_existe > 0,
    "UPDATE autogestion_porcentajes ap SET ap.tope = (SELECT NULLIF(SUM(ai.tope), 0) FROM autogestion_items ai WHERE ai.modulo = ap.modulo AND ai.estado = 'activo') WHERE ap.modulo IN ('extension', 'postgrado') AND ap.tope IS NULL",
    'SELECT 1'
);
PREPARE stmt_seed_tope FROM @sql_seed_tope;
EXECUTE stmt_seed_tope;
DEALLOCATE PREPARE stmt_seed_tope;

ALTER TABLE autogestion_items
    DROP COLUMN IF EXISTS tope;
