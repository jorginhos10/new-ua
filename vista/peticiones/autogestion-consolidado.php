<?php
$tituloPagina = 'Autogestión y perfil de proyectos';
$nombresMeses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>
                    Autogestión y perfil de proyectos
                    <?php /* Lo ya aceptado en Extensión, Postgrado, Unisalud, Convenios y Perfil de
                             proyectos dentro de la propia dependencia y todas las que reportan a
                             ella (hijas, nietas y demás niveles) — sin el nivel de consolidación
                             institucional que tiene el administrador. */ ?>
                    <span class="icono-info" tabindex="0" title="Lo ya aceptado en Extensión, Postgrado, Unisalud, Convenios y Perfil de proyectos dentro de tu propia dependencia y todas las que reportan a ella (hijas, nietas y demás niveles) — sin el nivel de consolidación institucional que tiene el administrador.">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    </span>
                </h1>
            </div>
            <div class="grupo-acciones-encabezado">
                <a href="index.php?ruta=consolidado-autogestion&exportar=xlsx<?= $anioSeleccionadoId > 0 ? '&anio_id=' . (int) $anioSeleccionadoId : '' ?>" class="boton-accion boton-accion-enviar">Exportar Excel</a>
                <a href="index.php?ruta=peticiones" class="boton-accion boton-accion-ver">&larr; Volver a Peticiones</a>
            </div>
        </div>

        <?php if (count($aniosActivos) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="consolidado-autogestion">
                <label for="anio_id_filtro">Año presupuestal</label>
                <select id="anio_id_filtro" name="anio_id" onchange="this.form.submit()">
                    <?php foreach ($aniosActivos as $anioFila): ?>
                    <option value="<?= (int) $anioFila['id'] ?>" <?= $anioSeleccionadoId === (int) $anioFila['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $anioFila['anio']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <h2 class="subtitulo-seccion">Cómo quedó distribuido entre tu dependencia y las que reportan a ella</h2>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <th>Ítems aceptados</th>
                        <th>Valor total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resumenPorDependencia as $resumen): ?>
                    <tr>
                        <td><?= htmlspecialchars($resumen['dependencia']) ?></td>
                        <td><?= (int) $resumen['cantidad_items'] ?></td>
                        <td>$ <?= number_format($resumen['valor_total'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($resumenPorDependencia)): ?>
                    <tr>
                        <td colspan="3">Todavía no hay nada aceptado para mostrar.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 class="subtitulo-seccion">Detalle</h2>
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
                        <td><?= $fila['costo_unitario'] !== null ? number_format($fila['costo_unitario'], 2, ',', '.') : '—' ?></td>
                        <td><?= $fila['valor_total'] !== null ? '$ ' . number_format($fila['valor_total'], 2, ',', '.') : '—' ?></td>
                        <td><?= !empty($mesesFila) ? htmlspecialchars(implode(', ', $mesesFila)) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($filas)): ?>
                    <tr>
                        <td colspan="14">No hay elementos aceptados para mostrar.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
