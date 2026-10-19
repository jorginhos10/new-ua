<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Categoría de gasto (37 subcategorías de actividad) para gastos principales. El motor que asigna
 * la categoría corre en el navegador (publico/js/categorizador-gastos.js) con el catálogo y el
 * mapeo que entrega este modelo; el servidor valida y guarda.
 */
class CategoriaGasto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerCatalogo(): array
    {
        $filas = $this->db->query(
            "SELECT id, macro, subcategoria, tokens FROM categorias_gasto WHERE estado = 'activo' ORDER BY orden"
        )->fetchAll();

        return array_map(static fn (array $fila): array => [
            'id' => $fila['id'],
            'macro' => $fila['macro'],
            'subcategoria' => $fila['subcategoria'],
            'tokens' => array_values(array_filter(array_map('trim', explode(',', $fila['tokens'])))),
        ], $filas);
    }

    public function obtenerMapeo(): array
    {
        return $this->db->query(
            'SELECT prefijo_codigo, accion, categoria_id, es_defecto, peso FROM rubro_categoria_gasto'
        )->fetchAll();
    }

    /** Subcategoría por id, también las inactivas: los gastos ya clasificados conservan su rótulo. */
    public function obtenerNombresPorId(): array
    {
        $nombres = [];
        foreach ($this->db->query('SELECT id, subcategoria FROM categorias_gasto')->fetchAll() as $fila) {
            $nombres[$fila['id']] = $fila['subcategoria'];
        }

        return $nombres;
    }

    // --- Administración (Configuraciones > Listas > Categorías de gasto) ---

    /**
     * Todas, también las inactivas, con cuántos gastos/reglas de rubro las usan (para "en uso"):
     * gastos principales y los egresos de Extensión, Postgrado y Unisalud. Si esas tres tablas aún
     * no tienen la columna (instalar.php sin correr), cuenta solo los gastos principales.
     */
    public function obtenerTodos(): array
    {
        $usosAutogestion = '(SELECT COUNT(*) FROM gastos_extension ge WHERE ge.categoria_gasto_id = c.id)
                    + (SELECT COUNT(*) FROM gastos_postgrado gp WHERE gp.categoria_gasto_id = c.id)
                    + (SELECT COUNT(*) FROM gastos_unisalud gu WHERE gu.categoria_gasto_id = c.id)
                    + (SELECT COUNT(*) FROM gastos_fijos gf WHERE gf.categoria_gasto_id = c.id)';
        $consulta = static fn (string $extra): string => 'SELECT c.id, c.macro, c.subcategoria, c.tokens, c.orden, c.estado,
                    (SELECT COUNT(*) FROM gastos g WHERE g.categoria_gasto_id = c.id)' . $extra . '
                    + (SELECT COUNT(*) FROM rubro_categoria_gasto r WHERE r.categoria_id = c.id) AS usos
             FROM categorias_gasto c
             ORDER BY c.orden, c.id';

        try {
            return $this->db->query($consulta(' + ' . $usosAutogestion))->fetchAll();
        } catch (PDOException $excepcion) {
            return $this->db->query($consulta(''))->fetchAll();
        }
    }

    /**
     * Valida la "Categoría de gasto" que llega de un formulario (categoria_gasto_id +
     * categoria_origen + categoria_confianza) y la deja lista para guardar. Vacía = sin categoría.
     * Lo usan los egresos de Extensión, Postgrado y Unisalud (Gastos hace lo mismo en su controlador).
     *
     * @return array{0: array{categoria_gasto_id: ?string, categoria_origen: ?string, categoria_confianza: ?float}, 1: string} [campos, error]
     */
    public function normalizarSeleccion(array $datos): array
    {
        $categoriaId = trim((string) ($datos['categoria_gasto_id'] ?? ''));
        if ($categoriaId === '') {
            return [['categoria_gasto_id' => null, 'categoria_origen' => null, 'categoria_confianza' => null], ''];
        }

        if (!$this->existeCategoria($categoriaId)) {
            return [[], 'La categoría de gasto seleccionada no existe.'];
        }

        $origen = ($datos['categoria_origen'] ?? '') === 'automatico' ? 'automatico' : 'manual';
        $confianza = $origen === 'automatico' && is_numeric($datos['categoria_confianza'] ?? null)
            ? round(min(1.0, max(0.0, (float) $datos['categoria_confianza'])), 3)
            : null;

        return [['categoria_gasto_id' => $categoriaId, 'categoria_origen' => $origen, 'categoria_confianza' => $confianza], ''];
    }

    public function obtenerPorId(string $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM categorias_gasto WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(string $id, string $macro, string $subcategoria, string $tokens, int $orden): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO categorias_gasto (id, macro, subcategoria, tokens, orden) VALUES (:id, :macro, :subcategoria, :tokens, :orden)'
        );

        return $consulta->execute(['id' => $id, 'macro' => $macro, 'subcategoria' => $subcategoria, 'tokens' => $tokens, 'orden' => $orden]);
    }

    public function actualizar(string $id, string $macro, string $subcategoria, string $tokens, int $orden): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE categorias_gasto SET macro = :macro, subcategoria = :subcategoria, tokens = :tokens, orden = :orden WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'macro' => $macro, 'subcategoria' => $subcategoria, 'tokens' => $tokens, 'orden' => $orden]);
    }

    public function cambiarEstado(string $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE categorias_gasto SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(string $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM categorias_gasto WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function siguienteOrden(): int
    {
        return (int) $this->db->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM categorias_gasto')->fetchColumn();
    }

    public function existeCategoria(string $id): bool
    {
        $consulta = $this->db->prepare("SELECT 1 FROM categorias_gasto WHERE id = :id AND estado = 'activo'");
        $consulta->execute(['id' => $id]);

        return $consulta->fetchColumn() !== false;
    }

    /**
     * Tablas de gasto que clasifica el lote de Configuraciones > Categoría de gasto (clave = tabla,
     * valor = rótulo para el resumen). También es la lista blanca que valida el controlador.
     */
    public const TABLAS_CLASIFICABLES = [
        'gastos' => 'Gastos principales',
        'gastos_extension' => 'Extensión',
        'gastos_postgrado' => 'Postgrado',
        'gastos_unisalud' => 'Unisalud',
        'gastos_fijos' => 'Gastos fijos',
    ];

    /**
     * modo 'vacios': solo gastos sin categoría. modo 'todo': todos (sobrescribe manuales). Cada gasto
     * lleva su `tabla` (los ids se repiten entre tablas). Las tablas de autogestión se omiten si aún
     * no tienen las columnas de categoría (instalar.php sin correr). A las filas de autogestión sin
     * `rubro_id` (p. ej. Excedentes automáticos, que solo guardan `rubro_texto`) se les resuelve el
     * código de rubro comparando ese texto con la descripción de la lista de rubros.
     */
    public function gastosParaClasificar(string $modo): array
    {
        $condicion = $modo === 'vacios' ? 'WHERE g.categoria_gasto_id IS NULL' : '';
        $rubroPorDescripcion = null;
        $gastos = [];

        foreach (array_keys(self::TABLAS_CLASIFICABLES) as $tabla) {
            // gastos_fijos no tiene rubro_texto ni tipo_automatico.
            $columnasTexto = $tabla === 'gastos_fijos'
                ? 'NULL AS rubro_texto, NULL AS tipo_automatico'
                : 'g.rubro_texto, g.tipo_automatico';
            try {
                $filas = $this->db->query(
                    "SELECT g.id, g.actividad, g.insumo, $columnasTexto, g.categoria_gasto_id, g.categoria_origen,
                            r.codigo AS rubro_codigo
                     FROM $tabla g
                     LEFT JOIN rubros r ON r.id = g.rubro_id
                     $condicion
                     ORDER BY g.id"
                )->fetchAll();
            } catch (PDOException $excepcion) {
                if ($tabla === 'gastos') {
                    throw $excepcion;
                }

                continue;
            }

            foreach ($filas as $fila) {
                if ($tabla !== 'gastos' && empty($fila['rubro_codigo']) && trim((string) ($fila['rubro_texto'] ?? '')) !== '') {
                    $rubroPorDescripcion ??= $this->rubrosPorDescripcionNormalizada();
                    $fila['rubro_codigo'] = $rubroPorDescripcion[self::normalizarTexto((string) $fila['rubro_texto'])] ?? null;
                }

                $fila['tabla'] = $tabla;
                $gastos[] = $fila;
            }
        }

        return $gastos;
    }

    /** descripción normalizada => código de rubro (el primero si se repite). */
    private function rubrosPorDescripcionNormalizada(): array
    {
        $mapa = [];
        foreach ($this->db->query('SELECT codigo, descripcion FROM rubros ORDER BY codigo')->fetchAll() as $rubro) {
            $clave = self::normalizarTexto((string) $rubro['descripcion']);
            if ($clave !== '' && !isset($mapa[$clave])) {
                $mapa[$clave] = $rubro['codigo'];
            }
        }

        return $mapa;
    }

    /** Minúsculas, sin tildes ni signos y con espacios colapsados (igual criterio que el motor del navegador). */
    private static function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9\s]/', ' ', $texto)));
    }

    /** Gastos con categoría elegida a mano, sumando las 4 tablas (las de autogestión sin columna cuentan 0). */
    public function contarManuales(): int
    {
        $total = 0;
        foreach (array_keys(self::TABLAS_CLASIFICABLES) as $tabla) {
            try {
                $total += (int) $this->db->query("SELECT COUNT(*) FROM $tabla WHERE categoria_origen = 'manual'")->fetchColumn();
            } catch (PDOException $excepcion) {
                if ($tabla === 'gastos') {
                    throw $excepcion;
                }
            }
        }

        return $total;
    }

    /**
     * Guarda el resultado del motor. Cada item lleva su `tabla` (una de TABLAS_CLASIFICABLES; sin ella,
     * 'gastos'). Solo acepta origen 'automatico' (con categoría existente) o 'no_aplica' (sin
     * categoría). Todo o nada.
     */
    public function guardarClasificacion(array $items): int
    {
        $this->db->beginTransaction();

        try {
            $consultas = [];
            $guardados = 0;
            foreach ($items as $item) {
                $tabla = (string) ($item['tabla'] ?? 'gastos');
                if (!isset(self::TABLAS_CLASIFICABLES[$tabla])) {
                    throw new InvalidArgumentException('Tabla no válida: ' . $tabla);
                }

                $consultas[$tabla] ??= $this->db->prepare(
                    "UPDATE $tabla
                     SET categoria_gasto_id = :categoria_gasto_id, categoria_origen = :categoria_origen, categoria_confianza = :categoria_confianza
                     WHERE id = :id"
                );
                $consultas[$tabla]->execute([
                    'id' => (int) $item['id'],
                    'categoria_gasto_id' => $item['categoria_gasto_id'],
                    'categoria_origen' => $item['categoria_origen'],
                    'categoria_confianza' => $item['categoria_confianza'],
                ]);
                $guardados++;
            }

            $this->db->commit();

            return $guardados;
        } catch (Throwable $excepcion) {
            $this->db->rollBack();

            throw $excepcion;
        }
    }
}
