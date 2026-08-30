<?php $tituloPagina = 'Mi perfil'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Mi perfil</h1>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=perfil" class="form-necesidad">
            <input type="hidden" name="accion" value="actualizar_datos">

            <div class="campo">
                <label for="perfil-nombre">Nombre *</label>
                <input type="text" id="perfil-nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
            </div>

            <div class="campo">
                <label for="perfil-correo">Correo *</label>
                <input type="email" id="perfil-correo" name="correo" value="<?= htmlspecialchars($usuario['correo']) ?>" required>
            </div>

            <div class="campo">
                <label>Rol</label>
                <input type="text" value="<?= htmlspecialchars(ucfirst((string) $usuario['rol'])) ?>" disabled>
            </div>

            <div class="campo">
                <label>Estamento</label>
                <input type="text" value="<?= htmlspecialchars($_SESSION['usuario_estamento'] ?? '—') ?>" disabled>
            </div>

            <div class="campo">
                <label>Dependencia</label>
                <input type="text" value="<?= htmlspecialchars($dependencia['nombre'] ?? '—') ?>" disabled>
            </div>

            <button type="submit" class="boton-enviar">Guardar cambios</button>
        </form>
    </div>

    <div class="tarjeta">
        <h2>Cambiar contraseña</h2>

        <form method="POST" action="index.php?ruta=perfil" class="form-necesidad">
            <input type="hidden" name="accion" value="actualizar_password">

            <div class="campo">
                <label for="perfil-password-actual">Contraseña actual *</label>
                <input type="password" id="perfil-password-actual" name="password_actual" required>
            </div>

            <div class="campo">
                <label for="perfil-password-nueva">Nueva contraseña *</label>
                <input type="password" id="perfil-password-nueva" name="password_nueva" minlength="6" required>
            </div>

            <div class="campo">
                <label for="perfil-password-confirmar">Confirmar nueva contraseña *</label>
                <input type="password" id="perfil-password-confirmar" name="password_confirmar" minlength="6" required>
            </div>

            <button type="submit" class="boton-enviar">Actualizar contraseña</button>
        </form>
    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
