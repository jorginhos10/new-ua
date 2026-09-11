<?php
require __DIR__ . '/../parciales/encabezado.php';

$nombresMeses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];
?>

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

        <span class="badge-rol badge-<?= htmlspecialchars($registro['estado']) ?>">
            <?= $registro['estado'] === 'enviado' ? 'Enviado' : 'Borrador' ?>
        </span>

        <?php if ($esGasto): ?>
        <section class="seccion-detalle">
            <h2>Datos generales</h2>
            <dl class="detalle-solicitud">
                <dt>Dependencia</dt>
                <dd><?= htmlspecialchars($registro['dependencia']) ?></dd>

                <?php if (isset($registro['categoria'])): ?>
                <dt>Categoría</dt>
                <dd><?= htmlspecialchars($registro['categoria']) ?></dd>
                <?php endif; ?>

                <dt>Sede</dt>
                <dd><?= htmlspecialchars($registro['sede_codigo'] . ' - ' . $registro['sede_nombre']) ?></dd>

                <dt>Línea estratégica</dt>
                <dd><?= htmlspecialchars($registro['linea_codigo'] . ' - ' . $registro['linea_nombre']) ?></dd>

                <dt>Motor de desarrollo</dt>
                <dd><?= htmlspecialchars($registro['motor_codigo'] . ' - ' . $registro['motor_nombre']) ?></dd>

                <dt>Proyecto PDI</dt>
                <dd><?= htmlspecialchars($registro['proyecto_codigo'] . ' - ' . $registro['proyecto_nombre']) ?></dd>

                <dt>Actividad</dt>
                <dd><?= htmlspecialchars($registro['actividad']) ?></dd>

                <dt>Rubro</dt>
                <dd><?= $registro['rubro_id'] !== null ? htmlspecialchars($registro['rubro_codigo'] . ' - ' . $registro['rubro_descripcion']) : htmlspecialchars((string) ($registro['rubro_texto'] ?? '—')) ?></dd>

                <dt>Insumo</dt>
                <dd><?= htmlspecialchars($registro['insumo']) ?></dd>

                <dt>Cantidad</dt>
                <dd><?= (int) $registro['cantidad'] ?></dd>

                <dt>Costo unitario</dt>
                <dd><?= number_format((float) $registro['costo_unitario'], 2) ?></dd>

                <dt>Valor total</dt>
                <dd><?= number_format((float) $registro['valor_total'], 2) ?></dd>

                <dt>Meses</dt>
                <dd>
                    <?php
                    $mesesRegistro = $registro['meses'] !== ''
                        ? array_map(static fn ($mes) => $nombresMeses[(int) $mes] ?? $mes, explode(',', $registro['meses']))
                        : [];
                    ?>
                    <?= htmlspecialchars(implode(', ', $mesesRegistro)) ?>
                </dd>
            </dl>
        </section>
        <?php else: ?>
        <section class="seccion-detalle">
            <h2>Datos generales</h2>
            <dl class="detalle-solicitud">
                <dt>Dependencia</dt>
                <dd><?= htmlspecialchars($registro['dependencia']) ?></dd>

                <dt>Valor total</dt>
                <dd><?= number_format((float) $registro['valor_total'], 2) ?></dd>
            </dl>
        </section>

        <section class="seccion-detalle">
            <h2>Conceptos</h2>
            <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Cantidad</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registro['conceptos'] as $concepto): ?>
                        <tr>
                            <td><?= htmlspecialchars($concepto['concepto']) ?></td>
                            <td><?= (int) $concepto['cantidad'] ?></td>
                            <td><?= number_format((float) $concepto['valor'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($registro['conceptos'])): ?>
                        <tr>
                            <td colspan="3">Sin conceptos.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (($registro['concepto_adicional'] ?? '') !== ''): ?>
        <section class="seccion-detalle">
            <h2>Concepto adicional</h2>
            <dl class="detalle-solicitud">
                <dt><?= htmlspecialchars($registro['concepto_adicional']) ?></dt>
                <dd><?= number_format((float) $registro['valor_adicional'], 2) ?></dd>
            </dl>
        </section>
        <?php endif; ?>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
