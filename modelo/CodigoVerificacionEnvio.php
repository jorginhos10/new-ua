<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Códigos de 6 dígitos que valida la dependencia superadmin (es_raiz_superadmin) antes de "Enviar
 * todos los gastos en borrador" — ella puede enviar sin haber llegado a su techo, y por eso cada
 * envío se confirma con un código que llega al correo de quien envía (ver
 * GastoControlador::solicitarCodigoEnvio() / enviarTodo()). Mismo esquema que
 * RecuperacionPassword, en su propia tabla para que un código de una cosa nunca sirva para la otra.
 */
class CodigoVerificacionEnvio
{
    /** Intentos fallidos permitidos por código antes de invalidarlo. */
    public const MAX_INTENTOS = 5;

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function crear(int $usuarioId, string $codigo, string $correo, string $expiraEn): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO codigos_verificacion_envio (usuario_id, codigo, correo, expira_en) VALUES (:usuario_id, :codigo, :correo, :expira_en)'
        );

        return $consulta->execute([
            'usuario_id' => $usuarioId,
            'codigo' => $codigo,
            'correo' => $correo,
            'expira_en' => $expiraEn,
        ]);
    }

    /** Solo el último código solicitado queda válido. */
    public function invalidarPendientes(int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE codigos_verificacion_envio SET usado = 1 WHERE usuario_id = :usuario_id AND usado = 0'
        );

        return $consulta->execute(['usuario_id' => $usuarioId]);
    }

    /** Segundos desde el último código pedido por el usuario (null si nunca pidió) — para no reenviar en ráfaga. */
    public function segundosDesdeUltimo(int $usuarioId): ?int
    {
        $consulta = $this->db->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, MAX(creado_en), NOW()) FROM codigos_verificacion_envio WHERE usuario_id = :usuario_id'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
        $segundos = $consulta->fetchColumn();

        return $segundos !== null && $segundos !== false ? (int) $segundos : null;
    }

    /**
     * Valida el código contra el último pendiente y vigente del usuario. Si coincide, lo marca
     * usado y devuelve la fila (con el correo al que se envió); si no, suma un intento fallido
     * (al llegar a MAX_INTENTOS el código se invalida) y devuelve null.
     */
    public function validarYConsumir(int $usuarioId, string $codigo): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT * FROM codigos_verificacion_envio
             WHERE usuario_id = :usuario_id AND usado = 0 AND expira_en >= NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        if (!hash_equals((string) $fila['codigo'], $codigo)) {
            // MySQL evalúa las asignaciones de izquierda a derecha: en IF() `intentos` ya viene sumado.
            $this->db->prepare(
                'UPDATE codigos_verificacion_envio
                 SET intentos = intentos + 1, usado = IF(intentos >= :maximo, 1, usado)
                 WHERE id = :id'
            )->execute(['maximo' => self::MAX_INTENTOS, 'id' => (int) $fila['id']]);

            return null;
        }

        $this->db->prepare('UPDATE codigos_verificacion_envio SET usado = 1 WHERE id = :id')->execute(['id' => (int) $fila['id']]);

        return $fila;
    }
}
