<?php
$landing = [
    'titulo' => 'Líneas',
    'ruta' => 'lineas',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 10, 'placeholder' => 'Código (ej. L1)', 'angosta' => true],
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre de la línea'],
        ['clave' => 'creado_en', 'etiqueta' => 'Creado', 'tipo' => 'calculado'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
