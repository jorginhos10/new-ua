<?php
/**
 * Comparación de UNA fila completa enviada: versión "Al enviar" (foto congelada en el lote) vs
 * "Actual" (en vivo), todas las columnas, con las celdas distintas resaltadas. Se usa desde la
 * tarjeta de lote del landing (una vez por cada fila editada) y desde Historial (una sola vez,
 * para el ítem de esa página).
 *
 * Variables esperadas antes de incluir este parcial:
 * - $cambioFila (array): ['titulo' => string, 'headers' => string[], 'alEnviar' => string[],
 *   'actual' => array<['valor' => string, 'clase' => string]>|null] — 'actual' en null si la fila
 *   fue eliminada después de enviarse.
 */
?>
<div class="lote-cambios-item">
    <div class="lote-cambios-titulo"><?= htmlspecialchars($cambioFila['titulo']) ?></div>
    <div class="tabla-scroll">
        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Versión</th>
                    <?php foreach ($cambioFila['headers'] as $encabezado): ?>
                    <th><?= htmlspecialchars($encabezado) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="texto-atenuado">Al enviar</td>
                    <?php foreach ($cambioFila['alEnviar'] as $valor): ?>
                    <td><?= htmlspecialchars($valor) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Actual</strong></td>
                    <?php if ($cambioFila['actual'] === null): ?>
                    <td colspan="<?= count($cambioFila['headers']) ?>"><span class="badge-rol badge-archivado">Eliminada</span></td>
                    <?php else: ?>
                    <?php foreach ($cambioFila['actual'] as $celda): ?>
                    <td class="<?= htmlspecialchars($celda['clase']) ?>"><?= htmlspecialchars($celda['valor']) ?></td>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php unset($cambioFila); ?>
