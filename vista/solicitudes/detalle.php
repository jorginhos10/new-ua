<?php
$tituloPagina = 'Detalle de la solicitud';
require __DIR__ . '/../parciales/encabezado.php';

$tabsPorTipo = ['arl' => 'arl', 'monitores' => 'monitores', 'ops' => 'ops', 'otros' => 'otros'];
$titulosPorTipo = [
    'arl' => 'ARL de estudiantes en prácticas',
    'monitores' => 'Monitores',
    'ops' => 'OPS prestación de servicios',
    'otros' => 'Petición',
];
?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <h1>Detalle — <?= htmlspecialchars($titulosPorTipo[$tipo]) ?></h1>
            <a href="<?= htmlspecialchars($rutaVolver) ?>" class="boton-agregar">&larr; <?= htmlspecialchars($etiquetaVolver) ?></a>
        </div>

        <span class="badge-rol badge-<?= htmlspecialchars($registro['estado']) ?>" <?= !empty($registro['enviada_a']) ? 'title="Enviada a: ' . htmlspecialchars($registro['enviada_a']) . '"' : '' ?>>
            <?= $registro['estado'] === 'enviada' ? 'Enviada' : 'Borrador' ?>
        </span>
        <?php if (!empty($registro['enviada_a'])): ?>
        <p class="texto-atenuado">Enviada a: <?= htmlspecialchars($registro['enviada_a']) ?></p>
        <?php endif; ?>

        <?php if ($tipo === 'arl'): ?>
        <section class="seccion-detalle">
            <h2>Datos generales</h2>
            <dl class="detalle-solicitud">
                <dt>Facultad</dt>
                <dd><?= htmlspecialchars($registro['facultad']) ?></dd>
            </dl>
        </section>

        <section class="seccion-detalle">
            <h2>Niveles de riesgo</h2>
            <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Nivel de riesgo</th>
                            <th>Estudiantes</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $numerosRomanos = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
                        $totalEstudiantes = 0;
                        $totalValor = 0.0;
                        foreach ($numerosRomanos as $nivel => $romano):
                            $estudiantes = (int) $registro['riesgo' . $nivel . '_estudiantes'];
                            $valor = (float) $registro['riesgo' . $nivel . '_valor'];
                            $totalEstudiantes += $estudiantes;
                            $totalValor += $valor;
                        ?>
                        <tr>
                            <td>Riesgo <?= $romano ?></td>
                            <td><?= $estudiantes ?></td>
                            <td>$ <?= number_format($valor, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td><strong><?= $totalEstudiantes ?></strong></td>
                            <td><strong>$ <?= number_format($totalValor, 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <?php elseif ($tipo === 'monitores'): ?>
        <section class="seccion-detalle">
            <h2>Datos generales</h2>
            <dl class="detalle-solicitud">
                <dt>Dependencia</dt>
                <dd><?= htmlspecialchars($registro['dependencia']) ?></dd>
                <dt>Tipo</dt>
                <dd><?= htmlspecialchars($tiposMonitor[$registro['tipo']] ?? $registro['tipo']) ?></dd>
            </dl>
        </section>

        <section class="seccion-detalle">
            <h2>Monitores por semestre</h2>
            <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Semestre I</th>
                            <th>Semestre II</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= (int) $registro['monitores_semestre1'] ?></td>
                            <td><?= (int) $registro['monitores_semestre2'] ?></td>
                            <td><strong><?= (int) $registro['monitores_semestre1'] + (int) $registro['monitores_semestre2'] ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <?php elseif ($tipo === 'ops'): ?>
        <section class="seccion-detalle">
            <h2>Ubicación presupuestal</h2>
            <dl class="detalle-solicitud">
                <dt>Sede</dt>
                <dd><?= htmlspecialchars($registro['sede_codigo'] . ' - ' . $registro['sede_nombre']) ?></dd>
                <dt>Dependencia</dt>
                <dd><?= htmlspecialchars($registro['dependencia']) ?></dd>
                <dt>Línea</dt>
                <dd><?= htmlspecialchars($registro['linea_codigo'] . ' - ' . $registro['linea_nombre']) ?></dd>
                <dt>Motor</dt>
                <dd><?= htmlspecialchars($registro['motor_codigo'] . ' - ' . $registro['motor_nombre']) ?></dd>
                <dt>Proyecto</dt>
                <dd><?= htmlspecialchars($registro['proyecto_codigo'] . ' - ' . $registro['proyecto_nombre']) ?></dd>
                <dt>Rubro</dt>
                <dd><?= htmlspecialchars($registro['rubro_codigo'] . ' - ' . $registro['rubro_descripcion']) ?></dd>
            </dl>
        </section>

        <section class="seccion-detalle">
            <h2>Perfil y valores</h2>
            <dl class="detalle-solicitud">
                <dt>Perfil</dt>
                <dd><?= htmlspecialchars($perfilesOps[$registro['perfil']] ?? $registro['perfil']) ?></dd>
                <dt>Valor unitario</dt>
                <dd>$ <?= number_format((float) $registro['valor'], 2) ?></dd>
                <dt>Cantidad</dt>
                <dd><?= (int) $registro['cantidad'] ?></dd>
                <dt>Total</dt>
                <dd><strong>$ <?= number_format((float) $registro['valor'] * (int) $registro['cantidad'], 2) ?></strong></dd>
            </dl>
        </section>

        <?php if (!empty($registro['observaciones'])): ?>
        <section class="seccion-detalle">
            <h2>Observaciones</h2>
            <p><?= nl2br(htmlspecialchars($registro['observaciones'])) ?></p>
        </section>
        <?php endif; ?>

        <?php else: ?>
        <section class="seccion-detalle">
            <h2>Datos generales</h2>
            <dl class="detalle-solicitud">
                <dt>Concepto</dt>
                <dd><?= htmlspecialchars($registro['concepto']) ?></dd>
            </dl>
        </section>

        <section class="seccion-detalle">
            <h2>Semestres</h2>
            <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Semestre 1</th>
                            <th>Semestre 2</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Cantidad</td>
                            <td><?= (int) $registro['semestre1'] ?></td>
                            <td><?= (int) $registro['semestre2'] ?></td>
                            <td><strong><?= (int) $registro['semestre1'] + (int) $registro['semestre2'] ?></strong></td>
                        </tr>
                        <tr>
                            <td>Valor</td>
                            <td>$ <?= number_format((float) $registro['valor_s1'], 2) ?></td>
                            <td>$ <?= number_format((float) $registro['valor_s2'], 2) ?></td>
                            <td><strong>$ <?= number_format((float) $registro['valor_s1'] + (float) $registro['valor_s2'], 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
