<?php
/**
 * Selector buscable reutilizable para elegir una dependencia — reemplaza un <select> con muchas
 * dependencias (siempre supera las 10) por un input predecible. Soporta tanto el caso simple
 * (valor = nombre de la dependencia, usado en gastos/egresos/solicitudes) como el caso con filtro
 * de rol por tipo (data-select-rol, usado en Usuarios/Solicitudes/Peticiones) y el caso por id
 * (Usuarios, Dependencias).
 *
 * Variables esperadas antes de incluir este parcial:
 * - $idPrefijoDependencia (string): prefijo de ids, ej. '' para crear, 'editar-' para editar.
 * - $nombreCampoDependencia (string): el name= del campo, ej. 'dependencia', 'facultad', 'dependencia_id'.
 * - $dependenciasOpciones (array): cada elemento es un string (nombre) o un arreglo
 *   ['id' => .., 'nombre' => .., 'tipo' => .. (opcional)].
 * - $dependenciaUsarId (bool, opcional, por defecto false): si true, el valor guardado/buscado es el id;
 *   si no, es el nombre.
 * - $dependenciaValorInicial (string|int|null, opcional): valor preseleccionado al cargar la página.
 * - $dependenciaDataSelectRol (string, opcional): id del <select> de rol que este campo debe filtrar
 *   (equivalente al data-select-rol que ya usan los <select> de dependencia con filtro por tipo).
 * - $idBaseDependenciaOverride (string, opcional): usar cuando el id original NO coincide con
 *   prefijo+name (ej. id="dependencia_ingreso" pero name="dependencia", para no chocar con otro
 *   campo de la misma página que también se llama "dependencia").
 */
$idBaseDependencia = $idBaseDependenciaOverride ?? ($idPrefijoDependencia . $nombreCampoDependencia);
$usarIdDependencia = $dependenciaUsarId ?? false;
$valorInicialDependencia = $dependenciaValorInicial ?? null;
$dataSelectRolAttrDependencia = !empty($dependenciaDataSelectRol) ? ' data-select-rol="' . htmlspecialchars((string) $dependenciaDataSelectRol) . '"' : '';

$opcionesDependenciaNormalizadas = array_map(static function ($item) use ($usarIdDependencia) {
    if (is_array($item)) {
        return [
            'valor' => $usarIdDependencia ? (string) $item['id'] : $item['nombre'],
            'texto' => $item['nombre'],
            'tipo' => $item['tipo'] ?? '',
        ];
    }

    return ['valor' => $item, 'texto' => $item, 'tipo' => ''];
}, $dependenciasOpciones);
?>
<div class="selector-buscable" id="<?= $idBaseDependencia ?>-selector">
    <input
        type="text"
        id="<?= $idBaseDependencia ?>_buscador"
        class="selector-buscable-input"
        placeholder="Buscar dependencia..."
        autocomplete="off"
        value="<?= $valorInicialDependencia !== null ? htmlspecialchars((string) $valorInicialDependencia) : '' ?>"
    >
    <input
        type="hidden"
        name="<?= htmlspecialchars($nombreCampoDependencia) ?>"
        id="<?= $idBaseDependencia ?>"
        value="<?= $valorInicialDependencia !== null ? htmlspecialchars((string) $valorInicialDependencia) : '' ?>"<?= $dataSelectRolAttrDependencia ?>
    >
    <div class="selector-buscable-lista" id="<?= $idBaseDependencia ?>_lista">
        <?php foreach ($opcionesDependenciaNormalizadas as $opcionDependencia): ?>
        <div class="selector-buscable-opcion" data-id="<?= htmlspecialchars((string) $opcionDependencia['valor']) ?>" data-texto="<?= htmlspecialchars($opcionDependencia['texto']) ?>" data-tipo="<?= htmlspecialchars($opcionDependencia['tipo']) ?>">
            <?= htmlspecialchars($opcionDependencia['texto']) ?>
        </div>
        <?php endforeach; ?>
        <div class="selector-buscable-vacio">Sin resultados.</div>
    </div>
</div>
<?php unset($dependenciaUsarId, $dependenciaValorInicial, $dependenciaDataSelectRol, $idBaseDependenciaOverride); ?>
