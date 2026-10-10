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
 *
 * Un tercer `tipo`, 'proyecto', reutiliza este mismo mecanismo (mismas tablas, mismo árbol de
 * código, propia numeración vía UNIQUE(tipo, codigo)) para la pestaña "Proyectos" — pero NO es
 * un tercer lado real del presupuesto institucional: es un desglose aparte, por proyecto PDI, de
 * la inversión que en Egresos vive agregada bajo un código de capítulo. Nunca se reparte a
 * Proyecto(s) PDI ni aporta a Articulación PDI (esa lógica sigue filtrando `tipo = 'egreso'`
 * explícitamente) — se guarda con `guardarLoteUnico()`, no con `guardarLote()` (que siempre
 * exige la pareja ingreso+egreso).
 */
class PresupuestoInstitucional
{
    private PDO $db;

    public const TIPOS_VALIDOS = ['ingreso', 'egreso', 'proyecto'];

    /**
     * Color del punto de "Indexación" (solo hojas de Programación presupuestal — el criterio con
     * el que se proyecta esa línea, diligenciado por texto libre desde la plantilla Excel). Los
     * términos conocidos usan los colores institucionales/secundarios del manual de marca; uno
     * nuevo que no esté en la lista cae en la paleta de respaldo según un hash de su texto, para
     * que siempre salga un color estable sin tener que tocar este código.
     */
    private const COLORES_INDEXACION = [
        'IPC' => '#143163',   // azul institucional
        'SMMLV' => '#D85819', // naranja institucional
        'ICES' => '#F9B233',  // amarillo secundario
    ];

    private const PALETA_INDEXACION_RESPALDO = ['#FF9912', '#1D71B8', '#706F6F'];

