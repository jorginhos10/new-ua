# Control de cambios de base de datos pendientes de producción

Este entorno trabaja contra la base de datos local `again` (XAMPP). Los cambios de esquema o
reestructuraciones que se hagan aquí **no se aplican solos** a la base de datos en línea
(producción), que se administra por separado. Este archivo lleva la lista de qué falta replicar
allá, en orden, para no perder ningún ajuste antes de un despliegue real.

Cada entrada indica: fecha, qué cambia, qué archivo(s) de `sql/` lo implementan, y el estado
(`Pendiente` hasta que alguien confirme que ya se aplicó también en producción).

## Para aplicar todo de una sola vez en producción

**`sql/produccion_pendientes_consolidado.sql`** junta las **5 entradas de abajo** (color de icono
por rol, Consulta/Formulador + su corrección de dependencia por Facultad, % por ítem de Extensión,
ítems propios de Postgrado, y tope único por módulo) en un solo archivo, en el orden correcto, para
poder correr todo de una sola vez en vez de ejecutar los archivos sueltos uno por uno.

Cada `ALTER` usa `IF [NOT] EXISTS` (o el equivalente con SQL dinámico, para los `FOREIGN KEY`, que
MariaDB no soporta con `IF NOT EXISTS`) y cada `INSERT`/`UPDATE` valida antes si hace falta, sin
pisar datos que ya se hayan corregido a mano en producción (color de un rol ya personalizado,
dependencia de un Invitado ya corregida a mano, ítems de Autogestión ya configurados, etc. — ver el
comentario de cada paso dentro del archivo). Es seguro volver a correrlo si una corrida anterior se
cortó a la mitad. Probado localmente corriéndolo dos veces seguidas sobre una base ya migrada: sin
errores, sin duplicados, y de hecho terminó de poner al día un par de usuarios Invitados que se
habían quedado sin `rol_id`/dependencia asignados de una corrida manual anterior.

No trae `USE <basededatos>;` a propósito: conéctate primero a la base de datos de producción
correcta y corre el archivo completo tal cual, en una sola sesión. Se recomienda respaldar la base
de datos antes de correrlo.

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

## 2026-09-21 — Porcentaje de Costos/Inversiones/Excedentes por ítem de Autogestión (Extensión)

- **Archivo:** `sql/autogestion_items_porcentajes.sql`
- **Cambio:** `ALTER TABLE autogestion_items ADD COLUMN costos/inversiones/excedentes DECIMAL(5,2)
  NULL` (mismo patrón nullable de "No aplica" que ya usaba `autogestion_porcentajes`); `UPDATE`
  que copia, solo para los ítems con `modulo = 'extension'`, el % que tenía el módulo completo en
  `autogestion_porcentajes` (semilla inicial, para no romper nada en curso).
- **Motivo:** El % dejó de ser global por módulo — ahora cada ítem de Extensión (Cursos libres,
  Educación continua, etc.) reparte su propio ingreso con su propio Costos/Inversiones/Excedentes,
  en vez de que todos los ítems compartan un único % fijado a nivel de módulo. La fila
  `autogestion_porcentajes` de `modulo = 'extension'` queda congelada/sin usar (ya no editable
  desde Configuraciones); Postgrado/Unidad de Salud/Convenios siguen usando `autogestion_porcentajes`
  igual que antes (no tienen ítems).
- **Aplicado en local:** Sí (2026-09-21).
- **Aplicado en producción:** Pendiente.
- **Nota:** El archivo trae `USE new_ua;` (igual que las demás migraciones de esta carpeta) pero la
  BD local se llama `again` — al aplicarlo ahí hubo que saltarse esa línea. La plantilla Excel de
  Extensión ("Exportar plantilla") todavía muestra su bloque de validación de % como "N/A" (ver
  comentario en `ExtensionControlador::exportarPlantilla()`): quedó pendiente rehacerlo para que
  valide por ítem en el propio archivo Excel — la validación real al importar sí es correcta ítem
  por ítem.

---

## 2026-09-21 — Postgrado: ítems de Autogestión propios (clonado del patrón de Extensión)

- **Archivo:** `sql/postgrado_autogestion_items.sql`
- **Cambio:** `autogestion_items` gana la columna `contribucion_postgrado DECIMAL(5,2) NULL`
  (nullable, mismo patrón "No aplica"); se crea un ítem `nombre='General', modulo='postgrado'`
  sembrado con el % que tenía `autogestion_porcentajes` para ese módulo; `ingresos_postgrado` y
  `gastos_postgrado` ganan `autogestion_id INT NOT NULL` con FK a `autogestion_items(id)` (misma
  posición relativa que sus columnas gemelas en `ingresos_extension`/`gastos_extension`), con TODO
  lo existente hasta ahora migrado a ese ítem "General" para no perder continuidad.
