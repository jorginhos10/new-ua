<?php $tituloPagina = 'Dashboard'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Bienvenido, <?= htmlspecialchars($nombreUsuario) ?></h1>
        <p>Rol: <?= htmlspecialchars($rolUsuario) ?></p>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
