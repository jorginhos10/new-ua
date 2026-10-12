-- Siembra la fila de "Actas" dentro de analisis_presentacion (ver AnalisisPresentacion::URL_ACTAS):
-- así su tarjeta entra al mismo orden que los documentos y se puede mover con las mismas flechas
-- ▲▼ — al final (orden más alto), que es donde se prefiere por defecto. Idempotente: si ya existe
-- una fila con esa url, no hace nada.
-- EJECUTAR UNA SOLA VEZ.

INSERT INTO analisis_presentacion (nombre, url, orden, actualizado_por)
SELECT 'Actas', 'actas:interno', (SELECT COALESCE(MAX(orden), 0) + 1 FROM analisis_presentacion), NULL
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM analisis_presentacion WHERE url = 'actas:interno'
);
