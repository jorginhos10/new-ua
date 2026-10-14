<?php
/**
 * Pestaña "Documentos" (antes "Presentación") de ?ruta=analisis: cuadrícula de tarjetas de enlaces
 * de OneDrive/SharePoint (Word, Excel, PowerPoint o PDF) que el superadmin administra, cada una con
 * nombre y posición propia. La tarjeta de "Actas" (si el usuario tiene esa pestaña permitida — ya
 * no está en la barra de pestañas, de ahí se vuelve con su propio botón "Volver") es una fila más
 * de $enlacesPresentacion con una url reservada (ver AnalisisPresentacion::URL_ACTAS/esActas()):
 * entra al mismo orden que los documentos y se mueve con las mismas flechas ▲▼, pero sin nombre/
 * enlace editables ni botón Eliminar — por defecto queda de última (ver sql/presentacion_actas_fila.sql).
 * Cada tarjeta de documento trae una miniatura (icono coloreado según el tipo detectado del enlace)
 * y una descripción automática (ver AnalisisPresentacion::tipoDocumento()) — nunca las escribe el
 * admin a mano, solo el nombre y el enlace. La cuadrícula es responsiva: más o menos columnas según
 * el ancho disponible y la cantidad de tarjetas (CSS grid auto-fill). Al hacer clic en un documento
 * se abre en otra pestaña, con la sesión de Microsoft 365 de quien lo mira — antes se abría
 * incrustado en un visor, pero SharePoint no deja incrustar sus páginas (los PDF sobre todo) desde
 * otro dominio y el iframe quedaba bloqueado.
 *
 * Variables esperadas (ver AnalisisControlador::renderizarPresentacion()): $enlacesPresentacion
 * (cada uno con id, nombre, url, miniatura_url, orden, y para los que no son Actas también
 * url_descarga, tipo_documento, actualizado_en, actualizado_por_nombre),
 * $totalDocumentos (cuenta solo documentos, sin Actas), $puedeEditarPresentacion,
 * $mensajesPresentacion, $vista. El mensaje que
 * explica la sección es un mensaje global más (misma tabla y editor que Configuraciones > Mensajes
 * globales), con su propia audiencia "analisis_presentacion" — se edita desde esa pantalla, acá
 * solo se muestra.
 */

$enlacesPresentacion = $enlacesPresentacion ?? [];
$puedeEditarPresentacion = $puedeEditarPresentacion ?? false;
$mensajesPresentacion = $mensajesPresentacion ?? [];
// $totalEnlaces cuenta TODO lo que se ve en la cuadrícula (documentos + la tarjeta de Actas, si
// está) — lo que importa para el límite de las flechas ▲▼ y para saber si la cuadrícula va vacía.
// $totalDocumentos es solo para el título "Documentos (N)": la tarjeta de Actas no es un documento.
$totalEnlaces = count($enlacesPresentacion);
$totalDocumentos = $totalDocumentos ?? count(array_filter(
    $enlacesPresentacion,
    static fn (array $enlace): bool => !AnalisisPresentacion::esActas($enlace)
));

/**
 * Miniatura de cada tarjeta: si el admin puso una URL de imagen ($miniaturaUrl), esa imagen tal
 * cual — hecho en el navegador de quien la ve, nunca se descarga ni se redimensiona en el
 * servidor; loading="lazy"/decoding="async" y un tamaño intrínseco acorde al recuadro real (no la
 * imagen completa) para no pedir más de lo que hace falta. Si no hay miniatura puesta a mano, cae
 * a una ficha de documento coloreada según el tipo (ver AnalisisPresentacion::tipoDocumento()) con
 * sus iniciales, o una carpeta azul institucional para la tarjeta de Actas ($tipoDocumento null)
 * — para que se note a simple vista que esa lleva a otra pantalla, no abre un documento.
 *
 * Nota: se evaluó generar una miniatura real automática (captura de pantalla del documento con
 * Chrome headless al crear/editar el enlace) y no funciona de forma confiable — los enlaces de
 * OneDrive/SharePoint institucionales exigen sesión de Microsoft 365; un Chrome headless sin esa
 * sesión se queda colgado o el visor de Office le hace crashear el renderer, probado directamente.
 * Por eso la miniatura real es una URL que pone el admin a mano, no algo generado solo.
 */
