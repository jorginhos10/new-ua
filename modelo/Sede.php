<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class Sede
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, nombre, creado_en FROM sedes ORDER BY nombre');

        return $consulta->fetchAll();
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM sedes WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO sedes (codigo, nombre) VALUES (:codigo, :nombre)');

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre]);
    }

    public function actualizarNombre(int $id, string $nombre): bool
    {
        $consulta = $this->db->prepare('UPDATE sedes SET nombre = :nombre WHERE id = :id');

        return $consulta->execute(['id' => $id, 'nombre' => $nombre]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM sedes WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

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
            $existente = $this->buscarPorCodigo($codigo);

            if ($existente === null) {
                $this->crear($codigo, $nombre);
                $creados++;
            } elseif ($existente['nombre'] !== $nombre) {
                $this->actualizarNombre((int) $existente['id'], $nombre);
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

        return ['creados' => $creados, 'actualizados' => $actualizados, 'eliminados' => $eliminados, 'omitidos' => $omitidos];
    }

    private function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre FROM sedes WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
