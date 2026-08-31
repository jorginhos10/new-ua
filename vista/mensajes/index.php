<?php $tituloPagina = 'Mensajes'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>Mensajes</h1>
            <button type="button" id="boton-abrir-modal-mensaje" class="boton-agregar">+ Redactar</button>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <div class="pestanas">
            <a href="index.php?ruta=mensajes&tab=recibidos" class="pestana<?= $tab === 'recibidos' ? ' activa' : '' ?>">Recibidos</a>
            <a href="index.php?ruta=mensajes&tab=enviados" class="pestana<?= $tab === 'enviados' ? ' activa' : '' ?>">Enviados</a>
        </div>

        <?php $listaActual = $tab === 'enviados' ? $enviados : $recibidos; ?>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <?php if ($tab === 'enviados'): ?>
                        <th>Para</th>
                        <?php else: ?>
                        <th>De</th>
                        <?php endif; ?>
                        <th>Asunto</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaActual as $mensajeFila): ?>
                    <tr class="fila-mensaje<?= $tab === 'recibidos' && (int) $mensajeFila['leido'] === 0 ? ' fila-no-leida' : '' ?>" data-mensaje-id="<?= (int) $mensajeFila['id'] ?>">
                        <td><?= htmlspecialchars($tab === 'enviados' ? $mensajeFila['destinatario_nombre'] : $mensajeFila['remitente_nombre']) ?></td>
                        <td>
                            <button
                                type="button"
                                class="enlace-asunto-mensaje boton-ver-mensaje"
                                data-id="<?= (int) $mensajeFila['id'] ?>"
                                data-asunto="<?= htmlspecialchars($mensajeFila['asunto']) ?>"
                                data-cuerpo="<?= htmlspecialchars($mensajeFila['cuerpo']) ?>"
                                data-remitente="<?= htmlspecialchars($tab === 'enviados' ? $nombreActual : $mensajeFila['remitente_nombre']) ?>"
                                data-destinatario="<?= htmlspecialchars($tab === 'enviados' ? $mensajeFila['destinatario_nombre'] : $nombreActual) ?>"
                                data-fecha="<?= htmlspecialchars($mensajeFila['creado_en']) ?>"
                                data-leido="<?= $tab === 'enviados' ? 1 : (int) $mensajeFila['leido'] ?>"
                                data-contraparte-id="<?= (int) ($tab === 'enviados' ? $mensajeFila['destinatario_id'] : $mensajeFila['remitente_id']) ?>"
                            ><?= htmlspecialchars($mensajeFila['asunto']) ?></button>
                        </td>
                        <td class="texto-atenuado"><?= htmlspecialchars($mensajeFila['creado_en']) ?></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <?php if ($tab === 'recibidos' && (int) $mensajeFila['leido'] === 0): ?>
                                <button type="button" class="boton-accion boton-accion-editar boton-marcar-leido" data-id="<?= (int) $mensajeFila['id'] ?>">Marcar como leído</button>
                                <?php endif; ?>
                                <form method="POST" action="index.php?ruta=mensajes">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $mensajeFila['id'] ?>">
                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($listaActual)): ?>
                    <tr>
                        <td colspan="4">No hay mensajes.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal-mensaje" class="modal-fondo<?= $responderA > 0 ? ' abierto' : '' ?>">
        <div class="modal-caja">
            <div class="modal-cabecera">
                <h2><?= $responderA > 0 ? 'Responder mensaje' : 'Redactar mensaje' ?></h2>
                <button type="button" id="boton-cerrar-modal-mensaje" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="index.php?ruta=mensajes&tab=enviados" class="form-necesidad">
                <div class="campo campo-ancho">
                    <label for="destinatario_id_buscador">Para *</label>
                    <div class="selector-buscable" id="destinatario_id-selector">
                        <input type="text" id="destinatario_id_buscador" class="selector-buscable-input" placeholder="Buscar destinatario..." autocomplete="off">
                        <input type="hidden" name="destinatario_id" id="destinatario_id" value="<?= $responderA > 0 ? (int) $responderA : '' ?>">
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
                    <label for="asunto">Asunto *</label>
                    <input type="text" id="asunto" name="asunto" value="<?= htmlspecialchars($asuntoRespuesta) ?>" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="cuerpo">Mensaje *</label>
                    <textarea id="cuerpo" name="cuerpo" rows="6" required></textarea>
                </div>

                <button type="submit" class="boton-enviar">Enviar</button>
            </form>
        </div>
    </div>

    <?php if ($responderA > 0): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            establecerValorBuscable('destinatario_id', <?= (int) $responderA ?>);
        });
    </script>
    <?php endif; ?>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
