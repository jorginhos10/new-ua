-- Nuevo estado "En Expediente" para Archivados (ver plan aprobado el-techo-no-deberia-kind-candle):
-- un registro centralizado, exclusivo del superadmin, distinto de 'archivada' (que sigue siendo
-- privado de la dependencia dueña). No se pierde ningún valor existente, solo se amplía el ENUM.
ALTER TABLE peticiones_archivadas
    MODIFY COLUMN accion ENUM('archivada','aprobada','redireccionada','expediente') NOT NULL DEFAULT 'archivada';
