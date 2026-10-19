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
    'programacion', 'comparativo' => ['lado' => $lado],
    'techos' => ['bloque' => $bloque],
    default => [],
};
$error = $error ?? '';
$exito = $exito ?? '';
// `usuarios.es_super_admin` (el SA): solo renderizarArbol() lo pasa; las demás pestañas lo calculan aquí.
$esSuperAdmin = $esSuperAdmin ?? ((int) ((new Usuario())->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0))['es_super_admin'] ?? 0) === 1);

$anioLabelActivo = (new AnioPresupuestal())->obtenerActivos()[0]['anio'] ?? date('Y');

function analisisUrl(string $tab, string $vista, array $extra = []): string
{
    $parametros = array_merge(['ruta' => 'analisis', 'tab' => $tab, 'vista' => $vista], $extra);

    return 'index.php?' . http_build_query($parametros);
}

// Las pestañas viven en el menú lateral (Programación presupuestal ▾, ver parciales/sidebar.php).
// Las acciones de la página (plantilla/importar/exportar, snapshot, Tiempo real/Repositorio/Usuario,
// selector de versión/dependencia) se arman aquí una vez y cada pestaña las pinta a la derecha de
// su propia barra de opciones ($accionesAnalisisHtml: árbol, Techos y Metas, tabla de
// distribución, Documentos, Actas) — así no ocupan una fila entera ni se amontonan en el encabezado.
ob_start();
?>
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
                <svg class="svg-importar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </button>
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg class="svg-exportar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </a>
            <?php if ($esSuperAdmin): ?>
            <form method="POST" action="<?= htmlspecialchars(analisisUrl('programacion', 'tiempo_real', ['lado' => $lado])) ?>" style="display:contents;" onsubmit="return confirm('¿Limpiar el árbol de ' + <?= htmlspecialchars(json_encode($lado === 'egresos' ? 'Egresos' : 'Ingresos'), ENT_QUOTES) ?> + '? Se borran todas sus líneas y valores para hacer un cargue en limpio. Antes se guarda una versión de respaldo en Repositorio.');">
                <input type="hidden" name="accion" value="limpiar_arbol">
                <button type="submit" class="analisis-icono-boton analisis-icono-limpiar" title="Limpiar árbol (cargue en limpio)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                </button>
            </form>
            <?php endif; ?>
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
                <svg class="svg-importar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </button>
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg class="svg-exportar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </a>
            <?php if ($esSuperAdmin): ?>
            <form method="POST" action="<?= htmlspecialchars(analisisUrl('proyectos', 'tiempo_real')) ?>" style="display:contents;" onsubmit="return confirm('¿Limpiar el árbol de ' + 'Proyectos de inversión' + '? Se borran todas sus líneas y valores para hacer un cargue en limpio. Antes se guarda una versión de respaldo en Repositorio.');">
                <input type="hidden" name="accion" value="limpiar_arbol">
                <button type="submit" class="analisis-icono-boton analisis-icono-limpiar" title="Limpiar árbol (cargue en limpio)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                </button>
            </form>
            <?php endif; ?>
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
                <svg class="svg-exportar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </a>
        </div>
        <?php elseif (in_array($tab, ['pdi', 'programacion'], true) && $vista === 'tiempo_real'): ?>
        <div class="analisis-grupo-datos" title="Plantilla / Importar / Exportar (todavía sin conectar a un backend real)">
            <button type="button" class="analisis-icono-boton" onclick="alert('Prueba de interfaz: esta página todavía no genera una plantilla real de este árbol.')" title="Plantilla">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </button>
            <button type="button" class="analisis-icono-boton" onclick="alert('Prueba de interfaz: esta página todavía no importa datos reales.')" title="Importar">
                <svg class="svg-importar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </button>
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg class="svg-exportar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </a>
        </div>
        <?php elseif (in_array($tab, ['pdi', 'programacion', 'comparativo', 'proyectos'], true)): ?>
        <div class="analisis-grupo-datos" title="Exportar">
            <a class="analisis-icono-boton analisis-icono-exportar" href="<?= htmlspecialchars($urlExportarArbol) ?>" title="Exportar a Excel">
                <svg class="svg-exportar" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </a>
        </div>
        <?php endif; ?>

        <?php $etiquetaSnapshot = AnalisisControlador::etiquetaSnapshotPestana($tab, $lado); ?>
        <?php if ($esSuperAdmin && $vista === 'tiempo_real' && $etiquetaSnapshot !== null): ?>
        <?php // Solo SA: modal "Solo esta página | Todas las páginas" (ver AnalisisControlador::crearSnapshotPestana()). ?>
        <form method="POST" action="<?= htmlspecialchars(analisisUrl($tab, 'tiempo_real', $paramsTab)) ?>" class="analisis-grupo-datos" title="Snapshot">
            <input type="hidden" name="accion" value="crear_snapshot_pestana">
            <input type="hidden" name="alcance" value="pagina">
            <button type="button" class="analisis-icono-boton" title="Tomar snapshot (queda como el activo en Repositorio)" onclick="document.getElementById('analisis-modal-snapshot').classList.add('abierto'); document.getElementById('analisis-snapshot-nombre').focus();">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
            </button>
            <div id="analisis-modal-snapshot" class="modal-fondo" onclick="if (event.target === this) { this.classList.remove('abierto'); }">
                <div class="modal-caja" style="max-width: 540px;">
                    <div class="modal-cabecera">
                        <h2>Tomar snapshot</h2>
                        <button type="button" class="modal-cerrar" aria-label="Cerrar" onclick="document.getElementById('analisis-modal-snapshot').classList.remove('abierto');">&times;</button>
                    </div>
                    <div class="campo">
                        <label for="analisis-snapshot-nombre">Nombre</label>
                        <input type="text" id="analisis-snapshot-nombre" name="nombre" maxlength="120" value="<?= htmlspecialchars('Snapshot ' . date('d/m/Y H:i')) ?>">
                    </div>
                    <p class="texto-atenuado" style="font-size: 0.82rem; margin: 0.6rem 0 0;" title="Con 'Todas las páginas' se crea uno por pestaña (Documentos, Programación presupuestal Ingresos y Egresos, Proyectos, Articulación PDI, Techos y Metas, Análisis de distribución) y a cada nombre se le agrega su pestaña.">
                        Cada snapshot queda como el <strong>activo</strong> de su pestaña en Repositorio.
                    </p>
                    <div class="modal-pie" style="gap: 0.5rem; flex-wrap: wrap;">
                        <button type="button" class="boton-secundario" onclick="document.getElementById('analisis-modal-snapshot').classList.remove('abierto');">Cancelar</button>
                        <button type="submit" class="boton-secundario" onclick="this.form.alcance.value = 'todas'; return confirm('¿Tomar un snapshot de TODAS las páginas de Análisis? Cada uno quedará como el activo de su pestaña.');">Todas las páginas</button>
                        <button type="submit" class="boton-agregar" onclick="this.form.alcance.value = 'pagina';">Solo esta página (<?= htmlspecialchars($etiquetaSnapshot) ?>)</button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <?php if (AccesoAnalisis::actual()['clase'] === 'superadmin' && !in_array($tab, ['actas'], true)): ?>
        <div class="analisis-toggle">
            <a href="<?= htmlspecialchars(analisisUrl($tab, 'tiempo_real', $paramsTab)) ?>" class="<?= $vista === 'tiempo_real' ? 'activo' : '' ?>">Tiempo real</a>
            <a href="<?= htmlspecialchars(analisisUrl($tab, 'repositorio', $paramsTab)) ?>" class="<?= $vista === 'repositorio' ? 'activo' : '' ?>">Repositorio</a>
            <a href="<?= htmlspecialchars(analisisUrl($tab, 'usuario', $paramsTab)) ?>" class="<?= $vista === 'usuario' ? 'activo' : '' ?>">Usuario</a>
        </div>
        <?php endif; ?>

        <?php $esSuperadminAnalisis = AccesoAnalisis::actual()['clase'] === 'superadmin'; ?>
        <?php if ($esSuperadminAnalisis && $vista === 'repositorio' && in_array($tab, ['pdi', 'programacion', 'comparativo', 'proyectos', 'techos', 'presentacion'], true)): ?>
        <select class="analisis-selector-modo" onchange="if(this.value){window.location.href=this.value;}">
            <?php if (empty($versiones)): ?>
            <option value="">Sin versiones guardadas</option>
            <?php endif; ?>
            <?php
            $extraSelectorVersion = match ($tab) {
                'programacion', 'comparativo' => ['lado' => $lado],
                'techos' => ['bloque' => $bloque] + ($dependenciaFiltroActual !== null ? ['dependencia' => $dependenciaFiltroActual] : []),
                default => [],
            };
            ?>
            <?php foreach ($versiones as $version): ?>
            <option value="<?= htmlspecialchars(analisisUrl($tab, 'repositorio', ['version_id' => $version['id']] + $extraSelectorVersion)) ?>" <?= (int) $version['id'] === (int) $versionIdActual ? 'selected' : '' ?>>
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
        <?php elseif (($vista === 'usuario' && !in_array($tab, ['actas', 'presentacion'], true)) || ($vista === 'repositorio' && $tab === 'techos')): ?>
        <select class="analisis-selector-modo" onchange="if(this.value){window.location.href=this.value;}">
            <?php foreach (AccesoAnalisis::dependenciasSelector($dependenciasTodas) as $dependenciaOpcion): ?>
            <option value="<?= htmlspecialchars(analisisUrl($tab, $vista, ['dependencia' => $dependenciaOpcion['nombre']] + $paramsTab + (isset($origenActivo) ? ['origen' => $origenActivo] : []))) ?>" <?= $dependenciaOpcion['nombre'] === $dependenciaFiltroActual ? 'selected' : '' ?>>
                <?= htmlspecialchars(Dependencia::nombreVisible($dependenciaOpcion['nombre'])) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
