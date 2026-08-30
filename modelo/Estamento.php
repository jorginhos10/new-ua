<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class Estamento
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, nombre, creado_en FROM estamentos ORDER BY creado_en DESC');

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, creado_en FROM estamentos WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeEstamento(string $nombre, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->db->prepare('SELECT id FROM estamentos WHERE nombre = :nombre AND id != :id LIMIT 1');
            $consulta->execute(['nombre' => $nombre, 'id' => $ignorarId]);
        } else {
            $consulta = $this->db->prepare('SELECT id FROM estamentos WHERE nombre = :nombre LIMIT 1');
            $consulta->execute(['nombre' => $nombre]);
        }

        return $consulta->fetch() !== false;
    }

    public function crear(string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO estamentos (nombre) VALUES (:nombre)');

        return $consulta->execute(['nombre' => $nombre]);
    }

    public function actualizar(int $id, string $nombre): bool
    {
        $consulta = $this->db->prepare('UPDATE estamentos SET nombre = :nombre WHERE id = :id');

        return $consulta->execute(['id' => $id, 'nombre' => $nombre]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM estamentos WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Sincroniza el catálogo con el contenido de un CSV importado: crea los que faltan, y borra
     * los que ya no aparecen en el archivo — salvo que sigan en uso en otra parte del sistema
     * (ej. usuarios, necesidades), en cuyo caso se dejan tal cual para no romper esa referencia.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $nombresCsv = [];
        $creados = 0;

        foreach ($filas as $fila) {
            $nombre = trim($fila['nombre'] ?? '');

            if ($nombre === '') {
                continue;
            }

            $nombresCsv[] = $nombre;

            if (!$this->existeEstamento($nombre)) {
                $this->crear($nombre);
                $creados++;
            }
        }

        $eliminados = 0;
        $omitidos = 0;

        foreach ($this->obtenerTodos() as $existente) {
            if (in_array($existente['nombre'], $nombresCsv, true)) {
                continue;
            }

            $id = (int) $existente['id'];

            if ($this->estaEnUso($id)) {
                $omitidos++;
                continue;
            }

            try {
                $this->eliminar($id);
                $eliminados++;
            } catch (PDOException $excepcion) {
                $omitidos++;
            }
        }

        return ['creados' => $creados, 'eliminados' => $eliminados, 'omitidos' => $omitidos];
    }

    private function estaEnUso(int $id): bool
    {
        return CsvConfiguracion::tieneReferencias($this->db, 'estamentos', $id);
    }
}
