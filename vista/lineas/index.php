<?php $tituloPagina = 'Líneas'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Líneas</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=lineas" class="form-agregar">
            <input type="text" name="codigo" placeholder="Código (ej. L1)" maxlength="10" required>
            <input type="text" name="nombre" placeholder="Nombre de la línea" required>
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
                <?php foreach ($lineas as $linea): ?>
                <tr>
                    <td><?= htmlspecialchars($linea['codigo']) ?></td>
                    <td><?= htmlspecialchars($linea['nombre']) ?></td>
                    <td><?= htmlspecialchars($linea['creado_en']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($lineas)): ?>
                <tr>
                    <td colspan="3">No hay líneas registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
