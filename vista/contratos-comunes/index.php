<?php
$landing = [
    'titulo' => 'Contratos comunes',
    'ruta' => 'contratos-comunes',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 60, 'placeholder' => 'Código'],
        ['clave' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Descripción'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
