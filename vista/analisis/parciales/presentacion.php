<?php
/**
 * Pestaña "Presentación" de ?ruta=analisis: lista de enlaces de OneDrive/SharePoint (presentación,
 * Word, Excel o PDF) que el superadmin administra, cada uno con nombre y posición propia. Al hacer
 * clic se abren incrustados con el visor de Office en una ventana que, a propósito, solo se cierra
 * con la X — no al hacer clic afuera ni con Escape, a diferencia de los demás modales de la app,
 * porque aquí se puede estar navegando varias diapositivas o páginas y un clic afuera por accidente
 * no debe botar la vista. Variables esperadas (ver AnalisisControlador::renderizarPresentacion()):
 * $enlacesPresentacion (cada uno con id, nombre, url, url_incrustada, actualizado_en,
 * actualizado_por_nombre), $puedeEditarPresentacion, $mensajesPresentacion, $vista. El mensaje que
 * explica la sección es un mensaje global más (misma tabla y editor que Configuraciones > Mensajes
 * globales), con su propia audiencia "analisis_presentacion" — se edita desde esa pantalla, acá
 * solo se muestra. Junto al mensaje hay un botón "Actas" (si el usuario tiene esa pestaña permitida)
 * que manda a esa vista, que ya no está en la barra de pestañas; de ahí se vuelve con su propio botón.
 */

$enlacesPresentacion = $enlacesPresentacion ?? [];
$puedeEditarPresentacion = $puedeEditarPresentacion ?? false;
$mensajesPresentacion = $mensajesPresentacion ?? [];
$totalEnlaces = count($enlacesPresentacion);
// Actas ya no es una pestaña aparte: se entra desde este botón (junto al mensaje global) y esa
// vista trae su propio botón "Volver" hacia acá.
$muestraBotonActas = in_array('actas', AccesoAnalisis::actual()['pestanas'], true);
?>

