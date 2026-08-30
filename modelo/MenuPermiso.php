<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/Dependencia.php';

class MenuPermiso
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Calcula la lista de menu_key permitidos para un usuario (fila de la tabla usuarios).
     * Devuelve null cuando no hay ninguna restricción configurada (se permite todo).
     * Si existe una plantilla específica para (tipo, rol) del usuario, esa manda; si no,
     * cae a la plantilla general del tipo (rol_id NULL), para no romper lo ya configurado.
     */
    public function calcularPermitidoParaUsuario(array $usuario): ?array
    {
        if ((int) ($usuario['menu_personalizado'] ?? 0) === 1) {
            return $this->obtenerMenuUsuario((int) $usuario['id']);
        }

        if (!empty($usuario['dependencia_id'])) {
            $dependencia = (new Dependencia())->obtenerPorId((int) $usuario['dependencia_id']);

            if ($dependencia !== null && !empty($dependencia['tipo'])) {
                $rolId = !empty($usuario['rol_id']) ? (int) $usuario['rol_id'] : null;

                if ($rolId !== null) {
                    $plantillaPorRol = $this->obtenerPlantillaPorTipo($dependencia['tipo'], $rolId);

                    if (!empty($plantillaPorRol)) {
                        return $plantillaPorRol;
                    }
                }

                $plantillaGeneral = $this->obtenerPlantillaPorTipo($dependencia['tipo'], null);

                if (!empty($plantillaGeneral)) {
                    return $plantillaGeneral;
                }
            }
        }

        return null;
    }

    public function obtenerPlantillaPorTipo(string $tipo, ?int $rolId = null): array
    {
        if ($rolId === null) {
            $consulta = $this->db->prepare('SELECT menu_key FROM tipo_dependencia_menu WHERE tipo = :tipo AND rol_id IS NULL');
            $consulta->execute(['tipo' => $tipo]);
        } else {
            $consulta = $this->db->prepare('SELECT menu_key FROM tipo_dependencia_menu WHERE tipo = :tipo AND rol_id = :rol_id');
            $consulta->execute(['tipo' => $tipo, 'rol_id' => $rolId]);
        }

        return array_column($consulta->fetchAll(), 'menu_key');
    }

    /**
     * Devuelve todas las plantillas agrupadas por tipo, y dentro de cada tipo por clave de rol
     * ('general' para rol_id NULL, o el id del rol como string).
     */
    public function obtenerPlantillasCompletas(): array
    {
        $consulta = $this->db->query('SELECT tipo, rol_id, menu_key FROM tipo_dependencia_menu');

        $mapa = [];
        foreach ($consulta->fetchAll() as $fila) {
            $claveRol = $fila['rol_id'] !== null ? (string) $fila['rol_id'] : 'general';
            $mapa[$fila['tipo']][$claveRol][] = $fila['menu_key'];
        }

        return $mapa;
    }

    public function guardarPlantillaPorTipo(string $tipo, ?int $rolId, array $menuKeys): bool
    {
        $this->db->beginTransaction();

        if ($rolId === null) {
            $eliminar = $this->db->prepare('DELETE FROM tipo_dependencia_menu WHERE tipo = :tipo AND rol_id IS NULL');
            $eliminar->execute(['tipo' => $tipo]);
        } else {
            $eliminar = $this->db->prepare('DELETE FROM tipo_dependencia_menu WHERE tipo = :tipo AND rol_id = :rol_id');
            $eliminar->execute(['tipo' => $tipo, 'rol_id' => $rolId]);
        }

        $insertar = $this->db->prepare('INSERT INTO tipo_dependencia_menu (tipo, rol_id, menu_key) VALUES (:tipo, :rol_id, :menu_key)');
        foreach ($menuKeys as $menuKey) {
            $insertar->execute(['tipo' => $tipo, 'rol_id' => $rolId, 'menu_key' => $menuKey]);
        }

        return $this->db->commit();
    }

    public function obtenerMenuUsuario(int $usuarioId): array
    {
        $consulta = $this->db->prepare('SELECT menu_key FROM usuario_menu WHERE usuario_id = :usuario_id');
        $consulta->execute(['usuario_id' => $usuarioId]);

        return array_column($consulta->fetchAll(), 'menu_key');
    }

    public function guardarMenuUsuario(int $usuarioId, array $menuKeys): bool
    {
        $this->db->beginTransaction();

        $eliminar = $this->db->prepare('DELETE FROM usuario_menu WHERE usuario_id = :usuario_id');
        $eliminar->execute(['usuario_id' => $usuarioId]);

        $insertar = $this->db->prepare('INSERT INTO usuario_menu (usuario_id, menu_key) VALUES (:usuario_id, :menu_key)');
        foreach ($menuKeys as $menuKey) {
            $insertar->execute(['usuario_id' => $usuarioId, 'menu_key' => $menuKey]);
        }

        return $this->db->commit();
    }
}