- **Motivo:** Postgrado no tenía ningún concepto de "ítems" (a diferencia de Extensión) — todo el
  módulo compartía un único % de Costos/Inversiones/Excedentes/Contribución a posgrado. Se clona el
  patrón completo de Extensión (selector en el sidebar, % por ítem, egresos automáticos con
  columna `autogestion_id`) para que cada ítem de Postgrado (ej. distintos programas/convenios)
  pueda tener su propio reparto.
- **Aplicado en local:** Sí (2026-09-21).
- **Aplicado en producción:** Pendiente.
- **Nota:** El `id` del ítem "General" es el que quede autoincrementado en cada entorno (11 en
  local) — no asumir ese mismo número en producción, ubicarlo por `modulo='postgrado' AND
  nombre='General'`. El archivo trae `USE new_ua;` (igual que las demás migraciones) pero la BD
  local se llama `again` — se aplicó saltándose esa línea. La fila `autogestion_porcentajes` de
  `modulo='postgrado'` queda congelada/sin usar desde Configuraciones (igual que pasó con
  `extension`), reemplazada por el % del ítem "General".

---

## 2026-09-21 — Tope único por módulo (Extensión/Postgrado), ya no por ítem

- **Archivo:** `sql/autogestion_tope_por_modulo.sql`
- **Cambio:** `autogestion_porcentajes` gana la columna `tope DECIMAL(15,2) NULL`; se siembra con
  la suma de los topes que tuvieran los ítems activos de `extension`/`postgrado` en ese momento
  (normalmente 0/NULL, si nadie había configurado nada); luego se elimina la columna `tope` de
  `autogestion_items` (deja de existir por ítem).
- **Motivo:** El tope debe ser UN SOLO número por módulo (el denominador de las tarjetas de
  Autogestión/Postgrado en Inicio, ver `DashboardControlador::obtenerResumenIngresos()`), no una
  suma de topes sueltos configurados ítem por ítem — así lo pidió el usuario explícitamente después
  de ver que la tabla de ítems seguía mostrando una columna "Tope" por fila.
- **Aplicado en local:** Sí (2026-09-21).
- **Aplicado en producción:** Pendiente.
- **Nota:** Ejecutar **después** de `sql/autogestion_items_porcentajes.sql` y
  `sql/postgrado_autogestion_items.sql` (ambos ya crean/usan `autogestion_items`/
  `autogestion_porcentajes`). El archivo trae `USE new_ua;` pero la BD local se llama `again` — se
  aplicó saltándose esa línea. En Configuraciones > Autogestión, el tope de Extensión/Postgrado
  ahora se edita en un solo campo arriba de la tabla de ítems (acción `guardar_tope`), no por fila.

---

## 2026-09-22 — Índice en `gastos(anio_presupuestal_id, dependencia)` (rendimiento)

- **Archivo:** `sql/gastos_indice_dependencia_anio.sql`
- **Cambio:** `ALTER TABLE gastos ADD INDEX idx_gastos_anio_dependencia (anio_presupuestal_id, dependencia);`
  — solo un índice nuevo, no toca datos ni estructura de columnas.
- **Motivo:** La página de Gastos (`GastoControlador::index()`) tardaba ~3 segundos en cargar.
  `Gasto::obtenerTotalesPropioYComprometidoPorDependencia()` tiene una subconsulta `EXISTS`
  correlacionada que filtra por `(anio_presupuestal_id, dependencia)` por cada fila candidata —
  sin este índice, MySQL escaneaba linealmente ~800 filas por cada una de las ~1600 filas de
  `gastos` (confirmado con `EXPLAIN`: `DEPENDENT SUBQUERY ... rows=814`). Con el índice, esa misma
  subconsulta pasó a `rows=19` y la página de Gastos bajó de ~3s a ~0.15s. No cambia ningún
  resultado (un índice nunca altera lo que devuelve un SELECT, solo cómo lo busca) — verificado
  comparando los totales por dependencia antes/después.
- **Aplicado en local:** Sí (2026-09-22).
- **Aplicado en producción:** Pendiente.
- **Nota:** Seguro de volver a correr (`ADD INDEX` con ese nombre falla si ya existe, pero no rompe
  nada — solo hay que confirmar el nombre `idx_gastos_anio_dependencia` no esté ya usado). No
  requiere downtime perceptible: es solo una reconstrucción de índice sobre ~1600-2000 filas.

---

## 2026-09-22 — Permiso "validación flexible de techo" en Gastos + Dumi visibles en el sidebar

