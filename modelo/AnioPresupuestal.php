<?php

require_once __DIR__ . '/../config/conexion.php';

class AnioPresupuestal
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, anio, presupuesto, estado, creado_en FROM anios_presupuestales ORDER BY anio DESC');

        return $consulta->fetchAll();
    }

    public function obtenerActivos(): array
    {
        $consulta = $this->db->query(
            "SELECT id, anio, presupuesto FROM anios_presupuestales WHERE estado = 'activo' ORDER BY anio DESC"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, anio, presupuesto, estado, creado_en FROM anios_presupuestales WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeAnio(int $anio, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->db->prepare('SELECT id FROM anios_presupuestales WHERE anio = :anio AND id != :id LIMIT 1');
            $consulta->execute(['anio' => $anio, 'id' => $ignorarId]);
        } else {
            $consulta = $this->db->prepare('SELECT id FROM anios_presupuestales WHERE anio = :anio LIMIT 1');
            $consulta->execute(['anio' => $anio]);
        }

        return $consulta->fetch() !== false;
    }

    public function crear(int $anio, float $presupuesto): bool
    {
        $consulta = $this->db->prepare('INSERT INTO anios_presupuestales (anio, presupuesto) VALUES (:anio, :presupuesto)');

        return $consulta->execute(['anio' => $anio, 'presupuesto' => $presupuesto]);
    }

    public function actualizar(int $id, int $anio, float $presupuesto): bool
    {
        $consulta = $this->db->prepare('UPDATE anios_presupuestales SET anio = :anio, presupuesto = :presupuesto WHERE id = :id');

        return $consulta->execute(['id' => $id, 'anio' => $anio, 'presupuesto' => $presupuesto]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE anios_presupuestales SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        try {
            $consulta = $this->db->prepare('DELETE FROM anios_presupuestales WHERE id = :id');

            return $consulta->execute(['id' => $id]);
        } catch (PDOException $excepcion) {
            return false;
        }
    }
}
