<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class Rubro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, codigo, descripcion, estado, creado_en FROM rubros ORDER BY codigo');

        return $consulta->fetchAll();
    }

    public function obtenerActivos(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, descripcion FROM rubros WHERE estado = 'activo' ORDER BY descripcion"
        );

        return $consulta->fetchAll();
    }

    /**
     * Igual que obtenerActivos(), pero solo los rubros cuyo capítulo+sección (los dos primeros
     * grupos de su código) esté marcado para $categoria en Configuraciones > Categorías de rubros
     * (autogestion|egresos|proyectos). Si esa categoría no tiene ningún prefijo configurado
     * todavía, se devuelven todos los activos para no dejar el selector vacío por defecto.
     */
    public function obtenerActivosPorCategoria(string $categoria): array
    {
        $columnasValidas = ['autogestion', 'egresos', 'proyectos'];

        if (!in_array($categoria, $columnasValidas, true)) {
            return $this->obtenerActivos();
        }

        $consulta = $this->db->prepare(
            "SELECT r.id, r.codigo, r.descripcion
             FROM rubros r
             JOIN rubro_categorias rc
                ON rc.cap = SUBSTRING_INDEX(r.codigo, '.', 1)
               AND rc.seccion = SUBSTRING_INDEX(SUBSTRING_INDEX(r.codigo, '.', 2), '.', -1)
             WHERE r.estado = 'activo' AND rc.$categoria = 1
             ORDER BY r.descripcion"
        );
        $consulta->execute();
        $filas = $consulta->fetchAll();

        if (empty($filas)) {
            return $this->obtenerActivos();
        }

        return $filas;
    }

    public function existeCodigo(string $codigo): bool
    {
        $consulta = $this->db->prepare('SELECT id FROM rubros WHERE codigo = :codigo LIMIT 1');
        $consulta->execute(['codigo' => $codigo]);

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $descripcion): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO rubros (codigo, descripcion) VALUES (:codigo, :descripcion)'
        );

        return $consulta->execute(['codigo' => $codigo, 'descripcion' => $descripcion]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE rubros SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function actualizarDescripcion(int $id, string $descripcion): bool
    {
        $consulta = $this->db->prepare('UPDATE rubros SET descripcion = :descripcion WHERE id = :id');

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
            'SELECT id, codigo, descripcion, estado FROM rubros WHERE codigo = :codigo LIMIT 1'
        );
        $consulta->execute(['codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
