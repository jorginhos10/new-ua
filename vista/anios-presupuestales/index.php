<?php $tituloPagina = 'Año presupuestal'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Año presupuestal</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=anios-presupuestales" class="form-agregar">
            <input type="number" name="anio" placeholder="Año (ej. 2026)" min="2000" max="2100" required>
            <input type="number" name="presupuesto" placeholder="Presupuesto" min="0" step="0.01" required>
            <button type="submit">Agregar</button>
        </form>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Año</th>
                    <th>Presupuesto</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anios as $anioFila): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $anioFila['anio']) ?></td>
                    <td><?= number_format((float) $anioFila['presupuesto'], 2) ?></td>
                    <td>
                        <form method="POST" action="index.php?ruta=anios-presupuestales" class="form-toggle">
                            <input type="hidden" name="accion" value="cambiar_estado">
                            <input type="hidden" name="id" value="<?= (int) $anioFila['id'] ?>">
                            <button type="submit" class="interruptor <?= $anioFila['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $anioFila['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                <span class="interruptor-perilla"></span>
                            </button>
                            <span class="interruptor-etiqueta"><?= $anioFila['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                        </form>
                    </td>
                    <td class="celda-acciones">
                        <div class="acciones-fila">
                            <a
                                href="index.php?ruta=configurar-presupuestos&anio_id=<?= (int) $anioFila['id'] ?>"
                                class="boton-accion boton-accion-ver"
                            >Configurar</a>
                            <button
                                type="button"
                                class="boton-accion boton-accion-editar boton-editar-anio"
                                data-id="<?= (int) $anioFila['id'] ?>"
                                data-anio="<?= (int) $anioFila['anio'] ?>"
                                data-presupuesto="<?= htmlspecialchars((string) $anioFila['presupuesto']) ?>"
                            >Editar</button>
                            <form method="POST" action="index.php?ruta=anios-presupuestales">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $anioFila['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($anios)): ?>
                <tr>
                    <td colspan="4">No hay años presupuestales registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="modal-editar-anio" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar año presupuestal</h2>
                <button type="button" id="boton-cerrar-modal-editar-anio" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=anios-presupuestales" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="editar-anio-id" value="">

                <div class="campo">
                    <label for="editar-anio-anio">Año *</label>
                    <input type="number" id="editar-anio-anio" name="anio" min="2000" max="2100" required>
                </div>

                <div class="campo">
                    <label for="editar-anio-presupuesto">Presupuesto *</label>
                    <input type="number" id="editar-anio-presupuesto" name="presupuesto" min="0" step="0.01" required>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
