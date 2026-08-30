<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Regístrate</title>
    <link rel="stylesheet" href="publico/css/estilo.css">
</head>
<body class="pagina-auth">
    <div class="contenedor-login contenedor-login-ancho">
        <h1 class="visualmente-oculto">Regístrate</h1>
        <div class="logo-institucional">
            <img src="assets/img/logo.png" alt="Universidad del Atlántico">
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=registro" class="form-2col">
            <label for="nombre">Nombre completo</label>
            <input class="campo-ancho" type="text" id="nombre" name="nombre" placeholder="Nombre completo" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required autofocus>

            <label for="facultad_buscador">Facultad</label>
            <div class="selector-buscable" id="selector-facultad">
                <input type="text" id="facultad_buscador" class="selector-buscable-input" placeholder="Selecciona una facultad" value="<?= htmlspecialchars($_POST['facultad'] ?? '') ?>" autocomplete="off" required>
                <input type="hidden" name="facultad" id="facultad" value="<?= htmlspecialchars($_POST['facultad'] ?? '') ?>">
                <div class="selector-buscable-lista" id="facultad_lista">
                    <?php foreach ($facultades as $facultadOpcion): ?>
                    <div class="selector-buscable-opcion" data-id="<?= htmlspecialchars($facultadOpcion['nombre']) ?>" data-texto="<?= htmlspecialchars($facultadOpcion['nombre']) ?>"><?= htmlspecialchars($facultadOpcion['nombre']) ?></div>
                    <?php endforeach; ?>
                    <div class="selector-buscable-vacio">Sin resultados.</div>
                </div>
            </div>

            <label for="estamento_buscador">Estamento</label>
            <div class="selector-buscable" id="selector-estamento">
                <input type="text" id="estamento_buscador" class="selector-buscable-input" placeholder="Selecciona un estamento" autocomplete="off" required>
                <input type="hidden" name="estamento_id" id="estamento_id">
                <div class="selector-buscable-lista" id="estamento_lista">
                    <?php foreach ($estamentos as $estamentoOpcion): ?>
                    <div class="selector-buscable-opcion" data-id="<?= (int) $estamentoOpcion['id'] ?>" data-texto="<?= htmlspecialchars($estamentoOpcion['nombre']) ?>"><?= htmlspecialchars($estamentoOpcion['nombre']) ?></div>
                    <?php endforeach; ?>
                    <div class="selector-buscable-vacio">Sin resultados.</div>
                </div>
            </div>

            <label for="correo">Correo electrónico</label>
            <input class="campo-ancho" type="email" id="correo" name="correo" placeholder="Correo electrónico" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

            <label for="password">Contraseña</label>
            <div class="campo-password">
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
                <button type="button" class="boton-mostrar-password" data-objetivo="password" aria-label="Mostrar contraseña">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>

            <label for="confirmar">Confirmar contraseña</label>
            <div class="campo-password">
                <input type="password" id="confirmar" name="confirmar" placeholder="Confirmar contraseña" required>
                <button type="button" class="boton-mostrar-password" data-objetivo="confirmar" aria-label="Mostrar contraseña">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>

            <button class="campo-ancho" type="submit">&#8594; Crear cuenta</button>
        </form>

        <p class="enlace-secundario">
            <a href="index.php?ruta=login">¿Ya tienes cuenta? Inicia sesión</a>
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

        document.querySelectorAll('.selector-buscable').forEach(function (contenedor) {
            var input = contenedor.querySelector('.selector-buscable-input');
            var oculto = contenedor.querySelector('input[type="hidden"]');
            var lista = contenedor.querySelector('.selector-buscable-lista');
            var vacio = contenedor.querySelector('.selector-buscable-vacio');
            var opciones = Array.prototype.slice.call(contenedor.querySelectorAll('.selector-buscable-opcion'));

            if (!input || !oculto || !lista) {
                return;
            }

            function filtrar() {
                var texto = input.value.trim().toLowerCase();
                var algunaVisible = false;

                opciones.forEach(function (opcion) {
                    var coincide = opcion.dataset.texto.toLowerCase().indexOf(texto) !== -1;
                    opcion.classList.toggle('oculta', !coincide);

                    if (coincide) {
                        algunaVisible = true;
                    }
                });

                if (vacio) {
                    vacio.style.display = algunaVisible ? 'none' : 'block';
                }
            }

            function abrir() {
                lista.classList.add('abierta');
                filtrar();
            }

            function cerrar() {
                lista.classList.remove('abierta');
            }

            input.addEventListener('focus', abrir);

            input.addEventListener('input', function () {
                oculto.value = '';
                abrir();
            });

            lista.addEventListener('mousedown', function (evento) {
                evento.preventDefault();
            });

            lista.addEventListener('click', function (evento) {
                var opcion = evento.target.closest('.selector-buscable-opcion');

                if (!opcion) {
                    return;
                }

                input.value = opcion.dataset.texto;
                oculto.value = opcion.dataset.id;
                cerrar();
            });

            document.addEventListener('click', function (evento) {
                if (!contenedor.contains(evento.target)) {
                    cerrar();
                }
            });
        });
    </script>
</body>
</html>