function miniaturaTarjetaPresentacion(?array $tipoDocumento, ?string $miniaturaUrl = null): string
{
    if ($miniaturaUrl !== null && $miniaturaUrl !== '') {
        // El recuadro real (.presentacion-tarjeta-miniatura) mide 130px de alto; 230×130 es nomás
        // una pista de proporción para que el navegador reserve el espacio, el ancho real lo pone
        // el CSS (width/height:100%, object-fit:cover) según el ancho de cada tarjeta en la grilla.
        return '<img src="' . htmlspecialchars($miniaturaUrl) . '" alt="" loading="lazy" decoding="async" width="230" height="130">';
    }

    if ($tipoDocumento === null) {
        return '<svg viewBox="0 0 48 60" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            . '<path d="M4 13a2 2 0 0 1 2-2h13l4 5h19a2 2 0 0 1 2 2v30a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" fill="#143163"/>'
            . '</svg>';
    }

    $color = htmlspecialchars($tipoDocumento['color']);
    $glifo = htmlspecialchars($tipoDocumento['glifo']);
    $tamanoFuente = strlen($tipoDocumento['glifo']) > 1 ? 11 : 16;

    return '<svg viewBox="0 0 48 60" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
        . '<path d="M6 2h22l12 12v42a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" fill="' . $color . '"/>'
        . '<path d="M28 2v10a2 2 0 0 0 2 2h10z" fill="rgba(255,255,255,0.4)"/>'
        . '<text x="24" y="42" font-size="' . $tamanoFuente . '" font-weight="700" fill="#fff" text-anchor="middle" font-family="Arial, sans-serif">' . $glifo . '</text>'
        . '</svg>';
}

/**
 * Botón "Descargar" de cada tarjeta de documento (nunca de la de Actas) — aparte del clic en la
 * tarjeta, que abre el documento en otra pestaña. $urlDescarga ya viene resuelta a descarga directa
 * (ver AnalisisPresentacion::urlDescarga()). stopPropagation evita que el clic también dispare el
 * enlace de la tarjeta.
 */
