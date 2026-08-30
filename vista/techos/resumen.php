<?php $tituloPagina = 'Resumen de techos'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Resumen de techos</h1>
                <p class="texto-atenuado">Distribución del techo presupuestal entre tus dependencias directas.</p>
            </div>
            <a href="index.php?ruta=techos&anio_id=<?= (int) $anioSeleccionadoId ?>" class="boton-accion">&larr; Volver a Techos</a>
        </div>

        <?php if (empty($aniosActivos)): ?>
        <p class="mensaje-error">Primero debes crear un año presupuestal activo en <a href="index.php?ruta=anios-presupuestales">Configuraciones &gt; Año presupuestal</a>.</p>
        <?php else: ?>

        <?php if (count($aniosActivos) >= 2): ?>
            <form method="GET" action="index.php" class="form-filtro-anio">
                <input type="hidden" name="ruta" value="resumen-techos">
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

        <?php if (empty($segmentos)): ?>
        <p class="texto-atenuado">Todavía no hay techos asignados a tus dependencias para mostrar en la gráfica.</p>
        <?php else: ?>

        <div class="resumen-techos-panel">
            <div class="grafica-anillo-envoltorio">
                <svg viewBox="0 0 100 100" class="grafica-anillo">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="var(--color-borde)" stroke-width="16"></circle>
                    <?php foreach ($segmentos as $segmento): ?>
                    <circle
                        cx="50" cy="50" r="40" fill="none"
                        stroke="<?= htmlspecialchars($segmento['color']) ?>"
                        stroke-width="16"
                        stroke-dasharray="<?= htmlspecialchars($segmento['dasharray']) ?>"
                        stroke-dashoffset="<?= htmlspecialchars((string) $segmento['dashoffset']) ?>"
                        transform="rotate(-90 50 50)"
                    ></circle>
                    <?php endforeach; ?>
                </svg>
                <div class="grafica-anillo-centro">
                    <span class="grafica-anillo-total"><?= number_format($totalTechos, 2) ?></span>
                    <span class="texto-atenuado">Total techos</span>
                </div>
            </div>

            <ul class="leyenda-grafica-anillo">
                <?php foreach ($segmentos as $segmento): ?>
                <li>
                    <span class="leyenda-color" style="background: <?= htmlspecialchars($segmento['color']) ?>;"></span>
                    <span class="leyenda-nombre"><?= htmlspecialchars($segmento['nombre']) ?></span>
                    <span class="leyenda-valor"><?= number_format($segmento['techo'], 2) ?> (<?= number_format($segmento['porcentaje'], 1) ?>%)</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php endif; ?>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
