<?php $tituloPagina = 'Configurar presupuestos'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Configurar presupuestos por dependencia<?= $anio !== null ? ' — ' . (int) $anio['anio'] : '' ?></h1>
                <p class="texto-atenuado">Define el mínimo y el techo presupuestal de cada dependencia para este año.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if ($anio === null): ?>
        <p class="mensaje-error">El año presupuestal seleccionado no existe. <a href="index.php?ruta=anios-presupuestales">Volver a Año presupuestal</a>.</p>
        <?php else: ?>

        <form method="GET" action="index.php" class="form-filtro-anio">
            <input type="hidden" name="ruta" value="configurar-presupuestos">
            <label for="anio_id_filtro">Año presupuestal</label>
            <select id="anio_id_filtro" name="anio_id" onchange="this.form.submit()">
                <?php foreach ($todosAnios as $anioOpcion): ?>
                <option value="<?= (int) $anioOpcion['id'] ?>" <?= $anioId === (int) $anioOpcion['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $anioOpcion['anio']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <a href="index.php?ruta=anios-presupuestales" class="boton-enlace">Volver a Año presupuestal</a>
        </form>

        <form method="POST" action="index.php?ruta=configurar-presupuestos&anio_id=<?= (int) $anioId ?>">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="anio_id" value="<?= (int) $anioId ?>">

            <div class="buscador-presupuestos">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="buscador-presupuestos-input" placeholder="Buscar dependencia..." autocomplete="off">
            </div>

            <div class="acordeon-presupuestos" id="acordeon-presupuestos">
                <?php foreach ($gruposDependencia as $grupo): ?>
                <?php
                $totalGrupo = count($grupo['items']);
                $configuradosGrupo = 0;
                foreach ($grupo['items'] as $dependenciaConteo) {
                    $valorConteo = $presupuestosActuales[$dependenciaConteo['id']] ?? null;
                    if ($valorConteo !== null && ($valorConteo['minimo'] !== null || $valorConteo['techo'] !== null)) {
                        $configuradosGrupo++;
                    }
                }
                ?>
                <details class="acordeon-grupo">
                    <summary class="acordeon-cabecera">
                        <span class="acordeon-flecha">▸</span>
                        <span class="acordeon-icono-grupo">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><line x1="9" y1="9" x2="9" y2="9.01"></line><line x1="9" y1="12" x2="9" y2="12.01"></line><line x1="9" y1="15" x2="9" y2="15.01"></line></svg>
                        </span>
                        <span class="acordeon-titulo"><?= htmlspecialchars($grupo['titulo']) ?></span>
                        <span class="acordeon-contador" data-total="<?= $totalGrupo ?>"><?= $configuradosGrupo > 0 ? $configuradosGrupo . '/' . $totalGrupo : $totalGrupo ?></span>
                    </summary>
                    <div class="acordeon-cuerpo">
                        <div class="fila-presupuesto-encabezado">
                            <span></span>
                            <span>Mínimo presupuestal</span>
                            <span>Techo presupuestal</span>
                        </div>
                        <?php foreach ($grupo['items'] as $dependencia): ?>
                        <?php $valores = $presupuestosActuales[$dependencia['id']] ?? ['minimo' => null, 'techo' => null]; ?>
                        <div class="fila-presupuesto-dependencia" data-nombre-buscable="<?= htmlspecialchars(mb_strtolower($dependencia['nombre'])) ?>">
                            <span class="fila-presupuesto-nombre">
                                <?= htmlspecialchars($dependencia['nombre']) ?>
                                <?php if (!empty($dependencia['es_cabeza_familia'])): ?>
                                <span class="etiqueta-cabecera-familia">Cabecera</span>
                                <?php endif; ?>
                            </span>
                            <div class="campo-moneda">
                                <span>$</span>
                                <input type="number" class="campo-minimo-presupuestal" name="minimo[<?= (int) $dependencia['id'] ?>]" min="0" step="0.01" placeholder="0.00" value="<?= $valores['minimo'] !== null ? htmlspecialchars((string) $valores['minimo']) : '' ?>">
                            </div>
                            <div class="campo-moneda">
                                <span>$</span>
                                <input type="number" class="campo-techo-presupuestal" name="techo[<?= (int) $dependencia['id'] ?>]" min="0" step="0.01" placeholder="0.00" value="<?= $valores['techo'] !== null ? htmlspecialchars((string) $valores['techo']) : '' ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endforeach; ?>
                <p class="buscador-presupuestos-vacio" id="buscador-presupuestos-vacio">No hay dependencias que coincidan con la búsqueda.</p>
            </div>

            <div class="acciones-formulario-presupuestos">
                <button type="submit" class="boton-enviar">Guardar</button>
                <button type="submit" name="accion" value="notificar" class="boton-enviar" title="Guarda y envía un mensaje al avalador de cada dependencia cuyo techo se agregó o modificó">Guardar y notificar</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