function botonesExtraTarjetaPresentacion(string $urlDescarga): string
{
    return '<div class="presentacion-tarjeta-extra">'
        . '<a class="icono-boton" href="' . htmlspecialchars($urlDescarga) . '" target="_blank" rel="noopener" download title="Descargar" onclick="event.stopPropagation()">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>'
        . '</a>'
        . '</div>';
}
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

    .presentacion-analisis-form input[name="miniatura_enlace"] {
        flex: 1;
        min-width: 220px;
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

    /* Cuadrícula responsiva: el número de columnas se ajusta solo según el ancho disponible y la
       cantidad de tarjetas (auto-fill) — nunca una cantidad fija de columnas. */
    .presentacion-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 1rem;
        align-content: start;
    }

    .presentacion-tarjeta {
        position: relative;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--color-borde);
        border-radius: 10px;
        background: var(--color-superficie);
        overflow: hidden;
        padding: 0;
    }

    a.presentacion-tarjeta {
        text-decoration: none;
        color: inherit;
        font: inherit;
        cursor: pointer;
    }

    /* Para la tarjeta de usuario (clic = abrir en otra pestaña): el enlace interno ocupa toda la
       tarjeta menos el botón "Descargar", que va aparte para no quedar anidado dentro de otro
       enlace. */
    .presentacion-tarjeta-disparador {
        display: flex;
        flex-direction: column;
        width: 100%;
        border: none;
        background: transparent;
        margin: 0;
        padding: 0;
        text-align: left;
        text-decoration: none;
        color: inherit;
        font: inherit;
        cursor: pointer;
    }

    /* Fila inferior, alineada con la descripción: a la izquierda el texto, a la derecha el botón
       "Descargar" — aparte del clic en la miniatura (o en el cuerpo, en la tarjeta de usuario),
       que abre el documento en otra pestaña. */
    .presentacion-tarjeta-pie {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    /* En la tarjeta de usuario, .presentacion-tarjeta-pie va fuera del enlace (no se puede anidar un
       <a> dentro de otro) — directo bajo .presentacion-tarjeta, por eso necesita su propio
       padding; en la de admin va dentro de .presentacion-tarjeta-cuerpo, que ya viene con el suyo. */
    .presentacion-tarjeta > .presentacion-tarjeta-pie {
        padding: 0 0.9rem 0.8rem;
    }

    .presentacion-tarjeta-extra {
        display: flex;
        gap: 0.2rem;
        flex-shrink: 0;
    }

    .presentacion-tarjeta-extra .icono-boton {
        width: 26px;
        height: 26px;
        background: var(--color-fondo);
        border: 1px solid var(--color-borde);
    }

    .presentacion-tarjeta-extra .icono-boton:hover {
        background: var(--color-primario-suave);
        border-color: var(--color-primario);
    }

    .presentacion-tarjeta-extra .icono-boton svg {
        width: 14px;
        height: 14px;
    }

    .presentacion-tarjeta:hover {
        border-color: var(--color-primario);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .presentacion-tarjeta-miniatura {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 130px;
        background: var(--color-fondo);
        border: none;
        border-bottom: 1px solid var(--color-borde);
        padding: 0;
        flex-shrink: 0;
        overflow: hidden;
    }

    a.presentacion-tarjeta-miniatura,
    button.presentacion-tarjeta-miniatura {
        cursor: pointer;
    }

    .presentacion-tarjeta-miniatura svg {
        width: 58px;
        height: 72px;
    }

    /* Miniatura puesta a mano (ver miniaturaTarjetaPresentacion()): llena el recuadro, recortada —
       no es la imagen completa, es una miniatura. */
    .presentacion-tarjeta-miniatura img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: top;
    }

    .presentacion-tarjeta-actas .presentacion-tarjeta-miniatura {
        background: var(--color-primario-suave);
    }

    .presentacion-tarjeta-actas .presentacion-tarjeta-miniatura svg {
        width: 50px;
        height: 62px;
    }

    .presentacion-tarjeta-cuerpo {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        padding: 0.8rem 0.9rem;
        flex: 1;
    }

    /* En la tarjeta de usuario, .presentacion-tarjeta-pie queda afuera del enlace, como pie real de
       la tarjeta — sin esto, el padding inferior de .presentacion-tarjeta-cuerpo (que ahí solo
       envuelve el nombre) dejaba un hueco entre el nombre y la descripción. */
    .presentacion-tarjeta-disparador .presentacion-tarjeta-cuerpo {
        padding-bottom: 0;
    }

    .presentacion-tarjeta-nombre {
        display: block;
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--color-texto);
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    a.presentacion-tarjeta-enlace {
        text-decoration: none;
        cursor: pointer;
    }

    .presentacion-tarjeta-campo-nombre {
        font-size: 0.88rem;
        font-weight: 700;
        padding: 0.35rem 0.5rem;
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        background: var(--color-superficie);
        color: var(--color-texto);
        width: 100%;
    }

    .presentacion-tarjeta-campo-url {
        font-size: 0.72rem;
        padding: 0.3rem 0.5rem;
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        background: var(--color-superficie);
        color: var(--color-texto-tenue);
        width: 100%;
    }

    .presentacion-tarjeta-descripcion {
        font-size: 0.78rem;
        color: var(--color-texto-tenue);
    }

    .presentacion-tarjeta-meta {
        font-size: 0.7rem;
        color: var(--color-texto-tenue);
    }

    .presentacion-tarjeta-acciones {
        margin-top: auto;
        padding-top: 0.6rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.4rem;
    }

    .presentacion-tarjeta-orden {
        display: flex;
        gap: 2px;
        flex-shrink: 0;
    }

    .presentacion-tarjeta-orden button {
        border: 1px solid var(--color-borde);
        background: var(--color-superficie);
        color: var(--color-texto-tenue);
        width: 22px;
        height: 20px;
        line-height: 1;
        font-size: 0.65rem;
        padding: 0;
        cursor: pointer;
        border-radius: 4px;
    }

    .presentacion-tarjeta-orden button:disabled {
        opacity: 0.3;
        cursor: default;
    }

    .presentacion-tarjeta-orden button:not(:disabled):hover {
        color: var(--color-primario);
        border-color: var(--color-primario);
    }

    .presentacion-tarjeta-botones {
        display: flex;
        gap: 0.3rem;
    }

    .presentacion-tarjeta-botones .boton-accion {
        font-size: 0.75rem;
        padding: 0.3rem 0.55rem;
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

</style>

<div class="tarjeta presentacion-analisis">
    <div class="presentacion-cuerpo">
        <?php if (!empty($mensajesPresentacion) || $puedeEditarPresentacion): ?>
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
            <?php if ($puedeEditarPresentacion): ?>
            <div class="presentacion-info-banner-acciones">
                <a class="presentacion-info-banner-editar" href="index.php?ruta=mensaje-global&audiencia=analisis_presentacion" title="Editar en Mensajes globales">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    <?= empty($mensajesPresentacion) ? 'Agregar mensaje' : 'Editar' ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($puedeEditarPresentacion): ?>

        <div class="presentacion-seccion">
            <h2 style="margin: 0 0 0.75rem; font-size: 1rem;">Agregar enlace</h2>
            <form class="presentacion-analisis-form" method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>">
                <input type="hidden" name="accion" value="guardar_enlace_presentacion">
                <input type="text" name="nombre_enlace" placeholder="Nombre que ven los usuarios" maxlength="150" required>
                <input type="text" name="url_enlace" placeholder="Enlace de OneDrive/SharePoint (presentación, Word, Excel o PDF) o código &quot;Insertar&quot;" required>
                <input type="text" name="miniatura_enlace" placeholder="URL de imagen para la miniatura (opcional)">
                <button type="submit" class="presentacion-analisis-boton">Agregar</button>
            </form>
        </div>

        <div class="presentacion-seccion presentacion-seccion-final">
            <h2 style="margin: 0 0 0.75rem; font-size: 1rem;">Documentos (<?= $totalDocumentos ?>)</h2>
            <?php if ($totalEnlaces === 0): ?>
            <p class="texto-atenuado">Todavía no hay documentos. Agrega el primero arriba.</p>
            <?php else: ?>
            <div class="presentacion-grid">
                <?php foreach ($enlacesPresentacion as $indice => $enlace): ?>
                <?php if (AnalisisPresentacion::esActas($enlace)): ?>
                <div class="presentacion-tarjeta presentacion-tarjeta-actas">
                    <a class="presentacion-tarjeta-miniatura" href="<?= htmlspecialchars(analisisUrl('actas', $vista)) ?>" title="Ir a Actas">
                        <?= miniaturaTarjetaPresentacion(null) ?>
                    </a>
                    <div class="presentacion-tarjeta-cuerpo">
                        <a class="presentacion-tarjeta-nombre presentacion-tarjeta-enlace" href="<?= htmlspecialchars(analisisUrl('actas', $vista)) ?>">Actas</a>
                        <p class="presentacion-tarjeta-descripcion">Actas cargadas por facultad</p>
                        <div class="presentacion-tarjeta-acciones">
                            <div class="presentacion-tarjeta-orden">
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
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="presentacion-tarjeta">
                    <a class="presentacion-tarjeta-miniatura" href="<?= htmlspecialchars($enlace['url']) ?>" target="_blank" rel="noopener" title="Abrir en otra pestaña">
                        <?= miniaturaTarjetaPresentacion($enlace['tipo_documento'], $enlace['miniatura_url'] ?? null) ?>
                    </a>
                    <div class="presentacion-tarjeta-cuerpo">
                        <input type="text" class="presentacion-tarjeta-campo-nombre" data-campo="nombre" name="nombre_enlace" value="<?= htmlspecialchars($enlace['nombre']) ?>" form="form-enlace-<?= (int) $enlace['id'] ?>" maxlength="150" required aria-label="Nombre del enlace">
                        <input type="text" class="presentacion-tarjeta-campo-url" data-campo="url" name="url_enlace" value="<?= htmlspecialchars($enlace['url']) ?>" form="form-enlace-<?= (int) $enlace['id'] ?>" required aria-label="Dirección del enlace">
                        <input type="text" class="presentacion-tarjeta-campo-url" data-campo="miniatura" name="miniatura_enlace" value="<?= htmlspecialchars($enlace['miniatura_url'] ?? '') ?>" form="form-enlace-<?= (int) $enlace['id'] ?>" placeholder="URL de imagen para la miniatura (opcional)" aria-label="URL de la miniatura">
                        <div class="presentacion-tarjeta-pie">
                            <p class="presentacion-tarjeta-descripcion"><?= htmlspecialchars($enlace['tipo_documento']['descripcion']) ?></p>
                            <?= botonesExtraTarjetaPresentacion($enlace['url_descarga']) ?>
                        </div>
                        <p class="presentacion-tarjeta-meta">
                            <?= htmlspecialchars(date('d/m/Y', strtotime($enlace['actualizado_en']))) ?><?= !empty($enlace['actualizado_por_nombre']) ? ' · ' . htmlspecialchars($enlace['actualizado_por_nombre']) : '' ?>
                        </p>
                        <div class="presentacion-tarjeta-acciones">
                            <div class="presentacion-tarjeta-orden">
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
                            <div class="presentacion-tarjeta-botones">
                                <form method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>" id="form-enlace-<?= (int) $enlace['id'] ?>">
                                    <input type="hidden" name="accion" value="guardar_enlace_presentacion">
                                    <input type="hidden" name="id" value="<?= (int) $enlace['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-editar">Guardar</button>
                                </form>
                                <form method="POST" action="<?= htmlspecialchars(analisisUrl('presentacion', $vista)) ?>" onsubmit="return confirm('¿Eliminar este documento? No se puede deshacer.');">
                                    <input type="hidden" name="accion" value="eliminar_enlace_presentacion">
                                    <input type="hidden" name="id" value="<?= (int) $enlace['id'] ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <?php if ($totalEnlaces === 0): ?>
        <div class="presentacion-analisis-vacio presentacion-seccion-final">
            <strong>Todavía no hay documentos disponibles.</strong>
        </div>
        <?php else: ?>
        <div class="presentacion-seccion-final">
            <div class="presentacion-grid">
                <?php foreach ($enlacesPresentacion as $enlace): ?>
                <?php if (AnalisisPresentacion::esActas($enlace)): ?>
                <a class="presentacion-tarjeta presentacion-tarjeta-actas" href="<?= htmlspecialchars(analisisUrl('actas', $vista)) ?>">
                    <span class="presentacion-tarjeta-miniatura"><?= miniaturaTarjetaPresentacion(null) ?></span>
                    <span class="presentacion-tarjeta-cuerpo">
                        <span class="presentacion-tarjeta-nombre">Actas</span>
                        <span class="presentacion-tarjeta-descripcion">Actas cargadas por facultad</span>
                    </span>
                </a>
                <?php else: ?>
                <div class="presentacion-tarjeta">
                    <a class="presentacion-tarjeta-disparador" href="<?= htmlspecialchars($enlace['url']) ?>" target="_blank" rel="noopener">
                        <span class="presentacion-tarjeta-miniatura"><?= miniaturaTarjetaPresentacion($enlace['tipo_documento'], $enlace['miniatura_url'] ?? null) ?></span>
                        <span class="presentacion-tarjeta-cuerpo">
                            <span class="presentacion-tarjeta-nombre" title="<?= htmlspecialchars($enlace['nombre']) ?>"><?= htmlspecialchars($enlace['nombre']) ?></span>
                        </span>
                    </a>
                    <div class="presentacion-tarjeta-pie">
                        <p class="presentacion-tarjeta-descripcion"><?= htmlspecialchars($enlace['tipo_documento']['descripcion']) ?></p>
                        <?= botonesExtraTarjetaPresentacion($enlace['url_descarga']) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php if (!empty($mensajesPresentacion)): ?>
<script src="publico/js/vendor/marked.min.js"></script>
<script src="publico/js/editor-markdown.js"></script>
<?php endif; ?>

<script>
// El ModSecurity del hosting bloquea (406 Not Acceptable) un POST con el código <iframe> de
// "Insertar" o una URL de imagen (".png", ".jpg"...) en crudo — y también en base64, porque
// reconoce "aHR0cHM6Ly" ("https://" codificado). Por eso el enlace y la miniatura viajan en
// hexadecimal ("<campo>_hex", ver AnalisisControlador::campoPresentacionPost()); los campos
// originales se deshabilitan para que no se envíen, y se rehabilitan si se vuelve con "Atrás".
(function () {
    var camposCodificados = ['url_enlace', 'miniatura_enlace'];

    var aHex = function (texto) {
        var hex = '';
        new TextEncoder().encode(texto).forEach(function (b) {
            hex += (b < 16 ? '0' : '') + b.toString(16);
        });
        return hex;
    };

    document.addEventListener('submit', function (evento) {
        var formulario = evento.target;
        var accion = formulario.elements && formulario.elements.namedItem('accion');
        if (!accion || accion.value !== 'guardar_enlace_presentacion') {
            return;
        }
        camposCodificados.forEach(function (nombre) {
            var campo = formulario.elements.namedItem(nombre);
            if (!campo || campo.disabled) {
                return;
            }
            var oculto = document.createElement('input');
            oculto.type = 'hidden';
            oculto.name = nombre + '_hex';
            oculto.value = aHex(campo.value);
            formulario.appendChild(oculto);
            campo.disabled = true;
            campo.dataset.deshabilitadoEnvio = '1';
        });
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('[data-deshabilitado-envio]').forEach(function (campo) {
            campo.disabled = false;
            delete campo.dataset.deshabilitadoEnvio;
        });
        document.querySelectorAll('input[name$="_hex"]').forEach(function (oculto) {
            oculto.remove();
        });
    });
})();

</script>
