<?php

require_once __DIR__ . '/../config/conexion.php';

class TipoDependenciaRol
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTiposDisponibles(): array
    {
        $consulta = $this->db->query(
            "SELECT DISTINCT tipo FROM dependencias WHERE tipo IS NOT NULL AND tipo != '' ORDER BY tipo"
        );

        return array_column($consulta->fetchAll(), 'tipo');
    }

    public function obtenerRolesPorTipo(string $tipo): array
    {
        $consulta = $this->db->prepare('SELECT rol_id FROM tipo_dependencia_roles WHERE tipo = :tipo');
        $consulta->execute(['tipo' => $tipo]);

        return array_map('intval', array_column($consulta->fetchAll(), 'rol_id'));
    }

    public function obtenerMapaCompleto(): array
    {
        $consulta = $this->db->query('SELECT tipo, rol_id FROM tipo_dependencia_roles');

        $mapa = [];
        foreach ($consulta->fetchAll() as $fila) {
            $mapa[$fila['tipo']][] = (int) $fila['rol_id'];
        }

        return $mapa;
    }

    public function guardarAsociaciones(string $tipo, array $rolIds): bool
    {
        $this->db->beginTransaction();

        $eliminar = $this->db->prepare('DELETE FROM tipo_dependencia_roles WHERE tipo = :tipo');
        $eliminar->execute(['tipo' => $tipo]);

        $insertar = $this->db->prepare('INSERT INTO tipo_dependencia_roles (tipo, rol_id) VALUES (:tipo, :rol_id)');
        foreach ($rolIds as $rolId) {
            $insertar->execute(['tipo' => $tipo, 'rol_id' => $rolId]);
        }

        return $this->db->commit();
    }
}
