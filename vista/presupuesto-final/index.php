<?php $tituloPagina = 'Exportar presupuesto final'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Exportar presupuesto final</h1>
        <p class="texto-atenuado">Gastos enviados de gastos principales y de autogestión (Extensión, Postgrado, Unidad de Salud y Sin Excedentes), por rubro, centro de costo y proyecto.</p>

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

        <a href="index.php?ruta=presupuesto-final-exportar&anio_id=<?= (int) $anioSeleccionado['id'] ?>" class="boton-enviar" style="display: inline-block; width: auto; text-decoration: none;">Exportar presupuesto final (.xlsx)</a>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
