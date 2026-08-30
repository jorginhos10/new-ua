<?php

require_once __DIR__ . '/../config/conexion.php';

class RelojArenaConfiguracion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtener(): ?array
    {
        $consulta = $this->db->query('SELECT * FROM reloj_arena_configuracion WHERE id = 1');
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function guardar(string $fechaInicio, string $fechaCierre): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO reloj_arena_configuracion (id, fecha_inicio, fecha_cierre)
             VALUES (1, :fecha_inicio, :fecha_cierre)
             ON DUPLICATE KEY UPDATE fecha_inicio = :fecha_inicio2, fecha_cierre = :fecha_cierre2'
        );

        return $consulta->execute([
            'fecha_inicio' => $fechaInicio,
            'fecha_cierre' => $fechaCierre,
            'fecha_inicio2' => $fechaInicio,
            'fecha_cierre2' => $fechaCierre,
        ]);
    }
}
