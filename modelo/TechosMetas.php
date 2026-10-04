<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/Dependencia.php';
require_once __DIR__ . '/Gasto.php';
require_once __DIR__ . '/PresupuestoDependencia.php';
require_once __DIR__ . '/AutogestionPorcentaje.php';
require_once __DIR__ . '/IngresoExtension.php';
require_once __DIR__ . '/IngresoPostgrado.php';
require_once __DIR__ . '/IngresoUnisalud.php';

/**
 * Datos de la pestaña "Techos y Metas" de ?ruta=analisis. El bloque "gastos" compara el techo de
 * cada dependencia con lo asignado (gastos ejecutados propios + los de sus hijas); cada bloque de
 * autogestión compara los ingresos de cada dependencia con el tope de su módulo — el tope es un
 * solo número por módulo, así que la comparación solo existe a nivel institucional. Reutiliza las
 * mismas fuentes que el módulo Techos y el Dashboard para que los números coincidan con esas
 * pantallas.
 */
class TechosMetas
{
    public const BLOQUES = ['gastos', 'extension', 'postgrado', 'unisalud'];

    private const MODELOS_AUTOGESTION = [
        'extension' => IngresoExtension::class,
        'postgrado' => IngresoPostgrado::class,
        'unisalud' => IngresoUnisalud::class,
    ];

    /**
     * @param string|null $dependenciaFiltro Si no es null (modo Usuario), solo se muestra esa
     *        dependencia con sus hijas, podando las ramas sin valores.
     * @param array<int, ?int> $aniosVentana año calendario => id de anios_presupuestales (null si no existe), para la gráfica.
     * @return array{nodos: array, totales: array, serie: array}
     */
    public function construirDatos(string $bloque, ?string $dependenciaFiltro, int $anioVigenteId, array $aniosVentana): array
    {
        $arbol = $this->arbolDependencias();
        if ($dependenciaFiltro !== null) {
            $arbol = $this->subarbol($arbol, $dependenciaFiltro);
        }

        // El tope de autogestión es institucional: solo se compara sin filtro de dependencia.
        $tope = $bloque !== 'gastos' && $dependenciaFiltro === null
            ? (new AutogestionPorcentaje())->obtenerTopePorModulo($bloque)
            : null;

        $nodos = $this->nodosDelBloque($bloque, $arbol, $anioVigenteId);
        $totales = $this->totalesDelBloque($bloque, $nodos, $tope);

        if ($dependenciaFiltro !== null) {
            $nodos = $this->podar($nodos, $this->clavesDeValor($bloque));
        }

        $serie = [];
        if ($tope !== null || $bloque === 'gastos') {
            foreach ($aniosVentana as $anio => $anioId) {
                if ($anioId === null) {
                    $serie[] = ['anio' => (int) $anio, 'porcentaje' => null, 'valor' => null, 'monto' => null];
                    continue;
                }

                $nodosAnio = $this->nodosDelBloque($bloque, $arbol, (int) $anioId);
                $totalesAnio = $this->totalesDelBloque($bloque, $nodosAnio, $tope);

                if ($bloque === 'gastos') {
                    $valor = $totalesAnio['techo'] > 0 ? $totalesAnio['techo'] : null;
                    $monto = $totalesAnio['asignacion'];
                } else {
                    $valor = $tope > 0 ? $tope : null;
                    $monto = $totalesAnio['ingresos'];
                }

                $serie[] = [
                    'anio' => (int) $anio,
                    'porcentaje' => $totalesAnio['porcentaje'],
                    'valor' => $valor,
                    'monto' => $monto,
                ];
            }
        }

        return ['nodos' => $nodos, 'totales' => $totales, 'serie' => $serie];
    }

    /** Árbol de dependencias monetizables: una raíz por cada dependencia sin padre. */
    public function arbolDependencias(): array
    {
        $modeloDependencia = new Dependencia();
        $arbol = [];

        foreach ($modeloDependencia->obtenerTodas() as $dependencia) {
            if (!empty($dependencia['flujo_id']) || (int) $dependencia['no_monetizable'] === 1) {
                continue;
            }

            $arbol[] = [
                'id' => (int) $dependencia['id'],
                'nombre' => $dependencia['nombre'],
                'hijos' => $this->convertirHijos($modeloDependencia->construirArbolDescendientes((int) $dependencia['id'])),
            ];
        }

        return $arbol;
    }

