<?php
/**
 * Barra superior de 4 zonas, estandarizada para los módulos de tipo "Petición" (Gastos,
 * Extensión, Postgrado, Unidad de Salud, Sin Excedente, Solicitudes, Perfil de proyectos).
 *
 * Zona 1: volver (solo en estados "consulta"/"edicion", oculta en "creacion").
 * Zona 2: título del módulo (+ elemento extra opcional, ej. selector de ítem de autogestión).
 * Zona 3: acciones secundarias — botones de solo ícono con tooltip.
 * Zona 4: acción principal — botón destacado con texto.
 *
 * Variables esperadas antes de incluir este parcial:
 * - $barraTitulo (string)
 * - $barraTituloExtra (string|null, HTML crudo, opcional)
 * - $barraEstado ('creacion'|'consulta'|'edicion', opcional, por defecto 'creacion')
 * - $barraRutaVolver (string|null, opcional): href de la zona 1
 * - $barraBotonesSecundarios (array, opcional): filas ['id'=>?, 'icono'=>string, 'etiqueta'=>string,
 *   'tipo'=>'button'|'a' (por defecto 'button'), 'href'=>?string, 'disabled'=>bool (por defecto false),
 *   'titulo_disabled'=>?string]
 * - $barraBotonPrincipal (array|null, opcional): ['id'=>?, 'etiqueta'=>string, 'tipo'=>'button'|'a'
 *   (por defecto 'button'), 'href'=>?string, 'disabled'=>bool (por defecto false)]
 */

$barraEstado = $barraEstado ?? 'creacion';
$barraTituloExtra = $barraTituloExtra ?? null;
$barraRutaVolver = $barraRutaVolver ?? null;
$barraBotonesSecundarios = $barraBotonesSecundarios ?? [];
$barraBotonPrincipal = $barraBotonPrincipal ?? null;

$barraIconos = [
    'volver' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'nuevo' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'enviar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
    'exportar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
    'seleccionar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>',
    'editar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    'duplicar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
    'eliminar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    'guardar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>',
    'historial' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
];
?>
<div class="cabecera-modulo barra-modulo">
    <div class="barra-modulo-zona1-y-2">
        <?php if ($barraEstado !== 'creacion' && $barraRutaVolver !== null): ?>
        <a href="<?= htmlspecialchars($barraRutaVolver) ?>" class="boton-icono-accion barra-modulo-volver" data-tooltip="Volver a Peticiones" title="Volver a Peticiones">
            <?= $barraIconos['volver'] ?>
        </a>
        <?php endif; ?>

        <div class="cabecera-modulo-titulo">
            <h1><?= htmlspecialchars($barraTitulo) ?></h1>
            <?php if ($barraTituloExtra !== null): ?>
            <?= $barraTituloExtra ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="grupo-acciones-encabezado">
        <?php foreach ($barraBotonesSecundarios as $barraBotonSecundario): ?>
        <?php
        $barraBotonTipo = $barraBotonSecundario['tipo'] ?? 'button';
        $barraBotonDisabled = !empty($barraBotonSecundario['disabled']);
        $barraBotonTitulo = $barraBotonDisabled && !empty($barraBotonSecundario['titulo_disabled'])
            ? $barraBotonSecundario['titulo_disabled']
            : $barraBotonSecundario['etiqueta'];
        ?>
        <?php if ($barraBotonTipo === 'a' && !$barraBotonDisabled): ?>
        <a
            href="<?= htmlspecialchars($barraBotonSecundario['href'] ?? '#') ?>"
            <?= !empty($barraBotonSecundario['id']) ? 'id="' . htmlspecialchars($barraBotonSecundario['id']) . '"' : '' ?>
            class="boton-icono-accion"
            data-tooltip="<?= htmlspecialchars($barraBotonTitulo) ?>"
            title="<?= htmlspecialchars($barraBotonTitulo) ?>"
        ><?= $barraIconos[$barraBotonSecundario['icono']] ?? '' ?></a>
        <?php else: ?>
        <button
            type="button"
            <?= !empty($barraBotonSecundario['id']) ? 'id="' . htmlspecialchars($barraBotonSecundario['id']) . '"' : '' ?>
            class="boton-icono-accion"
            data-tooltip="<?= htmlspecialchars($barraBotonTitulo) ?>"
            title="<?= htmlspecialchars($barraBotonTitulo) ?>"
            <?= $barraBotonDisabled ? 'disabled' : '' ?>
        ><?= $barraIconos[$barraBotonSecundario['icono']] ?? '' ?></button>
        <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($barraBotonPrincipal !== null): ?>
        <?php $barraPrincipalTipo = $barraBotonPrincipal['tipo'] ?? 'button'; ?>
        <?php if ($barraPrincipalTipo === 'a'): ?>
        <a
            href="<?= htmlspecialchars($barraBotonPrincipal['href'] ?? '#') ?>"
            <?= !empty($barraBotonPrincipal['id']) ? 'id="' . htmlspecialchars($barraBotonPrincipal['id']) . '"' : '' ?>
            class="boton-agregar"
        ><?= htmlspecialchars($barraBotonPrincipal['etiqueta']) ?></a>
        <?php else: ?>
        <button
            type="button"
            <?= !empty($barraBotonPrincipal['id']) ? 'id="' . htmlspecialchars($barraBotonPrincipal['id']) . '"' : '' ?>
            class="boton-agregar"
            <?= !empty($barraBotonPrincipal['disabled']) ? 'disabled' : '' ?>
        ><?= htmlspecialchars($barraBotonPrincipal['etiqueta']) ?></button>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php unset($barraTitulo, $barraTituloExtra, $barraEstado, $barraRutaVolver, $barraBotonesSecundarios, $barraBotonPrincipal, $barraIconos); ?>
