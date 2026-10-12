<?php
$landing = [
    'titulo' => 'Fuentes de financiación',
    'ruta' => 'fuentes-financiacion',
    'descripcion' => 'Catálogo de fuentes que las convocatorias de Perfil de proyectos habilitan para que los proyectos elijan una.',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre de la fuente de financiación'],
        ['clave' => 'cantidad_proyectos', 'etiqueta' => 'Proyectos', 'tipo' => 'calculado'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
