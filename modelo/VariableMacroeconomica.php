<?php

require_once __DIR__ . '/../config/conexion.php';

class VariableMacroeconomica
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query(
            'SELECT v.*, a.anio
             FROM variables_macroeconomicas v
             JOIN anios_presupuestales a ON a.id = v.anio_presupuestal_id
             ORDER BY a.anio DESC, v.nombre ASC'
        );

        return $consulta->fetchAll();
    }

    public function existeNombreEnAnio(string $nombre, int $anioPresupuestalId, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT id FROM variables_macroeconomicas WHERE nombre = :nombre AND anio_presupuestal_id = :anio_presupuestal_id';
        $parametros = [
            'nombre' => $nombre,
            'anio_presupuestal_id' => $anioPresupuestalId,
        ];

        if ($ignorarId !== null) {
            $sql .= ' AND id != :ignorar_id';
            $parametros['ignorar_id'] = $ignorarId;
        }

        $consulta = $this->db->prepare($sql . ' LIMIT 1');
        $consulta->execute($parametros);

        return $consulta->fetch() !== false;
    }

    public function crear(string $nombre, int $anioPresupuestalId, float $valor): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO variables_macroeconomicas (nombre, anio_presupuestal_id, valor) VALUES (:nombre, :anio_presupuestal_id, :valor)'
        );

        return $consulta->execute([
            'nombre' => $nombre,
            'anio_presupuestal_id' => $anioPresupuestalId,
            'valor' => $valor,
        ]);
    }

    public function obtenerPorNombreYAnio(string $nombre, int $anioPresupuestalId): ?array
    {
        $consulta = $this->db->prepare(
            "SELECT * FROM variables_macroeconomicas
             WHERE nombre = :nombre AND anio_presupuestal_id = :anio_presupuestal_id AND estado = 'activo'
             LIMIT 1"
        );
        $consulta->execute([
            'nombre' => $nombre,
            'anio_presupuestal_id' => $anioPresupuestalId,
        ]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerValoresPorNombre(string $nombre): array
    {
        $consulta = $this->db->prepare(
            "SELECT anio_presupuestal_id, valor FROM variables_macroeconomicas
             WHERE nombre = :nombre AND estado = 'activo'"
        );
        $consulta->execute(['nombre' => $nombre]);

        $valores = [];
        foreach ($consulta->fetchAll() as $fila) {
            $valores[(int) $fila['anio_presupuestal_id']] = (float) $fila['valor'];
        }

        return $valores;
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE variables_macroeconomicas SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM variables_macroeconomicas WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function actualizar(int $id, string $nombre, int $anioPresupuestalId, float $valor): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE variables_macroeconomicas SET nombre = :nombre, anio_presupuestal_id = :anio_presupuestal_id, valor = :valor WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'nombre' => $nombre,
            'anio_presupuestal_id' => $anioPresupuestalId,
            'valor' => $valor,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM variables_macroeconomicas WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }
}
