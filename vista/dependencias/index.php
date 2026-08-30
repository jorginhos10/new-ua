<?php $tituloPagina = 'Dependencias'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Dependencias</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=dependencias" class="form-agregar">
            <input type="text" name="codigo" placeholder="Código" maxlength="20" required>
            <input type="text" name="nombre" placeholder="Descripción" required>
            <input type="text" name="tipo" placeholder="Tipo (ej. Facultad, Oficina)" maxlength="50">
            <button type="submit">Agregar</button>
        </form>

        <?php if (!empty($tagsFamilia)): ?>
        <div class="filtro-tags" id="filtro-familias-dependencia">
            <button type="button" class="filtro-tag activo" data-familia="">Todos</button>
            <?php foreach ($tagsFamilia as $familia => $nombreFamilia): ?>
            <button type="button" class="filtro-tag" data-familia="<?= htmlspecialchars($familia) ?>"><?= htmlspecialchars($nombreFamilia) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Flujo</th>
                        <th>Descripción</th>
                        <th>N.M
                            <span class="icono-info" tabindex="0" title="No monetizable">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            </span>
                        </th>
                        <th>N.L
                            <span class="icono-info" tabindex="0" title="No listar">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            </span>
                        </th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dependencias as $dependencia): ?>
                    <tr data-familia="<?= htmlspecialchars($familiaPorId[$dependencia['id']] ?? '') ?>">
                        <td><?= htmlspecialchars($dependencia['codigo']) ?></td>
                        <td class="texto-atenuado"><?= !empty($flujoPorId[$dependencia['id']]) ? htmlspecialchars($flujoPorId[$dependencia['id']]) : '—' ?></td>
                        <td><?= (int) ($dependencia['es_raiz_superadmin'] ?? 0) === 1 ? '<span class="estrella-super-admin" title="Raíz del Superadmin">★</span> ' : '' ?><?= htmlspecialchars($dependencia['nombre']) ?><?= (int) ($dependencia['es_raiz_superadmin'] ?? 0) === 1 ? ' <span class="texto-atenuado">(root)</span>' : '' ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=dependencias" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_no_monetizable">
                                <input type="hidden" name="id" value="<?= (int) $dependencia['id'] ?>">
                                <input type="checkbox" onchange="this.form.submit()" <?= (int) $dependencia['no_monetizable'] === 1 ? 'checked' : '' ?> title="No monetizable">
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="index.php?ruta=dependencias" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_no_listar">
                                <input type="hidden" name="id" value="<?= (int) $dependencia['id'] ?>">
                                <input type="checkbox" onchange="this.form.submit()" <?= (int) ($dependencia['no_listar'] ?? 0) === 1 ? 'checked' : '' ?> title="No listar">
                            </form>
                        </td>
                        <td><?= htmlspecialchars($dependencia['tipo'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) ($dependencia['es_raiz_superadmin'] ?? 0) === 1): ?>
                            <span class="texto-atenuado">Activo</span>
                            <?php else: ?>
                            <form method="POST" action="index.php?ruta=dependencias" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $dependencia['id'] ?>">
                                <button type="submit" class="interruptor <?= $dependencia['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $dependencia['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $dependencia['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td class="celda-acciones">
                            <?php if ((int) ($dependencia['es_raiz_superadmin'] ?? 0) !== 1): ?>
                            <div class="acciones-fila">
                                <button
                                    type="button"
                                    class="boton-accion boton-accion-editar boton-editar-dependencia"
                                    data-id="<?= (int) $dependencia['id'] ?>"
                                    data-codigo="<?= htmlspecialchars($dependencia['codigo']) ?>"
                                    data-nombre="<?= htmlspecialchars($dependencia['nombre']) ?>"
                                    data-tipo="<?= htmlspecialchars($dependencia['tipo'] ?? '') ?>"
                                    data-flujo-id="<?= $dependencia['flujo_id'] !== null ? (int) $dependencia['flujo_id'] : '' ?>"
                                >Editar</button>
                                <form method="POST" action="index.php?ruta=dependencias">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $dependencia['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dependencias)): ?>
                    <tr>
                        <td colspan="8">No hay dependencias registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal-editar-dependencia" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar dependencia</h2>
                <button type="button" id="boton-cerrar-modal-editar-dependencia" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=dependencias" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="editar-dependencia-id" value="">

                <div class="campo">
                    <label for="editar-dependencia-codigo">Código *</label>
                    <input type="text" id="editar-dependencia-codigo" name="codigo" maxlength="20" required>
                </div>

                <div class="campo">
                    <label for="editar-dependencia-nombre">Descripción *</label>
                    <input type="text" id="editar-dependencia-nombre" name="nombre" required>
                </div>

                <div class="campo">
                    <label for="editar-dependencia-tipo">Tipo</label>
                    <input type="text" id="editar-dependencia-tipo" name="tipo" maxlength="50">
                </div>

                <div class="campo campo-ancho">
                    <label for="editar-dependencia-flujo_buscador">Flujo (a quién reporta)</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'flujo_id';
                    $idBaseDependenciaOverride = 'editar-dependencia-flujo';
                    $dependenciaUsarId = true;
                    $dependenciasOpciones = array_map(static function (array $dependenciaOpcion): array {
                        return [
                            'id' => $dependenciaOpcion['id'],
                            'nombre' => (int) ($dependenciaOpcion['es_raiz_superadmin'] ?? 0) === 1
                                ? '★ Superadmin (root)'
                                : $dependenciaOpcion['codigo'] . ' - ' . $dependenciaOpcion['nombre'],
                        ];
                    }, $dependencias);
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
