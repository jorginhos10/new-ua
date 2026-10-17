<?php
/**
 * Landing compartida de los 10 catálogos del botón "Listas" de Configuraciones — ver
 * controlador/ControladorListaCatalogo.php. Cada vista/<modulo>/index.php arma $landing y hace
 * require de este parcial; nunca escribe su propio <table>/<form>.
 *
 * $landing = [
 *   'titulo'      => string,
 *   'ruta'        => string ('ruta=' del módulo),
 *   'descripcion' => string opcional (párrafo bajo el título),
 *   'csv'         => bool (incluye vista/parciales/csv-configuracion.php),
 *   'estado'      => bool (columna Estado con el toggle .interruptor),
 *   'eliminar'    => bool (columna Eliminar, deshabilitado si la fila trae 'en_uso' => true),
 *   'columnas'    => array de:
 *     ['clave', 'etiqueta', 'tipo' => 'texto'|'fk'|'calculado', 'editable' => bool,
 *      'requerido' => bool, 'placeholder', 'maxlength', 'pattern', 'title',
 *      'opciones' => [['value', 'label'], ...] (solo tipo 'fk'),
 *      'mensajeFaltante' => string (solo fk requerido con 'opciones' vacío: qué mostrar en vez
 *                            del formulario "Agregar")],
 *   'filas'       => array de registros (cada uno con 'id' y las claves de 'columnas'),
 * ]
 * 'calculado' = columna de solo lectura, nunca aparece en el formulario "Agregar" ni es editable
 * (ej. Creado, o el conteo de uso de Fuentes de financiación).
 */
$tituloPagina = $landing['titulo'];
require __DIR__ . '/encabezado.php';

$ruta = $landing['ruta'];
$columnas = $landing['columnas'];
$filas = $landing['filas'];
$tieneEstado = $landing['estado'] ?? false;
$tieneEliminar = $landing['eliminar'] ?? false;
$tieneCsv = $landing['csv'] ?? false;

$columnaFaltante = null;
foreach ($columnas as $columna) {
    if (($columna['tipo'] ?? '') === 'fk' && !empty($columna['requerido']) && empty($columna['opciones'])) {
        $columnaFaltante = $columna;
        break;
    }
}

/** Columna angosta (código corto, NIT, Estado, Creado, conteos) = se ajusta a su contenido en vez
 * de repartirse el ancho por igual con Nombre/Descripción, que sí deben poder crecer. 'calculado'
 * siempre es angosta; para 'texto' lo decide explícitamente cada módulo con 'angosta' => true en
 * su config — el largo máximo (maxlength) NO es buen indicador: el código de Rubros permite hasta
 * 20 caracteres pero en la práctica mide 16 ("2.01.01.01.01.01"), mientras que el de Sedes permite
 * lo mismo y en la práctica mide 2 ("BA") — solo el propio módulo sabe cuál es cuál. */
$esColumnaAngosta = static function (array $columna): bool {
    $tipo = $columna['tipo'] ?? 'texto';

    return $tipo === 'calculado' || !empty($columna['angosta']);
};

