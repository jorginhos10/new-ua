<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Presupuesto institucional completo (nómina, obligaciones, inversión y demás capítulos que el
 * resto de la plataforma no captura), cargado por plantilla Excel — pestaña "Programación
 * presupuestal" de ?ruta=analisis (solo SA). Dos lados independientes (Ingresos/Egresos), cada
 * uno con su propio árbol de código jerárquico de puntos (ej. '1.0'/'1.1.0'/'1.1.1.0') — un
 * código terminado en '.0' es una fila TOTAL cuyo valor es la suma de sus descendientes,
 * recalculada aquí mismo al guardar, nunca en el navegador. Solo las hojas de Egresos pueden
 * repartirse a Proyecto(s) PDI (ver plan el-techo-no-deberia-kind-candle).
 */
class PresupuestoInstitucional
{
    private PDO $db;

    public const TIPOS_VALIDOS = ['ingreso', 'egreso'];

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public static function esCodigoTotal(string $codigo): bool
    {
        return str_ends_with($codigo, '.0');
    }

    /**
     * Deriva el código del padre de forma puramente mecánica, sin necesitar una columna aparte:
     * a X se le quita el '.0' final si lo tiene (identidad), se le quita el último segmento
     * restante (identidad del padre), y el código del padre es esa identidad + '.0' (o ninguno si
     * queda vacía). Ejemplos: '1.1.1.0' -> '1.1.0' -> '1.0' -> raíz. '1.1.1.1' (hoja) -> '1.1.1.0'.
     */
    public static function codigoPadre(string $codigo): ?string
    {
        $identidad = self::esCodigoTotal($codigo) ? substr($codigo, 0, -2) : $codigo;
        $segmentos = explode('.', $identidad);
        array_pop($segmentos);

        if (empty($segmentos)) {
            return null;
        }

        return implode('.', $segmentos) . '.0';
    }

    private function obtenerLineas(string $tipo): array
    {
        $consulta = $this->db->prepare(
            'SELECT id, codigo, es_total, descripcion, orden
             FROM presupuesto_institucional_lineas
             WHERE tipo = :tipo
             ORDER BY orden ASC, codigo ASC'
        );
        $consulta->execute(['tipo' => $tipo]);

        return $consulta->fetchAll();
    }

    /**
     * Árbol completo de un lado (ya anidado, hijos dentro de sus padres) con los valores de cada
     * línea para los años pedidos. `$anios` son años calendario reales (no claves relativas como
     * "vigente"/"anterior_total" — eso lo resuelve el llamador, mapeando cada clave a un año
     * concreto antes de llamar aquí, y de vuelta a esa clave después).
     *
     * Cada nodo: id, codigo, etiqueta, nivel, esTotal, valores[anio]=>float, valorCorte,
     * fechaCorte, proyectosPdi (string ya resuelto a nombres, solo hojas de Egresos con mapeo),
     * hijos[].
     */
    public function obtenerArbolConValores(string $tipo, array $anios): array
    {
        $lineas = $this->obtenerLineas($tipo);

        if (empty($lineas)) {
            return [];
        }

        $ids = array_column($lineas, 'id');
        $valoresPorLinea = $this->obtenerValoresPorLineas($ids, $anios);
        $proyectosPorLinea = $tipo === 'egreso' ? $this->obtenerProyectosPorLineas($ids) : [];

        $codigosExistentes = array_flip(array_column($lineas, 'codigo'));

        $nodosPorCodigo = [];
        foreach ($lineas as $linea) {
            $valoresLinea = $valoresPorLinea[(int) $linea['id']] ?? [];
            $valores = [];
            $valorCorte = null;
            $fechaCorte = null;

            foreach ($anios as $anio) {
                $valores[$anio] = (float) ($valoresLinea[$anio]['valor_final'] ?? 0);
                if (isset($valoresLinea[$anio]['valor_corte'])) {
                    $valorCorte = (float) $valoresLinea[$anio]['valor_corte'];
                    $fechaCorte = $valoresLinea[$anio]['fecha_corte'] ?? null;
                }
            }

            $nodosPorCodigo[$linea['codigo']] = [
                'id' => $tipo . '-' . $linea['codigo'],
                'codigo' => $linea['codigo'],
                'etiqueta' => $linea['descripcion'],
                'nivel' => 0,
                'esTotal' => (bool) $linea['es_total'],
                'valores' => $valores,
                'valorCorte' => $valorCorte,
                'fechaCorte' => $fechaCorte,
                'proyectosPdi' => $proyectosPorLinea[(int) $linea['id']]['nombres'] ?? '',
                'proyectosPdiNits' => $proyectosPorLinea[(int) $linea['id']]['nits'] ?? '',
                'hijos' => [],
            ];
        }

        $raices = [];
        foreach ($lineas as $linea) {
            $codigo = $linea['codigo'];
            $codigoPadre = self::codigoPadre($codigo);

            if ($codigoPadre !== null && isset($codigosExistentes[$codigoPadre])) {
                $nodosPorCodigo[$codigo]['nivel'] = $this->contarNivel($codigo, $codigosExistentes);
                $nodosPorCodigo[$codigoPadre]['hijos'][] = &$nodosPorCodigo[$codigo];
            } else {
                $raices[] = &$nodosPorCodigo[$codigo];
            }
        }
        unset($nodosPorCodigo);

        return $raices;
    }