    private function convertirHijos(array $nodos): array
    {
        return array_map(fn (array $nodo): array => [
            'id' => (int) $nodo['dependencia']['id'],
            'nombre' => $nodo['dependencia']['nombre'],
            'hijos' => $this->convertirHijos($nodo['hijos']),
        ], $nodos);
    }

    private function subarbol(array $arbol, string $nombre): array
    {
        foreach ($arbol as $nodo) {
            if ($nodo['nombre'] === $nombre) {
                return [$nodo];
            }

            $encontrado = $this->subarbol($nodo['hijos'], $nombre);
            if ($encontrado !== []) {
                return $encontrado;
            }
        }

        return [];
    }

    private function nodosDelBloque(string $bloque, array $arbol, int $anioId): array
    {
        if ($bloque === 'gastos') {
            $presupuestos = (new PresupuestoDependencia())->obtenerPorAnio($anioId);
            $gastado = (new Gasto())->obtenerTotalesEjecutadosPorDependencia($anioId);

            return $this->agruparRectoria($this->agregarRaicesSinTecho($this->calcularGastos($arbol, $presupuestos, $gastado)));
        }

        $claseModelo = self::MODELOS_AUTOGESTION[$bloque];
        $ingresos = (new $claseModelo())->obtenerTotalesPorDependencia($anioId);

        return $this->listaIngresosPropios($arbol, $ingresos);
    }

    /**
     * Misma regla que TechosControlador::calcularAsignadoArbol(): lo asignado a un nodo es lo que
     * gastó él mismo más lo de las hijas que NO tienen techo propio (las que sí tienen techo ya
     * están comprometidas en el techo que se les delegó). Restante = techo − asignado.
     */
    private function calcularGastos(array $nodos, array $presupuestos, array $gastado): array
    {
        $resultado = [];
        foreach ($nodos as $nodo) {
            $hijos = $this->calcularGastos($nodo['hijos'], $presupuestos, $gastado);

            $asignacion = (float) ($gastado[$nodo['nombre']] ?? 0.0);
            foreach ($hijos as $hijo) {
                if ($hijo['techo'] === null || $hijo['techo'] <= 0) {
                    $asignacion += $hijo['asignacion'];
                }
            }

            $techo = isset($presupuestos[$nodo['id']]['techo']) ? (float) $presupuestos[$nodo['id']]['techo'] : null;

            $resultado[] = [
                'id' => $nodo['id'],
                'nombre' => $nodo['nombre'],
                'destacado' => $this->esVicerrectoria($nodo['nombre']),
                'techo' => $techo,
                'asignacion' => $asignacion,
                'restante' => $techo !== null ? $techo - $asignacion : null,
                'porcentaje' => $techo !== null && $techo > 0 ? $asignacion / $techo * 100 : null,
                'hijos' => $hijos,
            ];
        }

        return $resultado;
    }

    /**
     * Agrupa, dentro de su padre, el tramo de hermanas que va desde RECTORIA hasta SECRETARIA
     * GENERAL (en el orden de código de la institución). El grupo solo suma a sus miembros, así
     * que los totales del padre no cambian.
     */
    private function agruparRectoria(array $nodos): array
    {
        $inicio = null;
        $fin = null;
        foreach ($nodos as $indice => $nodo) {
            if ($nodo['nombre'] === 'RECTORIA') {
                $inicio = $indice;
            }
            if ($nodo['nombre'] === 'SECRETARIA GENERAL') {
                $fin = $indice;
            }
        }

        if ($inicio !== null && $fin !== null && $inicio < $fin) {
            $miembros = array_slice($nodos, $inicio, $fin - $inicio + 1);
            $nodos = array_merge(array_slice($nodos, 0, $inicio), [$this->nodoGrupoRectoria($miembros)], array_slice($nodos, $fin + 1));
        }

        foreach ($nodos as &$nodo) {
            if (empty($nodo['esGrupo'])) {
                $nodo['hijos'] = $this->agruparRectoria($nodo['hijos']);
            }
        }
        unset($nodo);

        return $nodos;
    }

    private function esVicerrectoria(string $nombre): bool
    {
        return str_contains(strtoupper($nombre), 'VICERRECTOR');
    }

    private function nodoGrupoRectoria(array $miembros): array
    {
        $techos = array_column($miembros, 'techo');
        $hayTecho = array_filter($techos, static fn ($techo): bool => $techo !== null) !== [];
        $techo = $hayTecho ? array_sum(array_map(static fn ($valor): float => (float) ($valor ?? 0.0), $techos)) : null;
        $asignacion = array_sum(array_column($miembros, 'asignacion'));

        return [
            'id' => -1,
            'nombre' => 'Rectoría y Oficinas',
            'esGrupo' => true,
            'destacado' => true,
            'techo' => $techo,
            'asignacion' => $asignacion,
            'restante' => $techo !== null ? $techo - $asignacion : null,
            'porcentaje' => $techo !== null && $techo > 0 ? $asignacion / $techo * 100 : null,
            'hijos' => $miembros,
        ];
    }

