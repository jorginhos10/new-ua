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
?>

    <div class="tarjeta">
        <div class="pestanas" style="margin: 0 0 0.75rem;">
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
                        <th>Proyectos</th>
                        <th>Valor</th>
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
                        <td><?= htmlspecialchars($reloj['fecha_inicio']) ?> – <?= htmlspecialchars($reloj['fecha_cierre']) ?></td>
                        <td>
                            <?php if ($reloj['estado'] === 'abierto'): ?>
                                faltan <?= (int) $reloj['faltante'] ?> <?= htmlspecialchars($reloj['unidad']) ?>
                            <?php elseif ($reloj['estado'] === 'pendiente'): ?>
                                abre en <?= (int) $reloj['faltante'] ?> <?= htmlspecialchars($reloj['unidad']) ?>
                            <?php else: ?>
                                cerrada
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $convocatoria['cantidad_proyectos'] ?></td>
                        <td><?= number_format((float) $convocatoria['valor_total'], 2, ',', '.') ?></td>
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
                        <td colspan="9">No hay convocatorias registradas.</td>
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
                <label for="convocatoria-fecha-inicio">Inicio *</label>
                <input type="date" id="convocatoria-fecha-inicio" name="fecha_inicio" required value="<?= htmlspecialchars($convocatoriaEditar['fecha_inicio'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="convocatoria-fecha-cierre">Cierre *</label>
                <input type="date" id="convocatoria-fecha-cierre" name="fecha_cierre" required value="<?= htmlspecialchars($convocatoriaEditar['fecha_cierre'] ?? '') ?>">
            </div>

            <div class="campo campo-ancho">
                <label>Fuentes de financiación habilitadas</label>
                <?php if (empty($fuentesActivas)): ?>
                <p class="texto-atenuado">No hay fuentes activas. Agrégalas en Configuraciones > Listas.</p>
                <?php endif; ?>
                <?php foreach ($fuentesActivas as $fuente): ?>
                <label class="columnas-tarjeta-item">
                    <input type="checkbox" name="fuentes[]" value="<?= (int) $fuente['id'] ?>"<?= isset($fuentesMarcadasSet[(int) $fuente['id']]) ? ' checked' : '' ?>>
                    <?= htmlspecialchars($fuente['nombre']) ?>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="campo campo-ancho">
                <label>Dependencias habilitadas <span class="texto-atenuado">(sin ninguna marcada, no hay restricción por dependencia)</span></label>
                <div class="tabla-scroll" style="max-height: 260px;">
                    <table class="tabla-usuarios">
                        <thead>
                            <tr>
                                <th>Dependencia</th>
                                <th>Incluir descendientes</th>
                                <th>Habilitada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dependencias as $dependencia): $dependenciaId = (int) $dependencia['id']; $marcada = array_key_exists($dependenciaId, $dependenciasMarcadas); ?>
                            <tr>
                                <td><?= htmlspecialchars(Dependencia::nombreVisible($dependencia['nombre'])) ?></td>
                                <td><input type="checkbox" name="descendientes[]" value="<?= $dependenciaId ?>"<?= ($dependenciasMarcadas[$dependenciaId] ?? false) ? ' checked' : '' ?>></td>
                                <td><input type="checkbox" name="dependencias[]" value="<?= $dependenciaId ?>"<?= $marcada ? ' checked' : '' ?>></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit"><?= $editando ? 'Guardar cambios' : 'Crear convocatoria' ?></button>
            <?php if ($editando): ?>
            <a href="index.php?ruta=convocatorias" class="enlace-volver-configuraciones">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

        <?php else: ?>

        <?php foreach ($consolidado as $bloque): $c = $bloque['convocatoria']; $total = 0.0; $cantidadTotal = 0; ?>
        <h2><?= htmlspecialchars($c['nombre']) ?> <span class="texto-atenuado"><?= (int) $c['vigencia'] ?></span></h2>
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
                        <td><?= number_format((float) $fila['valor'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bloque['filas'])): ?>
                    <tr>
                        <td colspan="4">Sin proyectos en esta convocatoria.</td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td></td>
                        <td><strong><?= $cantidadTotal ?></strong></td>
                        <td><strong><?= number_format($total, 2, ',', '.') ?></strong></td>
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
