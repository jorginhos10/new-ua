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
 * - $barraTextoVolver (string, opcional, por defecto 'Volver a Peticiones'): tooltip/title
 *   del ícono de la zona 1 — sobreescribir cuando "volver" no es a Peticiones (ej. un
 *   módulo en su propio estado "edición" nativo, sin venir de un deep-link).
 * - $barraBotonesSecundarios (array, opcional): filas ['id'=>?, 'icono'=>string, 'etiqueta'=>string,
 *   'tipo'=>'button'|'a' (por defecto 'button'), 'href'=>?string, 'disabled'=>bool (por defecto false),
 *   'titulo_disabled'=>?string]
 * - $barraBotonPrincipal (array|null, opcional): ['id'=>?, 'etiqueta'=>string, 'tipo'=>'button'|'a'
 *   (por defecto 'button'), 'href'=>?string, 'disabled'=>bool (por defecto false), 'form'=>?string
 *   (id de un <form> externo a enviar — usa type="submit" + form="..." en vez de un botón simple)]
 * - $barraAccionesExtra (string|null, HTML crudo, opcional): contenido libre al inicio de la zona 3
 *   (antes de los botones), ej. un campo de filtro — para cuando ese contenido no encaja en el
 *   formato fijo de $barraBotonesSecundarios.
 *
 * Modo agrupado (opcional, patrón de Dev > Tabla — vista/dev/pruebas/tabla.php): si se define
 * $barraGruposIconos, la zona 3 se pinta como píldoras de íconos en vez de la lista plana de
 * $barraBotonesSecundarios, y la zona 4 como grupo principal (botón azul + Enviar como ícono).
 * - $barraGruposIconos (array): ['basico' => [...], 'especifico' => [...], 'datos' => [...]] — cada
 *   grupo es una lista de botones (mismo formato que $barraBotonesSecundarios) o de ['separador' => true].
 *   Un grupo vacío no se muestra. Cada grupo con botones se colapsa a un menú "⋯ Más acciones" en
 *   pantallas angostas.
 * - $barraBotonEnviar (array|null): ['id', 'etiqueta', 'disabled', 'titulo_disabled'] — ícono de
 *   enviar pegado al botón principal (gris deshabilitado, verde habilitado).
 * - $barraBuscar (string|null): selector CSS del contenedor donde buscar (ej. '[data-toggle-envio="gastos"]').
 *   Agrega la lupa al inicio del grupo básico; filtra las filas de sus tablas y las tarjetas de lote.
 */

$barraEstado = $barraEstado ?? 'creacion';
$barraTituloExtra = $barraTituloExtra ?? null;
$barraRutaVolver = $barraRutaVolver ?? null;
$barraTextoVolver = $barraTextoVolver ?? 'Volver a Peticiones';
$barraBotonesSecundarios = $barraBotonesSecundarios ?? [];
$barraBotonPrincipal = $barraBotonPrincipal ?? null;
$barraAccionesExtra = $barraAccionesExtra ?? null;
$barraGruposIconos = $barraGruposIconos ?? null;
$barraBotonEnviar = $barraBotonEnviar ?? null;
$barraBuscar = $barraBuscar ?? null;

// Íconos del modo agrupado: los de referencia de Dev > Tabla (16px). Los del modo plano (abajo,
// 18px) se dejan tal cual hasta que cada módulo pase al modo agrupado.
$barraIconosGrupo = [
    'seleccionar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>',
    'editar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>',
    'duplicar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
    'eliminar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>',
    'nuevo' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'plantilla' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>',
    'importar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
    'exportar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>',
    'enviar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
    'mas' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle></svg>',
];

