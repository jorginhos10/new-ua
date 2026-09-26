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

        <?php if ($esSuperAdminRaiz && !empty($tiposAutomaticoPorModulo)): ?>
        <div class="campo-ancho">
            <label>Permisos delegados sobre filas automáticas
                <span class="icono-info" tabindex="0" title="Además del superadmin, estos usuarios pueden editar/borrar filas automáticas (Excedentes/Contribución a posgrado) de este módulo, viendo las de todas las dependencias — vacío por defecto, solo el superadmin puede agregar.">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </span>
            </label>
            <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" class="form-necesidad">
                <input type="hidden" name="accion" value="otorgar_permiso_automatico">
                <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
                <div class="campo">
                    <select name="tipo" required>
                        <?php foreach ($tiposAutomaticoPorModulo as $tipoClave => $tipoLabel): ?>
                        <option value="<?= htmlspecialchars($tipoClave) ?>"><?= htmlspecialchars($tipoLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo campo-ancho">
                    <select name="usuario_id" required>
                        <option value="">Selecciona un usuario</option>
                        <?php foreach ($usuariosConRol as $usuarioOpcion): ?>
                        <option value="<?= (int) $usuarioOpcion['id'] ?>"><?= htmlspecialchars($usuarioOpcion['rol_nombre'] . ' · ' . $usuarioOpcion['nombre'] . ' (' . $usuarioOpcion['correo'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="boton-enviar">Otorgar permiso</button>
            </form>

            <?php if (!empty($permisosAutomatico)): ?>
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Usuario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permisosAutomatico as $permiso): ?>
                    <tr>
                        <td><?= htmlspecialchars($tiposAutomaticoPorModulo[$permiso['tipo']] ?? $permiso['tipo']) ?></td>
                        <td><?= htmlspecialchars($permiso['usuario_rol'] . ' · ' . $permiso['usuario_nombre'] . ' (' . $permiso['usuario_correo'] . ')') ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" class="form-toggle">
                                <input type="hidden" name="accion" value="revocar_permiso_automatico">
                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
                                <input type="hidden" name="id" value="<?= (int) $permiso['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-eliminar">Quitar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="texto-atenuado">Todavía no le has otorgado este permiso a nadie.</p>
            <?php endif; ?>
        </div>
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

            <?php if ($tieneDefinicionAutomatico): ?>
            <button
                type="button"
                class="boton-accion boton-definir-automatico"
                data-autogestion-id="0"
                data-nombre="<?= htmlspecialchars($modulos[$moduloActivo]) ?>"
                data-definiciones="<?= htmlspecialchars(json_encode($definicionesModulo)) ?>"
            >Definir parámetros de fila automática</button>
            <?php endif; ?>
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
                            <?php if ($tieneDefinicionAutomatico): ?>
                            <button
                                type="button"
                                class="boton-accion boton-definir-automatico"
                                data-autogestion-id="<?= (int) $item['id'] ?>"
                                data-nombre="<?= htmlspecialchars($item['nombre']) ?>"
                                data-definiciones="<?= htmlspecialchars(json_encode($item['definiciones'])) ?>"
                            >Definir</button>
                            <?php endif; ?>
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

        <?php if ($tieneDefinicionAutomatico): ?>
        <div id="modal-definir-automatico" class="modal-fondo">
            <div class="modal-caja">
                <div class="modal-cabecera">
                    <h2>Definir parámetros — <span id="definir-automatico-titulo"></span></h2>
                    <button type="button" id="boton-cerrar-modal-definir-automatico" class="modal-cerrar" aria-label="Cerrar">&times;</button>
                </div>

                <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" id="form-definir-automatico" class="form-necesidad">
                    <input type="hidden" name="accion" value="guardar_definicion_automatico">
                    <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
                    <input type="hidden" name="autogestion_id" id="definir-automatico-autogestion-id" value="">
                    <input type="hidden" name="id" id="definir-automatico-id" value="">

                    <?php if (count($tiposAutomaticoPorModulo) > 1): ?>
                    <div class="campo">
                        <label for="definir-automatico-tipo">Tipo *</label>
                        <select id="definir-automatico-tipo" name="tipo" required>
                            <?php foreach ($tiposAutomaticoPorModulo as $tipoClave => $tipoLabel): ?>
                            <option value="<?= htmlspecialchars($tipoClave) ?>"><?= htmlspecialchars($tipoLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars((string) array_key_first($tiposAutomaticoPorModulo)) ?>">
                    <?php endif; ?>

                    <?php if ($esSuperAdminRaiz): ?>
                    <div class="campo campo-ancho">
                        <label for="definir-automatico-dependencia_buscador">Dependencia (vacío = default para todas)</label>
                        <div class="selector-buscable" id="definir-automatico-selector-dependencia">
                            <input type="text" id="definir-automatico-dependencia_buscador" class="selector-buscable-input" placeholder="Todas (default) — o busca una dependencia..." autocomplete="off">
                            <input type="hidden" name="dependencia" id="definir-automatico-dependencia">
                            <div class="selector-buscable-lista" id="definir-automatico-dependencia_lista">
                                <?php foreach ($dependenciasTodas as $dependenciaOpcion): ?>
                                <div class="selector-buscable-opcion" data-id="<?= htmlspecialchars($dependenciaOpcion['nombre']) ?>" data-texto="<?= htmlspecialchars($dependenciaOpcion['nombre']) ?>">
                                    <?= htmlspecialchars($dependenciaOpcion['nombre']) ?>
                                </div>
                                <?php endforeach; ?>
                                <div class="selector-buscable-vacio">Sin resultados.</div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="campo campo-ancho">
                        <label>Dependencia</label>
                        <input type="text" value="Tu propia dependencia (fija, solo el superadmin puede cambiarla)" disabled>
                    </div>
                    <?php endif; ?>

                    <div class="campo">
                        <label for="definir-automatico-sede_id">Sede *</label>
                        <select id="definir-automatico-sede_id" name="sede_id" required>
                            <?php foreach ($sedes as $sedeOpcion): ?>
                            <option value="<?= (int) $sedeOpcion['id'] ?>"><?= htmlspecialchars($sedeOpcion['codigo'] . ' - ' . $sedeOpcion['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php
                    $idPrefijoProyecto = 'definir-automatico-';
                    $proyectosPdi = $proyectos;
                    require __DIR__ . '/../parciales/selector-proyecto-pdi.php';
                    ?>

                    <div class="campo campo-ancho">
                        <label for="definir-automatico-rubro_buscador">Rubro *</label>
                        <div class="selector-buscable" id="definir-automatico-selector-rubro">
                            <input type="text" id="definir-automatico-rubro_buscador" class="selector-buscable-input" placeholder="Buscar rubro..." autocomplete="off">
                            <input type="hidden" name="rubro_id" id="definir-automatico-rubro_id">
                            <div class="selector-buscable-lista" id="definir-automatico-rubro_lista">
                                <?php foreach ($rubros as $rubroOpcion): ?>
                                <div class="selector-buscable-opcion" data-id="<?= (int) $rubroOpcion['id'] ?>" data-texto="<?= htmlspecialchars($rubroOpcion['codigo'] . ' - ' . $rubroOpcion['descripcion']) ?>">
                                    <?= htmlspecialchars($rubroOpcion['codigo'] . ' - ' . $rubroOpcion['descripcion']) ?>
                                </div>
                                <?php endforeach; ?>
                                <div class="selector-buscable-vacio">Sin resultados.</div>
                            </div>
                        </div>
                    </div>

                    <div class="campo campo-ancho">
                        <label for="definir-automatico-actividad">Actividad *</label>
                        <input type="text" id="definir-automatico-actividad" name="actividad" required>
                    </div>

                    <div class="campo campo-ancho">
                        <label for="definir-automatico-insumo">Insumo *</label>
                        <input type="text" id="definir-automatico-insumo" name="insumo" required>
                    </div>

                    <div class="campo campo-ancho">
                        <label>Meses de ejecución *</label>
                        <div class="grupo-checkboxes-meses" id="definir-automatico-meses">
                            <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                            <label class="campo-checkbox">
                                <input type="checkbox" name="meses[]" value="<?= $mes ?>"> <?= $mes ?>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <button type="submit" class="boton-enviar">Guardar definición</button>
                </form>

                <h3>Definiciones ya creadas</h3>
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Dependencia</th>
                            <th>Sede</th>
                            <th>Rubro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="definir-automatico-lista-cuerpo">
                    </tbody>
                </table>
                <p class="texto-atenuado" id="definir-automatico-lista-vacia">Todavía no hay definiciones para este ítem.</p>
            </div>
        </div>

        <form method="POST" action="index.php?ruta=autogestion&tab=<?= htmlspecialchars($moduloActivo) ?>" id="form-eliminar-definicion-automatico" style="display:none;">
            <input type="hidden" name="accion" value="eliminar_definicion_automatico">
            <input type="hidden" name="modulo" value="<?= htmlspecialchars($moduloActivo) ?>">
            <input type="hidden" name="id" id="eliminar-definicion-automatico-id" value="">
        </form>

        <script type="application/json" id="datos-tipos-automatico"><?= json_encode($tiposAutomaticoPorModulo) ?></script>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
