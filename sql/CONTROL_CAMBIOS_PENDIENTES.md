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
