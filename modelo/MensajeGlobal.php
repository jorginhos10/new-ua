<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Mensajes globales del inicio de los administradores. Cada mensaje (tabla mensajes_globales)
 * es una o más diapositivas del carrusel; se agregan, editan y eliminan desde Configuraciones.
 */
class MensajeGlobal
{
    public const LIMITE_CONTENIDO = 2000;

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** Audiencias que pueden tener mensajes: los administradores y el Consejo Superior. */
    public const AUDIENCIAS = ['administrador', 'consejo_superior'];

    /** @return array<int, array{id: int|string, contenido: string, orden: int|string, audiencia: string}> */
    public function listar(string $audiencia): array
    {
        $consulta = $this->db->prepare(
            'SELECT id, contenido, orden, audiencia FROM mensajes_globales
             WHERE audiencia = :audiencia ORDER BY orden, id'
        );
        $consulta->execute(['audiencia' => $audiencia]);

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, contenido, orden FROM mensajes_globales WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(string $contenido, int $usuarioId, string $audiencia): int
    {
        $ordenConsulta = $this->db->prepare(
            'SELECT COALESCE(MAX(orden), 0) + 1 FROM mensajes_globales WHERE audiencia = :audiencia'
        );
        $ordenConsulta->execute(['audiencia' => $audiencia]);
        $orden = (int) $ordenConsulta->fetchColumn();

        $consulta = $this->db->prepare(
            'INSERT INTO mensajes_globales (contenido, orden, audiencia, actualizado_por)
             VALUES (:contenido, :orden, :audiencia, :usuario_id)'
        );
        $consulta->execute([
            'contenido' => $contenido,
            'orden' => $orden,
            'audiencia' => $audiencia,
            'usuario_id' => $usuarioId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, string $contenido, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE mensajes_globales SET contenido = :contenido, actualizado_por = :usuario_id WHERE id = :id'
        );

        return $consulta->execute(['contenido' => $contenido, 'usuario_id' => $usuarioId, 'id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM mensajes_globales WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Parte el texto Markdown en páginas de como máximo $limite caracteres, para mostrarlo en
     * varias diapositivas. El corte se hace entre bloques (párrafos separados por línea en blanco),
     * así una lista o una tabla no se parte a la mitad. Un bloque más largo que el límite va entero.
     * El render (Markdown a HTML) lo hace el navegador con publico/js/editor-markdown.js.
     */
    public static function paginar(string $contenido, int $limite = 400): array
    {
        $bloques = preg_split('/\n\s*\n/', trim($contenido), -1, PREG_SPLIT_NO_EMPTY);

        $paginas = [];
        $actual = '';

        foreach ($bloques as $bloque) {
            $bloque = trim($bloque);

            if ($actual !== '' && mb_strlen($actual . "\n\n" . $bloque) > $limite) {
                $paginas[] = $actual;
                $actual = '';
            }

            $actual = $actual === '' ? $bloque : $actual . "\n\n" . $bloque;
        }

        if ($actual !== '') {
            $paginas[] = $actual;
        }

        return $paginas;
    }
}
