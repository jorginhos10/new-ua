<?php

require_once __DIR__ . '/../config/conexion.php';

class Dependencia
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->query(
            'SELECT d.id, d.codigo, d.nombre, d.tipo, d.estado, d.flujo_id, d.no_monetizable, d.no_listar, d.es_raiz_superadmin, d.creado_en, f.nombre AS flujo_manual_nombre
             FROM dependencias d
             LEFT JOIN dependencias f ON f.id = d.flujo_id
             ORDER BY d.es_raiz_superadmin DESC, d.codigo'
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorTipo(string $tipo): array
    {
        $consulta = $this->db->prepare(
            "SELECT DISTINCT nombre FROM dependencias WHERE tipo = :tipo AND estado = 'activo' ORDER BY nombre"
        );
        $consulta->execute(['tipo' => $tipo]);

        return $consulta->fetchAll();
    }

    /**
     * Dependencias activas cuyo tipo está entre los indicados (ej. programas académicos de
     * pregrado/postgrado, para el campo "Programa académico" de Perfil de proyectos).
     */
    public function obtenerPorTipos(array $tipos): array
    {
        if (empty($tipos)) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($tipos), '?'));
        $consulta = $this->db->prepare(
            "SELECT id, codigo, nombre, tipo FROM dependencias WHERE estado = 'activo' AND tipo IN ($marcadores) ORDER BY nombre"
        );
        $consulta->execute(array_values($tipos));

        return $consulta->fetchAll();
    }

    public function obtenerActivas(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, nombre, tipo, es_raiz_superadmin, no_monetizable FROM dependencias WHERE estado = 'activo' AND no_listar = 0 ORDER BY es_raiz_superadmin DESC, nombre"
        );

        return $consulta->fetchAll();
    }

    /**
     * Igual que obtenerActivas(), pero sin las dependencias "hijas" (programas académicos de
     * pregrado/postgrado) — para los selectores de "enviar a" / "redireccionar a", donde solo
     * tiene sentido elegir una unidad administrativa (facultad, departamento, oficina, etc.),
     * no un programa académico individual.
     */
    public function obtenerActivasParaEnvio(): array
    {
        $consulta = $this->db->query(
            "SELECT id, codigo, nombre, tipo, es_raiz_superadmin, no_monetizable
             FROM dependencias
             WHERE estado = 'activo' AND no_listar = 0 AND (tipo IS NULL OR tipo NOT IN ('postgrado', 'pregrado'))
             ORDER BY es_raiz_superadmin DESC, nombre"
        );

        return $consulta->fetchAll();
    }

    public function contarMonetizablesActivas(): int
    {
        $consulta = $this->db->query(
            "SELECT COUNT(*) FROM dependencias WHERE estado = 'activo' AND no_monetizable = 0 AND es_raiz_superadmin = 0"
        );

        return (int) $consulta->fetchColumn();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, tipo, estado, flujo_id, es_raiz_superadmin FROM dependencias WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Devuelve la cadena de dependencias superiores de $dependenciaId, empezando
     * por su padre directo y subiendo hasta la raíz.
     */
    public function obtenerAncestros(int $dependenciaId): array
    {
        $ancestros = [];
        $actual = $this->obtenerPorId($dependenciaId);
        $limite = 50;

        while ($actual !== null && !empty($actual['flujo_id']) && $limite-- > 0) {
            $padre = $this->obtenerPorId((int) $actual['flujo_id']);

            if ($padre === null) {
                break;
            }

            $ancestros[] = $padre;
            $actual = $padre;
        }

        return $ancestros;
    }

    public function obtenerPorNombre(string $nombre): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nombre, tipo, estado, flujo_id, es_raiz_superadmin FROM dependencias WHERE nombre = :nombre');
        $consulta->execute(['nombre' => $nombre]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function existeCodigo(string $codigo, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->db->prepare('SELECT id FROM dependencias WHERE codigo = :codigo AND id != :id LIMIT 1');
            $consulta->execute(['codigo' => $codigo, 'id' => $ignorarId]);
        } else {
            $consulta = $this->db->prepare('SELECT id FROM dependencias WHERE codigo = :codigo LIMIT 1');
            $consulta->execute(['codigo' => $codigo]);
        }

        return $consulta->fetch() !== false;
    }

    public function crear(string $codigo, string $nombre, ?string $tipo = null): bool
    {
        $consulta = $this->db->prepare('INSERT INTO dependencias (codigo, nombre, tipo) VALUES (:codigo, :nombre, :tipo)');

        return $consulta->execute(['codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo]);
    }

    public function actualizar(int $id, string $codigo, string $nombre, ?string $tipo = null, ?int $flujoId = null): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE dependencias SET codigo = :codigo, nombre = :nombre, tipo = :tipo, flujo_id = :flujo_id WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'flujo_id' => $flujoId]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM dependencias WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE dependencias SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function cambiarNoMonetizable(int $id): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE dependencias SET no_monetizable = NOT no_monetizable WHERE id = :id'
        );

        return $consulta->execute(['id' => $id]);
    }

    public function cambiarNoListar(int $id): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE dependencias SET no_listar = NOT no_listar WHERE id = :id'
        );

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Calcula el flujo_id efectivo de cada dependencia (id => id del padre o null).
     * Ya no se infiere automáticamente a partir del código: cada dependencia debe
     * tener su flujo (columna flujo_id) asignado manualmente en Editar dependencia.
     */
    public function calcularFlujoEfectivo(array $dependencias): array
    {
        $flujoIdPorId = [];

        foreach ($dependencias as $dependencia) {
            $flujoIdPorId[(int) $dependencia['id']] = !empty($dependencia['flujo_id']) ? (int) $dependencia['flujo_id'] : null;
        }

        return $flujoIdPorId;
    }

    public function calcularFamiliaPorId(array $dependencias): array
    {
        $familiaPorId = [];

        foreach ($dependencias as $dependencia) {
            $codigo = $dependencia['codigo'];
            $familiaPorId[(int) $dependencia['id']] = ($codigo !== '0' && strlen($codigo) === 6) ? substr($codigo, 0, 2) : null;
        }

        return $familiaPorId;
    }

    /**
     * Devuelve las dependencias cuyo flujo efectivo (ver calcularFlujoEfectivo) apunta
     * directamente a $dependenciaId (sus "hijas" en el árbol de flujo).
     */
    public function obtenerHijasDirectas(int $dependenciaId): array
    {
        $todas = $this->obtenerTodas();
        $flujoIdPorId = $this->calcularFlujoEfectivo($todas);

        $hijas = [];
        foreach ($todas as $dependencia) {
            if (($flujoIdPorId[(int) $dependencia['id']] ?? null) === $dependenciaId) {
                $hijas[] = $dependencia;
            }
        }

        return $hijas;
    }

    /**
     * Construye el árbol completo de descendientes (hijas, nietas, etc.) de una dependencia,
     * usando el flujo_id de cada una. Cada nodo es ['dependencia' => array, 'hijos' => array de nodos].
     * Las dependencias marcadas como no monetizables se excluyen del árbol (con toda su rama),
     * ya que no pueden recibir ni asignar techo presupuestal.
     */
    public function construirArbolDescendientes(int $dependenciaId): array
    {
        $todas = $this->obtenerTodas();

        $porFlujo = [];
        foreach ($todas as $dependencia) {
            if (!empty($dependencia['flujo_id']) && (int) ($dependencia['no_monetizable'] ?? 0) !== 1) {
                $porFlujo[(int) $dependencia['flujo_id']][] = $dependencia;
            }
        }

        $construir = function (int $id, array $visitados) use (&$construir, $porFlujo): array {
            if (in_array($id, $visitados, true)) {
                return [];
            }

            $visitados[] = $id;
            $nodos = [];

            foreach ($porFlujo[$id] ?? [] as $hijo) {
                $nodos[] = [
                    'dependencia' => $hijo,
                    'hijos' => $construir((int) $hijo['id'], $visitados),
                ];
            }

            return $nodos;
        };

        return $construir($dependenciaId, []);
    }

    /**
     * Aplana el árbol de construirArbolDescendientes() en una lista simple de dependencias.
     */
    public function obtenerDescendientesPlano(int $dependenciaId): array
    {
        $aplanar = function (array $nodos) use (&$aplanar): array {
            $plano = [];

            foreach ($nodos as $nodo) {
                $plano[] = $nodo['dependencia'];
                $plano = array_merge($plano, $aplanar($nodo['hijos']));
            }

            return $plano;
        };

        return $aplanar($this->construirArbolDescendientes($dependenciaId));
    }
}
