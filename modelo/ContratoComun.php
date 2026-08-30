<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class ContratoComun
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, descripcion, estado, creado_en FROM contratos_comunes ORDER BY codigo');

        return $consulta->fetchAll();
    }

    public function obtenerActivos(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, descripcion FROM contratos_comunes WHERE estado = 'activo' ORDER BY codigo"
        );

        return $consulta->fetchAll();
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM contratos_comunes WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $descripcion): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO contratos_comunes (codigo, descripcion) VALUES (:codigo, :descripcion)'
        );

        return $consulta->execute(['codigo' => $codigo, 'descripcion' => $descripcion]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE contratos_comunes SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function actualizarDescripcion(int $id, string $descripcion): bool
    {
        $consulta = $this->db->prepare('UPDATE contratos_comunes SET descripcion = :descripcion WHERE id = :id');

        return $consulta->execute(['id' => $id, 'descripcion' => $descripcion]);
    }

    /**
     * Este catálogo no tiene borrado real, solo estado activo/inactivo. La sincronización desde CSV
     * no elimina filas: crea las que faltan, actualiza descripción/estado según el archivo, y
     * desactiva (no borra) las que ya no aparecen en el archivo.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $descripcion = trim($fila['descripcion'] ?? '');

            if ($codigo === '' || $descripcion === '') {
                continue;
            }

            $codigosCsv[] = $codigo;
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->buscarPorCodigo($codigo);

            if ($existente === null) {
                $this->crear($codigo, $descripcion);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } else {
                $cambio = false;

                if ($existente['descripcion'] !== $descripcion) {
                    $this->actualizarDescripcion((int) $existente['id'], $descripcion);
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
            if (in_array($existente['codigo'], $codigosCsv, true)) {
                continue;
            }

            if ($existente['estado'] === 'activo') {
                $this->cambiarEstado((int) $existente['id']);
                $desactivados++;
            }
        }

        return ['creados' => $creados, 'actualizados' => $actualizados, 'desactivados' => $desactivados];
    }

    private function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, codigo, descripcion, estado FROM contratos_comunes WHERE codigo = :codigo LIMIT 1'
        );
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
