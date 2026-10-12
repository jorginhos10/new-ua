<?php
$landing = [
    'titulo' => 'Estamentos',
    'ruta' => 'estamentos',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre del estamento'],
        ['clave' => 'creado_en', 'etiqueta' => 'Creado', 'tipo' => 'calculado'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
