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

## 2026-09-26 — Snapshot al enviar (lote + techo) en Gastos y Autogestión

- **Archivo:** `sql/envios_lote.sql`
- **Cambio:** dos tablas nuevas. `envios_lote` (origen, anio_presupuestal_id, dependencia, ambito, version,
  enviado_por, enviado_en, rol_destinatario_id, usuario_destinatario_id, total_lote, techo_numero,
  techo_flexible_activo, techo_categorias JSON, estado activo/inactivo). `envios_lote_filas` (lote_id
  FK con `ON DELETE CASCADE`, origen_fila_id, datos_json con la foto completa de la fila).
- **Motivo:** Ver plan `el-techo-no-deberia-kind-candle`. Al hacer clic en "Enviar" en Gastos o en
  Autogestión (Extensión/Postgrado/Unisalud/SinExcedentes) no quedaba ninguna foto de los valores
  enviados — si un Avalador/Gestor editaba la fila después, se perdía para siempre qué se envió
  originalmente. Cada "Enviar" ahora congela un lote versionado (v1, v2... por dependencia/año/origen)
  con sus filas y el techo presupuestal vigente en ese momento. La edición posterior sigue permitida
  igual que hoy — no se bloquea nada, la foto simplemente no cambia.
- **Aplicado en local:** Sí (2026-09-26).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con el despliegue de `modelo/EnvioLote.php` (nuevo) y los cambios en
  `modelo/Gasto.php`/`GastoExtension.php`/`GastoPostgrado.php`/`GastoUnisalud.php`/
  `GastoSinExcedentes.php` + sus 4 pares `Ingreso*.php` (`enviarTodosBorrador()` pasa a devolver las
  filas enviadas en vez de solo un conteo), los 5 controladores (`enviarTodo()`, `actualizar()`/
  `actualizarEgreso()`, nuevas acciones `ocultar_lote`/`eliminar_lote`), y
  `PeticionesControlador::historialItem()` (deja de exigir rol administrador, usa
  `puedeVerHistorialItem()`) — sin ese código, las tablas no tienen ningún efecto todavía.

---

## 2026-09-26 — Backfill de usuario_id en filas automáticas huérfanas de Autogestión

- **Archivo:** `sql/backfill_usuario_id_automaticos.sql`
- **Cambio:** migración de datos (no de esquema). `UPDATE` que copia `usuario_id` desde el
  ingreso que originó cada fila automática (Excedentes/Contribución a posgrado) en
  `gastos_extension`/`gastos_postgrado`/`gastos_unisalud`/`gastos_sin_excedentes`, para las que
  quedaron con `usuario_id NULL`.
- **Motivo:** esas filas antiguas (de antes de que `regenerarAutomaticosDeIngreso()` empezara a
  copiar el `usuario_id` del ingreso) caían en el filtro de respaldo para filas sin dueño
  (`filtrarPorPropietarioODestinatario()`: visibles a un ancestro de su dependencia) — un
  superadmin raíz las veía TODAS, de cualquier facultad, sin necesitar el modo Auditar ni un
  permiso delegado, y sus valores se sumaban de más en la barra de presupuesto/techo del landing
  de quien las veía. Encontrado al revisar por qué un usuario seguía viendo "Excedentes nivel
  central" de FACULTAD DE ARQUITECTURA después de la corrección de visibilidad del mismo día (ver
  la entrada de Snapshot al enviar, arriba, y el hallazgo de que la fuga real era de datos legado,
  no del mecanismo de permisos).
- **Aplicado en local:** Sí (2026-09-26) — 6 filas corregidas (3 en `gastos_extension` vía
  `ingreso_id`, 1 en `gastos_extension` sin `ingreso_id` — id 87, FACULTAD DE ARQUITECTURA,
  asignada a mano al único usuario dueño de los demás ingresos de esa dependencia+autogestión
  [usuario 67] — y 2 en `gastos_postgrado` vía `ingreso_id`).