    /**
     * Una raíz institucional sin techo propio (Superadmin) se muestra como la suma de sus hijas:
     * techo = suma de techos hijos, asignado = suma de asignados hijos — es lo que Techos llama
     * "Total" (totalTecho y totalAsignado sobre las hijas de la dependencia).
     */
    private function agregarRaicesSinTecho(array $nodos): array
    {
        foreach ($nodos as &$nodo) {
            if ($nodo['techo'] !== null || $nodo['hijos'] === []) {
                continue;
            }

            $techo = 0.0;
            $asignacion = 0.0;
            foreach ($nodo['hijos'] as $hijo) {
                $techo += $hijo['techo'] ?? 0.0;
                $asignacion += $hijo['asignacion'];
            }

            $nodo['techo'] = $techo;
            $nodo['asignacion'] = $asignacion;
            $nodo['restante'] = $techo - $asignacion;
            $nodo['porcentaje'] = $techo > 0 ? $asignacion / $techo * 100 : null;
        }
        unset($nodo);

        return $nodos;
    }

    /**
     * Lista plana de las dependencias que tienen ingresos propios en esta categoría, sin importar
     * su posición en la jerarquía. Cada una muestra solo lo que ella misma ingresó.
     */
    private function listaIngresosPropios(array $arbol, array $ingresos): array
    {
        $planas = [];
        $recorrer = function (array $nodos) use (&$recorrer, &$planas, $ingresos): void {
            foreach ($nodos as $nodo) {
                $propio = (float) ($ingresos[$nodo['nombre']] ?? 0.0);
                if (abs($propio) > 0.005) {
                    $planas[] = [
                        'id' => $nodo['id'],
                        'nombre' => $nodo['nombre'],
                        'destacado' => $this->esVicerrectoria($nodo['nombre']),
                        'ingresos' => $propio,
                        'hijos' => [],
                    ];
                }
                $recorrer($nodo['hijos']);
            }
        };
        $recorrer($arbol);

        usort($planas, static fn (array $a, array $b): int => $b['ingresos'] <=> $a['ingresos']);

        return $planas;
    }

    /**
     * Totales a nivel de las raíces visibles (no se suman hijas y padres a la vez). En autogestión
     * $tope es null cuando no aplica (modo Usuario): entonces no hay restante ni porcentaje.
     */
    private function totalesDelBloque(string $bloque, array $nodos, ?float $tope): array
    {
        if ($bloque === 'gastos') {
            $techo = 0.0;
            $asignacion = 0.0;
            foreach ($nodos as $nodo) {
                $techo += $nodo['techo'] ?? 0.0;
                $asignacion += $nodo['asignacion'];
            }

            return [
                'techo' => $techo,
                'asignacion' => $asignacion,
                'restante' => $techo - $asignacion,
                'porcentaje' => $techo > 0 ? $asignacion / $techo * 100 : null,
            ];
        }

        $ingresos = 0.0;
        foreach ($nodos as $nodo) {
            $ingresos += $nodo['ingresos'];
        }

        if ($tope === null) {
            return ['ingresos' => $ingresos, 'tope' => null, 'restante' => null, 'porcentaje' => null];
        }

        return [
            'ingresos' => $ingresos,
            'tope' => $tope,
            'restante' => $tope - $ingresos,
            'porcentaje' => $tope > 0 ? $ingresos / $tope * 100 : null,
        ];
    }

    private function clavesDeValor(string $bloque): array
    {
        return $bloque === 'gastos' ? ['techo', 'asignacion'] : ['ingresos'];
    }

    /** Quita las ramas sin ningún valor (modo Usuario): un nodo se conserva si él o alguna hija tiene valor. */
    private function podar(array $nodos, array $claves): array
    {
        $resultado = [];
        foreach ($nodos as $nodo) {
            $nodo['hijos'] = $this->podar($nodo['hijos'], $claves);

            $tieneValor = $nodo['hijos'] !== [];
            foreach ($claves as $clave) {
                if (abs((float) $nodo[$clave]) > 0.005) {
                    $tieneValor = true;
                }
            }

            if ($tieneValor) {
                $resultado[] = $nodo;
            }
        }

        return $resultado;
    }
}
