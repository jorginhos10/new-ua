<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/Dependencia.php';
require_once __DIR__ . '/MenuPermiso.php';

/**
 * Qué puede ver cada usuario en Análisis (ver AnalisisControlador::index()):
 * - superadmin: administrador en la dependencia raíz superadmin. Todo, como hasta ahora.
 * - administrador: rol administrador en cualquier otra dependencia. Solo la vista Usuario, de solo
 *   lectura, dentro de su dependencia y sus descendientes.
 * - consulta: rol consejo_superior. Solo la vista Repositorio, de solo lectura.
 *
 * Las pestañas salen de las claves analisis_* del menú (Jerarquías y Usuarios > Permisos). Sin
 * plantilla configurada para el usuario, valen PESTANAS_POR_DEFECTO.
 */
class AccesoAnalisis
{
    /**
     * Pestaña => clave de menú que la habilita. El orden es el de preferencia: para quien no tiene
     * "tab" en la URL, gana la primera de estas por la que SÍ tenga permiso (ver
     * AnalisisControlador::aplicarRestriccionesNoSuperadmin()) — Documentos (antes "Presentación")
     * primero, como en la barra de pestañas de la vista.
     */
    public const PESTANAS = [
        'presentacion' => 'analisis_presentacion',
        'programacion' => 'analisis_programacion',
        'proyectos' => 'analisis_proyectos',
        'pdi' => 'analisis_pdi',
        'analisis' => 'analisis_distribucion',
        'techos' => 'analisis_techos',
        'actas' => 'analisis_actas',
    ];

    public const PESTANAS_POR_DEFECTO = ['pdi', 'techos'];

    /** Techos y Metas no tiene vista Repositorio: a Consulta no se le muestra hasta que la tenga. */
    private const PESTANAS_SIN_REPOSITORIO = ['techos'];

    private const VISTAS = ['tiempo_real', 'repositorio', 'usuario'];

    private static ?array $actual = null;

    /**
     * Devuelve null si el usuario no puede entrar a Análisis. Si puede:
     * clase, vistas (en orden de preferencia), pestanas, dependencia propia y dependencias
     * permitidas (solo administrador; null = sin restricción).
     */
    public static function resolver(array $usuario, string $rolCuenta): ?array
    {
        $dependencia = !empty($usuario['dependencia_id'])
            ? (new Dependencia())->obtenerPorId((int) $usuario['dependencia_id'])
            : null;

        if ($rolCuenta === 'administrador') {
            if ($dependencia === null) {
                return null;
            }

            if (!empty($dependencia['es_raiz_superadmin'])) {
                return [
                    'clase' => 'superadmin',
                    'vistas' => self::VISTAS,
                    'pestanas' => array_keys(self::PESTANAS),
                    'dependencia' => $dependencia['nombre'],
                    'dependencias' => null,
                ];
            }

            $pestanas = self::pestanasPermitidas($usuario);
            if ($pestanas === []) {
                return null;
            }

            return [
                'clase' => 'administrador',
                'vistas' => ['usuario'],
                'pestanas' => $pestanas,
                'dependencia' => $dependencia['nombre'],
                'dependencias' => self::subarbol($dependencia),
            ];
        }

        if ($rolCuenta === 'consejo_superior') {
            $pestanas = array_values(array_diff(self::pestanasPermitidas($usuario), self::PESTANAS_SIN_REPOSITORIO));
            if ($pestanas === []) {
                return null;
            }

            return [
                'clase' => 'consulta',
                'vistas' => ['repositorio'],
                'pestanas' => $pestanas,
                'dependencia' => $dependencia['nombre'] ?? null,
                'dependencias' => null,
            ];
        }

        return null;
    }

    public static function establecer(array $acceso): void
    {
        self::$actual = $acceso;
    }

    /** Acceso del usuario de esta petición. Sin resolver, no ve nada. */
    public static function actual(): array
    {
        return self::$actual ?? ['clase' => 'ninguna', 'vistas' => [], 'pestanas' => [], 'dependencia' => null, 'dependencias' => null];
    }

    /** Dependencias que ofrece el selector de la vista Usuario: todas, o solo las del administrador. */
    public static function dependenciasSelector(array $todas): array
    {
        $dependencias = self::actual()['dependencias'];

        if ($dependencias === null) {
            return $todas;
        }

        return array_values(array_filter($todas, static fn (array $dependencia): bool => in_array($dependencia['nombre'], $dependencias, true)));
    }

    private static function pestanasPermitidas(array $usuario): array
    {
        $menu = (new MenuPermiso())->calcularPermitidoParaUsuario($usuario);

        // null = sin plantilla configurada (en MenuPermiso significa "todo"); aquí no puede
        // significar acceso total a Análisis, así que vale el valor por defecto.
        if ($menu === null) {
            return self::PESTANAS_POR_DEFECTO;
        }

        return array_values(array_filter(
            array_keys(self::PESTANAS),
            static fn (string $tab): bool => in_array(self::PESTANAS[$tab], $menu, true)
        ));
    }

    /** La dependencia y todas sus descendientes, por nombre (como las filtra la vista Usuario). */
    private static function subarbol(array $dependencia): array
    {
        $nombres = [$dependencia['nombre']];

        foreach ((new Dependencia())->obtenerDescendientesPlano((int) $dependencia['id']) as $descendiente) {
            $nombres[] = $descendiente['nombre'];
        }

        return $nombres;
    }
}
