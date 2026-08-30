ALTER TABLE necesidades_academicas
    ADD COLUMN vigencia INT NULL AFTER usuario_id,
    ADD COLUMN nombre_necesidad VARCHAR(200) NOT NULL DEFAULT '' AFTER vigencia,
    ADD COLUMN descripcion TEXT NULL AFTER nombre_necesidad,
    ADD COLUMN justificacion TEXT NULL AFTER descripcion,
    ADD COLUMN estamento_solicitante_id INT NULL AFTER justificacion,
    ADD COLUMN beneficiarios_cantidad INT NULL AFTER estamento_solicitante_id,
    ADD COLUMN sede_id INT NULL AFTER sede,
    ADD COLUMN proyecto_pdi_id INT NULL AFTER proyecto_pdi,
    ADD COLUMN responsable_usuario_id INT NULL AFTER responsable;

UPDATE necesidades_academicas n
LEFT JOIN sedes s ON s.nombre = n.sede OR s.codigo = n.sede
SET n.sede_id = s.id
WHERE n.sede_id IS NULL;

ALTER TABLE necesidades_academicas
    ADD CONSTRAINT fk_necesidades_estamento_solicitante FOREIGN KEY (estamento_solicitante_id) REFERENCES estamentos (id),
    ADD CONSTRAINT fk_necesidades_sede FOREIGN KEY (sede_id) REFERENCES sedes (id),
    ADD CONSTRAINT fk_necesidades_proyecto_pdi FOREIGN KEY (proyecto_pdi_id) REFERENCES proyectos (id),
    ADD CONSTRAINT fk_necesidades_responsable_usuario FOREIGN KEY (responsable_usuario_id) REFERENCES usuarios (id);

CREATE TABLE IF NOT EXISTS necesidad_beneficiarios_estamentos (
    necesidad_id INT NOT NULL,
    estamento_id INT NOT NULL,
    PRIMARY KEY (necesidad_id, estamento_id),
    CONSTRAINT fk_necben_necesidad FOREIGN KEY (necesidad_id) REFERENCES necesidades_academicas (id) ON DELETE CASCADE,
    CONSTRAINT fk_necben_estamento FOREIGN KEY (estamento_id) REFERENCES estamentos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
