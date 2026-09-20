ALTER TABLE roles
  ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#0071e3' AFTER orden;

-- Colores distintos de entrada para los roles del flujo de aprobación (Gestor -> Avalador ->
-- Formulador), para poder distinguirlos de un vistazo por el color del icono cuando comparten
-- nombre de persona en listas/selectores.
UPDATE roles SET color = '#ff9500' WHERE nombre = 'Gestor';
UPDATE roles SET color = '#34c759' WHERE nombre = 'Avalador';
UPDATE roles SET color = '#0071e3' WHERE nombre = 'Formulador';
