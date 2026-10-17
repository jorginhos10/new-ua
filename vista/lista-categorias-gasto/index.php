<?php
$landing = [
    'titulo' => 'Categorías de gasto',
    'ruta' => 'lista-categorias-gasto',
    'descripcion' => 'Catálogo de categorías de actividad que el motor asigna a cada gasto (Configuraciones > Categoría de gasto). Las palabras clave, separadas por coma, ayudan a elegir entre varias categorías de un mismo rubro a partir del texto de actividad e insumo. El código no se puede cambiar después de crearla; si ya está en uso, desactívala en vez de eliminarla.',
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'id', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => false, 'requerido' => true, 'maxlength' => 10, 'placeholder' => 'Código (ej. PER-01)', 'pattern' => '[A-Za-z0-9]{2,5}-[0-9]{1,3}', 'title' => 'LETRAS-NÚMERO, ej. PER-01', 'angosta' => true],
        ['clave' => 'macro', 'etiqueta' => 'Grupo', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 80, 'placeholder' => 'Grupo (ej. Servicios Personales)'],
        ['clave' => 'subcategoria', 'etiqueta' => 'Categoría', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 150, 'placeholder' => 'Nombre de la categoría'],
        ['clave' => 'tokens', 'etiqueta' => 'Palabras clave', 'tipo' => 'texto', 'editable' => true, 'placeholder' => 'Palabras clave separadas por coma'],
        ['clave' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'texto', 'editable' => true, 'maxlength' => 4, 'pattern' => '[0-9]*', 'title' => 'Número entero (vacío = al final)', 'placeholder' => 'Orden', 'angosta' => true],
        ['clave' => 'usos', 'etiqueta' => 'En uso', 'tipo' => 'calculado'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
