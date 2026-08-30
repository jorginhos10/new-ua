<?php $tituloPagina = 'Perfil de proyectos'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <?php
        $barraTitulo = 'Perfil de proyectos';
        $barraBotonesSecundarios = [
            [
                'id' => 'boton-seleccionar-perfil-proyectos',
                'icono' => 'seleccionar',
                'etiqueta' => 'Seleccionar elementos',
            ],
            [
                'id' => 'boton-duplicar-perfil-proyectos',
                'icono' => 'duplicar',
                'etiqueta' => 'Duplicar seleccionados',
                'disabled' => true,
                'titulo_disabled' => 'Selecciona uno o más elementos',
            ],
            [
                'id' => 'boton-eliminar-perfil-proyectos',
                'icono' => 'eliminar',
                'etiqueta' => 'Eliminar seleccionados',
                'disabled' => true,
                'titulo_disabled' => 'Selecciona uno o más elementos',
            ],
            [
                'id' => 'boton-abrir-modal-enviar-todo-perfil-proyectos',
                'icono' => 'enviar',
                'etiqueta' => 'Enviar todos los proyectos en borrador',
                'disabled' => !$puedeEnviarTodo,
                'titulo_disabled' => 'No hay proyectos en borrador para enviar',
            ],
            [
                'id' => null,
                'icono' => 'exportar',
                'etiqueta' => 'Exportar lista',
                'tipo' => 'a',
                'href' => 'index.php?ruta=perfil-proyectos-exportar',
            ],
        ];
        $barraBotonPrincipal = ['id' => 'boton-abrir-modal-crear-proyecto', 'etiqueta' => 'Crear proyecto'];
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <p>Formularios de necesidades diligenciados por los usuarios invitados. Haz clic en una fila para ver el detalle.</p>

        <div
            class="tabla-scroll tabla-bulk-seleccionable"
            data-boton-seleccionar="boton-seleccionar-perfil-proyectos"
            data-boton-duplicar="boton-duplicar-perfil-proyectos"
            data-boton-eliminar="boton-eliminar-perfil-proyectos"
            data-accion-form="index.php?ruta=perfil-proyectos"
            data-accion-eliminar="eliminar_seleccionados"
            data-accion-duplicar="duplicar_seleccionados"
        >
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th class="columna-seleccion"><input type="checkbox" class="checkbox-bulk-todos"></th>
                        <th>Estado</th>
                        <th>Solicitante</th>
                        <th>Vigencia</th>
                        <th>Nombre de la necesidad</th>
                        <th>Estamento solicitante</th>
                        <th>Línea</th>
                        <th>Sublínea</th>
                        <th>Sede</th>
                        <th>Dependencia</th>
                        <th>Programa académico</th>
                        <th>Proyecto PDI</th>
                        <th>Valor</th>
                        <th>Fuente</th>
                        <th>Responsable</th>
                        <th>Registrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($necesidades as $necesidad): ?>
                    <tr class="fila-clickeable" data-necesidad="<?= htmlspecialchars(json_encode($necesidad, JSON_UNESCAPED_UNICODE)) ?>" tabindex="0">
                        <td class="columna-seleccion">
                            <?php if ($necesidad['estado'] === 'borrador'): ?>
                            <input type="checkbox" class="checkbox-bulk-fila" data-id="<?= (int) $necesidad['id'] ?>">
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($necesidad['estado']) ?>"><?= $necesidad['estado'] === 'enviado' ? 'Enviado' : 'Borrador' ?></span></td>
                        <td><?= htmlspecialchars($necesidad['nombre_solicitante']) ?></td>
                        <td><?= $necesidad['vigencia'] !== null ? (int) $necesidad['vigencia'] : '—' ?></td>
                        <td><?= htmlspecialchars($necesidad['nombre_necesidad'] ?? '') ?></td>
                        <td><?= htmlspecialchars($necesidad['estamento_solicitante_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($necesidad['linea_inversion_nombre'] ?? $necesidad['linea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sublinea_inversion_nombre'] ?? $necesidad['sublinea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sede_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($necesidad['dependencia']) ?></td>
                        <td><?= htmlspecialchars($necesidad['programa_academico'] ?? '') ?></td>
                        <td><?= htmlspecialchars($necesidad['proyecto_nombre'] ?? '') ?></td>
                        <td><?= number_format((float) $necesidad['valor'], 2) ?></td>
                        <td><?= htmlspecialchars($necesidad['fuente_financiacion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['responsable_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($necesidad['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($necesidades)): ?>
                    <tr>
                        <td colspan="16">Aún no hay formularios registrados por invitados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal-crear-proyecto" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Crear proyecto</h2>
                <button type="button" id="boton-cerrar-modal-crear-proyecto" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=perfil-proyectos" class="form-necesidad">
                <input type="hidden" name="accion" value="crear_proyecto">

                <div class="campo">
                    <label for="crear-proyecto-vigencia">Vigencia *</label>
                    <select id="crear-proyecto-vigencia" name="vigencia" required>
                        <option value="">Selecciona la vigencia</option>
                        <?php foreach ($aniosVigencia as $anioVigencia): ?>
                        <option value="<?= (int) $anioVigencia ?>"><?= (int) $anioVigencia ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label for="crear-proyecto-nombre_necesidad">Nombre de la necesidad *</label>
                    <input type="text" id="crear-proyecto-nombre_necesidad" name="nombre_necesidad" placeholder="Diligenciar" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="crear-proyecto-descripcion">Descripción</label>
                    <textarea id="crear-proyecto-descripcion" name="descripcion" rows="3" placeholder="Diligenciar"></textarea>
                </div>

                <div class="campo campo-ancho">
                    <label for="crear-proyecto-justificacion">Justificación</label>
                    <textarea id="crear-proyecto-justificacion" name="justificacion" rows="3" placeholder="Diligenciar"></textarea>
                </div>

                <div class="campo">
                    <label for="crear-proyecto-estamento_solicitante_id">Estamento solicitante *</label>
                    <select id="crear-proyecto-estamento_solicitante_id" name="estamento_solicitante_id" required>
                        <option value="">Selecciona un estamento</option>
                        <?php foreach ($estamentos as $estamentoOpcion): ?>
                        <option value="<?= (int) $estamentoOpcion['id'] ?>"><?= htmlspecialchars($estamentoOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="crear-proyecto-beneficiarios_cantidad">Beneficiarios (cantidad)</label>
                    <input type="number" id="crear-proyecto-beneficiarios_cantidad" name="beneficiarios_cantidad" min="0" step="1" placeholder="0">
                </div>

                <div class="campo campo-ancho">
                    <label>Beneficiarios por estamento</label>
                    <div class="grupo-tags">
                        <?php foreach ($estamentos as $estamentoTag): ?>
                        <input
                            type="checkbox"
                            class="tag-chip"
                            name="beneficiarios_estamentos[]"
                            value="<?= (int) $estamentoTag['id'] ?>"
                            data-label="<?= htmlspecialchars($estamentoTag['nombre']) ?>"
                            title="<?= htmlspecialchars($estamentoTag['nombre']) ?>"
                        >
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php
                $idPrefijoLineaInversion = 'crear-proyecto-';
                $idCampoSublineaInversion = 'crear-proyecto-sublinea_inversion';
                require __DIR__ . '/../parciales/selector-linea-inversion.php';
                ?>

                <?php
                $idPrefijoSublineaInversion = 'crear-proyecto-';
                require __DIR__ . '/../parciales/selector-sublinea-inversion.php';
                ?>

                <div class="campo">
                    <label for="crear-proyecto-detalle_inversion">Inversión (detalle)</label>
                    <input type="text" id="crear-proyecto-detalle_inversion" name="detalle_inversion" placeholder="Elegir de acuerdo al alcance en la pestaña definiciones">
                </div>

                <div class="campo">
                    <label for="crear-proyecto-sede_id">Sede *</label>
                    <select id="crear-proyecto-sede_id" name="sede_id" required>
                        <option value="">Selecciona una sede</option>
                        <?php foreach ($sedes as $sedeOpcion): ?>
                        <option value="<?= (int) $sedeOpcion['id'] ?>"><?= htmlspecialchars($sedeOpcion['codigo'] . ' - ' . $sedeOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="crear-proyecto-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = 'crear-proyecto-';
                    $nombreCampoDependencia = 'dependencia';
                    $idBaseDependenciaOverride = 'crear-proyecto-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="crear-proyecto-programa-academico-dependencia_buscador">Programa académico</label>
                    <?php
                    $idPrefijoDependencia = 'crear-proyecto-programa-academico-';
                    $nombreCampoDependencia = 'programa_academico';
                    $idBaseDependenciaOverride = 'crear-proyecto-programa-academico-dependencia';
                    $dependenciasOpciones = $dependenciasPrograma;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <?php $idPrefijoProyecto = 'crear-proyecto-'; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

                <div class="campo campo-ancho">
                    <label for="crear-proyecto-articulacion_plan">Articulación con Plan/planes</label>
                    <input type="text" id="crear-proyecto-articulacion_plan" name="articulacion_plan" placeholder="Diligenciar">
                </div>

                <div class="campo campo-ancho">
                    <label for="crear-proyecto-espacio_intervenir">Espacio a intervenir</label>
                    <input type="text" id="crear-proyecto-espacio_intervenir" name="espacio_intervenir" placeholder="Salón, laboratorio, taller u oficina de acuerdo a nomenclatura de la sede">
                </div>

                <div class="campo campo-ancho">
                    <label for="crear-proyecto-requisitos_normativos">Requisitos normativos</label>
                    <input type="text" id="crear-proyecto-requisitos_normativos" name="requisitos_normativos" placeholder="Diligenciar">
                </div>

                <div class="campo">
                    <label for="crear-proyecto-valor">Valor *</label>
                    <input type="number" id="crear-proyecto-valor" name="valor" min="0" step="0.01" placeholder="0.00" required>
                </div>

                <div class="campo">
                    <label for="crear-proyecto-fuente_financiacion">Fuente de financiación *</label>
                    <input type="text" id="crear-proyecto-fuente_financiacion" name="fuente_financiacion" placeholder="Diligenciar" required>
                </div>

                <div class="campo">
                    <label for="crear-proyecto-responsable_usuario_id">Responsable *</label>
                    <select id="crear-proyecto-responsable_usuario_id" name="responsable_usuario_id" required>
                        <option value="">Selecciona un avalador</option>
                        <?php foreach ($avaladores as $avaladorOpcion): ?>
                        <option value="<?= (int) $avaladorOpcion['id'] ?>"><?= htmlspecialchars($avaladorOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label for="crear-proyecto-observaciones">Observaciones</label>
                    <textarea id="crear-proyecto-observaciones" name="observaciones" rows="3" placeholder="Diligenciar"></textarea>
                </div>

                <button type="submit" class="boton-enviar">Registrar proyecto</button>
            </form>
        </div>
    </div>

    <div id="modal-enviar-todo-perfil-proyectos" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Enviar todos los proyectos</h2>
                <button type="button" id="boton-cerrar-modal-enviar-todo-perfil-proyectos" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Se enviarán todos los proyectos en borrador, como una solicitud. Elige a quién se enviará: puede tener que pasar por varios avaladores intermedios antes de llegar al destino final.</p>

            <form method="POST" action="index.php?ruta=perfil-proyectos" class="form-necesidad">
                <input type="hidden" name="accion" value="enviar_todo">

                <div class="campo">
                    <label for="enviar-todo-perfil-proyectos-destino_buscador">Enviar a la dependencia *</label>
                    <?php
                    $idPrefijoDependencia = 'enviar-todo-perfil-proyectos-destino-';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'enviar-todo-perfil-proyectos-destino';
                    $dependenciasOpciones = $dependenciasTodas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-todo-perfil-proyectos-rol">Rol al que se enviará *</label>
                    <select id="enviar-todo-perfil-proyectos-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo" style="display:none;">
                    <label for="enviar-todo-perfil-proyectos-destinatario">¿A quién exactamente? *</label>
                    <select
                        id="enviar-todo-perfil-proyectos-destinatario"
                        name="usuario_destinatario_id"
                        class="selector-destinatario"
                        data-campo-dependencia="enviar-todo-perfil-proyectos-destino"
                        data-campo-rol="enviar-todo-perfil-proyectos-rol"
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                </div>

                <button type="submit" class="boton-enviar">Enviar todo</button>
            </form>
        </div>
    </div>

    <div id="modal-necesidad" class="modal-fondo">
        <div class="modal-caja modal-caja-documento">
            <div class="modal-cabecera">
                <h2>Ficha de necesidad académica</h2>
                <div class="modal-cabecera-acciones">
                    <button type="button" id="boton-imprimir-necesidad" class="boton-imprimir">Imprimir</button>
                    <button type="button" id="boton-cerrar-modal-necesidad" class="modal-cerrar" aria-label="Cerrar">&times;</button>
                </div>
            </div>

            <div class="documento-necesidad" id="documento-necesidad">
                <div class="documento-encabezado">
                    <h3>Formulario de necesidad académica</h3>
                    <p class="documento-fecha">Registrado: <strong id="doc-fecha"></strong></p>
                </div>

                <div class="documento-seccion">
                    <h4>Identificación de la necesidad</h4>
                    <div class="documento-grid">
                        <div class="documento-campo"><span>Vigencia</span><strong id="doc-vigencia"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Nombre de la necesidad</span><strong id="doc-nombre-necesidad"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Descripción</span><strong id="doc-descripcion"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Justificación</span><strong id="doc-justificacion"></strong></div>
                        <div class="documento-campo"><span>Estamento solicitante</span><strong id="doc-estamento"></strong></div>
                        <div class="documento-campo"><span>Beneficiarios</span><strong id="doc-beneficiarios"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Beneficiarios por estamento</span><strong id="doc-beneficiarios-estamentos"></strong></div>
                    </div>
                </div>

                <div class="documento-seccion">
                    <h4>Datos del solicitante</h4>
                    <div class="documento-grid">
                        <div class="documento-campo"><span>Solicitante</span><strong id="doc-solicitante"></strong></div>
                        <div class="documento-campo"><span>Responsable</span><strong id="doc-responsable"></strong></div>
                        <div class="documento-campo"><span>Sede</span><strong id="doc-sede"></strong></div>
                        <div class="documento-campo"><span>Dependencia</span><strong id="doc-dependencia"></strong></div>
                        <div class="documento-campo"><span>Programa académico</span><strong id="doc-programa"></strong></div>
                    </div>
                </div>

                <div class="documento-seccion">
                    <h4>Inversión</h4>
                    <div class="documento-grid">
                        <div class="documento-campo"><span>Línea de inversión</span><strong id="doc-linea"></strong></div>
                        <div class="documento-campo"><span>Sublínea de inversión</span><strong id="doc-sublinea"></strong></div>
                        <div class="documento-campo"><span>Proyecto PDI</span><strong id="doc-pdi"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Inversión (detalle)</span><strong id="doc-detalle"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Articulación con Plan/planes</span><strong id="doc-articulacion"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Espacio a intervenir</span><strong id="doc-espacio"></strong></div>
                        <div class="documento-campo documento-campo-ancho"><span>Requisitos normativos</span><strong id="doc-requisitos"></strong></div>
                    </div>
                </div>

                <div class="documento-seccion">
                    <h4>Financiación</h4>
                    <div class="documento-grid">
                        <div class="documento-campo"><span>Valor</span><strong id="doc-valor"></strong></div>
                        <div class="documento-campo"><span>Fuente de financiación</span><strong id="doc-fuente"></strong></div>
                    </div>
                </div>

                <div class="documento-seccion">
                    <h4>Observaciones</h4>
                    <p id="doc-observaciones" class="documento-observaciones"></p>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
