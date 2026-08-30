<?php $tituloPagina = 'Recolección de necesidades'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Recolección de necesidades de la comunidad académica</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (!$dentroDeVentana): ?>
        <p class="mensaje-error">
            No estás dentro de la fecha habilitada para formular necesidades.
            <?php if ($configuracionFormulador !== null): ?>
            El plazo es del <?= htmlspecialchars($configuracionFormulador['fecha_inicio']) ?> al <?= htmlspecialchars($configuracionFormulador['fecha_cierre']) ?>.
            <?php endif; ?>
        </p>
        <?php else: ?>
        <form method="POST" action="index.php?ruta=formulario-invitado" class="form-necesidad">
            <div class="campo">
                <label for="vigencia">Vigencia *</label>
                <select id="vigencia" name="vigencia" required>
                    <option value="">Selecciona la vigencia</option>
                    <?php foreach ($aniosVigencia as $anioVigencia): ?>
                    <option value="<?= (int) $anioVigencia ?>"><?= (int) $anioVigencia ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo campo-ancho">
                <label for="nombre_necesidad">Nombre de la necesidad *</label>
                <input type="text" id="nombre_necesidad" name="nombre_necesidad" placeholder="Diligenciar" required>
            </div>

            <div class="campo campo-ancho">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" placeholder="Diligenciar"></textarea>
            </div>

            <div class="campo campo-ancho">
                <label for="justificacion">Justificación</label>
                <textarea id="justificacion" name="justificacion" rows="3" placeholder="Diligenciar"></textarea>
            </div>

            <div class="campo">
                <label for="estamento_solicitante_id">Estamento solicitante *</label>
                <select id="estamento_solicitante_id" name="estamento_solicitante_id" required>
                    <option value="">Selecciona un estamento</option>
                    <?php foreach ($estamentos as $estamentoOpcion): ?>
                    <option value="<?= (int) $estamentoOpcion['id'] ?>"><?= htmlspecialchars($estamentoOpcion['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="beneficiarios_cantidad">Beneficiarios (cantidad)</label>
                <input type="number" id="beneficiarios_cantidad" name="beneficiarios_cantidad" min="0" step="1" placeholder="0">
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
            $idPrefijoLineaInversion = '';
            $idCampoSublineaInversion = 'sublinea_inversion';
            require __DIR__ . '/../parciales/selector-linea-inversion.php';
            ?>

            <?php
            $idPrefijoSublineaInversion = '';
            require __DIR__ . '/../parciales/selector-sublinea-inversion.php';
            ?>

            <div class="campo">
                <label for="detalle_inversion">Inversión (detalle)</label>
                <input type="text" id="detalle_inversion" name="detalle_inversion" placeholder="Elegir de acuerdo al alcance en la pestaña definiciones">
            </div>

            <div class="campo">
                <label for="sede_id">Sede *</label>
                <select id="sede_id" name="sede_id" required>
                    <option value="">Selecciona una sede</option>
                    <?php foreach ($sedes as $sedeOpcion): ?>
                    <option value="<?= (int) $sedeOpcion['id'] ?>"><?= htmlspecialchars($sedeOpcion['codigo'] . ' - ' . $sedeOpcion['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="dependencia_buscador">Dependencia *</label>
                <?php
                $idPrefijoDependencia = '';
                $nombreCampoDependencia = 'dependencia';
                $dependenciasOpciones = $dependenciasSugeridas;
                require __DIR__ . '/../parciales/selector-dependencia.php';
                ?>
            </div>

            <div class="campo">
                <label for="programa-academico-dependencia_buscador">Programa académico</label>
                <?php
                $idPrefijoDependencia = 'programa-academico-';
                $nombreCampoDependencia = 'programa_academico';
                $idBaseDependenciaOverride = 'programa-academico-dependencia';
                $dependenciasOpciones = $dependenciasPrograma;
                require __DIR__ . '/../parciales/selector-dependencia.php';
                ?>
            </div>

            <?php $idPrefijoProyecto = ''; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

            <div class="campo campo-ancho">
                <label for="articulacion_plan">Articulación con Plan/planes</label>
                <input type="text" id="articulacion_plan" name="articulacion_plan" placeholder="Diligenciar">
            </div>

            <div class="campo campo-ancho">
                <label for="espacio_intervenir">Espacio a intervenir</label>
                <input type="text" id="espacio_intervenir" name="espacio_intervenir" placeholder="Salón, laboratorio, taller u oficina de acuerdo a nomenclatura de la sede">
            </div>

            <div class="campo campo-ancho">
                <label for="requisitos_normativos">Requisitos normativos</label>
                <input type="text" id="requisitos_normativos" name="requisitos_normativos" placeholder="Diligenciar">
            </div>

            <div class="campo">
                <label for="valor">Valor *</label>
                <input type="number" id="valor" name="valor" min="0" step="0.01" placeholder="0.00" required>
            </div>

            <div class="campo">
                <label for="fuente_financiacion">Fuente de financiación *</label>
                <input type="text" id="fuente_financiacion" name="fuente_financiacion" placeholder="Diligenciar" required>
            </div>

            <div class="campo">
                <label for="responsable_usuario_id">Responsable *</label>
                <select id="responsable_usuario_id" name="responsable_usuario_id" required>
                    <option value="">Selecciona un avalador</option>
                    <?php foreach ($avaladores as $avaladorOpcion): ?>
                    <option value="<?= (int) $avaladorOpcion['id'] ?>"><?= htmlspecialchars($avaladorOpcion['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo campo-ancho">
                <label for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" rows="3" placeholder="Diligenciar"></textarea>
            </div>

            <button type="submit" class="boton-enviar">Registrar necesidad</button>
        </form>
        <?php endif; ?>

        <h2>Mis necesidades registradas</h2>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Vigencia</th>
                        <th>Nombre</th>
                        <th>Línea</th>
                        <th>Sublínea</th>
                        <th>Sede</th>
                        <th>Dependencia</th>
                        <th>Valor</th>
                        <th>Fuente</th>
                        <th>Responsable</th>
                        <th>Registrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($misNecesidades as $necesidad): ?>
                    <tr>
                        <td><?= $necesidad['vigencia'] !== null ? (int) $necesidad['vigencia'] : '—' ?></td>
                        <td><?= htmlspecialchars($necesidad['nombre_necesidad'] ?? '') ?></td>
                        <td><?= htmlspecialchars($necesidad['linea_inversion_nombre'] ?? $necesidad['linea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sublinea_inversion_nombre'] ?? $necesidad['sublinea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sede_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($necesidad['dependencia']) ?></td>
                        <td><?= number_format((float) $necesidad['valor'], 2) ?></td>
                        <td><?= htmlspecialchars($necesidad['fuente_financiacion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['responsable_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($necesidad['creado_en']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($misNecesidades)): ?>
                    <tr>
                        <td colspan="10">Aún no has registrado necesidades.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
