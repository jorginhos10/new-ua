<?php
$tituloPagina = 'Historial del lote #' . $loteId;
require __DIR__ . '/../parciales/encabezado.php';

$etiquetasAccion = [
    'aprobada' => 'Consolidado',
    'archivada' => 'Archivado',
    'restaurada' => 'Restaurado',
    'editada' => 'Editado',
    'redireccionada' => 'Redireccionado',
    'duplicada' => 'Duplicado',
    'desconsolidada' => 'Desconsolidado',
    'rechazada_redireccion' => 'Rechazó redirección',
    'eliminada' => 'Eliminado',
];
?>

    <div class="tarjeta">
        <?php
        $barraTitulo = 'Historial — lote #' . $loteId;
        // Registro de solo lectura de esta acción masiva: qué ítems afectó, cuándo y quién la hizo.
        // Solo se muestran los ítems que el usuario actual puede ver hoy.
        $barraTituloExtra = ' <span class="icono-info" tabindex="0" title="Registro de auditoría de solo lectura de esta acción masiva: qué ítems afectó, cuándo y quién la hizo. Solo se muestran los ítems que puedes ver hoy."><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></span>';
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
                        <th>Ítem</th>
                        <th>Detalle</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= htmlspecialchars($evento['creado_en']) ?></td>
                        <td><?= htmlspecialchars($evento['usuario_nombre'] ?? 'Usuario eliminado') ?></td>
                        <td><?= htmlspecialchars($etiquetasAccion[$evento['accion']] ?? $evento['accion']) ?></td>
                        <td><?= htmlspecialchars($evento['origen'] . ' #' . $evento['origen_id']) ?></td>
                        <td><?= htmlspecialchars($evento['detalle']) ?></td>
                        <td class="celda-acciones">
                            <a href="<?= htmlspecialchars($evento['ruta_origen']) ?>" class="boton-accion boton-accion-ver">Ir al origen</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($eventos)): ?>
                    <tr>
                        <td colspan="6">No hay eventos visibles para ti en este lote.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
