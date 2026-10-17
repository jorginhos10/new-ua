<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Fechas y etiqueta del reloj de arena del inicio de Consejo Superior (tabla reloj_arena_consejo).
 * La etiqueta es el texto que va debajo del anillo (por defecto "días") y se edita en
 * Configuraciones > Reloj de arena.
 */
class RelojArenaConsejo
{
    public const ETIQUETA_POR_DEFECTO = 'días';

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtener(): ?array
    {
        $consulta = $this->db->query('SELECT * FROM reloj_arena_consejo WHERE id = 1');
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        // Mientras no exista la columna `etiqueta` (o quede vacía), vale la de por defecto.
        $fila['etiqueta'] = trim((string) ($fila['etiqueta'] ?? '')) !== '' ? $fila['etiqueta'] : self::ETIQUETA_POR_DEFECTO;

        return $fila;
    }

    /** Si nunca se configuró: del 15 de octubre al 20 de diciembre del año en curso, etiqueta "días". */
    public function valoresPorDefecto(): array
    {
        $anio = (int) date('Y');

        return ['fecha_inicio' => $anio . '-10-15', 'fecha_cierre' => $anio . '-12-20', 'etiqueta' => self::ETIQUETA_POR_DEFECTO];
    }

    /** Fechas configuradas o, si no hay, las de por defecto. */
    public function obtenerConDefecto(): array
    {
        return $this->obtener() ?? $this->valoresPorDefecto();
    }

    public function guardar(string $fechaInicio, string $fechaCierre, string $etiqueta = self::ETIQUETA_POR_DEFECTO): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO reloj_arena_consejo (id, fecha_inicio, fecha_cierre, etiqueta)
             VALUES (1, :fecha_inicio, :fecha_cierre, :etiqueta)
             ON DUPLICATE KEY UPDATE fecha_inicio = :fecha_inicio2, fecha_cierre = :fecha_cierre2, etiqueta = :etiqueta2'
        );

        return $consulta->execute([
            'fecha_inicio' => $fechaInicio,
            'fecha_cierre' => $fechaCierre,
            'etiqueta' => $etiqueta,
            'fecha_inicio2' => $fechaInicio,
            'fecha_cierre2' => $fechaCierre,
            'etiqueta2' => $etiqueta,
        ]);
    }
}
