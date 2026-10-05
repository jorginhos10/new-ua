<?php $tituloPagina = 'Inicio'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Bienvenido, <?= htmlspecialchars($nombreUsuario) ?></h1>
        <p class="texto-atenuado">Rol: Formulador</p>

        <?php if (empty($convocatoriasInvitado)): ?>
        <p class="mensaje-error">No tienes convocatorias de proyectos disponibles para tu usuario.</p>
        <?php else: ?>
        <?php foreach ($convocatoriasInvitado as $elemento): $c = $elemento['convocatoria']; $reloj = $elemento['reloj']; ?>
        <p class="<?= $elemento['dentro'] ? 'mensaje-exito' : 'mensaje-error' ?>">
            <strong><?= htmlspecialchars($c['nombre']) ?></strong>:
            <?php if ($elemento['dentro']): ?>
            abierta, faltan <?= (int) $reloj['faltante'] ?> <?= htmlspecialchars($reloj['unidad']) ?> para cerrar (<?= htmlspecialchars($reloj['fecha_cierre']) ?>).
            <?php elseif ($reloj['estado'] === 'pendiente'): ?>
            abre el <?= htmlspecialchars($reloj['fecha_inicio']) ?>.
            <?php else: ?>
            cerrada el <?= htmlspecialchars($reloj['fecha_cierre']) ?>.
            <?php endif; ?>
        </p>
        <?php endforeach; ?>
        <a href="index.php?ruta=perfil-proyectos" class="boton-enviar" style="display: inline-block; width: auto; text-decoration: none;">Ir a Perfil de proyectos</a>
        <?php endif; ?>

        <p class="texto-atenuado" style="margin-top: 1rem;">
            Tienes <strong><?= (int) $totalNecesidades ?></strong> proyecto<?= $totalNecesidades === 1 ? '' : 's' ?> registrado<?= $totalNecesidades === 1 ? '' : 's' ?>.
        </p>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
