<?php
/**
 * Modal del botón Ingresos de Análisis de distribución (solo SA): elige qué código de Ingresos de
 * Programación presupuestal muestra ese botón (nombre y valor salen siempre de PP). Lo abre el ⚙
 * #ingresos-pp-abrir de selector-modulos.php; guarda por fetch en
 * AnalisisControlador::guardarCodigoIngresosPp() y recarga la página.
 * Variables esperadas: $lineasIngresosPp (codigo => [descripcion, valor]), $tarjetasPresupuesto.
 */
$lineasIngresosPp = $lineasIngresosPp ?? [];
$codigoIngresosActual = (string) ($tarjetasPresupuesto['ingresos']['codigo'] ?? '');
?>
<div id="ingresos-pp-modal" class="modal-fondo">
    <div class="modal-caja" style="max-width: 560px;">
        <div class="modal-cabecera">
            <h2>Botón Ingresos</h2>
            <button type="button" class="modal-cerrar" id="ingresos-pp-cerrar" aria-label="Cerrar">&times;</button>
        </div>

        <label for="ingresos-pp-codigo" style="display: block; margin-top: 0.75rem; font-weight: 600; font-size: 0.88rem;" title="El botón muestra el nombre y el valor del año vigente de esta línea de Programación presupuestal › Ingresos, y abre ese landing.">Código de Ingresos de PP</label>
        <select id="ingresos-pp-codigo" style="width: 100%; margin-top: 0.35rem;">
            <option value="">Sin configurar</option>
            <?php foreach ($lineasIngresosPp as $codigoLinea => $lineaIngreso): ?>
            <option value="<?= htmlspecialchars((string) $codigoLinea) ?>"<?= (string) $codigoLinea === $codigoIngresosActual ? ' selected' : '' ?>><?= htmlspecialchars($codigoLinea . ' · ' . $lineaIngreso['descripcion'] . ' — $' . number_format((float) $lineaIngreso['valor'], 0, ',', '.')) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (empty($lineasIngresosPp)): ?>
        <p class="texto-atenuado" style="font-size: 0.82rem;">Programación presupuestal todavía no tiene líneas de Ingresos.</p>
        <?php endif; ?>

        <div class="modal-pie" style="gap: 0.5rem;">
            <button type="button" class="boton-secundario" id="ingresos-pp-cancelar">Cancelar</button>
            <button type="button" class="boton-agregar" id="ingresos-pp-guardar">Guardar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('ingresos-pp-modal');
    var botonAbrir = document.getElementById('ingresos-pp-abrir');
    if (!modal || !botonAbrir) { return; }

    function cerrar() { modal.classList.remove('abierto'); }
    botonAbrir.addEventListener('click', function () { modal.classList.add('abierto'); });
    ['ingresos-pp-cerrar', 'ingresos-pp-cancelar'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', cerrar);
    });
    modal.addEventListener('click', function (evento) { if (evento.target === modal) { cerrar(); } });

    var botonGuardar = document.getElementById('ingresos-pp-guardar');
    botonGuardar.addEventListener('click', function () {
        var datos = new FormData();
        datos.append('accion', 'guardar_codigo_ingresos_pp');
        datos.append('codigo', document.getElementById('ingresos-pp-codigo').value);

        botonGuardar.disabled = true;
        fetch(window.location.href, { method: 'POST', body: datos, credentials: 'same-origin' })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (resultado) {
                if (!resultado.ok) { throw new Error(resultado.error || 'No se pudo guardar.'); }
                window.location.reload();
            })
            .catch(function (error) {
                botonGuardar.disabled = false;
                window.alert('No se pudo guardar: ' + error.message);
            });
    });
});
</script>
