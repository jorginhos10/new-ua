<?php $tituloPagina = 'Usuarios'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>Usuarios</h1>
            <?php if ($tab === 'administradores'): ?>
            <button type="button" id="boton-abrir-modal-administrador" class="boton-agregar">+ Agregar administrador</button>
            <?php elseif ($tab === 'consejo-superior'): ?>
            <button type="button" id="boton-abrir-modal-administrador" class="boton-agregar">+ Agregar miembro</button>
            <?php endif; ?>
        </div>

        <div class="pestanas">
            <a href="index.php?ruta=usuarios&tab=administradores" class="pestana<?= $tab === 'administradores' ? ' activa' : '' ?>">Administradores</a>
            <a href="index.php?ruta=usuarios&tab=consejo-superior" class="pestana<?= $tab === 'consejo-superior' ? ' activa' : '' ?>">Consulta</a>
            <a href="index.php?ruta=usuarios&tab=invitados" class="pestana<?= $tab === 'invitados' ? ' activa' : '' ?>">Formulador</a>
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
                            <td>
                                <?php if ((int) ($admin['es_super_admin'] ?? 0) === 1): ?>
                                Superadmin
                                <?php elseif (!empty($admin['rol_catalogo'])): ?>
                                <div class="celda-rol-nombre">
                                    <span class="icono-rol" style="background-color: <?= htmlspecialchars($admin['rol_color'] ?? '#0071e3') ?>;"><?= htmlspecialchars(mb_strtoupper(mb_substr($admin['rol_catalogo'], 0, 1))) ?></span>
                                    <?= htmlspecialchars($admin['rol_catalogo']) ?>
                                </div>
                                <?php else: ?>
                                —
                                <?php endif; ?>
                            </td>
                            <td><?= (int) ($admin['es_super_admin'] ?? 0) === 1 ? 'Superadmin' : htmlspecialchars($admin['dependencia_nombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($admin['estamento_nombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($admin['creado_en']) ?></td>
                            <td><?= $admin['ultimo_acceso'] !== null ? htmlspecialchars($admin['ultimo_acceso']) : '<span class="texto-atenuado">Nunca</span>' ?></td>
                            <td class="celda-acciones">
                                <div class="acciones-fila">
                                    <?php if ((int) ($admin['es_super_admin'] ?? 0) !== 1 && (int) $admin['id'] !== (int) $_SESSION['usuario_id']): ?>
                                    <a
                                        href="index.php?ruta=impersonar&id=<?= (int) $admin['id'] ?>"
                                        target="_blank"
                                        rel="opener"
                                        class="boton-accion boton-accion-enviar boton-accion-icono"
                                        title="Ingresar a la cuenta de <?= htmlspecialchars($admin['nombre']) ?> (se abre en una pestaña nueva)"
                                        aria-label="Ingresar a la cuenta de <?= htmlspecialchars($admin['nombre']) ?>"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                    </a>
                                    <?php endif; ?>
                                    <button
                                        type="button"
                                        class="boton-accion boton-accion-editar boton-editar-usuario"
                                        data-id="<?= (int) $admin['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($admin['nombre']) ?>"
                                        data-correo="<?= htmlspecialchars($admin['correo']) ?>"
                                        data-rol-id="<?= (int) ($admin['rol_id'] ?? 0) ?>"
                                        data-dependencia-id="<?= (int) ($admin['dependencia_id'] ?? 0) ?>"
                                        data-dependencia-tipo="<?= htmlspecialchars($admin['dependencia_tipo'] ?? '') ?>"
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
        <?php elseif ($tab === 'consejo-superior'): ?>
            <section class="panel-pestana">
                <h2>Consulta</h2>
                <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Creado</th>
                            <th>Último acceso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consejoSuperior as $miembro): ?>
                        <tr>
                            <td><?= htmlspecialchars($miembro['nombre']) ?></td>
                            <td><?= htmlspecialchars($miembro['correo']) ?></td>
                            <td>
                                <?php if (!empty($miembro['rol_catalogo'])): ?>
                                <div class="celda-rol-nombre">
                                    <span class="icono-rol" style="background-color: <?= htmlspecialchars($miembro['rol_color'] ?? '#0071e3') ?>;"><?= htmlspecialchars(mb_strtoupper(mb_substr($miembro['rol_catalogo'], 0, 1))) ?></span>
                                    <?= htmlspecialchars($miembro['rol_catalogo']) ?>
                                </div>
                                <?php else: ?>
                                —
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($miembro['creado_en']) ?></td>
                            <td><?= $miembro['ultimo_acceso'] !== null ? htmlspecialchars($miembro['ultimo_acceso']) : '<span class="texto-atenuado">Nunca</span>' ?></td>
                            <td class="celda-acciones">
                                <div class="acciones-fila">
                                    <?php if ((int) $miembro['id'] !== (int) $_SESSION['usuario_id']): ?>
                                    <a
                                        href="index.php?ruta=impersonar&id=<?= (int) $miembro['id'] ?>"
                                        target="_blank"
                                        rel="opener"
                                        class="boton-accion boton-accion-enviar boton-accion-icono"
                                        title="Ingresar a la cuenta de <?= htmlspecialchars($miembro['nombre']) ?> (se abre en una pestaña nueva)"
                                        aria-label="Ingresar a la cuenta de <?= htmlspecialchars($miembro['nombre']) ?>"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                    </a>
                                    <?php endif; ?>
                                    <button
                                        type="button"
                                        class="boton-accion boton-accion-editar boton-editar-usuario"
                                        data-id="<?= (int) $miembro['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($miembro['nombre']) ?>"
                                        data-correo="<?= htmlspecialchars($miembro['correo']) ?>"
                                        data-rol-id="<?= (int) ($miembro['rol_id'] ?? 0) ?>"
                                        data-dependencia-id="<?= (int) ($miembro['dependencia_id'] ?? 0) ?>"
                                        data-dependencia-tipo="<?= htmlspecialchars($miembro['dependencia_tipo'] ?? '') ?>"
                                        data-estamento-id="<?= (int) ($miembro['estamento_id'] ?? 0) ?>"
                                    >Editar</button>
                                    <button
                                        type="button"
                                        class="boton-accion boton-accion-ver boton-permisos-usuario"
                                        data-id="<?= (int) $miembro['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($miembro['nombre']) ?>"
                                        data-rol-id="0"
                                        data-dependencia-id="0"
                                        data-menu-efectivo="<?= htmlspecialchars(json_encode($miembro['menu_efectivo'])) ?>"
                                    >Permisos</button>
                                    <form method="POST" action="index.php?ruta=usuarios&tab=consejo-superior">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="tab" value="consejo-superior">
                                        <input type="hidden" name="id" value="<?= (int) $miembro['id'] ?>">
                                        <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($consejoSuperior)): ?>
                        <tr>
                            <td colspan="6">No hay miembros de Consulta registrados.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </section>
        <?php else: ?>
            <section class="panel-pestana">
                <h2>Formulador (invitados registrados)</h2>
                <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Creado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invitados as $invitado): ?>
                        <tr>
                            <td><?= htmlspecialchars($invitado['nombre']) ?></td>
                            <td><?= htmlspecialchars($invitado['correo']) ?></td>
                            <td>
                                <?php if (!empty($invitado['rol_catalogo'])): ?>
                                <div class="celda-rol-nombre">
                                    <span class="icono-rol" style="background-color: <?= htmlspecialchars($invitado['rol_color'] ?? '#0071e3') ?>;"><?= htmlspecialchars(mb_strtoupper(mb_substr($invitado['rol_catalogo'], 0, 1))) ?></span>
                                    <?= htmlspecialchars($invitado['rol_catalogo']) ?>
                                </div>
                                <?php else: ?>
                                —
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($invitado['creado_en']) ?></td>
                            <td class="celda-acciones">
                                <div class="acciones-fila">
                                    <?php if ((int) $invitado['id'] !== (int) $_SESSION['usuario_id']): ?>
                                    <a
                                        href="index.php?ruta=impersonar&id=<?= (int) $invitado['id'] ?>"
                                        target="_blank"
                                        rel="opener"
                                        class="boton-accion boton-accion-enviar boton-accion-icono"
                                        title="Ingresar a la cuenta de <?= htmlspecialchars($invitado['nombre']) ?> (se abre en una pestaña nueva)"
                                        aria-label="Ingresar a la cuenta de <?= htmlspecialchars($invitado['nombre']) ?>"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                    </a>
                                    <?php endif; ?>
                                    <button
                                        type="button"
                                        class="boton-accion boton-accion-editar boton-editar-usuario"
                                        data-id="<?= (int) $invitado['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($invitado['nombre']) ?>"
                                        data-correo="<?= htmlspecialchars($invitado['correo']) ?>"
                                        data-rol-id="<?= (int) ($invitado['rol_id'] ?? 0) ?>"
                                        data-dependencia-id="<?= (int) ($invitado['dependencia_id'] ?? 0) ?>"
                                        data-dependencia-tipo="<?= htmlspecialchars($invitado['dependencia_tipo'] ?? '') ?>"
                                        data-estamento-id="<?= (int) ($invitado['estamento_id'] ?? 0) ?>"
                                    >Editar</button>
                                    <button
                                        type="button"
                                        class="boton-accion boton-accion-ver boton-permisos-usuario"
                                        data-id="<?= (int) $invitado['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($invitado['nombre']) ?>"
                                        data-rol-id="0"
                                        data-dependencia-id="0"
                                        data-menu-efectivo="<?= htmlspecialchars(json_encode($invitado['menu_efectivo'])) ?>"
                                    >Permisos</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($invitados)): ?>
                        <tr>
                            <td colspan="5">No hay invitados registrados.</td>
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

    <?php if ($tab === 'administradores' || $tab === 'consejo-superior'): ?>
    <div id="modal-administrador" class="modal-fondo<?= !empty($error) ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2><?= $tab === 'consejo-superior' ? 'Agregar miembro del Consejo Superior' : 'Agregar administrador' ?></h2>
                <button type="button" id="boton-cerrar-modal-administrador" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=usuarios&tab=<?= $tab ?>" class="form-necesidad">
                <input type="hidden" name="rol_cuenta" value="<?= $tab === 'consejo-superior' ? 'consejo_superior' : 'administrador' ?>">
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
                        <option value="<?= (int) $rolCatalogo['id'] ?>" <?= ($tab === 'consejo-superior' && $rolCatalogo['nombre'] === 'Consulta') ? 'selected' : '' ?>><?= htmlspecialchars($rolCatalogo['nombre']) ?></option>
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
    <?php endif; ?>

    <?php if ($tab === 'administradores' || $tab === 'consejo-superior' || $tab === 'invitados'): ?>
    <div id="modal-editar-usuario" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar usuario</h2>
                <button type="button" id="boton-cerrar-modal-editar-usuario" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=usuarios&tab=<?= $tab ?>" class="form-necesidad">
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

                <?php if ($tab === 'invitados'): ?>
                <div class="campo">
                    <label>Tipo</label>
                    <p class="texto-atenuado">Formulador</p>
                </div>
                <?php else: ?>
                <div class="campo">
                    <label for="editar-usuario-tipo">Tipo</label>
                    <select id="editar-usuario-tipo" data-select-dependencia="editar-usuario-dependencia">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tiposDependencia as $tipoDependencia): ?>
                        <option value="<?= htmlspecialchars($tipoDependencia) ?>"><?= htmlspecialchars($tipoDependencia) ?></option>
                        <?php endforeach; ?>
                        <option value="__personalizado__" hidden>Personalizado</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="campo">
                    <label for="editar-usuario-dependencia_buscador">Dependencia</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_id';
                    $idBaseDependenciaOverride = 'editar-usuario-dependencia';
                    $dependenciaUsarId = true;
                    $dependenciasOpciones = $tab === 'invitados' ? $dependenciasInvitados : $dependencias;
                    $dependenciaDataSelectRol = $tab === 'invitados' ? null : 'editar-usuario-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <?php if ($tab === 'invitados'): ?>
                <div class="campo">
                    <label>Rol</label>
                    <p class="texto-atenuado">Formulador</p>
                </div>
                <?php else: ?>
                <div class="campo">
                    <label for="editar-usuario-rol">Rol</label>
                    <select id="editar-usuario-rol" name="rol_id">
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolCatalogo): ?>
                        <option value="<?= (int) $rolCatalogo['id'] ?>"><?= htmlspecialchars($rolCatalogo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

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
    <?php endif; ?>

    <?php if ($tab === 'administradores' || $tab === 'consejo-superior' || $tab === 'invitados'): ?>
    <div id="modal-permisos-usuario" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Permisos de <span id="permisos-usuario-nombre"></span></h2>
                <button type="button" id="boton-cerrar-modal-permisos-usuario" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=usuarios&tab=<?= $tab ?>" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar_permisos">
                <input type="hidden" name="id" id="permisos-usuario-id" value="">

                <?php if ($tab === 'administradores'): ?>
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
                <?php else: ?>
                <p class="texto-atenuado">
                    El rol y la dependencia de esta cuenta son fijos (<?= $tab === 'consejo-superior' ? 'Consulta' : 'Formulador' ?>) —
                    aquí solo se ajusta qué ve en el menú lateral.
                </p>
                <?php endif; ?>

                <div class="campo campo-ancho">
                    <label>
                        Menú visible
                        <?php if ($tab === 'administradores'): ?>
                        <button type="button" id="boton-restablecer-menu-permisos" class="boton-enlace">Restablecer a la plantilla del tipo</button>
                        <?php endif; ?>
                    </label>
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
