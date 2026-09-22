<?php $tituloPagina = 'Inicio'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Bienvenido, <?= htmlspecialchars($nombreUsuario) ?></h1>
        <p class="texto-atenuado">Rol: Formulador</p>

        <?php if ($dentroDeVentana): ?>
        <p class="mensaje-exito">
            El formulario de necesidades está habilitado
            <?php if ($configuracionFormulador !== null): ?>
            hasta el <?= htmlspecialchars($configuracionFormulador['fecha_cierre']) ?>
            <?php endif; ?>
            .
        </p>
        <a href="index.php?ruta=perfil-proyectos" class="boton-enviar" style="display: inline-block; width: auto; text-decoration: none;">Ir a Perfil de proyectos</a>
        <?php else: ?>
        <p class="mensaje-error">
            El formulario de necesidades no está habilitado en este momento.
            <?php if ($configuracionFormulador !== null): ?>
            El plazo es del <?= htmlspecialchars($configuracionFormulador['fecha_inicio']) ?> al <?= htmlspecialchars($configuracionFormulador['fecha_cierre']) ?>.
            <?php endif; ?>
        </p>
        <?php endif; ?>

        <p class="texto-atenuado" style="margin-top: 1rem;">
            Tienes <strong><?= (int) $totalNecesidades ?></strong> necesidad<?= $totalNecesidades === 1 ? '' : 'es' ?> registrada<?= $totalNecesidades === 1 ? '' : 's' ?>.
        </p>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
