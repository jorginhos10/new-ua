<?php
$tituloPagina = 'Repositorios';
require __DIR__ . '/../parciales/encabezado.php';

/** Versión marcada como activa dentro de una lista de versiones, o null si no hay ninguna. */
function repositorioVersionActiva(array $versiones): ?array
{
    foreach ($versiones as $version) {
        if (!empty($version['activa'])) {
            return $version;
        }
    }

    return null;
}

/** "nombre — d/m/Y H:i" de la activa, o el aviso de que no hay ninguna — reusado en el resumen y en cada <summary>. */
function repositorioEtiquetaActiva(array $versiones): string
{
    $activa = repositorioVersionActiva($versiones);
    if ($activa === null) {
        return 'Sin versión activa';
    }

    return $activa['nombre'] . ' — ' . date('d/m/Y H:i', strtotime($activa['creado_en']));
}
?>

    <div class="tarjeta">
        <h1>Repositorios</h1>
        <p class="texto-atenuado">
            Cada tipo de repositorio congela una copia de sus datos en un momento dado, totalmente
            independiente de los demás. Cambia la versión activa de cualquiera desde la tabla de
            abajo, o abre su sección para ver el historial completo, crear una nueva o eliminar.
        </p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h1>Resumen</h1>
        <p class="texto-atenuado">
            Un vistazo horizontal a los 7 repositorios: cuál versión está activa en cada uno y la
            opción de cambiarla al toque, sin entrar sección por sección.
        </p>

        <div style="overflow-x: auto;">
            <table class="tabla-usuarios repo-resumen-tabla">
                <thead>
                    <tr>
                        <th></th>
                        <?php foreach ($resumenRepositorios as $tipo): ?>
                        <th><?= htmlspecialchars($tipo['etiqueta']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">Activa</th>
                        <?php foreach ($resumenRepositorios as $tipo): ?>
                        <td><?= htmlspecialchars(repositorioEtiquetaActiva($tipo['versiones'])) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th scope="row">Cambiar a</th>
                        <?php foreach ($resumenRepositorios as $tipo): ?>
                        <td>
                            <?php if (empty($tipo['versiones'])): ?>
                            <span class="texto-atenuado">—</span>
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=repositorios" class="repo-resumen-cambiar">
                                <input type="hidden" name="accion" value="cambiar_activa">
                                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo['clave']) ?>">
                                <select name="id">
                                    <?php foreach ($tipo['versiones'] as $version): ?>
                                    <option value="<?= (int) $version['id'] ?>" <?= !empty($version['activa']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($version['nombre']) ?> — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($version['creado_en']))) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="boton-accion">Cambiar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tarjeta">
        <details class="repo-detalle">
            <summary>Snapshots <span class="repo-detalle-activa">Activa: <?= htmlspecialchars(repositorioEtiquetaActiva($snapshots)) ?></span></summary>

            <p class="texto-atenuado">
                Un snapshot es una copia completa de todos los datos del sistema en un momento dado.
                Se usarán a futuro como fuente de datos para una herramienta de visualización, elegible por snapshot.
            </p>

            <form method="POST" action="index.php?ruta=repositorios" class="form-agregar">
                <input type="hidden" name="accion" value="crear">
                <input type="text" name="nombre" placeholder="Nombre del snapshot (opcional)">
                <button type="submit">Crear snapshot</button>
            </form>

            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Nombre</th>
                        <th>Activa</th>
                        <th>Tablas incluidas</th>
                        <th>Creado por</th>
                        <th>Creado en</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($snapshots as $snapshot): ?>
                    <tr>
                        <td class="celda-acciones">
                            <form
                                method="POST"
                                action="index.php?ruta=repositorios"
                                onsubmit="return confirm('¿Eliminar el snapshot &quot;<?= htmlspecialchars(addslashes($snapshot['nombre'])) ?>&quot;? Esta acción no se puede deshacer.');"
                            >
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $snapshot['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($snapshot['nombre']) ?></td>
                        <td>
                            <?php if (!empty($snapshot['activa'])): ?>
                            Sí — es el que se ve en Análisis
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=repositorios" style="display:inline;">
                                <input type="hidden" name="accion" value="marcar_activa_snapshot">
                                <input type="hidden" name="id" value="<?= (int) $snapshot['id'] ?>">
                                <button type="submit" class="boton-accion">Marcar activo</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $snapshot['total_tablas'] ?></td>
                        <td><?= htmlspecialchars($snapshot['creado_por_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($snapshot['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($snapshots)): ?>
                    <tr>
                        <td colspan="6">No hay snapshots creados todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </details>
    </div>

    <div class="tarjeta">
        <details class="repo-detalle">
            <summary>Árbol PDI <span class="repo-detalle-activa">Activa: <?= htmlspecialchars(repositorioEtiquetaActiva($versionesArbol)) ?></span></summary>

            <p class="texto-atenuado">
                Congela la Línea/Motor/Proyecto y los gastos del año vigente (gasto_principal +
                autogestión) que usa la pestaña "Articulación PDI" de Análisis — solo esa pestaña,
                no toda la base de datos.
            </p>

            <form method="POST" action="index.php?ruta=repositorios" class="form-agregar">
                <input type="hidden" name="accion" value="crear_arbol">
                <input type="text" name="nombre" placeholder="Nombre de la versión (opcional)">
                <button type="submit">Crear versión</button>
            </form>

            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Nombre</th>
                        <th>Activa</th>
                        <th>Creado por</th>
                        <th>Creado en</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versionesArbol as $version): ?>
                    <tr>
                        <td class="celda-acciones">
                            <form
                                method="POST"
                                action="index.php?ruta=repositorios"
                                onsubmit="return confirm('¿Eliminar la versión &quot;<?= htmlspecialchars(addslashes($version['nombre'])) ?>&quot;? Esta acción no se puede deshacer.');"
                            >
                                <input type="hidden" name="accion" value="eliminar_arbol">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($version['nombre']) ?></td>
                        <td>
                            <?php if (!empty($version['activa'])): ?>
                            Sí — es la que se ve en Análisis
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=repositorios" style="display:inline;">
                                <input type="hidden" name="accion" value="marcar_activa_arbol">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion">Marcar activa</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($version['creado_por_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($version['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($versionesArbol)): ?>
                    <tr>
                        <td colspan="5">No hay versiones creadas todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </details>
    </div>

    <?php
    $seccionesPresupuesto = [
        ['tipo' => 'egreso', 'titulo' => 'Programación presupuestal — Egresos', 'versiones' => $versionesEgresos],
        ['tipo' => 'ingreso', 'titulo' => 'Programación presupuestal — Ingresos', 'versiones' => $versionesIngresos],
    ];
    ?>
    <?php foreach ($seccionesPresupuesto as $seccion): ?>
    <div class="tarjeta">
        <details class="repo-detalle">
            <summary><?= htmlspecialchars($seccion['titulo']) ?> <span class="repo-detalle-activa">Activa: <?= htmlspecialchars(repositorioEtiquetaActiva($seccion['versiones'])) ?></span></summary>

            <p class="texto-atenuado">
                Congela las líneas, valores y Proyecto(s) PDI del presupuesto institucional de este
                lado — Egresos e Ingresos tienen cada uno su propia lista, nunca se mezclan.
            </p>

            <form method="POST" action="index.php?ruta=repositorios" class="form-agregar">
                <input type="hidden" name="accion" value="crear_presupuesto">
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($seccion['tipo']) ?>">
                <input type="text" name="nombre" placeholder="Nombre de la versión (opcional)">
                <button type="submit">Crear versión</button>
            </form>

            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Nombre</th>
                        <th>Activa</th>
                        <th>Creado por</th>
                        <th>Creado en</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seccion['versiones'] as $version): ?>
                    <tr>
                        <td class="celda-acciones">
                            <form
                                method="POST"
                                action="index.php?ruta=repositorios"
                                onsubmit="return confirm('¿Eliminar la versión &quot;<?= htmlspecialchars(addslashes($version['nombre'])) ?>&quot;? Esta acción no se puede deshacer.');"
                            >
                                <input type="hidden" name="accion" value="eliminar_presupuesto">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($version['nombre']) ?></td>
                        <td>
                            <?php if (!empty($version['activa'])): ?>
                            Sí — es la que se ve en Análisis
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=repositorios" style="display:inline;">
                                <input type="hidden" name="accion" value="marcar_activa_presupuesto">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion">Marcar activa</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($version['creado_por_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($version['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($seccion['versiones'])): ?>
                    <tr>
                        <td colspan="5">No hay versiones creadas todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </details>
    </div>
    <?php endforeach; ?>

    <div class="tarjeta">
        <details class="repo-detalle">
            <summary>Proyectos <span class="repo-detalle-activa">Activa: <?= htmlspecialchars(repositorioEtiquetaActiva($versionesProyectos)) ?></span></summary>

            <p class="texto-atenuado">
                Congela las líneas y valores del desglose de inversión por proyecto PDI — es un árbol
                de código propio, sin relación con la tabla de proyectos real ni con Articulación PDI.
            </p>

            <form method="POST" action="index.php?ruta=repositorios" class="form-agregar">
                <input type="hidden" name="accion" value="crear_proyectos_presupuesto">
                <input type="text" name="nombre" placeholder="Nombre de la versión (opcional)">
                <button type="submit">Crear versión</button>
            </form>

            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Nombre</th>
                        <th>Activa</th>
                        <th>Creado por</th>
                        <th>Creado en</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versionesProyectos as $version): ?>
                    <tr>
                        <td class="celda-acciones">
                            <form
                                method="POST"
                                action="index.php?ruta=repositorios"
                                onsubmit="return confirm('¿Eliminar la versión &quot;<?= htmlspecialchars(addslashes($version['nombre'])) ?>&quot;? Esta acción no se puede deshacer.');"
                            >
                                <input type="hidden" name="accion" value="eliminar_proyectos_presupuesto">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($version['nombre']) ?></td>
                        <td>
                            <?php if (!empty($version['activa'])): ?>
                            Sí — es la que se ve en Análisis
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=repositorios" style="display:inline;">
                                <input type="hidden" name="accion" value="marcar_activa_proyectos_presupuesto">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion">Marcar activa</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($version['creado_por_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($version['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($versionesProyectos)): ?>
                    <tr>
                        <td colspan="5">No hay versiones creadas todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </details>
    </div>

    <div class="tarjeta">
        <details class="repo-detalle">
            <summary>Techos y Metas <span class="repo-detalle-activa">Activa: <?= htmlspecialchars(repositorioEtiquetaActiva($versionesTechos)) ?></span></summary>

            <p class="texto-atenuado">
                Congela el árbol institucional completo ya calculado (sin podar por dependencia) de
                los 4 bloques de Techos y Metas — gasto principal y los 3 módulos de autogestión —
                para el año vigente y los últimos 5 años. Con eso, "Repositorio" puede mostrar tanto
                el total institucional como el filtro por cualquier dependencia, sin volver a
                consultar la base de datos.
            </p>

            <form method="POST" action="index.php?ruta=repositorios" class="form-agregar">
                <input type="hidden" name="accion" value="crear_techos">
                <input type="text" name="nombre" placeholder="Nombre de la versión (opcional)">
                <button type="submit">Crear versión</button>
            </form>

            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Nombre</th>
                        <th>Activa</th>
                        <th>Creado por</th>
                        <th>Creado en</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versionesTechos as $version): ?>
                    <tr>
                        <td class="celda-acciones">
                            <form
                                method="POST"
                                action="index.php?ruta=repositorios"
                                onsubmit="return confirm('¿Eliminar la versión &quot;<?= htmlspecialchars(addslashes($version['nombre'])) ?>&quot;? Esta acción no se puede deshacer.');"
                            >
                                <input type="hidden" name="accion" value="eliminar_techos">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($version['nombre']) ?></td>
                        <td>
                            <?php if (!empty($version['activa'])): ?>
                            Sí — es la que se ve en Análisis
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=repositorios" style="display:inline;">
                                <input type="hidden" name="accion" value="marcar_activa_techos">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion">Marcar activa</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($version['creado_por_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($version['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($versionesTechos)): ?>
                    <tr>
                        <td colspan="5">No hay versiones creadas todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </details>
    </div>

    <div class="tarjeta">
        <details class="repo-detalle">
            <summary>Documentos <span class="repo-detalle-activa">Activa: <?= htmlspecialchars(repositorioEtiquetaActiva($versionesDocumentos)) ?></span></summary>

            <p class="texto-atenuado">
                Congela la lista de enlaces de la pestaña "Documentos" de Análisis (OneDrive/SharePoint,
                incluida Actas si estaba visible) tal como estaba en el momento de crear la versión.
            </p>

            <form method="POST" action="index.php?ruta=repositorios" class="form-agregar">
                <input type="hidden" name="accion" value="crear_documentos">
                <input type="text" name="nombre" placeholder="Nombre de la versión (opcional)">
                <button type="submit">Crear versión</button>
            </form>

            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Nombre</th>
                        <th>Activa</th>
                        <th>Creado por</th>
                        <th>Creado en</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versionesDocumentos as $version): ?>
                    <tr>
                        <td class="celda-acciones">
                            <form
                                method="POST"
                                action="index.php?ruta=repositorios"
                                onsubmit="return confirm('¿Eliminar la versión &quot;<?= htmlspecialchars(addslashes($version['nombre'])) ?>&quot;? Esta acción no se puede deshacer.');"
                            >
                                <input type="hidden" name="accion" value="eliminar_documentos">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($version['nombre']) ?></td>
                        <td>
                            <?php if (!empty($version['activa'])): ?>
                            Sí — es la que se ve en Análisis
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=repositorios" style="display:inline;">
                                <input type="hidden" name="accion" value="marcar_activa_documentos">
                                <input type="hidden" name="id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="boton-accion">Marcar activa</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($version['creado_por_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($version['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($versionesDocumentos)): ?>
                    <tr>
                        <td colspan="5">No hay versiones creadas todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </details>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
