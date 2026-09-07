<?php
$tituloPagina = 'Gastos';
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
$catalogosListos = !empty($sedes) && !empty($lineas) && !empty($motores) && !empty($proyectos) && !empty($rubros) && !empty($aniosActivos);
$modoEdicion = $gastoParaEditar !== null;
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <?php
        $barraTitulo = 'Gastos';
        $barraBotonesSecundarios = [
            [
                'id' => 'boton-seleccionar-gastos',
                'icono' => 'seleccionar',
                'etiqueta' => 'Seleccionar elementos',
                'disabled' => $modoEdicion,
            ],
            [
                'id' => 'boton-editar-gastos',
                'icono' => 'editar',
                'etiqueta' => 'Editar seleccionado',
                'disabled' => true,
                'titulo_disabled' => $modoEdicion ? 'Ya estás editando un elemento' : 'Selecciona exactamente un elemento',
            ],
            [
                'id' => 'boton-duplicar-gastos',
                'icono' => 'duplicar',
                'etiqueta' => 'Duplicar seleccionados',
                'disabled' => true,
                'titulo_disabled' => $modoEdicion ? 'No disponible mientras editas' : 'Selecciona uno o más elementos',
            ],
            [
                'id' => 'boton-eliminar-gastos',
                'icono' => 'eliminar',
                'etiqueta' => 'Eliminar seleccionados',
                'disabled' => true,
                'titulo_disabled' => $modoEdicion ? 'No disponible mientras editas' : 'Selecciona uno o más elementos',
            ],
            [
                'id' => 'boton-abrir-modal-enviar-todo-gasto',
                'icono' => 'enviar',
                'etiqueta' => 'Enviar todos los gastos en borrador',
                'disabled' => $modoEdicion || !$puedeEnviarTodo,
                'titulo_disabled' => 'Disponible cuando se haya ejecutado el 100% del presupuesto',
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
            ? ['id' => 'boton-guardar-edicion-gasto', 'etiqueta' => 'Guardar', 'form' => 'form-editar-gasto']
            : ($catalogosListos ? ['id' => 'boton-abrir-modal-gasto', 'etiqueta' => '+ Agregar gasto'] : null);
        $barraEstado = $modoEdicion ? 'edicion' : 'creacion';
        $barraRutaVolver = $modoEdicion
            ? ($volverEdicion !== '' ? $volverEdicion : 'index.php?ruta=gastos&anio_id=' . $anioSeleccionadoId)
            : null;
        $barraTextoVolver = ($modoEdicion && $volverEdicion !== '') ? 'Volver a Peticiones' : 'Volver a Gastos';
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <div class="acciones-importar-exportar">
            <a href="index.php?ruta=gastos-exportar-plantilla" class="boton-secundario">Exportar plantilla (.xlsx)</a>
            <form method="POST" action="index.php?ruta=gastos<?= $anioSeleccionadoId > 0 ? '&anio_id=' . $anioSeleccionadoId : '' ?>" enctype="multipart/form-data" class="form-importar">
                <input type="hidden" name="accion" value="importar">
                <input type="file" name="archivo" accept=".xlsx" required>
                <button type="submit" class="boton-secundario">Importar</button>
            </form>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($erroresImportacion)): ?>
            <div class="mensaje-error">
                <ul class="lista-errores-importacion">
                    <?php foreach ($erroresImportacion as $errorFila): ?>
                    <li><?= htmlspecialchars($errorFila) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

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
        <?php elseif (empty($aniosActivos)): ?>
            <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php endif; ?>

        <?php if (count($aniosActivos) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="gastos">
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

        <?php if ($anioSeleccionado): ?>
            <div class="progreso-presupuesto">
                <div class="progreso-presupuesto-info">
                    <span class="progreso-presupuesto-porcentaje">
                        <?php if ($presupuestoAnio > 0): ?>
                            <?= number_format($porcentajeGastado, 1) ?>% del <?= $dependenciaUsuarioEsRaiz ? 'presupuesto' : 'techo' ?> <?= (int) $anioSeleccionado['anio'] ?><?= $dependenciaUsuarioEsRaiz ? '' : ' de tu dependencia' ?>
                            (<?= number_format($totalGastado, 2) ?> / <?= number_format($presupuestoAnio, 2) ?>)
                        <?php elseif ($dependenciaUsuarioId === null): ?>
                            No tienes una dependencia asignada, así que no se puede mostrar un techo presupuestal.
                        <?php else: ?>
                            Tu dependencia no tiene techo presupuestal asignado para este año.
                        <?php endif; ?>
                    </span>
                    <span class="progreso-presupuesto-dias">Día <?= $diaActual ?>/<?= $totalDiasAnio ?></span>
                </div>
                <?php if ($puedeVerTechos): ?>
                <a href="index.php?ruta=techos" class="barra-progreso" title="Ir a Techos">
                    <div class="barra-progreso-relleno" style="width: <?= number_format($porcentajeGastado, 2, '.', '') ?>%;"></div>
                </a>
                <?php else: ?>
                <div class="barra-progreso" title="Techo presupuestal (solo informativo)">
                    <div class="barra-progreso-relleno" style="width: <?= number_format($porcentajeGastado, 2, '.', '') ?>%;"></div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($modoEdicion): ?>
        <?php require __DIR__ . '/formulario-edicion.php'; ?>
        <?php else: ?>
        <?php
        $gastosBorrador = array_values(array_filter($gastos, static fn (array $g): bool => $g['estado'] === 'borrador'));
        $gastosEnviado = array_values(array_filter($gastos, static fn (array $g): bool => $g['estado'] === 'enviado'));
        $filaGasto = static function (array $gasto) use ($anioSeleccionadoId, $nombresMeses): void {
            $mesesGasto = $gasto['meses'] !== ''
                ? array_map(static fn ($mes) => $nombresMeses[(int) $mes] ?? $mes, explode(',', $gasto['meses']))
                : [];
            ?>
                    <tr>
                        <td class="columna-seleccion">
                            <?php if ($gasto['estado'] === 'borrador'): ?>
                            <input type="checkbox" class="checkbox-bulk-fila" data-id="<?= (int) $gasto['id'] ?>">
                            <?php endif; ?>
                        </td>
                        <td class="celda-acciones">
                            <?php if ($gasto['estado'] === 'borrador'): ?>
                            <div class="acciones-fila">
                                <a
                                    href="index.php?ruta=gastos&anio_id=<?= (int) $anioSeleccionadoId ?>&editar_id=<?= (int) $gasto['id'] ?>"
                                    class="boton-accion boton-accion-editar"
                                >Editar</a>
                                <form method="POST" action="index.php?ruta=gastos&anio_id=<?= (int) $anioSeleccionadoId ?>" class="form-eliminar-gasto">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $gasto['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span class="texto-atenuado">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($gasto['estado']) ?>"><?= $gasto['estado'] === 'enviado' ? 'Enviado' : 'Borrador' ?></span></td>
                        <td><?= htmlspecialchars($gasto['sede_codigo'] . ' - ' . $gasto['sede_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['dependencia']) ?></td>
                        <td><?= htmlspecialchars($gasto['linea_codigo'] . ' - ' . $gasto['linea_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['motor_codigo'] . ' - ' . $gasto['motor_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['proyecto_codigo'] . ' - ' . $gasto['proyecto_nombre']) ?></td>
                        <td><?= htmlspecialchars($gasto['objeto_proyecto_paa']) ?></td>
                        <td><?= htmlspecialchars($gasto['actividad']) ?></td>
                        <td><?= $gasto['rubro_id'] !== null ? htmlspecialchars($gasto['rubro_codigo'] . ' - ' . $gasto['rubro_descripcion']) : htmlspecialchars($gasto['rubro_texto'] ?? '—') ?></td>
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
                <span class="acordeon-contador"><?= count($gastosBorrador) ?></span>
            </summary>
            <div class="acordeon-cuerpo">
                <div
                    class="tabla-scroll tabla-bulk-seleccionable"
                    data-boton-seleccionar="boton-seleccionar-gastos"
                    data-boton-editar="boton-editar-gastos"
                    data-boton-duplicar="boton-duplicar-gastos"
                    data-boton-eliminar="boton-eliminar-gastos"
                    data-accion-form="index.php?ruta=gastos&anio_id=<?= (int) $anioSeleccionadoId ?>"
                    data-accion-eliminar="eliminar_seleccionados"
                    data-accion-duplicar="duplicar_seleccionados"
                    data-editar-en-pagina="1"
                >
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th class="columna-seleccion"><input type="checkbox" class="checkbox-bulk-todos"></th>
                                <th>Acciones</th>
                                <th>Estado</th>
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
                            <?php foreach ($gastosBorrador as $gasto): $filaGasto($gasto); endforeach; ?>
                            <?php if (empty($gastosBorrador)): ?>
                            <tr>
                                <td colspan="16">No hay borradores.</td>
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
                <span class="acordeon-contador"><?= count($gastosEnviado) ?></span>
            </summary>
            <div class="acordeon-cuerpo">
                <div class="tabla-scroll">
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th class="columna-seleccion"></th>
                                <th>Acciones</th>
                                <th>Estado</th>
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
                            <?php foreach ($gastosEnviado as $gasto): $filaGasto($gasto); endforeach; ?>
                            <?php if (empty($gastosEnviado)): ?>
                            <tr>
                                <td colspan="16">No hay gastos enviados.</td>
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
    <div id="modal-gasto" class="modal-fondo<?= !empty($error) && $accion !== 'actualizar' ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Registrar gasto</h2>
                <button type="button" id="boton-cerrar-modal-gasto" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <?php if (!empty($error) && $accion !== 'actualizar'): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" action="index.php?ruta=gastos" class="form-necesidad">
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
                                <span id="resumen-meses-total-valor" class="resumen-meses-total-valor">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="boton-enviar">Registrar gasto</button>
            </form>
        </div>
    </div>

    <div id="modal-enviar-todo-gasto" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Enviar todos los gastos</h2>
                <button type="button" id="boton-cerrar-modal-enviar-todo-gasto" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Se enviarán todos los gastos en borrador de la dependencia seleccionada para el año presupuestal actual.</p>

            <form method="POST" action="index.php?ruta=gastos&anio_id=<?= (int) $anioSeleccionadoId ?>" class="form-necesidad form-confirmar-envio" data-campo-dependencia="enviar-todo-gasto-dependencia" data-campo-rol="enviar-todo-gasto-rol">
                <input type="hidden" name="accion" value="enviar_todo">
                <input type="hidden" name="anio_presupuestal_id" value="<?= (int) $anioSeleccionadoId ?>">

                <div class="campo">
                    <label for="enviar-todo-gasto-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = 'enviar-todo-gasto-';
                    $nombreCampoDependencia = 'dependencia';
                    $dependenciasOpciones = $dependenciasConTipo;
                    $dependenciaDataSelectRol = 'enviar-todo-gasto-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-todo-gasto-rol">Rol al que se enviará *</label>
                    <select id="enviar-todo-gasto-rol" name="rol_destinatario_id" required>
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

    <script type="application/json" id="datos-roles-por-tipo"><?= json_encode($rolesPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

    <?php endif; ?>

    <?php if ($modoEdicion): ?>
    <div id="modal-confirmar-salir-edicion" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Cambios sin guardar</h2>
            </div>

            <p class="texto-atenuado">Tienes cambios sin guardar en este gasto. Si sales ahora se perderán.</p>

            <div class="acciones-fila">
                <button type="button" id="boton-seguir-editando-edicion" class="boton-accion boton-accion-editar">Seguir editando</button>
                <button type="button" id="boton-salir-sin-guardar-edicion" class="boton-accion boton-accion-eliminar">Salir sin guardar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
