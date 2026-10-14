<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/TechosMetas.php';
require_once __DIR__ . '/AutogestionPorcentaje.php';
require_once __DIR__ . '/AnioPresupuestal.php';

/**
 * Versiones congeladas de "Techos y Metas" (pestaña de ?ruta=analisis) — independiente de
 * VersionArbol/PresupuestoInstitucional/Snapshot, mismo molde (tabla propia + `activa` +
 * crear/eliminar/marcarActiva/obtenerActiva) pero sin compartir código con ellas.
 *
 * Lo que se congela por cada bloque de TechosMetas::BLOQUES no son las tablas crudas (gastos,
 * ingresos de cada módulo, presupuesto por dependencia, etc. — serían ~8 tablas con joins y reglas
 * de negocio no triviales) sino el RESULTADO ya calculado de TechosMetas::nodosDelBloque(), tanto
 * del año vigente como de los años de la ventana de 5 años (para poder reconstruir también la
 * "serie" histórica), más el árbol institucional de dependencias (sin podar) y el tope del módulo.
 * Con eso, obtenerBloqueDeVersion() puede reconstruir exactamente lo mismo que
 * TechosMetas::construirDatos() arma en vivo — incluido el filtro por dependencia (modo Usuario) —
 * usando los mismos métodos puros de TechosMetas (subarbol/totalesDelBloque/podar, ya públicos)
 * pero sin volver a tocar la base de datos: nodosDelBloque() (el único que sí consulta) solo se
 * llama una vez, al crear la versión, nunca al leerla.
 */
