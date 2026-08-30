<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class LineaInversion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, nombre, descripcion, estado, creado_en FROM lineas_inversion ORDER BY codigo');

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, nombre, descripcion FROM lineas_inversion WHERE estado = 'activo' ORDER BY codigo"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, descripcion, estado FROM lineas_inversion WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, descripcion FROM lineas_inversion WHERE codigo = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM lineas_inversion WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre, string $descripcion): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO lineas_inversion (codigo, nombre, descripcion) VALUES (:codigo, :nombre, :descripcion)'
        );

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE lineas_inversion SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function actualizarDatos(int $id, string $nombre, string $descripcion): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE lineas_inversion SET nombre = :nombre, descripcion = :descripcion WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion]);
    }

    /**
     * Este catálogo no tiene borrado real, solo estado activo/inactivo. La sincronización desde CSV
     * no elimina filas: crea las que faltan, actualiza nombre/descripción/estado según el archivo, y
     * desactiva (no borra) las que ya no aparecen en el archivo.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $descripcion = trim($fila['descripcion'] ?? '');

            if ($codigo === '' || $nombre === '') {
                continue;
            }

            $codigosCsv[] = $codigo;
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->buscarPorCodigo($codigo);

            if ($existente === null) {
                $this->crear($codigo, $nombre, $descripcion);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } else {
                $cambio = false;

                if ($existente['nombre'] !== $nombre || $existente['descripcion'] !== $descripcion) {
                    $this->actualizarDatos((int) $existente['id'], $nombre, $descripcion);
                    $cambio = true;
                }

                if ($existente['estado'] !== $estadoDeseado) {
                    $this->cambiarEstado((int) $existente['id']);
                    $cambio = true;
                }

                if ($cambio) {
                    $actualizados++;
                }
            }
        }

        $desactivados = 0;

        foreach ($this->obtenerTodas() as $existente) {
            if (in_array($existente['codigo'], $codigosCsv, true)) {
                continue;
            }

            if ($existente['estado'] === 'activo') {
                $this->cambiarEstado((int) $existente['id']);
                $desactivados++;
            }
        }

        return ['creados' => $creados, 'actualizados' => $actualizados, 'desactivados' => $desactivados];
    }

    private function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, codigo, nombre, descripcion, estado FROM lineas_inversion WHERE codigo = :codigo LIMIT 1'
        );
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
