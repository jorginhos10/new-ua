<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Permiso delegado por el superadmin raíz para editar/borrar filas automáticas (Excedentes,
 * Contribución a posgrado) de un módulo — sin este permiso (o sin ser superadmin), esas filas
 * quedan protegidas por el modelo de Gasto correspondiente (ver eliminarForzado()/actualizarForzado()).
 */
class AutogestionAutomaticoPermiso
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function tienePermiso(string $modulo, string $tipo, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'SELECT 1 FROM autogestion_automatico_permisos WHERE modulo = :modulo AND tipo = :tipo AND usuario_id = :usuario_id LIMIT 1'
        );
        $consulta->execute(['modulo' => $modulo, 'tipo' => $tipo, 'usuario_id' => $usuarioId]);

        return $consulta->fetch() !== false;
    }

    public function listar(string $modulo): array
    {
        $consulta = $this->db->prepare(
            'SELECT p.id, p.tipo, p.usuario_id, u.nombre AS usuario_nombre, u.correo AS usuario_correo, r.nombre AS usuario_rol
             FROM autogestion_automatico_permisos p
             JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN roles r ON r.id = u.rol_id
             WHERE p.modulo = :modulo
             ORDER BY p.tipo, u.nombre'
        );
        $consulta->execute(['modulo' => $modulo]);

        return $consulta->fetchAll();
    }

    public function otorgar(string $modulo, string $tipo, int $usuarioId, int $otorgadoPor): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO autogestion_automatico_permisos (modulo, tipo, usuario_id, otorgado_por)
             VALUES (:modulo, :tipo, :usuario_id, :otorgado_por)
             ON DUPLICATE KEY UPDATE otorgado_por = :otorgado_por2'
        );

        return $consulta->execute([
            'modulo' => $modulo,
            'tipo' => $tipo,
            'usuario_id' => $usuarioId,
            'otorgado_por' => $otorgadoPor,
            'otorgado_por2' => $otorgadoPor,
        ]);
    }

    public function revocar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM autogestion_automatico_permisos WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }
}
