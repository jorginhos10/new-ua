<?php
$tituloPagina = 'Peticiones';
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <?php
        // El filtro de Archivados/Enviadas vive en la fila del título (espacio que antes quedaba en
        // blanco a la derecha de "Peticiones recibidas"), no en una fila propia — ver
        // $barraAccionesExtra en barra-modulo.php.
        $barraAccionesExtra = null;
        $iconoLupaFiltro = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';
        if ($vista === 'archivar' && !empty($archivadosPorGrupo)) {
            $barraAccionesExtra = '<div class="campo-filtro-inline">' . $iconoLupaFiltro
                . '<input type="text" id="filtro-archivado" placeholder="Filtrar tarjetas…" autocomplete="off"></div>';
        } elseif ($vista === 'enviadas' && !empty($enviadasPorGrupo)) {
            $barraAccionesExtra = '<div class="campo-filtro-inline">' . $iconoLupaFiltro
                . '<input type="text" id="filtro-enviado" placeholder="Filtrar tarjetas…" autocomplete="off"></div>';
        }

        $barraTitulo = 'Peticiones recibidas';
        $barraBotonesSecundarios = [];
        $barraBotonPrincipal = null;
        $barraEstado = 'creacion';
        $barraRutaVolver = null;
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <div class="cabecera-modulo">
            <div class="pestanas">
                <a href="index.php?ruta=peticiones&vista=pendientes" class="pestana<?= $vista === 'pendientes' ? ' activa' : '' ?>">Pendientes</a>
                <a href="index.php?ruta=peticiones&vista=consolidado" class="pestana<?= $vista === 'consolidado' ? ' activa' : '' ?>">Consolidado por tipo</a>
                <a href="index.php?ruta=peticiones&vista=archivar" class="pestana<?= $vista === 'archivar' ? ' activa' : '' ?>">Archivados</a>
                <a href="index.php?ruta=peticiones&vista=enviadas" class="pestana<?= $vista === 'enviadas' ? ' activa' : '' ?>" title="Lo que tu dependencia (o sus hijas) ya envió, con su estado actual.">Enviadas</a>
            </div>
            <?php if ($vista === 'pendientes'): ?>
            <div class="grupo-acciones-encabezado" id="barra-acciones-pendientes" data-anio-id="<?= (int) $anioSeleccionadoId ?>">
                <button type="button" id="boton-pendientes-ver" class="boton-accion boton-accion-ver" disabled>Ver</button>
                <button type="button" id="boton-pendientes-aprobar" class="boton-accion boton-accion-enviar" disabled>Aceptar seleccionados</button>
                <button type="button" id="boton-pendientes-archivar" class="boton-accion boton-accion-editar" disabled>Archivar seleccionados</button>
                <button type="button" id="boton-pendientes-enviar" class="boton-agregar" disabled>Enviar</button>
                <button type="button" id="boton-pendientes-eliminar" class="boton-accion boton-accion-eliminar" disabled>Eliminar seleccionados</button>
            </div>
            <?php endif; ?>
            <?php if ($vista === 'consolidado'): ?>
            <div class="grupo-acciones-encabezado" id="barra-acciones-consolidado" data-anio-id="<?= (int) $anioSeleccionadoId ?>">
                <button type="button" id="boton-consolidado-ver" class="boton-accion boton-accion-ver" disabled>Ver</button>
                <button type="button" id="boton-consolidado-desconsolidar" class="boton-accion boton-accion-eliminar" disabled>Desconsolidar</button>
                <button type="button" id="boton-consolidado-archivar" class="boton-accion boton-accion-editar" disabled>Archivar</button>
                <button type="button" id="boton-consolidado-redireccionar" class="boton-agregar" disabled>Enviar</button>
            </div>
            <?php endif; ?>
            <?php if ($vista === 'archivar'): ?>
            <div class="grupo-acciones-encabezado" id="barra-acciones-archivar" data-anio-id="<?= (int) $anioSeleccionadoId ?>">
                <button type="button" id="boton-archivado-ver" class="boton-icono-accion" data-tooltip="Ver" title="Ver" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
                <button type="button" id="boton-archivado-restaurar" class="boton-icono-accion" data-tooltip="Restaurar" title="Restaurar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                </button>
                <?php if (!$esSuperAdminRaiz): ?>
                <button type="button" id="boton-archivado-expediente" class="boton-icono-accion" data-tooltip="Mandar a Expediente" title="Mandar a Expediente" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path><path d="M10 12h4"></path></svg>
                </button>
                <?php endif; ?>
                <button type="button" id="boton-archivado-duplicar" class="boton-icono-accion" data-tooltip="Duplicar" title="Duplicar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                </button>
                <button type="button" id="boton-archivado-consolidar" class="boton-icono-accion" data-tooltip="Consolidar" title="Consolidar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
                <button type="button" id="boton-archivado-enviar" class="boton-icono-accion" data-tooltip="Enviar" title="Enviar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
                <button type="button" id="boton-archivado-historial" class="boton-icono-accion" data-tooltip="Ver historial" title="Ver historial" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </button>
            </div>
            <?php endif; ?>
            <?php if ($vista === 'enviadas'): ?>
            <div class="grupo-acciones-encabezado" id="barra-acciones-enviadas" data-anio-id="<?= (int) $anioSeleccionadoId ?>">
                <button type="button" id="boton-enviado-ver" class="boton-icono-accion" data-tooltip="Ver" title="Ver" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
                <button type="button" id="boton-enviado-duplicar" class="boton-icono-accion" data-tooltip="Duplicar" title="Duplicar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                </button>
                <button type="button" id="boton-enviado-consolidar" class="boton-icono-accion" data-tooltip="Consolidar" title="Consolidar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
                <button type="button" id="boton-enviado-enviar" class="boton-icono-accion" data-tooltip="Enviar" title="Enviar" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
                <button type="button" id="boton-enviado-historial" class="boton-icono-accion" data-tooltip="Ver historial" title="Ver historial" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($modoJerarquia && ($vista === 'pendientes' || $vista === 'consolidado')): ?>
        <?php /* Auditando: viendo todo lo pendiente de tu árbol de dependencias (activado en el
                 interruptor "Auditar" del encabezado), no solo lo dirigido a ti. Insignia compacta
                 en vez de un <p> completo, para no quitarle espacio a la tabla. */ ?>
        <span class="badge-rol badge-borrador" tabindex="0" title="Auditando: viendo todo lo pendiente de tu árbol de dependencias (activado en el interruptor &quot;Auditar&quot; del encabezado), no solo lo dirigido a ti.">Auditando</span>
        <?php endif; ?>

        <?php if (empty($aniosActivos)): ?>
            <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php endif; ?>

        <?php if (count($aniosActivos) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="peticiones">
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
                        <th><input type="checkbox" id="checkbox-pendientes-todos" <?= empty(array_filter($pendientes, static fn (array $i): bool => $i['estado_item'] === 'pendiente')) ? 'disabled' : '' ?>></th>
                        <th class="th-ordenable">Tipo</th>
                        <th class="th-ordenable">Origen</th>
                        <th class="th-ordenable columna-derecha">Cantidad</th>
                        <th class="th-ordenable columna-derecha">Valor</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['estado_item'] === 'pendiente'): ?>
                            <input
                                type="checkbox"
                                class="checkbox-pendiente"
                                data-origen="<?= htmlspecialchars($item['origen']) ?>"
                                data-origen-id="<?= (int) $item['origen_id'] ?>"
                                data-tipo="<?= htmlspecialchars($item['tipo']) ?>"
                                data-detalle="<?= htmlspecialchars($item['detalle']) ?>"
                                data-cantidad="<?= $item['cantidad'] !== null ? htmlspecialchars((string) $item['cantidad']) : '' ?>"
                                data-valor="<?= $item['valor'] !== null ? htmlspecialchars((string) $item['valor']) : '' ?>"
                                data-ruta-ver="<?= htmlspecialchars($item['ruta_ver']) ?>"
                                data-ruta-origen="<?= htmlspecialchars($item['ruta_origen']) ?>"
                            >
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['tipo']) ?></td>
                        <td><?= htmlspecialchars($item['detalle']) ?></td>
                        <td class="columna-derecha" data-orden="<?= $item['cantidad'] !== null ? (float) $item['cantidad'] : 0 ?>"><?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '—' ?></td>
                        <td class="columna-derecha" data-orden="<?= $item['valor'] ?? 0 ?>"><?= $item['valor'] !== null ? '$ ' . number_format($item['valor'], 2, ',', '.') : '—' ?></td>
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
                        <td colspan="7">No hay elementos por debajo de tu dependencia para este año.</td>
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
                        <th><input type="checkbox" id="checkbox-pendientes-todos" <?= empty($pendientes) ? 'disabled' : '' ?>></th>
                        <th class="th-ordenable">Tipo</th>
                        <th class="th-ordenable">Origen</th>
                        <th class="th-ordenable columna-derecha">Cantidad</th>
                        <th class="th-ordenable columna-derecha">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $item): ?>
                    <?php $esGrupo = isset($item['items']); ?>
                    <tr>
                        <td>
                            <?php if ($esGrupo): ?>
                            <input type="checkbox" class="checkbox-pendiente" data-items="<?= htmlspecialchars(json_encode($item['items'])) ?>" data-ruta-ver="<?= htmlspecialchars($item['ruta_ver']) ?>">
                            <?php else: ?>
                            <input
                                type="checkbox"
                                class="checkbox-pendiente"
                                data-origen="<?= htmlspecialchars($item['origen']) ?>"
                                data-origen-id="<?= (int) $item['origen_id'] ?>"
                                data-tipo="<?= htmlspecialchars($item['tipo']) ?>"
                                data-detalle="<?= htmlspecialchars($item['detalle']) ?>"
                                data-cantidad="<?= $item['cantidad'] !== null ? htmlspecialchars((string) $item['cantidad']) : '' ?>"
                                data-valor="<?= $item['valor'] !== null ? htmlspecialchars((string) $item['valor']) : '' ?>"
                                data-ruta-ver="<?= htmlspecialchars($item['ruta_ver']) ?>"
                                data-ruta-origen="<?= htmlspecialchars($item['ruta_origen']) ?>"
                            >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($item['tipo']) ?>
                            <?php if (!empty($item['redireccionado'])): ?>
                            <span class="etiqueta-redireccionado" title="Este ítem fue redireccionado a tu dependencia">Redireccionado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['detalle']) ?></td>
                        <td class="columna-derecha" data-orden="<?= $item['cantidad'] !== null ? (float) $item['cantidad'] : 0 ?>"><?= $item['cantidad'] !== null ? htmlspecialchars($item['cantidad']) : '—' ?></td>
                        <td class="columna-derecha" data-orden="<?= $item['valor'] ?? 0 ?>"><?= $item['valor'] !== null ? '$ ' . number_format($item['valor'], 2, ',', '.') : '—' ?></td>
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
                        <td><?= $fila['techo'] !== null ? '$ ' . number_format($fila['techo'], 2, ',', '.') : '—' ?></td>
                        <td><?= $fila['valor'] !== null ? '$ ' . number_format($fila['valor'], 2, ',', '.') : '—' ?></td>
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
                                data-vista-agrupada="1"
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
            <table class="tabla-usuarios" id="tabla-archivado-agrupado">
                <colgroup>
                    <col style="width: 34px;">
                    <col style="width: 20%;">
                    <col style="width: 28%;">
                    <col style="width: 25%;">
                    <col style="width: 17%;">
                    <col style="width: 10%;">
                </colgroup>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkbox-archivado-todos" <?= empty($archivadosPorGrupo) ? 'disabled' : '' ?>></th>
                        <th>Tipo</th>
                        <th>Dependencia</th>
                        <th>Remitente</th>
                        <th>Estado</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archivadosPorGrupo as $grupo): ?>
                    <tr class="fila-filtrable" data-texto-filtro="<?= htmlspecialchars(mb_strtolower($grupo['tipo'] . ' ' . $grupo['dependencia'] . ' ' . $grupo['remitente'] . ' ' . ($grupo['estado'] === 'expediente' ? 'en expediente' : 'archivado'))) ?>">
                        <td>
                            <input
                                type="checkbox"
                                class="checkbox-archivado"
                                data-tipo="<?= htmlspecialchars($grupo['tipo']) ?>"
                                data-anio-id="<?= (int) $anioSeleccionadoId ?>"
                                data-items="<?= htmlspecialchars(json_encode($grupo['items'])) ?>"
                                data-ruta-ver="<?= htmlspecialchars($grupo['ruta_ver']) ?>"
                                data-estado="<?= htmlspecialchars($grupo['estado']) ?>"
                                data-vista-agrupada="1"
                            >
                        </td>
                        <td class="celda-truncar" title="<?= htmlspecialchars($grupo['tipo']) ?>"><?= htmlspecialchars($grupo['tipo']) ?></td>
                        <td class="celda-truncar" title="<?= htmlspecialchars($grupo['dependencia']) ?>"><?= htmlspecialchars($grupo['dependencia']) ?></td>
                        <td class="celda-truncar" title="<?= htmlspecialchars($grupo['remitente']) ?>"><?= htmlspecialchars($grupo['remitente']) ?></td>
                        <td>
                            <?php if ($grupo['estado'] === 'expediente'): ?>
                            <span class="badge-rol badge-expediente">En Expediente</span>
                            <?php else: ?>
                            <span class="badge-rol badge-archivado">Archivado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $grupo['cantidad'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($archivadosPorGrupo)): ?>
                    <tr>
                        <td colspan="6">No hay peticiones archivadas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <?php /* "Lo que tu dependencia (o sus hijas) ya envió, con su estado actual." — texto movido
                 al title de la pestaña "Enviadas" (arriba) para no quitarle espacio a la tabla. */ ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios" id="tabla-enviado-agrupado">
                <colgroup>
                    <col style="width: 34px;">
                    <col style="width: 24%;">
                    <col style="width: 34%;">
                    <col style="width: 30%;">
                    <col style="width: 12%;">
                </colgroup>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkbox-enviado-todos" <?= empty($enviadasPorGrupo) ? 'disabled' : '' ?>></th>
                        <th>Tipo</th>
                        <th>Enviado a</th>
                        <th>Remitente</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enviadasPorGrupo as $grupo): ?>
                    <tr class="fila-filtrable" data-texto-filtro="<?= htmlspecialchars(mb_strtolower($grupo['tipo'] . ' ' . $grupo['dependencia'] . ' ' . $grupo['remitente'])) ?>">
                        <td>
                            <input
                                type="checkbox"
                                class="checkbox-enviado"
                                data-tipo="<?= htmlspecialchars($grupo['tipo']) ?>"
                                data-anio-id="<?= (int) $anioSeleccionadoId ?>"
                                data-items="<?= htmlspecialchars(json_encode($grupo['items'])) ?>"
                                data-ruta-ver="<?= htmlspecialchars($grupo['ruta_ver']) ?>"
                                data-vista-agrupada="1"
                            >
                        </td>
                        <td class="celda-truncar" title="<?= htmlspecialchars($grupo['tipo']) ?>"><?= htmlspecialchars($grupo['tipo']) ?></td>
                        <td class="celda-truncar" title="<?= htmlspecialchars($grupo['dependencia']) ?>"><?= htmlspecialchars($grupo['dependencia']) ?></td>
                        <td class="celda-truncar" title="<?= htmlspecialchars($grupo['remitente']) ?>"><?= htmlspecialchars($grupo['remitente']) ?></td>
                        <td><?= (int) $grupo['cantidad'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($enviadasPorGrupo)): ?>
                    <tr>
                        <td colspan="5">No has enviado nada todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
                            <th class="th-ordenable" data-orden-inicial="asc">Tipo</th>
                            <th class="th-ordenable" data-orden-inicial="asc" data-orden-defecto>Detalle</th>
                            <th class="th-ordenable" data-orden-inicial="asc">Cantidad</th>
                            <th class="th-ordenable" data-orden-inicial="asc">Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="ver-consolidado-cuerpo"></tbody>
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
                <div id="redireccionar-consolidado-campos-items"></div>

                <div class="campo">
                    <label for="redireccionar-consolidado-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'redireccionar-consolidado-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="redireccionar-consolidado-destinatario">¿A quién se enviará? *</label>
                    <select
                        id="redireccionar-consolidado-destinatario"
                        class="selector-rol-destinatario"
                        data-campo-dependencia="redireccionar-consolidado-dependencia"
                        data-campo-rol-oculto="redireccionar-consolidado-rol"
                        data-campo-usuario-oculto="redireccionar-consolidado-usuario"
                        required
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                    <input type="hidden" id="redireccionar-consolidado-rol" name="rol_destinatario_id">
                    <input type="hidden" id="redireccionar-consolidado-usuario" name="usuario_destinatario_id">
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
                <div id="enviar-archivado-campos-items"></div>

                <div class="campo">
                    <label for="enviar-archivado-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'enviar-archivado-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-archivado-destinatario">¿A quién se enviará? *</label>
                    <select
                        id="enviar-archivado-destinatario"
                        class="selector-rol-destinatario"
                        data-campo-dependencia="enviar-archivado-dependencia"
                        data-campo-rol-oculto="enviar-archivado-rol"
                        data-campo-usuario-oculto="enviar-archivado-usuario"
                        required
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                    <input type="hidden" id="enviar-archivado-rol" name="rol_destinatario_id">
                    <input type="hidden" id="enviar-archivado-usuario" name="usuario_destinatario_id">
                </div>

                <button type="submit" class="boton-enviar">Enviar</button>
            </form>
        </div>
    </div>

    <div id="modal-enviar-pendientes" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Enviar seleccionados</h2>
                <button type="button" id="boton-cerrar-modal-enviar-pendientes" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=peticiones" class="form-necesidad form-confirmar-envio" data-campo-dependencia="enviar-pendientes-dependencia" data-campo-rol="enviar-pendientes-rol">
                <input type="hidden" name="accion" value="enviar_pendientes_grupo">
                <input type="hidden" name="vista" value="pendientes">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <div id="enviar-pendientes-campos-items"></div>

                <div class="campo">
                    <label for="enviar-pendientes-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'enviar-pendientes-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-pendientes-destinatario">¿A quién se enviará? *</label>
                    <select
                        id="enviar-pendientes-destinatario"
                        class="selector-rol-destinatario"
                        data-campo-dependencia="enviar-pendientes-dependencia"
                        data-campo-rol-oculto="enviar-pendientes-rol"
                        data-campo-usuario-oculto="enviar-pendientes-usuario"
                        required
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                    <input type="hidden" id="enviar-pendientes-rol" name="rol_destinatario_id">
                    <input type="hidden" id="enviar-pendientes-usuario" name="usuario_destinatario_id">
                </div>

                <button type="submit" class="boton-enviar">Enviar</button>
            </form>
        </div>
    </div>

    <div id="modal-ver-pendientes" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Detalle de los ítems seleccionados</h2>
                <button type="button" id="boton-cerrar-modal-ver-pendientes" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th class="th-ordenable" data-orden-inicial="asc">Tipo</th>
                            <th class="th-ordenable" data-orden-inicial="asc" data-orden-defecto>Detalle</th>
                            <th class="th-ordenable" data-orden-inicial="asc">Cantidad</th>
                            <th class="th-ordenable" data-orden-inicial="asc">Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="ver-pendientes-cuerpo"></tbody>
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
                <div id="enviar-enviado-campos-items"></div>

                <div class="campo">
                    <label for="enviar-enviado-dependencia_buscador">Dependencia *</label>
                    <?php
                    $idPrefijoDependencia = '';
                    $nombreCampoDependencia = 'dependencia_destino';
                    $idBaseDependenciaOverride = 'enviar-enviado-dependencia';
                    $dependenciasOpciones = $dependenciasSugeridas;
                    require __DIR__ . '/../parciales/selector-dependencia.php';
                    ?>
                </div>

                <div class="campo">
                    <label for="enviar-enviado-destinatario">¿A quién se enviará? *</label>
                    <select
                        id="enviar-enviado-destinatario"
                        class="selector-rol-destinatario"
                        data-campo-dependencia="enviar-enviado-dependencia"
                        data-campo-rol-oculto="enviar-enviado-rol"
                        data-campo-usuario-oculto="enviar-enviado-usuario"
                        required
                    >
                        <option value="">Selecciona a quién enviarlo</option>
                    </select>
                    <input type="hidden" id="enviar-enviado-rol" name="rol_destinatario_id">
                    <input type="hidden" id="enviar-enviado-usuario" name="usuario_destinatario_id">
                </div>

                <button type="submit" class="boton-enviar">Enviar</button>
            </form>
        </div>
    </div>

    <script type="application/json" id="datos-roles-por-tipo"><?= json_encode($rolesPorTipo, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
