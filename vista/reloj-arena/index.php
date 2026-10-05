<?php $tituloPagina = 'Reloj de arena'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Reloj de arena</h1>
        <p class="texto-atenuado">Define la fecha de inicio y la fecha de cierre que se usan para el reloj de arena del dashboard.</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=reloj-arena" class="form-agregar">
            <input type="hidden" name="formulario" value="dashboard">
            <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($configuracion['fecha_inicio'] ?? '') ?>" required>
            <input type="date" name="fecha_cierre" value="<?= htmlspecialchars($configuracion['fecha_cierre'] ?? '') ?>" required>
            <button type="submit">Guardar</button>
        </form>
    </div>

    <div class="tarjeta">
        <h1>Reloj de arena del Consejo Superior</h1>
        <p class="texto-atenuado">Define las fechas del reloj de arena que ven en su inicio los usuarios del Consejo Superior.</p>

        <?php if (!empty($errorConsejo)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($errorConsejo) ?></p>
        <?php endif; ?>

        <?php if (!empty($exitoConsejo)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exitoConsejo) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=reloj-arena" class="form-agregar">
            <input type="hidden" name="formulario" value="consejo">
            <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($configuracionConsejo['fecha_inicio'] ?? '') ?>" required>
            <input type="date" name="fecha_cierre" value="<?= htmlspecialchars($configuracionConsejo['fecha_cierre'] ?? '') ?>" required>
            <button type="submit">Guardar</button>
        </form>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
