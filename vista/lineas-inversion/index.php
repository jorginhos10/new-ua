<?php
$landing = [
    'titulo' => 'Líneas de inversión',
    'ruta' => 'lineas-inversion',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 20, 'placeholder' => 'Código', 'angosta' => true],
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre'],
        ['clave' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Descripción'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