- **Archivo:** `sql/gasto_techo_flexible_permiso.sql`
- **Cambio:** `usuarios` gana la columna `techo_flexible TINYINT(1) NULL` (tri-estado: NULL =
  hereda el default de su tipo/rol, 1 = forzado activo, 0 = forzado inactivo); se crea la tabla
  `tipo_dependencia_techo_flexible (tipo, rol_id, activo)` con los defaults por tipo+rol — mismo
  patrón de dos niveles que `tipo_dependencia_menu`/`MenuPermiso`, para este único booleano.
- **Motivo:** Ver plan `el-techo-no-deberia-kind-candle`. El criterio de "un hijo sin techo propio
  cuenta contra el techo de quien envía" (`GastoControlador::resolverDependenciaConTecho()`) ya
  existía de forma automática e implícita para todo administrador — este cambio lo formaliza como
  un permiso explícito y auditable (por usuario, con default por tipo/rol), sin alterar el
  algoritmo de validación en sí. Además, "Gastos" en el sidebar se vuelve un desplegable (como
  Extensión/Postgrado) con una opción por cada programa Dumi asociado al usuario, para que sean
  más fáciles de encontrar — este permiso NUNCA afecta cómo se valida un Dumi.
- **Aplicado en local:** Sí (2026-09-22).
- **Aplicado en producción:** Pendiente.
- **Nota:** Con el permiso desactivado (default para todo el mundo hasta que un admin lo prenda),
  el comportamiento de Gastos es idéntico al de antes de este cambio. Se configura por usuario
  desde Usuarios → Permisos (campo "Validación flexible de techo"), o por tipo/rol desde
  Jerarquías > Mapa > ⚙ (checkbox nuevo en el mismo modal del menú).

## 2026-09-23 — Historial por lote en Peticiones

- **Archivo:** `sql/peticiones_historial_lotes.sql`
- **Cambio:** Nueva tabla `peticiones_historial_lotes` (id, tipo, accion, detalle, cantidad_items,
  usuario_id, creado_en); `ALTER TABLE peticiones_historial ADD COLUMN lote_id INT NULL, ADD INDEX
  idx_lote (lote_id);`.
- **Motivo:** Ver plan `el-techo-no-deberia-kind-candle`. Las acciones masivas de Peticiones
  (aprobar/archivar/consolidar/duplicar/enviar/restaurar N ítems a la vez) grababan N filas casi
  idénticas en `peticiones_historial`, una por ítem, sin ningún dato que las relacionara como una
  sola acción real. Ahora esas filas comparten un `lote_id` que apunta a un único resumen en
  `peticiones_historial_lotes`, permitiendo ver el historial agrupado "por tabla"/tipo (ej. todos
  los eventos masivos que tocaron Gastos) además del historial por ítem individual que ya existía.
- **Aplicado en local:** Sí (2026-09-23).
- **Aplicado en producción:** Pendiente.
- **Nota:** Las 4 acciones que ya eran sobre un solo ítem (aprobar/archivar uno, restaurar uno,
  rechazar una redirección, eliminar un pendiente) siguen grabando con `lote_id = NULL` — no
  necesitan agruparse, ya son atómicas. Esta ronda solo agrega el almacenamiento (`lote_id` +
  `registrarConLote()`) y las vistas de lectura `peticiones-historial-tipo`/`peticiones-historial-lote`;
  todavía NO hay ningún botón en Archivados/Enviadas que lleve a ellas (eso es la parte C del plan,
  pospuesta explícitamente por el usuario) — por ahora solo se llega por URL directa.

---

## 2026-09-24 — Estado "En Expediente" en Archivados + visibilidad restringida del superadmin

- **Archivo:** `sql/peticiones_archivadas_expediente.sql`
- **Cambio:** `ALTER TABLE peticiones_archivadas MODIFY COLUMN accion ENUM('archivada','aprobada','redireccionada','expediente') NOT NULL DEFAULT 'archivada';`
  — solo amplía el ENUM, no pierde ningún valor ni fila existente.
- **Motivo:** Ver plan `el-techo-no-deberia-kind-candle`. El superadmin veía siempre TODO lo
  archivado/enviado del sistema en esas dos pestañas, sin que el interruptor "Auditar" tuviera
  ningún efecto ahí (solo afectaba Pendientes/Consolidado) — fácil de confundir con lo propio.
  Ahora, con Auditar apagado, el superadmin solo ve su propia dependencia + un nuevo registro
  centralizado "En Expediente" que cualquier usuario puede alimentar con el botón "Mandar a
  Expediente" (saca el ítem de SU Archivado); solo el superadmin puede "Restaurar" desde Expediente,
  lo que lo devuelve al Archivado del dueño original (no a Pendientes). Los ítems en Expediente
  quedan excluidos de todas las barras de progreso/techos/metas de Gastos e Ingresos de Autogestión.
