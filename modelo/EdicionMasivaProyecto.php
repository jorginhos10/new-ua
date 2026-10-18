<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/Proyecto.php';
require_once __DIR__ . '/PeticionHistorial.php';

/**
 * Edición masiva del Proyecto PDI desde la tabla real (publico/js/tabla-real/tabla.js): el
 * administrador selecciona varias filas, pulsa Editar y en el modal elige un Proyecto PDI; aquí se
 * sobrescribe SOLO ese campo (con la línea y el motor que se derivan de él, igual que en los
 * formularios) en las filas seleccionadas. Lo usan Análisis de distribución
 * (AnalisisControlador::procesarEdicionMasivaProyecto()) y el "Ver" de Peticiones
 * (PeticionesControlador::procesarEdicionMasivaProyecto()); cada llamador decide los permisos
 * fila por fila. Solo los orígenes cuya tabla tiene proyecto_id/linea_id/motor_id.
 *
 * Antes de cada edición masiva, aplicar() guarda el estado previo de las filas
 * (edicion_masiva_proyecto_respaldos, se conservan los MAX_RESPALDOS más recientes por origen) y el
 * superadmin puede revertirla con restaurar().
 */
class EdicionMasivaProyecto
{
    /** origen (clave de la tabla real) => tabla con proyecto_id, linea_id y motor_id. */
    private const TABLAS = [
        'gasto_principal' => 'gastos',
        'gasto_extension' => 'gastos_extension',
        'gasto_postgrado' => 'gastos_postgrado',
        'gasto_unisalud' => 'gastos_unisalud',
        'ops' => 'solicitudes_ops',
        'gasto_fijo' => 'gastos_fijos',
    ];

    /** Cuántos respaldos se conservan por origen (los más recientes). */
    public const MAX_RESPALDOS = 2;

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public static function admite(string $origen): bool
    {
        return isset(self::TABLAS[$origen]);
    }

