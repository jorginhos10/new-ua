<?php
$landing = [
    'titulo' => 'Rubros',
    'ruta' => 'rubros',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 20, 'placeholder' => 'Código (ej. 101010101)'],
        ['clave' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Descripción'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
