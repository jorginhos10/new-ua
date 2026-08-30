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
                <label for="linea_inversion">Línea de inversión *</label>
                <input type="text" id="linea_inversion" name="linea_inversion" placeholder="Elegir de acuerdo al alcance en la pestaña definiciones" required>
            </div>

            <div class="campo">
                <label for="sublinea_inversion">Sublínea de inversión *</label>
                <input type="text" id="sublinea_inversion" name="sublinea_inversion" placeholder="Elegir de acuerdo al alcance en la pestaña definiciones" required>
            </div>

            <div class="campo">
                <label for="detalle_inversion">Inversión (detalle)</label>
                <input type="text" id="detalle_inversion" name="detalle_inversion" placeholder="Elegir de acuerdo al alcance en la pestaña definiciones">
            </div>

            <div class="campo">
                <label for="sede">Sede *</label>
                <input type="text" id="sede" name="sede" placeholder="Diligenciar" required>
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
                <label for="programa_academico">Programa académico</label>
                <input type="text" id="programa_academico" name="programa_academico" placeholder="Diligenciar">
            </div>

            <div class="campo">
                <label for="proyecto_pdi">Proyecto PDI</label>
                <input type="text" id="proyecto_pdi" name="proyecto_pdi" placeholder="En formato de LxMxPx">
            </div>

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
                <label for="responsable">Responsable *</label>
                <input type="text" id="responsable" name="responsable" placeholder="Diligenciar" required>
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
                        <th>Línea</th>
                        <th>Sublínea</th>
                        <th>Sede</th>
                        <th>Dependencia</th>
                        <th>Proyecto PDI</th>
                        <th>Espacio</th>
                        <th>Valor</th>
                        <th>Fuente</th>
                        <th>Responsable</th>
                        <th>Registrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($misNecesidades as $necesidad): ?>
                    <tr>
                        <td><?= htmlspecialchars($necesidad['linea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sublinea_inversion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['sede']) ?></td>
                        <td><?= htmlspecialchars($necesidad['dependencia']) ?></td>
                        <td><?= htmlspecialchars($necesidad['proyecto_pdi'] ?? '') ?></td>
                        <td><?= htmlspecialchars($necesidad['espacio_intervenir'] ?? '') ?></td>
                        <td><?= number_format((float) $necesidad['valor'], 2) ?></td>
                        <td><?= htmlspecialchars($necesidad['fuente_financiacion']) ?></td>
                        <td><?= htmlspecialchars($necesidad['responsable']) ?></td>
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
