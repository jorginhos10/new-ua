<?php $tituloPagina = 'Sublíneas de inversión'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Sublíneas de inversión</h1>
        <p class="texto-atenuado">Catálogo de sublíneas de inversión, condicionadas a una línea de inversión, que se pueden elegir al registrar un proyecto en Perfil de proyectos.</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if (empty($lineasInversion)): ?>
            <p class="mensaje-error">Primero debes crear al menos una línea de inversión activa en <a href="index.php?ruta=lineas-inversion">Configuraciones &gt; Líneas de inversión</a>.</p>
        <?php else: ?>
            <form method="POST" action="index.php?ruta=sublineas-inversion" class="form-agregar">
                <input type="text" name="codigo" placeholder="Código (ej. SLI1)" maxlength="20" required>
                <input type="text" name="nombre" placeholder="Nombre de la sublínea" required>
                <input type="text" name="descripcion" placeholder="Descripción (se muestra al pasar el mouse)" required>
                <select name="linea_inversion_id" required>
                    <option value="">Selecciona una línea de inversión</option>
                    <?php foreach ($lineasInversion as $linea): ?>
                    <option value="<?= (int) $linea['id'] ?>"><?= htmlspecialchars($linea['codigo'] . ' - ' . $linea['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Agregar</button>
            </form>
        <?php endif; ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Línea de inversión</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sublineasInversion as $sublinea): ?>
                    <tr>
                        <td><?= htmlspecialchars($sublinea['codigo']) ?></td>
                        <td><?= htmlspecialchars($sublinea['nombre']) ?></td>
                        <td><?= htmlspecialchars($sublinea['descripcion']) ?></td>
                        <td><?= htmlspecialchars($sublinea['linea_codigo'] . ' - ' . $sublinea['linea_nombre']) ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=sublineas-inversion" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= (int) $sublinea['id'] ?>">
                                <button type="submit" class="interruptor <?= $sublinea['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $sublinea['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $sublinea['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sublineasInversion)): ?>
                    <tr>
                        <td colspan="5">No hay sublíneas de inversión registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