    /** Fila mínima para decidir permisos (id, usuario_id); null si no existe o no se admite. */
    public function obtenerRegistro(string $origen, int $id): ?array
    {
        if (!self::admite($origen)) {
            return null;
        }

        $consulta = $this->db->prepare('SELECT id, usuario_id FROM `' . self::TABLAS[$origen] . '` WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Sobrescribe proyecto_id (y su línea/motor) en una fila. En `gastos` nunca toca los espejos de
     * techo (tipo_automatico = 'techo_hijo'), que no son gastos reales. Deja traza en el historial
     * de Peticiones (salvo gastos fijos, que no pasan por Peticiones). Devuelve si cambió la fila.
     */
    public function actualizar(string $origen, int $id, array $proyecto): bool
    {
        if (!self::admite($origen)) {
            return false;
        }

        $consulta = $this->db->prepare(
            'UPDATE `' . self::TABLAS[$origen] . '` SET proyecto_id = :proyecto_id, linea_id = :linea_id, motor_id = :motor_id
             WHERE id = :id' . $this->condicionExtra($origen)
        );
        $consulta->execute([
            'proyecto_id' => (int) $proyecto['id'],
            'linea_id' => (int) $proyecto['linea_id'],
            'motor_id' => (int) $proyecto['motor_id'],
            'id' => $id,
        ]);

        if ($consulta->rowCount() > 0 && $origen !== 'gasto_fijo') {
            (new PeticionHistorial())->registrar(
                $origen,
                $id,
                'editada',
                'Proyecto PDI cambiado a ' . $proyecto['codigo'] . ' - ' . $proyecto['nombre'] . ' (edición masiva)'
            );
        }

        return $consulta->rowCount() > 0;
    }

    /** Condición extra de `actualizar()`: en `gastos` nunca se tocan los espejos de techo. */
    private function condicionExtra(string $origen): string
    {
        return $origen === 'gasto_principal' ? " AND (tipo_automatico IS NULL OR tipo_automatico != 'techo_hijo')" : '';
    }

    /**
     * Aplica el Proyecto PDI a las filas $items ([[origenFila, idFila], ...], ya autorizadas por el
     * llamador) guardando ANTES un respaldo de su estado previo. Si el respaldo no se puede guardar,
     * no se modifica nada. $origen es el módulo desde el que se edita (los gastos fijos de Gasto
     * principal van dentro de su respaldo, cada uno con su propio origen_fila).
     *
     * @return array{actualizados: int, omitidos: int, error: string}
     */
    public function aplicar(string $origen, array $items, array $proyecto, int $usuarioId): array
    {
        $filasAntes = [];
        $omitidos = 0;

        foreach ($items as [$origenFila, $idFila]) {
            if (!self::admite($origenFila)) {
                $omitidos++;
                continue;
            }

            $consulta = $this->db->prepare(
                'SELECT id, proyecto_id, linea_id, motor_id FROM `' . self::TABLAS[$origenFila] . '` WHERE id = :id' . $this->condicionExtra($origenFila)
            );
            $consulta->execute(['id' => $idFila]);
            $fila = $consulta->fetch();

            // Sin fila, o ya con ese Proyecto PDI: no hay nada que cambiar ni que respaldar.
            if ($fila === false || (int) $fila['proyecto_id'] === (int) $proyecto['id']) {
                $omitidos++;
                continue;
            }

            $filasAntes[] = [
                'origen_fila' => $origenFila,
                'id' => (int) $fila['id'],
                'proyecto_id' => $fila['proyecto_id'] !== null ? (int) $fila['proyecto_id'] : null,
                'linea_id' => $fila['linea_id'] !== null ? (int) $fila['linea_id'] : null,
                'motor_id' => $fila['motor_id'] !== null ? (int) $fila['motor_id'] : null,
            ];
        }

        if (empty($filasAntes)) {
            return ['actualizados' => 0, 'omitidos' => $omitidos, 'error' => ''];
        }

        try {
            $this->guardarRespaldo($origen, $proyecto, $filasAntes, $usuarioId);
        } catch (Throwable $excepcion) {
            return ['actualizados' => 0, 'omitidos' => $omitidos + count($filasAntes), 'error' => 'No se pudo guardar el respaldo de la edición masiva; no se cambió nada.'];
        }

        $actualizados = 0;
        foreach ($filasAntes as $filaAntes) {
            if ($this->actualizar($filaAntes['origen_fila'], $filaAntes['id'], $proyecto)) {
                $actualizados++;
            } else {
                $omitidos++;
            }
        }

        return ['actualizados' => $actualizados, 'omitidos' => $omitidos, 'error' => ''];
    }

    /** Inserta el respaldo y poda los que excedan MAX_RESPALDOS para ese origen. */
    private function guardarRespaldo(string $origen, array $proyecto, array $filasAntes, int $usuarioId): void
    {
        $this->db->prepare(
            'INSERT INTO edicion_masiva_proyecto_respaldos (origen, proyecto_nuevo_id, proyecto_nuevo_texto, total_filas, filas, creado_por)
             VALUES (:origen, :proyecto_nuevo_id, :proyecto_nuevo_texto, :total_filas, :filas, :creado_por)'
        )->execute([
            'origen' => $origen,
            'proyecto_nuevo_id' => (int) $proyecto['id'],
            'proyecto_nuevo_texto' => $proyecto['codigo'] . ' - ' . $proyecto['nombre'],
            'total_filas' => count($filasAntes),
            'filas' => json_encode($filasAntes, JSON_UNESCAPED_UNICODE),
            'creado_por' => $usuarioId,
        ]);

        $sobrantes = $this->db->prepare(
            'SELECT id FROM edicion_masiva_proyecto_respaldos WHERE origen = :origen ORDER BY id DESC LIMIT 100 OFFSET ' . self::MAX_RESPALDOS
        );
        $sobrantes->execute(['origen' => $origen]);
        $idsSobrantes = $sobrantes->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($idsSobrantes)) {
            $marcadores = implode(',', array_fill(0, count($idsSobrantes), '?'));
            $this->db->prepare("DELETE FROM edicion_masiva_proyecto_respaldos WHERE id IN ($marcadores)")->execute(array_values($idsSobrantes));
        }
    }

    /** Respaldos conservados de un origen, el más reciente primero (sin el detalle de filas). */
    public function obtenerRespaldos(string $origen): array
    {
        $consulta = $this->db->prepare(
            'SELECT r.id, r.proyecto_nuevo_texto, r.total_filas, r.creado_en, r.restaurado_en, u.nombre AS creado_por_nombre
             FROM edicion_masiva_proyecto_respaldos r
             LEFT JOIN usuarios u ON u.id = r.creado_por
             WHERE r.origen = :origen
             ORDER BY r.id DESC
             LIMIT ' . self::MAX_RESPALDOS
        );
        $consulta->execute(['origen' => $origen]);

        return $consulta->fetchAll();
    }

    /**
     * Revierte un respaldo: cada fila vuelve a su Proyecto PDI/línea/motor anteriores SOLO si todavía
     * tiene el Proyecto PDI que puso esa edición masiva (si alguien la cambió después, o ya no
     * existe, se omite). No genera otro respaldo y deja el respaldo marcado como restaurado.
     *
     * @return array{restauradas: int, omitidas: int, error: string}
     */
    public function restaurar(int $respaldoId, string $origen, int $usuarioId): array
    {
        $consulta = $this->db->prepare('SELECT * FROM edicion_masiva_proyecto_respaldos WHERE id = :id AND origen = :origen');
        $consulta->execute(['id' => $respaldoId, 'origen' => $origen]);
        $respaldo = $consulta->fetch();

        if ($respaldo === false) {
            return ['restauradas' => 0, 'omitidas' => 0, 'error' => 'El respaldo ya no existe.'];
        }
        if ($respaldo['restaurado_en'] !== null) {
            return ['restauradas' => 0, 'omitidas' => 0, 'error' => 'Esa edición masiva ya fue restaurada.'];
        }

        $filas = json_decode((string) $respaldo['filas'], true) ?? [];
        $restauradas = 0;
        $omitidas = 0;

        foreach ($filas as $fila) {
            $origenFila = (string) ($fila['origen_fila'] ?? '');
            if (!self::admite($origenFila)) {
                $omitidas++;
                continue;
            }

            $actualizar = $this->db->prepare(
                'UPDATE `' . self::TABLAS[$origenFila] . '` SET proyecto_id = :proyecto_id, linea_id = :linea_id, motor_id = :motor_id
                 WHERE id = :id AND proyecto_id = :proyecto_nuevo_id' . $this->condicionExtra($origenFila)
            );
            $actualizar->execute([
                'proyecto_id' => $fila['proyecto_id'],
                'linea_id' => $fila['linea_id'],
                'motor_id' => $fila['motor_id'],
                'id' => (int) $fila['id'],
                'proyecto_nuevo_id' => (int) $respaldo['proyecto_nuevo_id'],
            ]);

            if ($actualizar->rowCount() > 0) {
                $restauradas++;
                if ($origenFila !== 'gasto_fijo') {
                    (new PeticionHistorial())->registrar(
                        $origenFila,
                        (int) $fila['id'],
                        'editada',
                        'Proyecto PDI restaurado al anterior (se revirtió la edición masiva a ' . $respaldo['proyecto_nuevo_texto'] . ')'
                    );
                }
            } else {
                $omitidas++;
            }
        }

        $this->db->prepare('UPDATE edicion_masiva_proyecto_respaldos SET restaurado_en = NOW(), restaurado_por = :usuario WHERE id = :id')
            ->execute(['usuario' => $usuarioId, 'id' => $respaldoId]);

        return ['restauradas' => $restauradas, 'omitidas' => $omitidas, 'error' => ''];
    }

    /** Proyecto PDI por id (con linea_id/motor_id) o null. */
    public static function proyecto(int $proyectoId): ?array
    {
        return $proyectoId > 0 ? (new Proyecto())->obtenerPorId($proyectoId) : null;
    }
}
