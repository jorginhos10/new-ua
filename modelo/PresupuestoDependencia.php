<?php

require_once __DIR__ . '/../config/conexion.php';

class PresupuestoDependencia
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerMapaPorAnio(): array
    {
        $consulta = $this->db->query('SELECT anio_presupuestal_id, dependencia_id, minimo, techo FROM presupuesto_dependencia');

        $mapa = [];
        foreach ($consulta->fetchAll() as $fila) {
            $mapa[(int) $fila['anio_presupuestal_id']][(int) $fila['dependencia_id']] = [
                'minimo' => $fila['minimo'],
                'techo' => $fila['techo'],
            ];
        }

        return $mapa;
    }

    public function obtenerPorAnio(int $anioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT dependencia_id, minimo, techo, techo_bloqueado FROM presupuesto_dependencia WHERE anio_presupuestal_id = :anio_id'
        );
        $consulta->execute(['anio_id' => $anioId]);

        $mapa = [];
        foreach ($consulta->fetchAll() as $fila) {
            $mapa[(int) $fila['dependencia_id']] = [
                'minimo' => $fila['minimo'],
                'techo' => $fila['techo'],
                'bloqueado' => (int) $fila['techo_bloqueado'] === 1,
            ];
        }

        return $mapa;
    }

    public function guardar(int $anioId, int $dependenciaId, ?float $minimo, ?float $techo): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO presupuesto_dependencia (anio_presupuestal_id, dependencia_id, minimo, techo)
             VALUES (:anio_id, :dependencia_id, :minimo, :techo)
             ON DUPLICATE KEY UPDATE minimo = VALUES(minimo), techo = VALUES(techo)'
        );

        return $consulta->execute([
            'anio_id' => $anioId,
            'dependencia_id' => $dependenciaId,
            'minimo' => $minimo,
            'techo' => $techo,
        ]);
    }

    public function alternarBloqueo(int $anioId, int $dependenciaId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO presupuesto_dependencia (anio_presupuestal_id, dependencia_id, techo_bloqueado)
             VALUES (:anio_id, :dependencia_id, 1)
             ON DUPLICATE KEY UPDATE techo_bloqueado = NOT techo_bloqueado'
        );

        return $consulta->execute([
            'anio_id' => $anioId,
            'dependencia_id' => $dependenciaId,
        ]);
    }

    public function obtenerBloqueo(int $anioId, int $dependenciaId): bool
    {
        $consulta = $this->db->prepare(
            'SELECT techo_bloqueado FROM presupuesto_dependencia WHERE anio_presupuestal_id = :anio_id AND dependencia_id = :dependencia_id'
        );
        $consulta->execute(['anio_id' => $anioId, 'dependencia_id' => $dependenciaId]);
        $valor = $consulta->fetchColumn();

        return $valor !== false && (int) $valor === 1;
    }
}
