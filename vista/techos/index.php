<?php $tituloPagina = 'Techos'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Techos</h1>
                <p class="texto-atenuado">Configura el mínimo y el techo presupuestal de las dependencias que reportan a la tuya.</p>
            </div>
            <div class="grupo-acciones-encabezado">
                <button type="button" class="boton-accion boton-accion-enviar">Exportar Excel</button>
                <a href="index.php?ruta=resumen-techos&anio_id=<?= (int) $anioSeleccionadoId ?>" class="boton-accion boton-accion-ver">Resumen de techos</a>
                <a href="index.php?ruta=control-versiones&anio_id=<?= (int) $anioSeleccionadoId ?>" class="boton-accion boton-accion-ver">Control de versiones</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if ($dependenciaUsuarioId === null): ?>
        <p class="texto-atenuado">No tienes una dependencia asignada. Pide a un administrador que te asocie una dependencia en Usuarios &rarr; Permisos.</p>
        <?php elseif (empty($aniosActivos)): ?>
        <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php elseif (empty($arbolHijas)): ?>
        <p class="texto-atenuado">No tienes dependencias hijas asociadas según el flujo configurado en Dependencias.</p>
        <?php else: ?>

        <?php if (count($aniosActivos) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="techos">
                <label for="anio_id_filtro">Año presupuestal</label>
                <select id="anio_id_filtro" name="anio_id" onchange="this.form.submit()">
                    <?php foreach ($aniosActivos as $anioFila): ?>
                    <option value="<?= (int) $anioFila['id'] ?>" <?= $anioSeleccionadoId === (int) $anioFila['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $anioFila['anio']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=techos&anio_id=<?= (int) $anioSeleccionadoId ?>">
            <input type="hidden" name="accion" value="guardar">

            <div class="fila-presupuesto-encabezado <?= $esSuperAdmin ? '' : 'sin-minimo' ?>">
                <span>Dependencia</span>
                <?php if ($esSuperAdmin): ?>
                <span>Mínimo presupuestal</span>
                <?php endif; ?>
                <span>Techo presupuestal</span>
                <span>Asignado</span>
                <span>Restante</span>
            </div>

            <?php
            $iconoCandadoCerrado = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
            $iconoCandadoAbierto = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';

            $renderizarNodoArbolTechos = function (array $nodo) use (&$renderizarNodoArbolTechos, $presupuestosActuales, $asignadoPorId, $esSuperAdmin, $anioSeleccionadoId, $iconoCandadoCerrado, $iconoCandadoAbierto): void {
                $dependencia = $nodo['dependencia'];
                $valores = $presupuestosActuales[$dependencia['id']] ?? ['minimo' => null, 'techo' => null, 'bloqueado' => false];
                $tieneHijos = !empty($nodo['hijos']);
                $bloqueado = !empty($valores['bloqueado']);
                $gastado = $asignadoPorId[(int) $dependencia['id']] ?? 0.0;
                $techoNumerico = $valores['techo'] !== null ? (float) $valores['techo'] : null;
                $restante = $techoNumerico !== null ? $techoNumerico - $gastado : null;
                ?>
                <div class="nodo-arbol-presupuesto">
                    <div class="fila-presupuesto-dependencia <?= $esSuperAdmin ? '' : 'sin-minimo' ?>">
                        <span class="fila-presupuesto-nombre">
                            <?php if ($tieneHijos): ?>
                            <button type="button" class="boton-expandir-arbol" aria-expanded="false" aria-label="Expandir">+</button>
                            <?php else: ?>
                            <span class="espaciador-expandir-arbol"></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($dependencia['nombre']) ?>
                        </span>
                        <?php if ($esSuperAdmin): ?>
                        <div class="campo-moneda">
                            <span>$</span>
                            <input type="number" name="minimo[<?= (int) $dependencia['id'] ?>]" min="0" step="0.01" placeholder="0.00" value="<?= $valores['minimo'] !== null ? htmlspecialchars((string) $valores['minimo']) : '' ?>">
                        </div>
                        <?php endif; ?>
                        <div class="campo-techo-con-candado">
                            <div class="campo-moneda">
                                <span>$</span>
                                <input
                                    type="number"
                                    class="campo-techo-input"
                                    name="techo[<?= (int) $dependencia['id'] ?>]"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    value="<?= $valores['techo'] !== null ? htmlspecialchars((string) $valores['techo']) : '' ?>"
                                    data-minimo="<?= $valores['minimo'] !== null ? htmlspecialchars((string) $valores['minimo']) : '' ?>"
                                    <?= $bloqueado && !$esSuperAdmin ? 'disabled' : '' ?>
                                >
                            </div>
                            <?php if ($esSuperAdmin): ?>
                            <button
                                type="button"
                                class="boton-candado-techo <?= $bloqueado ? 'candado-cerrado' : 'candado-abierto' ?>"
                                data-anio-id="<?= (int) $anioSeleccionadoId ?>"
                                data-dependencia-id="<?= (int) $dependencia['id'] ?>"
                                data-bloqueado="<?= $bloqueado ? '1' : '0' ?>"
                                title="<?= $bloqueado ? 'Bloqueado: clic para desbloquear' : 'Sin bloquear: clic para bloquear' ?>"
                            ><?= $bloqueado ? $iconoCandadoCerrado : $iconoCandadoAbierto ?></button>
                            <?php else: ?>
                            <span class="icono-candado-estatico <?= $bloqueado ? 'candado-cerrado' : 'candado-abierto' ?>" title="<?= $bloqueado ? 'Bloqueado por el administrador' : 'Sin bloquear' ?>">
                                <?= $bloqueado ? $iconoCandadoCerrado : $iconoCandadoAbierto ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <span class="etiqueta-monto">$<?= number_format($gastado, 2) ?></span>
                        <span class="etiqueta-monto <?= $restante !== null && $restante < 0 ? 'etiqueta-restante-negativo' : '' ?>">
                            <?= $restante !== null ? '$' . number_format($restante, 2) : '—' ?>
                        </span>
                        <p class="advertencia-minimo oculto">El techo es menor al mínimo presupuestal de esta dependencia.</p>
                    </div>
                    <?php if ($tieneHijos): ?>
                    <div class="subarbol-presupuesto oculto">
                        <?php foreach ($nodo['hijos'] as $nodoHijo) {
                            $renderizarNodoArbolTechos($nodoHijo);
                        } ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
            };

            foreach ($arbolHijas as $nodoRaiz) {
                $renderizarNodoArbolTechos($nodoRaiz);
            }
            ?>

            <div class="fila-presupuesto-total <?= $esSuperAdmin ? '' : 'sin-minimo' ?>">
                <span>Total</span>
                <?php if ($esSuperAdmin): ?>
                <span>$<?= number_format($totalMinimo, 2) ?></span>
                <?php endif; ?>
                <span>$<?= number_format($totalTecho, 2) ?></span>
                <span>$<?= number_format($totalAsignado, 2) ?></span>
                <span class="<?= $totalRestante < 0 ? 'etiqueta-restante-negativo' : '' ?>">$<?= number_format($totalRestante, 2) ?></span>
            </div>

            <div class="acciones-formulario-presupuestos">
                <button type="submit" class="boton-enviar">Guardar</button>
                <button type="submit" name="accion" value="notificar" class="boton-enviar" title="Guarda y envía un mensaje al avalador de cada dependencia cuyo techo se agregó o modificó">Guardar y notificar</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
