<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Permiso "validación flexible de techo" para Gastos — ver plan aprobado
 * el-techo-no-deberia-kind-candle. Resuelve un tri-estado: override individual del usuario
 * (usuarios.techo_flexible: NULL = hereda, 1 = forzado activo, 0 = forzado inactivo), y si no hay
 * override, un default por (tipo de dependencia, rol) en tipo_dependencia_techo_flexible — mismo
 * patrón de dos niveles que MenuPermiso, para este único booleano.
 *
 * IMPORTANTE: este permiso jamás debe consultarse para decidir la validación de un gasto cuya
 * dependencia elegida sea de tipo "Dumi" — esa resolución (GastoControlador::resolverDependenciaRemitente())
 * es siempre la misma, sin importar el resultado de este permiso.
 */
class TechoFlexiblePermiso
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * @param array $usuario Fila de usuarios (necesita 'techo_flexible' y 'rol_id').
     * @param array|null $dependencia Fila de dependencias del usuario (necesita 'tipo'), o null.
     */
    public function estaActivoParaUsuario(array $usuario, ?array $dependencia): bool
    {
        if (array_key_exists('techo_flexible', $usuario) && $usuario['techo_flexible'] !== null) {
            return (int) $usuario['techo_flexible'] === 1;
        }

        if ($dependencia === null || empty($dependencia['tipo'])) {
            return false;
        }

        $rolId = !empty($usuario['rol_id']) ? (int) $usuario['rol_id'] : null;

        return $this->obtenerDefaultPorTipo($dependencia['tipo'], $rolId);
    }

    private function obtenerDefaultPorTipo(string $tipo, ?int $rolId): bool
    {
        if ($rolId !== null) {
            $consulta = $this->db->prepare(
                'SELECT activo FROM tipo_dependencia_techo_flexible WHERE tipo = :tipo AND rol_id = :rol_id'
            );
            $consulta->execute(['tipo' => $tipo, 'rol_id' => $rolId]);
            $fila = $consulta->fetch();

            if ($fila !== false) {
                return (int) $fila['activo'] === 1;
            }
        }

        $consulta = $this->db->prepare(
            'SELECT activo FROM tipo_dependencia_techo_flexible WHERE tipo = :tipo AND rol_id IS NULL'
        );
        $consulta->execute(['tipo' => $tipo]);
        $fila = $consulta->fetch();

        return $fila !== false && (int) $fila['activo'] === 1;
    }

    /**
     * Todos los defaults agrupados por tipo, y dentro de cada tipo por clave de rol ('general'
     * para rol_id NULL, o el id del rol como string) — mismo formato que
     * MenuPermiso::obtenerPlantillasCompletas(), para reutilizar el mismo patrón de JS en el
     * modal de Jerarquías > Mapa.
     */
    public function obtenerDefaultsCompletos(): array
    {
        $consulta = $this->db->query('SELECT tipo, rol_id, activo FROM tipo_dependencia_techo_flexible');

        $mapa = [];
        foreach ($consulta->fetchAll() as $fila) {
            $claveRol = $fila['rol_id'] !== null ? (string) $fila['rol_id'] : 'general';
            $mapa[$fila['tipo']][$claveRol] = (int) $fila['activo'] === 1;
        }

        return $mapa;
    }

    public function guardarDefaultPorTipo(string $tipo, ?int $rolId, bool $activo): bool
    {
        if ($rolId === null) {
            $eliminar = $this->db->prepare('DELETE FROM tipo_dependencia_techo_flexible WHERE tipo = :tipo AND rol_id IS NULL');
            $eliminar->execute(['tipo' => $tipo]);
        } else {
            $eliminar = $this->db->prepare('DELETE FROM tipo_dependencia_techo_flexible WHERE tipo = :tipo AND rol_id = :rol_id');
            $eliminar->execute(['tipo' => $tipo, 'rol_id' => $rolId]);
        }

        $insertar = $this->db->prepare(
            'INSERT INTO tipo_dependencia_techo_flexible (tipo, rol_id, activo) VALUES (:tipo, :rol_id, :activo)'
        );

        return $insertar->execute(['tipo' => $tipo, 'rol_id' => $rolId, 'activo' => $activo ? 1 : 0]);
    }

    /**
     * Override individual: null = quitar override (heredar del tipo/rol), true/false = forzar.
     */
    public function guardarOverrideUsuario(int $usuarioId, ?bool $activo): bool
    {
        $consulta = $this->db->prepare('UPDATE usuarios SET techo_flexible = :valor WHERE id = :id');

        return $consulta->execute([
            'valor' => $activo === null ? null : ($activo ? 1 : 0),
            'id' => $usuarioId,
        ]);
    }
}
