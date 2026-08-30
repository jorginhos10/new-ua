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
        <h1>Reloj de arena del formulador</h1>
        <p class="texto-atenuado">Define la fecha de inicio y la fecha de cierre en las que el invitado (formulador) puede registrar necesidades. Fuera de este rango no podrá formular.</p>

        <?php if (!empty($errorFormulador)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($errorFormulador) ?></p>
        <?php endif; ?>

        <?php if (!empty($exitoFormulador)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exitoFormulador) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=reloj-arena" class="form-agregar">
            <input type="hidden" name="formulario" value="formulador">
            <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($configuracionFormulador['fecha_inicio'] ?? '') ?>" required>
            <input type="date" name="fecha_cierre" value="<?= htmlspecialchars($configuracionFormulador['fecha_cierre'] ?? '') ?>" required>
            <button type="submit">Guardar</button>
        </form>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
