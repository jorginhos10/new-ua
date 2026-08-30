<?php $tituloPagina = 'Rubros'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Rubros</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=rubros" class="form-agregar">
            <input type="text" name="codigo" placeholder="Código (ej. 101010101)" maxlength="20" required>
            <input type="text" name="descripcion" placeholder="Descripción" required>
            <button type="submit">Agregar</button>
        </form>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rubros as $rubro): ?>
                    <tr>
                        <td><?= htmlspecialchars($rubro['codigo']) ?></td>
                        <td><?= htmlspecialchars($rubro['descripcion']) ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=rubros" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $rubro['id'] ?>">
                                <button type="submit" class="interruptor <?= $rubro['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $rubro['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $rubro['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rubros)): ?>
                    <tr>
                        <td colspan="3">No hay rubros registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
