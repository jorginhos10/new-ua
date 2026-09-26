<?php
/**
 * Tarjeta de un lote enviado (snapshot congelado de un clic de "Enviar") en el landing de un
 * módulo: dos botones independientes — "Ver cambios" (comparación de fila completa de cada fila
 * editada después de enviarse) y "Ver lote completo" (tabla entera + total actual + techo
 * congelado con saldo) — más, solo para superadmin, "Ocultar snapshot"/"Eliminar snapshot".
 * La lógica de negocio (comparar, resolver textos, calcular totales) ya viene resuelta desde el
 * controlador; este parcial solo pinta.
 *
 * Variables esperadas antes de incluir este parcial:
 * - $loteVista (array): ver VistaLoteEnvio::construirLotes() para la forma exacta.
 * - $esSuperAdmin (bool)
 * - $rutaAccionLote (string): action de los formularios de Ocultar/Eliminar.
 * - $origenLote (string): clave de TABLAS_ORIGEN (ej. 'gasto_principal'), para el enlace a Historial.
 */
$columnasLote = count($loteVista['headers']) + 2;
$indiceValorTotal = array_search('Valor total', $loteVista['headers'], true);
$colspanEtiquetaTotal = $indiceValorTotal !== false ? $indiceValorTotal + 1 : count($loteVista['headers']);
$celdasTrasTotal = $columnasLote - $colspanEtiquetaTotal - 1;
?>
<div class="lote-card" data-lote-card>
    <div class="lote-card-cabecera">
        <div>
            <div class="lote-card-meta">
                Enviado v<?= (int) $loteVista['version'] ?> · <?= (int) $loteVista['cantidadItems'] ?> ítem(s)
                <span class="texto-atenuado">— <?= htmlspecialchars($loteVista['dependencia']) ?></span>
                <?php if ($loteVista['editado']): ?>
                <span class="badge-rol badge-borrador">Editado después de enviar</span>
                <?php endif; ?>
            </div>
            <div class="lote-card-sub">
                <?= htmlspecialchars(date('d/m/Y, g:i a', strtotime($loteVista['enviadoEn']))) ?> → <?= htmlspecialchars($loteVista['destinatarioTexto']) ?>
            </div>
        </div>
        <div class="lote-card-botones">
            <button type="button" class="lote-boton" data-lote-boton="cambios"<?= empty($loteVista['cambios']) ? ' disabled title="Ninguna fila de este lote se ha editado después de enviarse."' : '' ?>>Ver cambios</button>
            <button type="button" class="lote-boton" data-lote-boton="completo">Ver lote completo</button>
            <?php if ($esSuperAdmin): ?>
            <form method="POST" action="<?= htmlspecialchars($rutaAccionLote) ?>">
                <input type="hidden" name="accion" value="ocultar_lote">
                <input type="hidden" name="lote_id" value="<?= (int) $loteVista['id'] ?>">
                <button type="submit" class="lote-boton" title="Solo superadmin — el snapshot deja de mostrarse, pero no se borra.">Ocultar snapshot</button>
            </form>
            <form method="POST" action="<?= htmlspecialchars($rutaAccionLote) ?>" onsubmit="return confirm('¿Eliminar definitivamente este snapshot (Enviado v<?= (int) $loteVista['version'] ?>)? Esta acción no se puede deshacer. Las filas de gastos no se tocan.');">
                <input type="hidden" name="accion" value="eliminar_lote">
                <input type="hidden" name="lote_id" value="<?= (int) $loteVista['id'] ?>">
                <button type="submit" class="lote-boton lote-boton-eliminar" title="Solo superadmin">Eliminar snapshot</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel-lote oculto" data-lote-panel="cambios">
        <?php foreach ($loteVista['cambios'] as $cambioFila): ?>
        <?php require __DIR__ . '/comparacion-fila-lote.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="panel-lote oculto" data-lote-panel="completo">
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <?php foreach ($loteVista['headers'] as $encabezado): ?>
                        <th><?= htmlspecialchars($encabezado) ?></th>
                        <?php endforeach; ?>
                        <th>Historial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loteVista['filas'] as $filaVista): ?>
                    <tr>
                        <td>
                            <?php if ($filaVista['eliminada']): ?>
                            <span class="badge-rol badge-archivado">Eliminada</span>
                            <?php elseif ($filaVista['editada']): ?>
                            <span class="badge-rol badge-borrador">Editado</span>
                            <?php else: ?>
                            <span class="texto-atenuado">Sin cambios</span>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($filaVista['celdas'] as $celda): ?>
                        <td class="<?= htmlspecialchars($celda['clase']) ?>"><?= htmlspecialchars($celda['valor']) ?></td>
                        <?php endforeach; ?>
                        <td>
                            <a class="boton-accion" href="index.php?ruta=peticiones-historial-item&amp;origen=<?= urlencode($origenLote) ?>&amp;origen_id=<?= (int) $filaVista['origenId'] ?>">Ver historial</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fila-total-lote">
                        <td colspan="<?= $colspanEtiquetaTotal ?>" style="text-align: right;">TOTAL LOTE (actual)</td>
                        <td><?= htmlspecialchars($loteVista['totalLoteFormateado']) ?></td>
                        <?php for ($i = 0; $i < $celdasTrasTotal; $i++): ?><td></td><?php endfor; ?>
                    </tr>
                    <tr class="fila-techo-lote">
                        <td colspan="<?= $colspanEtiquetaTotal ?>" style="text-align: right;">Techo asignado en ese momento (saldo actual)</td>
                        <td><?= $loteVista['techoFormateado'] !== null ? htmlspecialchars($loteVista['techoFormateado']) : 'Sin techo asignado' ?></td>
                        <?php for ($i = 0; $i < $celdasTrasTotal; $i++): ?><td></td><?php endfor; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php unset($columnasLote, $indiceValorTotal, $colspanEtiquetaTotal, $celdasTrasTotal, $filaVista, $celda); ?>
