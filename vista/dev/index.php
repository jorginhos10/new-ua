<?php $tituloPagina = 'Dev'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Dev</h1>
                <p class="texto-atenuado">
                    Zona de pruebas para landings y vistas nuevas. Pueden tomar datos reales de la
                    plataforma como referencia, pero quedan aparte del sistema: nada de aquí
                    reemplaza ni actualiza un componente real hasta que se pida explícitamente.
                </p>
            </div>
        </div>

        <?php if (empty($vistas)): ?>
        <p class="texto-atenuado">Aún no hay vistas de prueba registradas. Cuando se defina la primera, aparecerá aquí como una tarjeta.</p>
        <?php else: ?>
        <div class="box-items-config">
            <?php foreach ($vistas as $slug => $vista): ?>
            <a href="<?= htmlspecialchars($vista['href'] ?? ('index.php?ruta=dev-vista&v=' . urlencode($slug))) ?>" class="tarjeta-modulo">
                <div class="tarjeta-modulo-cabecera">
                    <h2><?= htmlspecialchars($vista['titulo']) ?></h2>
                </div>
                <p class="texto-atenuado"><?= htmlspecialchars($vista['descripcion']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
