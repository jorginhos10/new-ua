<?php

// Pestañas de Análisis que se asignan desde Jerarquías y Usuarios > Permisos. No van en
// menu_items.php: ese catálogo es la lista de rutas del menú lateral de Consejo e Invitado, y
// estas claves no son rutas. Ver AccesoAnalisis::PESTANAS.
return [
    'Análisis' => [
        'analisis_programacion' => 'Programación presupuestal',
        'analisis_pdi' => 'Articulación PDI',
        'analisis_distribucion' => 'Análisis de distribución',
        'analisis_proyectos' => 'Proyectos (análisis)',
        'analisis_techos' => 'Techos y Metas',
    ],
];
