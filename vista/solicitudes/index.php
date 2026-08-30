<?php
$tituloPagina = 'Solicitudes';
$numerosRomanos = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
$catalogosListos = !empty($aniosActivos);
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>2.2 Solicitudes</h1>
            <?php if ($catalogosListos && $tab === 'arl'): ?>
            <button type="button" id="boton-abrir-modal-solicitud" class="boton-agregar">+ Agregar solicitud</button>
            <?php elseif ($catalogosListos && $tab === 'monitores'): ?>
            <button type="button" id="boton-abrir-modal-monitor" class="boton-agregar">+ Agregar solicitud</button>
            <?php elseif ($tab === 'ops' && !empty($sedes) && !empty($lineas) && !empty($motores) && !empty($proyectos) && !empty($rubros) && !empty($aniosActivos)): ?>
            <button type="button" id="boton-abrir-modal-ops" class="boton-agregar">+ Agregar solicitud</button>
            <?php elseif ($catalogosListos && $tab === 'otros'): ?>
            <button type="button" id="boton-abrir-modal-peticion" class="boton-agregar">+ Agregar solicitud</button>
            <?php endif; ?>
        </div>
        <p>Son de carácter informativo.</p>

        <div class="pestanas">
            <a href="index.php?ruta=solicitudes&tab=arl" class="pestana<?= $tab === 'arl' ? ' activa' : '' ?>">1. ARL de estudiantes en prácticas</a>
            <a href="index.php?ruta=solicitudes&tab=monitores" class="pestana<?= $tab === 'monitores' ? ' activa' : '' ?>">2. Monitores</a>
            <a href="index.php?ruta=solicitudes&tab=ops" class="pestana<?= $tab === 'ops' ? ' activa' : '' ?>">3. OPS prestación de servicios</a>
            <a href="index.php?ruta=solicitudes&tab=otros" class="pestana<?= $tab === 'otros' ? ' activa' : '' ?>">4. Petición</a>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (!in_array($tab, ['arl', 'monitores', 'ops', 'otros'], true)): ?>
        <p class="texto-atenuado">Este tipo de solicitud está en construcción.</p>
        <?php else: ?>

        <?php if (empty($aniosActivos)): ?>
            <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php endif; ?>

        <?php if (count($aniosActivos) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="solicitudes">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
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

        <?php if ($tab === 'arl'): ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Facultad</th>
                        <th>Estado</th>
                        <th>Total de practicantes</th>
                        <th>Total valor año</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $solicitud): ?>
                    <?php
                    $totalPracticantes = (int) $solicitud['riesgo1_estudiantes'] + (int) $solicitud['riesgo2_estudiantes']
                        + (int) $solicitud['riesgo3_estudiantes'] + (int) $solicitud['riesgo4_estudiantes']
                        + (int) $solicitud['riesgo5_estudiantes'];
                    $totalValor = (float) $solicitud['riesgo1_valor'] + (float) $solicitud['riesgo2_valor']
                        + (float) $solicitud['riesgo3_valor'] + (float) $solicitud['riesgo4_valor']
                        + (float) $solicitud['riesgo5_valor'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($solicitud['facultad']) ?></td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($solicitud['estado']) ?>" <?= !empty($solicitud['enviada_a']) ? 'title="Enviada a: ' . htmlspecialchars($solicitud['enviada_a']) . '"' : '' ?>><?= $solicitud['estado'] === 'enviada' ? 'Enviada' : 'Borrador' ?></span></td>
                        <td><?= $totalPracticantes ?></td>
                        <td>$ <?= number_format($totalValor, 2) ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a
                                    href="index.php?ruta=solicitud-detalle&tipo=arl&id=<?= (int) $solicitud['id'] ?>"
                                    class="boton-accion boton-accion-ver"
                                >Ver</a>
                                <?php if ($solicitud['estado'] === 'borrador'): ?>
                                <button
                                    type="button"
                                    class="boton-accion boton-accion-editar fila-menu-editar-solicitud"
                                    data-solicitud="<?= htmlspecialchars(json_encode($solicitud, JSON_UNESCAPED_UNICODE)) ?>"
                                >Editar</button>
                                <form
                                    method="POST"
                                    action="index.php?ruta=solicitudes"
                                    class="form-enviar-solicitud"
                                    data-dependencia="<?= htmlspecialchars($solicitud['facultad']) ?>"
                                    data-rol="<?= (int) ($solicitud['rol_destinatario_id'] ?? 0) ?>"
                                >
                                    <input type="hidden" name="accion" value="enviar">
                                    <input type="hidden" name="tab" value="arl">
                                    <input type="hidden" name="id" value="<?= (int) $solicitud['id'] ?>">
                                    <input type="hidden" name="usuario_destinatario_id" value="">
                                    <button type="submit" class="boton-accion boton-accion-enviar">Enviar</button>
                                </form>
                                <form method="POST" action="index.php?ruta=solicitudes" class="form-eliminar-solicitud">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="tab" value="arl">
                                    <input type="hidden" name="id" value="<?= (int) $solicitud['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="5">No hay solicitudes registradas para este año.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($tab === 'monitores'): ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Monitores semestre I</th>
                        <th>Monitores semestre II</th>
                        <th>N° de monitores</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudesMonitores as $solicitudMonitor): ?>
                    <?php $totalMonitores = (int) $solicitudMonitor['monitores_semestre1'] + (int) $solicitudMonitor['monitores_semestre2']; ?>
                    <tr>
                        <td><?= htmlspecialchars($solicitudMonitor['dependencia']) ?></td>
                        <td><?= htmlspecialchars($tiposMonitor[$solicitudMonitor['tipo']] ?? $solicitudMonitor['tipo']) ?></td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($solicitudMonitor['estado']) ?>" <?= !empty($solicitudMonitor['enviada_a']) ? 'title="Enviada a: ' . htmlspecialchars($solicitudMonitor['enviada_a']) . '"' : '' ?>><?= $solicitudMonitor['estado'] === 'enviada' ? 'Enviada' : 'Borrador' ?></span></td>
                        <td><?= (int) $solicitudMonitor['monitores_semestre1'] ?></td>
                        <td><?= (int) $solicitudMonitor['monitores_semestre2'] ?></td>
                        <td><?= $totalMonitores > 0 ? $totalMonitores : '-' ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a
                                    href="index.php?ruta=solicitud-detalle&tipo=monitores&id=<?= (int) $solicitudMonitor['id'] ?>"
                                    class="boton-accion boton-accion-ver"
                                >Ver</a>
                                <?php if ($solicitudMonitor['estado'] === 'borrador'): ?>
                                <button
                                    type="button"
                                    class="boton-accion boton-accion-editar fila-menu-editar-monitor"
                                    data-monitor="<?= htmlspecialchars(json_encode($solicitudMonitor, JSON_UNESCAPED_UNICODE)) ?>"
                                >Editar</button>
                                <form
                                    method="POST"
                                    action="index.php?ruta=solicitudes"
                                    class="form-enviar-solicitud"
                                    data-dependencia="<?= htmlspecialchars($solicitudMonitor['dependencia']) ?>"
                                    data-rol="<?= (int) ($solicitudMonitor['rol_destinatario_id'] ?? 0) ?>"
                                >
                                    <input type="hidden" name="accion" value="enviar_monitor">
                                    <input type="hidden" name="tab" value="monitores">
                                    <input type="hidden" name="id" value="<?= (int) $solicitudMonitor['id'] ?>">
                                    <input type="hidden" name="usuario_destinatario_id" value="">
                                    <button type="submit" class="boton-accion boton-accion-enviar">Enviar</button>
                                </form>
                                <form method="POST" action="index.php?ruta=solicitudes" class="form-eliminar-monitor">
                                    <input type="hidden" name="accion" value="eliminar_monitor">
                                    <input type="hidden" name="tab" value="monitores">
                                    <input type="hidden" name="id" value="<?= (int) $solicitudMonitor['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($solicitudesMonitores)): ?>
                    <tr>
                        <td colspan="7">No hay solicitudes de monitores registradas para este año.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($tab === 'ops'): ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Sede</th>
                        <th>Dependencia</th>
                        <th>Rubro</th>
                        <th>Perfil</th>
                        <th>Estado</th>
                        <th>Valor</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudesOps as $solicitudOps): ?>
                    <?php $totalOps = (float) $solicitudOps['valor'] * (int) $solicitudOps['cantidad']; ?>
                    <tr>
                        <td><?= htmlspecialchars($solicitudOps['sede_codigo'] . ' - ' . $solicitudOps['sede_nombre']) ?></td>
                        <td><?= htmlspecialchars($solicitudOps['dependencia']) ?></td>
                        <td><?= htmlspecialchars($solicitudOps['rubro_codigo'] . ' - ' . $solicitudOps['rubro_descripcion']) ?></td>
                        <td><?= htmlspecialchars($perfilesOps[$solicitudOps['perfil']] ?? $solicitudOps['perfil']) ?></td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($solicitudOps['estado']) ?>" <?= !empty($solicitudOps['enviada_a']) ? 'title="Enviada a: ' . htmlspecialchars($solicitudOps['enviada_a']) . '"' : '' ?>><?= $solicitudOps['estado'] === 'enviada' ? 'Enviada' : 'Borrador' ?></span></td>
                        <td>$ <?= number_format((float) $solicitudOps['valor'], 2) ?></td>
                        <td><?= (int) $solicitudOps['cantidad'] ?></td>
                        <td>$ <?= number_format($totalOps, 2) ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a
                                    href="index.php?ruta=solicitud-detalle&tipo=ops&id=<?= (int) $solicitudOps['id'] ?>"
                                    class="boton-accion boton-accion-ver"
                                >Ver</a>
                                <?php if ($solicitudOps['estado'] === 'borrador'): ?>
                                <button
                                    type="button"
                                    class="boton-accion boton-accion-editar fila-menu-editar-ops"
                                    data-ops="<?= htmlspecialchars(json_encode($solicitudOps, JSON_UNESCAPED_UNICODE)) ?>"
                                >Editar</button>
                                <form
                                    method="POST"
                                    action="index.php?ruta=solicitudes"
                                    class="form-enviar-solicitud"
                                    data-dependencia="<?= htmlspecialchars($solicitudOps['dependencia']) ?>"
                                    data-rol="<?= (int) ($solicitudOps['rol_destinatario_id'] ?? 0) ?>"
                                >
                                    <input type="hidden" name="accion" value="enviar_ops">
                                    <input type="hidden" name="tab" value="ops">
                                    <input type="hidden" name="id" value="<?= (int) $solicitudOps['id'] ?>">
                                    <input type="hidden" name="usuario_destinatario_id" value="">
                                    <button type="submit" class="boton-accion boton-accion-enviar">Enviar</button>
                                </form>
                                <form method="POST" action="index.php?ruta=solicitudes" class="form-eliminar-ops">
                                    <input type="hidden" name="accion" value="eliminar_ops">
                                    <input type="hidden" name="tab" value="ops">
                                    <input type="hidden" name="id" value="<?= (int) $solicitudOps['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($solicitudesOps)): ?>
                    <tr>
                        <td colspan="9">No hay solicitudes OPS registradas para este año.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Estado</th>
                        <th>Semestre 1</th>
                        <th>Valor S1</th>
                        <th>Semestre 2</th>
                        <th>Valor S2</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudesPeticiones as $solicitudPeticion): ?>
                    <tr>
                        <td><?= htmlspecialchars($solicitudPeticion['concepto']) ?></td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($solicitudPeticion['estado']) ?>" <?= !empty($solicitudPeticion['enviada_a']) ? 'title="Enviada a: ' . htmlspecialchars($solicitudPeticion['enviada_a']) . '"' : '' ?>><?= $solicitudPeticion['estado'] === 'enviada' ? 'Enviada' : 'Borrador' ?></span></td>
                        <td><?= (int) $solicitudPeticion['semestre1'] ?></td>
                        <td>$ <?= number_format((float) $solicitudPeticion['valor_s1'], 2) ?></td>
                        <td><?= (int) $solicitudPeticion['semestre2'] ?></td>
                        <td>$ <?= number_format((float) $solicitudPeticion['valor_s2'], 2) ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a
                                    href="index.php?ruta=solicitud-detalle&tipo=otros&id=<?= (int) $solicitudPeticion['id'] ?>"
                                    class="boton-accion boton-accion-ver"
                                >Ver</a>
                                <?php if ($solicitudPeticion['estado'] === 'borrador'): ?>
                                <button
                                    type="button"
                                    class="boton-accion boton-accion-editar fila-menu-editar-peticion"
                                    data-peticion="<?= htmlspecialchars(json_encode($solicitudPeticion, JSON_UNESCAPED_UNICODE)) ?>"
                                >Editar</button>
                                <form method="POST" action="index.php?ruta=solicitudes" class="form-enviar-solicitud">
                                    <input type="hidden" name="accion" value="enviar_peticion">
                                    <input type="hidden" name="tab" value="otros">
                                    <input type="hidden" name="id" value="<?= (int) $solicitudPeticion['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-enviar">Enviar</button>
                                </form>
                                <form method="POST" action="index.php?ruta=solicitudes" class="form-eliminar-peticion">
                                    <input type="hidden" name="accion" value="eliminar_peticion">
                                    <input type="hidden" name="tab" value="otros">
                                    <input type="hidden" name="id" value="<?= (int) $solicitudPeticion['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($solicitudesPeticiones)): ?>
                    <tr>
                        <td colspan="7">No hay peticiones registradas para este año.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($catalogosListos): ?>
    <div id="modal-solicitud" class="modal-fondo<?= !empty($error) && $tab === 'arl' ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Agregar solicitud — ARL de estudiantes en prácticas</h2>
                <button type="button" id="boton-cerrar-modal-solicitud" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" id="form-solicitud-arl" class="form-necesidad"
                data-smlv-por-anio="<?= htmlspecialchars(json_encode($smlvPorAnio)) ?>"
                data-porcentajes-riesgo="<?= htmlspecialchars(json_encode($porcentajesRiesgo)) ?>">
                <input type="hidden" name="tab" value="arl">
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
                    <label for="facultad_buscador">Facultad *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'facultad';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'rol_destinatario_id';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="rol_destinatario_id">Rol al que se enviará *</label>
                    <select id="rol_destinatario_id" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label>Riesgos ARL *</label>
                    <div class="tabla-scroll">
                        <table class="tabla-usuarios tabla-solicitud-riesgos">
                            <thead>
                                <tr>
                                    <th>Clase de riesgo</th>
                                    <th>Número de estudiantes</th>
                                    <th>Valor anual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($numerosRomanos as $nivel => $numero): ?>
                                <tr>
                                    <td>Riesgo <?= $numero ?></td>
                                    <td>
                                        <label class="etiqueta-oculta" for="riesgo<?= $nivel ?>_estudiantes">Número de estudiantes Riesgo <?= $numero ?></label>
                                        <input type="number" id="riesgo<?= $nivel ?>_estudiantes" name="riesgo<?= $nivel ?>_estudiantes" class="campo-solicitud-estudiantes" data-nivel="<?= $nivel ?>" min="0" step="1" value="0">
                                    </td>
                                    <td>
                                        <label class="etiqueta-oculta" for="riesgo<?= $nivel ?>_valor">Valor anual Riesgo <?= $numero ?></label>
                                        <input type="text" id="riesgo<?= $nivel ?>_valor" name="riesgo<?= $nivel ?>_valor" class="campo-solicitud-valor" value="0.00" readonly>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <button type="submit" class="boton-enviar">Registrar solicitud</button>
            </form>
        </div>
    </div>

    <div id="modal-editar-solicitud" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar solicitud — ARL de estudiantes en prácticas</h2>
                <button type="button" id="boton-cerrar-modal-editar-solicitud" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" id="form-editar-solicitud-arl" class="form-necesidad"
                data-smlv-por-anio="<?= htmlspecialchars(json_encode($smlvPorAnio)) ?>"
                data-porcentajes-riesgo="<?= htmlspecialchars(json_encode($porcentajesRiesgo)) ?>">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="tab" value="arl">
                <input type="hidden" name="id" id="editar-solicitud-id" value="">

                <div class="campo">
                    <label for="editar-solicitud-anio">Año presupuestal *</label>
                    <select id="editar-solicitud-anio" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-solicitud-facultad_buscador">Facultad *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'facultad';
                    $idBaseDependenciaOverride = 'editar-solicitud-facultad';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'editar-solicitud-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="editar-solicitud-rol">Rol al que se enviará *</label>
                    <select id="editar-solicitud-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label>Riesgos ARL *</label>
                    <div class="tabla-scroll">
                        <table class="tabla-usuarios tabla-solicitud-riesgos">
                            <thead>
                                <tr>
                                    <th>Clase de riesgo</th>
                                    <th>Número de estudiantes</th>
                                    <th>Valor anual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($numerosRomanos as $nivel => $numero): ?>
                                <tr>
                                    <td>Riesgo <?= $numero ?></td>
                                    <td>
                                        <label class="etiqueta-oculta" for="editar-solicitud-riesgo<?= $nivel ?>_estudiantes">Número de estudiantes Riesgo <?= $numero ?></label>
                                        <input type="number" id="editar-solicitud-riesgo<?= $nivel ?>_estudiantes" name="riesgo<?= $nivel ?>_estudiantes" class="campo-solicitud-estudiantes" data-nivel="<?= $nivel ?>" min="0" step="1" value="0">
                                    </td>
                                    <td>
                                        <label class="etiqueta-oculta" for="editar-solicitud-riesgo<?= $nivel ?>_valor">Valor anual Riesgo <?= $numero ?></label>
                                        <input type="text" id="editar-solicitud-riesgo<?= $nivel ?>_valor" name="riesgo<?= $nivel ?>_valor" class="campo-solicitud-valor" value="0.00" readonly>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

    <div id="modal-monitor" class="modal-fondo<?= !empty($error) && $tab === 'monitores' ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Agregar solicitud — Monitores</h2>
                <button type="button" id="boton-cerrar-modal-monitor" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" class="form-necesidad">
                <input type="hidden" name="accion" value="crear_monitor">
                <input type="hidden" name="tab" value="monitores">
                <div class="campo">
                    <label for="monitor_anio_presupuestal_id">Año presupuestal *</label>
                    <select id="monitor_anio_presupuestal_id" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>" <?= $anioSeleccionadoId === (int) $anioOpcion['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="monitor_dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia';
                    $idBaseDependenciaOverride = 'monitor_dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'monitor_rol_destinatario_id';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="monitor_rol_destinatario_id">Rol al que se enviará *</label>
                    <select id="monitor_rol_destinatario_id" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="monitor_tipo">Tipo *</label>
                    <select id="monitor_tipo" name="tipo" required>
                        <option value="">Selecciona un tipo</option>
                        <?php foreach ($tiposMonitor as $tipoClave => $tipoNombre): ?>
                        <option value="<?= htmlspecialchars($tipoClave) ?>"><?= htmlspecialchars($tipoNombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="monitor_semestre1">Monitores semestre I</label>
                    <input type="number" id="monitor_semestre1" name="monitores_semestre1" min="0" step="1" value="0">
                </div>

                <div class="campo">
                    <label for="monitor_semestre2">Monitores semestre II</label>
                    <input type="number" id="monitor_semestre2" name="monitores_semestre2" min="0" step="1" value="0">
                </div>

                <button type="submit" class="boton-enviar">Registrar solicitud</button>
            </form>
        </div>
    </div>

    <div id="modal-editar-monitor" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar solicitud — Monitores</h2>
                <button type="button" id="boton-cerrar-modal-editar-monitor" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar_monitor">
                <input type="hidden" name="tab" value="monitores">
                <input type="hidden" name="id" id="editar-monitor-id" value="">

                <div class="campo">
                    <label for="editar-monitor-anio">Año presupuestal *</label>
                    <select id="editar-monitor-anio" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-monitor-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia';
                    $idBaseDependenciaOverride = 'editar-monitor-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'editar-monitor-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="editar-monitor-rol">Rol al que se enviará *</label>
                    <select id="editar-monitor-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-monitor-tipo">Tipo *</label>
                    <select id="editar-monitor-tipo" name="tipo" required>
                        <option value="">Selecciona un tipo</option>
                        <?php foreach ($tiposMonitor as $tipoClave => $tipoNombre): ?>
                        <option value="<?= htmlspecialchars($tipoClave) ?>"><?= htmlspecialchars($tipoNombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-monitor-semestre1">Monitores semestre I</label>
                    <input type="number" id="editar-monitor-semestre1" name="monitores_semestre1" min="0" step="1" value="0">
                </div>

                <div class="campo">
                    <label for="editar-monitor-semestre2">Monitores semestre II</label>
                    <input type="number" id="editar-monitor-semestre2" name="monitores_semestre2" min="0" step="1" value="0">
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

    <div id="modal-ops" class="modal-fondo<?= !empty($error) && $tab === 'ops' ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Agregar solicitud — OPS prestación de servicios</h2>
                <button type="button" id="boton-cerrar-modal-ops" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" class="form-necesidad">
                <input type="hidden" name="accion" value="crear_ops">
                <input type="hidden" name="tab" value="ops">

                <div class="campo">
                    <label for="ops_anio_presupuestal_id">Año presupuestal *</label>
                    <select id="ops_anio_presupuestal_id" name="anio_presupuestal_id" required>
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

                <?php $idPrefijoProyecto = ''; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

                <div class="campo">
                    <label for="ops_dependencia_buscador">Dependencia académico/administrativa *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia';
                    $idBaseDependenciaOverride = 'ops_dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'ops_rol_destinatario_id';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="ops_rol_destinatario_id">Rol al que se enviará *</label>
                    <select id="ops_rol_destinatario_id" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                    <label for="ops_perfil">Perfil *</label>
                    <select id="ops_perfil" name="perfil" required>
                        <option value="">Selecciona un perfil</option>
                        <?php foreach ($perfilesOps as $perfilClave => $perfilNombre): ?>
                        <option value="<?= htmlspecialchars($perfilClave) ?>"><?= htmlspecialchars($perfilNombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="ops_valor">Valor *</label>
                    <input type="number" id="ops_valor" name="valor" min="0" step="0.01" value="0" required>
                </div>

                <div class="campo">
                    <label for="ops_cantidad">Cantidad *</label>
                    <input type="number" id="ops_cantidad" name="cantidad" min="1" step="1" value="1" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="ops_observaciones">Observaciones</label>
                    <input type="text" id="ops_observaciones" name="observaciones" placeholder="Opcional">
                </div>

                <button type="submit" class="boton-enviar">Registrar solicitud</button>
            </form>
        </div>
    </div>

    <div id="modal-editar-ops" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar solicitud — OPS prestación de servicios</h2>
                <button type="button" id="boton-cerrar-modal-editar-ops" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar_ops">
                <input type="hidden" name="tab" value="ops">
                <input type="hidden" name="id" id="editar-ops-id" value="">

                <div class="campo">
                    <label for="editar-ops-anio">Año presupuestal *</label>
                    <select id="editar-ops-anio" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-ops-sede">Sede *</label>
                    <select id="editar-ops-sede" name="sede_id" required>
                        <option value="">Selecciona una sede</option>
                        <?php foreach ($sedes as $sede): ?>
                        <option value="<?= (int) $sede['id'] ?>"><?= htmlspecialchars($sede['codigo'] . ' - ' . $sede['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php $idPrefijoProyecto = 'editar-ops-'; $proyectosPdi = $proyectos; require __DIR__ . '/../parciales/selector-proyecto-pdi.php'; ?>

                <div class="campo">
                    <label for="editar-ops-dependencia_buscador">Dependencia académico/administrativa *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia';
                    $idBaseDependenciaOverride = 'editar-ops-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'editar-ops-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="editar-ops-rol">Rol al que se enviará *</label>
                    <select id="editar-ops-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label for="editar-ops-rubro_buscador">Rubro *</label>
                    <div class="selector-buscable" id="editar-ops-selector-rubro">
                        <input type="text" id="editar-ops-rubro_buscador" class="selector-buscable-input" placeholder="Buscar rubro..." autocomplete="off">
                        <input type="hidden" name="rubro_id" id="editar-ops-rubro_id">
                        <div class="selector-buscable-lista" id="editar-ops-rubro_lista">
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
                    <label for="editar-ops-perfil">Perfil *</label>
                    <select id="editar-ops-perfil" name="perfil" required>
                        <option value="">Selecciona un perfil</option>
                        <?php foreach ($perfilesOps as $perfilClave => $perfilNombre): ?>
                        <option value="<?= htmlspecialchars($perfilClave) ?>"><?= htmlspecialchars($perfilNombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-ops-valor">Valor *</label>
                    <input type="number" id="editar-ops-valor" name="valor" min="0" step="0.01" value="0" required>
                </div>

                <div class="campo">
                    <label for="editar-ops-cantidad">Cantidad *</label>
                    <input type="number" id="editar-ops-cantidad" name="cantidad" min="1" step="1" value="1" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="editar-ops-observaciones">Observaciones</label>
                    <input type="text" id="editar-ops-observaciones" name="observaciones" placeholder="Opcional">
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

    <div id="modal-peticion" class="modal-fondo<?= !empty($error) && $tab === 'otros' ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Agregar solicitud — Petición</h2>
                <button type="button" id="boton-cerrar-modal-peticion" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" class="form-necesidad">
                <input type="hidden" name="accion" value="crear_peticion">
                <input type="hidden" name="tab" value="otros">

                <div class="campo">
                    <label for="peticion_anio_presupuestal_id">Año presupuestal *</label>
                    <select id="peticion_anio_presupuestal_id" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>" <?= $anioSeleccionadoId === (int) $anioOpcion['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label for="peticion_concepto">Concepto *</label>
                    <input type="text" id="peticion_concepto" name="concepto" placeholder="Diligenciar" required>
                </div>

                <div class="campo">
                    <label for="peticion_rol_destinatario_id">Rol al que se enviará *</label>
                    <select id="peticion_rol_destinatario_id" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="peticion_semestre1">Semestre 1</label>
                    <input type="number" id="peticion_semestre1" name="semestre1" min="0" step="1" value="0">
                </div>

                <div class="campo">
                    <label for="peticion_valor_s1">Valor S1</label>
                    <input type="number" id="peticion_valor_s1" name="valor_s1" min="0" step="0.01" value="0">
                </div>

                <div class="campo">
                    <label for="peticion_semestre2">Semestre 2</label>
                    <input type="number" id="peticion_semestre2" name="semestre2" min="0" step="1" value="0">
                </div>

                <div class="campo">
                    <label for="peticion_valor_s2">Valor S2</label>
                    <input type="number" id="peticion_valor_s2" name="valor_s2" min="0" step="0.01" value="0">
                </div>

                <button type="submit" class="boton-enviar">Registrar solicitud</button>
            </form>
        </div>
    </div>

    <div id="modal-editar-peticion" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar solicitud — Petición</h2>
                <button type="button" id="boton-cerrar-modal-editar-peticion" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=solicitudes" class="form-necesidad">
                <input type="hidden" name="accion" value="actualizar_peticion">
                <input type="hidden" name="tab" value="otros">
                <input type="hidden" name="id" id="editar-peticion-id" value="">

                <div class="campo">
                    <label for="editar-peticion-anio">Año presupuestal *</label>
                    <select id="editar-peticion-anio" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label for="editar-peticion-concepto">Concepto *</label>
                    <input type="text" id="editar-peticion-concepto" name="concepto" placeholder="Diligenciar" required>
                </div>

                <div class="campo">
                    <label for="editar-peticion-rol">Rol al que se enviará *</label>
                    <select id="editar-peticion-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="editar-peticion-semestre1">Semestre 1</label>
                    <input type="number" id="editar-peticion-semestre1" name="semestre1" min="0" step="1" value="0">
                </div>

                <div class="campo">
                    <label for="editar-peticion-valor-s1">Valor S1</label>
                    <input type="number" id="editar-peticion-valor-s1" name="valor_s1" min="0" step="0.01" value="0">
                </div>

                <div class="campo">
                    <label for="editar-peticion-semestre2">Semestre 2</label>
                    <input type="number" id="editar-peticion-semestre2" name="semestre2" min="0" step="1" value="0">
                </div>

                <div class="campo">
                    <label for="editar-peticion-valor-s2">Valor S2</label>
                    <input type="number" id="editar-peticion-valor-s2" name="valor_s2" min="0" step="0.01" value="0">
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>


    <?php endif; ?>

    <div id="modal-elegir-destinatario" class="modal-fondo">
        <div class="modal-caja modal-caja-selector">
            <div class="modal-cabecera">
                <h2>¿A quién enviar?</h2>
                <button type="button" id="boton-cerrar-modal-elegir-destinatario" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Hay más de una persona con ese rol en esa dependencia. Elige a quién remitir la solicitud.</p>

            <div class="campo">
                <label for="elegir-destinatario-select">Destinatario *</label>
                <select id="elegir-destinatario-select"></select>
            </div>

            <button type="button" id="boton-confirmar-destinatario" class="boton-enviar">Confirmar y enviar</button>
        </div>
    </div>

    <script type="application/json" id="datos-roles-por-tipo"><?= json_encode($rolesPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
