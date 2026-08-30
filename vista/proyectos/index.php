<?php $tituloPagina = 'Proyectos'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Proyectos</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (empty($motores)): ?>
            <p class="mensaje-error">Primero debes crear al menos un motor en el módulo Motores.</p>
        <?php else: ?>
            <form method="POST" action="index.php?ruta=proyectos" class="form-agregar">
                <input type="text" name="codigo" placeholder="Código (ej. P1)" maxlength="10" required>
                <input type="text" name="nombre" placeholder="Nombre del proyecto" required>
                <div class="selector-buscable" id="motor_id-selector">
                    <input type="text" id="motor_id_buscador" class="selector-buscable-input" placeholder="Buscar motor..." autocomplete="off">
                    <input type="hidden" name="motor_id" id="motor_id">
                    <div class="selector-buscable-lista" id="motor_id_lista">
                        <?php foreach ($motores as $motor): ?>
                        <div class="selector-buscable-opcion" data-id="<?= (int) $motor['id'] ?>" data-texto="<?= htmlspecialchars($motor['codigo'] . ' - ' . $motor['nombre']) ?>">
                            <?= htmlspecialchars($motor['codigo'] . ' - ' . $motor['nombre']) ?>
                        </div>
                        <?php endforeach; ?>
                        <div class="selector-buscable-vacio">Sin resultados.</div>
                    </div>
                </div>
                <button type="submit">Agregar</button>
            </form>
        <?php endif; ?>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>NIT</th>
                    <th>Nombre</th>
                    <th>Motor</th>
                    <th>Línea</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proyectos as $proyecto): ?>
                <tr>
                    <td><?= htmlspecialchars($proyecto['codigo']) ?></td>
                    <td><?= $proyecto['nit'] !== null ? htmlspecialchars($proyecto['nit']) : '—' ?></td>
                    <td><?= htmlspecialchars($proyecto['nombre']) ?></td>
                    <td><?= htmlspecialchars($proyecto['motor_codigo'] . ' - ' . $proyecto['motor_nombre']) ?></td>
                    <td><?= htmlspecialchars($proyecto['linea_codigo'] . ' - ' . $proyecto['linea_nombre']) ?></td>
                    <td><?= htmlspecialchars($proyecto['creado_en']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($proyectos)): ?>
                <tr>
                    <td colspan="6">No hay proyectos registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