class VersionTechos
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT v.id, v.nombre, v.activa, v.creado_en, u.nombre AS creado_por_nombre
             FROM techos_versiones v
             LEFT JOIN usuarios u ON u.id = v.creado_por
             ORDER BY v.creado_en DESC'
        );

        return $consulta->fetchAll();
    }

    /** La versión que "Repositorio" debe mostrar cuando el admin no acaba de elegir otra. */
    public function obtenerActiva(): ?array
    {
        $consulta = $this->db->query(
            'SELECT id, nombre, activa, creado_en, creado_por
             FROM techos_versiones
             ORDER BY activa DESC, creado_en DESC, id DESC
             LIMIT 1'
        );
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, activa, creado_en, creado_por FROM techos_versiones WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function marcarActiva(int $id): void
    {
        $consultaVerificar = $this->db->prepare('SELECT id FROM techos_versiones WHERE id = :id');
        $consultaVerificar->execute(['id' => $id]);
        if ($consultaVerificar->fetch() === false) {
            return;
        }

        $this->db->beginTransaction();

        try {
            $this->db->exec('UPDATE techos_versiones SET activa = 0');
            $this->db->prepare('UPDATE techos_versiones SET activa = 1 WHERE id = :id')->execute(['id' => $id]);
            $this->db->commit();
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    /**
     * Congela los 4 bloques con el mismo año vigente/ventana de 5 años que usa
     * AnalisisControlador::renderizarTechosMetas() — para que "serie" se pueda reconstruir después.
     */
    public function crear(string $nombre, int $usuarioId): int
    {
        [$anioVigenteNumero, $aniosVentana] = $this->resolverAnios();
        $techosMetas = new TechosMetas();
        $arbol = $techosMetas->arbolDependencias();

        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare('INSERT INTO techos_versiones (nombre, creado_por) VALUES (:nombre, :creado_por)');
            $consulta->execute(['nombre' => $nombre, 'creado_por' => $usuarioId]);
            $versionId = (int) $this->db->lastInsertId();

            $insertarDatos = $this->db->prepare(
                'INSERT INTO techos_versiones_datos (version_id, bloque, datos) VALUES (:version_id, :bloque, :datos)'
            );

            foreach (TechosMetas::BLOQUES as $bloque) {
                $tope = $bloque !== 'gastos' ? (new AutogestionPorcentaje())->obtenerTopePorModulo($bloque) : null;

                // La ventana ya incluye el año vigente como su último año (ver resolverAnios()),
                // así que nodosPorAnio[$anioVigenteNumero] sirve también como "el año vigente".
                $nodosPorAnio = [];
                foreach ($aniosVentana as $anio => $anioId) {
                    $nodosPorAnio[$anio] = $anioId !== null ? $techosMetas->nodosDelBloque($bloque, $arbol, (int) $anioId) : null;
                }

                $insertarDatos->execute([
                    'version_id' => $versionId,
                    'bloque' => $bloque,
                    'datos' => json_encode([
                        'arbol' => $arbol,
                        'anioVigente' => $anioVigenteNumero,
                        'nodosPorAnio' => $nodosPorAnio,
                        'tope' => $tope,
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $this->db->commit();

            return $versionId;
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    public function eliminar(int $id): bool
    {
        $fila = $this->obtenerPorId($id);
        if ($fila === null) {
            return false;
        }

        $consulta = $this->db->prepare('DELETE FROM techos_versiones WHERE id = :id');
        $resultado = $consulta->execute(['id' => $id]);

        if ($resultado && !empty($fila['activa'])) {
            // Se borró la que estaba marcada como activa: si queda alguna otra, la más reciente
            // pasa a ser la nueva activa — igual que VersionArbol::eliminar().
            $siguiente = $this->obtenerActiva();
            if ($siguiente !== null) {
                $this->marcarActiva((int) $siguiente['id']);
            }
        }

        return $resultado;
    }

    /**
     * Reconstruye ['nodos', 'totales', 'serie'] con la misma forma que
     * TechosMetas::construirDatos() devuelve en vivo, a partir de lo que se congeló —
     * $dependenciaFiltro filtra igual que el modo Usuario en vivo, sin tocar la base de datos.
     */
    public function obtenerBloqueDeVersion(int $versionId, string $bloque, ?string $dependenciaFiltro): ?array
    {
        $consulta = $this->db->prepare('SELECT datos FROM techos_versiones_datos WHERE version_id = :version_id AND bloque = :bloque');
        $consulta->execute(['version_id' => $versionId, 'bloque' => $bloque]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        $congelado = json_decode($fila['datos'], true);
        if ($congelado === null) {
            return null;
        }

        $techosMetas = new TechosMetas();
        $arbol = $congelado['arbol'];
        $tope = $dependenciaFiltro === null ? $congelado['tope'] : null;

        // Nombres de dependencia dentro del filtro (ella + descendientes) — para recortar las
        // listas planas de autogestión, que no tienen jerarquía propia (ver nota en la clase).
        $nombresFiltro = null;
        if ($dependenciaFiltro !== null) {
            $subarbolFiltro = $techosMetas->subarbol($arbol, $dependenciaFiltro);
            $nombresFiltro = $this->recolectarNombres($subarbolFiltro);
        }

        $nodosAnioVigente = $congelado['nodosPorAnio'][$congelado['anioVigente']] ?? [];
        $nodos = $this->filtrarNodosCongelados($bloque, $nodosAnioVigente, $dependenciaFiltro, $nombresFiltro, $techosMetas);
        $totales = $techosMetas->totalesDelBloque($bloque, $nodos, $tope);

        if ($dependenciaFiltro !== null) {
            $nodos = $techosMetas->podar($nodos, $techosMetas->clavesDeValor($bloque));
        }

        $serie = [];
        if ($tope !== null || $bloque === 'gastos') {
            foreach ($congelado['nodosPorAnio'] as $anio => $nodosAnioCongelados) {
                if ($nodosAnioCongelados === null) {
                    $serie[] = ['anio' => (int) $anio, 'porcentaje' => null, 'valor' => null, 'monto' => null];
                    continue;
                }

                $nodosAnio = $this->filtrarNodosCongelados($bloque, $nodosAnioCongelados, $dependenciaFiltro, $nombresFiltro, $techosMetas);
                $totalesAnio = $techosMetas->totalesDelBloque($bloque, $nodosAnio, $tope);

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

    /**
     * 'gastos' congeló un árbol anidado (mismo shape que subarbol() espera: nombre/hijos) — se
     * filtra con subarbol() directo, igual que en vivo. Los bloques de autogestión congelaron una
     * lista PLANA (ver TechosMetas::listaIngresosPropios(), hijos siempre []): se filtra por
     * pertenencia de nombre al subárbol de dependencias ($nombresFiltro), ya que la lista en sí no
     * tiene jerarquía propia para podar con subarbol().
     */
    private function filtrarNodosCongelados(string $bloque, array $nodos, ?string $dependenciaFiltro, ?array $nombresFiltro, TechosMetas $techosMetas): array
    {
        if ($dependenciaFiltro === null) {
            return $nodos;
        }

        if ($bloque === 'gastos') {
            return $techosMetas->subarbol($nodos, $dependenciaFiltro);
        }

        return array_values(array_filter($nodos, static fn (array $nodo): bool => in_array($nodo['nombre'], $nombresFiltro, true)));
    }

    private function recolectarNombres(array $nodos): array
    {
        $nombres = [];
        foreach ($nodos as $nodo) {
            $nombres[] = $nodo['nombre'];
            $nombres = array_merge($nombres, $this->recolectarNombres($nodo['hijos']));
        }

        return $nombres;
    }

    private function resolverAnios(): array
    {
        $modeloAnio = new AnioPresupuestal();
        $aniosTodos = $modeloAnio->obtenerTodos();
        $aniosActivos = $modeloAnio->obtenerActivos();
        $anioVigenteRegistro = $aniosActivos[0] ?? ($aniosTodos[0] ?? null);
        $anioVigenteNumero = $anioVigenteRegistro !== null ? (int) $anioVigenteRegistro['anio'] : (int) date('Y');

        $idPorAnio = [];
        foreach ($aniosTodos as $filaAnio) {
            $idPorAnio[(int) $filaAnio['anio']] = (int) $filaAnio['id'];
        }

        $aniosVentana = [];
        for ($anio = $anioVigenteNumero - 4; $anio <= $anioVigenteNumero; $anio++) {
            $aniosVentana[$anio] = $idPorAnio[$anio] ?? null;
        }

        return [$anioVigenteNumero, $aniosVentana];
    }
}
