-- Varios mensajes globales (cada uno es una diapositiva en el inicio de los administradores).
-- Reemplaza el registro único de mensaje_global (que se conserva como respaldo, sin uso).
-- El primer mensaje toma el contenido actual, si hay alguno. Se puede correr más de una vez.

CREATE TABLE IF NOT EXISTS mensajes_globales (
    id INT NOT NULL AUTO_INCREMENT,
    contenido TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    actualizado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mensajes_globales_orden (orden),
    CONSTRAINT fk_mensajes_globales_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mensajes_globales (contenido, orden, actualizado_por)
SELECT mg.contenido, 1, mg.actualizado_por
FROM mensaje_global mg
WHERE mg.id = 1
  AND mg.contenido IS NOT NULL
  AND mg.contenido <> ''
  AND NOT EXISTS (SELECT 1 FROM mensajes_globales);
