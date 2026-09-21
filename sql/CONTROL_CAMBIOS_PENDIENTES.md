# Control de cambios de base de datos pendientes de producción

Este entorno trabaja contra la base de datos local `again` (XAMPP). Los cambios de esquema o
reestructuraciones que se hagan aquí **no se aplican solos** a la base de datos en línea
(producción), que se administra por separado. Este archivo lleva la lista de qué falta replicar
allá, en orden, para no perder ningún ajuste antes de un despliegue real.

Cada entrada indica: fecha, qué cambia, qué archivo(s) de `sql/` lo implementan, y el estado
(`Pendiente` hasta que alguien confirme que ya se aplicó también en producción).

---

## 2026-09-20 — Color de icono por rol

- **Archivo:** `sql/roles_color.sql`
- **Cambio:** `ALTER TABLE roles ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#0071e3' AFTER orden;`
  más un `UPDATE` inicial de colores para los roles Gestor/Avalador/Formulador.
- **Motivo:** Selector de color para el icono de cada rol (permite distinguir por color cuando
  dos personas con roles distintos comparten nombre).
- **Aplicado en local:** Sí (2026-09-20).
- **Aplicado en producción:** Pendiente.
- **Nota:** Los valores de color ya fueron editados manualmente en local desde la pantalla de
  Roles después del `UPDATE` inicial — al aplicar en producción, usar los valores reales que
  queden en la tabla `roles` de local en ese momento (columna `color`), no los del `UPDATE` de
  este script, que solo son la semilla inicial.

---

## 2026-09-20 — Consulta/Formulador: rol_id + dependencia para Consejo Superior e Invitados

- **Archivo:** `sql/usuarios_consulta_formulador_dependencias.sql`
- **Cambio:** `orden = 0` para el rol "Consulta"; nueva fila en `dependencias` (código
  `CONSEJOSUP`, tipo `Consejo Superior`, raíz); nueva fila raíz en `jerarquias` con el mismo tipo;
  el nodo `jerarquias` "formuladores" (id 41 en local) pasa a `padre_id = NULL`; se asigna
  `rol_id`/`dependencia_id` a todos los usuarios `consejo_superior` (→ rol Consulta + dependencia
  Consejo Superior) e `invitado` (→ rol Formulador + dependencia `tipo='Formulador'` existente).
- **Motivo:** Que Consejo Superior e Invitados tengan un rol y una dependencia reales (compartidos
  con el catálogo de Administrador), para poder configurarles el menú del sidebar por tipo igual
  que a cualquier otro, y mostrarlos correctamente en el Mapa de Jerarquías.
- **Aplicado en local:** Sí (2026-09-20).
- **Aplicado en producción:** Pendiente.
- **Nota:** El `id` del nodo "formuladores" en `jerarquias` (41) y de la dependencia "FORMULADOR"
  (509) son los de la BD local — en producción hay que ubicarlos por nombre/tipo, no asumir esos
  mismos números. Ejecutar **después** de `sql/roles_color.sql` (usa la columna `color`/el rol
  "Consulta", que ya debe existir).
- **Corrección posterior (mismo día):** el `dependencia_id` de los Invitados NO debe quedar en la
  dependencia genérica "FORMULADOR" — debe apuntar a la Facultad real que cada uno eligió al
  registrarse (columna `usuarios.facultad`), para poder enrutar su envío a un administrador Gestor
  de esa misma Facultad. Ver `sql/usuarios_invitados_dependencia_facultad_real.sql` (ejecutar
  después de este script). Además, en Usuarios ya no se fuerza el `rol_id`/`dependencia_id` de
  Consulta/Formulador desde el backend — el formulario de Editar/Agregar ahora tiene los mismos
  campos Tipo/Dependencia/Rol/Estamento que Administradores, así que un administrador puede
  corregir la dependencia de cualquier Invitado a mano si el texto de "facultad" no coincidió con
  ninguna dependencia real.

---

<!--
Plantilla para la próxima entrada:

## AAAA-MM-DD — Título corto del cambio

- **Archivo:** `sql/nombre_del_archivo.sql`
- **Cambio:** (resumen del ALTER/CREATE/migración de datos)
- **Motivo:** (por qué se hizo)
- **Aplicado en local:** Sí/No (fecha)
- **Aplicado en producción:** Pendiente / Sí (fecha)
- **Nota:** (cualquier dato manual, orden de ejecución, dependencias con otra tabla, etc.)
-->
