ALTER TABLE peticiones_archivadas
    ADD COLUMN accion ENUM('archivada','aprobada') NOT NULL DEFAULT 'archivada' AFTER origen_id,
    ADD COLUMN ruta_origen VARCHAR(255) NULL AFTER ruta_ver;
