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

    public function actualizarValor(string $origen, int $origenId, float $valor): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE peticiones_archivadas SET valor = :valor WHERE origen = :origen AND origen_id = :origen_id'
        );

        return $consulta->execute([
            'valor' => $valor,
            'origen' => $origen,
            'origen_id' => $origenId,
        ]);
    }

    /**
     * Re-sincroniza el snapshot (valor y detalle/dependencia) después de que el ítem real se
     * edita desde su módulo de origen. No-op si el ítem no está archivado/consolidado (el WHERE
     * simplemente no encuentra filas).
     */
    public function sincronizarDesdeOrigen(string $origen, int $origenId, ?float $valor, ?string $detalle): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE peticiones_archivadas SET valor = :valor, detalle = :detalle
             WHERE origen = :origen AND origen_id = :origen_id'
        );

        return $consulta->execute([
            'valor' => $valor,
            'detalle' => $detalle,
            'origen' => $origen,
            'origen_id' => $origenId,
        ]);
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM peticiones_archivadas WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function restaurar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM peticiones_archivadas WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Redirecciona ítems puntuales (por origen+origen_id), en vez de un tipo completo — permite
     * redireccionar una selección arbitraria de ítems consolidados, de uno o varios tipos a la vez.
     */
    public function redireccionarItems(array $pares, string $dependenciaDestino, string $accionOrigen = 'aprobada', ?int $rolDestinatarioId = null, ?int $usuarioDestinatarioId = null): int
    {
        if (empty($pares)) {
            return 0;
        }

        $consulta = $this->db->prepare(
            "UPDATE peticiones_archivadas
             SET accion = 'redireccionada', redireccionado_a_dependencia = :destino,
                 rol_destinatario_id = :rol_destinatario_id, usuario_destinatario_id = :usuario_destinatario_id
             WHERE origen = :origen AND origen_id = :origen_id AND accion = :accion_origen"
        );

        $total = 0;
        foreach ($pares as $par) {
            $consulta->execute([
                'destino' => $dependenciaDestino,
                'rol_destinatario_id' => $rolDestinatarioId,
                'usuario_destinatario_id' => $usuarioDestinatarioId,
                'origen' => $par['origen'],
                'origen_id' => $par['origen_id'],
                'accion_origen' => $accionOrigen,
            ]);
            $total += $consulta->rowCount();
        }

        return $total;
    }

    /**
     * Igual que `archivar()` (mismo upsert por origen+origen_id), pero forzando
     * accion='redireccionada' y el destino — sirve para redireccionar ítems que pueden
     * no tener todavía ninguna fila (a diferencia de `redireccionarItems()`, que exige
     * una fila previa con un accion concreto).
     */
    public function redireccionarDirecto(array $datos, string $dependenciaDestino, ?int $rolDestinatarioId = null, ?int $usuarioDestinatarioId = null): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO peticiones_archivadas
                (origen, origen_id, accion, tipo, detalle, cantidad, valor, ruta_ver, ruta_origen, redireccionado_a_dependencia, rol_destinatario_id, usuario_destinatario_id)
             VALUES
                (:origen, :origen_id, \'redireccionada\', :tipo, :detalle, :cantidad, :valor, :ruta_ver, :ruta_origen, :destino, :rol_destinatario_id, :usuario_destinatario_id)
             ON DUPLICATE KEY UPDATE
                accion = \'redireccionada\',
                tipo = VALUES(tipo),
                detalle = VALUES(detalle),
                cantidad = VALUES(cantidad),
                valor = VALUES(valor),
                ruta_ver = VALUES(ruta_ver),
                ruta_origen = VALUES(ruta_origen),
                redireccionado_a_dependencia = VALUES(redireccionado_a_dependencia),
                rol_destinatario_id = VALUES(rol_destinatario_id),
                usuario_destinatario_id = VALUES(usuario_destinatario_id)'
        );

        return $consulta->execute([
            'origen' => $datos['origen'],
            'origen_id' => $datos['origen_id'],
            'tipo' => $datos['tipo'],
            'detalle' => $datos['detalle'],
            'cantidad' => $datos['cantidad'],
            'valor' => $datos['valor'],
            'ruta_ver' => $datos['ruta_ver'],
            'ruta_origen' => $datos['ruta_origen'] ?? null,
            'destino' => $dependenciaDestino,
            'rol_destinatario_id' => $rolDestinatarioId,
            'usuario_destinatario_id' => $usuarioDestinatarioId,
        ]);
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
