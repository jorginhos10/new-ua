<?php
$landing = [
    'titulo' => 'Sedes',
    'ruta' => 'sedes',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 20, 'placeholder' => 'Código (ej. N)', 'angosta' => true],
        ['clave' => 'nit', 'etiqueta' => 'NIT', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 2, 'pattern' => '0[0-9]', 'title' => 'Dos dígitos que empiezan por 0 (00 a 09)', 'placeholder' => 'NIT (00 a 09)', 'angosta' => true],
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre de la sede'],
        ['clave' => 'creado_en', 'etiqueta' => 'Creado', 'tipo' => 'calculado'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
