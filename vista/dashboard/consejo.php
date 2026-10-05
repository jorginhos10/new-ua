<?php
$tituloPagina = 'Dashboard';
require __DIR__ . '/../parciales/encabezado.php';

// Cada mensaje del Consejo Superior es una diapositiva; si es largo, se reparte en varias (1/2, 2/2...).
$diapositivasMensaje = [];
foreach ($mensajesGlobales ?? [] as $mensajeGlobalFila) {
    $partes = MensajeGlobal::paginar($mensajeGlobalFila['contenido']);
    foreach ($partes as $indiceParte => $parte) {
        $diapositivasMensaje[] = ['texto' => $parte, 'indice' => $indiceParte + 1, 'total' => count($partes)];
    }
}
$totalDiapositivas = max(1, count($diapositivasMensaje));
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
                            <p class="texto-atenuado">El reloj de arena aún no tiene fechas configuradas.</p>
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
                        <h2 class="mini-slider-titulo" data-mini-slider-titulo>Mensaje global</h2>
                        <div class="mini-slider-nav">
                            <button type="button" class="mini-slider-flecha" data-mini-slider-prev aria-label="Anterior">&#8249;</button>
                            <button type="button" class="mini-slider-flecha" data-mini-slider-next aria-label="Siguiente">&#8250;</button>
                        </div>
                    </div>

                    <div class="inicio-carrusel-diapositivas">
                        <?php if (empty($diapositivasMensaje)): ?>
                        <div class="mini-slider-slide inicio-tarjeta" data-mini-slider-slide data-titulo="Mensaje global">
                            <p class="texto-atenuado">No hay ningún mensaje global configurado.</p>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($diapositivasMensaje as $indiceDiapositiva => $diapositiva): ?>
                        <div class="mini-slider-slide inicio-tarjeta" data-mini-slider-slide data-titulo="Mensaje global"<?= $indiceDiapositiva > 0 ? ' hidden' : '' ?>>
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
    </div>

<script src="publico/js/vendor/marked.min.js"></script>
<script src="publico/js/editor-markdown.js"></script>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