- **Aplicado en producción:** Pendiente.
- **Nota:** el archivo `.sql` cubre el caso general (fila con `ingreso_id` válido). El caso sin
  `ingreso_id` (huérfana) no se puede resolver con una consulta genérica — el archivo deja
  documentado cómo detectarlas y el criterio usado en local para asignarles dueño a mano.
  Deliberadamente NO incluye las 26 filas de `gastos` (gasto_principal) con `usuario_id NULL`
  encontradas de paso: son todas `tipo_automatico = 'techo_hijo'`, un mecanismo distinto (cálculo
  interno de techo heredado, excluido explícitamente de `enviarTodosBorrador()`) — fuera de
  alcance de este arreglo a menos que se confirme que también les afecta el mismo problema de
  visibilidad.

---

## 2026-09-26 — Versiones ligeras del árbol PDI (página Análisis, solo SA)

- **Archivo:** `sql/arbol_versiones.sql`
- **Cambio:** dos tablas nuevas. `arbol_versiones` (pestana ENUM 'articulacion_pdi'/
  'programacion_presupuestal', nombre, creado_por, creado_en). `arbol_versiones_datos`
  (version_id FK con `ON DELETE CASCADE`, tabla, datos LONGTEXT con el JSON de esa tabla en ese
  momento).
- **Motivo:** nueva página `?ruta=analisis` (solo superadmin de dependencia raíz) con un árbol
  Línea/Motor/Proyecto/Gasto reutilizado del prototipo `vista/dev/pruebas/arbol.php`. A
  diferencia de `modelo/Snapshot.php` (copia TODA la BD, usado por `?ruta=repositorios`), este
  mecanismo solo congela las 5 tablas que el árbol necesita (`gastos`, `lineas`, `motores`,
  `proyectos`, `anios_presupuestales`) — mucho más liviano, y cada pestaña del árbol (Articulación
  PDI / Programación presupuestal) lista solo sus propias versiones. Ver plan aprobado
  `el-techo-no-deberia-kind-candle`.
- **Aplicado en local:** Sí (2026-09-26).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con el despliegue de `modelo/VersionArbol.php` (nuevo),
  `modelo/FuenteDatosAnalisis.php` (nuevo), `controlador/AnalisisControlador.php` (nuevo), la
  nueva ruta `analisis` en `index.php`, y la nueva entrada del sidebar en
  `vista/parciales/sidebar.php` — sin ese código, las tablas no tienen ningún efecto todavía.

---

## 2026-09-26 — Valor monetario de las solicitudes de Monitores

- **Archivo:** `sql/agregar_valor_monitores.sql`
- **Cambio:** `ALTER TABLE solicitudes_monitores ADD COLUMN valor DECIMAL(14,2) NULL AFTER
  monitores_semestre2;`
- **Motivo:** Monitores no tenía ningún concepto de valor monetario (solo conteos por semestre),
  a diferencia de ARL/Extensión/Postgrado/Unisalud — hacía falta para poder mostrarlo en su
  landing real (`?ruta=solicitudes&tab=monitores`) y para agregarlo al selector de la página
  Análisis (`?ruta=analisis&tab=analisis`). Fórmula: `(monitores_semestre1 + monitores_semestre2)
  * 2 * SMMLV` del año presupuestal de la solicitud (SMMLV vía `variables_macroeconomicas`,
  ver `VariableMacroeconomica::obtenerPorNombreYAnio()`). `modelo/SolicitudMonitor.php` la calcula
  y guarda en `crear()`/`actualizar()`; las filas ya existentes quedan en NULL y se recalculan al
  vuelo en cada lectura (`aplicarRespaldoValor()`) hasta que alguien las edite y guarden con el
  valor ya persistido — nunca se ve un valor vacío, viejo o nuevo.
- **Aplicado en local:** Sí (2026-09-26).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con el despliegue de `modelo/SolicitudMonitor.php`,
  `controlador/PeticionesControlador.php` (columna 'Valor' agregada a la definición de columnas
  del origen 'monitores') y las vistas `vista/solicitudes/index.php` / `detalle.php` (columna
  "Valor" nueva) — sin ese código, la columna nueva no se lee ni se muestra en ningún lado.

---

## 2026-09-27 — Presupuesto institucional (pestaña Programación presupuestal, plantilla Egresos/Ingresos)

