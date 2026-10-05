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
            <input type="hidden" name="accion" value="crear">
            <input type="text" name="nombre" placeholder="Nombre de la fuente de financiación" maxlength="150" required>
            <button type="submit">Agregar</button>
        </form>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Proyectos</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fuentes as $fuente): $enUso = (int) $fuente['cantidad_proyectos'] > 0; ?>
                    <tr>
                        <td>
                            <form method="POST" action="index.php?ruta=fuentes-financiacion" class="form-agregar">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="id" value="<?= (int) $fuente['id'] ?>">
                                <input type="text" name="nombre" value="<?= htmlspecialchars($fuente['nombre']) ?>" maxlength="150" required aria-label="Nombre de la fuente">
                                <button type="submit" class="boton-accion boton-accion-editar">Guardar</button>
                            </form>
                        </td>
                        <td><?= (int) $fuente['cantidad_proyectos'] ?></td>
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
                        <td>
                            <form method="POST" action="index.php?ruta=fuentes-financiacion" onsubmit="return confirm('¿Eliminar esta fuente de financiación? No se puede deshacer.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $fuente['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar" <?= $enUso ? 'disabled title="Tiene proyectos; desactívala en lugar de eliminarla"' : '' ?>>Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($fuentes)): ?>
                    <tr>
                        <td colspan="4">No hay fuentes de financiación registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
