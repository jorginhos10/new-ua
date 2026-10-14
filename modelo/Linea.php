<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class Linea
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, nombre, estado, creado_en FROM lineas ORDER BY codigo');

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query("SELECT id, codigo, nombre FROM lineas WHERE estado = 'activo' ORDER BY codigo");

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, estado, creado_en FROM lineas WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, estado FROM lineas WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM lineas WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO lineas (codigo, nombre) VALUES (:codigo, :nombre)');

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre]);
    }

    public function actualizar(int $id, string $codigo, string $nombre): bool
    {
        $consulta = $this->db->prepare('UPDATE lineas SET codigo = :codigo, nombre = :nombre WHERE id = :id');

        return $consulta->execute(['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE lineas SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM lineas WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Este catálogo no tiene borrado real vía CSV, solo estado activo/inactivo (mismo patrón que
     * Rubro/ContratoComun/LineaInversion): crea las que faltan, actualiza nombre/estado según el
     * archivo, y desactiva (no borra) las que ya no aparecen.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');

            if ($codigo === '' || $nombre === '') {
                continue;
            }

            $codigosCsv[] = $codigo;
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->obtenerPorCodigo($codigo);

            if ($existente === null) {
                $this->crear($codigo, $nombre);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } else {
                $cambio = false;

                if ($existente['nombre'] !== $nombre) {
                    $this->actualizar((int) $existente['id'], $codigo, $nombre);
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
}
