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

        $condicionExtra = $origen === 'gasto_principal' ? " AND (tipo_automatico IS NULL OR tipo_automatico != 'techo_hijo')" : '';
        $consulta = $this->db->prepare(
            'UPDATE `' . self::TABLAS[$origen] . '` SET proyecto_id = :proyecto_id, linea_id = :linea_id, motor_id = :motor_id
             WHERE id = :id' . $condicionExtra
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

    /** Proyecto PDI por id (con linea_id/motor_id) o null. */
    public static function proyecto(int $proyectoId): ?array
    {
        return $proyectoId > 0 ? (new Proyecto())->obtenerPorId($proyectoId) : null;
    }
}
