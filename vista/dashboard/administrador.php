<?php
$tituloPagina = 'Dashboard';
require __DIR__ . '/../parciales/encabezado.php';

// Cada mensaje global es una diapositiva; si es largo, se reparte en varias (1/2, 2/2...).
$diapositivasMensaje = [];
foreach ($mensajesGlobales ?? [] as $mensajeGlobalFila) {
    $partes = MensajeGlobal::paginar($mensajeGlobalFila['contenido']);
    foreach ($partes as $indiceParte => $parte) {
        $diapositivasMensaje[] = ['texto' => $parte, 'indice' => $indiceParte + 1, 'total' => count($partes)];
    }
}
$totalDiapositivas = 3 + max(1, count($diapositivasMensaje));
?>

    <div class="panel-dashboard-admin">
        <div class="inicio-sectores">

            <section class="inicio-sector">
                <div class="inicio-tarjeta inicio-bienvenida">
                    <p class="inicio-institucion">Universidad del Atlántico</p>
                    <h1>Hola, <?= htmlspecialchars($nombreUsuario) ?></h1>
                    <p class="inicio-rol">Rol: <?= htmlspecialchars($rolActual) ?></p>
                    <p class="inicio-correo"><?= htmlspecialchars($correoUsuario) ?></p>
                    <p class="inicio-texto">Bienvenido al Sistema de Planeación y Presupuesto Institucional. Elige un módulo en el menú lateral para comenzar.</p>
                </div>

                <div class="inicio-pareja">
                    <div class="inicio-tarjeta inicio-tarjeta-variables">
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

                    <div class="inicio-tarjeta inicio-tarjeta-reloj">
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
                                <text x="60" y="56" text-anchor="middle" class="reloj-arena-anillo-numero"><?= (int) $relojArena['faltante'] ?></text>
                                <text x="60" y="76" text-anchor="middle" class="reloj-arena-anillo-etiqueta"><?= htmlspecialchars($relojArena['unidad']) ?></text>
                            </svg>
                            <span class="reloj-arena-fechas"><?= htmlspecialchars($relojArena['fecha_inicio']) ?> — <?= htmlspecialchars($relojArena['fecha_cierre']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="inicio-sector">
                <div class="mini-slider inicio-carrusel" data-mini-slider>
                    <div class="mini-slider-cabecera">
                        <h2 class="mini-slider-titulo" data-mini-slider-titulo>Resumen de gastos</h2>
                        <div class="mini-slider-nav">
                            <button type="button" class="mini-slider-flecha" data-mini-slider-prev aria-label="Anterior">&#8249;</button>
                            <button type="button" class="mini-slider-flecha" data-mini-slider-next aria-label="Siguiente">&#8250;</button>
                        </div>
                    </div>

                    <div class="inicio-carrusel-diapositivas">
                        <div class="mini-slider-slide inicio-tarjeta" data-mini-slider-slide data-titulo="Resumen de gastos">
                            <?php if (empty($resumenCostos)): ?>
                            <p class="texto-atenuado">No hay años presupuestales activos.</p>
                            <?php endif; ?>
                            <?php foreach ($resumenCostos as $costo): ?>
                            <div class="resumen-tarjeta">
                                <p class="resumen-anio">Año <?= htmlspecialchars((string) $costo['anio']) ?> · <?= (int) $costo['dependencias_con_techo'] ?> dependencias con techo</p>
                                <?php if ($costo['dependencias_con_techo'] === 0): ?>
                                <p class="texto-atenuado">Ninguna dependencia tiene techo asignado este año.</p>
                                <?php else: ?>
                                <div class="resumen-kpis">
                                    <div class="resumen-kpi"><span>Techo asignado</span><strong>$ <?= number_format($costo['techo_total'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Asignado</span><strong>$ <?= number_format($costo['gastado'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Disponible</span><strong class="<?= $costo['disponible'] < 0 ? 'resumen-negativo' : '' ?>">$ <?= number_format($costo['disponible'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>% asignado</span><strong><?= number_format($costo['porcentaje'], 1, ',', '.') ?>%</strong></div>
                                </div>
                                <div class="barra-progreso">
                                    <div class="barra-progreso-relleno" style="width: <?= number_format($costo['porcentaje'], 2, '.', '') ?>%;"></div>
                                </div>
                                <p class="texto-atenuado resumen-pie"><?= (int) $costo['dependencias_con_gasto'] ?> de <?= (int) $costo['dependencias_con_techo'] ?> con asignación ·<?= (int) $costo['dependencias_sobre_techo'] ?> sobre su techo</p>
                                <div class="resumen-detalle">
                                    <div>
                                        <p class="resumen-subtitulo">Proyectos del PDI</p>
                                        <div class="resumen-fila"><span>Con presupuesto</span><strong><?= (int) $costo['proyectos_con_presupuesto'] ?> de <?= (int) $costo['proyectos_total'] ?></strong></div>
                                        <div class="resumen-barra-mini resumen-barra-ancha"><div style="width: <?= $costo['proyectos_total'] > 0 ? number_format(($costo['proyectos_con_presupuesto'] / $costo['proyectos_total']) * 100, 2, '.', '') : '0' ?>%;"></div></div>
                                        <div class="resumen-fila"><span>Sin presupuesto</span><strong><?= (int) ($costo['proyectos_total'] - $costo['proyectos_con_presupuesto']) ?></strong></div>
                                    </div>
                                    <div>
                                        <p class="resumen-subtitulo">PAC por trimestre</p>
                                        <?php foreach ($costo['trimestres'] as $trimestre): ?>
                                        <div class="resumen-fila">
                                            <span><?= $trimestre['etiqueta'] ?></span>
                                            <div class="resumen-barra-mini"><div style="width: <?= number_format($trimestre['porcentaje'], 2, '.', '') ?>%;"></div></div>
                                            <strong><?= number_format($trimestre['porcentaje'], 1, ',', '.') ?>%</strong>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mini-slider-slide inicio-tarjeta" data-mini-slider-slide data-titulo="Autogestión" hidden>
                            <?php if (empty($resumenAutogestion)): ?>
                            <p class="texto-atenuado">No hay años presupuestales activos.</p>
                            <?php endif; ?>
                            <?php foreach ($resumenAutogestion as $ingreso): ?>
                            <div class="resumen-tarjeta">
                                <p class="resumen-anio">Año <?= htmlspecialchars((string) $ingreso['anio']) ?> · <?= (int) $ingreso['dependencias_con_techo'] ?> dependencias con techo</p>
                                <?php if ($ingreso['tope'] <= 0): ?>
                                <p class="texto-atenuado">Sin tope configurado en Autogestión para este año.</p>
                                <?php elseif ($ingreso['dependencias_con_techo'] === 0): ?>
                                <p class="texto-atenuado">Ninguna dependencia tiene techo asignado este año.</p>
                                <?php else: ?>
                                <div class="resumen-kpis">
                                    <div class="resumen-kpi"><span>Tope del módulo</span><strong>$ <?= number_format($ingreso['tope'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Ingresos</span><strong>$ <?= number_format($ingreso['ingresos'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Disponible</span><strong class="<?= $ingreso['disponible'] < 0 ? 'resumen-negativo' : '' ?>">$ <?= number_format($ingreso['disponible'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Alcanzado</span><strong><?= number_format($ingreso['porcentaje'], 1, ',', '.') ?>%</strong></div>
                                </div>
                                <div class="barra-progreso">
                                    <div class="barra-progreso-relleno" style="width: <?= number_format($ingreso['porcentaje'], 2, '.', '') ?>%;"></div>
                                </div>
                                <p class="texto-atenuado resumen-pie"><?= (int) $ingreso['dependencias_con_ingreso'] ?> de <?= (int) $ingreso['dependencias_con_techo'] ?> con ingresos registrados</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mini-slider-slide inicio-tarjeta" data-mini-slider-slide data-titulo="Postgrado" hidden>
                            <?php if (empty($resumenPostgrado)): ?>
                            <p class="texto-atenuado">No hay años presupuestales activos.</p>
                            <?php endif; ?>
                            <?php foreach ($resumenPostgrado as $ingreso): ?>
                            <div class="resumen-tarjeta">
                                <p class="resumen-anio">Año <?= htmlspecialchars((string) $ingreso['anio']) ?> · <?= (int) $ingreso['dependencias_con_techo'] ?> dependencias con techo</p>
                                <?php if ($ingreso['tope'] <= 0): ?>
                                <p class="texto-atenuado">Sin tope configurado en Autogestión para este año.</p>
                                <?php elseif ($ingreso['dependencias_con_techo'] === 0): ?>
                                <p class="texto-atenuado">Ninguna dependencia tiene techo asignado este año.</p>
                                <?php else: ?>
                                <div class="resumen-kpis">
                                    <div class="resumen-kpi"><span>Tope del módulo</span><strong>$ <?= number_format($ingreso['tope'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Ingresos</span><strong>$ <?= number_format($ingreso['ingresos'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Disponible</span><strong class="<?= $ingreso['disponible'] < 0 ? 'resumen-negativo' : '' ?>">$ <?= number_format($ingreso['disponible'], 0, ',', '.') ?></strong></div>
                                    <div class="resumen-kpi"><span>Alcanzado</span><strong><?= number_format($ingreso['porcentaje'], 1, ',', '.') ?>%</strong></div>
                                </div>
                                <div class="barra-progreso">
                                    <div class="barra-progreso-relleno" style="width: <?= number_format($ingreso['porcentaje'], 2, '.', '') ?>%;"></div>
                                </div>
                                <p class="texto-atenuado resumen-pie"><?= (int) $ingreso['dependencias_con_ingreso'] ?> de <?= (int) $ingreso['dependencias_con_techo'] ?> con ingresos registrados</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (empty($diapositivasMensaje)): ?>
                        <div class="mini-slider-slide inicio-tarjeta" data-mini-slider-slide data-titulo="Mensaje global" hidden>
                            <p class="texto-atenuado">No hay ningún mensaje global configurado.</p>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($diapositivasMensaje as $diapositiva): ?>
                        <div class="mini-slider-slide inicio-tarjeta" data-mini-slider-slide data-titulo="Mensaje global" hidden>
                            <?php if ($diapositiva['total'] > 1): ?>
                            <p class="inicio-indicador-fila"><span class="inicio-indicador"><?= $diapositiva['indice'] ?>/<?= $diapositiva['total'] ?></span></p>
                            <?php endif; ?>
                            <div class="mensaje-global-contenido markdown" data-markdown="<?= htmlspecialchars($diapositiva['texto'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mini-slider-puntos">
                        <?php for ($punto = 0; $punto < $totalDiapositivas; $punto++): ?>
                        <button type="button" class="mini-slider-punto<?= $punto === 0 ? ' activo' : '' ?>" data-mini-slider-punto="<?= $punto ?>" aria-label="Diapositiva <?= $punto + 1 ?>"></button>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
        </div>

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
                        <td><?= number_format($solicitud['valor'], 2, ',', '.') ?></td>
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

<script src="publico/js/vendor/marked.min.js"></script>
<script src="publico/js/editor-markdown.js"></script>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
