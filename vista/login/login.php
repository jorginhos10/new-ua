<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="publico/css/estilo.css">
</head>
<body class="pagina-auth">
    <div class="contenedor-login">
        <h1 class="visualmente-oculto">Iniciar sesión</h1>
        <div class="logo-institucional">
            <img src="assets/img/logo.png" alt="Universidad del Atlántico">
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=login">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" placeholder="Correo electrónico" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required autofocus>

            <label for="password">Contraseña</label>
            <div class="campo-password">
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
                <button type="button" class="boton-mostrar-password" data-objetivo="password" aria-label="Mostrar contraseña">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>

            <button type="submit">&#8594; Entrar</button>
        </form>

        <p class="enlace-secundario">
            <a href="index.php?ruta=registro">¿No tienes cuenta? Regístrate</a>
        </p>
    </div>

    <script>
        document.querySelectorAll('.boton-mostrar-password').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var campo = document.getElementById(boton.dataset.objetivo);
                if (!campo) {
                    return;
                }
                campo.type = campo.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
</body>
</html>
