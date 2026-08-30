<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';

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

    /**
     * Las filas de este catálogo no se crean ni se borran desde aquí (se derivan de los prefijos
     * cap.sección realmente usados en Rubros, vía sincronizarPrefijos()); el CSV solo controla las 3
     * marcas booleanas. Igual que el guardado por formulario, es un reemplazo total: cap.sección que
     * no aparezca en el archivo queda sin marcar en las 3 categorías. Filas del CSV cuyo cap.sección
     * no exista en el catálogo actual se ignoran (no se pueden inventar combinaciones nuevas).
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $deseado = [];

        foreach ($filas as $fila) {
            $cap = trim($fila['cap'] ?? '');
            $seccion = trim($fila['seccion'] ?? '');

            if ($cap === '' || $seccion === '') {
                continue;
            }

            $deseado[$cap . '.' . $seccion] = [
                'autogestion' => $this->esVerdadero($fila['autogestion'] ?? ''),
                'egresos' => $this->esVerdadero($fila['egresos'] ?? ''),
                'proyectos' => $this->esVerdadero($fila['proyectos'] ?? ''),
            ];
        }

        $actualizados = 0;

        foreach ($this->obtenerTodos() as $existente) {
            $clave = $existente['cap'] . '.' . $existente['seccion'];
            $valores = $deseado[$clave] ?? ['autogestion' => false, 'egresos' => false, 'proyectos' => false];

            $cambio = (bool) $existente['autogestion'] !== $valores['autogestion']
                || (bool) $existente['egresos'] !== $valores['egresos']
                || (bool) $existente['proyectos'] !== $valores['proyectos'];

            if ($cambio) {
                $this->guardar((int) $existente['id'], $valores['autogestion'], $valores['egresos'], $valores['proyectos']);
                $actualizados++;
            }

            unset($deseado[$clave]);
        }

        return ['actualizados' => $actualizados, 'sin_coincidencia' => count($deseado)];
    }

    private function esVerdadero(string $valor): bool
    {
        return in_array(strtolower(trim($valor)), ['1', 'si', 'sí', 'true', 'x'], true);
    }
}
