<?php
/**
 * Pestaña "Actas" de ?ruta=analisis: una fila por facultad con las actas que cargó
 * en el módulo Actas y el enlace para descargar cada una. Variables esperadas (ver
 * AnalisisControlador::renderizarActas()): $dependenciasActas (cada dependencia con su lista 'actas').
 */

$dependenciasActas = $dependenciasActas ?? [];
$totalActas = array_sum(array_map(static fn (array $dependencia): int => count($dependencia['actas']), $dependenciasActas));
$dependenciasConActa = count(array_filter($dependenciasActas, static fn (array $dependencia): bool => !empty($dependencia['actas'])));
$urlDescargarActa = static fn (int $id): string => 'index.php?' . http_build_query(['ruta' => 'analisis', 'tab' => 'actas', 'accion' => 'descargar_acta', 'id' => $id]);
$tamanoLegible = static fn (int $bytes): string => $bytes >= 1048576
    ? number_format($bytes / 1048576, 1, ',', '.') . ' MB'
    : number_format(max(1, $bytes / 1024), 0, ',', '.') . ' KB';
?>

<style>
    .actas-analisis {
        flex: 1;
        min-height: 0;
        overflow: auto;
        padding: 0 0.5rem 1rem;
    }

    .actas-analisis-resumen {
        margin: 0 0 0.75rem;
        font-size: 0.85rem;
        color: var(--color-texto-secundario);
    }

    .actas-analisis-tabla {
        width: 100%;
        border-collapse: collapse;
        background: var(--color-superficie);
        border: 1px solid var(--color-borde);
        border-radius: 8px;
        font-size: 0.85rem;
    }

    .actas-analisis-tabla th {
        position: sticky;
        top: 0;
        background: var(--color-fondo);
        color: var(--color-texto-secundario);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        text-align: left;
        padding: 0.6rem 0.75rem;
        border-bottom: 1px solid var(--color-borde);
    }

    .actas-analisis-tabla td {
        padding: 0.6rem 0.75rem;
        border-bottom: 1px solid var(--color-borde);
        vertical-align: top;
        color: var(--color-texto);
    }

    .actas-analisis-tabla tr:last-child td {
        border-bottom: none;
    }

    .actas-analisis-dependencia {
        font-weight: 600;
    }


    .actas-analisis-lista {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .actas-analisis-enlace {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--color-primario);
        text-decoration: none;
        font-weight: 500;
        word-break: break-word;
    }

    .actas-analisis-enlace:hover {
        text-decoration: underline;
    }

    .actas-analisis-meta {
        display: block;
        margin-left: 1.4rem;
        font-size: 0.75rem;
        color: var(--color-texto-tenue);
    }

    .actas-analisis-sin-acta {
        color: var(--color-texto-tenue);
        font-style: italic;
    }
</style>

<div class="actas-analisis">
    <p class="actas-analisis-resumen">
        <?= $dependenciasConActa ?> de <?= count($dependenciasActas) ?> facultades han cargado acta · <?= $totalActas ?> acta<?= $totalActas === 1 ? '' : 's' ?> en total
    </p>

    <table class="actas-analisis-tabla">
        <thead>
            <tr>
                <th style="width: 35%;">Facultad</th>
                <th>Actas</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dependenciasActas)): ?>
            <tr><td colspan="2" class="actas-analisis-sin-acta">No hay facultades para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($dependenciasActas as $dependencia): ?>
            <tr>
                <td class="actas-analisis-dependencia">
                    <?= htmlspecialchars(Dependencia::nombreVisible($dependencia['nombre'])) ?>
                </td>
                <td>
                    <?php if (empty($dependencia['actas'])): ?>
                    <span class="actas-analisis-sin-acta">Sin acta</span>
                    <?php else: ?>
                    <ul class="actas-analisis-lista">
                        <?php foreach ($dependencia['actas'] as $acta): ?>
                        <li>
                            <a class="actas-analisis-enlace" href="<?= htmlspecialchars($urlDescargarActa((int) $acta['id'])) ?>" target="_blank" rel="noopener" title="Descargar acta">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                <?= htmlspecialchars($acta['nombre_archivo']) ?>
                            </a>
                            <span class="actas-analisis-meta">
                                Vigencia <?= htmlspecialchars((string) $acta['anio']) ?>
                                · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($acta['creado_en']))) ?>
                                · <?= htmlspecialchars($acta['remitente_nombre']) ?><?= !empty($acta['destinatario_nombre']) ? ' → ' . htmlspecialchars($acta['destinatario_nombre']) : '' ?>
                                · <?= $tamanoLegible((int) $acta['tamano_bytes']) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
