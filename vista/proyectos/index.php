<?php
$opcionesMotor = array_map(
    static fn (array $m): array => [
        'value' => $m['id'],
        'label' => $m['linea_codigo'] . ' ' . $m['codigo'] . ' - ' . $m['nombre'] . ($m['estado'] === 'inactivo' ? ' (inactivo)' : ''),
    ],
    $motoresOpciones
);

$landing = [
    'titulo' => 'Proyectos',
    'ruta' => 'proyectos',
    'csv' => true,
    'estado' => true,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'codigo', 'etiqueta' => 'Código', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 10, 'placeholder' => 'Código (ej. P1)', 'angosta' => true],
        ['clave' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'placeholder' => 'Nombre del proyecto'],
        ['clave' => 'motor_id', 'etiqueta' => 'Motor', 'tipo' => 'fk', 'editable' => true, 'requerido' => true, 'opciones' => $opcionesMotor, 'mensajeFaltante' => 'Primero debes crear al menos un motor en el módulo Motores.'],
        ['clave' => 'nit', 'etiqueta' => 'NIT', 'tipo' => 'calculado'],
        ['clave' => 'creado_en', 'etiqueta' => 'Creado', 'tipo' => 'calculado'],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
