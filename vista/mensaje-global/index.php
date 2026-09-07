<?php $tituloPagina = 'Mensaje global'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <h1>Mensaje global</h1>
        <p class="texto-atenuado">
            Este mensaje se muestra en el Dashboard a los usuarios que no pueden ver el resumen de gastos
            (es decir, a todos los administradores que no son superadmin), en el mismo espacio que ocupa ese widget.
        </p>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php?ruta=mensaje-global">
            <div class="campo">
                <label for="contenido">Contenido</label>
                <textarea id="contenido" name="contenido" rows="5" maxlength="2000" placeholder="Ej: Recuerda diligenciar el formulario de necesidades [aquí](https://ejemplo.com/formulario) antes del viernes."><?= htmlspecialchars($mensaje['contenido'] ?? '') ?></textarea>
            </div>
            <p class="texto-atenuado" style="font-size: 0.8rem; margin-top: 0.4rem;">
                Para agregar un hipervínculo usa la sintaxis <code>[texto del enlace](https://url)</code>. El resto del texto se muestra tal cual, sin necesidad de HTML.
                Déjalo vacío para no mostrar ningún mensaje.
            </p>
            <button type="submit" style="margin-top: 1rem;">Guardar</button>
        </form>
    </div>

    <?php if (!empty($mensaje['contenido'])): ?>
    <div class="tarjeta">
        <h1>Vista previa</h1>
        <div class="mensaje-global-contenido"><?= MensajeGlobal::renderizar($mensaje['contenido']) ?></div>
    </div>
    <?php endif; ?>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
