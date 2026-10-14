<?php $tituloPagina = 'Exportar presupuesto final'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Exportar presupuesto final</h1>
        <p class="texto-atenuado">Gastos enviados de gastos principales y de autogestión (Extensión, Postgrado y Unidad de Salud), por rubro, centro de costo y proyecto.</p>

        <?php if ($anioSeleccionado === null): ?>
        <p class="mensaje-error">No hay años presupuestales activos.</p>
        <?php else: ?>

        <form method="GET" action="index.php" class="form-agregar">
            <input type="hidden" name="ruta" value="presupuesto-final">
            <select name="anio_id" onchange="this.form.submit()">
                <?php foreach ($anios as $anio): ?>
                <option value="<?= (int) $anio['id'] ?>"<?= (int) $anio['id'] === (int) $anioSeleccionado['id'] ? ' selected' : '' ?>><?= (int) $anio['anio'] ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <p>
            Filas a exportar: <strong><?= count($resumen['filas']) ?></strong> ·
            Valor total: <strong><?= number_format($resumen['total'], 2, ',', '.') ?></strong>
        </p>

        <?php if ($resumen['omitidas'] > 0): ?>
        <p class="mensaje-error"><?= (int) $resumen['omitidas'] ?> gasto(s) enviado(s) se omiten porque no tienen rubro, sede, dependencia o proyecto válidos.</p>
        <?php endif; ?>

        <a href="index.php?ruta=presupuesto-final-exportar&anio_id=<?= (int) $anioSeleccionado['id'] ?>" class="boton-icono-accion" data-tooltip="Exportar presupuesto final (.xlsx)" title="Exportar presupuesto final (.xlsx)"><svg class="svg-exportar" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></a>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