    private function contarNivel(string $codigo, array $codigosExistentes): int
    {
        $nivel = 0;
        $actual = $codigo;

        while (true) {
            $padre = self::codigoPadre($actual);
            if ($padre === null || !isset($codigosExistentes[$padre])) {
                break;
            }
            $nivel++;
            $actual = $padre;
        }

        return $nivel;
    }

    /** [linea_id => [anio => ['valor_final'=>float, 'valor_corte'=>?float, 'fecha_corte'=>?string]]] */
    private function obtenerValoresPorLineas(array $ids, array $anios): array
    {
        if (empty($ids) || empty($anios)) {
            return [];
        }

        $marcadoresIds = implode(',', array_fill(0, count($ids), '?'));
        $marcadoresAnios = implode(',', array_fill(0, count($anios), '?'));

        $consulta = $this->db->prepare(
            "SELECT linea_id, anio, valor_final, valor_corte, fecha_corte
             FROM presupuesto_institucional_valores
             WHERE linea_id IN ($marcadoresIds) AND anio IN ($marcadoresAnios)"
        );
        $consulta->execute(array_merge(array_values($ids), array_values($anios)));

        $resultado = [];
        foreach ($consulta->fetchAll() as $fila) {
            $resultado[(int) $fila['linea_id']][(int) $fila['anio']] = [
                'valor_final' => (float) $fila['valor_final'],
                'valor_corte' => $fila['valor_corte'] !== null ? (float) $fila['valor_corte'] : null,
                'fecha_corte' => $fila['fecha_corte'],
            ];
        }

        return $resultado;
    }

    /**
     * [linea_id => ['nombres' => "P3 - Ampliación de la educación digital; P1 - ...", 'nits' =>
     * "010203;010101"]] — solo Egresos. 'nombres' es para mostrar en el árbol; 'nits' es para
     * prellenar la plantilla de exportación (el mismo formato que se vuelve a leer al importar).
     */
    private function obtenerProyectosPorLineas(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->db->prepare(
            "SELECT lp.linea_id, p.codigo, p.nombre, p.nit
             FROM presupuesto_institucional_linea_proyectos lp
             JOIN proyectos p ON p.id = lp.proyecto_id
             WHERE lp.linea_id IN ($marcadores)
             ORDER BY lp.linea_id, p.id"
        );
        $consulta->execute(array_values($ids));

        $nombresPorLinea = [];
        $nitsPorLinea = [];
        foreach ($consulta->fetchAll() as $fila) {
            $lineaId = (int) $fila['linea_id'];
            $nombresPorLinea[$lineaId][] = $fila['codigo'] . ' - ' . $fila['nombre'];
            $nitsPorLinea[$lineaId][] = $fila['nit'];
        }

        $resultado = [];
        foreach ($nombresPorLinea as $lineaId => $nombres) {
            $resultado[$lineaId] = [
                'nombres' => implode('; ', $nombres),
                'nits' => implode(';', $nitsPorLinea[$lineaId]),
            ];
        }

        return $resultado;
    }

