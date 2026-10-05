<?php $tituloPagina = 'Sedes'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="tarjeta-encabezado">
            <h1>Sedes</h1>
            <?php $csvRuta = 'sedes'; $csvEtiqueta = 'Sedes'; require __DIR__ . '/../parciales/csv-configuracion.php'; ?>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=sedes" class="form-agregar">
            <input type="text" name="codigo" placeholder="Código (ej. N)" maxlength="20" required>
            <input type="text" name="nit" placeholder="NIT (dos dígitos, 00 a 09)" pattern="0[0-9]" maxlength="2" title="Dos dígitos que empiezan por 0 (00 a 09)" required>
            <input type="text" name="nombre" placeholder="Nombre de la sede" required>
            <button type="submit">Agregar</button>
        </form>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>NIT</th>
                        <th>Nombre</th>
                        <th>Creado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sedes as $sede): ?>
                    <tr>
                        <td><input type="text" name="codigo" form="form-sede-<?= (int) $sede['id'] ?>" value="<?= htmlspecialchars($sede['codigo']) ?>" maxlength="20" required aria-label="Código"></td>
                        <td><input type="text" name="nit" form="form-sede-<?= (int) $sede['id'] ?>" value="<?= htmlspecialchars($sede['nit']) ?>" pattern="0[0-9]" maxlength="2" required aria-label="NIT" title="Dos dígitos que empiezan por 0 (00 a 09)"></td>
                        <td><input type="text" name="nombre" form="form-sede-<?= (int) $sede['id'] ?>" value="<?= htmlspecialchars($sede['nombre']) ?>" maxlength="150" required aria-label="Nombre"></td>
                        <td><?= htmlspecialchars($sede['creado_en']) ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=sedes" id="form-sede-<?= (int) $sede['id'] ?>">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="id" value="<?= (int) $sede['id'] ?>">
                                <button type="submit" class="boton-accion boton-accion-editar">Guardar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sedes)): ?>
                    <tr>
                        <td colspan="5">No hay sedes registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
