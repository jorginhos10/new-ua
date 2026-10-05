<?php $tituloPagina = 'Mensaje global'; require __DIR__ . '/../parciales/encabezado.php';
$etiquetaAudiencia = $audiencia === 'consejo_superior' ? 'Consejo Superior' : 'administradores';
?>

    <nav class="pestanas">
        <a href="index.php?ruta=mensaje-global&audiencia=administrador" class="pestana<?= $audiencia === 'administrador' ? ' activa' : '' ?>">Administradores</a>
        <a href="index.php?ruta=mensaje-global&audiencia=consejo_superior" class="pestana<?= $audiencia === 'consejo_superior' ? ' activa' : '' ?>">Consejo Superior</a>
    </nav>

    <div class="tarjeta">
        <div class="mensaje-global-cabecera">
            <h1>Mensajes globales</h1>
            <button type="button" id="boton-nuevo-mensaje">Nuevo mensaje</button>
        </div>
        <p class="texto-atenuado">
            Cada mensaje es una diapositiva del carrusel del inicio de <?= htmlspecialchars($etiquetaAudiencia) ?>.
            Admite formato Markdown. Si un mensaje es largo, se reparte en varias diapositivas.
        </p>

        <?php if (!empty($aviso)): ?>
            <p class="<?= $aviso['tipo'] === 'exito' ? 'mensaje-exito' : 'mensaje-error' ?>"><?= htmlspecialchars($aviso['texto']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (empty($mensajes)): ?>
    <div class="tarjeta">
        <p class="texto-atenuado">No hay mensajes para <?= htmlspecialchars($etiquetaAudiencia) ?>. Usa "Nuevo mensaje" para agregar el primero.</p>
    </div>
    <?php endif; ?>

    <?php foreach ($mensajes as $indice => $mensaje): ?>
    <div class="tarjeta mensaje-global-item">
        <div class="mensaje-global-cabecera">
            <h2>Mensaje <?= $indice + 1 ?></h2>
            <div class="mensaje-global-acciones">
                <button type="button" class="boton-accion boton-editar-mensaje" data-id="<?= (int) $mensaje['id'] ?>" data-contenido="<?= htmlspecialchars($mensaje['contenido'], ENT_QUOTES, 'UTF-8') ?>">Editar</button>
                <button type="button" class="boton-accion boton-accion-eliminar boton-eliminar-mensaje" data-id="<?= (int) $mensaje['id'] ?>">Eliminar</button>
            </div>
        </div>
        <div class="markdown" data-markdown="<?= htmlspecialchars($mensaje['contenido'], ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
    <?php endforeach; ?>

    <form id="formulario-mensaje-global" method="POST" action="index.php?ruta=mensaje-global" hidden>
        <input type="hidden" name="accion" id="campo-accion-mensaje">
        <input type="hidden" name="id" id="campo-id-mensaje">
        <input type="hidden" name="contenido" id="campo-contenido-mensaje">
        <input type="hidden" name="audiencia" value="<?= htmlspecialchars($audiencia) ?>">
    </form>

    <script src="publico/js/vendor/marked.min.js"></script>
    <script src="publico/js/editor-markdown.js"></script>
    <script>
        (function () {
            var formulario = document.getElementById('formulario-mensaje-global');

            function enviar(accion, id, contenido) {
                document.getElementById('campo-accion-mensaje').value = accion;
                document.getElementById('campo-id-mensaje').value = id || '';
                document.getElementById('campo-contenido-mensaje').value = contenido || '';
                formulario.submit();
            }

            document.getElementById('boton-nuevo-mensaje').addEventListener('click', function () {
                EditorMarkdown.abrir({
                    titulo: 'Nuevo mensaje',
                    contenido: '',
                    maximo: 2000,
                    textoGuardar: 'Agregar mensaje',
                    alGuardar: function (texto) { enviar('crear', 0, texto); }
                });
            });

            document.querySelectorAll('.boton-editar-mensaje').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    var id = this.getAttribute('data-id');
                    EditorMarkdown.abrir({
                        titulo: 'Editar mensaje',
                        contenido: this.getAttribute('data-contenido'),
                        maximo: 2000,
                        textoGuardar: 'Guardar cambios',
                        alGuardar: function (texto) { enviar('editar', id, texto); }
                    });
                });
            });

            document.querySelectorAll('.boton-eliminar-mensaje').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    if (confirm('¿Eliminar este mensaje? Dejará de mostrarse en el inicio.')) {
                        enviar('eliminar', this.getAttribute('data-id'), '');
                    }
                });
            });
        })();
    </script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
