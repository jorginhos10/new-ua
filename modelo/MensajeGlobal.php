<?php

require_once __DIR__ . '/../config/conexion.php';

class MensajeGlobal
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtener(): ?array
    {
        $consulta = $this->db->query('SELECT * FROM mensaje_global WHERE id = 1');
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function guardar(string $contenido, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO mensaje_global (id, contenido, actualizado_por)
             VALUES (1, :contenido, :usuario_id)
             ON DUPLICATE KEY UPDATE contenido = :contenido2, actualizado_por = :usuario_id2'
        );

        return $consulta->execute([
            'contenido' => $contenido,
            'usuario_id' => $usuarioId,
            'contenido2' => $contenido,
            'usuario_id2' => $usuarioId,
        ]);
    }

    /**
     * Convierte el contenido en texto plano a HTML seguro: escapa todo el texto y solo
     * reconoce la sintaxis [texto](url) para crear hipervínculos, con esquema http(s)/mailto
     * validado. Nunca permite HTML arbitrario proveniente del contenido guardado.
     */
    public static function renderizar(string $contenido): string
    {
        $escapado = nl2br(htmlspecialchars($contenido, ENT_QUOTES, 'UTF-8'));

        return preg_replace_callback(
            '/\[([^\[\]]{1,200})\]\((https?:\/\/[^\s()]{1,500}|mailto:[^\s()]{1,200})\)/i',
            static function (array $coincidencia): string {
                return '<a href="' . htmlspecialchars($coincidencia[2], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">'
                    . $coincidencia[1] . '</a>';
            },
            $escapado
        );
    }
}
