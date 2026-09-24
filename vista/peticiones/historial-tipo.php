<?php
$tituloPagina = 'Historial de ' . $tipo;
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <?php
        $barraTitulo = 'Historial — ' . $tipo;
        // Cada fila es una sola acción masiva sobre varios ítems de este tipo a la vez (aprobar/
        // archivar/consolidar/duplicar/enviar/restaurar/desconsolidar); entrar a una fila muestra
        // exactamente qué ítems afectó.
        $barraTituloExtra = ' <span class="icono-info" tabindex="0" title="Cada fila es una sola acción masiva sobre varios ítems de este tipo a la vez (aprobar/archivar/consolidar/duplicar/enviar/restaurar/desconsolidar). Entra a una fila para ver exactamente qué ítems afectó."><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></span>';
        $barraBotonesSecundarios = [];
        $barraBotonPrincipal = null;
        $barraEstado = 'consulta';
        $barraRutaVolver = $volver;
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Actor</th>
                        <th>Acción</th>
                        <th>Cantidad de ítems</th>
                        <th>Detalle</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lotes as $lote): ?>
                    <tr>
                        <td><?= htmlspecialchars($lote['creado_en']) ?></td>
                        <td><?= htmlspecialchars($lote['usuario_nombre'] ?? 'Usuario eliminado') ?></td>
                        <td><?= htmlspecialchars($lote['accion']) ?></td>
                        <td><?= (int) $lote['cantidad_items'] ?></td>
                        <td><?= htmlspecialchars($lote['detalle']) ?></td>
                        <?php
                        $rutaVerLote = 'index.php?ruta=peticiones-historial-lote&lote_id=' . (int) $lote['id']
                            . '&volver=' . urlencode('index.php?ruta=peticiones-historial-tipo&tipo=' . $tipo);
                        ?>
                        <td class="celda-acciones">
                            <a href="<?= htmlspecialchars($rutaVerLote) ?>" class="boton-accion boton-accion-ver">Ver ítems</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lotes)): ?>
                    <tr>
                        <td colspan="6">Todavía no hay eventos masivos registrados para este tipo.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
