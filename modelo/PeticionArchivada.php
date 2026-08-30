<?php

require_once __DIR__ . '/../config/conexion.php';

class PeticionArchivada
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerClavesProcesadas(): array
    {
        $consulta = $this->db->query('SELECT origen, origen_id FROM peticiones_archivadas');
        $claves = [];

        foreach ($consulta->fetchAll() as $fila) {
            $claves[$fila['origen'] . ':' . $fila['origen_id']] = true;
        }

        return $claves;
    }

    public function obtenerAccionesPorClave(): array
    {
        $consulta = $this->db->query('SELECT origen, origen_id, accion FROM peticiones_archivadas');
        $acciones = [];

        foreach ($consulta->fetchAll() as $fila) {
            $acciones[$fila['origen'] . ':' . $fila['origen_id']] = $fila['accion'];
        }

        return $acciones;
    }

    public function obtenerPorAccion(string $accion): array
    {
        $consulta = $this->db->prepare('SELECT * FROM peticiones_archivadas WHERE accion = :accion ORDER BY archivado_en DESC');
        $consulta->execute(['accion' => $accion]);

        return $consulta->fetchAll();
    }

    public function archivar(array $datos): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO peticiones_archivadas
                (origen, origen_id, accion, tipo, detalle, cantidad, valor, ruta_ver, ruta_origen, redireccionado_a_dependencia)
             VALUES
                (:origen, :origen_id, :accion, :tipo, :detalle, :cantidad, :valor, :ruta_ver, :ruta_origen, NULL)
             ON DUPLICATE KEY UPDATE
                accion = VALUES(accion),
                tipo = VALUES(tipo),
                detalle = VALUES(detalle),
                cantidad = VALUES(cantidad),
                valor = VALUES(valor),
                ruta_ver = VALUES(ruta_ver),
                ruta_origen = VALUES(ruta_origen),
                redireccionado_a_dependencia = NULL'
        );

        return $consulta->execute([
            'origen' => $datos['origen'],
            'origen_id' => $datos['origen_id'],
            'accion' => $datos['accion'] ?? 'archivada',
            'tipo' => $datos['tipo'],
            'detalle' => $datos['detalle'],
            'cantidad' => $datos['cantidad'],
            'valor' => $datos['valor'],
            'ruta_ver' => $datos['ruta_ver'],
            'ruta_origen' => $datos['ruta_origen'] ?? null,
        ]);
    }

    public function restaurar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM peticiones_archivadas WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function redireccionar(string $tipo, string $dependenciaDestino): int
    {
        $consulta = $this->db->prepare(
            "UPDATE peticiones_archivadas
             SET accion = 'redireccionada', redireccionado_a_dependencia = :destino
             WHERE tipo = :tipo AND accion = 'aprobada'"
        );
        $consulta->execute(['destino' => $dependenciaDestino, 'tipo' => $tipo]);

        return $consulta->rowCount();
    }

    public function obtenerRedireccionadas(): array
    {
        $consulta = $this->db->query("SELECT * FROM peticiones_archivadas WHERE accion = 'redireccionada' ORDER BY archivado_en DESC");

        return $consulta->fetchAll();
    }

    public function rechazarRedireccion(string $origen, int $origenId): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE peticiones_archivadas
             SET accion = 'aprobada', redireccionado_a_dependencia = NULL
             WHERE origen = :origen AND origen_id = :origen_id AND accion = 'redireccionada'"
        );
        $consulta->execute(['origen' => $origen, 'origen_id' => $origenId]);

        return $consulta->rowCount() > 0;
    }

    public function eliminarPorOrigen(string $origen, int $origenId): bool
    {
        $consulta = $this->db->prepare('DELETE FROM peticiones_archivadas WHERE origen = :origen AND origen_id = :origen_id');

        return $consulta->execute(['origen' => $origen, 'origen_id' => $origenId]);
    }
}
