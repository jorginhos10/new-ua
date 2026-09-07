<?php $tituloPagina = 'Actas'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>Actas</h1>
            <button type="button" id="boton-abrir-modal-acta" class="boton-agregar">+ Subir acta</button>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <div class="pestanas">
            <a href="index.php?ruta=actas&tab=recibidas" class="pestana<?= $tab === 'recibidas' ? ' activa' : '' ?>">Recibidas</a>
            <a href="index.php?ruta=actas&tab=enviadas" class="pestana<?= $tab === 'enviadas' ? ' activa' : '' ?>">Enviadas</a>
        </div>

        <?php $listaActual = $tab === 'enviadas' ? $enviadas : $recibidas; ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <?php if ($tab === 'enviadas'): ?>
                        <th>Para</th>
                        <?php else: ?>
                        <th>De</th>
                        <?php endif; ?>
                        <th>Facultad</th>
                        <th>Año</th>
                        <th>Archivo</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaActual as $actaFila): ?>
                    <tr class="<?= $tab === 'recibidas' && (int) $actaFila['leido'] === 0 ? 'fila-no-leida' : '' ?>">
                        <td><?= htmlspecialchars($tab === 'enviadas' ? $actaFila['destinatario_nombre'] : $actaFila['remitente_nombre']) ?></td>
                        <td><?= htmlspecialchars($actaFila['dependencia_nombre']) ?></td>
                        <td><?= htmlspecialchars((string) $actaFila['anio']) ?></td>
                        <td>
                            <a href="index.php?ruta=actas-descargar&id=<?= (int) $actaFila['id'] ?>" target="_blank" class="enlace-asunto-mensaje">
                                <?= htmlspecialchars($actaFila['nombre_archivo']) ?>
                            </a>
                        </td>
                        <td class="texto-atenuado"><?= htmlspecialchars($actaFila['creado_en']) ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <a href="index.php?ruta=actas-descargar&id=<?= (int) $actaFila['id'] ?>" target="_blank" class="boton-accion boton-accion-ver">Ver</a>
                                <form method="POST" action="index.php?ruta=actas">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $actaFila['id'] ?>">
                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($listaActual)): ?>
                    <tr>
                        <td colspan="6">No hay actas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal-acta" class="modal-fondo<?= !empty($error) ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2>Subir acta</h2>
                <button type="button" id="boton-cerrar-modal-acta" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=actas&tab=enviadas" enctype="multipart/form-data" class="form-necesidad" id="form-subir-acta">
                <div class="campo">
                    <label for="anio_presupuestal_id">Año presupuestal *</label>
                    <select id="anio_presupuestal_id" name="anio_presupuestal_id" required>
                        <option value="">Selecciona un año</option>
                        <?php foreach ($aniosActivos as $anioOpcion): ?>
                        <option value="<?= (int) $anioOpcion['id'] ?>"><?= htmlspecialchars((string) $anioOpcion['anio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label>Facultad</label>
                    <select disabled>
                        <option selected><?= htmlspecialchars($dependenciaFacultad['nombre']) ?></option>
                    </select>
                </div>

                <div class="campo campo-ancho">
                    <label for="destinatario_id_buscador">Enviar a *</label>
                    <div class="selector-buscable" id="destinatario_id-selector">
                        <input type="text" id="destinatario_id_buscador" class="selector-buscable-input" placeholder="Buscar destinatario..." autocomplete="off">
                        <input type="hidden" name="destinatario_id" id="destinatario_id">
                        <div class="selector-buscable-lista" id="destinatario_id_lista">
                            <?php foreach ($destinatarios as $destinatario): ?>
                            <div class="selector-buscable-opcion" data-id="<?= (int) $destinatario['id'] ?>" data-texto="<?= htmlspecialchars($destinatario['nombre'] . ' ' . $destinatario['correo']) ?>" data-mostrar="<?= htmlspecialchars($destinatario['nombre'] . ' (' . $destinatario['correo'] . ')') ?>">
                                <?= htmlspecialchars($destinatario['nombre']) ?> (<?= htmlspecialchars($destinatario['correo']) ?>)
                            </div>
                            <?php endforeach; ?>
                            <div class="selector-buscable-vacio">Sin resultados.</div>
                        </div>
                    </div>
                </div>

                <div class="campo campo-ancho">
                    <label for="archivo_acta">Archivo PDF * (máximo 3 MB)</label>
                    <input type="file" id="archivo_acta" name="archivo" accept="application/pdf">
                    <p id="archivo_acta_error" class="mensaje-error" style="display:none;"></p>
                </div>

                <div class="campo campo-ancho" id="vista_previa_acta_envoltorio" style="display:none;">
                    <label>Vista previa</label>
                    <iframe id="vista_previa_acta" title="Vista previa del acta" style="width:100%; height:420px; border:1px solid var(--color-borde); border-radius:var(--radio-chico);"></iframe>
                </div>

                <button type="submit" id="boton-enviar-acta" class="boton-enviar" disabled>Enviar acta</button>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
