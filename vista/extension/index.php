<?php
$tituloPagina = 'Extensión';
$nombresMeses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];
$nombresMesesCompletos = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$catalogosListosEgreso = !empty($sedes) && !empty($lineas) && !empty($motores) && !empty($proyectos) && !empty($rubros) && !empty($autogestionItems) && !empty($aniosActivos);
$catalogosListosIngreso = !empty($autogestionItems) && !empty($aniosActivos);
$catalogosListos = $tab === 'ingresos' ? $catalogosListosIngreso : $catalogosListosEgreso;
$modoEdicion = $egresoParaEditar !== null || $ingresoParaEditar !== null;
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <?php
        $barraTituloExtra = null;
        if (!empty($autogestionItems)) {
            ob_start();
            ?>
            <select class="selector-autogestion-item" onchange="if (this.value) { window.location.href = this.value; }">
                <?php foreach ($autogestionItems as $itemAutogestion): ?>
                <option
                    value="index.php?ruta=extension&tab=<?= htmlspecialchars($tab) ?>&autogestion_id=<?= (int) $itemAutogestion['id'] ?>&anio_id=<?= (int) $anioSeleccionadoId ?>"
                    <?= $autogestionSeleccionadoId === (int) $itemAutogestion['id'] ? 'selected' : '' ?>
                ><?= htmlspecialchars($itemAutogestion['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php
            $barraTituloExtra = ob_get_clean();
        }

        $barraTitulo = 'Extensión';
        $barraBotonesSecundarios = [
            [
                'id' => 'boton-seleccionar-extension',
                'icono' => 'seleccionar',
                'etiqueta' => 'Seleccionar elementos',
                'disabled' => $modoEdicion,
            ],
            [
                'id' => 'boton-editar-extension',
                'icono' => 'editar',
                'etiqueta' => 'Editar seleccionado',
                'disabled' => true,
                'titulo_disabled' => $modoEdicion ? 'Ya estás editando un elemento' : 'Selecciona exactamente un elemento',
            ],
            [
                'id' => 'boton-duplicar-extension',
                'icono' => 'duplicar',
                'etiqueta' => 'Duplicar seleccionados',
                'disabled' => true,
                'titulo_disabled' => $modoEdicion ? 'No disponible mientras editas' : 'Selecciona uno o más elementos',
            ],
            [
                'id' => 'boton-eliminar-extension',
                'icono' => 'eliminar',
                'etiqueta' => 'Eliminar seleccionados',
                'disabled' => true,
                'titulo_disabled' => $modoEdicion ? 'No disponible mientras editas' : 'Selecciona uno o más elementos',
            ],
            [
                'id' => 'boton-abrir-modal-enviar-todo-extension',
                'icono' => 'enviar',
                'etiqueta' => 'Enviar todos los ingresos y egresos en borrador de este ítem',
                'disabled' => $modoEdicion || !$puedeEnviarTodo,
                'titulo_disabled' => 'Disponible cuando el total de egresos sea igual al total de ingresos de este ítem',
            ],
        ];
        if ($modoEdicion) {
            $barraBotonesSecundarios[] = [
                'id' => 'boton-nuevo-item-desde-edicion',
                'icono' => 'nuevo',
                'etiqueta' => 'Nuevo ítem',
            ];
        }
        $barraBotonPrincipal = $modoEdicion
            ? ['id' => 'boton-guardar-edicion-extension', 'etiqueta' => 'Guardar', 'form' => $egresoParaEditar !== null ? 'form-editar-egreso' : 'form-editar-ingreso']
            : ($catalogosListos ? ['id' => 'boton-abrir-modal-gasto', 'etiqueta' => '+ Agregar ' . ($tab === 'ingresos' ? 'ingreso' : 'egreso')] : null);
        $barraEstado = $modoEdicion ? 'edicion' : 'creacion';
        $barraRutaVolver = $modoEdicion
            ? ($volverEdicion !== '' ? $volverEdicion : 'index.php?ruta=extension&tab=' . $tab . '&anio_id=' . $anioSeleccionadoId . '&autogestion_id=' . $autogestionSeleccionadoId)
            : null;
        $barraTextoVolver = ($modoEdicion && $volverEdicion !== '') ? 'Volver a Peticiones' : 'Volver a Extensión';
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <div class="pestanas">
            <a href="index.php?ruta=extension&tab=ingresos" class="pestana<?= $tab === 'ingresos' ? ' activa' : '' ?>">Ingresos</a>
            <a href="index.php?ruta=extension&tab=egresos" class="pestana<?= $tab === 'egresos' ? ' activa' : '' ?>">Egresos</a>
            <?php if ($tab === 'ingresos'): ?>
            <span class="total-pestanas">Total ingresos: <?= number_format($totalGastado, 2) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if ($tab === 'egresos'): ?>
            <?php if (empty($sedes)): ?>
                <p class="mensaje-error">Primero debes crear al menos una sede en <a href="index.php?ruta=sedes">Configuraciones &gt; Sedes</a>.</p>
            <?php elseif (empty($lineas)): ?>
                <p class="mensaje-error">Primero debes crear al menos una línea en <a href="index.php?ruta=lineas">Configuraciones &gt; Línea</a>.</p>
            <?php elseif (empty($motores)): ?>
                <p class="mensaje-error">Primero debes crear al menos un motor en <a href="index.php?ruta=motores">Configuraciones &gt; Motor</a>.</p>
            <?php elseif (empty($proyectos)): ?>
                <p class="mensaje-error">Primero debes crear al menos un proyecto en <a href="index.php?ruta=proyectos">Configuraciones &gt; Proyecto</a>.</p>
            <?php elseif (empty($rubros)): ?>
                <p class="mensaje-error">Primero debes crear al menos un rubro en <a href="index.php?ruta=rubros">Configuraciones &gt; Rubros</a>.</p>
            <?php elseif (empty($autogestionItems)): ?>
                <p class="mensaje-error">Primero debes crear al menos un ítem en <a href="index.php?ruta=autogestion">Configuraciones &gt; Autogestión</a>.</p>
            <?php elseif (empty($aniosActivos)): ?>
                <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
            <?php endif; ?>
        <?php elseif (empty($autogestionItems)): ?>
            <p class="mensaje-error">Primero debes crear al menos un ítem en <a href="index.php?ruta=autogestion">Configuraciones &gt; Autogestión</a>.</p>
        <?php elseif (empty($aniosActivos)): ?>
            <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php endif; ?>

        <form method="GET" action="index.php" id="form-filtros" class="form-filtro-anio">
            <input type="hidden" name="ruta" value="extension">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <?php if (count($aniosActivos) >= 2): ?>
            <label for="anio_id_filtro">Año presupuestal</label>
            <select id="anio_id_filtro" name="anio_id" onchange="this.form.submit()">
                <?php foreach ($aniosActivos as $anioFila): ?>
                <option value="<?= (int) $anioFila['id'] ?>" <?= $anioSeleccionadoId === (int) $anioFila['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $anioFila['anio']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php elseif ($anioSeleccionadoId > 0): ?>
            <input type="hidden" name="anio_id" value="<?= $anioSeleccionadoId ?>">
            <?php endif; ?>
        </form>

        <?php if ($tab === 'egresos' && $anioSeleccionado): ?>
            <div class="progreso-presupuesto">
                <div class="progreso-presupuesto-info">
                    <span class="progreso-presupuesto-porcentaje">
                        <?php if ($presupuestoAnio > 0): ?>
                            <?= number_format($porcentajeGastado, 1) ?>% de los ingresos <?= (int) $anioSeleccionado['anio'] ?> asignados
                            (<?= number_format($totalEjecutado, 2) ?> / <?= number_format($presupuestoAnio, 2) ?>)
                        <?php else: ?>
                            Este ítem todavía no tiene ingresos registrados: no se pueden agregar egresos hasta que tenga.
                        <?php endif; ?>
                    </span>
                    <span class="progreso-presupuesto-dias">Día <?= $diaActual ?>/<?= $totalDiasAnio ?></span>
                </div>
                <div class="barra-progreso">
                    <div class="barra-progreso-segmento costos" style="width: <?= number_format($pctCostos, 2, '.', '') ?>%;"></div>
                    <div class="barra-progreso-segmento inversion" style="width: <?= number_format($pctInversion, 2, '.', '') ?>%;"></div>
                    <div class="barra-progreso-segmento excedentes" style="width: <?= number_format($pctExcedentes, 2, '.', '') ?>%;"></div>
                </div>
                <div class="progreso-presupuesto-leyenda">
                    <span><span class="punto costos"></span>Costos: <?= number_format($totalCostos, 2) ?></span>
                    <span><span class="punto inversion"></span>Inversión: <?= number_format($totalInversion, 2) ?></span>
                    <span><span class="punto excedentes"></span>Excedentes: <?= number_format($totalExcedentes, 2) ?></span>
                </div>
            </div>
        <?php elseif ($tab === 'ingresos' && $anioSeleccionado): ?>
            <div class="resumen-egresos">
                <span class="resumen-egresos-titulo">
                    <?php if ($presupuestoAnio > 0): ?>
                        <?= number_format($porcentajeGastado, 1) ?>% de los ingresos <?= (int) $anioSeleccionado['anio'] ?> asignados
                        (<?= number_format($totalEjecutado, 2) ?> / <?= number_format($presupuestoAnio, 2) ?>)
                    <?php else: ?>
                        Este ítem todavía no tiene ingresos registrados.
                    <?php endif; ?>
                </span>
                <div class="progreso-presupuesto-leyenda">
                    <span><span class="punto costos"></span>Costos: <?= number_format($totalCostos, 2) ?></span>
                    <span><span class="punto inversion"></span>Inversión: <?= number_format($totalInversion, 2) ?></span>
                    <span><span class="punto excedentes"></span>Excedentes: <?= number_format($totalExcedentes, 2) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($modoEdicion): ?>
        <?php require __DIR__ . '/' . ($egresoParaEditar !== null ? 'formulario-edicion-egreso.php' : 'formulario-edicion-ingreso.php'); ?>
        <?php elseif ($tab === 'egresos'): ?>
        <?php
        $egresosBorrador = array_values(array_filter($gastos, static fn (array $g): bool => $g['estado'] === 'borrador'));
        $egresosEnviado = array_values(array_filter($gastos, static fn (array $g): bool => $g['estado'] === 'enviado'));
        $filaEgresoExtension = static function (array $gasto) use ($anioSeleccionadoId, $autogestionSeleccionadoId, $nombresMeses): void {
            $mesesGasto = $gasto['meses'] !== ''
                ? array_map(static fn ($mes) => $nombresMeses[(int) $mes] ?? $mes, explode(',', $gasto['meses']))
                : [];
            ?>
                    <tr>
                        <td class="columna-seleccion">
                            <?php if ($gasto['tipo_automatico'] === null && $gasto['estado'] === 'borrador'): ?>
                            <input type="checkbox" class="checkbox-bulk-fila" data-id="<?= (int) $gasto['id'] ?>">
                            <?php endif; ?>
                        </td>
                        <td class="celda-acciones">
                            <?php if ($gasto['tipo_automatico'] === null && $gasto['estado'] === 'borrador'): ?>
                            <div class="acciones-fila">
                                <a
                                    href="index.php?ruta=extension&tab=egresos&anio_id=<?= (int) $anioSeleccionadoId ?>&autogestion_id=<?= (int) $autogestionSeleccionadoId ?>&editar_id=<?= (int) $gasto['id'] ?>"
                                    class="boton-accion boton-accion-editar"
                                >Editar</a>
                                <form method="POST" action="index.php?ruta=extension&tab=egresos&anio_id=<?= (int) $anioSeleccionadoId ?>&autogestion_id=<?= (int) $autogestionSeleccionadoId ?>" class="form-eliminar-egreso">
                                    <input type="hidden" name="tab" value="egresos">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $gasto['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                            <?php elseif ($gasto['tipo_automatico'] !== null): ?>
                            <span class="texto-atenuado">Automático</span>
                            <?php else: ?>
                            <span class="texto-atenuado">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($gasto['estado']) ?>"><?= $gasto['estado'] === 'enviado' ? 'Enviado' : 'Borrador' ?></span></td>
                        <td><?= htmlspecialchars($gasto['categoria']) ?></td>
                        <td><?= htmlspecialchars($gasto['sede_codigo'] . ' - ' . $gasto['sede_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['dependencia']) ?></td>
                        <td><?= htmlspecialchars($gasto['linea_codigo'] . ' - ' . $gasto['linea_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['motor_codigo'] . ' - ' . $gasto['motor_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['proyecto_codigo'] . ' - ' . $gasto['proyecto_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['objeto_proyecto_paa']) ?></td>
                        <td><?= htmlspecialchars($gasto['actividad']) ?></td>
                        <td><?= $gasto['rubro_id'] !== null ? htmlspecialchars($gasto['rubro_codigo'] . ' - ' . $gasto['rubro_descripcion']) : htmlspecialchars((string) $gasto['rubro_texto']) ?></td>
                        <td><?= htmlspecialchars($gasto['insumo']) ?></td>
                        <td><?= (int) $gasto['cantidad'] ?></td>
                        <td><?= number_format((float) $gasto['costo_unitario'], 2) ?></td>
                        <td><?= number_format((float) $gasto['valor_total'], 2) ?></td>
                        <td><?= htmlspecialchars(implode(', ', $mesesGasto)) ?></td>
                    </tr>
            <?php
        };
        ?>

        <details class="acordeon-grupo" open>
            <summary class="acordeon-cabecera">
                <span class="acordeon-flecha">▸</span>
                <span class="acordeon-icono-grupo"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></span>
                <span class="acordeon-titulo">Borradores</span>
                <span class="acordeon-contador"><?= count($egresosBorrador) ?></span>
            </summary>
            <div class="acordeon-cuerpo">
                <div
                    class="tabla-scroll tabla-bulk-seleccionable"
                    data-boton-seleccionar="boton-seleccionar-extension"
                    data-boton-editar="boton-editar-extension"
                    data-boton-duplicar="boton-duplicar-extension"
                    data-boton-eliminar="boton-eliminar-extension"
                    data-accion-form="index.php?ruta=extension&tab=egresos&anio_id=<?= (int) $anioSeleccionadoId ?>&autogestion_id=<?= (int) $autogestionSeleccionadoId ?>"
                    data-accion-eliminar="eliminar_seleccionados"
                    data-accion-duplicar="duplicar_seleccionados"
                    data-editar-en-pagina="1"
                    data-tab="egresos"
                >
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th class="columna-seleccion"><input type="checkbox" class="checkbox-bulk-todos"></th>
                                <th>Acciones</th>
                                <th>Estado</th>
                                <th>Categoría</th>
                                <th>Sede</th>
                                <th>Dependencia</th>
                                <th>Línea estratégica</th>
                                <th>Motor de desarrollo</th>
                                <th>Proyecto PDI</th>
                                <th>Contratos comunes</th>
                                <th>Actividad</th>
                                <th>Rubro</th>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th>Costo unitario</th>
                                <th>Valor total</th>
                                <th>Meses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($egresosBorrador as $gasto): $filaEgresoExtension($gasto); endforeach; ?>
                            <?php if (empty($egresosBorrador)): ?>
                            <tr>
                                <td colspan="17">No hay borradores.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>

        <details class="acordeon-grupo">
            <summary class="acordeon-cabecera">
                <span class="acordeon-flecha">▸</span>
                <span class="acordeon-icono-grupo"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg></span>
                <span class="acordeon-titulo">Enviados</span>
                <span class="acordeon-contador"><?= count($egresosEnviado) ?></span>
            </summary>
            <div class="acordeon-cuerpo">
                <div class="tabla-scroll">
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th class="columna-seleccion"></th>
                                <th>Acciones</th>
                                <th>Estado</th>
                                <th>Categoría</th>
                                <th>Sede</th>
                                <th>Dependencia</th>
                                <th>Línea estratégica</th>
                                <th>Motor de desarrollo</th>
                                <th>Proyecto PDI</th>
                                <th>Contratos comunes</th>
                                <th>Actividad</th>
                                <th>Rubro</th>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th>Costo unitario</th>
                                <th>Valor total</th>
                                <th>Meses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($egresosEnviado as $gasto): $filaEgresoExtension($gasto); endforeach; ?>
                            <?php if (empty($egresosEnviado)): ?>
                            <tr>
                                <td colspan="17">No hay egresos enviados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
        <?php else: ?>
        <?php
        $ingresosBorrador = array_values(array_filter($gastos, static fn (array $i): bool => $i['estado'] === 'borrador'));
        $ingresosEnviado = array_values(array_filter($gastos, static fn (array $i): bool => $i['estado'] === 'enviado'));
        $filaIngresoExtension = static function (array $ingreso) use ($anioSeleccionadoId, $autogestionSeleccionadoId): void {
            ?>
                    <tr>
                        <td class="columna-seleccion">
                            <?php if ($ingreso['estado'] === 'borrador'): ?>
                            <input type="checkbox" class="checkbox-bulk-fila" data-id="<?= (int) $ingreso['id'] ?>">
                            <?php endif; ?>
                        </td>
                        <td class="celda-acciones">
                            <?php if ($ingreso['estado'] === 'borrador'): ?>
                            <div class="acciones-fila">
                                <a
                                    href="index.php?ruta=extension&tab=ingresos&anio_id=<?= (int) $anioSeleccionadoId ?>&autogestion_id=<?= (int) $autogestionSeleccionadoId ?>&editar_id=<?= (int) $ingreso['id'] ?>"
                                    class="boton-accion boton-accion-editar"
                                >Editar</a>
                                <form method="POST" action="index.php?ruta=extension&tab=ingresos&anio_id=<?= (int) $anioSeleccionadoId ?>&autogestion_id=<?= (int) $autogestionSeleccionadoId ?>" class="form-eliminar-ingreso">
                                    <input type="hidden" name="tab" value="ingresos">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $ingreso['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span class="texto-atenuado">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($ingreso['estado']) ?>"><?= $ingreso['estado'] === 'enviado' ? 'Enviado' : 'Borrador' ?></span></td>
                        <td><?= htmlspecialchars($ingreso['dependencia']) ?></td>
                        <td>
                            <div class="lista-conceptos-celda">
                                <?php foreach ($ingreso['conceptos'] as $concepto): ?>
                                <span><?= htmlspecialchars($concepto['concepto']) ?> (×<?= (int) $concepto['cantidad'] ?>)</span>
                                <?php endforeach; ?>
                                <?php if (empty($ingreso['conceptos'])): ?>
                                <span class="texto-atenuado">Sin conceptos.</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="lista-conceptos-celda">
                                <?php foreach ($ingreso['conceptos'] as $concepto): ?>
                                <span><?= number_format((float) $concepto['valor'], 2) ?></span>
                                <?php endforeach; ?>
                                <?php if (empty($ingreso['conceptos'])): ?>
                                <span class="texto-atenuado">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= $ingreso['concepto_adicional'] !== '' ? htmlspecialchars($ingreso['concepto_adicional']) . ' (' . number_format((float) $ingreso['valor_adicional'], 2) . ')' : '—' ?></td>
                        <td><?= number_format((float) $ingreso['valor_total'], 2) ?></td>
                    </tr>
            <?php
        };
        ?>

        <details class="acordeon-grupo" open>
            <summary class="acordeon-cabecera">
                <span class="acordeon-flecha">▸</span>
                <span class="acordeon-icono-grupo"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></span>
                <span class="acordeon-titulo">Borradores</span>
                <span class="acordeon-contador"><?= count($ingresosBorrador) ?></span>
            </summary>
            <div class="acordeon-cuerpo">
                <div
                    class="tabla-scroll tabla-bulk-seleccionable"
                    data-boton-seleccionar="boton-seleccionar-extension"
                    data-boton-editar="boton-editar-extension"
                    data-boton-duplicar="boton-duplicar-extension"
                    data-boton-eliminar="boton-eliminar-extension"
                    data-accion-form="index.php?ruta=extension&tab=ingresos&anio_id=<?= (int) $anioSeleccionadoId ?>&autogestion_id=<?= (int) $autogestionSeleccionadoId ?>"
                    data-accion-eliminar="eliminar_seleccionados"
                    data-accion-duplicar="duplicar_seleccionados"
                    data-editar-en-pagina="1"
                    data-tab="ingresos"
                >
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th class="columna-seleccion"><input type="checkbox" class="checkbox-bulk-todos"></th>
                                <th>Acciones</th>
                                <th>Estado</th>
                                <th>Dependencia</th>
                                <th>Concepto</th>
                                <th>Valor</th>
                                <th>Concepto adicional</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingresosBorrador as $ingreso): $filaIngresoExtension($ingreso); endforeach; ?>
                            <?php if (empty($ingresosBorrador)): ?>
                            <tr>
                                <td colspan="8">No hay borradores.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>

        <details class="acordeon-grupo">
            <summary class="acordeon-cabecera">
                <span class="acordeon-flecha">▸</span>
                <span class="acordeon-icono-grupo"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg></span>
                <span class="acordeon-titulo">Enviados</span>
                <span class="acordeon-contador"><?= count($ingresosEnviado) ?></span>
            </summary>
            <div class="acordeon-cuerpo">
                <div class="tabla-scroll">
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th class="columna-seleccion"></th>
                                <th>Acciones</th>
                                <th>Estado</th>
                                <th>Dependencia</th>
                                <th>Concepto</th>
                                <th>Valor</th>
                                <th>Concepto adicional</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingresosEnviado as $ingreso): $filaIngresoExtension($ingreso); endforeach; ?>
                            <?php if (empty($ingresosEnviado)): ?>
                            <tr>
                                <td colspan="8">No hay ingresos enviados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
        <?php endif; ?>
    </div>

    <?php if ($catalogosListos): ?>
    <div id="modal-gasto" class="modal-fondo<?= !empty($error) ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Registrar <?= $tab === 'ingresos' ? 'ingreso' : 'egreso' ?></h2>
                <button type="button" id="boton-cerrar-modal-gasto" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <?php if ($tab === 'egresos'): ?>
            <form method="POST" action="index.php?ruta=extension&tab=egresos" id="form-egreso" class="form-necesidad">
                <input type="hidden" name="tab" value="egresos">
                <input type="hidden" name="autogestion_id" value="<?= $autogestionSeleccionadoId ?>">
                <div class="campo">
                    <label for="anio_presupuestal_id">Año presupuestal *</label>
                    <select id="anio_presupuestal_id" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>" <?= $anioSeleccionadoId === (int) $anioOpcion['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="categoria">Categoría *</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categoriasEgreso as $categoriaOpcion): ?>
                        <option value="<?= htmlspecialchars($categoriaOpcion) ?>"><?= htmlspecialchars($categoriaOpcion) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="sede_id">Sede *</label>
                    <select id="sede_id" name="sede_id" required>
                        <option value="">Selecciona una sede</option>
                        <?php foreach ($sedes as $sede): ?>
                        <option value="<?= (int) $sede['id'] ?>"><?= htmlspecialchars($sede['codigo'] . ' - ' . $sede['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="<?= (!$tieneHijas && $dependenciaPorDefecto !== null) ? 'dependencia' : 'dependencia_buscador' ?>">Dependencia *</label>
                    <?php if (!$tieneHijas && $dependenciaPorDefecto !== null): ?>
                    <select disabled>
                        <option selected><?= htmlspecialchars($dependenciaPorDefecto) ?></option>
                    </select>
                    <input type="hidden" name="dependencia" id="dependencia" value="<?= htmlspecialchars($dependenciaPorDefecto) ?>">
                    <?php else: ?>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaValorInicial = $dependenciaPorDefecto;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                    <?php endif; ?>
                </div>

                <?php $idPrefijoProyecto = ''; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

                <?php $idPrefijoContrato = ''; require __DIR__ . '/../parciales/selector-contrato-comun.php'; ?>

                <div class="campo campo-ancho">
                    <label for="actividad">Actividad *</label>
                    <input type="text" id="actividad" name="actividad" placeholder="Diligenciar" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="rubro_buscador">Rubro *</label>
                    <div class="selector-buscable" id="selector-rubro">
                        <input type="text" id="rubro_buscador" class="selector-buscable-input" placeholder="Buscar rubro..." autocomplete="off">
                        <input type="hidden" name="rubro_id" id="rubro_id">
                        <div class="selector-buscable-lista" id="rubro_lista">
                            <?php foreach ($rubros as $rubro): ?>
                            <div class="selector-buscable-opcion" data-id="<?= (int) $rubro['id'] ?>" data-texto="<?= htmlspecialchars($rubro['codigo'] . ' - ' . $rubro['descripcion']) ?>">
                                <?= htmlspecialchars($rubro['codigo'] . ' - ' . $rubro['descripcion']) ?>
                            </div>
                            <?php endforeach; ?>
                            <div class="selector-buscable-vacio">Sin resultados.</div>
                        </div>
                    </div>
                </div>

                <div class="campo">
                    <label for="insumo">Insumo *</label>
                    <input type="text" id="insumo" name="insumo" placeholder="Diligenciar" required>
                </div>

                <div class="campo">
                    <label for="cantidad">Cantidad *</label>
                    <input type="number" id="cantidad" name="cantidad" min="1" step="1" required>
                </div>

                <div class="campo">
                    <label for="costo_unitario">Costo unitario *</label>
                    <input type="number" id="costo_unitario" name="costo_unitario" min="0" step="0.01" required>
                </div>

                <div class="campo campo-ancho">
                    <label>Meses de ejecución *</label>
                    <div class="calendario-meses-envoltorio">
                        <div class="calendario-meses">
                            <div class="calendario-meses-cabecera">Calendario</div>
                            <div class="calendario-meses-grilla">
                                <?php foreach ($nombresMesesCompletos as $numero => $nombre): ?>
                                <label class="mes-celda">
                                    <input type="checkbox" name="meses[]" value="<?= $numero ?>">
                                    <span><?= htmlspecialchars($nombre) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="resumen-meses">
                            <h3>Distribución del presupuesto</h3>
                            <ul id="lista-meses-seleccionados" class="lista-meses-seleccionados">
                                <li class="lista-meses-vacio">Selecciona los meses de ejecución.</li>
                            </ul>
                            <div class="resumen-meses-total">
                                <span>Total</span>
                                <span id="resumen-meses-total-valor">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="boton-enviar">Registrar egreso</button>
            </form>
            <?php else: ?>
            <form method="POST" action="index.php?ruta=extension&tab=ingresos" class="form-necesidad">
                <input type="hidden" name="tab" value="ingresos">
                <input type="hidden" name="autogestion_id" value="<?= $autogestionSeleccionadoId ?>">
                <div class="campo">
                    <label for="anio_presupuestal_id">Año presupuestal *</label>
                    <select id="anio_presupuestal_id" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>" <?= $anioSeleccionadoId === (int) $anioOpcion['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="<?= (!$tieneHijas && $dependenciaPorDefecto !== null) ? 'dependencia_ingreso' : 'dependencia_ingreso_buscador' ?>">Dependencia *</label>
                    <?php if (!$tieneHijas && $dependenciaPorDefecto !== null): ?>
                    <select disabled>
                        <option selected><?= htmlspecialchars($dependenciaPorDefecto) ?></option>
                    </select>
                    <input type="hidden" name="dependencia" id="dependencia_ingreso" value="<?= htmlspecialchars($dependenciaPorDefecto) ?>">
                    <?php else: ?>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia';
                    $idBaseDependenciaOverride = 'dependencia_ingreso';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaValorInicial = $dependenciaPorDefecto;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                    <?php endif; ?>
                </div>

                <div class="campo campo-ancho">
                    <label>Conceptos *</label>
                    <div class="filas-conceptos" id="filas-conceptos">
                        <div class="fila-concepto">
                            <div class="campo">
                                <label>Concepto</label>
                                <input type="text" name="concepto[]" placeholder="Ej. Matrícula">
                            </div>
                            <div class="campo">
                                <label>Cantidad</label>
                                <input type="number" name="cantidad_concepto[]" min="1" step="1">
                            </div>
                            <div class="campo">
                                <label>Valor unitario</label>
                                <input type="number" name="valor_concepto[]" min="0" step="0.01">
                            </div>
                            <button type="button" class="boton-quitar-fila" aria-label="Quitar concepto">&times;</button>
                        </div>
                    </div>
                    <button type="button" class="boton-agregar-fila" id="boton-agregar-concepto">+ Agregar concepto</button>
                </div>

                <div class="campo">
                    <label for="concepto_adicional">Concepto adicional</label>
                    <input type="text" id="concepto_adicional" name="concepto_adicional" placeholder="Opcional">
                </div>

                <div class="campo">
                    <label for="valor_adicional">Valor adicional</label>
                    <input type="number" id="valor_adicional" name="valor_adicional" min="0" step="0.01" placeholder="0.00">
                </div>

                <button type="submit" class="boton-enviar">Registrar ingreso</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>


    <div id="modal-enviar-todo-extension" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Enviar todos los ingresos y egresos</h2>
                <button type="button" id="boton-cerrar-modal-enviar-todo-extension" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Se enviarán todos los ingresos y egresos en borrador de este ítem de autogestión, como una solicitud. Elige a quién se enviará: puede tener que pasar por varios avaladores intermedios antes de llegar al destino final.</p>

            <form method="POST" action="index.php?ruta=extension&anio_id=<?= (int) $anioSeleccionadoId ?>&autogestion_id=<?= (int) $autogestionSeleccionadoId ?>" class="form-necesidad">
                <input type="hidden" name="accion" value="enviar_todo">
                <input type="hidden" name="anio_presupuestal_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="autogestion_id" value="<?= (int) $autogestionSeleccionadoId ?>">

                <div class="campo">
                    <label for="enviar-todo-extension-destino_buscador">Enviar a la dependencia *</label>
                    <?php
                    $idPrefijoDependencia = 'enviar-todo-extension-destino-';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'enviar-todo-extension-destino';
                    $dependenciasOpciones = $dependenciasTodas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-todo-extension-rol">Rol al que se enviará *</label>
                    <select id="enviar-todo-extension-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo" style="display:none;">
                    <label for="enviar-todo-extension-destinatario">¿A quién exactamente? *</label>
                    <select
                        id="enviar-todo-extension-destinatario"
                        name="usuario_destinatario_id"
                        class="selector-destinatario"
                        data-campo-dependencia="enviar-todo-extension-destino"
                        data-campo-rol="enviar-todo-extension-rol"
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                </div>

                <button type="submit" class="boton-enviar">Enviar todo</button>
            </form>
        </div>
    </div>

    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

    <?php if ($modoEdicion): ?>
    <div id="modal-confirmar-salir-edicion" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Cambios sin guardar</h2>
            </div>

            <p class="texto-atenuado">Tienes cambios sin guardar en este ítem. Si sales ahora se perderán.</p>

            <div class="acciones-fila">
                <button type="button" id="boton-seguir-editando-edicion" class="boton-accion boton-accion-editar">Seguir editando</button>
                <button type="button" id="boton-salir-sin-guardar-edicion" class="boton-accion boton-accion-eliminar">Salir sin guardar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
