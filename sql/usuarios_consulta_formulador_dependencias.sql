-- Consulta manda arriba de toda la cadena de aprobación
UPDATE roles SET orden = 0 WHERE nombre = 'Consulta';

-- Nueva dependencia raíz para Consejo Superior (código provisorio, editable luego desde
-- Configuraciones > Dependencias igual que cualquier otra)
INSERT INTO dependencias (codigo, nombre, tipo, flujo_id, no_monetizable, no_listar, es_raiz_superadmin)
VALUES ('CONSEJOSUP', 'CONSEJO SUPERIOR', 'Consejo Superior', NULL, 0, 0, 0);

-- Nuevo nodo raíz en Jerarquías (para el Mapa), mismo "tipo" que la dependencia de arriba
INSERT INTO jerarquias (nombre, padre_id, tipo) VALUES ('Consejo Superior', NULL, 'Consejo Superior');

-- Saca "formuladores" de debajo de Facultades y lo sube a su propia sección raíz del Mapa
UPDATE jerarquias SET padre_id = NULL WHERE id = 41;

-- Asigna rol_id + dependencia_id a las cuentas existentes de cada tipo
UPDATE usuarios SET
    rol_id = (SELECT id FROM roles WHERE nombre = 'Consulta'),
    dependencia_id = (SELECT id FROM dependencias WHERE tipo = 'Consejo Superior' LIMIT 1)
  WHERE rol = 'consejo_superior';

UPDATE usuarios SET
    rol_id = (SELECT id FROM roles WHERE nombre = 'Formulador'),
    dependencia_id = (SELECT id FROM dependencias WHERE tipo = 'Formulador' LIMIT 1)
  WHERE rol = 'invitado';
