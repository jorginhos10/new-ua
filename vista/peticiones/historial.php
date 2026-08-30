<?php
$tituloPagina = 'Historial';
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
];
?>

    <div class="tarjeta">
        <?php
        $barraTitulo = 'Historial — ' . $bandejas[$bandeja]['etiqueta'];
        $barraBotonesSecundarios = [];
        $barraBotonPrincipal = null;
        $barraEstado = 'consulta';
        $barraRutaVolver = 'index.php?ruta=peticiones&bandeja=' . urlencode($bandeja);
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <p class="texto-atenuado">Registro de auditoría de solo lectura: qué le pasó a cada ítem desde que llegó a Peticiones (no incluye el momento en que se envió desde su módulo de origen).</p>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Actor</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                        <th>Origen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= htmlspecialchars($evento['creado_en']) ?></td>
                        <td><?= htmlspecialchars($evento['usuario_nombre'] ?? 'Usuario eliminado') ?></td>
                        <td><?= htmlspecialchars($etiquetasAccion[$evento['accion']] ?? $evento['accion']) ?></td>
                        <td><?= htmlspecialchars($evento['detalle']) ?></td>
                        <td><?= htmlspecialchars($evento['origen']) ?> #<?= (int) $evento['origen_id'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($eventos)): ?>
                    <tr>
                        <td colspan="5">Todavía no hay eventos registrados en esta bandeja.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
