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
        $consulta = $this->db->query('SELECT id, codigo, nit, nombre, creado_en FROM sedes ORDER BY nombre');

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nit, nombre FROM sedes WHERE id = :id');
        $consulta->execute(['id' => $id]);
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

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM sedes WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Importa desde CSV (codigo, nombre, nit). Una fila sin NIT válido no se crea ni actualiza y se
     * cuenta en "invalidas", para que nunca quede una sede sin NIT.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;
        $invalidas = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $nit = trim($fila['nit'] ?? '');

            if ($codigo === '' || $nombre === '') {
                continue;
            }

            if (!preg_match(self::PATRON_NIT, $nit)) {
                $invalidas++;
                continue;
            }

            $codigosCsv[] = $codigo;
            $existente = $this->buscarPorCodigo($codigo);

            if ($existente === null) {
                $this->crear($codigo, $nombre, $nit);
                $creados++;
            } elseif ($existente['nombre'] !== $nombre || $existente['nit'] !== $nit) {
                $this->actualizar((int) $existente['id'], $codigo, $nombre, $nit);
                $actualizados++;
            }
        }

        $eliminados = 0;
        $omitidos = 0;

        foreach ($this->obtenerTodas() as $existente) {
            if (in_array($existente['codigo'], $codigosCsv, true)) {
                continue;
            }

            $id = (int) $existente['id'];

            if (CsvConfiguracion::tieneReferencias($this->db, 'sedes', $id)) {
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

        return ['creados' => $creados, 'actualizados' => $actualizados, 'eliminados' => $eliminados, 'omitidos' => $omitidos, 'invalidas' => $invalidas];
    }

    private function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nit, nombre FROM sedes WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
