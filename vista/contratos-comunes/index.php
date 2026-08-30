<?php $tituloPagina = 'Contratos comunes'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="tarjeta-encabezado">
            <h1>Contratos comunes</h1>
            <?php $csvRuta = 'contratos-comunes'; $csvEtiqueta = 'Contratos comunes'; require __DIR__ . '/../parciales/csv-configuracion.php'; ?>
        </div>
        <p class="texto-atenuado">Catálogo de contratos comunes (BCC) que se pueden elegir en el campo "Contratos comunes" al registrar un gasto.</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=contratos-comunes" class="form-agregar">
            <input type="text" name="codigo" placeholder="Código (ej. BCC-ALOJAMIENTO)" maxlength="60" required>
            <input type="text" name="descripcion" placeholder="Descripción" required>
            <button type="submit">Agregar</button>
        </form>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contratosComunes as $contrato): ?>
                    <tr>
                        <td><?= htmlspecialchars($contrato['codigo']) ?></td>
                        <td><?= htmlspecialchars($contrato['descripcion']) ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=contratos-comunes" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $contrato['id'] ?>">
                                <button type="submit" class="interruptor <?= $contrato['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $contrato['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $contrato['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contratosComunes)): ?>
                    <tr>
                        <td colspan="3">No hay contratos comunes registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
