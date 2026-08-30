<?php
$tituloPagina = 'Dashboard';
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="panel-dashboard-admin">
        <section class="panel-caja">
            <div class="variables-reloj-grid">
                <div>
                    <h2>Variables</h2>
                    <ul class="lista-variables-macro">
                        <?php foreach ($variablesMacro as $variable): ?>
                        <?php $valorVariable = (float) $variable['valor']; ?>
                        <li>
                            <span><?= htmlspecialchars($variable['nombre']) ?> <span class="texto-atenuado">(<?= htmlspecialchars((string) $variable['anio']) ?>)</span></span>
                            <strong><?= $valorVariable < 1 ? number_format($valorVariable * 100, 2) . '%' : number_format($valorVariable, 0) ?></strong>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($variablesMacro)): ?>
                        <li class="texto-atenuado">No hay variables registradas.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="reloj-arena-widget">
                    <?php if (!$relojArena['configurado']): ?>
                    <p class="texto-atenuado">Configura las fechas en <a href="index.php?ruta=reloj-arena">Configuraciones &gt; Reloj de arena</a>.</p>
                    <?php else: ?>
                    <?php
                    $radio = 46;
                    $circunferencia = number_format(2 * M_PI * $radio, 2, '.', '');
                    $offsetFinal = number_format((float) $circunferencia * (1 - $relojArena['porcentaje_transcurrido'] / 100), 2, '.', '');
                    ?>
                    <svg viewBox="0 0 120 120" class="reloj-arena-svg" aria-hidden="true">
                        <circle cx="60" cy="60" r="<?= $radio ?>" class="reloj-arena-anillo-fondo"></circle>
                        <circle
                            cx="60" cy="60" r="<?= $radio ?>"
                            class="reloj-arena-anillo-progreso"
                            data-reloj-arena-anillo
                            data-offset-final="<?= $offsetFinal ?>"
                            style="stroke-dasharray: <?= $circunferencia ?>; stroke-dashoffset: <?= $circunferencia ?>;"
                        ></circle>
                        <text x="60" y="56" text-anchor="middle" class="reloj-arena-anillo-numero"><?= (int) $relojArena['dias_faltantes'] ?></text>
                        <text x="60" y="76" text-anchor="middle" class="reloj-arena-anillo-etiqueta">días</text>
                    </svg>
                    <span class="reloj-arena-fechas"><?= htmlspecialchars($relojArena['fecha_inicio']) ?> — <?= htmlspecialchars($relojArena['fecha_cierre']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="panel-caja">
            <div class="mini-slider" data-mini-slider>
                <div class="mini-slider-cabecera">
                    <h2 class="mini-slider-titulo" data-mini-slider-titulo>Resumen de gastos</h2>
                    <div class="mini-slider-nav">
                        <button type="button" class="mini-slider-flecha" data-mini-slider-prev aria-label="Anterior">&#8249;</button>
                        <button type="button" class="mini-slider-flecha" data-mini-slider-next aria-label="Siguiente">&#8250;</button>
                    </div>
                </div>

                <div class="mini-slider-slide" data-mini-slider-slide data-titulo="Resumen de gastos">
                    <?php if (empty($resumenCostos)): ?>
                    <p class="texto-atenuado">No hay años presupuestales activos.</p>
                    <?php endif; ?>
                    <?php foreach ($resumenCostos as $costo): ?>
                    <div class="resumen-costos-item">
                        <div class="progreso-presupuesto-info">
                            <span class="progreso-presupuesto-porcentaje">
                                <?php if ($costo['presupuesto'] > 0): ?>
                                    <?= number_format($costo['porcentaje'], 1) ?>% del presupuesto <?= htmlspecialchars((string) $costo['anio']) ?> programado
                                <?php else: ?>
                                    <?= htmlspecialchars((string) $costo['anio']) ?>: sin presupuesto asignado
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="barra-progreso">
                            <div class="barra-progreso-relleno" style="width: <?= number_format($costo['porcentaje'], 2, '.', '') ?>%;"></div>
                        </div>
                        <span class="texto-atenuado resumen-costos-cifras">$ <?= number_format($costo['total_gastado'], 2) ?> / $ <?= number_format($costo['presupuesto'], 2) ?></span>
                        <span class="texto-atenuado resumen-costos-cifras"><?= (int) $costo['dependencias_con_dato'] ?>/<?= (int) $costo['dependencias_total'] ?> dependencias</span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mini-slider-slide" data-mini-slider-slide data-titulo="Autogestión" hidden>
                    <?php if (empty($resumenAutogestion)): ?>
                    <p class="texto-atenuado">No hay años presupuestales activos.</p>
                    <?php endif; ?>
                    <?php foreach ($resumenAutogestion as $ingreso): ?>
                    <div class="resumen-costos-item">
                        <div class="progreso-presupuesto-info">
                            <span class="progreso-presupuesto-porcentaje">
                                <?php if ($ingreso['presupuesto'] > 0): ?>
                                    <?= number_format($ingreso['porcentaje'], 1) ?>% del presupuesto <?= htmlspecialchars((string) $ingreso['anio']) ?> en ingresos (Extensión + Sin excedentes)
                                <?php else: ?>
                                    <?= htmlspecialchars((string) $ingreso['anio']) ?>: sin presupuesto asignado
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="barra-progreso">
                            <div class="barra-progreso-relleno" style="width: <?= number_format($ingreso['porcentaje'], 2, '.', '') ?>%;"></div>
                        </div>
                        <span class="texto-atenuado resumen-costos-cifras">$ <?= number_format($ingreso['total_ingresos'], 2) ?> / $ <?= number_format($ingreso['presupuesto'], 2) ?></span>
                        <span class="texto-atenuado resumen-costos-cifras"><?= (int) $ingreso['dependencias_con_dato'] ?>/<?= (int) $ingreso['dependencias_total'] ?> dependencias</span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mini-slider-slide" data-mini-slider-slide data-titulo="Postgrado" hidden>
                    <?php if (empty($resumenPostgrado)): ?>
                    <p class="texto-atenuado">No hay años presupuestales activos.</p>
                    <?php endif; ?>
                    <?php foreach ($resumenPostgrado as $ingreso): ?>
                    <div class="resumen-costos-item">
                        <div class="progreso-presupuesto-info">
                            <span class="progreso-presupuesto-porcentaje">
                                <?php if ($ingreso['presupuesto'] > 0): ?>
                                    <?= number_format($ingreso['porcentaje'], 1) ?>% del presupuesto <?= htmlspecialchars((string) $ingreso['anio']) ?> en ingresos (Postgrado)
                                <?php else: ?>
                                    <?= htmlspecialchars((string) $ingreso['anio']) ?>: sin presupuesto asignado
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="barra-progreso">
                            <div class="barra-progreso-relleno" style="width: <?= number_format($ingreso['porcentaje'], 2, '.', '') ?>%;"></div>
                        </div>
                        <span class="texto-atenuado resumen-costos-cifras">$ <?= number_format($ingreso['total_ingresos'], 2) ?> / $ <?= number_format($ingreso['presupuesto'], 2) ?></span>
                        <span class="texto-atenuado resumen-costos-cifras"><?= (int) $ingreso['dependencias_con_dato'] ?>/<?= (int) $ingreso['dependencias_total'] ?> dependencias</span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mini-slider-puntos">
                    <button type="button" class="mini-slider-punto activo" data-mini-slider-punto="0" aria-label="Diapositiva 1"></button>
                    <button type="button" class="mini-slider-punto" data-mini-slider-punto="1" aria-label="Diapositiva 2"></button>
                    <button type="button" class="mini-slider-punto" data-mini-slider-punto="2" aria-label="Diapositiva 3"></button>
                </div>
            </div>
        </section>

        <section class="panel-caja">
            <div class="panel-caja-cabecera">
                <h2>Lista de usuarios asociados</h2>
                <a href="index.php?ruta=usuarios">Gestionar</a>
            </div>
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuariosAsociados as $usuarioFila): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuarioFila['nombre']) ?></td>
                        <td><?= htmlspecialchars($usuarioFila['correo']) ?></td>
                        <td><span class="badge-rol badge-<?= htmlspecialchars($usuarioFila['rol']) ?>"><?= htmlspecialchars($usuarioFila['rol']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usuariosAsociados)): ?>
                    <tr>
                        <td colspan="3">No hay usuarios registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="panel-caja">
            <h2>Listado de peticiones entrantes</h2>
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Solicitante / Facultad</th>
                        <th>Valor</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $solicitud): ?>
                    <tr>
                        <td><?= htmlspecialchars($solicitud['tipo']) ?></td>
                        <td><?= htmlspecialchars($solicitud['origen']) ?></td>
                        <td><?= number_format($solicitud['valor'], 2) ?></td>
                        <td><?= htmlspecialchars($solicitud['fecha']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="4">No hay solicitudes registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
