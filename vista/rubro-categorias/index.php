<?php $tituloPagina = 'Categorías de rubros'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="tarjeta-encabezado">
            <h1>Categorías de rubros</h1>
            <?php $csvRuta = 'rubro-categorias'; $csvEtiqueta = 'Categorías de rubros'; require __DIR__ . '/../parciales/csv-configuracion.php'; ?>
        </div>
        <p class="texto-atenuado">Cada rubro empieza con capítulo.sección (ej. "2.01..."). Marca en qué categorías de la barra lateral debe aparecer cada combinación — así el selector de rubro de cada módulo solo muestra los que le corresponden.</p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=rubro-categorias">
            <div class="tabla-scroll">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Cap</th>
                            <th>Sección</th>
                            <th>Autogestión</th>
                            <th>Egresos</th>
                            <th>Proyectos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><?= htmlspecialchars($categoria['cap']) ?></td>
                            <td><?= htmlspecialchars($categoria['seccion']) ?></td>
                            <td><input type="checkbox" name="autogestion[]" value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['autogestion'] === 1 ? 'checked' : '' ?>></td>
                            <td><input type="checkbox" name="egresos[]" value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['egresos'] === 1 ? 'checked' : '' ?>></td>
                            <td><input type="checkbox" name="proyectos[]" value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['proyectos'] === 1 ? 'checked' : '' ?>></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="5">Todavía no hay rubros registrados.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="boton-enviar">Guardar</button>
        </form>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
