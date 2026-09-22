<?php $tituloPagina = 'Autogestión'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Autogestión</h1>

        <div class="pestanas">
            <?php foreach ($modulos as $moduloClave => $moduloNombre): ?>
            <a href="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloClave) ?>" class="pestana<?= $moduloActivo === $moduloClave ? ' activa' : '' ?>"><?= htmlspecialchars($moduloNombre) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php
        // Extensión y Postgrado tienen ítems propios (cada uno con su propio Costos/Inversiones/
        // Excedentes, y Postgrado además Contribución a posgrado) — Unidad de Salud/Convenios
        // siguen con un único % global por módulo, porque no tienen ítems.
        $moduloTieneItems = in_array($moduloActivo, ['extension', 'postgrado'], true);
        $camposPorcentajeItem = ['costos' => 'Costos', 'inversiones' => 'Inversiones', 'excedentes' => 'Excedentes'];
        if ($moduloActivo === 'postgrado') {
            $camposPorcentajeItem['contribucion_postgrado'] = 'Contribución a posgrado';
        }
        ?>

        <?php if ($moduloTieneItems): ?>
        <div class="campo-ancho">
            <label for="campo-tope-modulo">Tope</label>
            <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" class="form-necesidad">
                <input type="hidden" name="accion" value="guardar_tope">
                <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
                <div class="campo">
                    <input
                        type="number"
                        id="campo-tope-modulo"
                        name="tope"
                        min="0"
                        step="0.01"
                        placeholder="Sin tope"
                        value="<?= $porcentajes['tope'] !== null ? htmlspecialchars((string) $porcentajes['tope']) : '' ?>"
                    >
                </div>
                <button type="submit" class="boton-enviar">Guardar tope</button>
            </form>
            <p class="texto-atenuado">Un solo tope para todo <?= htmlspecialchars($modulos[$moduloActivo]) ?> — es el denominador de la tarjeta correspondiente en Inicio (suma de ingresos de este módulo, sin archivados, contra este número).</p>
        </div>
        <?php endif; ?>

        <?php if (!$moduloTieneItems): ?>
        <div class="campo-ancho">
            <label>Porcentaje</label>
            <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" id="form-porcentaje" class="form-necesidad">
                <input type="hidden" name="accion" value="guardar_porcentaje">
                <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">

                <?php foreach (['costos' => 'Costos', 'inversiones' => 'Inversiones', 'excedentes' => 'Excedentes'] as $campoClave => $campoLabel): ?>
                <?php $noAplicaCampo = $porcentajes[$campoClave] === null; ?>
                <div class="campo">
                    <label for="<?= $campoClave ?>"><?= $campoLabel ?></label>
                    <input type="number" id="<?= $campoClave ?>" name="<?= $campoClave ?>" class="campo-porcentaje-valor" data-no-aplica="no_aplica_<?= $campoClave ?>" min="0" max="100" step="0.01" value="<?= $noAplicaCampo ? '' : htmlspecialchars((string) $porcentajes[$campoClave]) ?>" placeholder="<?= $noAplicaCampo ? 'No aplica' : '0.00' ?>" <?= $noAplicaCampo ? 'disabled' : '' ?> required>
                    <label class="campo-checkbox">
                        <input type="checkbox" name="no_aplica_<?= $campoClave ?>" value="1" <?= $noAplicaCampo ? 'checked' : '' ?>>
                        No aplica
                    </label>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="boton-enviar">Guardar porcentajes</button>
            </form>
        </div>
        <?php else: ?>
        <p class="texto-atenuado">El % de <?= implode(', ', $camposPorcentajeItem) ?> de <?= htmlspecialchars($modulos[$moduloActivo]) ?> se configura por cada ítem, en la tabla de abajo (deja el campo vacío para "No aplica").</p>
        <?php endif; ?>

        <?php if ($moduloTieneItems): ?>
        <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" class="form-agregar">
            <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
            <input type="text" name="nombre" placeholder="Nombre (ej. Gastos, Excedentes, Inversiones)" required>
            <button type="submit">Agregar</button>
        </form>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Acciones</th>
                    <th>Nombre</th>
                    <?php foreach ($camposPorcentajeItem as $etiquetaColumna): ?>
                    <th><?= htmlspecialchars($etiquetaColumna) ?> %</th>
                    <?php endforeach; ?>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="celda-acciones">
                        <div class="acciones-fila">
                            <button
                                type="button"
                                class="boton-accion boton-accion-editar boton-editar-autogestion-item"
                                data-item="<?= htmlspecialchars(json_encode($item)) ?>"
                            >Editar</button>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                    <?php foreach (array_keys($camposPorcentajeItem) as $campoPorcentaje): ?>
                    <td>
                        <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" class="form-porcentaje-autogestion-item">
                            <input type="hidden" name="accion" value="actualizar_porcentaje_item">
                            <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <?php foreach (array_keys($camposPorcentajeItem) as $campoOculto): ?>
                            <?php if ($campoOculto !== $campoPorcentaje): ?>
                            <input type="hidden" name="<?= $campoOculto ?>" value="<?= $item[$campoOculto] !== null ? htmlspecialchars((string) $item[$campoOculto]) : '' ?>">
                            <?php endif; ?>
                            <?php endforeach; ?>
                            <input
                                type="number"
                                name="<?= $campoPorcentaje ?>"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="No aplica"
                                value="<?= $item[$campoPorcentaje] !== null ? htmlspecialchars((string) $item[$campoPorcentaje]) : '' ?>"
                                onchange="this.form.requestSubmit()"
                            >
                        </form>
                    </td>
                    <?php endforeach; ?>
                    <td>
                        <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" class="form-toggle">
                            <input type="hidden" name="accion" value="cambiar_estado">
                            <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button type="submit" class="interruptor <?= $item['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $item['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                <span class="interruptor-perilla"></span>
                            </button>
                            <span class="interruptor-etiqueta"><?= $item['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?= 3 + count($camposPorcentajeItem) ?>">No hay ítems de autogestión registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div id="modal-editar-autogestion-item" class="modal-fondo">
            <div class="modal-caja">
                <div class="modal-cabecera">
                    <h2>Editar ítem de autogestión</h2>
                    <button type="button" id="boton-cerrar-modal-editar-autogestion-item" class="modal-cerrar" aria-label="Cerrar">&times;</button>
                </div>

                <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" class="form-necesidad">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
                    <input type="hidden" name="id" id="editar-autogestion-item-id" value="">

                    <div class="campo campo-ancho">
                        <label for="editar-autogestion-item-nombre">Nombre *</label>
                        <input type="text" id="editar-autogestion-item-nombre" name="nombre" required>
                    </div>

                    <button type="submit" class="boton-enviar">Guardar cambios</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
