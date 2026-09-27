<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/VariableMacroeconomica.php';

class SolicitudMonitor
{
    private PDO $db;

    private const NOMBRE_VARIABLE_SMMLV = 'SMMLV';

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM solicitudes_monitores WHERE anio_presupuestal_id = :anio_presupuestal_id ORDER BY dependencia ASC'
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_map([$this, 'aplicarRespaldoValor'], $consulta->fetchAll());
    }

    public function obtenerEnviadasPorAnio(int $anioPresupuestalId): array
    {
        $consulta = $this->db->prepare(
            "SELECT * FROM solicitudes_monitores WHERE anio_presupuestal_id = :anio_presupuestal_id AND estado = 'enviada' ORDER BY dependencia ASC"
        );
        $consulta->execute(['anio_presupuestal_id' => $anioPresupuestalId]);

        return array_map([$this, 'aplicarRespaldoValor'], $consulta->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM solicitudes_monitores WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $this->aplicarRespaldoValor($fila) : null;
    }

    public function crear(array $datos): bool
    {
        $valor = $this->calcularValor((int) $datos['anio_presupuestal_id'], (int) $datos['monitores_semestre1'] + (int) $datos['monitores_semestre2']);

        $consulta = $this->db->prepare(
            'INSERT INTO solicitudes_monitores
                (anio_presupuestal_id, dependencia, rol_destinatario_id, usuario_id, tipo, monitores_semestre1, monitores_semestre2, valor)
             VALUES
                (:anio_presupuestal_id, :dependencia, :rol_destinatario_id, :usuario_id, :tipo, :monitores_semestre1, :monitores_semestre2, :valor)'
        );

        return $consulta->execute([
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia' => $datos['dependencia'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'usuario_id' => $datos['usuario_id'] ?? null,
            'tipo' => $datos['tipo'],
            'monitores_semestre1' => $datos['monitores_semestre1'],
            'monitores_semestre2' => $datos['monitores_semestre2'],
            'valor' => $valor,
        ]);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $valor = $this->calcularValor((int) $datos['anio_presupuestal_id'], (int) $datos['monitores_semestre1'] + (int) $datos['monitores_semestre2']);

        $consulta = $this->db->prepare(
            'UPDATE solicitudes_monitores SET
                anio_presupuestal_id = :anio_presupuestal_id,
                dependencia = :dependencia,
                rol_destinatario_id = :rol_destinatario_id,
                tipo = :tipo,
                monitores_semestre1 = :monitores_semestre1,
                monitores_semestre2 = :monitores_semestre2,
                valor = :valor
             WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'anio_presupuestal_id' => $datos['anio_presupuestal_id'],
            'dependencia' => $datos['dependencia'],
            'rol_destinatario_id' => $datos['rol_destinatario_id'],
            'tipo' => $datos['tipo'],
            'monitores_semestre1' => $datos['monitores_semestre1'],
            'monitores_semestre2' => $datos['monitores_semestre2'],
            'valor' => $valor,
        ]);
    }

    /**
     * numero_de_monitores * 2 * SMMLV vigente del año de la solicitud (variables_macroeconomicas,
     * nombre='SMMLV', ver VariableMacroeconomica::obtenerPorNombreYAnio()). Si ese año no tiene un
     * SMMLV activo configurado, devuelve 0.0 en vez de fallar — el valor puede corregirse después
     * si se agrega la variable, sin bloquear el guardado de la solicitud.
     */
    private function calcularValor(int $anioPresupuestalId, int $totalMonitores): float
    {
        $smmlv = (new VariableMacroeconomica())->obtenerPorNombreYAnio(self::NOMBRE_VARIABLE_SMMLV, $anioPresupuestalId);
        $valorSmmlv = $smmlv !== null ? (float) $smmlv['valor'] : 0.0;

        return $totalMonitores * 2 * $valorSmmlv;
    }

    /**
     * Función de respaldo: las solicitudes creadas antes de agregar la columna `valor` (o
     * cualquier fila que por algún motivo haya quedado en NULL) la recalculan al vuelo en vez de
     * mostrar un valor vacío — crear()/actualizar() ya la guardan calculada, así que esto solo
     * cubre datos viejos o casos borde, no el camino normal.
     */
    private function aplicarRespaldoValor(array $fila): array
    {
        if ($fila['valor'] === null || $fila['valor'] === '') {
            $totalMonitores = (int) $fila['monitores_semestre1'] + (int) $fila['monitores_semestre2'];
            $fila['valor'] = $this->calcularValor((int) $fila['anio_presupuestal_id'], $totalMonitores);
        } else {
            $fila['valor'] = (float) $fila['valor'];
        }

        return $fila;
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM solicitudes_monitores WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function enviar(int $id, string $dependenciaDestino, ?int $usuarioDestinatarioId = null, ?int $rolDestinatarioId = null): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE solicitudes_monitores SET estado = 'enviada', enviada_a = :enviada_a, usuario_destinatario_id = :usuario_destinatario_id, rol_destinatario_id = :rol_destinatario_id WHERE id = :id"
        );

        return $consulta->execute([
            'id' => $id,
            'enviada_a' => $dependenciaDestino,
            'usuario_destinatario_id' => $usuarioDestinatarioId,
            'rol_destinatario_id' => $rolDestinatarioId,
        ]);
    }

    /**
     * Devuelve una solicitud ya enviada a borrador (acción "Devolver a borrador" en Peticiones >
     * Pendientes) — limpia el destinatario para que quede como recién creada, editable de nuevo
     * por su dueño.
     */
    public function devolverABorrador(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE solicitudes_monitores SET estado = 'borrador', rol_destinatario_id = NULL,
                usuario_destinatario_id = NULL, enviada_a = NULL
             WHERE id = :id AND estado = 'enviada'"
        );

        return $consulta->execute(['id' => $id]);
    }
}
