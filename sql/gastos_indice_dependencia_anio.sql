-- Gasto::obtenerTotalesPropioYComprometidoPorDependencia() (usada en Gastos > index(), la barra de
-- presupuesto propio/heredado/comprometido) hace una subconsulta EXISTS correlacionada que filtra
-- por (anio_presupuestal_id, dependencia) por cada fila candidata — sin índice sobre `dependencia`,
-- MySQL escaneaba linealmente ~800 filas por cada una de las ~1600 filas de gastos (confirmado con
-- EXPLAIN: "DEPENDENT SUBQUERY ... rows=814"), lo que hacía tardar esa sola consulta ~2.9s y toda
-- la página de Gastos ~3s. Este índice compuesto deja que esa búsqueda sea directa en vez de un
-- escaneo, sin cambiar ningún dato ni comportamiento.
ALTER TABLE gastos
    ADD INDEX idx_gastos_anio_dependencia (anio_presupuestal_id, dependencia);
