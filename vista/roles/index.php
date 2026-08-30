<?php $tituloPagina = 'Roles'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Roles</h1>
        <p class="texto-atenuado">El orden define la jerarquía entre roles (1 = más alto).</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=roles" class="form-agregar">
            <input type="text" name="nombre" placeholder="Nombre del rol" required>
            <input type="number" name="orden" placeholder="Orden" min="1" style="max-width: 90px;">
            <button type="submit">Agregar</button>
        </form>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Nombre</th>
                    <th>Creado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $rol): ?>
                <tr>
                    <td><?= (int) $rol['orden'] ?></td>
                    <td><?= htmlspecialchars($rol['nombre']) ?></td>
                    <td><?= htmlspecialchars($rol['creado_en']) ?></td>
                    <td class="celda-acciones">
                        <div class="acciones-fila">
                            <button
                                type="button"
                                class="boton-accion boton-accion-editar boton-editar-rol"
                                data-id="<?= (int) $rol['id'] ?>"
                                data-nombre="<?= htmlspecialchars($rol['nombre']) ?>"
                                data-orden="<?= (int) $rol['orden'] ?>"
                            >Editar</button>
                            <form method="POST" action="index.php?ruta=roles">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $rol['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($roles)): ?>
                <tr>
                    <td colspan="4">No hay roles registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="modal-editar-rol" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar rol</h2>
                <button type="button" id="boton-cerrar-modal-editar-rol" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=roles" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="editar-rol-id" value="">

                <div class="campo">
                    <label for="editar-rol-nombre">Nombre *</label>
                    <input type="text" id="editar-rol-nombre" name="nombre" required>
                </div>

                <div class="campo">
                    <label for="editar-rol-orden">Orden (jerarquía)</label>
                    <input type="number" id="editar-rol-orden" name="orden" min="1">
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
