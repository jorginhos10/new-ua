-- Categoría de Peticiones (no es una dependencia real) que decide si un envío de Sin Excedente
-- debe listarse en la bandeja Extensión ('extension') o Postgrado ('postgrado') dentro de Peticiones.
ALTER TABLE gastos_sin_excedentes ADD COLUMN categoria_peticion VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE ingresos_sin_excedentes ADD COLUMN categoria_peticion VARCHAR(20) NULL DEFAULT NULL;