- **Archivo:** `sql/presupuesto_institucional.sql`
- **Cambio:** tres tablas nuevas. `presupuesto_institucional_lineas` (tipo ENUM 'ingreso'/'egreso',
  codigo VARCHAR con el código jerárquico de puntos ej. `1.1.1.0`, es_total, descripcion, orden;
  UNIQUE por (tipo, codigo) — Ingresos y Egresos numeran cada uno desde su propio `1.0`).
  `presupuesto_institucional_valores` (linea_id FK, anio = año CALENDARIO real —no el id de
  `anios_presupuestales`—, valor_final, valor_corte, fecha_corte; UNIQUE por (linea_id, anio)).
  `presupuesto_institucional_linea_proyectos` (linea_id FK, proyecto_id FK — mapeo N:M, solo para
  líneas hoja de Egresos).
- **Motivo:** "Programación presupuestal" pasa de mostrar Línea/Motor/Proyecto a mostrar el
  presupuesto institucional completo de la IES (nómina, obligaciones, inversión y demás capítulos
  que el resto de la plataforma nunca capturó — esta solo cubre recursos distintos de esos tres),
  cargado por una plantilla Excel de 2 hojas ("Egresos"/"Ingresos", mismo patrón de 2 hojas que ya
  usan Extensión/Postgrado/Unisalud). Un código terminado en `.0` es una fila total (suma de sus
  descendientes, recalculada al importar — nunca lo que traiga el archivo en esa celda). Cuando una
  línea hoja de Egresos corresponde a Proyecto(s) PDI (referenciados por su `nit`, ya existente en
  `proyectos` — no un código nuevo), su valor del año activo se reparte entre esos proyectos y se
  SUMA al total que "Articulación PDI" ya calculaba — de paso se corrigió que ese total solo sumaba
  `gastos` (gasto_principal) y no los 3 de autogestión (Extensión/Postgrado/Unisalud). Ver plan
  `el-techo-no-deberia-kind-candle`.
- **Aplicado en local:** Sí (2026-09-27).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con `modelo/PresupuestoInstitucional.php` (nuevo), `modelo/Proyecto.php`
  (nuevo `obtenerPorNit()`), `modelo/GeneradorXlsx.php` (nuevo `descargarPlantillaMultihoja()`),
  `modelo/FuenteDatosAnalisis.php` (nuevo `obtenerGastosAutogestionPorAnio()`),
  `modelo/VersionArbol.php` (ahora también congela `gastos_extension`/`postgrado`/`unisalud` —
  antes solo `gastos` —, para que una versión de Articulación PDI incluya autogestión igual que en
  vivo), `controlador/AnalisisControlador.php` y `vista/analisis/` — sin ese código, las tablas no
  tienen ningún efecto todavía.

---

## 2026-09-27 — Versiones (snapshots) del presupuesto institucional, una lista por lado

- **Archivo:** `sql/presupuesto_institucional_versiones.sql`
- **Cambio:** dos tablas nuevas. `presupuesto_institucional_versiones` (tipo ENUM
  'ingreso'/'egreso', nombre, creado_por, creado_en). `presupuesto_institucional_versiones_datos`
  (version_id FK con `ON DELETE CASCADE`, datos LONGTEXT — un solo JSON con las líneas + valores +
  mapeo a Proyecto(s) PDI de ese lado en ese momento).
- **Motivo:** "Guardar versión" (Articulación PDI / Programación presupuestal) vivía en modo
  Tiempo real y, para Programación presupuestal, seguía usando `VersionArbol` (pensada para
  Línea/Motor/Proyecto) — pero esa pestaña ya no muestra eso, muestra el presupuesto
  institucional (ver entrada anterior). Se corrigió doble: (1) "Guardar versión" se movió a modo
  Repositorio (junto al selector de qué versión ver, no en Tiempo real); (2) Programación
  presupuestal ahora congela sus propias tablas (`presupuesto_institucional_*`) en vez de
  reutilizar `VersionArbol` — con lista de versiones INDEPENDIENTE por lado (Egresos e Ingresos
  nunca comparten versiones, igual que ya no comparten numeración de código). Articulación PDI no
  cambia: sigue con `VersionArbol` tal cual. Ver plan `el-techo-no-deberia-kind-candle`.
