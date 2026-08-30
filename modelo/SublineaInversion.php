<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';
require_once __DIR__ . '/LineaInversion.php';

class SublineaInversion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query(
            'SELECT s.id, s.codigo, s.nombre, s.descripcion, s.linea_inversion_id, s.estado, s.creado_en,
                    l.codigo AS linea_codigo, l.nombre AS linea_nombre
             FROM sublineas_inversion s
             JOIN lineas_inversion l ON l.id = s.linea_inversion_id
             ORDER BY s.codigo'
        );

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT s.id, s.codigo, s.nombre, s.descripcion, s.linea_inversion_id, l.codigo AS linea_codigo
             FROM sublineas_inversion s
             JOIN lineas_inversion l ON l.id = s.linea_inversion_id
             WHERE s.estado = 'activo'
             ORDER BY s.codigo"
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, descripcion, linea_inversion_id FROM sublineas_inversion WHERE codigo = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM sublineas_inversion WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre, string $descripcion, int $lineaInversionId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO sublineas_inversion (codigo, nombre, descripcion, linea_inversion_id) VALUES (:codigo, :nombre, :descripcion, :linea_inversion_id)'
        );

        return $consulta->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'linea_inversion_id' => $lineaInversionId,
        ]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE sublineas_inversion SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function actualizarDatos(int $id, string $nombre, string $descripcion, int $lineaInversionId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE sublineas_inversion SET nombre = :nombre, descripcion = :descripcion, linea_inversion_id = :linea_inversion_id WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'linea_inversion_id' => $lineaInversionId,
        ]);
    }

    /**
     * Igual que las demás sincronizaciones desde CSV, pero además resuelve la línea de inversión por
     * su código ("linea_codigo" en el archivo) en vez de esperar un id numérico crudo. Filas cuya
     * línea no exista se ignoran (no se crean ni se actualizan) para no dejar una FK inválida.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $modeloLinea = new LineaInversion();
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;
        $ignorados = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $descripcion = trim($fila['descripcion'] ?? '');
            $lineaCodigo = trim($fila['linea_codigo'] ?? '');

            if ($codigo === '' || $nombre === '' || $lineaCodigo === '') {
                continue;
            }

            $linea = $modeloLinea->obtenerPorCodigo($lineaCodigo);

            if ($linea === null) {
                $ignorados++;
                continue;
            }

            $codigosCsv[] = $codigo;
            $lineaInversionId = (int) $linea['id'];
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->buscarPorCodigo($codigo);

            if ($existente === null) {
                $this->crear($codigo, $nombre, $descripcion, $lineaInversionId);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } else {
                $cambio = false;

                if (
                    $existente['nombre'] !== $nombre
                    || $existente['descripcion'] !== $descripcion
                    || (int) $existente['linea_inversion_id'] !== $lineaInversionId
                ) {
                    $this->actualizarDatos((int) $existente['id'], $nombre, $descripcion, $lineaInversionId);
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

        foreach ($this->obtenerTodas() as $existente) {
            if (in_array($existente['codigo'], $codigosCsv, true)) {
                continue;
            }

            if ($existente['estado'] === 'activo') {
                $this->cambiarEstado((int) $existente['id']);
                $desactivados++;
            }
        }

        return [
            'creados' => $creados,
            'actualizados' => $actualizados,
            'desactivados' => $desactivados,
            'ignorados' => $ignorados,
        ];
    }

    private function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, codigo, nombre, descripcion, linea_inversion_id, estado FROM sublineas_inversion WHERE codigo = :codigo LIMIT 1'
        );
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
