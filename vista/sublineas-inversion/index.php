<?php
$opcionesLinea = array_map(
    static fn (array $l): array => [
        'value' => $l['id'],
        'label' => $l['codigo'] . ' - ' . $l['nombre'] . ($l['estado'] === 'inactivo' ? ' (inactiva)' : ''),
    ],
    $lineasOpciones
);

$landing = [
    'titulo' => 'Sublíneas de inversión',
    'ruta' => 'sublineas-inversion',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 20, 'placeholder' => 'Código', 'angosta' => true],
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre'],
        ['clave' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Descripción'],
        ['clave' => 'linea_inversion_id', 'etiqueta' => 'Línea de inversión', 'tipo' => 'fk', 'editable' => true, 'requerido' => true, 'opciones' => $opcionesLinea, 'mensajeFaltante' => 'Primero debes crear al menos una línea en el módulo Líneas de inversión.'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
