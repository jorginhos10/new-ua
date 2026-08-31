<?php
$tituloPagina = 'Peticiones';
require __DIR__ . '/../parciales/encabezado.php';

$flechaModulo = '<svg class="tarjeta-modulo-flecha" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
?>

    <div class="tarjeta">
        <?php
        $barraTitulo = $bandeja !== null ? 'Peticiones — ' . $bandejas[$bandeja]['etiqueta'] : 'Peticiones recibidas';
        $barraBotonesSecundarios = [
            // [
            //     'id' => null,
            //     'icono' => 'exportar',
            //     'etiqueta' => 'Autogestión y perfil de proyectos',
            //     'tipo' => 'a',
            //     'href' => 'index.php?ruta=consolidado-autogestion',
            // ],
        ];
        if ($bandeja !== null) {
            $barraBotonesSecundarios[] = [
                'id' => null,
                'icono' => 'historial',
                'etiqueta' => 'Historial',
                'tipo' => 'a',
                'href' => 'index.php?ruta=peticiones-historial&bandeja=' . urlencode($bandeja),
            ];
        }
        $barraBotonPrincipal = null;
        $barraEstado = $bandeja !== null ? 'consulta' : 'creacion';
        $barraRutaVolver = $bandeja !== null ? 'index.php?ruta=peticiones' : null;
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if ($bandeja === null): ?>
        <p>Elige una bandeja para revisar lo que ya fue enviado o registrado.</p>
        <div class="box-items-config">
            <?php foreach ($bandejas as $claveBandeja => $infoBandeja): ?>
            <a href="index.php?ruta=peticiones&bandeja=<?= urlencode($claveBandeja) ?>" class="tarjeta-modulo">
                <div class="tarjeta-modulo-cabecera">
                    <h2><?= htmlspecialchars($infoBandeja['etiqueta']) ?></h2>
                    <?= $flechaModulo ?>
                </div>
                <p class="texto-atenuado"><?= (int) ($conteosBandejas[$claveBandeja] ?? 0) ?> pendiente(s) por revisar</p>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>

        <div class="cabecera-modulo">
            <div class="pestanas">
                <a href="index.php?ruta=peticiones&bandeja=<?= urlencode($bandeja) ?>&vista=pendientes" class="pestana<?= $vista === 'pendientes' ? ' activa' : '' ?>">Pendientes</a>
                <a href="index.php?ruta=peticiones&bandeja=<?= urlencode($bandeja) ?>&vista=consolidado" class="pestana<?= $vista === 'consolidado' ? ' activa' : '' ?>">Consolidado por tipo</a>
                <a href="index.php?ruta=peticiones&bandeja=<?= urlencode($bandeja) ?>&vista=archivar" class="pestana<?= $vista === 'archivar' ? ' activa' : '' ?>">Archivados</a>
                <a href="index.php?ruta=peticiones&bandeja=<?= urlencode($bandeja) ?>&vista=enviadas" class="pestana<?= $vista === 'enviadas' ? ' activa' : '' ?>">Enviadas</a>
            </div>
            <?php if ($vista === 'consolidado'): ?>
            <div class="grupo-acciones-encabezado" id="barra-acciones-consolidado" data-anio-id="<?= (int) $anioSeleccionadoId ?>" data-bandeja="<?= htmlspecialchars($bandeja) ?>">
                <button type="button" id="boton-consolidado-ver" class="boton-accion boton-accion-ver" disabled>Ver</button>
                <button type="button" id="boton-consolidado-editar" class="boton-accion boton-accion-editar" disabled>Editar</button>
                <button type="button" id="boton-consolidado-archivar" class="boton-accion boton-accion-editar" disabled>Archivar</button>
                <button type="button" id="boton-consolidado-redireccionar" class="boton-agregar" disabled>Enviar</button>
            </div>
            <?php endif; ?>
            <?php if ($vista === 'archivar'): ?>
            <div class="grupo-acciones-encabezado" id="barra-acciones-archivar" data-anio-id="<?= (int) $anioSeleccionadoId ?>" data-bandeja="<?= htmlspecialchars($bandeja) ?>">
                <button type="button" id="boton-archivado-ver" class="boton-accion boton-accion-ver" disabled>Ver</button>
                <button type="button" id="boton-archivado-duplicar" class="boton-accion boton-accion-editar" disabled>Duplicar</button>
                <button type="button" id="boton-archivado-consolidar" class="boton-accion boton-accion-enviar" disabled>Consolidar</button>
                <button type="button" id="boton-archivado-enviar" class="boton-agregar" disabled>Enviar</button>
            </div>
            <?php endif; ?>
            <?php if ($vista === 'enviadas'): ?>
            <div class="grupo-acciones-encabezado" id="barra-acciones-enviadas" data-anio-id="<?= (int) $anioSeleccionadoId ?>" data-bandeja="<?= htmlspecialchars($bandeja) ?>">
                <button type="button" id="boton-enviado-ver" class="boton-accion boton-accion-ver" disabled>Ver</button>
                <button type="button" id="boton-enviado-duplicar" class="boton-accion boton-accion-editar" disabled>Duplicar</button>
                <button type="button" id="boton-enviado-consolidar" class="boton-accion boton-accion-enviar" disabled>Consolidar</button>
                <button type="button" id="boton-enviado-enviar" class="boton-agregar" disabled>Enviar</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($esSuperAdminRaiz && ($vista === 'pendientes' || $vista === 'consolidado')): ?>
        <div class="enlace-modo-jerarquia">
            <?php if ($modoJerarquia): ?>
            <a href="index.php?ruta=peticiones&bandeja=<?= urlencode($bandeja) ?>&vista=<?= htmlspecialchars($vista) ?>&anio_id=<?= (int) $anioSeleccionadoId ?>">← Volver a mis pendientes por aceptar (por defecto)</a>
            <?php else: ?>
            <a href="index.php?ruta=peticiones&bandeja=<?= urlencode($bandeja) ?>&vista=<?= htmlspecialchars($vista) ?>&anio_id=<?= (int) $anioSeleccionadoId ?>&modo=jerarquia">Ver todo lo que tienes por debajo</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($aniosActivos)): ?>
            <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php endif; ?>

        <?php if (count($aniosActivos) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="peticiones">
                <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                <input type="hidden" name="vista" value="<?= htmlspecialchars($vista) ?>">
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

        <?php if ($vista === 'pendientes' && $modoJerarquia): ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th>Cantidad</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['tipo']) ?></td>
                        <td><?= htmlspecialchars($item['detalle']) ?></td>
                        <td><?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '—' ?></td>
                        <td><?= $item['valor'] !== null ? '$ ' . number_format($item['valor'], 2) : '—' ?></td>
                        <td>
                            <?php if ($item['estado_item'] === 'aprobada'): ?>
                            <span class="etiqueta-consolidado" title="Ya fue aceptado y consolidado">&#10003; Consolidado</span>
                            <?php elseif ($item['estado_item'] === 'archivada'): ?>
                            <span class="texto-atenuado">Archivado</span>
                            <?php elseif ($item['estado_item'] === 'redireccionada'): ?>
                            <span class="texto-atenuado">Redireccionado</span>
                            <?php else: ?>
                            <span class="texto-atenuado">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a href="<?= htmlspecialchars($item['ruta_ver']) ?>" class="boton-accion boton-accion-ver">Ver</a>
                                <?php if ($item['estado_item'] === 'pendiente'): ?>
                                <form method="POST" action="index.php?ruta=peticiones">
                                    <input type="hidden" name="accion" value="aprobar">
                                    <input type="hidden" name="vista" value="pendientes">
                                    <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                                    <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                                    <input type="hidden" name="modo" value="jerarquia">
                                    <input type="hidden" name="origen" value="<?= htmlspecialchars($item['origen']) ?>">
                                    <input type="hidden" name="origen_id" value="<?= (int) $item['origen_id'] ?>">
                                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($item['tipo']) ?>">
                                    <input type="hidden" name="detalle" value="<?= htmlspecialchars($item['detalle']) ?>">
                                    <input type="hidden" name="cantidad" value="<?= htmlspecialchars((string) $item['cantidad']) ?>">
                                    <input type="hidden" name="valor" value="<?= $item['valor'] !== null ? htmlspecialchars((string) $item['valor']) : '' ?>">
                                    <input type="hidden" name="ruta_ver" value="<?= htmlspecialchars($item['ruta_ver']) ?>">
                                    <input type="hidden" name="ruta_origen" value="<?= htmlspecialchars($item['ruta_origen']) ?>">
                                    <button type="submit" class="boton-accion boton-accion-enviar">Aprobar</button>
                                </form>
                                <form method="POST" action="index.php?ruta=peticiones">
                                    <input type="hidden" name="accion" value="archivar">
                                    <input type="hidden" name="vista" value="pendientes">
                                    <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                                    <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                                    <input type="hidden" name="modo" value="jerarquia">
                                    <input type="hidden" name="origen" value="<?= htmlspecialchars($item['origen']) ?>">
                                    <input type="hidden" name="origen_id" value="<?= (int) $item['origen_id'] ?>">
                                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($item['tipo']) ?>">
                                    <input type="hidden" name="detalle" value="<?= htmlspecialchars($item['detalle']) ?>">
                                    <input type="hidden" name="cantidad" value="<?= htmlspecialchars((string) $item['cantidad']) ?>">
                                    <input type="hidden" name="valor" value="<?= $item['valor'] !== null ? htmlspecialchars((string) $item['valor']) : '' ?>">
                                    <input type="hidden" name="ruta_ver" value="<?= htmlspecialchars($item['ruta_ver']) ?>">
                                    <button type="submit" class="boton-accion">Archivar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pendientes)): ?>
                    <tr>
                        <td colspan="6">No hay elementos por debajo de tu dependencia para este año.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($vista === 'pendientes'): ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th>Cantidad</th>
                        <th>Valor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $item): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($item['tipo']) ?>
                            <?php if (!empty($item['redireccionado'])): ?>
                            <span class="etiqueta-redireccionado" title="Este ítem fue redireccionado a tu dependencia">Redireccionado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['detalle']) ?></td>
                        <td><?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '—' ?></td>
                        <td><?= $item['valor'] !== null ? '$ ' . number_format($item['valor'], 2) : '—' ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a href="<?= htmlspecialchars($item['ruta_ver']) ?>" class="boton-accion boton-accion-ver">Ver</a>
                                <form method="POST" action="index.php?ruta=peticiones">
                                    <input type="hidden" name="accion" value="aprobar">
                                    <input type="hidden" name="vista" value="pendientes">
                                    <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                                    <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                                    <input type="hidden" name="origen" value="<?= htmlspecialchars($item['origen']) ?>">
                                    <input type="hidden" name="origen_id" value="<?= (int) $item['origen_id'] ?>">
                                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($item['tipo']) ?>">
                                    <input type="hidden" name="detalle" value="<?= htmlspecialchars($item['detalle']) ?>">
                                    <input type="hidden" name="cantidad" value="<?= htmlspecialchars((string) $item['cantidad']) ?>">
                                    <input type="hidden" name="valor" value="<?= $item['valor'] !== null ? htmlspecialchars((string) $item['valor']) : '' ?>">
                                    <input type="hidden" name="ruta_ver" value="<?= htmlspecialchars($item['ruta_ver']) ?>">
                                    <input type="hidden" name="ruta_origen" value="<?= htmlspecialchars($item['ruta_origen']) ?>">
                                    <button type="submit" class="boton-accion boton-accion-enviar"><?= htmlspecialchars($item['accion_aprobar']) ?></button>
                                </form>
                                <?php if (!empty($item['redireccionado'])): ?>
                                <form method="POST" action="index.php?ruta=peticiones" onsubmit="return confirm('¿Rechazar este ítem redireccionado y devolverlo a la dependencia de origen?');">
                                    <input type="hidden" name="accion" value="rechazar_redireccion">
                                    <input type="hidden" name="vista" value="pendientes">
                                    <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                                    <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                                    <input type="hidden" name="origen" value="<?= htmlspecialchars($item['origen']) ?>">
                                    <input type="hidden" name="origen_id" value="<?= (int) $item['origen_id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar"><?= htmlspecialchars($item['accion_rechazar']) ?></button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="boton-accion boton-accion-eliminar"><?= htmlspecialchars($item['accion_rechazar']) ?></button>
                                <?php endif; ?>
                                <form method="POST" action="index.php?ruta=peticiones">
                                    <input type="hidden" name="accion" value="archivar">
                                    <input type="hidden" name="vista" value="pendientes">
                                    <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                                    <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                                    <input type="hidden" name="origen" value="<?= htmlspecialchars($item['origen']) ?>">
                                    <input type="hidden" name="origen_id" value="<?= (int) $item['origen_id'] ?>">
                                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($item['tipo']) ?>">
                                    <input type="hidden" name="detalle" value="<?= htmlspecialchars($item['detalle']) ?>">
                                    <input type="hidden" name="cantidad" value="<?= htmlspecialchars((string) $item['cantidad']) ?>">
                                    <input type="hidden" name="valor" value="<?= $item['valor'] !== null ? htmlspecialchars((string) $item['valor']) : '' ?>">
                                    <input type="hidden" name="ruta_ver" value="<?= htmlspecialchars($item['ruta_ver']) ?>">
                                    <button type="submit" class="boton-accion">Archivar</button>
                                </form>
                                <form method="POST" action="index.php?ruta=peticiones" onsubmit="return confirm('¿Eliminar permanentemente el registro de origen de este ítem? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="accion" value="eliminar_pendiente">
                                    <input type="hidden" name="vista" value="pendientes">
                                    <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                                    <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                                    <input type="hidden" name="origen" value="<?= htmlspecialchars($item['origen']) ?>">
                                    <input type="hidden" name="origen_id" value="<?= (int) $item['origen_id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pendientes)): ?>
                    <tr>
                        <td colspan="5">No hay peticiones pendientes para este año.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($vista === 'consolidado' && $modoJerarquia): ?>
        <p class="texto-atenuado">Consolidado único: todo lo aprobado por debajo de tu dependencia, en una sola tabla, respetando la dependencia, el rubro, el techo y el valor de cada fila.</p>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Dependencia</th>
                        <th>Rubro</th>
                        <th>Techo</th>
                        <th>Valor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consolidadoUnificado as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['tipo']) ?></td>
                        <td><?= $fila['dependencia'] !== null ? htmlspecialchars($fila['dependencia']) : '—' ?></td>
                        <td><?= $fila['rubro'] !== null ? htmlspecialchars($fila['rubro']) : '—' ?></td>
                        <td><?= $fila['techo'] !== null ? '$ ' . number_format($fila['techo'], 2) : '—' ?></td>
                        <td><?= $fila['valor'] !== null ? '$ ' . number_format($fila['valor'], 2) : '—' ?></td>
                        <td class="celda-acciones">
                            <a href="<?= htmlspecialchars($fila['ruta_ver']) ?>" class="boton-accion boton-accion-ver">Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($consolidadoUnificado)): ?>
                    <tr>
                        <td colspan="6">Todavía no hay peticiones consolidadas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($vista === 'consolidado'): ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkbox-consolidado-todos" <?= empty($consolidado) ? 'disabled' : '' ?>></th>
                        <th>Tipo</th>
                        <th>Cantidad incorporada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consolidado as $grupo): ?>
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="checkbox-consolidado"
                                data-tipo="<?= htmlspecialchars($grupo['tipo']) ?>"
                                data-anio-id="<?= (int) $anioSeleccionadoId ?>"
                                data-items="<?= htmlspecialchars(json_encode($grupo['items'])) ?>"
                                data-puede-editar="<?= $grupo['puede_editar'] ? '1' : '0' ?>"
                                data-redireccionado="<?= !empty($tiposRedireccionados[$grupo['tipo']]) ? '1' : '0' ?>"
                            >
                        </td>
                        <td><?= htmlspecialchars($grupo['tipo']) ?></td>
                        <td><?= (int) $grupo['cantidad'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($consolidado)): ?>
                    <tr>
                        <td colspan="3">Todavía no hay peticiones consolidadas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($vista === 'archivar'): ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkbox-archivado-todos" <?= empty($archivados) ? 'disabled' : '' ?>></th>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th>Cantidad</th>
                        <th>Valor</th>
                        <th>Archivada</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archivados as $item): ?>
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="checkbox-archivado"
                                data-origen="<?= htmlspecialchars($item['origen']) ?>"
                                data-origen-id="<?= (int) $item['origen_id'] ?>"
                                data-tipo="<?= htmlspecialchars($item['tipo']) ?>"
                                data-detalle="<?= htmlspecialchars($item['detalle']) ?>"
                                data-cantidad="<?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '' ?>"
                                data-valor="<?= $item['valor'] !== null ? (float) $item['valor'] : '' ?>"
                                data-ruta-ver="<?= htmlspecialchars($item['ruta_ver']) ?>"
                            >
                        </td>
                        <td><?= htmlspecialchars($item['tipo']) ?></td>
                        <td><?= htmlspecialchars($item['detalle']) ?></td>
                        <td><?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '—' ?></td>
                        <td><?= $item['valor'] !== null ? '$ ' . number_format((float) $item['valor'], 2) : '—' ?></td>
                        <td><?= htmlspecialchars($item['archivado_en']) ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a href="<?= htmlspecialchars($item['ruta_ver']) ?>" class="boton-accion boton-accion-ver">Ver</a>
                                <form method="POST" action="index.php?ruta=peticiones">
                                    <input type="hidden" name="accion" value="restaurar">
                                    <input type="hidden" name="vista" value="archivar">
                                    <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                                    <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-editar">Restaurar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($archivados)): ?>
                    <tr>
                        <td colspan="7">No hay peticiones archivadas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="texto-atenuado">Lo que tu dependencia (o sus hijas) ya envió, con su estado actual.</p>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkbox-enviado-todos" <?= empty($enviadas) ? 'disabled' : '' ?>></th>
                        <th>Tipo</th>
                        <th>Enviado a</th>
                        <th>Estado</th>
                        <th>Cantidad</th>
                        <th>Valor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enviadas as $item): ?>
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="checkbox-enviado"
                                data-origen="<?= htmlspecialchars($item['origen']) ?>"
                                data-origen-id="<?= (int) $item['origen_id'] ?>"
                                data-tipo="<?= htmlspecialchars($item['tipo']) ?>"
                                data-detalle="<?= htmlspecialchars($item['detalle']) ?>"
                                data-cantidad="<?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '' ?>"
                                data-valor="<?= $item['valor'] !== null ? (float) $item['valor'] : '' ?>"
                                data-ruta-ver="<?= htmlspecialchars($item['ruta_ver']) ?>"
                                data-accion-actual="<?= $item['accion_actual'] !== null ? htmlspecialchars($item['accion_actual']) : '' ?>"
                            >
                        </td>
                        <td><?= htmlspecialchars($item['tipo']) ?></td>
                        <td><?= htmlspecialchars($item['detalle']) ?></td>
                        <td><?= htmlspecialchars($item['estado_enviada']) ?></td>
                        <td><?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '—' ?></td>
                        <td><?= $item['valor'] !== null ? '$ ' . number_format((float) $item['valor'], 2) : '—' ?></td>
                        <td class="celda-acciones">
                            <a href="<?= htmlspecialchars($item['ruta_ver']) ?>" class="boton-accion boton-accion-ver">Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($enviadas)): ?>
                    <tr>
                        <td colspan="7">No has enviado nada todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="modal-ver-consolidado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Detalle de <span id="ver-consolidado-tipo"></span></h2>
                <button type="button" id="boton-cerrar-modal-ver-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="enlace-modo-jerarquia">
                <a id="enlace-ver-consolidado-completo" href="#">Ver detalle completo (dependencias, techos y rubros) y exportar a Excel &rarr;</a>
            </p>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Cantidad</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="ver-consolidado-cuerpo"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-editar-consolidado" class="modal-fondo" data-anio-id="<?= (int) $anioSeleccionadoId ?>" data-bandeja="<?= htmlspecialchars($bandeja) ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Editar <span id="editar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-editar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Cada ítem se edita en el formulario real de su módulo de origen, con todos sus campos. Solo puedes editar los ítems que hoy son tuyos (o cualquiera si eres superadmin).</p>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="editar-consolidado-cuerpo"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-redireccionar-consolidado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Redireccionar <span id="redireccionar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-redireccionar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=peticiones" class="form-necesidad form-confirmar-envio" data-campo-dependencia="redireccionar-consolidado-dependencia" data-campo-rol="redireccionar-consolidado-rol">
                <input type="hidden" name="accion" value="redireccionar_consolidado">
                <input type="hidden" name="vista" value="consolidado">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                <div id="redireccionar-consolidado-campos-items"></div>

                <div class="campo">
                    <label for="redireccionar-consolidado-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'redireccionar-consolidado-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'redireccionar-consolidado-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="redireccionar-consolidado-rol">Rol *</label>
                    <select id="redireccionar-consolidado-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo" style="display:none;">
                    <label for="redireccionar-consolidado-destinatario">¿A quién exactamente? *</label>
                    <select
                        id="redireccionar-consolidado-destinatario"
                        name="usuario_destinatario_id"
                        class="selector-destinatario"
                        data-campo-dependencia="redireccionar-consolidado-dependencia"
                        data-campo-rol="redireccionar-consolidado-rol"
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                </div>

                <button type="submit" class="boton-enviar">Redireccionar</button>
            </form>
        </div>
    </div>

    <div id="modal-enviar-archivado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Enviar <span id="enviar-archivado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-enviar-archivado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=peticiones" class="form-necesidad form-confirmar-envio" data-campo-dependencia="enviar-archivado-dependencia" data-campo-rol="enviar-archivado-rol">
                <input type="hidden" name="accion" value="enviar_archivado">
                <input type="hidden" name="vista" value="archivar">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                <div id="enviar-archivado-campos-items"></div>

                <div class="campo">
                    <label for="enviar-archivado-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'enviar-archivado-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'enviar-archivado-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-archivado-rol">Rol *</label>
                    <select id="enviar-archivado-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo" style="display:none;">
                    <label for="enviar-archivado-destinatario">¿A quién exactamente? *</label>
                    <select
                        id="enviar-archivado-destinatario"
                        name="usuario_destinatario_id"
                        class="selector-destinatario"
                        data-campo-dependencia="enviar-archivado-dependencia"
                        data-campo-rol="enviar-archivado-rol"
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                </div>

                <button type="submit" class="boton-enviar">Enviar</button>
            </form>
        </div>
    </div>

    <div id="modal-ver-archivado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Detalle de los ítems seleccionados</h2>
                <button type="button" id="boton-cerrar-modal-ver-archivado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Cantidad</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="ver-archivado-cuerpo"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-ver-enviado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Detalle de los ítems seleccionados</h2>
                <button type="button" id="boton-cerrar-modal-ver-enviado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Cantidad</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="ver-enviado-cuerpo"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-enviar-enviado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Enviar <span id="enviar-enviado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-enviar-enviado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=peticiones" class="form-necesidad form-confirmar-envio" data-campo-dependencia="enviar-enviado-dependencia" data-campo-rol="enviar-enviado-rol">
                <input type="hidden" name="accion" value="enviar_enviado">
                <input type="hidden" name="vista" value="enviadas">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="bandeja" value="<?= htmlspecialchars($bandeja) ?>">
                <div id="enviar-enviado-campos-items"></div>

                <div class="campo">
                    <label for="enviar-enviado-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'enviar-enviado-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    $dependenciaDataSelectRol = 'enviar-enviado-rol';
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-enviado-rol">Rol *</label>
                    <select id="enviar-enviado-rol" name="rol_destinatario_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rolOpcion): ?>
                        <option value="<?= (int) $rolOpcion['id'] ?>"><?= htmlspecialchars($rolOpcion['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo" style="display:none;">
                    <label for="enviar-enviado-destinatario">¿A quién exactamente? *</label>
                    <select
                        id="enviar-enviado-destinatario"
                        name="usuario_destinatario_id"
                        class="selector-destinatario"
                        data-campo-dependencia="enviar-enviado-dependencia"
                        data-campo-rol="enviar-enviado-rol"
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                </div>

                <button type="submit" class="boton-enviar">Enviar</button>
            </form>
        </div>
    </div>

    <script type="application/json" id="datos-roles-por-tipo"><?= json_encode($rolesPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
