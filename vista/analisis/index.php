<?php
/**
 * Cáscara de ?ruta=analisis (solo SA raíz — ver AnalisisControlador::verificarAcceso()): sin la
 * barra de módulo habitual, en su lugar una franja de máx. 2rem con las 4 pestañas a la
 * izquierda y el toggle de 3 vías (Tiempo real/Repositorio/Usuario) a la derecha. El sidebar ya
 * viene colapsado desde el HTML en esta página (ver vista/parciales/sidebar.php) — sin JS, para
 * que no se vea abrirse y cerrarse al cargar.
 *
 * Variables esperadas del controlador (con valores por defecto cuando no aplican a la pestaña
 * activa): $tab, $vista, $tituloPagina, $rolVista, $pestanaArbol, $versiones, $versionIdActual,
 * $snapshots, $snapshotIdActual, $dependenciasTodas, $dependenciaFiltroActual, $cuadreProyectos
 * (solo tab=proyectos, Tiempo real), $error.
 */

require_once __DIR__ . '/../../modelo/AnioPresupuestal.php';

$tab = $tab ?? 'programacion';
$vista = $vista ?? 'tiempo_real';
$rolVista = $rolVista ?? 'consulta';
$pestanaArbol = $pestanaArbol ?? 'articulacion_pdi';
$lado = $lado ?? 'ingresos';
$versiones = $versiones ?? [];
$versionIdActual = $versionIdActual ?? null;
$snapshots = $snapshots ?? [];
$snapshotIdActual = $snapshotIdActual ?? null;
$dependenciasTodas = $dependenciasTodas ?? [];
$dependenciaFiltroActual = $dependenciaFiltroActual ?? null;
$cuadreProyectos = $cuadreProyectos ?? [];
$bloque = $bloque ?? 'gastos';
$datos = $datos ?? ['nodos' => [], 'totales' => [], 'serie' => []];
$paramsTab = match ($tab) {
    'programacion' => ['lado' => $lado],
    'techos' => ['bloque' => $bloque],
    default => [],
};
$error = $error ?? '';
$exito = $exito ?? '';

$anioLabelActivo = (new AnioPresupuestal())->obtenerActivos()[0]['anio'] ?? date('Y');

function analisisUrl(string $tab, string $vista, array $extra = []): string
{
    $parametros = array_merge(['ruta' => 'analisis', 'tab' => $tab, 'vista' => $vista], $extra);

    return 'index.php?' . http_build_query($parametros);
}

require __DIR__ . '/../parciales/encabezado.php';
?>

<style>
    /* Cadena de alto forzada a 100vh (mismo patrón que vista/peticiones/tipo-detalle.php) — esta
       página no usa barra-modulo.php, así que .area-contenido pasa a alojar directo la franja +
       el contenido de la pestaña activa. */
    html, body {
        height: 100%;
        overflow: hidden;
    }

    .layout {
        height: 100vh;
        min-height: 0;
    }

    .contenido-principal {
        min-height: 0;
    }

    .area-contenido {
        display: flex;
        flex-direction: column;
        min-height: 0;
        flex: 1;
        padding: 0;
    }

    /* Una sola franja (el doble de alta que la versión original de 2rem, para que quepan cómodos
       las 4 pestañas + el grupo de botones + el toggle de 3 vías) — no dos franjas separadas. */
    .analisis-barra {
        flex-shrink: 0;
        min-height: 4rem;
        max-height: 4rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0 1rem;
        border-bottom: 1px solid var(--color-borde);
        background: var(--color-superficie);
        flex-wrap: wrap;
        overflow-x: auto;
    }

    .analisis-tabs {
        display: flex;
        align-items: center;
        gap: 1.1rem;
        flex-shrink: 0;
    }

    .analisis-tabs a {
        font-size: 0.82rem;
        color: var(--color-texto-secundario);
        text-decoration: none;
        white-space: nowrap;
        padding: 0.3rem 0;
        border-bottom: 2px solid transparent;
        transition: color var(--transicion), border-color var(--transicion);
    }

    .analisis-tabs a:hover {
        color: var(--color-texto);
    }

    .analisis-tabs a.activa {
        color: var(--color-primario);
        border-bottom-color: var(--color-primario);
        font-weight: 600;
    }

    .analisis-acciones {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-shrink: 0;
        margin-left: auto;
    }

    .analisis-grupo-datos {
        display: inline-flex;
        align-items: center;
        gap: 0.1rem;
        background: var(--color-gris-fondo);
        border-radius: 999px;
        padding: 0.15rem;
    }

    .analisis-icono-boton {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--color-gris-texto);
        cursor: pointer;
        transition: background var(--transicion), color var(--transicion);
    }

    .analisis-icono-boton:hover {
        background: rgba(0, 0, 0, 0.08);
    }

    .analisis-icono-boton.analisis-icono-exportar {
        color: var(--color-primario);
    }

    .analisis-toggle {
        display: inline-flex;
        border-radius: 999px;
        background: var(--color-fondo);
        border: 1px solid var(--color-borde);
        flex-shrink: 0;
    }

    .analisis-toggle a {
        border: none;
        background: transparent;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.78rem;
        cursor: pointer;
        color: var(--color-texto-secundario);
        text-decoration: none;
        white-space: nowrap;
        transition: background var(--transicion), color var(--transicion);
    }

    .analisis-toggle a.activo {
        background: var(--color-primario);
        color: #fff;
    }

    .analisis-selector-modo {
        font-size: 0.78rem;
        border: 1px solid var(--color-borde);
        border-radius: 6px;
        padding: 0.2rem 0.4rem;
        color: var(--color-texto);
        max-width: 220px;
    }

    .analisis-contenido {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding-top: 1rem;
    }

    .analisis-mensaje {
        margin: 0.75rem 1rem 0;
        padding: 0.6rem 0.9rem;
        border-radius: 8px;
        font-size: 0.85rem;
        white-space: pre-line;
        flex-shrink: 0;
    }

    .analisis-mensaje-error {
        background: #fdecea;
        color: #a83232;
        border: 1px solid #f3c2bc;
    }

    .analisis-mensaje-exito {
        background: #e8f6ee;
        color: #1e6b3c;
        border: 1px solid #bfe6cd;
    }
