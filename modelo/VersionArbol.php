<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Versión ligera del árbol PDI (Línea/Motor/Proyecto/Gasto/Año presupuestal) — a diferencia de
 * Snapshot.php (copia TODA la base de datos), esta solo congela las 5 tablas que el árbol
 * necesita. Cada pestaña del árbol (Articulación PDI / Programación presupuestal) tiene su
 * propia lista de versiones vía la columna `pestana`, aunque comparten este mismo mecanismo — ver
 * plan el-techo-no-deberia-kind-candle.
 */
class VersionArbol
{
    private PDO $db;

    /**
     * Únicas tablas que el árbol lee — a propósito una lista fija y corta, no toda la BD. Incluye
     * los 3 gastos de autogestión (además de gasto_principal) porque Articulación PDI también sus
     * suma a su total por proyecto — sin ellas, una versión congelada quedaría con el total del
     * proyecto desactualizado frente a gastos_extension/postgrado/unisalud en ese momento.
     */
    private const TABLAS_ARBOL = ['gastos', 'gastos_extension', 'gastos_postgrado', 'gastos_unisalud', 'lineas', 'motores', 'proyectos', 'anios_presupuestales'];

    public const PESTANAS_VALIDAS = ['articulacion_pdi', 'programacion_presupuestal'];

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(string $pestana): array
    {
        $consulta = $this->db->prepare(
            'SELECT v.id, v.nombre, v.activa, v.creado_en, u.nombre AS creado_por_nombre
             FROM arbol_versiones v
             LEFT JOIN usuarios u ON u.id = v.creado_por
             WHERE v.pestana = :pestana
             ORDER BY v.creado_en DESC'
        );
        $consulta->execute(['pestana' => $pestana]);

        return $consulta->fetchAll();
    }

    /**
     * La versión que "Repositorio" debe mostrar cuando el admin no acaba de elegir otra —
     * la última que él marcó vía marcarActiva(), o, si ninguna está marcada todavía (o la
     * marcada se eliminó), la más reciente, igual que el comportamiento de siempre.
     */
    public function obtenerActiva(string $pestana): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, pestana, nombre, activa, creado_en, creado_por
             FROM arbol_versiones
             WHERE pestana = :pestana
             ORDER BY activa DESC, creado_en DESC, id DESC
             LIMIT 1'
        );
        $consulta->execute(['pestana' => $pestana]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * El admin elige, desde el selector de "Repositorio", cuál versión ver — esa elección
     * queda como la activa para todos (no solo para su propia visita) hasta que él la
     * cambie de nuevo.
     */
    public function marcarActiva(int $id, string $pestana): void
    {
        $consultaVerificar = $this->db->prepare('SELECT id FROM arbol_versiones WHERE id = :id AND pestana = :pestana');
        $consultaVerificar->execute(['id' => $id, 'pestana' => $pestana]);
        if ($consultaVerificar->fetch() === false) {
            return;
        }

        $this->db->beginTransaction();

        try {
            $this->db->prepare('UPDATE arbol_versiones SET activa = 0 WHERE pestana = :pestana')->execute(['pestana' => $pestana]);
            $this->db->prepare('UPDATE arbol_versiones SET activa = 1 WHERE id = :id')->execute(['id' => $id]);
            $this->db->commit();
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, pestana, nombre, activa, creado_en, creado_por FROM arbol_versiones WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Copia las 5 tablas del árbol dentro de una transacción: si algo falla a mitad de camino,
     * no queda una versión a medias.
     */
    public function crear(string $pestana, string $nombre, int $usuarioId): int
    {
        if (!in_array($pestana, self::PESTANAS_VALIDAS, true)) {
            throw new InvalidArgumentException('Pestaña de árbol no válida: ' . $pestana);
        }

        $this->db->beginTransaction();

        try {
            $consulta = $this->db->prepare('INSERT INTO arbol_versiones (pestana, nombre, creado_por) VALUES (:pestana, :nombre, :creado_por)');
            $consulta->execute(['pestana' => $pestana, 'nombre' => $nombre, 'creado_por' => $usuarioId]);
            $versionId = (int) $this->db->lastInsertId();

            $insertarDatos = $this->db->prepare(
                'INSERT INTO arbol_versiones_datos (version_id, tabla, datos) VALUES (:version_id, :tabla, :datos)'
            );

            foreach (self::TABLAS_ARBOL as $tabla) {
                $filas = $this->db->query('SELECT * FROM `' . $tabla . '`')->fetchAll();
                $insertarDatos->execute([
                    'version_id' => $versionId,
                    'tabla' => $tabla,
                    'datos' => json_encode($filas, JSON_UNESCAPED_UNICODE),
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

        $consulta = $this->db->prepare('DELETE FROM arbol_versiones WHERE id = :id');
        $resultado = $consulta->execute(['id' => $id]);

        if ($resultado && !empty($fila['activa'])) {
            // Se borró la que estaba marcada como activa: si queda alguna otra de esta
            // pestaña, la más reciente pasa a ser la nueva activa — para que "Activa" nunca
            // quede vacía mientras Análisis sigue mostrando algo (el fallback de
            // obtenerActiva()) y ambos queden de acuerdo.
            $siguiente = $this->obtenerActiva($fila['pestana']);
            if ($siguiente !== null) {
                $this->marcarActiva((int) $siguiente['id'], $fila['pestana']);
            }
        }

        return $resultado;
    }

    /**
     * Filas de una tabla del árbol dentro de una versión guardada, con el mismo shape que
     * devolvería un `SELECT * FROM <tabla>` real en ese momento — para que FuenteDatosAnalisis
     * pueda usarlas sin que el árbol note la diferencia.
     */
    public function obtenerTablaDeVersion(int $versionId, string $tabla): array
    {
        $consulta = $this->db->prepare('SELECT datos FROM arbol_versiones_datos WHERE version_id = :version_id AND tabla = :tabla');
        $consulta->execute(['version_id' => $versionId, 'tabla' => $tabla]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return [];
        }

        return json_decode($fila['datos'], true) ?? [];
    }
}
