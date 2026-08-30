<?php
$tituloPagina = 'Control de versiones';

require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Control de versiones</h1>
                <p class="texto-atenuado">Historial de cambios en mínimos y techos presupuestales, como un log de commits.</p>
            </div>
            <a href="index.php?ruta=techos&anio_id=<?= (int) $anioSeleccionadoId ?>" class="boton-accion">&larr; Volver a Techos</a>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (empty($anios)): ?>
        <p class="mensaje-error">Primero debes crear un año presupuestal en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php else: ?>

        <?php if (count($anios) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="control-versiones">
                <label for="anio_id_filtro">Año presupuestal</label>
                <select id="anio_id_filtro" name="anio_id" onchange="this.form.submit()">
                    <?php foreach ($anios as $anioFila): ?>
                    <option value="<?= (int) $anioFila['id'] ?>" <?= $anioSeleccionadoId === (int) $anioFila['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $anioFila['anio']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <?php if (empty($versiones)): ?>
        <p class="texto-atenuado">Todavía no hay cambios registrados para este año.</p>
        <?php else: ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cambio anterior</th>
                        <th>Cambio actual</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versiones as $version): ?>
                    <tr>
                        <td><?= htmlspecialchars($version['creado_en']) ?></td>
                        <td class="texto-atenuado"><?= $version['usuario_anterior'] !== null ? 'por ' . htmlspecialchars($version['usuario_anterior']) : '—' ?></td>
                        <td>por <?= htmlspecialchars($version['usuario_nombre']) ?></td>
                        <td class="celda-acciones">
                            <button
                                type="button"
                                class="boton-accion boton-accion-ver boton-ver-version"
                                data-version-id="<?= (int) $version['id'] ?>"
                                data-fecha="<?= htmlspecialchars($version['creado_en']) ?>"
                                data-usuario="<?= htmlspecialchars($version['usuario_nombre']) ?>"
                                data-cambios="<?= htmlspecialchars(json_encode($version['cambios']), ENT_QUOTES) ?>"
                            >Ver</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="modal-ver-version" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Cambio del <span id="ver-version-fecha"></span></h2>
                <button type="button" id="boton-cerrar-modal-ver-version" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>
            <p class="texto-atenuado">por <strong id="ver-version-usuario"></strong></p>

            <div id="ver-version-diff" class="version-historial-diff"></div>

            <div class="acciones-formulario-presupuestos">
                <button type="button" id="boton-cerrar-modal-ver-version-pie" class="boton-accion">Cerrar</button>

                <?php if ($esSuperAdmin): ?>
                <form method="POST" action="index.php?ruta=control-versiones&anio_id=<?= (int) $anioSeleccionadoId ?>" onsubmit="return confirm('¿Restaurar esta versión? Esto sobrescribirá los valores actuales.');">
                    <input type="hidden" name="accion" value="restaurar">
                    <input type="hidden" name="version_id" id="ver-version-id-restaurar" value="">
                    <button type="submit" class="boton-accion boton-accion-editar">Restaurar</button>
                </form>

                <form method="POST" action="index.php?ruta=control-versiones&anio_id=<?= (int) $anioSeleccionadoId ?>" onsubmit="return confirm('¿Restaurar esta versión y notificar a los avaladores de las dependencias afectadas?');">
                    <input type="hidden" name="accion" value="restaurar_notificar">
                    <input type="hidden" name="version_id" id="ver-version-id-notificar" value="">
                    <button type="submit" class="boton-enviar">Restaurar y notificar</button>
                </form>
                <?php else: ?>
                <p class="texto-atenuado">Solo el superadministrador puede restaurar una versión.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