- **Aplicado en local:** Sí (2026-09-27).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con `modelo/PresupuestoInstitucional.php` (nuevos `crearVersion()`,
  `obtenerVersiones()`, `obtenerArbolConValoresDeVersion()`) y `controlador/AnalisisControlador.php`
  (selector de versiones acotado por `$lado`) — sin ese código, las tablas no tienen ningún efecto
  todavía. La creación de estas versiones ya NO vive en `AnalisisControlador` — ver entrada
  siguiente.

---

## 2026-09-27 — Crear versiones/snapshots se centralizó en ?ruta=repositorios

- **Archivo:** sin cambio de esquema (solo controladores/vistas).
- **Cambio:** "Guardar versión" (Árbol PDI y Programación presupuestal Egresos/Ingresos) se quitó
  por completo de `?ruta=analisis` — ese formulario ya no existe ahí, ni en modo Tiempo real ni en
  Repositorio. Las 3 acciones de creación (`crear_arbol`, `crear_presupuesto` con `tipo`
  ingreso/egreso) se movieron a `RepositorioControlador`, como secciones nuevas en la página
  `?ruta=repositorios` que ya existía para el snapshot de BD completa — junto con sus
  `eliminar_arbol`/`eliminar_presupuesto`. `?ruta=analisis` conserva únicamente el selector de CUÁL
  versión ver (modo Repositorio), nunca la creación.
- **Motivo:** el usuario señaló, con una captura de la UI vieja en `?ruta=analisis`, que crear
  versiones "solo debe ser visible pra superadmin" — `?ruta=analisis` se gatea por
  `dependencias.es_raiz_superadmin` (a nivel de dependencia), mientras que `?ruta=repositorios` ya
  se gateaba por `usuarios.es_super_admin` (a nivel de usuario, más estricto) para el snapshot
  completo. En vez de duplicar ese gate más estricto dentro de Análisis, se centralizó toda
  creación de versiones/snapshots en la página que ya tenía el gate correcto.
- **Aplicado en local:** Sí (2026-09-27) — verificado por HTTP+BD: crear/listar/eliminar las 3
  (Árbol PDI, Egresos, Ingresos) desde `?ruta=repositorios`, confirmando que aparecen sin
  mezclarse entre secciones ni con snapshots; que las creadas ahí sí aparecen en los selectores de
  `?ruta=analisis`; que `eliminar` borra la fila padre y sus filas hijas (`ON DELETE CASCADE`); y
  que un usuario sin `es_super_admin` sigue siendo redirigido fuera de `?ruta=repositorios`.
- **Aplicado en producción:** Pendiente.
- **Nota:** `procesarGuardarVersion()`/`procesarGuardarVersionPresupuesto()` (y sus ramas de
  dispatch en `procesarPost()`) se eliminaron de `AnalisisControlador.php` — quedaron sin uso al
  quitar el formulario que los disparaba.

---

## 2026-09-27 — La versión elegida en "Repositorio" queda pegada hasta que el admin la cambie

- **Archivo:** `sql/versiones_activas.sql`
- **Cambio:** columna `activa TINYINT(1) NOT NULL DEFAULT 0` nueva en `arbol_versiones` y en
  `presupuesto_institucional_versiones`. Solo una fila por grupo (`pestana` en la primera, `tipo`
  en la segunda) puede tener `activa = 1` a la vez. El script deja marcada como activa la más
  reciente de cada grupo al migrar, para no cambiar el comportamiento de golpe en las que ya
  existían.
- **Motivo:** el selector de "¿qué versión ver?" en modo Repositorio (`?ruta=analisis`) vivía
  solo en la URL (`?version_id=N`) — sin ese parámetro en la URL (un enlace del menú, otra
  pestaña del navegador, otro admin entrando de nuevo) siempre se caía a la más reciente por
  fecha, sin memoria de cuál había elegido el admin la última vez. Se pidió que, una vez elegida,
  esa quede como la que se muestra hasta que el admin la cambie él mismo — sin depender de que la
  URL siga cargando el parámetro.
