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
        <p class="enlace-secundario">
            <a href="#" id="enlace-recuperar-password">Recuperar contraseña</a>
        </p>
    </div>

    <div id="modal-recuperar-password" class="modal-fondo">
        <div class="modal-caja" style="max-width: 420px;">
            <div class="modal-cabecera">
                <h2>Recuperar contraseña</h2>
                <button type="button" id="boton-cerrar-modal-recuperar" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            </div>

            <div class="recuperar-cuerpo">
                <p id="recuperar-mensaje" class="mensaje-error" style="display:none;"></p>

                <div id="recuperar-paso-correo" class="recuperar-paso">
                    <p class="texto-atenuado">Ingresa tu correo electrónico y te enviaremos un código de 9 dígitos.</p>
                    <div class="campo">
                        <label for="recuperar-correo">Correo electrónico</label>
                        <input type="email" id="recuperar-correo" placeholder="Correo electrónico" required>
                    </div>
                    <button type="button" id="boton-recuperar-enviar-codigo" class="boton-enviar">Enviar código</button>
                </div>

                <div id="recuperar-paso-codigo" class="recuperar-paso" style="display:none;">
                    <p class="texto-atenuado">Revisa tu correo e ingresa el código de 9 dígitos junto con tu nueva contraseña.</p>
                    <div class="campo">
                        <label for="recuperar-codigo">Código de 9 dígitos</label>
                        <input type="text" id="recuperar-codigo" inputmode="numeric" maxlength="9" placeholder="000000000" required>
                    </div>
                    <div class="campo">
                        <label for="recuperar-password-nueva">Contraseña nueva</label>
                        <input type="password" id="recuperar-password-nueva" placeholder="Contraseña nueva" required>
                    </div>
                    <div class="campo">
                        <label for="recuperar-password-confirmar">Confirmar contraseña</label>
                        <input type="password" id="recuperar-password-confirmar" placeholder="Confirmar contraseña" required>
                    </div>
                    <button type="button" id="boton-recuperar-confirmar" class="boton-enviar">Restablecer contraseña</button>
                    <p class="enlace-secundario">
                        <a href="#" id="enlace-recuperar-reenviar">¿No te llegó? Enviar otro código</a>
                    </p>
                </div>
            </div>
        </div>
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

        (function () {
            var modal = document.getElementById('modal-recuperar-password');
            var enlaceAbrir = document.getElementById('enlace-recuperar-password');
            var botonCerrar = document.getElementById('boton-cerrar-modal-recuperar');
            var mensaje = document.getElementById('recuperar-mensaje');
            var pasoCorreo = document.getElementById('recuperar-paso-correo');
            var pasoCodigo = document.getElementById('recuperar-paso-codigo');
            var campoCorreo = document.getElementById('recuperar-correo');
            var campoCodigo = document.getElementById('recuperar-codigo');
            var campoPasswordNueva = document.getElementById('recuperar-password-nueva');
            var campoPasswordConfirmar = document.getElementById('recuperar-password-confirmar');
            var botonEnviarCodigo = document.getElementById('boton-recuperar-enviar-codigo');
            var botonConfirmar = document.getElementById('boton-recuperar-confirmar');
            var enlaceReenviar = document.getElementById('enlace-recuperar-reenviar');
            var correoSolicitado = '';

            function mostrarMensaje(texto, esExito) {
                mensaje.textContent = texto;
                mensaje.className = esExito ? 'mensaje-exito' : 'mensaje-error';
                mensaje.style.display = texto ? 'block' : 'none';
            }

            function irAPasoCorreo() {
                pasoCorreo.style.display = '';
                pasoCodigo.style.display = 'none';
                mostrarMensaje('', false);
            }

            function irAPasoCodigo() {
                pasoCorreo.style.display = 'none';
                pasoCodigo.style.display = '';
            }

            function abrirModal() {
                irAPasoCorreo();
                campoCorreo.value = '';
                campoCodigo.value = '';
                campoPasswordNueva.value = '';
                campoPasswordConfirmar.value = '';
                modal.classList.add('abierto');
                campoCorreo.focus();
            }

            function cerrarModal() {
                modal.classList.remove('abierto');
            }

            function solicitarCodigo() {
                var correo = campoCorreo.value.trim();

                if (!correo) {
                    mostrarMensaje('Ingresa tu correo electrónico.', false);
                    return;
                }

                botonEnviarCodigo.disabled = true;
                botonEnviarCodigo.textContent = 'Enviando...';
                mostrarMensaje('', false);

                var datos = new URLSearchParams();
                datos.set('correo', correo);

                fetch('index.php?ruta=recuperar-password-solicitar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: datos.toString()
                })
                    .then(function (respuesta) { return respuesta.json(); })
                    .then(function (resultado) {
                        if (resultado.exito) {
                            correoSolicitado = correo;
                            mostrarMensaje(resultado.mensaje, true);
                            irAPasoCodigo();
                        } else {
                            mostrarMensaje(resultado.mensaje || 'No se pudo enviar el código.', false);
                        }
                    })
                    .catch(function () {
                        mostrarMensaje('No se pudo conectar con el servidor. Intenta de nuevo.', false);
                    })
                    .finally(function () {
                        botonEnviarCodigo.disabled = false;
                        botonEnviarCodigo.textContent = 'Enviar código';
                    });
            }

            function confirmarNuevaPassword() {
                var codigo = campoCodigo.value.trim();
                var password = campoPasswordNueva.value;
                var confirmar = campoPasswordConfirmar.value;

                if (!codigo || !password || !confirmar) {
                    mostrarMensaje('Completa todos los campos.', false);
                    return;
                }

                botonConfirmar.disabled = true;
                botonConfirmar.textContent = 'Guardando...';
                mostrarMensaje('', false);

                var datos = new URLSearchParams();
                datos.set('correo', correoSolicitado);
                datos.set('codigo', codigo);
                datos.set('password', password);
                datos.set('confirmar', confirmar);

                fetch('index.php?ruta=recuperar-password-confirmar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: datos.toString()
                })
                    .then(function (respuesta) { return respuesta.json(); })
                    .then(function (resultado) {
                        mostrarMensaje(resultado.mensaje, resultado.exito);
                        if (resultado.exito) {
                            setTimeout(cerrarModal, 2000);
                        }
                    })
                    .catch(function () {
                        mostrarMensaje('No se pudo conectar con el servidor. Intenta de nuevo.', false);
                    })
                    .finally(function () {
                        botonConfirmar.disabled = false;
                        botonConfirmar.textContent = 'Restablecer contraseña';
                    });
            }

            enlaceAbrir.addEventListener('click', function (evento) {
                evento.preventDefault();
                abrirModal();
            });

            botonCerrar.addEventListener('click', cerrarModal);

            modal.addEventListener('click', function (evento) {
                if (evento.target === modal) {
                    cerrarModal();
                }
            });

            document.addEventListener('keydown', function (evento) {
                if (evento.key === 'Escape') {
                    cerrarModal();
                }
            });

            botonEnviarCodigo.addEventListener('click', solicitarCodigo);
            botonConfirmar.addEventListener('click', confirmarNuevaPassword);

            enlaceReenviar.addEventListener('click', function (evento) {
                evento.preventDefault();
                irAPasoCorreo();
                campoCorreo.value = correoSolicitado;
            });
        })();
    </script>
</body>
</html>