$barraIconos = [
    'volver' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
    'nuevo' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    'enviar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
    'exportar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
    'importar' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>',
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
        <a href="<?= htmlspecialchars($barraRutaVolver) ?>" class="boton-icono-accion barra-modulo-volver" data-tooltip="<?= htmlspecialchars($barraTextoVolver) ?>" title="<?= htmlspecialchars($barraTextoVolver) ?>">
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
        <?php if ($barraAccionesExtra !== null): ?>
        <?= $barraAccionesExtra ?>
        <?php endif; ?>

        <?php if ($barraGruposIconos !== null): ?>
        <?php foreach (['basico', 'especifico', 'datos'] as $barraClaveGrupo): ?>
        <?php
        $barraItemsGrupo = $barraGruposIconos[$barraClaveGrupo] ?? [];
        $barraBotonesGrupo = array_values(array_filter($barraItemsGrupo, static fn (array $item): bool => empty($item['separador'])));
        ?>
        <?php if (!empty($barraBotonesGrupo)): ?>
        <div class="grupo-iconos colapsable" data-grupo="<?= $barraClaveGrupo ?>">
            <?php if ($barraClaveGrupo === 'basico' && $barraBuscar !== null): ?>
            <div class="buscar-envoltorio" data-buscar-en="<?= htmlspecialchars($barraBuscar) ?>">
                <button type="button" class="icono-boton barra-boton-buscar" title="Buscar"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></button>
                <div class="combo-envoltorio combo-buscar">
                    <div class="combo-fantasma" aria-hidden="true"><span class="combo-fantasma-tecleado"></span><span class="combo-fantasma-sugerencia"></span></div>
                    <input type="text" class="combo-input barra-campo-buscar" placeholder="Buscar en la tabla…" autocomplete="off">
                </div>
            </div>
            <span class="separador-grupo-basico"></span>
            <?php endif; ?>
            <?php foreach ($barraItemsGrupo as $barraItem): ?>
            <?php if (!empty($barraItem['separador'])): ?>
            <span class="separador-grupo-basico"></span>
            <?php else: ?>
            <?php
            $barraItemDisabled = !empty($barraItem['disabled']);
            $barraItemTitulo = $barraItemDisabled && !empty($barraItem['titulo_disabled']) ? $barraItem['titulo_disabled'] : $barraItem['etiqueta'];
            $barraItemId = !empty($barraItem['id']) ? ' id="' . htmlspecialchars($barraItem['id']) . '"' : '';
            $barraItemIcono = $barraIconosGrupo[$barraItem['icono']] ?? '';
            ?>
            <?php if (($barraItem['tipo'] ?? 'button') === 'a' && !$barraItemDisabled): ?>
            <a href="<?= htmlspecialchars($barraItem['href'] ?? '#') ?>"<?= $barraItemId ?> class="icono-boton" title="<?= htmlspecialchars($barraItemTitulo) ?>"><?= $barraItemIcono ?></a>
            <?php else: ?>
            <button type="button"<?= $barraItemId ?> class="icono-boton" title="<?= htmlspecialchars($barraItemTitulo) ?>"<?= $barraItemDisabled ? ' disabled' : '' ?>><?= $barraItemIcono ?></button>
            <?php endif; ?>
            <?php endif; ?>
            <?php endforeach; ?>
            <button type="button" class="icono-boton grupo-iconos-colapsar" title="Más acciones" data-toggle-menu><?= $barraIconosGrupo['mas'] ?></button>
            <div class="grupo-iconos-menu">
                <?php foreach ($barraBotonesGrupo as $barraItem): ?>
                <?php $barraItemIcono = $barraIconosGrupo[$barraItem['icono']] ?? ''; ?>
                <?php if (($barraItem['tipo'] ?? 'button') === 'a' && empty($barraItem['disabled'])): ?>
                <a href="<?= htmlspecialchars($barraItem['href'] ?? '#') ?>"><?= $barraItemIcono ?><?= htmlspecialchars($barraItem['etiqueta']) ?></a>
                <?php elseif (!empty($barraItem['id'])): ?>
                <button type="button" data-proxy="<?= htmlspecialchars($barraItem['id']) ?>"<?= !empty($barraItem['disabled']) ? ' disabled' : '' ?>><?= $barraItemIcono ?><?= htmlspecialchars($barraItem['etiqueta']) ?></button>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($barraBotonPrincipal !== null || $barraBotonEnviar !== null): ?>
        <div class="grupo-boton-principal">
            <?php if ($barraBotonPrincipal !== null): ?>
            <?php if (($barraBotonPrincipal['tipo'] ?? 'button') === 'a'): ?>
            <a href="<?= htmlspecialchars($barraBotonPrincipal['href'] ?? '#') ?>"<?= !empty($barraBotonPrincipal['id']) ? ' id="' . htmlspecialchars($barraBotonPrincipal['id']) . '"' : '' ?> class="boton-principal-azul"><?= htmlspecialchars($barraBotonPrincipal['etiqueta']) ?></a>
            <?php else: ?>
            <button
                type="<?= !empty($barraBotonPrincipal['form']) ? 'submit' : 'button' ?>"
                <?= !empty($barraBotonPrincipal['id']) ? 'id="' . htmlspecialchars($barraBotonPrincipal['id']) . '"' : '' ?>
                <?= !empty($barraBotonPrincipal['form']) ? 'form="' . htmlspecialchars($barraBotonPrincipal['form']) . '"' : '' ?>
                class="boton-principal-azul"
                <?= !empty($barraBotonPrincipal['disabled']) ? 'disabled' : '' ?>
            ><?= htmlspecialchars($barraBotonPrincipal['etiqueta']) ?></button>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($barraBotonEnviar !== null): ?>
            <?php $barraEnviarDisabled = !empty($barraBotonEnviar['disabled']); ?>
            <button
                type="button"
                <?= !empty($barraBotonEnviar['id']) ? 'id="' . htmlspecialchars($barraBotonEnviar['id']) . '"' : '' ?>
                class="boton-enviar-icono<?= $barraEnviarDisabled ? '' : ' habilitado' ?>"
                title="<?= htmlspecialchars($barraEnviarDisabled && !empty($barraBotonEnviar['titulo_disabled']) ? $barraBotonEnviar['titulo_disabled'] : $barraBotonEnviar['etiqueta']) ?>"
                <?= $barraEnviarDisabled ? 'disabled' : '' ?>
            ><?= $barraIconosGrupo['enviar'] ?></button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>

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
            type="<?= !empty($barraBotonPrincipal['form']) ? 'submit' : 'button' ?>"
            <?= !empty($barraBotonPrincipal['id']) ? 'id="' . htmlspecialchars($barraBotonPrincipal['id']) . '"' : '' ?>
            <?= !empty($barraBotonPrincipal['form']) ? 'form="' . htmlspecialchars($barraBotonPrincipal['form']) . '"' : '' ?>
            class="boton-agregar"
            <?= !empty($barraBotonPrincipal['disabled']) ? 'disabled' : '' ?>
        ><?= htmlspecialchars($barraBotonPrincipal['etiqueta']) ?></button>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php unset(
    $barraTitulo, $barraTituloExtra, $barraEstado, $barraRutaVolver, $barraTextoVolver, $barraBotonesSecundarios,
    $barraBotonPrincipal, $barraAccionesExtra, $barraIconos, $barraGruposIconos, $barraBotonEnviar, $barraIconosGrupo, $barraBuscar,
    $barraClaveGrupo, $barraItemsGrupo, $barraBotonesGrupo, $barraItem, $barraItemDisabled, $barraItemTitulo,
    $barraItemId, $barraItemIcono, $barraEnviarDisabled
); ?>