- **Aplicado en local:** Sí (2026-09-27) — verificado por HTTP+BD (Árbol PDI y Programación
  presupuestal Egresos): elegir una versión por el selector la marca `activa=1` y desmarca las
  demás de su grupo; recargar la página sin `version_id` en la URL sigue mostrando esa misma
  elegida; elegir otra la reemplaza; borrar la versión activa promueve automáticamente la más
  reciente que quede de su grupo a `activa=1` (para que la columna "Activa" de
  `?ruta=repositorios` nunca quede vacía mientras Análisis sigue mostrando algo).
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con `modelo/VersionArbol.php` (`obtenerActiva()`, `marcarActiva()`, y
  `eliminar()` ahora promueve la siguiente al borrar la activa),
  `modelo/PresupuestoInstitucional.php` (mismo par de métodos con sufijo `Version*`) y
  `controlador/AnalisisControlador.php::renderizarArbol()` (ya no usa `$versiones[0]` como
  respaldo, usa la fila `activa` de cada modelo). También se agregó una columna "Activa" a las
  tablas de versiones en `vista/repositorios/index.php` para que se vea cuál es, sin tener que
  entrar a Análisis.

---

## 2026-09-27 — La plantilla de Programación presupuestal deja de traer "Fecha de corte"

- **Archivo:** `sql/presupuesto_institucional_quitar_fecha_corte.sql`
- **Cambio:** se elimina la columna `fecha_corte` de `presupuesto_institucional_valores`. La
  columna `valor_corte` (la cifra "Año anterior a corte") se queda igual — solo se quita la
  FECHA asociada, no el valor.
- **Motivo:** "esto es netamente de la página" — la fecha que rotula la columna "Año anterior (a
  corte)" no es un dato del presupuesto institucional que tenga sentido traer por línea en la
  plantilla (cada línea traía su propia fecha, y encima `obtenerFechaCorteReferencia()` solo
  tomaba la primera que encontrara con `LIMIT 1`, sin ORDER BY — arbitrario). Ahora esa fecha es
  exactamente lo mismo que ya existía para Articulación PDI: un `<input type="date">` en la
  página, vía `$_GET['corte']`, sin persistir en ningún lado ni depender de lo importado.
- **Bug encontrado y corregido de paso:** al quitar la columna "Fecha de corte" de la plantilla,
  los índices de las columnas de años históricos en `validarFilasPresupuesto()` tenían un
  desfase de una columna respecto a como los escribe `exportarPlantillaPresupuesto()` — ya
  existía ANTES de este cambio (no lo introdujo esta corrección): al reimportar una plantilla
  recién exportada por la misma plataforma, los 4 valores históricos quedaban corridos una
  columna a la derecha (el de hace 2 años se guardaba como si fuera el de hace 3, etc., y el de
  hace 5 años se perdía). Verificado por HTTP con un archivo real (una fila con un valor distinto
  por año) que cada año importado cae exactamente en su columna.
- **Aplicado en local:** Sí (2026-09-27) — verificado por HTTP+BD: plantilla exportada sin la
  columna "Fecha de corte" (6 encabezados fijos + 4 años históricos, en vez de 7 + 4); una fila de
  prueba con valores distintos por año importa cada uno en el año correcto; la página muestra el
  `<input type="date">` editable de nuevo (antes un `<span>` de solo lectura) y su cambio por URL
  (`?corte=AAAA-MM-DD`) sigue funcionando igual que en Articulación PDI.
- **Aplicado en producción:** Pendiente.
- **Nota:** Acompañar con `modelo/PresupuestoInstitucional.php` (se quitó
  `obtenerFechaCorteReferencia()`, ya sin uso, y `fecha_corte` de todo el resto del archivo),
  `controlador/AnalisisControlador.php` (plantilla export/import sin esa columna, `$fechaCorte`
  se resuelve igual para las 2 pestañas) y `vista/analisis/parciales/arbol.php` (input de fecha
  de vuelta, con su manejador en JS).

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
