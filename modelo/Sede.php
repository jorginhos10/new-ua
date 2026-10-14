<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class Sede
{
    /** El NIT de sede son dos dígitos que empiezan por 0 (00 a 09). */
    public const PATRON_NIT = '/^0[0-9]$/';

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, nit, nombre, estado, creado_en FROM sedes ORDER BY nombre');

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nit, nombre, estado FROM sedes WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nit, nombre, estado FROM sedes WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeCodigo(string $codigo, ?int $excluirId = null): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM sedes WHERE codigo = :codigo AND id <> :excluir LIMIT 1');
        $consulta->execute(['codigo' => $codigo, 'excluir' => $excluirId ?? 0]);

        return $consulta->fetch() !== false;
    }

    public function existeNit(string $nit, ?int $excluirId = null): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM sedes WHERE nit = :nit AND id <> :excluir LIMIT 1');
        $consulta->execute(['nit' => $nit, 'excluir' => $excluirId ?? 0]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre, string $nit): bool
    {
        $consulta = $this->db->prepare('INSERT INTO sedes (codigo, nombre, nit) VALUES (:codigo, :nombre, :nit)');

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre, 'nit' => $nit]);
    }

    public function actualizar(int $id, string $codigo, string $nombre, string $nit): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE sedes SET codigo = :codigo, nombre = :nombre, nit = :nit WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre, 'nit' => $nit]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE sedes SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM sedes WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Mismo patrón que el resto de catálogos de "Listas" (crear/actualizar/desactivar, nunca borra
     * desde CSV) — una fila sin NIT válido no se crea ni actualiza y cuenta como "ignorados", para
     * que nunca quede una sede sin NIT.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;
        $ignorados = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $nit = trim($fila['nit'] ?? '');

            if ($codigo === '' || $nombre === '') {
                continue;
            }

            if (!preg_match(self::PATRON_NIT, $nit)) {
                $ignorados++;
                continue;
            }

            $codigosCsv[] = $codigo;
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->obtenerPorCodigo($codigo);

            if ($existente === null) {
                $this->crear($codigo, $nombre, $nit);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } else {
                $cambio = false;

                if ($existente['nombre'] !== $nombre || $existente['nit'] !== $nit) {
                    $this->actualizar((int) $existente['id'], $codigo, $nombre, $nit);
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

        return ['creados' => $creados, 'actualizados' => $actualizados, 'desactivados' => $desactivados, 'ignorados' => $ignorados];
    }
}
