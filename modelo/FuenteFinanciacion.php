<?php

require_once __DIR__ . '/../config/conexion.php';

class FuenteFinanciacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query('SELECT id, nombre, estado, creado_en FROM fuentes_financiacion ORDER BY nombre');

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT id, nombre FROM fuentes_financiacion WHERE estado = 'activo' ORDER BY nombre"
        );

        return $consulta->fetchAll();
    }

    public function existeNombre(string $nombre): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM fuentes_financiacion WHERE nombre = :nombre LIMIT 1');
        $consulta->execute(['nombre' => $nombre]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $nombre): bool
    {
        $consulta = $this->db->prepare('INSERT INTO fuentes_financiacion (nombre) VALUES (:nombre)');

        return $consulta->execute(['nombre' => $nombre]);
    }

    /** Sin borrado real: una fuente usada por proyectos viejos no puede desaparecer. */
    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE fuentes_financiacion SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }
}
