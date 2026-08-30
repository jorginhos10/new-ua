<?php

require_once __DIR__ . '/../config/conexion.php';

class Facultad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, nombre, estado, creado_en FROM facultades ORDER BY nombre');

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query("SELECT id, nombre FROM facultades WHERE estado = 'activo' ORDER BY nombre");

        return $consulta->fetchAll();
    }

    public function existeNombre(string $nombre): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM facultades WHERE nombre = :nombre LIMIT 1');
        $consulta->execute(['nombre' => $nombre]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO facultades (nombre) VALUES (:nombre)');

        return $consulta->execute(['nombre' => $nombre]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE facultades SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Este catálogo no tiene borrado real, solo estado activo/inactivo. Por eso la sincronización
     * desde CSV no elimina filas: crea las que faltan, activa/desactiva según la columna "estado"
     * del archivo, y desactiva (no borra) las que ya no aparecen en el archivo.
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

            $existente = $this->buscarPorNombre($nombre);

            if ($existente === null) {
                $this->crear($nombre);

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado((int) $this->db->lastInsertId());
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

    private function buscarPorNombre(string $nombre): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, estado FROM facultades WHERE nombre = :nombre LIMIT 1');
        $consulta->execute(['nombre' => $nombre]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
