<?php

require_once __DIR__ . '/../config/conexion.php';

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function buscarPorCorreo(string $correo): array|false
    {
        $consulta = $this->db->prepare(
            'SELECT u.*, e.nombre AS estamento_nombre, d.nombre AS dependencia_nombre, r.nombre AS rol_nombre
             FROM usuarios u
             LEFT JOIN estamentos e ON e.id = u.estamento_id
             LEFT JOIN dependencias d ON d.id = u.dependencia_id
             LEFT JOIN roles r ON r.id = u.rol_id
             WHERE u.correo = :correo LIMIT 1'
        );
        $consulta->execute(['correo' => $correo]);

        return $consulta->fetch();
    }

    public function verificarCredenciales(string $correo, string $password): array|false
    {
        $datos = $this->buscarPorCorreo($correo);

        if ($datos && password_verify($password, $datos['password'])) {
            return $datos;
        }

        return false;
    }

    public function existeCorreo(string $correo, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->db->prepare('SELECT id FROM usuarios WHERE correo = :correo AND id != :id LIMIT 1');
            $consulta->execute(['correo' => $correo, 'id' => $ignorarId]);

            return $consulta->fetch() !== false;
        }

        return $this->buscarPorCorreo($correo) !== false;
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, nombre, correo, rol, rol_id, dependencia_id, estamento_id, menu_personalizado, es_super_admin, creado_en FROM usuarios WHERE id = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Igual que obtenerPorId(), pero con los nombres ya resueltos (rol, dependencia, estamento) —
     * la misma forma que deja en $_SESSION el login normal. La usa la impersonación de usuarios
     * (Usuarios > Administradores > "Ingresar como") para reconstruir esas claves de sesión sin
     * pasar por verificarCredenciales(), que exige contraseña.
     */
    public function obtenerPorIdConNombres(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT u.id, u.nombre, u.correo, u.rol, u.rol_id, u.dependencia_id, u.estamento_id, u.es_super_admin,
                    r.nombre AS rol_nombre, d.nombre AS dependencia_nombre, e.nombre AS estamento_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.rol_id
             LEFT JOIN dependencias d ON d.id = u.dependencia_id
             LEFT JOIN estamentos e ON e.id = u.estamento_id
             WHERE u.id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function obtenerPorDependenciaYRol(int $dependenciaId, int $rolId): array
    {
        $consulta = $this->db->prepare(
            'SELECT id, nombre, correo FROM usuarios WHERE dependencia_id = :dependencia_id AND rol_id = :rol_id'
        );
        $consulta->execute(['dependencia_id' => $dependenciaId, 'rol_id' => $rolId]);

        return $consulta->fetchAll();
    }

    public function obtenerPorRolId(int $rolId): array
    {
        $consulta = $this->db->prepare(
            'SELECT id, nombre, correo FROM usuarios WHERE rol_id = :rol_id'
        );
        $consulta->execute(['rol_id' => $rolId]);

        return $consulta->fetchAll();
    }

    /**
     * Mapa [nombre de dependencia][rol_id] => [usuarios], para mostrar en el frontend a quién se
     * le va a enviar/redireccionar algo antes de confirmar, sin ir al servidor. Cada usuario trae
     * también el nombre de su rol y su correo, para poder mostrar en un solo campo combinado
     * "Rol · Nombre (correo)" al elegir un destinatario específico.
     */
    public function obtenerMapaPorDependenciaYRol(): array
    {
        $consulta = $this->db->query(
            "SELECT d.nombre AS dependencia_nombre, u.rol_id, r.nombre AS rol_nombre,
                    u.id AS usuario_id, u.nombre AS usuario_nombre, u.correo AS usuario_correo
             FROM usuarios u
             JOIN dependencias d ON d.id = u.dependencia_id
             JOIN roles r ON r.id = u.rol_id
             WHERE u.rol_id IS NOT NULL"
        );

        $mapa = [];
        foreach ($consulta->fetchAll() as $fila) {
            $mapa[$fila['dependencia_nombre']][(string) $fila['rol_id']][] = [
                'id' => (int) $fila['usuario_id'],
                'nombre' => $fila['usuario_nombre'],
                'correo' => $fila['usuario_correo'],
                'rol_nombre' => $fila['rol_nombre'],
            ];
        }

        return $mapa;
    }

    public function crear(string $nombre, string $correo, string $password, string $rol, ?string $facultad = null, ?int $rolId = null, ?int $dependenciaId = null, ?int $estamentoId = null): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO usuarios (nombre, facultad, correo, password, rol, rol_id, dependencia_id, estamento_id) VALUES (:nombre, :facultad, :correo, :password, :rol, :rol_id, :dependencia_id, :estamento_id)'
        );

        return $consulta->execute([
            'nombre' => $nombre,
            'facultad' => $facultad,
            'correo' => $correo,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => $rol,
            'rol_id' => $rolId,
            'dependencia_id' => $dependenciaId,
            'estamento_id' => $estamentoId,
        ]);
    }

    public function obtenerPorRol(string $rol): array
    {
        $consulta = $this->db->prepare(
            'SELECT u.id, u.nombre, u.correo, u.rol, u.rol_id, u.dependencia_id, u.estamento_id, u.menu_personalizado, u.es_super_admin, u.creado_en, u.ultimo_acceso, r.nombre AS rol_catalogo, d.nombre AS dependencia_nombre, d.tipo AS dependencia_tipo, e.nombre AS estamento_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.rol_id
             LEFT JOIN dependencias d ON d.id = u.dependencia_id
             LEFT JOIN estamentos e ON e.id = u.estamento_id
             WHERE u.rol = :rol ORDER BY u.creado_en DESC'
        );
        $consulta->execute(['rol' => $rol]);

        return $consulta->fetchAll();
    }

    public function actualizar(int $id, string $nombre, string $correo, ?int $estamentoId = null): bool
    {
        $consulta = $this->db->prepare('UPDATE usuarios SET nombre = :nombre, correo = :correo, estamento_id = :estamento_id WHERE id = :id');

        return $consulta->execute(['id' => $id, 'nombre' => $nombre, 'correo' => $correo, 'estamento_id' => $estamentoId]);
    }

    public function actualizarPassword(int $id, string $password): bool
    {
        $consulta = $this->db->prepare('UPDATE usuarios SET password = :password WHERE id = :id');

        return $consulta->execute(['id' => $id, 'password' => password_hash($password, PASSWORD_DEFAULT)]);
    }

    public function registrarAcceso(int $id): bool
    {
        $consulta = $this->db->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function actualizarPermisos(int $id, ?int $rolId, ?int $dependenciaId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE usuarios SET rol_id = :rol_id, dependencia_id = :dependencia_id WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'rol_id' => $rolId, 'dependencia_id' => $dependenciaId]);
    }

    public function actualizarMenuPersonalizado(int $id, bool $personalizado): bool
    {
        $consulta = $this->db->prepare('UPDATE usuarios SET menu_personalizado = :valor WHERE id = :id');

        return $consulta->execute(['id' => $id, 'valor' => $personalizado ? 1 : 0]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function obtenerTodosExcepto(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            'SELECT id, nombre, correo FROM usuarios WHERE id != :id ORDER BY nombre'
        );
        $consulta->execute(['id' => $usuarioId]);

        return $consulta->fetchAll();
    }

    public function obtenerRecientes(int $limite = 5): array
    {
        $consulta = $this->db->prepare(
            'SELECT id, nombre, correo, rol, creado_en FROM usuarios ORDER BY creado_en DESC LIMIT :limite'
        );
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function obtenerRecientesPorDependencias(array $dependenciaIds, int $limite = 5): array
    {
        if (empty($dependenciaIds)) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($dependenciaIds), '?'));
        $consulta = $this->db->prepare(
            "SELECT id, nombre, correo, rol, creado_en FROM usuarios
             WHERE dependencia_id IN ($marcadores)
             ORDER BY creado_en DESC LIMIT $limite"
        );
        $consulta->execute(array_map('intval', $dependenciaIds));

        return $consulta->fetchAll();
    }
}