</style>

<div class="analisis-barra">
    <?php $pestanasPermitidas = AccesoAnalisis::actual()['pestanas']; ?>
    <nav class="analisis-tabs">
        <?php if (in_array('programacion', $pestanasPermitidas, true)): ?>
        <a href="<?= htmlspecialchars(analisisUrl('programacion', $vista, ['lado' => $lado])) ?>" class="<?= $tab === 'programacion' ? 'activa' : '' ?>">Programación presupuestal <?= htmlspecialchars((string) $anioLabelActivo) ?></a>
        <?php endif; ?>
        <?php if (in_array('pdi', $pestanasPermitidas, true)): ?>
        <a href="<?= htmlspecialchars(analisisUrl('pdi', $vista)) ?>" class="<?= $tab === 'pdi' ? 'activa' : '' ?>">Articulación PDI</a>
        <?php endif; ?>
        <?php if (in_array('analisis', $pestanasPermitidas, true)): ?>
        <a href="<?= htmlspecialchars(analisisUrl('analisis', $vista)) ?>" class="<?= $tab === 'analisis' ? 'activa' : '' ?>">Análisis de distribución</a>
        <?php endif; ?>
        <?php if (in_array('proyectos', $pestanasPermitidas, true)): ?>
        <a href="<?= htmlspecialchars(analisisUrl('proyectos', $vista)) ?>" class="<?= $tab === 'proyectos' ? 'activa' : '' ?>">Proyectos</a>
        <?php endif; ?>
        <?php if (in_array('techos', $pestanasPermitidas, true)): ?>
        <a href="<?= htmlspecialchars(analisisUrl('techos', $vista === 'usuario' ? 'usuario' : 'tiempo_real')) ?>" class="<?= $tab === 'techos' ? 'activa' : '' ?>">Techos y Metas</a>
        <?php endif; ?>
        <?php if (in_array('actas', $pestanasPermitidas, true)): ?>
        <a href="<?= htmlspecialchars(analisisUrl('actas', $vista)) ?>" class="<?= $tab === 'actas' ? 'activa' : '' ?>">Actas</a>
        <?php endif; ?>
    </nav>

    <div class="analisis-acciones">
        <?php
        // Exportar de las pestañas de árbol: la misma URL que se está viendo (lado, versión,
        // dependencia, fecha de corte) + accion — el controlador arma el árbol igual que para la
        // página y lo descarga (ver AnalisisControlador::exportarArbol()).
        $urlExportarArbol = 'index.php?' . http_build_query(array_merge($_GET, ['ruta' => 'analisis', 'tab' => $tab, 'vista' => $vista, 'accion' => 'exportar_arbol']));
        ?>
        <?php if ($tab === 'programacion' && $vista === 'tiempo_real'): ?>
        <div class="analisis-grupo-datos" title="Plantilla / Importar / Exportar">
            <a class="analisis-icono-boton" href="<?= htmlspecialchars(analisisUrl('programacion', 'tiempo_real', ['lado' => $lado, 'accion' => 'exportar_plantilla_presupuesto'])) ?>" title="Plantilla">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </a>
            <button type="button" class="analisis-icono-boton" id="analisis-boton-importar-presupuesto" title="Importar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </button>
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </a>
        </div>

        <form method="POST" action="<?= htmlspecialchars(analisisUrl('programacion', 'tiempo_real', ['lado' => $lado])) ?>" enctype="multipart/form-data" id="analisis-form-importar-presupuesto" style="display:none;">
            <input type="hidden" name="accion" value="importar_presupuesto">
            <input type="file" name="archivo" id="analisis-archivo-importar-presupuesto" accept=".xlsx" hidden>
        </form>
        <?php elseif ($tab === 'proyectos' && $vista === 'tiempo_real'): ?>
        <div class="analisis-grupo-datos" title="Plantilla / Importar / Exportar">
            <a class="analisis-icono-boton" href="<?= htmlspecialchars(analisisUrl('proyectos', 'tiempo_real', ['accion' => 'exportar_plantilla_proyectos'])) ?>" title="Plantilla">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </a>
            <button type="button" class="analisis-icono-boton" id="analisis-boton-importar-presupuesto" title="Importar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </button>
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </a>
        </div>

        <form method="POST" action="<?= htmlspecialchars(analisisUrl('proyectos', 'tiempo_real')) ?>" enctype="multipart/form-data" id="analisis-form-importar-presupuesto" style="display:none;">
            <input type="hidden" name="accion" value="importar_proyectos">
            <input type="file" name="archivo" id="analisis-archivo-importar-presupuesto" accept=".xlsx" hidden>
        </form>
        <?php elseif ($tab === 'analisis'): ?>
        <?php
        // Exporta lo que se está viendo: mismo snapshot (Repositorio) o dependencia (Usuario).
        $extraExportarAnalisis = ['accion' => 'exportar_analisis'];
        if ($vista === 'repositorio' && !empty($snapshotIdActual)) {
            $extraExportarAnalisis['snapshot_id'] = $snapshotIdActual;
        } elseif ($vista === 'usuario' && !empty($dependenciaFiltroActual)) {
            $extraExportarAnalisis['dependencia'] = $dependenciaFiltroActual;
        }
        ?>
        <div class="analisis-grupo-datos" title="Exportar">
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars(analisisUrl('analisis', $vista, $extraExportarAnalisis)) ?>" title="Exportar a Excel (resumen + una hoja por módulo)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </a>
        </div>
        <?php elseif (in_array($tab, ['pdi', 'programacion'], true) && $vista === 'tiempo_real'): ?>
        <div class="analisis-grupo-datos" title="Plantilla / Importar / Exportar (todavía sin conectar a un backend real)">
            <button type="button" class="analisis-icono-boton" onclick="alert('Prueba de interfaz: esta página todavía no genera una plantilla real de este árbol.')" title="Plantilla">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </button>
            <button type="button" class="analisis-icono-boton" onclick="alert('Prueba de interfaz: esta página todavía no importa datos reales.')" title="Importar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </button>
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </a>
        </div>
        <?php elseif (in_array($tab, ['pdi', 'programacion', 'proyectos'], true)): ?>
        <div class="analisis-grupo-datos" title="Exportar">
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </a>
        </div>
        <?php endif; ?>

        <?php if (AccesoAnalisis::actual()['clase'] === 'superadmin' && $tab !== 'actas'): ?>
        <div class="analisis-toggle">
            <a href="<?= htmlspecialchars(analisisUrl($tab, 'tiempo_real', $paramsTab)) ?>" class="<?= $vista === 'tiempo_real' ? 'activo' : '' ?>">Tiempo real</a>
            <?php if ($tab !== 'techos'): ?>
            <a href="<?= htmlspecialchars(analisisUrl($tab, 'repositorio', $paramsTab)) ?>" class="<?= $vista === 'repositorio' ? 'activo' : '' ?>">Repositorio</a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars(analisisUrl($tab, 'usuario', $paramsTab)) ?>" class="<?= $vista === 'usuario' ? 'activo' : '' ?>">Usuario</a>
        </div>
        <?php endif; ?>

        <?php $esSuperadminAnalisis = AccesoAnalisis::actual()['clase'] === 'superadmin'; ?>
        <?php if ($esSuperadminAnalisis && $vista === 'repositorio' && in_array($tab, ['pdi', 'programacion', 'proyectos'], true)): ?>
        <select class="analisis-selector-modo" onchange="if(this.value){window.location.href=this.value;}">
            <?php if (empty($versiones)): ?>
            <option value="">Sin versiones guardadas</option>
            <?php endif; ?>
            <?php foreach ($versiones as $version): ?>
            <option value="<?= htmlspecialchars(analisisUrl($tab, 'repositorio', ['version_id' => $version['id']] + ($tab === 'programacion' ? ['lado' => $lado] : []))) ?>" <?= (int) $version['id'] === (int) $versionIdActual ? 'selected' : '' ?>>
                <?= htmlspecialchars($version['nombre']) ?> — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($version['creado_en']))) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php elseif ($esSuperadminAnalisis && $tab === 'analisis' && $vista === 'repositorio'): ?>
        <select class="analisis-selector-modo" onchange="if(this.value){window.location.href=this.value;}">
            <?php if (empty($snapshots)): ?>
            <option value="">Sin snapshots guardados</option>
            <?php endif; ?>
            <?php foreach ($snapshots as $snapshot): ?>
            <option value="<?= htmlspecialchars(analisisUrl($tab, 'repositorio', ['snapshot_id' => $snapshot['id']] + (isset($origenActivo) ? ['origen' => $origenActivo] : []))) ?>" <?= (int) $snapshot['id'] === (int) $snapshotIdActual ? 'selected' : '' ?>>
                <?= htmlspecialchars($snapshot['nombre']) ?> — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($snapshot['creado_en']))) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php elseif ($vista === 'usuario' && $tab !== 'actas'): ?>
        <select class="analisis-selector-modo" onchange="if(this.value){window.location.href=this.value;}">
            <?php foreach (AccesoAnalisis::dependenciasSelector($dependenciasTodas) as $dependenciaOpcion): ?>
            <option value="<?= htmlspecialchars(analisisUrl($tab, 'usuario', ['dependencia' => $dependenciaOpcion['nombre']] + $paramsTab + (isset($origenActivo) ? ['origen' => $origenActivo] : []))) ?>" <?= $dependenciaOpcion['nombre'] === $dependenciaFiltroActual ? 'selected' : '' ?>>
                <?= htmlspecialchars(Dependencia::nombreVisible($dependenciaOpcion['nombre'])) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
