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
<style>
    .pdi-ajustes-caja { max-width: 680px; }
    .pdi-ajustes-resumen { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.6rem; margin: 1.5rem 0 1.25rem; }
    .pdi-ajustes-dato { border: 1px solid var(--color-borde); border-radius: var(--radio-chico); padding: 0.55rem 0.7rem; background: var(--color-fondo); }
    .pdi-ajustes-dato small { display: block; font-size: 0.72rem; color: var(--color-texto-tenue); text-transform: uppercase; letter-spacing: 0.03em; }
    .pdi-ajustes-dato strong { display: block; margin-top: 0.2rem; font-size: 1rem; font-variant-numeric: tabular-nums; }
    .pdi-ajustes-dato.cuadra strong { color: #1e6b3c; }
    .pdi-ajustes-dato.descuadra strong { color: #a83232; }
    .pdi-ajustes-dato em { display: block; font-style: normal; font-size: 0.72rem; color: #a83232; margin-top: 0.1rem; }
    .pdi-ajustes-seccion { border-top: 1px solid var(--color-borde); padding-top: 1rem; margin-top: 1rem; }
    .pdi-ajustes-seccion > label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 0.4rem; }
    .pdi-ajustes-fila { display: flex; gap: 0.5rem; align-items: stretch; }
    .pdi-ajustes-fila .campo { flex: 1; min-width: 0; margin: 0; }
    .pdi-ajustes-fila .campo.pdi-ajustes-monto { flex: 0 0 11rem; }
    .pdi-ajustes-fila .boton-agregar { flex-shrink: 0; white-space: nowrap; }
    .pdi-ajustes-fijados { margin-top: 0.6rem; display: flex; flex-direction: column; gap: 0.35rem; }
    .pdi-ajustes-fijado { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; font-size: 0.82rem; padding: 0.35rem 0.5rem; border-radius: var(--radio-chico); background: var(--color-fondo); }
    @media (max-width: 600px) { .pdi-ajustes-resumen { grid-template-columns: 1fr; } .pdi-ajustes-fila { flex-wrap: wrap; } .pdi-ajustes-fila .campo.pdi-ajustes-monto { flex: 1 1 100%; } }
</style>

<div id="pdi-ajustes-modal" class="modal-fondo">
    <div class="modal-caja pdi-ajustes-caja">
        <div class="modal-cabecera">
            <h2>Ajustes de Articulación PDI</h2>
            <button type="button" class="modal-cerrar" id="pdi-ajustes-cerrar" aria-label="Cerrar">&times;</button>
        </div>

        <div class="pdi-ajustes-resumen" title="El total de este árbol debe cuadrar con el total de Egresos de Programación presupuestal del año vigente. Hasta $50 de diferencia se carga sola al proyecto por defecto; si es mayor, elige a quién sumarle o restarle la diferencia.">
            <div class="pdi-ajustes-dato">
                <small>PP Egresos <?= (int) $infoAjustePdi['anio'] ?></small>
                <strong><?= $pesosPdi((float) $infoAjustePdi['pp']) ?></strong>
            </div>
            <div class="pdi-ajustes-dato">
                <small>Suma de proyectos</small>
                <strong><?= $pesosPdi((float) $infoAjustePdi['suma']) ?></strong>
            </div>
            <div class="pdi-ajustes-dato <?= $infoAjustePdi['diferencia'] == 0.0 ? 'cuadra' : 'descuadra' ?>">
                <small>Diferencia</small>
                <strong><?= $pesosPdi((float) $infoAjustePdi['diferencia']) ?></strong>
                <?php if ($infoAjustePdi['pendiente']): ?>
                <em title="La diferencia supera $50 y no se ha asignado: el total del árbol no cuadra con PP.">Sin asignar</em>
                <?php endif; ?>
            </div>
        </div>

        <div class="pdi-ajustes-seccion">
            <label for="pdi-ajustes-absorbe" title="La diferencia contra PP se suma (o se resta si es negativa) al valor de este proyecto.">Asignar la diferencia a</label>
            <div class="pdi-ajustes-fila">
                <div class="campo">
                    <select id="pdi-ajustes-absorbe">
                        <option value="">Por defecto<?= $etiquetaPorDefectoPdi !== '' ? ' (' . htmlspecialchars($etiquetaPorDefectoPdi) . ')' : '' ?></option>
                        <?php foreach ($proyectosAjustePdi as $proyectoAjuste): ?>
                        <option value="<?= (int) $proyectoAjuste['id'] ?>"<?= (int) $proyectoAjuste['id'] === (int) $infoAjustePdi['absorbeElegido'] ? ' selected' : '' ?>><?= htmlspecialchars($proyectoAjuste['etiqueta']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="boton-agregar" id="pdi-ajustes-guardar-absorbe">Guardar</button>
            </div>
        </div>

        <div class="pdi-ajustes-seccion">
            <label for="pdi-ajustes-valor-proyecto" title="Reemplaza el valor calculado de un proyecto (admite negativos). La diferencia que esto genere contra PP la absorbe el proyecto elegido arriba.">Valor a mostrar de un proyecto</label>
            <div class="pdi-ajustes-fila">
                <div class="campo">
                    <select id="pdi-ajustes-valor-proyecto">
                        <?php foreach ($proyectosAjustePdi as $proyectoAjuste): ?>
                        <option value="<?= (int) $proyectoAjuste['id'] ?>"><?= htmlspecialchars($proyectoAjuste['etiqueta']) ?> — calculado <?= $pesosPdi((float) $proyectoAjuste['calculado']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo pdi-ajustes-monto">
                    <input type="number" id="pdi-ajustes-valor-monto" step="1" placeholder="Valor (admite negativos)">
                </div>
                <button type="button" class="boton-agregar" id="pdi-ajustes-guardar-valor">Fijar</button>
            </div>
            <?php $hayFijadosPdi = array_filter($proyectosAjustePdi, static fn (array $p): bool => $p['fijado'] !== null); ?>
            <?php if (!empty($hayFijadosPdi)): ?>
            <div class="pdi-ajustes-fijados">
                <?php foreach ($hayFijadosPdi as $proyectoAjuste): ?>
                <div class="pdi-ajustes-fijado">
                    <span><?= htmlspecialchars($proyectoAjuste['etiqueta']) ?> → <strong><?= $pesosPdi((float) $proyectoAjuste['fijado']) ?></strong> <span class="texto-atenuado">(calculado <?= $pesosPdi((float) $proyectoAjuste['calculado']) ?>)</span></span>
                    <button type="button" class="boton-secundario pdi-ajustes-quitar-valor" data-proyecto-id="<?= (int) $proyectoAjuste['id'] ?>">Quitar</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
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
        if (monto === '' || isNaN(Number(monto))) {
            window.alert('Escribe un valor numérico (puede ser negativo).');
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
