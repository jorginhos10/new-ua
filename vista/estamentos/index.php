<?php $tituloPagina = 'Estamentos'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="tarjeta-encabezado">
            <h1>Estamentos</h1>
            <?php $csvRuta = 'estamentos'; $csvEtiqueta = 'Estamentos'; require __DIR__ . '/../parciales/csv-configuracion.php'; ?>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=estamentos" class="form-agregar">
            <input type="text" name="nombre" placeholder="Nombre del estamento" required>
            <button type="submit">Agregar</button>
        </form>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Creado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estamentos as $estamento): ?>
                <tr>
                    <td><?= htmlspecialchars($estamento['nombre']) ?></td>
                    <td><?= htmlspecialchars($estamento['creado_en']) ?></td>
                    <td class="celda-acciones">
                        <div class="acciones-fila">
                            <button
                                type="button"
                                class="boton-accion boton-accion-editar boton-editar-estamento"
                                data-id="<?= (int) $estamento['id'] ?>"
                                data-nombre="<?= htmlspecialchars($estamento['nombre']) ?>"
                            >Editar</button>
                            <form method="POST" action="index.php?ruta=estamentos">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $estamento['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($estamentos)): ?>
                <tr>
                    <td colspan="3">No hay estamentos registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="modal-editar-estamento" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar estamento</h2>
                <button type="button" id="boton-cerrar-modal-editar-estamento" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=estamentos" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="editar-estamento-id" value="">

                <div class="campo campo-ancho">
                    <label for="editar-estamento-nombre">Nombre *</label>
                    <input type="text" id="editar-estamento-nombre" name="nombre" required>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
