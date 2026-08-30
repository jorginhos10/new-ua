<?php $tituloPagina = 'Jerarquías'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Jerarquías</h1>
                <p>Árbol de centros de costo (OPL, vicerrectorías, departamentos, facultades, programas).</p>
            </div>
            <div class="selector-modo-vista">
                <button type="button" class="selector-modo-vista-boton activo" data-modo-vista="estructura">Estructural</button>
                <button type="button" class="selector-modo-vista-boton" data-modo-vista="mapa">Mapa</button>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <div data-vista-jerarquia="estructura">
        <form method="POST" action="index.php?ruta=jerarquias" class="form-necesidad">
            <div class="campo">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej. ViceDocencia" required>
            </div>

            <div class="campo">
                <label for="padre_id">Depende de</label>
                <select id="padre_id" name="padre_id">
                    <option value="0">— Sin padre (raíz) —</option>
                    <?php foreach ($padresDisponibles as $padreOpcion): ?>
                    <option value="<?= (int) $padreOpcion['id'] ?>"><?= htmlspecialchars($padreOpcion['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" data-select-roles="roles">
                    <option value="">Sin tipo</option>
                    <?php foreach ($tiposDependencia as $tipoDependencia): ?>
                    <option value="<?= htmlspecialchars($tipoDependencia) ?>"><?= htmlspecialchars($tipoDependencia) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo campo-ancho">
                <label>Roles asociados al tipo</label>
                <div id="roles" class="grupo-checkbox-roles">
                    <?php foreach ($roles as $rolCatalogo): ?>
                    <label class="campo-checkbox">
                        <input type="checkbox" name="roles[]" value="<?= (int) $rolCatalogo['id'] ?>">
                        <?= htmlspecialchars($rolCatalogo['nombre']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="boton-enviar">Agregar</button>
        </form>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nombre</th>
                        <th>Depende de</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jerarquias as $nodo): ?>
                    <tr>
                        <td class="celda-fila-menu">
                            <div class="fila-menu">
                                <button type="button" class="fila-menu-boton" aria-label="Más opciones">⋯</button>
                                <div class="fila-menu-dropdown">
                                    <button
                                        type="button"
                                        class="fila-menu-editar"
                                        data-id="<?= (int) $nodo['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($nodo['nombre']) ?>"
                                        data-padre-id="<?= $nodo['padre_id'] !== null ? (int) $nodo['padre_id'] : 0 ?>"
                                        data-tipo="<?= htmlspecialchars($nodo['tipo'] ?? '') ?>"
                                    >Editar</button>
                                    <form method="POST" action="index.php?ruta=jerarquias" class="form-eliminar-jerarquia">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= (int) $nodo['id'] ?>">
                                        <button type="submit" class="fila-menu-eliminar">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="arbol-nombre">
                                <?php for ($i = 0; $i < (int) $nodo['nivel'] - 1; $i++): ?>
                                <span class="arbol-guia"></span>
                                <?php endfor; ?>
                                <?php if ((int) $nodo['nivel'] > 0): ?>
                                <span class="arbol-guia arbol-guia-final"></span>
                                <?php endif; ?>
                                <span class="arbol-etiqueta nivel-<?= min((int) $nodo['nivel'], 4) ?>"><?= htmlspecialchars($nodo['nombre']) ?></span>
                            </div>
                        </td>
                        <td class="texto-atenuado"><?= $nodo['nombre_padre'] !== null ? '↳ ' . htmlspecialchars($nodo['nombre_padre']) : '—' ?></td>
                        <td class="texto-atenuado"><?= !empty($nodo['tipo']) ? htmlspecialchars($nodo['tipo']) : '—' ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=jerarquias" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $nodo['id'] ?>">
                                <button type="submit" class="interruptor <?= $nodo['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $nodo['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $nodo['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($jerarquias)): ?>
                    <tr>
                        <td colspan="5">No hay jerarquías registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>

    <div class="tarjeta" data-vista-jerarquia="mapa" hidden>
        <h2>Organigrama</h2>

        <?php if (empty($jerarquiasAnidadas)): ?>
        <p class="texto-atenuado">No hay jerarquías registradas.</p>
        <?php else: ?>
        <div class="organigrama-envoltorio">
            <ul class="organigrama">
                <?php
                $rolesPorId = array_column($roles, null, 'id');

                $dibujarNodo = function (array $nodo) use (&$dibujarNodo, $rolesPorTipo, $rolesPorId): void {
                    $rolesNodo = [];

                    if (!empty($nodo['tipo']) && !empty($rolesPorTipo[$nodo['tipo']])) {
                        foreach ($rolesPorTipo[$nodo['tipo']] as $rolId) {
                            if (isset($rolesPorId[$rolId])) {
                                $rolesNodo[] = $rolesPorId[$rolId];
                            }
                        }

                        usort($rolesNodo, fn (array $a, array $b) => $a['orden'] <=> $b['orden']);
                    }

                    echo '<li>';
                    echo '<div class="organigrama-caja">';
                    if (!empty($nodo['tipo'])) {
                        echo '<button type="button" class="organigrama-boton-menu boton-configurar-menu-tipo" data-tipo="' . htmlspecialchars($nodo['tipo']) . '" title="Configurar menú visible para ' . htmlspecialchars($nodo['tipo']) . '">⚙</button>';
                    }
                    echo '<div class="organigrama-cabecera">';
                    echo '<span class="organigrama-nombre">' . htmlspecialchars($nodo['nombre']) . '</span>';
                    if ($nodo['techo'] !== null) {
                        echo '<span class="organigrama-techo">$ ' . number_format((float) $nodo['techo'], 2) . '</span>';
                    }
                    if ($nodo['estado'] !== 'activo') {
                        echo '<span class="organigrama-inactivo">Inactivo</span>';
                    }
                    echo '</div>';
                    if (!empty($rolesNodo)) {
                        echo '<div class="organigrama-roles">';
                        foreach ($rolesNodo as $rol) {
                            $nivel = min((int) $rol['orden'], 3);
                            echo '<span class="organigrama-rol-chip">';
                            echo '<span class="organigrama-rol-numero nivel-' . $nivel . '">' . (int) $rol['orden'] . '</span>';
                            echo htmlspecialchars($rol['nombre']);
                            echo '</span>';
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                    if (!empty($nodo['hijos'])) {
                        echo '<ul>';
                        foreach ($nodo['hijos'] as $hijo) {
                            $dibujarNodo($hijo);
                        }
                        echo '</ul>';
                    }
                    echo '</li>';
                };

                foreach ($jerarquiasAnidadas as $raiz) {
                    $dibujarNodo($raiz);
                }
                ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <div id="modal-editar-jerarquia" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar jerarquía</h2>
                <button type="button" id="boton-cerrar-modal-editar-jerarquia" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=jerarquias" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="editar-jerarquia-id" value="">

                <div class="campo">
                    <label for="editar-jerarquia-nombre">Nombre *</label>
                    <input type="text" id="editar-jerarquia-nombre" name="nombre" required>
                </div>

                <div class="campo">
                    <label for="editar-jerarquia-padre">Depende de</label>
                    <select id="editar-jerarquia-padre" name="padre_id">
                        <option value="0">— Sin padre (raíz) —</option>
                        <?php foreach ($padresDisponibles as $padreOpcion): ?>
                        <option value="<?= (int) $padreOpcion['id'] ?>"><?= htmlspecialchars($padreOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-jerarquia-tipo">Tipo</label>
                    <select id="editar-jerarquia-tipo" name="tipo" data-select-roles="editar-jerarquia-roles">
                        <option value="">Sin tipo</option>
                        <?php foreach ($tiposDependencia as $tipoDependencia): ?>
                        <option value="<?= htmlspecialchars($tipoDependencia) ?>"><?= htmlspecialchars($tipoDependencia) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label>Roles asociados al tipo</label>
                    <div id="editar-jerarquia-roles" class="grupo-checkbox-roles">
                        <?php foreach ($roles as $rolCatalogo): ?>
                        <label class="campo-checkbox">
                            <input type="checkbox" name="roles[]" value="<?= (int) $rolCatalogo['id'] ?>">
                            <?= htmlspecialchars($rolCatalogo['nombre']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

    <div id="modal-menu-tipo" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Menú visible para "<span id="menu-tipo-nombre"></span>"</h2>
                <button type="button" id="boton-cerrar-modal-menu-tipo" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Esta es la plantilla predeterminada de lo que puede ver un usuario asociado a este tipo de dependencia. Puedes definir una plantilla distinta por rol (por ejemplo, Avalador vs. Gestor), o dejarla en "General" para que aplique a todos. Se puede individualizar por usuario desde Usuarios &rarr; Permisos.</p>

            <form method="POST" action="index.php?ruta=jerarquias" class="form-necesidad">
                <input type="hidden" name="accion" value="guardar_menu">
                <input type="hidden" name="tipo" id="menu-tipo-valor" value="">

                <div class="campo campo-ancho">
                    <label for="menu-tipo-rol">Rol</label>
                    <select id="menu-tipo-rol" name="rol_id">
                        <option value="">General (todos los roles)</option>
                        <?php foreach ($roles as $rolCatalogo): ?>
                        <option value="<?= (int) $rolCatalogo['id'] ?>" data-rol-id="<?= (int) $rolCatalogo['id'] ?>"><?= htmlspecialchars($rolCatalogo['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php foreach ($itemsMenu as $grupoNombre => $itemsGrupo): ?>
                <div class="campo campo-ancho">
                    <label><?= htmlspecialchars($grupoNombre) ?></label>
                    <div class="grupo-checkbox-roles">
                        <?php foreach ($itemsGrupo as $itemClave => $itemLabel): ?>
                        <label class="campo-checkbox">
                            <input type="checkbox" name="menu[]" value="<?= htmlspecialchars($itemClave) ?>">
                            <?= htmlspecialchars($itemLabel) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="boton-enviar">Guardar plantilla</button>
            </form>
        </div>
    </div>

    <script type="application/json" id="datos-roles-por-tipo-jerarquia"><?= json_encode($rolesPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script type="application/json" id="datos-menu-por-tipo"><?= json_encode($menuPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
