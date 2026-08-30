<?php $tituloPagina = 'Variables macroeconómicas'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Variables macroeconómicas</h1>
        <p>Indicadores de referencia para la planeación presupuestal (IPC, SMLV, UVT, tasa de cambio, etc.) por año presupuestal.</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (empty($aniosActivos)): ?>
            <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php else: ?>
        <form method="POST" action="index.php?ruta=variables-macroeconomicas" class="form-agregar">
            <input type="text" name="nombre" placeholder="Nombre (ej. IPC, SMLV, UVT)" required>
            <select name="anio_presupuestal_id" required>
                <option value="">Año presupuestal</option>
                <?php foreach ($aniosActivos as $anioOpcion): ?>
                <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="valor" placeholder="Valor" step="0.0001" required>
            <button type="submit">Agregar</button>
        </form>
        <?php endif; ?>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Acciones</th>
                    <th>Nombre</th>
                    <th>Año presupuestal</th>
                    <th>Valor</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variables as $variable): ?>
                <tr>
                    <td class="celda-acciones">
                        <div class="acciones-fila">
                            <button
                                type="button"
                                class="boton-accion boton-accion-editar boton-editar-variable"
                                data-variable="<?= htmlspecialchars(json_encode($variable)) ?>"
                            >Editar</button>
                            <form method="POST" action="index.php?ruta=variables-macroeconomicas" class="form-eliminar-variable">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $variable['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($variable['nombre']) ?></td>
                    <td><?= htmlspecialchars((string) $variable['anio']) ?></td>
                    <td><?= number_format((float) $variable['valor'], 4) ?></td>
                    <td>
                        <form method="POST" action="index.php?ruta=variables-macroeconomicas" class="form-toggle">
                            <input type="hidden" name="accion" value="cambiar_estado">
                            <input type="hidden" name="id" value="<?= (int) $variable['id'] ?>">
                            <button type="submit" class="interruptor <?= $variable['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $variable['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                <span class="interruptor-perilla"></span>
                            </button>
                            <span class="interruptor-etiqueta"><?= $variable['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($variables)): ?>
                <tr>
                    <td colspan="5">No hay variables macroeconómicas registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="modal-editar-variable" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar variable macroeconómica</h2>
                <button type="button" id="boton-cerrar-modal-editar-variable" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=variables-macroeconomicas" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="editar-variable-id" value="">

                <div class="campo">
                    <label for="editar-variable-nombre">Nombre *</label>
                    <input type="text" id="editar-variable-nombre" name="nombre" required>
                </div>

                <div class="campo">
                    <label for="editar-variable-anio">Año presupuestal *</label>
                    <select id="editar-variable-anio" name="anio_presupuestal_id" required>
                        <option value="">Año presupuestal</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-variable-valor">Valor *</label>
                    <input type="number" id="editar-variable-valor" name="valor" step="0.0001" required>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
