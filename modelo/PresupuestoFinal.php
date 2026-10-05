<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Presupuesto final: archivo plano de presupuesto (columnas de la estructura del sistema de destino).
 * Suma los gastos enviados del año por rubro, centro de costo, programa, procedencia y tipo.
 * - Centro de costo = NIT de la sede (2 dígitos) + código de la dependencia (6 dígitos).
 * - Programa presupuestal = NIT del proyecto PDI.
 * - Período: 01/01 al 31/12 del año presupuestal.
 * - Procedencia: "Gastos" (principal) o "Autogestión - <módulo>". Tipo: "Gasto", o la categoría en autogestión.
 * Las filas sin rubro, sede, dependencia o proyecto válidos se omiten y se cuentan.
 */
class PresupuestoFinal
{
    /** Las nueve columnas de la estructura del sistema de destino, con sus duplicados de lectura, y al final procedencia y tipo. */
    public const ENCABEZADOS = [
        'Código Rubro*', 'Descripción Rubro', 'Código Unidad', 'Código Centro Costo', 'Descripción Centro Costo',
        'Código Fuente Financiación', 'Presupuesto Condicionado', 'Código programa presupuestal',
        'Descripción programa presupuestal', 'Período Inicial', 'Período Final', 'Valor inicial*',
        'Procedencia', 'Tipo',
    ];

    /** Origen de cada tabla: procedencia y de dónde sale el tipo. */
    private const ORIGENES = [
        ['tabla' => 'gastos', 'procedencia' => 'Gastos', 'tipo' => "'Gasto'"],
        ['tabla' => 'gastos_extension', 'procedencia' => 'Autogestión - Extensión', 'tipo' => 'g.categoria'],
        ['tabla' => 'gastos_postgrado', 'procedencia' => 'Autogestión - Postgrado', 'tipo' => 'g.categoria'],
        ['tabla' => 'gastos_unisalud', 'procedencia' => 'Autogestión - Unisalud', 'tipo' => 'g.categoria'],
        ['tabla' => 'gastos_sin_excedentes', 'procedencia' => 'Autogestión - Sin excedentes', 'tipo' => 'g.categoria'],
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** @return array{filas: array<int, array>, omitidas: int, total: float} */
    public function construir(int $anioPresupuestalId, int $anio): array
    {
        $uniones = [];
        foreach (self::ORIGENES as $origen) {
            $uniones[] = "SELECT '{$origen['procedencia']}' AS procedencia, {$origen['tipo']} AS tipo,
                                 g.rubro_id, g.sede_id, g.dependencia, g.proyecto_id, g.valor_total
                          FROM {$origen['tabla']} g WHERE g.anio_presupuestal_id = ? AND g.estado = 'enviado'";
        }

        $consulta = $this->db->prepare(
            'SELECT x.procedencia, x.tipo,
                    r.codigo AS rubro_codigo, r.descripcion AS rubro_descripcion,
                    s.nit AS sede_nit, s.codigo AS sede_codigo,
                    d.codigo AS dependencia_codigo, d.nombre AS dependencia_nombre,
                    p.nit AS proyecto_nit, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre, x.valor_total
             FROM (' . implode(' UNION ALL ', $uniones) . ') x
             LEFT JOIN rubros r ON r.id = x.rubro_id
             LEFT JOIN sedes s ON s.id = x.sede_id
             LEFT JOIN dependencias d ON d.nombre = x.dependencia
             LEFT JOIN proyectos p ON p.id = x.proyecto_id'
        );
        $consulta->execute(array_fill(0, count(self::ORIGENES), $anioPresupuestalId));

        $acumulados = [];
        $omitidas = 0;

        foreach ($consulta->fetchAll() as $fila) {
            if ($fila['rubro_codigo'] === null || $fila['sede_nit'] === null
                || $fila['dependencia_codigo'] === null || $fila['proyecto_nit'] === null
            ) {
                $omitidas++;
                continue;
            }

            $centro = $fila['sede_nit'] . $fila['dependencia_codigo'];
            // Las inversiones de autogestión se distinguen con un 4 al inicio del código de rubro en el archivo.
            // El rubro del catálogo es el mismo (misma descripción) y no se cambia en la base.
            $codigoRubro = $fila['rubro_codigo'];
            if (($fila['tipo'] ?? '') === 'Inversiones' && str_starts_with($codigoRubro, '2.')) {
                $codigoRubro = '4' . substr($codigoRubro, 1);
            }

            $clave = implode('|', [$fila['procedencia'], $fila['tipo'] ?? '', $codigoRubro, $centro, $fila['proyecto_nit']]);

            if (!isset($acumulados[$clave])) {
                $acumulados[$clave] = [
                    'procedencia' => $fila['procedencia'],
                    'tipo' => $fila['tipo'] ?? '',
                    'rubro_codigo' => $codigoRubro,
                    'rubro_descripcion' => $fila['rubro_descripcion'] ?? '',
                    'centro' => $centro,
                    // Centro de costo: "dependencia - código de sede" (p. ej. FACULTAD DE INGENIERÍA - N).
                    'centro_descripcion' => $fila['dependencia_nombre'] . ' - ' . $fila['sede_codigo'],
                    'proyecto_nit' => $fila['proyecto_nit'],
                    // Programa presupuestal: "PX - descripción" (p. ej. P1 - Multilingüismo e interculturalidad).
                    'proyecto_nombre' => $fila['proyecto_codigo'] . ' - ' . $fila['proyecto_nombre'],
                    'valor' => 0.0,
                ];
            }

            $acumulados[$clave]['valor'] += (float) $fila['valor_total'];
        }

        ksort($acumulados);

        $filas = [];
        $total = 0.0;

        foreach ($acumulados as $acumulado) {
            $valor = round($acumulado['valor'], 2);
            $total += $valor;

            $filas[] = [
                $acumulado['rubro_codigo'],
                $acumulado['rubro_descripcion'],
                '',
                $acumulado['centro'],
                $acumulado['centro_descripcion'],
                '',
                0,
                $acumulado['proyecto_nit'],
                $acumulado['proyecto_nombre'],
                '01/01/' . $anio,
                '31/12/' . $anio,
                $valor,
                $acumulado['procedencia'],
                $acumulado['tipo'],
            ];
        }

        return ['filas' => $filas, 'omitidas' => $omitidas, 'total' => round($total, 2)];
    }
}