    /**
     * Fecha de corte guardada para el año anterior de este lado — usada solo para la ETIQUETA de
     * la columna "Año anterior (a corte)" en la pestaña Programación presupuestal (ya no hay un
     * selector de fecha editable ahí: la fecha viene de lo importado). Una sola consulta liviana,
     * no hace falta traer todo el árbol solo para esto.
     */
    public function obtenerFechaCorteReferencia(string $tipo, int $anio): ?string
    {
        $consulta = $this->db->prepare(
            "SELECT v.fecha_corte
             FROM presupuesto_institucional_valores v
             JOIN presupuesto_institucional_lineas l ON l.id = v.linea_id
             WHERE l.tipo = :tipo AND v.anio = :anio AND v.fecha_corte IS NOT NULL
             LIMIT 1"
        );
        $consulta->execute(['tipo' => $tipo, 'anio' => $anio]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila['fecha_corte'] : null;
    }

    /**
     * proyecto_id => valor ya repartido para el año dado: cada línea hoja de Egresos con
     * proyectos asociados reparte su valor_final en partes iguales entre ellos. Nunca incluye
     * Ingresos (no tiene este concepto).
     */
    public function obtenerAportePorProyecto(int $anio): array
    {
        $consulta = $this->db->prepare(
            "SELECT lp.proyecto_id, v.valor_final,
                    (SELECT COUNT(*) FROM presupuesto_institucional_linea_proyectos lp2 WHERE lp2.linea_id = lp.linea_id) AS num_proyectos
             FROM presupuesto_institucional_linea_proyectos lp
             JOIN presupuesto_institucional_lineas l ON l.id = lp.linea_id AND l.tipo = 'egreso'
             JOIN presupuesto_institucional_valores v ON v.linea_id = lp.linea_id AND v.anio = :anio"
        );
        $consulta->execute(['anio' => $anio]);

        $aporte = [];
        foreach ($consulta->fetchAll() as $fila) {
            $numProyectos = max(1, (int) $fila['num_proyectos']);
            $proyectoId = (int) $fila['proyecto_id'];
            $aporte[$proyectoId] = ($aporte[$proyectoId] ?? 0.0) + ((float) $fila['valor_final'] / $numProyectos);
        }

        return $aporte;
    }

    /**
     * Todo o nada para AMBOS lados (una sola plantilla, un solo archivo, un solo envío): upsert
     * de líneas (por tipo+código), upsert de valores importados (hoja), reemplazo completo del
     * mapeo a Proyecto(s) PDI de las líneas del lote, y recálculo de las filas total (suma de
     * descendientes, nunca lo que traiga el archivo en esa celda).
     *
     * Forma esperada de cada línea en $lineasIngreso/$lineasEgreso:
     * ['codigo'=>string, 'descripcion'=>string, 'proyectos_ids'=>int[], 'valores'=>[anio =>
     * ['valor_final'=>float, 'valor_corte'=>?float, 'fecha_corte'=>?string]]].
     */
    public function guardarLote(array $lineasIngreso, array $lineasEgreso, int $usuarioId): void
    {
        $this->db->beginTransaction();

        try {
            $this->guardarLadoLote('ingreso', $lineasIngreso, $usuarioId);
            $this->guardarLadoLote('egreso', $lineasEgreso, $usuarioId);
            $this->recalcularTotales('ingreso');
            $this->recalcularTotales('egreso');
            $this->db->commit();
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    private function guardarLadoLote(string $tipo, array $lineas, int $usuarioId): void
    {
        $upsertLinea = $this->db->prepare(
            'INSERT INTO presupuesto_institucional_lineas (tipo, codigo, es_total, descripcion, orden, actualizado_por)
             VALUES (:tipo, :codigo, :es_total, :descripcion, :orden, :actualizado_por)
             ON DUPLICATE KEY UPDATE es_total = VALUES(es_total), descripcion = VALUES(descripcion),
                 orden = VALUES(orden), actualizado_por = VALUES(actualizado_por)'
        );
        $obtenerId = $this->db->prepare(
            'SELECT id FROM presupuesto_institucional_lineas WHERE tipo = :tipo AND codigo = :codigo'
        );
        $upsertValor = $this->db->prepare(
            'INSERT INTO presupuesto_institucional_valores (linea_id, anio, valor_final, valor_corte, fecha_corte)
             VALUES (:linea_id, :anio, :valor_final, :valor_corte, :fecha_corte)
             ON DUPLICATE KEY UPDATE valor_final = VALUES(valor_final), valor_corte = VALUES(valor_corte),
                 fecha_corte = VALUES(fecha_corte)'
        );
        $borrarProyectos = $this->db->prepare(
            'DELETE FROM presupuesto_institucional_linea_proyectos WHERE linea_id = :linea_id'
        );
        $insertarProyecto = $this->db->prepare(
            'INSERT IGNORE INTO presupuesto_institucional_linea_proyectos (linea_id, proyecto_id) VALUES (:linea_id, :proyecto_id)'
        );

        foreach ($lineas as $orden => $linea) {
            $codigo = $linea['codigo'];
            $upsertLinea->execute([
                'tipo' => $tipo,
                'codigo' => $codigo,
                'es_total' => self::esCodigoTotal($codigo) ? 1 : 0,
                'descripcion' => $linea['descripcion'],
                'orden' => $orden,
                'actualizado_por' => $usuarioId,
            ]);

            $obtenerId->execute(['tipo' => $tipo, 'codigo' => $codigo]);
            $lineaId = (int) $obtenerId->fetchColumn();

            foreach ($linea['valores'] as $anio => $valor) {
                $upsertValor->execute([
                    'linea_id' => $lineaId,
                    'anio' => $anio,
                    'valor_final' => $valor['valor_final'] ?? 0,
                    'valor_corte' => $valor['valor_corte'] ?? null,
                    'fecha_corte' => $valor['fecha_corte'] ?? null,
                ]);
            }

            if (!self::esCodigoTotal($codigo)) {
                $borrarProyectos->execute(['linea_id' => $lineaId]);
                foreach ($linea['proyectos_ids'] ?? [] as $proyectoId) {
                    $insertarProyecto->execute(['linea_id' => $lineaId, 'proyecto_id' => $proyectoId]);
                }
            }
        }
    }

    /**
     * Recalcula, de más profundo a menos profundo, el valor de cada fila total como la suma de
     * sus hijos directos — así una fila total de 2 niveles ('1.0') ya suma un hijo total de 1
     * nivel ('1.1.0') que a su vez acaba de sumar sus propias hojas, en la misma pasada.
     */
    private function recalcularTotales(string $tipo): void
    {
        $lineas = $this->obtenerLineas($tipo);
        $idsPorCodigo = [];
        foreach ($lineas as $linea) {
            $idsPorCodigo[$linea['codigo']] = (int) $linea['id'];
        }

        $hijosPorCodigo = [];
        foreach ($lineas as $linea) {
            $padre = self::codigoPadre($linea['codigo']);
            if ($padre !== null && isset($idsPorCodigo[$padre])) {
                $hijosPorCodigo[$padre][] = $linea['codigo'];
            }
        }

        $codigosTotales = array_column(array_filter($lineas, static fn (array $l): bool => (bool) $l['es_total']), 'codigo');
        usort($codigosTotales, static fn (string $a, string $b): int => substr_count($b, '.') <=> substr_count($a, '.'));

        $upsertValor = $this->db->prepare(
            'INSERT INTO presupuesto_institucional_valores (linea_id, anio, valor_final, valor_corte)
             VALUES (:linea_id, :anio, :valor_final, :valor_corte)
             ON DUPLICATE KEY UPDATE valor_final = VALUES(valor_final), valor_corte = VALUES(valor_corte)'
        );

        foreach ($codigosTotales as $codigoTotal) {
            $hijos = $hijosPorCodigo[$codigoTotal] ?? [];
            if (empty($hijos)) {
                continue;
            }

            $idsHijos = array_map(static fn (string $c): int => $idsPorCodigo[$c], $hijos);
            $marcadores = implode(',', array_fill(0, count($idsHijos), '?'));

            $consulta = $this->db->prepare(
                "SELECT anio, SUM(valor_final) AS suma_final, SUM(valor_corte) AS suma_corte
                 FROM presupuesto_institucional_valores
                 WHERE linea_id IN ($marcadores)
                 GROUP BY anio"
            );
            $consulta->execute($idsHijos);

            $lineaId = $idsPorCodigo[$codigoTotal];
            foreach ($consulta->fetchAll() as $sumaFila) {
                $upsertValor->execute([
                    'linea_id' => $lineaId,
                    'anio' => $sumaFila['anio'],
                    'valor_final' => $sumaFila['suma_final'],
                    'valor_corte' => $sumaFila['suma_corte'],
                ]);
            }
        }
    }
}
