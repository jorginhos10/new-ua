-- Estado por defecto (desplegado/recogido) de las listas de las 3 pestañas árbol de ?ruta=analisis
-- (Articulación PDI, Programación presupuestal, Proyectos). Lo configura solo el SA
-- (usuarios.es_super_admin) y aplica a todos los que abran la página. Sin fila = desplegado.
CREATE TABLE IF NOT EXISTS analisis_arbol_configuracion (
    pestana VARCHAR(20) NOT NULL PRIMARY KEY,
    expandido TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_por INT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
