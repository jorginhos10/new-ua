<?php require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo barra-modulo">
            <div class="barra-modulo-zona1-y-2">
                <a href="#" onclick="history.back(); return false;" class="boton-icono-accion barra-modulo-volver" data-tooltip="Volver" title="Volver">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div class="cabecera-modulo-titulo">
                    <h1><?= htmlspecialchars($tituloPagina) ?></h1>
                </div>
            </div>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Valor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['actividad']) ?></td>
                        <td><?= htmlspecialchars($item['insumo']) ?></td>
                        <td><?= (int) $item['cantidad'] ?></td>
                        <td>$ <?= number_format($item['valor_total'], 2) ?></td>
                        <td class="celda-acciones">
                            <a href="<?= htmlspecialchars($item['ruta_ver']) ?>" class="boton-accion boton-accion-ver">Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5">No hay elementos pendientes para esta dependencia.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
