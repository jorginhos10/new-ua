<?php $tituloPagina = 'Líneas de inversión'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="tarjeta-encabezado">
            <h1>Líneas de inversión</h1>
            <?php $csvRuta = 'lineas-inversion'; $csvEtiqueta = 'Líneas de inversión'; require __DIR__ . '/../parciales/csv-configuracion.php'; ?>
        </div>
        <p class="texto-atenuado">Catálogo de líneas de inversión que se pueden elegir al registrar un proyecto en Perfil de proyectos.</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=lineas-inversion" class="form-agregar">
            <input type="text" name="codigo" placeholder="Código (ej. LI1)" maxlength="20" required>
            <input type="text" name="nombre" placeholder="Nombre de la línea de inversión" required>
            <input type="text" name="descripcion" placeholder="Descripción (se muestra al pasar el mouse)" required>
            <button type="submit">Agregar</button>
        </form>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lineasInversion as $linea): ?>
                    <tr>
                        <td><?= htmlspecialchars($linea['codigo']) ?></td>
                        <td><?= htmlspecialchars($linea['nombre']) ?></td>
                        <td><?= htmlspecialchars($linea['descripcion']) ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=lineas-inversion" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $linea['id'] ?>">
                                <button type="submit" class="interruptor <?= $linea['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $linea['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $linea['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lineasInversion)): ?>
                    <tr>
                        <td colspan="4">No hay líneas de inversión registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
