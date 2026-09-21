<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

class Rol
{
    private const COLOR_DEFECTO = '#0071e3';

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** Valida "#RRGGBB"; cualquier valor que no cumpla ese formato cae al color por defecto. */
    public static function normalizarColor(string $color): string
    {
        $color = trim($color);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1 ? $color : self::COLOR_DEFECTO;
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query('SELECT id, nombre, orden, color, creado_en FROM roles ORDER BY orden ASC, id ASC');

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, orden, color, creado_en FROM roles WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorNombre(string $nombre): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, orden, color, creado_en FROM roles WHERE nombre = :nombre LIMIT 1');
        $consulta->execute(['nombre' => $nombre]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeRol(string $nombre, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->db->prepare('SELECT id FROM roles WHERE nombre = :nombre AND id != :id LIMIT 1');
            $consulta->execute(['nombre' => $nombre, 'id' => $ignorarId]);
        } else {
            $consulta = $this->db->prepare('SELECT id FROM roles WHERE nombre = :nombre LIMIT 1');
            $consulta->execute(['nombre' => $nombre]);
        }

        return $consulta->fetch() !== false;
    }

    public function obtenerSiguienteOrden(): int
    {
        $consulta = $this->db->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM roles');

        return (int) $consulta->fetchColumn();
    }

    public function crear(string $nombre, int $orden, string $color = self::COLOR_DEFECTO): bool
    {
        $consulta = $this->db->prepare('INSERT INTO roles (nombre, orden, color) VALUES (:nombre, :orden, :color)');

        return $consulta->execute(['nombre' => $nombre, 'orden' => $orden, 'color' => self::normalizarColor($color)]);
    }

    public function actualizar(int $id, string $nombre, int $orden, string $color = self::COLOR_DEFECTO): bool
    {
        $consulta = $this->db->prepare('UPDATE roles SET nombre = :nombre, orden = :orden, color = :color WHERE id = :id');

        return $consulta->execute(['id' => $id, 'nombre' => $nombre, 'orden' => $orden, 'color' => self::normalizarColor($color)]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM roles WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function sincronizarDesdeCsv(array $filas): array
    {
        $nombresCsv = [];
        $creados = 0;
        $actualizados = 0;

        foreach ($filas as $fila) {
            $nombre = trim($fila['nombre'] ?? '');

            if ($nombre === '') {
                continue;
            }

            $nombresCsv[] = $nombre;
            $ordenTexto = trim($fila['orden'] ?? '');
            $orden = $ordenTexto !== '' ? (int) $ordenTexto : $this->obtenerSiguienteOrden();
            $colorTexto = trim($fila['color'] ?? '');

            $existente = $this->buscarPorNombre($nombre);

            if ($existente === null) {
                $this->crear($nombre, $orden, $colorTexto !== '' ? $colorTexto : self::COLOR_DEFECTO);
                $creados++;
            } else {
                // Si el CSV no trae columna de color, se conserva el color que ya tenía el rol
                // (evita que reimportar una plantilla vieja borre los colores ya configurados).
                $color = $colorTexto !== '' ? self::normalizarColor($colorTexto) : $existente['color'];

                if ((int) $existente['orden'] !== $orden || $existente['color'] !== $color) {
                    $this->actualizar((int) $existente['id'], $nombre, $orden, $color);
                    $actualizados++;
                }
            }
        }

        $eliminados = 0;
        $omitidos = 0;

        foreach ($this->obtenerTodos() as $existente) {
            if (in_array($existente['nombre'], $nombresCsv, true)) {
                continue;
            }

            $id = (int) $existente['id'];

            if (CsvConfiguracion::tieneReferencias($this->db, 'roles', $id)) {
                $omitidos++;
                continue;
            }

            try {
                $this->eliminar($id);
                $eliminados++;
            } catch (PDOException $excepcion) {
                $omitidos++;
            }
        }

        return ['creados' => $creados, 'actualizados' => $actualizados, 'eliminados' => $eliminados, 'omitidos' => $omitidos];
    }

    private function buscarPorNombre(string $nombre): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, orden, color, creado_en FROM roles WHERE nombre = :nombre LIMIT 1');
        $consulta->execute(['nombre' => $nombre]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }
}