</div>

<?php if ($error !== '' || $exito !== ''): ?>
<div class="analisis-mensaje <?= $error !== '' ? 'analisis-mensaje-error' : 'analisis-mensaje-exito' ?>"><?= nl2br(htmlspecialchars($error !== '' ? $error : $exito)) ?></div>
<?php endif; ?>

<div class="analisis-contenido">
    <?php if ($tab === 'pdi' || $tab === 'programacion' || $tab === 'proyectos'): ?>
        <?php if ($tab === 'proyectos'): ?>
            <?php require __DIR__ . '/parciales/proyectos-cuadre.php'; ?>
        <?php endif; ?>
        <?php require __DIR__ . '/parciales/arbol.php'; ?>
    <?php elseif ($tab === 'techos'): ?>
        <?php require __DIR__ . '/parciales/techos-metas.php'; ?>
    <?php elseif ($tab === 'actas'): ?>
        <?php require __DIR__ . '/parciales/actas.php'; ?>
    <?php else: ?>
        <div style="display:flex; gap:0.75rem; flex:1; min-height:0; padding:0.5rem;">
            <div style="width:220px; flex-shrink:0;">
                <?php require __DIR__ . '/parciales/selector-modulos.php'; ?>
            </div>
            <div style="flex:1; min-width:0; display:flex; flex-direction:column; min-height:0;">
                <?php require __DIR__ . '/parciales/tabla-modulo.php'; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var botonImportarPresupuesto = document.getElementById('analisis-boton-importar-presupuesto');
    var archivoImportarPresupuesto = document.getElementById('analisis-archivo-importar-presupuesto');
    var formImportarPresupuesto = document.getElementById('analisis-form-importar-presupuesto');
    if (botonImportarPresupuesto && archivoImportarPresupuesto && formImportarPresupuesto) {
        botonImportarPresupuesto.addEventListener('click', function () { archivoImportarPresupuesto.click(); });
        archivoImportarPresupuesto.addEventListener('change', function () {
            if (archivoImportarPresupuesto.files.length > 0) {
                formImportarPresupuesto.submit();
            }
        });
    }
});
</script>