- **Aplicado en local:** Sí (2026-09-24).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar esta migración con el despliegue del código de
  `PeticionesControlador.php`/`PeticionArchivada.php` y con el barrido de exclusión `NOT EXISTS
  (...accion = 'expediente')` en los métodos de totales de `Gasto`/`GastoExtension`/`GastoPostgrado`/
  `GastoUnisalud`/`GastoSinExcedentes`/`IngresoExtension`/`IngresoPostgrado`/`IngresoUnisalud`/
  `IngresoSinExcedentes` — sin ese código, la columna ampliada no tiene ningún efecto visible todavía.

---

## 2026-09-24 — Permisos delegados y "Definir" parámetros sobre filas automáticas de Autogestión

- **Archivos:** `sql/autogestion_automatico_permisos.sql`, `sql/autogestion_automatico_definiciones.sql`.
- **Cambio:** dos tablas nuevas. `autogestion_automatico_permisos` (modulo, tipo, usuario_id,
  otorgado_por) — quién, además del superadmin, puede editar/borrar filas automáticas de un
  (módulo, tipo); empieza vacía. `autogestion_automatico_definiciones` (modulo, autogestion_id NULL,
  tipo, dependencia NULL, sede_id, proyecto_id, rubro_id, actividad, insumo, meses, creado_por) —
  plantilla de campos a usar al generar la fila automática, por ítem+dependencia, reemplazando las
  constantes fijas `AUTOMATICO_*` de cada controlador.
- **Motivo:** Ver plan `el-techo-no-deberia-kind-candle`. El superadmin necesita poder borrar
  manualmente una fila automática mal calculada, delegar ese poder a usuarios puntuales que deben
  consolidar/ajustar excedentes o contribución a posgrado entre dependencias, y dejar que cada
  dependencia clasifique presupuestalmente su propio excedente (sede/rubro/proyecto/actividad/insumo)
  en vez de compartir una única constante fija en todo el sistema.
- **Aplicado en local:** Sí (2026-09-24).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con el despliegue del código de `GastoExtension.php`/`GastoPostgrado.php`/
  `GastoUnisalud.php`/`GastoSinExcedentes.php` (`eliminarForzado()`/`actualizarForzado()`),
  `AutogestionAutomaticoPermiso.php`, `AutogestionAutomaticoDefinicion.php` (nuevos), y los cambios
  en `ExtensionControlador.php`/`PostgradoControlador.php`/`UnisaludControlador.php`/
  `SinExcedentesControlador.php`/`AutogestionControlador.php` — sin ese código, las tablas no tienen
  ningún efecto todavía. El punto de "convertir rubro 2.xx→4.xx al elegir Inversiones" del mismo
  pedido quedó pospuesto (fuera de esta migración) a pedido explícito del usuario.

---

## 2026-09-25 — Marca de control de capítulo (2→4) al importar plantilla de autogestión

- **Archivo:** `sql/gastos_autogestion_capitulo_control.sql`
- **Cambio:** `ALTER TABLE gastos_extension/gastos_postgrado/gastos_unisalud ADD COLUMN
  capitulo_control VARCHAR(5) NULL;` — no toca `gastos_sin_excedentes` ni `rubro_id`/el catálogo de
  rubros.
- **Motivo:** Colombia clasifica funcionamiento con capítulo "2" e inversión con "4", pero el
  catálogo de rubros de este sistema todavía no tiene ningún código "4.xx" — no se van a crear
  rubros nuevos solo para esto. Al importar la plantilla, si una fila de Gastos queda clasificada
  como "Inversiones" pero el Rubro elegido es de capítulo "2", se guarda "4" en esta columna nueva
  como marca de control (el `rubro_id` real, con su mismo código y nombre, no cambia) — pensada para
  que un reporte futuro para el SA pueda distinguir estas filas. Esta ronda solo agrega el guardado
  del dato al importar; no hay todavía ningún reporte/pantalla que lo lea.
- **Aplicado en local:** Sí (2026-09-25).
- **Aplicado en producción:** Pendiente.
- **Nota:** Solo aplica a Extensión/Postgrado/Unisalud (mismo alcance que el resto de cambios de
  autogestión de esta ronda, ver plan `el-techo-no-deberia-kind-candle`) — no a Convenios/
  SinExcedentes ni al módulo principal de Gastos. No cambia nada en la plantilla Excel exportada
  (`GeneradorXlsx`) ni en el formulario manual de "Nuevo gasto" — solo en el procesamiento del
  archivo importado.

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
