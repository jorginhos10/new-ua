-- Corrección: los Invitados (rol Formulador) necesitan su dependencia_id apuntando a la Facultad
-- real que eligieron al registrarse (columna de texto "facultad"), no a la dependencia genérica
-- compartida "FORMULADOR" (tipo=Formulador) — porque el envío de su necesidad debe poder
-- enrutarse a un administrador Gestor de esa misma Facultad. En producción, para cada usuario con
-- rol='invitado', ubicar la dependencia cuyo nombre coincide con su columna "facultad" y usar su
-- id; si "facultad" está vacío/NULL (registros viejos sin ese dato), dejar dependencia_id en NULL
-- para que un administrador la asigne a mano desde Usuarios > Formulador > Editar.
UPDATE usuarios u
JOIN dependencias d ON d.nombre = u.facultad AND d.tipo = 'Facultad'
SET u.dependencia_id = d.id
WHERE u.rol = 'invitado';

UPDATE usuarios SET dependencia_id = NULL WHERE rol = 'invitado' AND (facultad IS NULL OR facultad = '');
