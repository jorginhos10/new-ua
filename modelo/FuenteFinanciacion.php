<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class FuenteFinanciacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** Todas las fuentes, con cuántos proyectos la usan (para decidir si se puede eliminar). */
    public function obtenerTodas(): array
    {
        $consulta = $this->db->query(
            'SELECT f.id, f.nombre, f.estado, f.creado_en, COUNT(n.id) AS cantidad_proyectos
             FROM fuentes_financiacion f
             LEFT JOIN necesidades_academicas n ON n.fuente_financiacion_id = f.id
             GROUP BY f.id, f.nombre, f.estado, f.creado_en
             ORDER BY f.nombre'
        );

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT id, nombre FROM fuentes_financiacion WHERE estado = 'activo' ORDER BY nombre"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, estado FROM fuentes_financiacion WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorNombre(string $nombre): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, estado FROM fuentes_financiacion WHERE nombre = :nombre LIMIT 1');
        $consulta->execute(['nombre' => $nombre]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $consulta = $this->db->prepare(
            'SELECT id FROM fuentes_financiacion WHERE nombre = :nombre AND id <> :excluir LIMIT 1'
        );
        $consulta->execute(['nombre' => $nombre, 'excluir' => $excluirId ?? 0]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO fuentes_financiacion (nombre) VALUES (:nombre)');

        return $consulta->execute(['nombre' => $nombre]);
    }

    /**
     * Renombra la fuente y actualiza el texto guardado en los proyectos que la usan, para que la
     * columna "Fuente" (texto) y el catálogo no se desalineen.
     */
    public function actualizarNombre(int $id, string $nombre): bool
    {
        $this->db->beginTransaction();

        try {
            $this->db->prepare('UPDATE fuentes_financiacion SET nombre = :nombre WHERE id = :id')
                ->execute(['nombre' => $nombre, 'id' => $id]);
            $this->db->prepare('UPDATE necesidades_academicas SET fuente_financiacion = :nombre WHERE fuente_financiacion_id = :id')
                ->execute(['nombre' => $nombre, 'id' => $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return true;
    }

    public function contarProyectos(int $id): int
    {
        $consulta = $this->db->prepare('SELECT COUNT(*) FROM necesidades_academicas WHERE fuente_financiacion_id = :id');
        $consulta->execute(['id' => $id]);

        return (int) $consulta->fetchColumn();
    }

    /** Quita la fuente de las convocatorias que la habilitaban y luego la borra. Los proyectos deben haberse revisado antes. */
    public function eliminar(int $id): bool
    {
        $this->db->beginTransaction();

        try {
            $this->db->prepare('DELETE FROM convocatoria_fuentes WHERE fuente_id = :id')->execute(['id' => $id]);
            $this->db->prepare('DELETE FROM fuentes_financiacion WHERE id = :id')->execute(['id' => $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return true;
    }

    /** Sin borrado de una fuente en uso: se puede desactivar para que deje de ofrecerse. */
    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE fuentes_financiacion SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Mismo patrón que el resto de catálogos de "Listas" (crear/actualizar/desactivar, nunca borra
     * desde CSV) — el renombrado usa actualizarNombre() para no desalinear el texto denormalizado
     * en necesidades_academicas.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $nombresCsv = [];
        $creados = 0;
        $actualizados = 0;

        foreach ($filas as $fila) {
            $nombre = trim($fila['nombre'] ?? '');

            if ($nombre === '') {
                continue;
            }

            $nombresCsv[] = $nombre;
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->obtenerPorNombre($nombre);

            if ($existente === null) {
                $this->crear($nombre);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } elseif ($existente['estado'] !== $estadoDeseado) {
                $this->cambiarEstado((int) $existente['id']);
                $actualizados++;
            }
        }

        $desactivados = 0;

        foreach ($this->obtenerTodas() as $existente) {
            if (in_array($existente['nombre'], $nombresCsv, true)) {
                continue;
            }

            if ($existente['estado'] === 'activo') {
                $this->cambiarEstado((int) $existente['id']);
                $desactivados++;
            }
        }

        return ['creados' => $creados, 'actualizados' => $actualizados, 'desactivados' => $desactivados];
    }
}
