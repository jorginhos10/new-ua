<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Clasifica los rubros presupuestales por "capítulo" y "sección" (los dos primeros grupos de
 * dígitos de su código, ej. "2.01.01.01.01.01" -> cap=2, sección=01), y permite marcar en cuáles
 * categorías de la barra lateral (Autogestión, Egresos, Proyectos) debe aparecer cada combinación.
 */
class RubroCategoria
{
    private PDO $db;

    private const CATEGORIAS_VALIDAS = ['autogestion', 'egresos', 'proyectos'];

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Crea, si no existe todavía, una fila (sin categorías marcadas) por cada combinación de
     * capítulo+sección que aparezca actualmente en la tabla de rubros. Así la pantalla de
     * configuración siempre refleja los prefijos realmente usados, sin tener que mantenerlos a mano.
     */
    public function sincronizarPrefijos(): void
    {
        $consulta = $this->db->query(
            "SELECT DISTINCT
                    SUBSTRING_INDEX(codigo, '.', 1) AS cap,
                    SUBSTRING_INDEX(SUBSTRING_INDEX(codigo, '.', 2), '.', -1) AS seccion
             FROM rubros
             WHERE codigo LIKE '%.%'"
        );

        $insertar = $this->db->prepare(
            'INSERT IGNORE INTO rubro_categorias (cap, seccion) VALUES (:cap, :seccion)'
        );

        foreach ($consulta->fetchAll() as $fila) {
            $insertar->execute(['cap' => $fila['cap'], 'seccion' => $fila['seccion']]);
        }
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT id, cap, seccion, autogestion, egresos, proyectos FROM rubro_categorias
             ORDER BY CAST(cap AS UNSIGNED), CAST(seccion AS UNSIGNED)'
        );

        return $consulta->fetchAll();
    }

    public function guardar(int $id, bool $autogestion, bool $egresos, bool $proyectos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE rubro_categorias SET autogestion = :autogestion, egresos = :egresos, proyectos = :proyectos WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'autogestion' => $autogestion ? 1 : 0,
            'egresos' => $egresos ? 1 : 0,
            'proyectos' => $proyectos ? 1 : 0,
        ]);
    }

    /**
     * Prefijos "cap.seccion" (ej. "2.01") marcados para una categoría de la barra lateral.
     */
    public function obtenerPrefijosPorCategoria(string $categoria): array
    {
        if (!in_array($categoria, self::CATEGORIAS_VALIDAS, true)) {
            return [];
        }

        $consulta = $this->db->prepare(
            "SELECT cap, seccion FROM rubro_categorias WHERE $categoria = 1"
        );
        $consulta->execute();

        return array_map(
            static fn (array $fila): string => $fila['cap'] . '.' . $fila['seccion'],
            $consulta->fetchAll()
        );
    }
}
