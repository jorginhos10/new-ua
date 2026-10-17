<?php
$landing = [
    'titulo' => 'Rubros por categoría',
    'ruta' => 'lista-rubros-categorias-gasto',
    'descripcion' => 'Reglas del motor de categorización: para cada prefijo de código de rubro, qué categoría(s) le corresponden — manda la regla con el prefijo más largo que coincide con el rubro del gasto. Si un prefijo tiene varias categorías, la marcada "Por defecto" gana cuando el texto de actividad/insumo no decide y el peso (0 a 1) baja la preferencia de las alternativas. "No aplica" deja ese rubro sin categoría. Después de cambiar reglas, usa "Recalcular" en Configuraciones > Categoría de gasto.',
    'estado' => false,
    'eliminar' => true,
    'columnas' => [
        ['clave' => 'prefijo_codigo', 'etiqueta' => 'Prefijo de rubro', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 30, 'pattern' => '[0-9]+(\.[0-9]+)*', 'title' => 'Código numérico, ej. 2.02.01', 'placeholder' => 'Prefijo (ej. 2.02.01)', 'angosta' => true],
        ['clave' => 'rubro_descripcion', 'etiqueta' => 'Rubro', 'tipo' => 'calculado'],
        ['clave' => 'accion_regla', 'etiqueta' => 'Tipo', 'tipo' => 'fk', 'editable' => true, 'requerido' => true, 'opciones' => [['value' => 'categoria', 'label' => 'Con categoría'], ['value' => 'no_aplica', 'label' => 'No aplica']]],
        ['clave' => 'categoria_id', 'etiqueta' => 'Categoría', 'tipo' => 'fk', 'editable' => true, 'requerido' => false, 'opciones' => $opcionesCategoria],
        ['clave' => 'es_defecto', 'etiqueta' => 'Por defecto', 'tipo' => 'fk', 'editable' => true, 'requerido' => true, 'opciones' => [['value' => '1', 'label' => 'Sí'], ['value' => '0', 'label' => 'No']]],
        ['clave' => 'peso', 'etiqueta' => 'Peso', 'tipo' => 'texto', 'editable' => true, 'requerido' => true, 'maxlength' => 4, 'pattern' => '0?[.,][0-9]{1,2}|1([.,]0{1,2})?', 'title' => 'Mayor que 0 y hasta 1 (ej. 0.80)', 'placeholder' => 'Peso (ej. 1.00)', 'angosta' => true],
    ],
    'filas' => $registros,
];
require __DIR__ . '/../parciales/landing-lista.php';
