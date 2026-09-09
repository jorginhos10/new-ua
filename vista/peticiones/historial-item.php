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
];
?>

    <div class="tarjeta">
        <?php
        $barraTitulo = 'Historial — ' . $origen . ' #' . $origenId;
        $barraBotonesSecundarios = [];
        $barraBotonPrincipal = null;
        $barraEstado = 'consulta';
        $barraRutaVolver = $volver;
        require __DIR__ . '/../parciales/barra-modulo.php';
        ?>

        <p class="texto-atenuado">Registro de auditoría de solo lectura de este ítem: qué le pasó desde que llegó a Peticiones (no incluye el momento en que se envió desde su módulo de origen).</p>

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
