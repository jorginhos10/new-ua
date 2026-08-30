<?php
$tituloPagina = 'Consolidado detallado';
$nombresMeses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];
require __DIR__ . '/../parciales/encabezado.php';

$parametrosExportar = ['ruta' => 'consolidado-detalle', 'exportar' => 'xlsx'];
if ($anioSeleccionadoId > 0) {
    $parametrosExportar['anio_id'] = $anioSeleccionadoId;
}
if ($tipoFiltro !== '') {
    $parametrosExportar['tipo'] = $tipoFiltro;
}
?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Consolidado detallado<?= $tipoFiltro !== '' ? ' — ' . htmlspecialchars($tipoFiltro) : '' ?></h1>
                <p class="texto-atenuado">Todo lo aprobado y consolidado<?= $anio !== null ? ' para ' . htmlspecialchars((string) $anio['anio']) : '' ?>, con el mismo detalle que la tabla de Gastos: dependencia, sede, línea, motor, proyecto, rubro, actividad, insumo y techo presupuestal.</p>
            </div>
            <div class="grupo-acciones-encabezado">
                <a href="index.php?<?= http_build_query($parametrosExportar) ?>" class="boton-accion boton-accion-enviar">Exportar Excel</a>
                <a href="index.php?ruta=peticiones&vista=consolidado<?= $anioSeleccionadoId > 0 ? '&anio_id=' . (int) $anioSeleccionadoId : '' ?>" class="boton-accion boton-accion-ver">&larr; Volver a Peticiones</a>
            </div>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Dependencia</th>
                        <th>Sede</th>
                        <th>Línea estratégica</th>
                        <th>Motor de desarrollo</th>
                        <th>Proyecto PDI</th>
                        <th>Objeto/Proyecto (PAA)</th>
                        <th>Actividad</th>
                        <th>Rubro</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Costo unitario</th>
                        <th>Valor total</th>
                        <th>Meses</th>
                        <th>Techo presupuestal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                    <?php
                    $mesesFila = $fila['meses'] !== '—' && $fila['meses'] !== ''
                        ? array_map(static fn ($mes) => $nombresMeses[(int) trim($mes)] ?? trim($mes), explode(',', $fila['meses']))
                        : [];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['tipo']) ?></td>
                        <td><?= $fila['dependencia'] !== null ? htmlspecialchars($fila['dependencia']) : '—' ?></td>
                        <td><?= htmlspecialchars($fila['sede']) ?></td>
                        <td><?= htmlspecialchars($fila['linea']) ?></td>
                        <td><?= htmlspecialchars($fila['motor']) ?></td>
                        <td><?= htmlspecialchars($fila['proyecto']) ?></td>
                        <td><?= htmlspecialchars($fila['objeto_proyecto_paa']) ?></td>
                        <td><?= htmlspecialchars($fila['actividad']) ?></td>
                        <td><?= htmlspecialchars($fila['rubro']) ?></td>
                        <td><?= htmlspecialchars($fila['insumo']) ?></td>
                        <td><?= $fila['cantidad'] !== null ? htmlspecialchars((string) $fila['cantidad']) : '—' ?></td>
                        <td><?= $fila['costo_unitario'] !== null ? number_format($fila['costo_unitario'], 2) : '—' ?></td>
                        <td><?= $fila['valor_total'] !== null ? '$ ' . number_format($fila['valor_total'], 2) : '—' ?></td>
                        <td><?= !empty($mesesFila) ? htmlspecialchars(implode(', ', $mesesFila)) : '—' ?></td>
                        <td><?= $fila['techo'] !== null ? '$ ' . number_format($fila['techo'], 2) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($filas)): ?>
                    <tr>
                        <td colspan="15">No hay elementos consolidados para mostrar.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