    public static function colorIndexacion(?string $texto): ?string
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }

        $clave = mb_strtoupper($texto);
        if (isset(self::COLORES_INDEXACION[$clave])) {
            return self::COLORES_INDEXACION[$clave];
        }

        $paleta = self::PALETA_INDEXACION_RESPALDO;

        return $paleta[crc32($clave) % count($paleta)];
    }

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
            'SELECT id, codigo, es_total, descripcion, indexacion, orden
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
     * proyectosPdi (string ya resuelto a nombres, solo hojas de Egresos con mapeo), indexacion
     * (texto libre, solo hojas — ver colorIndexacion()), hijos[].
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

        return $this->construirArbolDesdeDatos($tipo, $lineas, $valoresPorLinea, $proyectosPorLinea, $anios);
    }

    /**
     * Igual forma que obtenerArbolConValores(), pero leyendo de una versión congelada
     * (presupuesto_institucional_versiones_datos) en vez de las tablas en vivo — usada en modo
     * Repositorio cuando hay una versión seleccionada. Los Proyecto(s) PDI se resuelven contra la
     * tabla `proyectos` real (catálogo estable, no se congela) usando los ids que sí quedaron
     * congelados en el mapeo linea->proyecto de esa versión.
     */
    public function obtenerArbolConValoresDeVersion(int $versionId, array $anios): array
    {
        $consulta = $this->db->prepare('SELECT datos FROM presupuesto_institucional_versiones_datos WHERE version_id = :version_id');
        $consulta->execute(['version_id' => $versionId]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return [];
        }

        $datos = json_decode($fila['datos'], true) ?? [];
        $lineas = $datos['lineas'] ?? [];

        if (empty($lineas)) {
            return [];
        }

        $tipo = $lineas[0]['tipo'];

        $valoresPorLinea = [];
        foreach ($datos['valores'] ?? [] as $valor) {
            $valoresPorLinea[(int) $valor['linea_id']][(int) $valor['anio']] = [
                'valor_final' => (float) $valor['valor_final'],
                'valor_corte' => $valor['valor_corte'] !== null ? (float) $valor['valor_corte'] : null,
            ];
        }

        $idsProyectoPorLinea = [];
        foreach ($datos['proyectos'] ?? [] as $mapa) {
            $idsProyectoPorLinea[(int) $mapa['linea_id']][] = (int) $mapa['proyecto_id'];
        }

        $todosLosIds = [];
        foreach ($idsProyectoPorLinea as $idsLinea) {
            $todosLosIds = array_merge($todosLosIds, $idsLinea);
        }
        $todosLosIds = array_unique($todosLosIds);

        $proyectosInfo = [];
        if (!empty($todosLosIds)) {
            $marcadores = implode(',', array_fill(0, count($todosLosIds), '?'));
            $consultaP = $this->db->prepare("SELECT id, codigo, nombre, nit FROM proyectos WHERE id IN ($marcadores)");
            $consultaP->execute(array_values($todosLosIds));
            foreach ($consultaP->fetchAll() as $p) {
                $proyectosInfo[(int) $p['id']] = $p;
            }
        }

        $proyectosPorLinea = [];
        foreach ($idsProyectoPorLinea as $lineaId => $idsLinea) {
            $nombres = [];
            $nits = [];
            foreach ($idsLinea as $proyectoId) {
                if (isset($proyectosInfo[$proyectoId])) {
                    $nombres[] = $proyectosInfo[$proyectoId]['codigo'] . ' - ' . $proyectosInfo[$proyectoId]['nombre'];
                    $nits[] = $proyectosInfo[$proyectoId]['nit'];
                }
            }
            $proyectosPorLinea[$lineaId] = ['nombres' => implode('; ', $nombres), 'nits' => implode(';', $nits)];
        }

        return $this->construirArbolDesdeDatos($tipo, $lineas, $valoresPorLinea, $proyectosPorLinea, $anios);
    }

    /** Ensambla el árbol anidado a partir de filas ya resueltas (en vivo o desde una versión). */
    private function construirArbolDesdeDatos(string $tipo, array $lineas, array $valoresPorLinea, array $proyectosPorLinea, array $anios): array
    {
        $codigosExistentes = array_flip(array_column($lineas, 'codigo'));

        $nodosPorCodigo = [];
        foreach ($lineas as $linea) {
            $valoresLinea = $valoresPorLinea[(int) $linea['id']] ?? [];
            $valores = [];
            $valorCorte = null;

            foreach ($anios as $anio) {
                $valores[$anio] = (float) ($valoresLinea[$anio]['valor_final'] ?? 0);
                if (isset($valoresLinea[$anio]['valor_corte'])) {
                    $valorCorte = (float) $valoresLinea[$anio]['valor_corte'];
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
                'proyectosPdi' => $proyectosPorLinea[(int) $linea['id']]['nombres'] ?? '',
                'proyectosPdiNits' => $proyectosPorLinea[(int) $linea['id']]['nits'] ?? '',
                'indexacion' => $linea['indexacion'] ?? null,
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

    /** [linea_id => [anio => ['valor_final'=>float, 'valor_corte'=>?float]]] */
    private function obtenerValoresPorLineas(array $ids, array $anios): array
    {
        if (empty($ids) || empty($anios)) {
            return [];
        }

        $marcadoresIds = implode(',', array_fill(0, count($ids), '?'));
        $marcadoresAnios = implode(',', array_fill(0, count($anios), '?'));

        $consulta = $this->db->prepare(
            "SELECT linea_id, anio, valor_final, valor_corte
             FROM presupuesto_institucional_valores
             WHERE linea_id IN ($marcadoresIds) AND anio IN ($marcadoresAnios)"
        );
        $consulta->execute(array_merge(array_values($ids), array_values($anios)));

        $resultado = [];
        foreach ($consulta->fetchAll() as $fila) {
            $resultado[(int) $fila['linea_id']][(int) $fila['anio']] = [
                'valor_final' => (float) $fila['valor_final'],
                'valor_corte' => $fila['valor_corte'] !== null ? (float) $fila['valor_corte'] : null,
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

    /** Versiones guardadas de un lado (Egresos e Ingresos tienen cada uno su propia lista). */
    public function obtenerVersiones(string $tipo): array
    {
        $consulta = $this->db->prepare(
            'SELECT v.id, v.nombre, v.activa, v.creado_en, u.nombre AS creado_por_nombre
             FROM presupuesto_institucional_versiones v
             LEFT JOIN usuarios u ON u.id = v.creado_por
             WHERE v.tipo = :tipo
             ORDER BY v.creado_en DESC'
        );
        $consulta->execute(['tipo' => $tipo]);

        return $consulta->fetchAll();
    }

    /**
     * La versión que "Repositorio" debe mostrar cuando el admin no acaba de elegir otra —
     * la última que él marcó vía marcarVersionActiva(), o, si ninguna está marcada todavía
     * (o la marcada se eliminó), la más reciente, igual que el comportamiento de siempre.
     */
    public function obtenerVersionActiva(string $tipo): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, tipo, nombre, activa, creado_en, creado_por
             FROM presupuesto_institucional_versiones
             WHERE tipo = :tipo
             ORDER BY activa DESC, creado_en DESC, id DESC
             LIMIT 1'
        );
        $consulta->execute(['tipo' => $tipo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * El admin elige, desde el selector de "Repositorio", cuál versión ver — esa elección
     * queda como la activa para todos (no solo para su propia visita) hasta que él la
     * cambie de nuevo. Egresos e Ingresos nunca comparten esta marca (acotado por $tipo).
     */
    public function marcarVersionActiva(int $id, string $tipo): void
    {
        $consultaVerificar = $this->db->prepare('SELECT id FROM presupuesto_institucional_versiones WHERE id = :id AND tipo = :tipo');
        $consultaVerificar->execute(['id' => $id, 'tipo' => $tipo]);
        if ($consultaVerificar->fetch() === false) {
            return;
        }

        $this->db->beginTransaction();

        try {
            $this->db->prepare('UPDATE presupuesto_institucional_versiones SET activa = 0 WHERE tipo = :tipo')->execute(['tipo' => $tipo]);
            $this->db->prepare('UPDATE presupuesto_institucional_versiones SET activa = 1 WHERE id = :id')->execute(['id' => $id]);
            $this->db->commit();
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    /**
     * Congela las líneas + valores + mapeo a Proyecto(s) PDI de UN lado (Egresos o Ingresos, cada
     * uno con su propia lista de versiones — nunca se mezclan) en un solo bloque JSON, igual de
     * espíritu a VersionArbol/Snapshot pero acotado a este lado puntual.
     */
    public function crearVersion(string $tipo, string $nombre, int $usuarioId): int
    {
        $lineas = $this->obtenerLineas($tipo);
        foreach ($lineas as &$linea) {
            $linea['tipo'] = $tipo;
        }
        unset($linea);

        $ids = array_column($lineas, 'id');

        $valores = [];
        $proyectos = [];
        if (!empty($ids)) {
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $consulta = $this->db->prepare("SELECT * FROM presupuesto_institucional_valores WHERE linea_id IN ($marcadores)");
            $consulta->execute(array_values($ids));
            $valores = $consulta->fetchAll();

            if ($tipo === 'egreso') {
                $consultaP = $this->db->prepare("SELECT linea_id, proyecto_id FROM presupuesto_institucional_linea_proyectos WHERE linea_id IN ($marcadores)");
                $consultaP->execute(array_values($ids));
                $proyectos = $consultaP->fetchAll();
            }
        }

        $datos = json_encode(['lineas' => $lineas, 'valores' => $valores, 'proyectos' => $proyectos], JSON_UNESCAPED_UNICODE);

        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare('INSERT INTO presupuesto_institucional_versiones (tipo, nombre, creado_por) VALUES (:tipo, :nombre, :creado_por)');
            $consulta->execute(['tipo' => $tipo, 'nombre' => $nombre, 'creado_por' => $usuarioId]);
            $versionId = (int) $this->db->lastInsertId();

            $this->db->prepare('INSERT INTO presupuesto_institucional_versiones_datos (version_id, datos) VALUES (:version_id, :datos)')
                ->execute(['version_id' => $versionId, 'datos' => $datos]);

            $this->db->commit();

            return $versionId;
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    public function eliminarVersion(int $id): bool
    {
        $fila = $this->obtenerVersionPorId($id);
        if ($fila === null) {
            return false;
        }

        $consulta = $this->db->prepare('DELETE FROM presupuesto_institucional_versiones WHERE id = :id');
        $resultado = $consulta->execute(['id' => $id]);

        if ($resultado && !empty($fila['activa'])) {
            // Se borró la que estaba marcada como activa: si queda alguna otra de este tipo,
            // la más reciente pasa a ser la nueva activa — para que "Activa" nunca quede
            // vacía mientras Análisis sigue mostrando algo (el fallback de
            // obtenerVersionActiva()) y ambos queden de acuerdo.
            $siguiente = $this->obtenerVersionActiva($fila['tipo']);
            if ($siguiente !== null) {
                $this->marcarVersionActiva((int) $siguiente['id'], $fila['tipo']);
            }
        }

        return $resultado;
    }

    public function obtenerVersionPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, tipo, nombre, activa, creado_en, creado_por FROM presupuesto_institucional_versiones WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Todo o nada para AMBOS lados (una sola plantilla, un solo archivo, un solo envío): upsert
     * de líneas (por tipo+código), upsert de valores importados (hoja), reemplazo completo del
     * mapeo a Proyecto(s) PDI de las líneas del lote, y recálculo de las filas total (suma de
     * descendientes, nunca lo que traiga el archivo en esa celda).
     *
     * Forma esperada de cada línea en $lineasIngreso/$lineasEgreso:
     * ['codigo'=>string, 'descripcion'=>string, 'proyectos_ids'=>int[], 'indexacion'=>?string,
     * 'valores'=>[anio => ['valor_final'=>float, 'valor_corte'=>?float]]].
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

    /** Como guardarLote(), pero para un lado sin pareja ingreso/egreso — hoy solo 'proyecto'. */
    public function guardarLoteUnico(string $tipo, array $lineas, int $usuarioId): void
    {
        $this->db->beginTransaction();

        try {
            $this->guardarLadoLote($tipo, $lineas, $usuarioId);
            $this->recalcularTotales($tipo);
            $this->db->commit();
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    private function guardarLadoLote(string $tipo, array $lineas, int $usuarioId): void
    {
        $upsertLinea = $this->db->prepare(
            'INSERT INTO presupuesto_institucional_lineas (tipo, codigo, es_total, descripcion, indexacion, orden, actualizado_por)
             VALUES (:tipo, :codigo, :es_total, :descripcion, :indexacion, :orden, :actualizado_por)
             ON DUPLICATE KEY UPDATE es_total = VALUES(es_total), descripcion = VALUES(descripcion),
                 indexacion = VALUES(indexacion), orden = VALUES(orden), actualizado_por = VALUES(actualizado_por)'
        );
        $obtenerId = $this->db->prepare(
            'SELECT id FROM presupuesto_institucional_lineas WHERE tipo = :tipo AND codigo = :codigo'
        );
        $upsertValor = $this->db->prepare(
            'INSERT INTO presupuesto_institucional_valores (linea_id, anio, valor_final, valor_corte)
             VALUES (:linea_id, :anio, :valor_final, :valor_corte)
             ON DUPLICATE KEY UPDATE valor_final = VALUES(valor_final), valor_corte = VALUES(valor_corte)'
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
                'indexacion' => $linea['indexacion'] ?? null,
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
