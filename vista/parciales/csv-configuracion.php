<?php
/**
 * Botones "Plantilla" (exportar CSV) e "Importar" (reemplazo masivo vía CSV) para una vista de
 * catálogo de Configuraciones, más el modal de advertencia + formulario de subida.
 *
 * Variables esperadas antes de incluir este parcial:
 * - $csvRuta (string): valor de `ruta` en la URL de este catálogo (ej. 'estamentos').
 * - $csvEtiqueta (string): nombre legible del catálogo, para los textos del modal (ej. "Estamentos").
 */
?>
<div class="grupo-acciones-encabezado">
    <a
        href="index.php?ruta=<?= htmlspecialchars($csvRuta) ?>&accion_csv=plantilla"
        class="boton-icono-accion"
        data-tooltip="Descargar plantilla CSV"
        title="Descargar plantilla CSV"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
    </a>
    <button
        type="button"
        id="boton-abrir-modal-importar-csv-<?= htmlspecialchars($csvRuta) ?>"
        class="boton-icono-accion boton-abrir-modal-importar-csv"
        data-tooltip="Importar CSV"
        title="Importar CSV"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
    </button>
</div>

<div id="modal-importar-csv-<?= htmlspecialchars($csvRuta) ?>" class="modal-fondo">
    <div class="modal-caja">
        <div class="modal-cabecera">
            <h2>Importar <?= htmlspecialchars($csvEtiqueta) ?></h2>
            <button type="button" class="modal-cerrar boton-cerrar-modal-importar-csv" aria-label="Cerrar">&times;</button>
        </div>

        <p class="mensaje-error">Advertencia: esta acción reemplazará los datos existentes en esta configuración con el contenido del archivo CSV. Los registros que ya no aparezcan en el archivo se eliminarán si nada más los está usando; si algún registro todavía está en uso en otra parte del sistema, se dejará tal cual para no romper esa referencia.</p>

        <form
            method="POST"
            action="index.php?ruta=<?= htmlspecialchars($csvRuta) ?>"
            enctype="multipart/form-data"
            class="form-necesidad form-confirmar-importar-csv"
            data-mensaje-confirmar="¿Confirmas que quieres reemplazar los datos de &quot;<?= htmlspecialchars($csvEtiqueta) ?>&quot; con el contenido de este archivo? Esta acción no se puede deshacer."
        >
            <input type="hidden" name="accion" value="importar_csv">

            <div class="campo campo-ancho">
                <label for="archivo_csv-<?= htmlspecialchars($csvRuta) ?>">Archivo CSV *</label>
                <input type="file" id="archivo_csv-<?= htmlspecialchars($csvRuta) ?>" name="archivo_csv" accept=".csv" required>
            </div>

            <button type="submit" class="boton-enviar">Reemplazar datos</button>
        </form>
    </div>
</div>
