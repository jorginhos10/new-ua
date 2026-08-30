<?php $tituloPagina = 'Motores'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Motores</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (empty($lineas)): ?>
            <p class="mensaje-error">Primero debes crear al menos una línea en el módulo Líneas.</p>
        <?php else: ?>
            <form method="POST" action="index.php?ruta=motores" class="form-agregar">
                <input type="text" name="codigo" placeholder="Código (ej. M1)" maxlength="10" required>
                <input type="text" name="nombre" placeholder="Nombre del motor" required>
                <select name="linea_id" required>
                    <option value="">Selecciona una línea</option>
                    <?php foreach ($lineas as $linea): ?>
                    <option value="<?= (int) $linea['id'] ?>"><?= htmlspecialchars($linea['codigo'] . ' - ' . $linea['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Agregar</button>
            </form>
        <?php endif; ?>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Línea</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($motores as $motor): ?>
                <tr>
                    <td><?= htmlspecialchars($motor['codigo']) ?></td>
                    <td><?= htmlspecialchars($motor['nombre']) ?></td>
                    <td><?= htmlspecialchars($motor['linea_codigo'] . ' - ' . $motor['linea_nombre']) ?></td>
                    <td><?= htmlspecialchars($motor['creado_en']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($motores)): ?>
                <tr>
                    <td colspan="4">No hay motores registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
