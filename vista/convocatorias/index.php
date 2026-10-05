<?php
$tituloPagina = 'Convocatorias de proyectos';
require __DIR__ . '/../parciales/encabezado.php';

$etiquetasAudiencia = [
    'invitado' => 'Invitados',
    'administrador' => 'Administradores',
    'ambos' => 'Invitados y administradores',
];
$fuentesMarcadasSet = array_flip(array_map('intval', $fuentesMarcadas ?? []));
$editando = $convocatoriaEditar !== null;
$topeEditando = $editando ? $convocatoriaEditar['tope_por_proyecto'] : null;
$modoTopeEditando = $topeEditando !== null ? 'tope' : 'libre';
?>

    <div class="tarjeta">
        <div class="pestanas" style="margin: 0 0 1rem;">
            <a href="index.php?ruta=convocatorias" class="pestana<?= $vista === 'convocatorias' ? ' activa' : '' ?>">Convocatorias</a>
            <a href="index.php?ruta=convocatorias&vista=consolidado" class="pestana<?= $vista === 'consolidado' ? ' activa' : '' ?>">Consolidado</a>
        </div>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if ($vista === 'convocatorias'): ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Convocatoria</th>
                        <th>Vigencia</th>
                        <th>Formulan</th>
                        <th>Periodo</th>
                        <th>Reloj</th>
                        <th>Valor por proyecto</th>
                        <th>Proyectos</th>
                        <th>Valor total</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($convocatorias as $convocatoria): $reloj = CuentaRegresiva::calcular($convocatoria['fecha_inicio'], $convocatoria['fecha_cierre']); ?>
                    <tr>
                        <td><?= htmlspecialchars($convocatoria['nombre']) ?></td>
                        <td><?= (int) $convocatoria['vigencia'] ?></td>
                        <td><?= htmlspecialchars($etiquetasAudiencia[$convocatoria['audiencia']] ?? $convocatoria['audiencia']) ?></td>
                        <td class="texto-nowrap"><?= htmlspecialchars($reloj['fecha_inicio']) ?> – <?= htmlspecialchars($reloj['fecha_cierre']) ?></td>
                        <td class="texto-nowrap">
                            <?php if ($reloj['estado'] === 'abierto'): ?>
                                faltan <?= (int) $reloj['faltante'] ?> <?= htmlspecialchars($reloj['unidad']) ?>
                            <?php elseif ($reloj['estado'] === 'pendiente'): ?>
                                abre en <?= (int) $reloj['faltante_para_abrir'] ?> <?= htmlspecialchars($reloj['unidad_para_abrir']) ?>
                            <?php else: ?>
                                cerrada
                            <?php endif; ?>
                        </td>
                        <td class="texto-nowrap">
                            <?= $convocatoria['tope_por_proyecto'] !== null ? '$ ' . number_format((float) $convocatoria['tope_por_proyecto'], 2, ',', '.') : 'Libre' ?>
                        </td>
                        <td><?= (int) $convocatoria['cantidad_proyectos'] ?></td>
                        <td class="texto-nowrap"><?= number_format((float) $convocatoria['valor_total'], 2, ',', '.') ?></td>
                        <td>
                            <form method="POST" action="index.php?ruta=convocatorias" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_activa">
                                <input type="hidden" name="id" value="<?= (int) $convocatoria['id'] ?>">
                                <button type="submit" class="interruptor <?= (int) $convocatoria['activa'] === 1 ? 'interruptor-activo' : '' ?>" aria-label="Activar o desactivar" title="<?= (int) $convocatoria['activa'] === 1 ? 'Activa (clic para desactivar)' : 'Inactiva (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= (int) $convocatoria['activa'] === 1 ? 'Activa' : 'Inactiva' ?></span>
                            </form>
                        </td>
                        <td><a href="index.php?ruta=convocatorias&editar_id=<?= (int) $convocatoria['id'] ?>" class="boton-accion boton-accion-editar">Editar</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($convocatorias)): ?>
                    <tr>
                        <td colspan="10">No hay convocatorias registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tarjeta">
        <h2><?= $editando ? 'Editar convocatoria' : 'Nueva convocatoria' ?></h2>

        <form method="POST" action="index.php?ruta=convocatorias<?= $editando ? '&editar_id=' . (int) $convocatoriaEditar['id'] : '' ?>" class="form-necesidad">
            <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?= (int) $convocatoriaEditar['id'] ?>">
            <?php endif; ?>

            <div class="campo campo-ancho">
                <label for="convocatoria-nombre">Nombre *</label>
                <input type="text" id="convocatoria-nombre" name="nombre" maxlength="150" required value="<?= htmlspecialchars($convocatoriaEditar['nombre'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="convocatoria-vigencia">Vigencia *</label>
                <select id="convocatoria-vigencia" name="vigencia" required>
                    <?php foreach ($aniosVigencia as $anioVigencia): ?>
                    <option value="<?= (int) $anioVigencia ?>"<?= (int) ($convocatoriaEditar['vigencia'] ?? 0) === (int) $anioVigencia ? ' selected' : '' ?>><?= (int) $anioVigencia ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="convocatoria-audiencia">Quién formula *</label>
                <select id="convocatoria-audiencia" name="audiencia" required>
                    <?php foreach ($etiquetasAudiencia as $valor => $etiqueta): ?>
                    <option value="<?= htmlspecialchars($valor) ?>"<?= ($convocatoriaEditar['audiencia'] ?? 'ambos') === $valor ? ' selected' : '' ?>><?= htmlspecialchars($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="convocatoria-tope-modo">Valor por proyecto *</label>
                <select id="convocatoria-tope-modo" name="tope_modo" required>
                    <option value="libre"<?= $modoTopeEditando === 'libre' ? ' selected' : '' ?>>Libre, sin tope</option>
                    <option value="tope"<?= $modoTopeEditando === 'tope' ? ' selected' : '' ?>>Con tope por proyecto</option>
                </select>
            </div>

            <div class="campo">
                <label for="convocatoria-fecha-inicio">Inicio *</label>
                <input type="date" id="convocatoria-fecha-inicio" name="fecha_inicio" required value="<?= htmlspecialchars($convocatoriaEditar['fecha_inicio'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="convocatoria-fecha-cierre">Cierre *</label>
                <input type="date" id="convocatoria-fecha-cierre" name="fecha_cierre" required value="<?= htmlspecialchars($convocatoriaEditar['fecha_cierre'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="convocatoria-tope-valor">Tope por proyecto ($)</label>
                <input type="number" id="convocatoria-tope-valor" name="tope_valor" min="0.01" step="0.01" placeholder="0.00"
                    value="<?= $topeEditando !== null ? htmlspecialchars(number_format((float) $topeEditando, 2, '.', '')) : '' ?>"
                    <?= $modoTopeEditando === 'libre' ? 'disabled' : '' ?>>
            </div>

            <div class="campo campo-ancho">
                <label>Fuentes de financiación habilitadas</label>
                <?php if (empty($fuentesActivas)): ?>
                <p class="texto-atenuado">No hay fuentes activas. Agrégalas en Configuraciones > Listas.</p>
                <?php else: ?>
                <div class="grupo-tags">
                    <?php foreach ($fuentesActivas as $fuente): ?>
                    <input type="checkbox" class="tag-chip" name="fuentes[]" value="<?= (int) $fuente['id'] ?>"
                        data-label="<?= htmlspecialchars($fuente['nombre']) ?>"<?= isset($fuentesMarcadasSet[(int) $fuente['id']]) ? ' checked' : '' ?>>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="campo campo-ancho">
                <label>Dependencias habilitadas <span class="texto-atenuado">· sin ninguna marcada, no hay restricción por dependencia. «Con descendientes» incluye también todas sus dependencias hijas.</span></label>
                <div class="arbol-convocatoria" id="arbol-convocatoria-dependencias">
                    <?php if (!empty($arbolDependencias)): ?>
                    <div class="fila-arbol-encabezado">
                        <span>Dependencia</span>
                        <span class="casillas-arbol-dependencia">
                            <span class="casilla-arbol-titulo">Habilitada</span>
                            <span class="casilla-arbol-titulo">Con descendientes</span>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if (empty($arbolDependencias)): ?>
                    <p class="texto-atenuado">No hay dependencias activas.</p>
                    <?php endif; ?>
                    <?php
                    $renderizarNodoDependencia = function (array $nodo) use (&$renderizarNodoDependencia, $dependenciasMarcadas): void {
                        $dependencia = $nodo['dependencia'];
                        $dependenciaId = (int) $dependencia['id'];
                        $nombreDependencia = Dependencia::nombreVisible($dependencia['nombre']);
                        $habilitada = array_key_exists($dependenciaId, $dependenciasMarcadas);
                        $conDescendientes = $dependenciasMarcadas[$dependenciaId] ?? false;
                        $tieneHijos = !empty($nodo['hijos']);
                        ?>
                        <div class="nodo-arbol-presupuesto">
                            <div class="fila-arbol-dependencia">
                                <span class="fila-presupuesto-nombre">
                                    <?php if ($tieneHijos): ?>
                                    <button type="button" class="boton-expandir-arbol" aria-expanded="false" aria-label="Expandir">+</button>
                                    <?php else: ?>
                                    <span class="espaciador-expandir-arbol"></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($nombreDependencia) ?>
                                </span>
                                <span class="casillas-arbol-dependencia">
                                    <label class="casilla-arbol">
                                        <input type="checkbox" name="dependencias[]" value="<?= $dependenciaId ?>" data-arbol-habilitar aria-label="Habilitada: <?= htmlspecialchars($nombreDependencia) ?>"<?= $habilitada ? ' checked' : '' ?>>
                                    </label>
                                    <label class="casilla-arbol">
                                        <input type="checkbox" name="descendientes[]" value="<?= $dependenciaId ?>" data-arbol-descendientes aria-label="Con descendientes: <?= htmlspecialchars($nombreDependencia) ?>"<?= $conDescendientes ? ' checked' : '' ?>>
                                    </label>
                                </span>
                            </div>
                            <?php if ($tieneHijos): ?>
                            <div class="subarbol-presupuesto oculto">
                                <?php foreach ($nodo['hijos'] as $nodoHijo) { $renderizarNodoDependencia($nodoHijo); } ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    };

                    foreach ($arbolDependencias as $nodoRaiz) {
                        $renderizarNodoDependencia($nodoRaiz);
                    }
                    ?>
                </div>
            </div>

            <button type="submit" class="boton-enviar" style="grid-column: span 1;"><?= $editando ? 'Guardar cambios' : 'Crear convocatoria' ?></button>
            <?php if ($editando): ?>
            <a href="index.php?ruta=convocatorias" class="texto-atenuado" style="align-self: center; justify-self: start;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <script>
    (function () {
        var modo = document.getElementById('convocatoria-tope-modo');
        var tope = document.getElementById('convocatoria-tope-valor');

        if (modo && tope) {
            var sincronizarTope = function () {
                var conTope = modo.value === 'tope';
                tope.disabled = !conTope;
                tope.required = conTope;

                if (!conTope) {
                    tope.value = '';
                }
            };

            modo.addEventListener('change', sincronizarTope);
            sincronizarTope();
        }

        var arbol = document.getElementById('arbol-convocatoria-dependencias');

        if (arbol) {
            // Una dependencia marcada con "Con descendientes" incluye todas sus hijas: estas quedan marcadas
            // y bloqueadas, y se restauran cuando se desmarca el padre.
            // Casillas de todas las dependencias descendientes de un nodo (no las de la fila del propio nodo).
            var descendientesDe = function (nodo) {
                var casillas = [];

                nodo.querySelectorAll(':scope > .subarbol-presupuesto > .nodo-arbol-presupuesto').forEach(function (hijo) {
                    hijo.querySelectorAll(':scope > .fila-arbol-dependencia input[type="checkbox"]').forEach(function (casilla) {
                        casillas.push(casilla);
                    });
                    casillas = casillas.concat(descendientesDe(hijo));
                });

                return casillas;
            };

            var sincronizarArbol = function () {
                arbol.querySelectorAll('[data-heredada]').forEach(function (casilla) {
                    casilla.disabled = false;
                    casilla.checked = casilla.dataset.previo === '1';
                    casilla.removeAttribute('data-heredada');
                    casilla.closest('.fila-arbol-dependencia').classList.remove('arbol-heredada');
                });

                arbol.querySelectorAll('[data-arbol-descendientes]:checked').forEach(function (padre) {
                    descendientesDe(padre.closest('.nodo-arbol-presupuesto')).forEach(function (casilla) {
                        if (!casilla.hasAttribute('data-heredada')) {
                            casilla.dataset.previo = casilla.checked ? '1' : '0';
                            casilla.setAttribute('data-heredada', '');
                        }

                        casilla.checked = true;
                        casilla.disabled = true;
                        casilla.closest('.fila-arbol-dependencia').classList.add('arbol-heredada');
                    });
                });
            };

            arbol.addEventListener('change', function (evento) {
                var casilla = evento.target;
                var nodo = casilla.closest('.nodo-arbol-presupuesto');

                // Con descendientes implica habilitada; quitar habilitada quita también descendientes.
                if (casilla.hasAttribute('data-arbol-descendientes') && casilla.checked) {
                    nodo.querySelector(':scope > .fila-arbol-dependencia [data-arbol-habilitar]').checked = true;
                }

                if (casilla.hasAttribute('data-arbol-habilitar') && !casilla.checked) {
                    nodo.querySelector(':scope > .fila-arbol-dependencia [data-arbol-descendientes]').checked = false;
                }

                sincronizarArbol();
            });

            sincronizarArbol();
        }
    })();
    </script>

        <?php else: ?>

        <?php foreach ($consolidado as $bloque): $c = $bloque['convocatoria']; $total = 0.0; $cantidadTotal = 0; ?>
        <h2><?= htmlspecialchars($c['nombre']) ?><?php if (strpos($c['nombre'], (string) $c['vigencia']) === false): ?> <span class="texto-atenuado"><?= (int) $c['vigencia'] ?></span><?php endif; ?></h2>
        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <th>Fuente de financiación</th>
                        <th>Proyectos</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloque['filas'] as $fila): $total += (float) $fila['valor']; $cantidadTotal += (int) $fila['cantidad']; ?>
                    <tr>
                        <td><?= htmlspecialchars(Dependencia::nombreVisible($fila['dependencia'] ?? 'Sin dependencia')) ?></td>
                        <td><?= htmlspecialchars($fila['fuente'] ?? 'Sin fuente') ?></td>
                        <td><?= (int) $fila['cantidad'] ?></td>
                        <td class="texto-nowrap"><?= number_format((float) $fila['valor'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bloque['filas'])): ?>
                    <tr>
                        <td colspan="4">Sin proyectos en esta convocatoria.</td>
                    </tr>
                    <?php else: ?>
                    <tr class="fila-totales-tdt">
                        <td><strong>Total</strong></td>
                        <td></td>
                        <td><strong><?= $cantidadTotal ?></strong></td>
                        <td class="texto-nowrap"><strong><?= number_format($total, 2, ',', '.') ?></strong></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

        <?php if (empty($consolidado)): ?>
        <p class="texto-atenuado">No hay convocatorias registradas.</p>
        <?php endif; ?>
    </div>
        <?php endif; ?>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
