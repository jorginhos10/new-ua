<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Ajustes manuales (solo superadmin) del árbol de Articulación PDI de ?ruta=analisis, por año:
 * - 'valor': valor a mostrar de un proyecto (reemplaza al calculado).
 * - 'absorbe': proyecto al que se le suma/resta la diferencia contra Programación presupuestal
 *   (uno solo por año; sin fila, aplica el de por defecto — ver AnalisisControlador::construirArbolPdi()).
 */
class AnalisisPdiAjuste
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** @return array{valores: array<int, float>, absorbe: ?int} proyecto_id => valor fijado, y el proyecto absorbente. */
    public function obtener(int $anio): array
    {
        $consulta = $this->db->prepare('SELECT tipo, proyecto_id, valor FROM analisis_pdi_ajustes WHERE anio = :anio');
        $consulta->execute(['anio' => $anio]);

        $valores = [];
        $absorbe = null;
        foreach ($consulta->fetchAll() as $fila) {
            if ($fila['tipo'] === 'absorbe') {
                $absorbe = (int) $fila['proyecto_id'];
            } else {
                $valores[(int) $fila['proyecto_id']] = (float) $fila['valor'];
            }
        }

        return ['valores' => $valores, 'absorbe' => $absorbe];
    }

    public function guardarValor(int $anio, int $proyectoId, float $valor, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            "INSERT INTO analisis_pdi_ajustes (anio, tipo, proyecto_id, valor, actualizado_por)
             VALUES (:anio, 'valor', :proyecto_id, :valor, :usuario_id)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), actualizado_por = VALUES(actualizado_por)"
        );

        return $consulta->execute([
            'anio' => $anio,
            'proyecto_id' => $proyectoId,
            'valor' => round($valor, 0),
            'usuario_id' => $usuarioId,
        ]);
    }

    public function quitarValor(int $anio, int $proyectoId): bool
    {
        $consulta = $this->db->prepare("DELETE FROM analisis_pdi_ajustes WHERE anio = :anio AND tipo = 'valor' AND proyecto_id = :proyecto_id");

        return $consulta->execute(['anio' => $anio, 'proyecto_id' => $proyectoId]);
    }

    /** Un solo absorbente por año: reemplaza al anterior. */
    public function guardarAbsorbe(int $anio, int $proyectoId, int $usuarioId): bool
    {
        $this->db->beginTransaction();

        try {
            $this->quitarAbsorbe($anio);
            $this->db->prepare(
                "INSERT INTO analisis_pdi_ajustes (anio, tipo, proyecto_id, valor, actualizado_por)
                 VALUES (:anio, 'absorbe', :proyecto_id, NULL, :usuario_id)"
            )->execute(['anio' => $anio, 'proyecto_id' => $proyectoId, 'usuario_id' => $usuarioId]);
            $this->db->commit();
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }

        return true;
    }

    /** Vuelve al absorbente por defecto. */
    public function quitarAbsorbe(int $anio): bool
    {
        $consulta = $this->db->prepare("DELETE FROM analisis_pdi_ajustes WHERE anio = :anio AND tipo = 'absorbe'");

        return $consulta->execute(['anio' => $anio]);
    }
}
