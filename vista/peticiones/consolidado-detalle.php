<?php
$tituloPagina = 'Consolidado detallado';
$nombresMeses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];
require __DIR__ . '/../parciales/encabezado.php';

$parametrosExportar = ['ruta' => 'consolidado-detalle', 'exportar' => 'xlsx'];
if ($anioSeleccionadoId > 0) {
    $parametrosExportar['anio_id'] = $anioSeleccionadoId;
}
if ($tipoFiltro !== '') {
    $parametrosExportar['tipo'] = $tipoFiltro;
}
?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Consolidado detallado<?= $tipoFiltro !== '' ? ' — ' . htmlspecialchars($tipoFiltro) : '' ?></h1>
                <p class="texto-atenuado">Todo lo aprobado y consolidado<?= $anio !== null ? ' para ' . htmlspecialchars((string) $anio['anio']) : '' ?>, con el mismo detalle que la tabla de Gastos: dependencia, sede, línea, motor, proyecto, rubro, actividad, insumo y techo presupuestal.</p>
            </div>
            <div class="grupo-acciones-encabezado" id="barra-acciones-consolidado">
                <button type="button" id="boton-consolidado-ver" class="boton-accion boton-accion-ver" disabled>Ver</button>
                <button type="button" id="boton-consolidado-editar" class="boton-accion boton-accion-editar" disabled>Editar</button>
                <button type="button" id="boton-consolidado-redireccionar" class="boton-accion boton-accion-enviar" disabled>Redireccionar</button>
                <button type="button" id="boton-consolidado-duplicar" class="boton-agregar" disabled>Duplicar</button>
                <a href="index.php?<?= http_build_query($parametrosExportar) ?>" class="boton-accion boton-accion-enviar">Exportar Excel</a>
                <a href="index.php?ruta=peticiones&vista=consolidado<?= $anioSeleccionadoId > 0 ? '&anio_id=' . (int) $anioSeleccionadoId : '' ?>" class="boton-accion boton-accion-ver">&larr; Volver a Peticiones</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkbox-consolidado-todos" <?= empty($filas) ? 'disabled' : '' ?>></th>
                        <th>Tipo</th>
                        <th>Dependencia</th>
                        <th>Sede</th>
                        <th>Línea estratégica</th>
                        <th>Motor de desarrollo</th>
                        <th>Proyecto PDI</th>
                        <th>Objeto/Proyecto (PAA)</th>
                        <th>Actividad</th>
                        <th>Rubro</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Costo unitario</th>
                        <th>Valor total</th>
                        <th>Meses</th>
                        <th>Techo presupuestal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                    <?php
                    $mesesFila = $fila['meses'] !== '—' && $fila['meses'] !== ''
                        ? array_map(static fn ($mes) => $nombresMeses[(int) trim($mes)] ?? trim($mes), explode(',', $fila['meses']))
                        : [];
                    $itemJs = [
                        'origen' => $fila['origen'],
                        'origen_id' => $fila['origen_id'],
                        'tipo' => $fila['tipo'],
                        'detalle' => $fila['dependencia'],
                        'dependencia' => $fila['dependencia'],
                        'cantidad' => $fila['cantidad'],
                        'valor' => $fila['valor_total'],
                        'ruta_ver' => $fila['ruta_ver'],
                        'sede' => $fila['sede'],
                        'linea' => $fila['linea'],
                        'motor' => $fila['motor'],
                        'proyecto' => $fila['proyecto'],
                        'objeto_proyecto_paa' => $fila['objeto_proyecto_paa'],
                        'actividad' => $fila['actividad'],
                        'rubro' => $fila['rubro'],
                        'insumo' => $fila['insumo'],
                        'costo_unitario' => $fila['costo_unitario'],
                        'meses' => $fila['meses'],
                        'techo' => $fila['techo'],
                    ];
                    ?>
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="checkbox-consolidado"
                                data-tipo="<?= htmlspecialchars($fila['tipo']) ?>"
                                data-anio-id="<?= (int) $anioSeleccionadoId ?>"
                                data-items="<?= htmlspecialchars(json_encode([$itemJs])) ?>"
                                data-puede-editar="<?= !empty($fila['puede_editar']) ? '1' : '0' ?>"
                                data-redireccionado="0"
                            >
                        </td>
                        <td><?= htmlspecialchars($fila['tipo']) ?></td>
                        <td><?= $fila['dependencia'] !== null ? htmlspecialchars($fila['dependencia']) : '—' ?></td>
                        <td><?= htmlspecialchars($fila['sede']) ?></td>
                        <td><?= htmlspecialchars($fila['linea']) ?></td>
                        <td><?= htmlspecialchars($fila['motor']) ?></td>
                        <td><?= htmlspecialchars($fila['proyecto']) ?></td>
                        <td><?= htmlspecialchars($fila['objeto_proyecto_paa']) ?></td>
                        <td><?= htmlspecialchars($fila['actividad']) ?></td>
                        <td><?= htmlspecialchars($fila['rubro']) ?></td>
                        <td><?= htmlspecialchars($fila['insumo']) ?></td>
                        <td><?= $fila['cantidad'] !== null ? htmlspecialchars((string) $fila['cantidad']) : '—' ?></td>
                        <td><?= $fila['costo_unitario'] !== null ? number_format($fila['costo_unitario'], 2) : '—' ?></td>
                        <td><?= $fila['valor_total'] !== null ? '$ ' . number_format($fila['valor_total'], 2) : '—' ?></td>
                        <td><?= !empty($mesesFila) ? htmlspecialchars(implode(', ', $mesesFila)) : '—' ?></td>
                        <td><?= $fila['techo'] !== null ? '$ ' . number_format($fila['techo'], 2) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($filas)): ?>
                    <tr>
                        <td colspan="16">No hay elementos consolidados para mostrar.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

    <div id="modal-editar-consolidado" class="modal-fondo">
        <div class="modal-caja modal-caja-ancha">
            <div class="modal-cabecera">
                <h2>Editar <span id="editar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-editar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Solo puedes editar los ítems seleccionados porque todos pertenecen actualmente a tu dependencia (o a una dependencia hija tuya). Si alguno se redirecciona o cambia de dueño, dejarás de poder editarlo.</p>

            <form method="POST" action="index.php?ruta=consolidado-detalle" class="form-necesidad">
                <input type="hidden" name="accion" value="editar_consolidado_grupo">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="tipo_filtro" value="<?= htmlspecialchars($tipoFiltro) ?>">

                <div class="tabla-scroll">
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Dependencia</th>
                                <th>Sede</th>
                                <th>Línea estratégica</th>
                                <th>Motor de desarrollo</th>
                                <th>Proyecto PDI</th>
                                <th>Objeto/Proyecto (PAA)</th>
                                <th>Actividad</th>
                                <th>Rubro</th>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th>Costo unitario</th>
                                <th>Valor total</th>
                                <th>Meses</th>
                                <th>Techo presupuestal</th>
                            </tr>
                        </thead>
                        <tbody id="editar-consolidado-cuerpo"></tbody>
                    </table>
                </div>

                <button type="submit" class="boton-enviar">Guardar cambios</button>
            </form>
        </div>
    </div>

    <div id="modal-redireccionar-consolidado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Redireccionar <span id="redireccionar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-redireccionar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=consolidado-detalle" class="form-necesidad form-confirmar-envio" data-campo-dependencia="redireccionar-consolidado-dependencia" data-campo-rol="redireccionar-consolidado-rol">
                <input type="hidden" name="accion" value="redireccionar_consolidado">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="tipo_filtro" value="<?= htmlspecialchars($tipoFiltro) ?>">
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

    <div id="modal-duplicar-consolidado" class="modal-fondo">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Duplicar <span id="duplicar-consolidado-tipo-texto"></span></h2>
                <button type="button" id="boton-cerrar-modal-duplicar-consolidado" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <p class="texto-atenuado">Se creará una copia exacta de cada ítem seleccionado (incluyendo sus conceptos, si aplica), como un ítem nuevo separado dentro del mismo tipo.</p>

            <div class="tabla-scroll">
                <table class="tabla-usuarios tabla-consolidado-detalle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody id="duplicar-consolidado-cuerpo"></tbody>
                </table>
            </div>

            <form method="POST" action="index.php?ruta=consolidado-detalle" class="form-necesidad">
                <input type="hidden" name="accion" value="duplicar_consolidado">
                <input type="hidden" name="anio_id" value="<?= (int) $anioSeleccionadoId ?>">
                <input type="hidden" name="tipo_filtro" value="<?= htmlspecialchars($tipoFiltro) ?>">
                <div id="duplicar-consolidado-campos-items"></div>

                <button type="submit" class="boton-enviar">Duplicar</button>
            </form>
        </div>
    </div>

    <script type="application/json" id="datos-usuarios-por-dependencia-rol"><?= json_encode($usuariosPorDependenciaYRol, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
