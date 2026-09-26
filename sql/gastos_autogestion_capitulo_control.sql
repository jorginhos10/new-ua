-- Marca de control (no cambia el catálogo de rubros): cuando al importar la plantilla una fila de
-- Gastos tiene Categoría "Inversiones" pero el Rubro elegido es de capítulo "2" (funcionamiento —
-- no existen todavía rubros de capítulo "4" en el catálogo), se guarda aquí '4' como el capítulo
-- efectivo para control/reporte, sin tocar `rubro_id` (sigue apuntando al mismo rubro "2.xx", mismo
-- nombre) ni crear ningún rubro nuevo. NULL = sin marca, el capítulo real del rubro es el que aplica.
-- Ver plan aprobado el-techo-no-deberia-kind-candle.
ALTER TABLE gastos_extension ADD COLUMN capitulo_control VARCHAR(5) NULL;
ALTER TABLE gastos_postgrado ADD COLUMN capitulo_control VARCHAR(5) NULL;
ALTER TABLE gastos_unisalud ADD COLUMN capitulo_control VARCHAR(5) NULL;
