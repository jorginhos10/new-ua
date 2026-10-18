<?php
/**
 * Modal de ajustes de Articulación PDI (solo SA, Tiempo real/Repositorio) — se abre con el icono
 * #pdi-ajustes-abrir de parciales/arbol.php. Permite (1) elegir a qué proyecto se le suma o resta la
 * diferencia contra el total de Egresos de Programación presupuestal y (2) fijar el valor a mostrar
 * de un proyecto. Guarda por fetch en AnalisisControlador::guardarAjustePdi() y recarga la página.
 * Variable esperada: $infoAjustePdi (ver AnalisisControlador::redondearYCuadrarTotalesPdi()).
 */
$pesosPdi = static fn (float $valor): string => '$' . number_format($valor, 0, ',', '.');
$proyectosAjustePdi = $infoAjustePdi['proyectos'];
$etiquetaPorDefectoPdi = '';
foreach ($proyectosAjustePdi as $proyectoAjuste) {
    if ((int) $proyectoAjuste['id'] === (int) $infoAjustePdi['idPorDefecto']) {
        $etiquetaPorDefectoPdi = $proyectoAjuste['etiqueta'];
    }
}
?>
<div id="pdi-ajustes-modal" class="modal-fondo">
    <div class="modal-caja" style="max-width: 640px;">
        <div class="modal-cabecera">
            <h2>Ajustes de Articulación PDI</h2>
            <button type="button" class="modal-cerrar" id="pdi-ajustes-cerrar" aria-label="Cerrar">&times;</button>
        </div>

        <p style="margin: 0.75rem 0; font-size: 0.9rem;" title="El total de este árbol debe cuadrar con el total de Egresos de Programación presupuestal del año vigente. Hasta $50 de diferencia se carga sola al proyecto por defecto; si es mayor, elige a quién sumarle o restarle la diferencia.">
            <strong>PP Egresos <?= (int) $infoAjustePdi['anio'] ?>:</strong> <?= $pesosPdi((float) $infoAjustePdi['pp']) ?> ·
            <strong>Suma de proyectos:</strong> <?= $pesosPdi((float) $infoAjustePdi['suma']) ?> ·
            <strong style="color: <?= $infoAjustePdi['diferencia'] == 0.0 ? '#1e6b3c' : '#a83232' ?>;">Diferencia: <?= $pesosPdi((float) $infoAjustePdi['diferencia']) ?></strong>
            <?php if ($infoAjustePdi['pendiente']): ?>
            <span style="color: #a83232;" title="La diferencia supera $50 y no se ha asignado: el total del árbol no cuadra con PP.">· sin asignar</span>
            <?php endif; ?>
        </p>

        <div style="border-top: 1px solid var(--color-borde); padding-top: 0.75rem;">
            <label for="pdi-ajustes-absorbe" style="font-weight: 600; font-size: 0.88rem;" title="La diferencia contra PP se suma (o se resta si es negativa) al valor de este proyecto.">Asignar la diferencia a</label>
            <div style="display: flex; gap: 0.5rem; margin-top: 0.35rem;">
                <select id="pdi-ajustes-absorbe" style="flex: 1; min-width: 0;">
                    <option value="">Por defecto<?= $etiquetaPorDefectoPdi !== '' ? ' (' . htmlspecialchars($etiquetaPorDefectoPdi) . ')' : '' ?></option>
                    <?php foreach ($proyectosAjustePdi as $proyectoAjuste): ?>
                    <option value="<?= (int) $proyectoAjuste['id'] ?>"<?= (int) $proyectoAjuste['id'] === (int) $infoAjustePdi['absorbeElegido'] ? ' selected' : '' ?>><?= htmlspecialchars($proyectoAjuste['etiqueta']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="boton-agregar" id="pdi-ajustes-guardar-absorbe">Guardar</button>
            </div>
        </div>

        <div style="border-top: 1px solid var(--color-borde); padding-top: 0.75rem; margin-top: 1rem;">
            <label for="pdi-ajustes-valor-proyecto" style="font-weight: 600; font-size: 0.88rem;" title="Reemplaza el valor calculado de un proyecto. La diferencia que esto genere contra PP la absorbe el proyecto elegido arriba.">Valor a mostrar de un proyecto</label>
            <div style="display: flex; gap: 0.5rem; margin-top: 0.35rem;">
                <select id="pdi-ajustes-valor-proyecto" style="flex: 1; min-width: 0;">
                    <?php foreach ($proyectosAjustePdi as $proyectoAjuste): ?>
                    <option value="<?= (int) $proyectoAjuste['id'] ?>"><?= htmlspecialchars($proyectoAjuste['etiqueta']) ?> — calculado <?= $pesosPdi((float) $proyectoAjuste['calculado']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="pdi-ajustes-valor-monto" min="0" step="1" placeholder="Valor" style="width: 9rem;">
                <button type="button" class="boton-agregar" id="pdi-ajustes-guardar-valor">Fijar</button>
            </div>
            <?php foreach ($proyectosAjustePdi as $proyectoAjuste): ?>
            <?php if ($proyectoAjuste['fijado'] === null) { continue; } ?>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; font-size: 0.82rem; margin-top: 0.4rem;">
                <span><?= htmlspecialchars($proyectoAjuste['etiqueta']) ?> → <strong><?= $pesosPdi((float) $proyectoAjuste['fijado']) ?></strong> <span class="texto-atenuado">(calculado <?= $pesosPdi((float) $proyectoAjuste['calculado']) ?>)</span></span>
                <button type="button" class="boton-secundario pdi-ajustes-quitar-valor" data-proyecto-id="<?= (int) $proyectoAjuste['id'] ?>">Quitar</button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="modal-pie" style="gap: 0.5rem;">
            <button type="button" class="boton-secundario" id="pdi-ajustes-cancelar">Cerrar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('pdi-ajustes-modal');
    var botonAbrir = document.getElementById('pdi-ajustes-abrir');
    if (!modal || !botonAbrir) { return; }

    function cerrar() { modal.classList.remove('abierto'); }
    botonAbrir.addEventListener('click', function () { modal.classList.add('abierto'); });
    ['pdi-ajustes-cerrar', 'pdi-ajustes-cancelar'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', cerrar);
    });
    modal.addEventListener('click', function (evento) { if (evento.target === modal) { cerrar(); } });

    // Guarda por POST (JSON) y recarga para ver el árbol con el ajuste aplicado.
    function guardar(campos) {
        var datos = new FormData();
        datos.append('accion', 'guardar_ajuste_pdi');
        Object.keys(campos).forEach(function (clave) { datos.append(clave, campos[clave]); });

        fetch(window.location.href, { method: 'POST', body: datos, credentials: 'same-origin' })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (resultado) {
                if (!resultado.ok) { throw new Error(resultado.error || 'No se pudo guardar.'); }
                window.location.reload();
            })
            .catch(function (error) { window.alert('No se pudo guardar el ajuste: ' + error.message); });
    }

    document.getElementById('pdi-ajustes-guardar-absorbe').addEventListener('click', function () {
        var proyectoId = document.getElementById('pdi-ajustes-absorbe').value;
        guardar(proyectoId === '' ? { tipo: 'quitar_absorbe' } : { tipo: 'absorbe', proyecto_id: proyectoId });
    });

    document.getElementById('pdi-ajustes-guardar-valor').addEventListener('click', function () {
        var monto = document.getElementById('pdi-ajustes-valor-monto').value;
        if (monto === '' || isNaN(Number(monto)) || Number(monto) < 0) {
            window.alert('Escribe un valor numérico mayor o igual a 0.');
            return;
        }
        guardar({ tipo: 'valor', proyecto_id: document.getElementById('pdi-ajustes-valor-proyecto').value, valor: monto });
    });

    modal.querySelectorAll('.pdi-ajustes-quitar-valor').forEach(function (boton) {
        boton.addEventListener('click', function () {
            guardar({ tipo: 'quitar_valor', proyecto_id: boton.dataset.proyectoId });
        });
    });
});
</script>
