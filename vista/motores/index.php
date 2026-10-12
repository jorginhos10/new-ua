<?php
$opcionesLinea = array_map(
    static fn (array $l): array => [
        'value' => $l['id'],
        'label' => $l['codigo'] . ' - ' . $l['nombre'] . ($l['estado'] === 'inactivo' ? ' (inactiva)' : ''),
    ],
    $lineasOpciones
);

$landing = [
    'titulo' => 'Motores',
    'ruta' => 'motores',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 10, 'placeholder' => 'Código (ej. M1)', 'angosta' => true],
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre del motor'],
        ['clave' => 'linea_id', 'etiqueta' => 'Línea', 'tipo' => 'fk', 'editable' => true, 'requerido' => true, 'opciones' => $opcionesLinea, 'mensajeFaltante' => 'Primero debes crear al menos una línea en el módulo Líneas.'],
        ['clave' => 'creado_en', 'etiqueta' => 'Creado', 'tipo' => 'calculado'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
