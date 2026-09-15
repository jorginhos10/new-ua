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

<?php require __DIR__ . '/../parciales/pie.php'; ?>
