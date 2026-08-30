<?php $tituloPagina = 'Perfil de proyectos'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>Perfil de proyectos</h1>
            <div class="grupo-acciones-encabezado">
                <button
                    type="button"
                    id="boton-abrir-modal-enviar-todo-perfil-proyectos"
                    class="boton-accion boton-accion-enviar"
                    <?= $puedeEnviarTodo ? '' : 'disabled' ?>
                    title="<?= $puedeEnviarTodo ? 'Enviar todos los proyectos en borrador' : 'No hay proyectos en borrador para enviar' ?>"
                >Enviar todo</button>
                <a href="index.php?ruta=perfil-proyectos-exportar" class="boton-agregar">Exportar lista</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <p>Formularios de necesidades diligenciados por los usuarios invitados. Haz clic en una fila para ver el detalle.</p>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Solicitante</th>
                        <th>Línea</th>
                        <th>Sublínea</th>
                        <th>Sede</th>
                        <th>Dependencia</th>
                        <th>Programa académico</th>
                        <th>Proyecto PDI</th>
                        <th>Espacio</th>
                        <th>Valor</th>
                        <th>Fuente</th>
                        <th>Responsable</th>
                        <th>Observaciones</th>
                        <th>Registrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($necesidades as $necesidad): ?>
                    <tr class="fila-clickeable" data-necesidad="<?= htmlspecialchars(json_encode($necesidad, JSON_UNESCAPED_UNICODE)) ?>" tabindex="0">
                        <td><span class="badge-rol badge-<?= htmlspecialchars($necesidad['estado']) ?>"><?= $necesidad['estado'] === 'enviado' ? 'Enviado' : 'Borrador' ?></span></td>
                        <td><?= htmlspecialchars($necesidad['nombre_solicitante']) ?></td>
                        <td><?= htmlspecialchars($necesidad['linea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sublinea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sede']) ?></td>
                        <td><?= htmlspecialchars($necesidad['dependencia']) ?></td>
                        <td><?= htmlspecialchars($necesidad['programa_academico'] ?? '') ?></td>
                        <td><?= htmlspecialchars($necesidad['proyecto_pdi'] ?? '') ?></td>
                        <td><?= htmlspecialchars($necesidad['espacio_intervenir'] ?? '') ?></td>
                        <td><?= number_format((float) $necesidad['valor'], 2) ?></td>
                        <td><?= htmlspecialchars($necesidad['fuente_financiacion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['responsable']) ?></td>
                        <td><?= htmlspecialchars($necesidad['observaciones'] ?? '') ?></td>
                        <td><?= htmlspecialchars($necesidad['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($necesidades)): ?>
                    <tr>
                        <td colspan="14">Aún no hay formularios registrados por invitados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

<?php require __DIR__ . '/../parciales/pie.php'; ?>
