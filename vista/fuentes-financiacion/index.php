<?php $tituloPagina = 'Fuentes de financiación'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Fuentes de financiación</h1>
        <p class="texto-atenuado">Catálogo de fuentes que las convocatorias de Perfil de proyectos habilitan para que los proyectos elijan una.</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=fuentes-financiacion" class="form-agregar">
            <input type="text" name="nombre" placeholder="Nombre de la fuente de financiación" maxlength="150" required>
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
                    <?php foreach ($fuentes as $fuente): ?>
                    <tr>
                        <td><?= htmlspecialchars($fuente['nombre']) ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=fuentes-financiacion" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $fuente['id'] ?>">
                                <button type="submit" class="interruptor <?= $fuente['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $fuente['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $fuente['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($fuentes)): ?>
                    <tr>
                        <td colspan="2">No hay fuentes de financiación registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