<?php
$accionesAnalisisHtml = ob_get_clean();
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

    /* Limpiar árbol: acción destructiva, se tiñe de rojo al pasar el cursor. */
    .analisis-icono-boton.analisis-icono-limpiar:hover {
        background: var(--color-error-fondo);
        color: var(--color-error-texto);
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

<?php if ($error !== '' || $exito !== ''): ?>
<div class="analisis-mensaje <?= $error !== '' ? 'analisis-mensaje-error' : 'analisis-mensaje-exito' ?>"><?= nl2br(htmlspecialchars($error !== '' ? $error : $exito)) ?></div>
<?php endif; ?>

<div class="analisis-contenido">
    <?php if (in_array($tab, ['pdi', 'programacion', 'comparativo', 'proyectos'], true)): ?>
        <?php if ($tab === 'proyectos'): ?>
            <?php require __DIR__ . '/parciales/proyectos-cuadre.php'; ?>
        <?php endif; ?>
        <?php require __DIR__ . '/parciales/arbol.php'; ?>
    <?php elseif ($tab === 'techos'): ?>
        <?php require __DIR__ . '/parciales/techos-metas.php'; ?>
    <?php elseif ($tab === 'actas'): ?>
        <?php require __DIR__ . '/parciales/actas.php'; ?>
    <?php elseif ($tab === 'presentacion'): ?>
        <?php require __DIR__ . '/parciales/presentacion.php'; ?>
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
