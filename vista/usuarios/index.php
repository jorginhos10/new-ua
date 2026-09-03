<?php $tituloPagina = 'Usuarios'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>Usuarios</h1>
            <?php if ($tab === 'administradores'): ?>
            <button type="button" id="boton-abrir-modal-administrador" class="boton-agregar">+ Agregar administrador</button>
            <?php endif; ?>
        </div>

        <div class="pestanas">
            <a href="index.php?ruta=usuarios&tab=administradores" class="pestana<?= $tab === 'administradores' ? ' activa' : '' ?>">Administradores</a>
            <a href="index.php?ruta=usuarios&tab=invitados" class="pestana<?= $tab === 'invitados' ? ' activa' : '' ?>">Invitados</a>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if ($tab === 'administradores'): ?>
            <section class="panel-pestana">
                <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Dependencia</th>
                            <th>Estamento</th>
                            <th>Creado</th>
                            <th>Último acceso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($administradores as $admin): ?>
                        <tr>
                            <td><?= (int) ($admin['es_super_admin'] ?? 0) === 1 ? '<span class="estrella-super-admin" title="Super administrador">★</span> ' : '' ?><?= htmlspecialchars($admin['nombre']) ?></td>
                            <td><?= htmlspecialchars($admin['correo']) ?></td>
                            <td><?= (int) ($admin['es_super_admin'] ?? 0) === 1 ? 'Superadmin' : htmlspecialchars($admin['rol_catalogo'] ?? '—') ?></td>
                            <td><?= (int) ($admin['es_super_admin'] ?? 0) === 1 ? 'Superadmin' : htmlspecialchars($admin['dependencia_nombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($admin['estamento_nombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($admin['creado_en']) ?></td>
                            <td><?= $admin['ultimo_acceso'] !== null ? htmlspecialchars($admin['ultimo_acceso']) : '<span class="texto-atenuado">Nunca</span>' ?></td>
                            <td class="celda-acciones">
                                <div class="acciones-fila">
                                    <button
                                        type="button"
                                        class="boton-accion boton-accion-editar boton-editar-usuario"
                                        data-id="<?= (int) $admin['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($admin['nombre']) ?>"
                                        data-correo="<?= htmlspecialchars($admin['correo']) ?>"
                                        data-rol-id="<?= (int) ($admin['rol_id'] ?? 0) ?>"
                                        data-dependencia-id="<?= (int) ($admin['dependencia_id'] ?? 0) ?>"
                                        data-estamento-id="<?= (int) ($admin['estamento_id'] ?? 0) ?>"
                                    >Editar</button>
                                    <button
                                        type="button"
                                        class="boton-accion boton-accion-ver boton-permisos-usuario"
                                        data-id="<?= (int) $admin['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($admin['nombre']) ?>"
                                        data-rol-id="<?= (int) ($admin['rol_id'] ?? 0) ?>"
                                        data-dependencia-id="<?= (int) ($admin['dependencia_id'] ?? 0) ?>"
                                        data-menu-efectivo="<?= htmlspecialchars(json_encode($admin['menu_efectivo'])) ?>"
                                    >Permisos</button>
                                    <form method="POST" action="index.php?ruta=usuarios&tab=administradores">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                                        <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($administradores)): ?>
                        <tr>
                            <td colspan="8">No hay administradores registrados.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </section>
        <?php else: ?>
            <section class="panel-pestana">
                <h2>Invitados registrados</h2>
                <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Creado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invitados as $invitado): ?>
                        <tr>
                            <td><?= htmlspecialchars($invitado['nombre']) ?></td>
                            <td><?= htmlspecialchars($invitado['correo']) ?></td>
                            <td><?= htmlspecialchars($invitado['creado_en']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($invitados)): ?>
                        <tr>
                            <td colspan="3">No hay invitados registrados.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <script type="application/json" id="datos-roles-por-tipo"><?= json_encode($rolesPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script type="application/json" id="datos-menu-por-tipo-usuarios"><?= json_encode($menuPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <?php if ($tab === 'administradores'): ?>
    <div id="modal-administrador" class="modal-fondo<?= !empty($error) ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Agregar administrador</h2>
                <button type="button" id="boton-cerrar-modal-administrador" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=usuarios&tab=administradores" class="form-necesidad">
                <div class="campo">
                    <label for="admin-nombre">Nombre completo *</label>
                    <input type="text" id="admin-nombre" name="nombre" placeholder="Nombre completo" required>
                </div>

                <div class="campo">
                    <label for="admin-correo">Correo electrónico *</label>
                    <input type="email" id="admin-correo" name="correo" placeholder="Correo electrónico" required>
                </div>

                <div class="campo">
                    <label for="admin-password">Contraseña *</label>
                    <input type="password" id="admin-password" name="password" placeholder="Contraseña" required>
                </div>

                <div class="campo">
                    <label for="admin-tipo">Tipo</label>
                    <select id="admin-tipo" data-select-dependencia="admin-dependencia">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tiposDependencia as $tipoDependencia): ?>
                        <option value="<?= htmlspecialchars($tipoDependencia) ?>"><?= htmlspecialchars($tipoDependencia) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="admin-dependencia_buscador">Dependencia</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_id';
                    $idBaseDependenciaOverride = 'admin-dependencia';
                    $dependenciaUsarId = true;
                    $dependenciasOpciones = $dependencias;
                    $dependenciaDataSelectRol = 'admin-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="admin-rol">Rol</label>
                    <select id="admin-rol" name="rol_id">
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolCatalogo): ?>
                        <option value="<?= (int) $rolCatalogo['id'] ?>"><?= htmlspecialchars($rolCatalogo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="admin-estamento">Estamento</label>
                    <select id="admin-estamento" name="estamento_id">
                        <option value="">Selecciona un estamento</option>
                        <?php foreach ($estamentos as $estamentoCatalogo): ?>
                        <option value="<?= (int) $estamentoCatalogo['id'] ?>"><?= htmlspecialchars($estamentoCatalogo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="boton-enviar">Agregar</button>
            </form>
        </div>
    </div>

    <div id="modal-editar-usuario" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar usuario</h2>
                <button type="button" id="boton-cerrar-modal-editar-usuario" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=usuarios&tab=administradores" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="editar-usuario-id" value="">

                <div class="campo">
                    <label for="editar-usuario-nombre">Nombre completo *</label>
                    <input type="text" id="editar-usuario-nombre" name="nombre" required>
                </div>

                <div class="campo">
                    <label for="editar-usuario-correo">Correo electrónico *</label>
                    <input type="email" id="editar-usuario-correo" name="correo" required>
                </div>

                <div class="campo">
                    <label for="editar-usuario-password">Nueva contraseña</label>
                    <input type="password" id="editar-usuario-password" name="password" placeholder="Dejar en blanco para no cambiarla">
                </div>

                <div class="campo">
                    <label for="editar-usuario-tipo">Tipo</label>
                    <select id="editar-usuario-tipo" data-select-dependencia="editar-usuario-dependencia">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tiposDependencia as $tipoDependencia): ?>
                        <option value="<?= htmlspecialchars($tipoDependencia) ?>"><?= htmlspecialchars($tipoDependencia) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-usuario-dependencia_buscador">Dependencia</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_id';
                    $idBaseDependenciaOverride = 'editar-usuario-dependencia';
                    $dependenciaUsarId = true;
                    $dependenciasOpciones = $dependencias;
                    $dependenciaDataSelectRol = 'editar-usuario-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="editar-usuario-rol">Rol</label>
                    <select id="editar-usuario-rol" name="rol_id">
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolCatalogo): ?>
                        <option value="<?= (int) $rolCatalogo['id'] ?>"><?= htmlspecialchars($rolCatalogo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-usuario-estamento">Estamento</label>
                    <select id="editar-usuario-estamento" name="estamento_id">
                        <option value="">Selecciona un estamento</option>
                        <?php foreach ($estamentos as $estamentoCatalogo): ?>
                        <option value="<?= (int) $estamentoCatalogo['id'] ?>"><?= htmlspecialchars($estamentoCatalogo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

    <div id="modal-permisos-usuario" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Permisos de <span id="permisos-usuario-nombre"></span></h2>
                <button type="button" id="boton-cerrar-modal-permisos-usuario" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=usuarios&tab=administradores" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar_permisos">
                <input type="hidden" name="id" id="permisos-usuario-id" value="">

                <div class="campo">
                    <label for="permisos-usuario-tipo">Tipo</label>
                    <select id="permisos-usuario-tipo" data-select-dependencia="permisos-usuario-dependencia">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tiposDependencia as $tipoDependencia): ?>
                        <option value="<?= htmlspecialchars($tipoDependencia) ?>"><?= htmlspecialchars($tipoDependencia) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="permisos-usuario-dependencia_buscador">Dependencia</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_id';
                    $idBaseDependenciaOverride = 'permisos-usuario-dependencia';
                    $dependenciaUsarId = true;
                    $dependenciasOpciones = $dependencias;
                    $dependenciaDataSelectRol = 'permisos-usuario-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="permisos-usuario-rol">Rol</label>
                    <select id="permisos-usuario-rol" name="rol_id">
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolCatalogo): ?>
                        <option value="<?= (int) $rolCatalogo['id'] ?>"><?= htmlspecialchars($rolCatalogo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label>Menú visible <button type="button" id="boton-restablecer-menu-permisos" class="boton-enlace">Restablecer a la plantilla del tipo</button></label>
                    <?php foreach ($itemsMenu as $grupoNombre => $itemsGrupo): ?>
                    <p class="texto-atenuado" style="margin: 0.5rem 0 0.2rem;"><?= htmlspecialchars($grupoNombre) ?></p>
                    <div class="grupo-checkbox-roles">
                        <?php foreach ($itemsGrupo as $itemClave => $itemLabel): ?>
                        <label class="campo-checkbox">
                            <input type="checkbox" name="menu[]" value="<?= htmlspecialchars($itemClave) ?>">
                            <?= htmlspecialchars($itemLabel) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="boton-enviar">Guardar permisos</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
