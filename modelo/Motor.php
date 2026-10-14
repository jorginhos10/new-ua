<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';
require_once __DIR__ . '/Linea.php';

class Motor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT m.id, m.codigo, m.nombre, m.linea_id, m.estado, m.creado_en, l.codigo AS linea_codigo, l.nombre AS linea_nombre
             FROM motores m
             JOIN lineas l ON l.id = m.linea_id
             ORDER BY m.codigo'
        );

        return $consulta->fetchAll();
    }

    public function obtenerActivos(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, nombre FROM motores WHERE estado = 'activo' ORDER BY codigo"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT m.id, m.codigo, m.nombre, m.linea_id, m.estado, m.creado_en
             FROM motores m WHERE m.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * `codigo` NO es único globalmente (ej. "M1" se repite bajo cada línea, a propósito — ver
     * captura de la landing) — la clave natural real es (codigo, linea_id), nunca codigo solo.
     */
    public function obtenerPorCodigoYLinea(string $codigo, int $lineaId): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, codigo, nombre, linea_id, estado FROM motores WHERE codigo = :codigo AND linea_id = :linea_id LIMIT 1'
        );
        $consulta->execute(['codigo' => $codigo, 'linea_id' => $lineaId]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(string $codigo, string $nombre, int $lineaId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO motores (codigo, nombre, linea_id) VALUES (:codigo, :nombre, :linea_id)'
        );

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre, 'linea_id' => $lineaId]);
    }

    public function actualizar(int $id, string $codigo, string $nombre, int $lineaId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE motores SET codigo = :codigo, nombre = :nombre, linea_id = :linea_id WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre, 'linea_id' => $lineaId]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE motores SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM motores WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Mismo patrón que Linea/Rubro (crear/actualizar/desactivar, nunca borra), con la diferencia
     * de que resuelve la línea por código (columna linea_codigo del CSV) — mismo mecanismo que ya
     * usa SublineaInversion::sincronizarDesdeCsv() para su FK. Filas cuyo linea_codigo no exista
     * se ignoran (contador 'ignorados').
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $modeloLinea = new Linea();
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;
        $ignorados = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $lineaCodigo = trim($fila['linea_codigo'] ?? '');

            if ($codigo === '' || $nombre === '' || $lineaCodigo === '') {
                continue;
            }

            $linea = $modeloLinea->obtenerPorCodigo($lineaCodigo);

            if ($linea === null) {
                $ignorados++;
                continue;
            }

            $lineaId = (int) $linea['id'];
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->obtenerPorCodigoYLinea($codigo, $lineaId);
            $codigosCsv[] = $lineaId . '|' . $codigo;

            if ($existente === null) {
                $this->crear($codigo, $nombre, $lineaId);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } else {
                $cambio = false;

                if ($existente['nombre'] !== $nombre) {
                    $this->actualizar((int) $existente['id'], $codigo, $nombre, $lineaId);
                    $cambio = true;
                }

                if ($existente['estado'] !== $estadoDeseado) {
                    $this->cambiarEstado((int) $existente['id']);
                    $cambio = true;
                }

                if ($cambio) {
                    $actualizados++;
                }
            }
        }

        $desactivados = 0;

        foreach ($this->obtenerTodos() as $existente) {
            if (in_array((int) $existente['linea_id'] . '|' . $existente['codigo'], $codigosCsv, true)) {
                continue;
            }

            if ($existente['estado'] === 'activo') {
                $this->cambiarEstado((int) $existente['id']);
                $desactivados++;
            }
        }

        return ['creados' => $creados, 'actualizados' => $actualizados, 'desactivados' => $desactivados, 'ignorados' => $ignorados];
    }
}