// Los 10 catálogos del botón "Listas" de Configuraciones (ya no tienen landing de botones propio,
// el botón entra directo al primero). Sidebar en vez de pestañas horizontales a propósito: una
// fila más no rompe el layout ni obliga a scroll horizontal cuando se agreguen más listas.
$pestanasListas = [
    'lineas' => 'Líneas',
    'motores' => 'Motores',
    'proyectos' => 'Proyectos',
    'estamentos' => 'Estamentos',
    'rubros' => 'Rubros',
    'sedes' => 'Sedes',
    'contratos-comunes' => 'Contratos comunes',
    'lineas-inversion' => 'Líneas de inversión',
    'sublineas-inversion' => 'Sublíneas de inversión',
    'fuentes-financiacion' => 'Fuentes de financiación',
    'lista-categorias-gasto' => 'Categorías de gasto',
    'lista-rubros-categorias-gasto' => 'Rubros por categoría',
];
?>

    <div class="listas-layout">
        <aside class="listas-sidebar">
            <a href="index.php?ruta=configuraciones" class="listas-volver">&larr; Configuraciones</a>
            <div class="listas-sidebar-titulo">Listas</div>
            <nav class="listas-sidebar-nav">
                <?php foreach ($pestanasListas as $rutaPestana => $etiquetaPestana): ?>
                <a href="index.php?ruta=<?= htmlspecialchars($rutaPestana) ?>" class="<?= $rutaPestana === $ruta ? 'activo' : '' ?>"><?= htmlspecialchars($etiquetaPestana) ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>

    <div class="tarjeta listas-contenido">
        <div class="tarjeta-encabezado">
            <h1><?= htmlspecialchars($landing['titulo']) ?></h1>
            <?php if ($tieneCsv): $csvRuta = $ruta; $csvEtiqueta = $landing['titulo']; require __DIR__ . '/csv-configuracion.php'; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($landing['descripcion'])): ?>
        <p class="texto-atenuado"><?= htmlspecialchars($landing['descripcion']) ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="mensaje-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <p class="mensaje-exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>

        <?php if ($columnaFaltante !== null): ?>
        <p class="mensaje-error"><?= htmlspecialchars($columnaFaltante['mensajeFaltante'] ?? ('Primero debes crear al menos un registro relacionado de "' . $columnaFaltante['etiqueta'] . '".')) ?></p>
        <?php else: ?>
        <form method="POST" action="index.php?ruta=<?= htmlspecialchars($ruta) ?>" class="form-agregar">
            <input type="hidden" name="accion" value="crear">
            <?php foreach ($columnas as $columna): if (($columna['tipo'] ?? 'texto') === 'calculado') continue; ?>
                <?php if ($columna['tipo'] === 'fk'): ?>
                <select name="<?= htmlspecialchars($columna['clave']) ?>" <?= !empty($columna['requerido']) ? 'required' : '' ?>>
                    <option value="">Selecciona <?= htmlspecialchars(mb_strtolower($columna['etiqueta'])) ?></option>
                    <?php foreach ($columna['opciones'] as $opcion): ?>
                    <option value="<?= htmlspecialchars((string) $opcion['value']) ?>"><?= htmlspecialchars($opcion['label']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <input
                    type="text"
                    name="<?= htmlspecialchars($columna['clave']) ?>"
                    placeholder="<?= htmlspecialchars($columna['placeholder'] ?? $columna['etiqueta']) ?>"
                    <?= isset($columna['maxlength']) ? 'maxlength="' . (int) $columna['maxlength'] . '"' : '' ?>
                    <?= isset($columna['pattern']) ? 'pattern="' . htmlspecialchars($columna['pattern']) . '"' : '' ?>
                    <?= isset($columna['title']) ? 'title="' . htmlspecialchars($columna['title']) . '"' : '' ?>
                    <?= !empty($columna['requerido']) ? 'required' : '' ?>
                >
                <?php endif; ?>
            <?php endforeach; ?>
            <button type="submit">Agregar</button>
        </form>
        <?php endif; ?>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <?php foreach ($columnas as $columna): ?>
                        <th class="<?= $esColumnaAngosta($columna) ? 'col-angosta' : '' ?>"><?= htmlspecialchars($columna['etiqueta']) ?></th>
                        <?php endforeach; ?>
                        <?php if ($tieneEstado): ?><th class="col-angosta">Estado</th><?php endif; ?>
                        <th class="col-angosta"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): $idFormulario = 'form-' . $ruta . '-' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $fila['id']); ?>
                    <tr>
                        <?php foreach ($columnas as $columna): $valor = $fila[$columna['clave']] ?? null; $tipo = $columna['tipo'] ?? 'texto'; ?>
                        <td class="<?= $esColumnaAngosta($columna) ? 'col-angosta' : '' ?>">
                            <?php if ($tipo === 'calculado' || empty($columna['editable'])): ?>
                                <?= htmlspecialchars((string) $valor) ?>
                            <?php elseif ($tipo === 'fk'): ?>
                                <select name="<?= htmlspecialchars($columna['clave']) ?>" form="<?= $idFormulario ?>" <?= !empty($columna['requerido']) ? 'required' : '' ?> aria-label="<?= htmlspecialchars($columna['etiqueta']) ?>">
                                    <?php foreach ($columna['opciones'] as $opcion): ?>
                                    <option value="<?= htmlspecialchars((string) $opcion['value']) ?>" <?= (string) $opcion['value'] === (string) $valor ? 'selected' : '' ?>><?= htmlspecialchars($opcion['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="<?= htmlspecialchars($columna['clave']) ?>"
                                    form="<?= $idFormulario ?>"
                                    value="<?= htmlspecialchars((string) $valor) ?>"
                                    <?= isset($columna['maxlength']) ? 'maxlength="' . (int) $columna['maxlength'] . '"' : '' ?>
                                    <?= isset($columna['pattern']) ? 'pattern="' . htmlspecialchars($columna['pattern']) . '"' : '' ?>
                                    <?= isset($columna['title']) ? 'title="' . htmlspecialchars($columna['title']) . '"' : '' ?>
                                    <?= !empty($columna['requerido']) ? 'required' : '' ?>
                                    aria-label="<?= htmlspecialchars($columna['etiqueta']) ?>"
                                >
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>

                        <?php if ($tieneEstado): ?>
                        <td class="col-angosta">
                            <form method="POST" action="index.php?ruta=<?= htmlspecialchars($ruta) ?>" class="form-toggle">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $fila['id']) ?>">
                                <button type="submit" class="interruptor <?= $fila['estado'] === 'activo' ? 'interruptor-activo' : '' ?>" aria-label="Cambiar estado" title="<?= $fila['estado'] === 'activo' ? 'Activo (clic para desactivar)' : 'Inactivo (clic para activar)' ?>">
                                    <span class="interruptor-perilla"></span>
                                </button>
                                <span class="interruptor-etiqueta"><?= $fila['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span>
                            </form>
                        </td>
                        <?php endif; ?>

                        <td class="celda-acciones col-angosta">
                            <div class="acciones-fila">
                                <form method="POST" action="index.php?ruta=<?= htmlspecialchars($ruta) ?>" id="<?= $idFormulario ?>">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $fila['id']) ?>">
                                    <button type="submit" class="boton-accion boton-accion-editar">Guardar</button>
                                </form>
                                <?php if ($tieneEliminar): $enUso = !empty($fila['en_uso']); ?>
                                <form method="POST" action="index.php?ruta=<?= htmlspecialchars($ruta) ?>" onsubmit="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $fila['id']) ?>">
                                    <button type="submit" class="boton-accion boton-accion-eliminar" <?= $enUso ? 'disabled title="Está en uso; desactívalo en lugar de eliminarlo"' : '' ?>>Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($filas)): ?>
                    <tr>
                        <td colspan="<?= count($columnas) + ($tieneEstado ? 1 : 0) + 1 ?>">No hay registros todavía.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

<?php require __DIR__ . '/pie.php'; ?>