<style>
    .presentacion-analisis {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        padding: 1rem;
        margin: 0.5rem;
    }

    .presentacion-cuerpo {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }

    .presentacion-seccion {
        flex-shrink: 0;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--color-borde);
    }

    .presentacion-seccion-final {
        flex: 1;
        min-height: 0;
        border-bottom: none;
        padding-bottom: 0;
        margin-bottom: 0;
    }

    .presentacion-info-banner {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .presentacion-info-banner-cuerpo {
        flex: 1;
        min-width: 0;
    }

    .presentacion-info-banner .mensaje-global-contenido {
        font-size: 0.85rem;
    }

    .presentacion-info-banner .mensaje-global-contenido:not(:last-child) {
        margin-bottom: 0.6rem;
    }

    .presentacion-info-banner-editar {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        flex-shrink: 0;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--color-primario);
        text-decoration: none;
        white-space: nowrap;
    }

    .presentacion-info-banner-editar:hover {
        text-decoration: underline;
    }

    .presentacion-info-banner-acciones {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .presentacion-boton-actas {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        background: var(--color-superficie);
        color: var(--color-texto);
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .presentacion-boton-actas:hover {
        border-color: var(--color-primario);
        color: var(--color-primario);
    }

    .presentacion-analisis-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        align-items: center;
    }

    .presentacion-analisis-form input[type="text"] {
        padding: 0.45rem 0.6rem;
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        background: var(--color-superficie);
        color: var(--color-texto);
        font-size: 0.85rem;
    }

    .presentacion-analisis-form input[name="nombre_enlace"] {
        width: 220px;
    }

    .presentacion-analisis-form input[name="url_enlace"] {
        flex: 1;
        min-width: 260px;
    }

    .presentacion-analisis-boton {
        padding: 0.45rem 0.9rem;
        border: none;
        border-radius: 6px;
        background: var(--color-primario);
        color: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .presentacion-enlace-fila {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 0;
        border-bottom: 1px solid var(--color-borde);
    }

    .presentacion-enlace-fila:last-child {
        border-bottom: none;
    }

    .presentacion-enlace-fila input[type="text"] {
        padding: 0.4rem 0.55rem;
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        background: var(--color-superficie);
        color: var(--color-texto);
        font-size: 0.82rem;
    }

    .presentacion-enlace-fila input[data-campo="nombre"] {
        width: 200px;
    }

    .presentacion-enlace-fila input[data-campo="url"] {
        flex: 1;
        min-width: 220px;
    }

    .presentacion-enlace-orden {
        display: flex;
        flex-direction: column;
        gap: 1px;
        flex-shrink: 0;
    }

    .presentacion-enlace-orden button {
        border: 1px solid var(--color-borde);
        background: var(--color-superficie);
        color: var(--color-texto-tenue);
        width: 22px;
        height: 18px;
        line-height: 1;
        font-size: 0.65rem;
        padding: 0;
        cursor: pointer;
        border-radius: 4px;
    }

    .presentacion-enlace-orden button:disabled {
        opacity: 0.3;
        cursor: default;
    }

    .presentacion-enlace-orden button:not(:disabled):hover {
        color: var(--color-primario);
        border-color: var(--color-primario);
    }

    .presentacion-enlace-actualizado {
        font-size: 0.72rem;
        color: var(--color-texto-tenue);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .presentacion-lista-usuario {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        max-width: 520px;
    }

    .presentacion-enlace-boton {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.8rem 1rem;
        border: 1px solid var(--color-borde);
        border-radius: 8px;
        background: var(--color-superficie);
        color: var(--color-texto);
        font-size: 0.9rem;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
        width: 100%;
    }

    .presentacion-enlace-boton:hover {
        border-color: var(--color-primario);
        color: var(--color-primario);
    }

    .presentacion-enlace-boton svg {
        flex-shrink: 0;
    }

    .presentacion-analisis-vacio {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border: 1px dashed var(--color-borde);
        border-radius: 8px;
        color: var(--color-texto-tenue);
        font-size: 0.9rem;
        text-align: center;
        padding: 2rem 1rem;
    }

    #presentacion-visor-modal {
        padding: 0.75rem;
    }

    .presentacion-visor-caja {
        width: 100%;
        max-width: none;
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow: hidden;
    }

    .presentacion-visor-cabecera {
        margin: 0;
        padding: 0.45rem 0.8rem;
        border-radius: var(--radio) var(--radio) 0 0;
    }

    .presentacion-visor-cabecera h2 {
        font-size: 0.9rem;
    }

    .presentacion-visor-cabecera .modal-cerrar {
        font-size: 1.2rem;
        padding: 0 0.4rem;
    }

    .presentacion-visor-marco {
        flex: 1;
        min-height: 0;
    }

    .presentacion-visor-marco iframe {
        display: block;
        width: 100%;
        height: 100%;
        border: 0;
    }
</style>

<div class="tarjeta presentacion-analisis">
    <div class="presentacion-cuerpo">
        <?php if (!empty($mensajesPresentacion) || $puedeEditarPresentacion || $muestraBotonActas): ?>
        <div class="presentacion-seccion presentacion-info-banner">
            <div class="presentacion-info-banner-cuerpo">
                <?php if (empty($mensajesPresentacion)): ?>
                <p class="texto-atenuado" style="margin: 0;">No hay ningún mensaje configurado para explicar esta sección.</p>
                <?php else: ?>
                <?php foreach ($mensajesPresentacion as $mensaje): ?>
                <div class="mensaje-global-contenido markdown" data-markdown="<?= htmlspecialchars($mensaje['contenido'], ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="presentacion-info-banner-acciones">
                <?php if ($muestraBotonActas): ?>
                <a class="presentacion-boton-actas" href="<?= htmlspecialchars(analisisUrl('actas', $vista)) ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    Actas
                </a>
                <?php endif; ?>
                <?php if ($puedeEditarPresentacion): ?>
                <a class="presentacion-info-banner-editar" href="index.php?ruta=mensaje-global&audiencia=analisis_presentacion" title="Editar en Mensajes globales">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    <?= empty($mensajesPresentacion) ? 'Agregar mensaje' : 'Editar' ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($puedeEditarPresentacion): ?>

        <div class="presentacion-seccion">
            <h2 style="margin: 0 0 0.75rem; font-size: 1rem;">Agregar enlace</h2>
            <form class="presentacion-analisis-form" method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>">
                <input type="hidden" name="accion" value="guardar_enlace_presentacion">
                <input type="text" name="nombre_enlace" placeholder="Nombre que ven los usuarios" maxlength="150" required>
                <input type="text" name="url_enlace" placeholder="Enlace de OneDrive/SharePoint (presentación, Word, Excel o PDF) o código &quot;Insertar&quot;" required>
                <button type="submit" class="presentacion-analisis-boton">Agregar</button>
            </form>
        </div>

        <div class="presentacion-seccion presentacion-seccion-final">
            <h2 style="margin: 0 0 0.25rem; font-size: 1rem;">Enlaces (<?= $totalEnlaces ?>)</h2>
            <?php if ($totalEnlaces === 0): ?>
            <p class="texto-atenuado">Todavía no hay enlaces. Agrega el primero arriba.</p>
            <?php else: ?>
            <?php foreach ($enlacesPresentacion as $indice => $enlace): ?>
            <div class="presentacion-enlace-fila">
                <div class="presentacion-enlace-orden">
                    <form method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>">
                        <input type="hidden" name="accion" value="mover_enlace_presentacion">
                        <input type="hidden" name="id" value="<?= (int) $enlace['id'] ?>">
                        <input type="hidden" name="direccion" value="arriba">
                        <button type="submit" title="Subir"<?= $indice === 0 ? ' disabled' : '' ?>>▲</button>
                    </form>
                    <form method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>">
                        <input type="hidden" name="accion" value="mover_enlace_presentacion">
                        <input type="hidden" name="id" value="<?= (int) $enlace['id'] ?>">
                        <input type="hidden" name="direccion" value="abajo">
                        <button type="submit" title="Bajar"<?= $indice === $totalEnlaces - 1 ? ' disabled' : '' ?>>▼</button>
                    </form>
                </div>

                <button type="button" class="icono-boton" data-abrir-visor data-src="<?= htmlspecialchars($enlace['url_incrustada']) ?>" data-titulo="<?= htmlspecialchars($enlace['nombre']) ?>" title="Ver">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>

                <input type="text" data-campo="nombre" name="nombre_enlace" value="<?= htmlspecialchars($enlace['nombre']) ?>" form="form-enlace-<?= (int) $enlace['id'] ?>" maxlength="150" required aria-label="Nombre del enlace">
                <input type="text" data-campo="url" name="url_enlace" value="<?= htmlspecialchars($enlace['url']) ?>" form="form-enlace-<?= (int) $enlace['id'] ?>" required aria-label="Dirección del enlace">

                <span class="presentacion-enlace-actualizado">
                    <?= htmlspecialchars(date('d/m/Y', strtotime($enlace['actualizado_en']))) ?><?= !empty($enlace['actualizado_por_nombre']) ? ' · ' . htmlspecialchars($enlace['actualizado_por_nombre']) : '' ?>
                </span>

                <form method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>" id="form-enlace-<?= (int) $enlace['id'] ?>">
                    <input type="hidden" name="accion" value="guardar_enlace_presentacion">
                    <input type="hidden" name="id" value="<?= (int) $enlace['id'] ?>">
                    <button type="submit" class="boton-accion boton-accion-editar">Guardar</button>
                </form>

                <form method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>" onsubmit="return confirm('¿Eliminar este enlace? No se puede deshacer.');">
                    <input type="hidden" name="accion" value="eliminar_enlace_presentacion">
                    <input type="hidden" name="id" value="<?= (int) $enlace['id'] ?>">
                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <?php if ($totalEnlaces === 0): ?>
        <div class="presentacion-analisis-vacio presentacion-seccion-final">
            <strong>Todavía no hay enlaces disponibles.</strong>
        </div>
        <?php else: ?>
        <div class="presentacion-seccion-final">
            <div class="presentacion-lista-usuario">
                <?php foreach ($enlacesPresentacion as $enlace): ?>
                <button type="button" class="presentacion-enlace-boton" data-abrir-visor data-src="<?= htmlspecialchars($enlace['url_incrustada']) ?>" data-titulo="<?= htmlspecialchars($enlace['nombre']) ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <?= htmlspecialchars($enlace['nombre']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<div id="presentacion-visor-modal" class="modal-fondo">
    <div class="modal-caja presentacion-visor-caja">
        <div class="modal-cabecera presentacion-visor-cabecera">
            <h2 id="presentacion-visor-titulo"></h2>
            <button type="button" class="modal-cerrar" id="presentacion-visor-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <div class="presentacion-visor-marco">
            <iframe id="presentacion-visor-iframe" src="" title="Visor de documento" allowfullscreen referrerpolicy="no-referrer"></iframe>
        </div>
    </div>
</div>

<?php if (!empty($mensajesPresentacion)): ?>
<script src="publico/js/vendor/marked.min.js"></script>
<script src="publico/js/editor-markdown.js"></script>
<?php endif; ?>

<script>
(function () {
    var modal = document.getElementById('presentacion-visor-modal');
    var iframe = document.getElementById('presentacion-visor-iframe');
    var titulo = document.getElementById('presentacion-visor-titulo');
    var botonCerrar = document.getElementById('presentacion-visor-cerrar');

    if (!modal || !iframe) {
        return;
    }

    var teclasNavegacion = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '];

    var enfocarVisor = function () {
        iframe.focus();
        try { iframe.contentWindow.focus(); } catch (e) {}
    };

    document.querySelectorAll('[data-abrir-visor]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            if (!boton.dataset.src) {
                return;
            }
            iframe.src = boton.dataset.src;
            titulo.textContent = boton.dataset.titulo || '';
            modal.classList.add('abierto');
        });
    });

    // A propósito, esta ventana solo se cierra con la X: ni clic afuera ni Escape (a diferencia de
    // los demás modales de la app) — se puede estar navegando el documento y un clic o tecla fuera
    // de lugar no debe botar la vista.
    if (botonCerrar) {
        botonCerrar.addEventListener('click', function () {
            modal.classList.remove('abierto');
            iframe.src = '';
        });
    }

    // El visor de Office está en otro dominio: el navegador solo le entrega las flechas cuando
    // tiene el foco (no se le pueden reenviar teclas desde aquí). Se le da el foco al cargar y
    // cada vez que se toca una tecla de navegación fuera de un campo de texto, mientras el visor
    // está abierto.
    iframe.addEventListener('load', enfocarVisor);
    document.addEventListener('keydown', function (evento) {
        if (!modal.classList.contains('abierto')) {
            return;
        }
        var destino = evento.target;
        var escribiendo = destino && (destino.tagName === 'INPUT' || destino.tagName === 'TEXTAREA' || destino.tagName === 'SELECT' || destino.isContentEditable);
        if (!escribiendo && teclasNavegacion.indexOf(evento.key) !== -1) {
            evento.preventDefault();
            enfocarVisor();
        }
    });
})();
</script>
