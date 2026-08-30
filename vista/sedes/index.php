<?php $tituloPagina = 'Sedes'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Sedes</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=sedes" class="form-agregar">
            <input type="text" name="codigo" placeholder="Código (ej. N)" maxlength="20" required>
            <input type="text" name="nombre" placeholder="Nombre de la sede" required>
            <button type="submit">Agregar</button>
        </form>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sedes as $sede): ?>
                <tr>
                    <td><?= htmlspecialchars($sede['codigo']) ?></td>
                    <td><?= htmlspecialchars($sede['nombre']) ?></td>
                    <td><?= htmlspecialchars($sede['creado_en']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($sedes)): ?>
                <tr>
                    <td colspan="3">No hay sedes registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
