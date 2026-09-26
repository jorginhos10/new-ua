<?php
$tituloPagina = 'Historial del ítem';
require __DIR__ . '/../parciales/encabezado.php';

$etiquetasAccion = [
    'aprobada' => 'Consolidado',
    'archivada' => 'Archivado',
    'restaurada' => 'Restaurado',
    'editada' => 'Editado',
    'redireccionada' => 'Redireccionado',
    'duplicada' => 'Duplicado',
    'rechazada_redireccion' => 'Rechazó redirección',
    'eliminada' => 'Eliminado',
    'enviado' => 'Enviado',
    'editado_tras_enviar' => 'Editado después de enviar',
];
?>

    <div class="tarjeta">
        <?php
        $barraTitulo = 'Historial — ' . $origen . ' #' . $origenId;
        // Registro de solo lectura: qué le pasó a este ítem desde que llegó a Peticiones (no
        // incluye el momento en que se envió desde su módulo de origen). Antes iba en un <p> debajo
        // del título, quitando espacio a la tabla en pantallas chicas — ahora vive en el title del
        // icono-info, igual patrón que ya usa vista/dependencias/index.php.
        $barraTituloExtra = ' <span class="icono-info" tabindex="0" title="Registro de auditoría de solo lectura de este ítem: cuándo se envió desde su módulo (Gastos/Autogestión), si se editó después de enviarse, y qué le pasó en Peticiones."><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></span>';
        $barraBotonesSecundarios = [];
        $barraBotonPrincipal = null;
        $barraEstado = 'consulta';
        $barraRutaVolver = $volver;
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <?php if ($loteItem !== null): ?>
        <div class="lote-card" style="margin-bottom: 16px;">
            <div class="lote-card-cabecera">
                <div>
                    <div class="lote-card-meta">
                        Este ítem fue enviado como parte de Enviado v<?= (int) $loteItem['version'] ?> (<?= (int) $loteItem['cantidadItems'] ?> ítem(s) de <?= htmlspecialchars($loteItem['dependencia']) ?>)
                        <?php if ($loteItem['eliminada']): ?>
                        <span class="badge-rol badge-archivado">Eliminado después de enviarse</span>
                        <?php elseif ($loteItem['cambioFila'] !== null): ?>
                        <span class="badge-rol badge-borrador">Editado después de enviar</span>
                        <?php else: ?>
                        <span class="texto-atenuado">— sin cambios desde el envío</span>
                        <?php endif; ?>
                    </div>
                    <div class="lote-card-sub">
                        <?= htmlspecialchars(date('d/m/Y, g:i a', strtotime($loteItem['enviadoEn']))) ?> → <?= htmlspecialchars($loteItem['destinatarioTexto']) ?>
                        · Total del lote (actual): <?= htmlspecialchars($loteItem['totalLoteFormateado']) ?>
                        · Techo asignado en ese momento: <?= $loteItem['techoFormateado'] !== null ? htmlspecialchars($loteItem['techoFormateado']) : 'sin techo asignado' ?>
                    </div>
                </div>
            </div>
            <?php if ($loteItem['cambioFila'] !== null): ?>
            <?php $cambioFila = $loteItem['cambioFila']; require __DIR__ . '/../parciales/comparacion-fila-lote.php'; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Actor</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= htmlspecialchars($evento['creado_en']) ?></td>
                        <td><?= htmlspecialchars($evento['usuario_nombre'] ?? 'Usuario eliminado') ?></td>
                        <td><?= htmlspecialchars($etiquetasAccion[$evento['accion']] ?? $evento['accion']) ?></td>
                        <td><?= htmlspecialchars($evento['detalle']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($eventos)): ?>
                    <tr>
                        <td colspan="4">Todavía no hay eventos registrados para este ítem.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
