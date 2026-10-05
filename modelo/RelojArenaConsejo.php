<?php

require_once __DIR__ . '/../config/conexion.php';

/** Fechas del reloj de arena del inicio de Consejo Superior (tabla reloj_arena_consejo). */
class RelojArenaConsejo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtener(): ?array
    {
        $consulta = $this->db->query('SELECT * FROM reloj_arena_consejo WHERE id = 1');
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /** Si nunca se configuró: del 15 de octubre al 20 de diciembre del año en curso. */
    public function valoresPorDefecto(): array
    {
        $anio = (int) date('Y');

        return ['fecha_inicio' => $anio . '-10-15', 'fecha_cierre' => $anio . '-12-20'];
    }

    /** Fechas configuradas o, si no hay, las de por defecto. */
    public function obtenerConDefecto(): array
    {
        return $this->obtener() ?? $this->valoresPorDefecto();
    }

    public function guardar(string $fechaInicio, string $fechaCierre): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO reloj_arena_consejo (id, fecha_inicio, fecha_cierre)
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
