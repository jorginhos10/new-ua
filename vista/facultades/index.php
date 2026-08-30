<?php $tituloPagina = 'Facultades'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Facultades</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=facultades" class="form-agregar">
            <input type="text" name="nombre" placeholder="Nombre de la facultad" required>
            <button type="submit">Agregar</button>
        </form>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facultades as $facultad): ?>
                    <tr>
                        <td><?= htmlspecialchars($facultad['nombre']) ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=facultades" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $facultad['id'] ?>">
                                <button type="submit" class="interruptor <?= $facultad['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $facultad['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $facultad['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($facultades)): ?>
                    <tr>
                        <td colspan="2">No hay facultades registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
