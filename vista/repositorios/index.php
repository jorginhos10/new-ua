<?php $tituloPagina = 'Repositorios'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Repositorios</h1>
        <p class="texto-atenuado">
            Un snapshot es una copia completa de todos los datos del sistema en un momento dado.
            Se usarán a futuro como fuente de datos para una herramienta de visualización, elegible por snapshot.
        </p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

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
                    <td><?= (int) $snapshot['total_tablas'] ?></td>
                    <td><?= htmlspecialchars($snapshot['creado_por_nombre'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($snapshot['creado_en']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($snapshots)): ?>
                <tr>
                    <td colspan="5">No hay snapshots creados todavía.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="tarjeta">
        <h1>Árbol PDI</h1>
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
                    <td><?= !empty($version['activa']) ? 'Sí — es la que se ve en Análisis' : '—' ?></td>
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
    </div>

    <?php
    $seccionesPresupuesto = [
        ['tipo' => 'egreso', 'titulo' => 'Programación presupuestal — Egresos', 'versiones' => $versionesEgresos],
        ['tipo' => 'ingreso', 'titulo' => 'Programación presupuestal — Ingresos', 'versiones' => $versionesIngresos],
    ];
    ?>
    <?php foreach ($seccionesPresupuesto as $seccion): ?>
    <div class="tarjeta">
        <h1><?= htmlspecialchars($seccion['titulo']) ?></h1>
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
                    <td><?= !empty($version['activa']) ? 'Sí — es la que se ve en Análisis' : '—' ?></td>
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
    </div>
    <?php endforeach; ?>

    <div class="tarjeta">
        <h1>Proyectos</h1>
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
                    <td><?= !empty($version['activa']) ? 'Sí — es la que se ve en Análisis' : '—' ?></td>
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
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
