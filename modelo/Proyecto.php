<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/CsvConfiguracion.php';
require_once __DIR__ . '/Linea.php';
require_once __DIR__ . '/Motor.php';

class Proyecto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT p.id, p.codigo, p.nit, p.nombre, p.motor_id, p.estado, p.creado_en,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    l.id AS linea_id, l.codigo AS linea_codigo, l.nombre AS linea_nombre
             FROM proyectos p
             JOIN motores m ON m.id = p.motor_id
             JOIN lineas l ON l.id = m.linea_id
             ORDER BY l.id, m.id, p.id'
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT p.id, p.codigo, p.nit, p.nombre, p.motor_id, p.estado, p.creado_en,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    l.id AS linea_id, l.codigo AS linea_codigo, l.nombre AS linea_nombre
             FROM proyectos p
             JOIN motores m ON m.id = p.motor_id
             JOIN lineas l ON l.id = m.linea_id
             WHERE p.id = :id'
        );
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * El NIT (línea+motor+proyecto, 2 dígitos cada uno) es la referencia estable a un proyecto
     * que se usa en la plantilla de presupuesto institucional (?ruta=analisis) — a diferencia de
     * `codigo` ("P3"), que se repite entre motores distintos, el NIT es único en toda la tabla.
     */
    public function obtenerPorNit(string $nit): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT p.id, p.codigo, p.nit, p.nombre, p.motor_id, p.creado_en,
                    m.codigo AS motor_codigo, m.nombre AS motor_nombre,
                    l.id AS linea_id, l.codigo AS linea_codigo, l.nombre AS linea_nombre
             FROM proyectos p
             JOIN motores m ON m.id = p.motor_id
             JOIN lineas l ON l.id = m.linea_id
             WHERE p.nit = :nit'
        );
        $consulta->execute(['nit' => $nit]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /** `codigo` tampoco es único globalmente (se repite entre motores) — clave natural (motor_id, codigo). */
    public function obtenerPorMotorYCodigo(int $motorId, string $codigo): ?array
    {
        $consulta = $this->db->prepare('SELECT id, codigo, nit, nombre, motor_id, estado FROM proyectos WHERE motor_id = :motor_id AND codigo = :codigo LIMIT 1');
        $consulta->execute(['motor_id' => $motorId, 'codigo' => $codigo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * El NIT es el código numérico de 6 dígitos del PDI (línea+motor+proyecto, 2 dígitos cada
     * uno), calculado a partir de los códigos "L1"/"M1"/"P1" ya existentes de cada nivel.
     */
    public function crear(string $codigo, string $nombre, int $motorId): bool
    {
        $nit = $this->calcularNitDesdeMotor($motorId, $codigo);

        $consulta = $this->db->prepare(
            'INSERT INTO proyectos (codigo, nit, nombre, motor_id) VALUES (:codigo, :nit, :nombre, :motor_id)'
        );

        return $consulta->execute(['codigo' => $codigo, 'nit' => $nit, 'nombre' => $nombre, 'motor_id' => $motorId]);
    }

    /** El NIT se recalcula porque puede depender de `codigo` o de a qué motor/línea pertenece. */
    public function actualizar(int $id, string $codigo, string $nombre, int $motorId): bool
    {
        $nit = $this->calcularNitDesdeMotor($motorId, $codigo);

        $consulta = $this->db->prepare(
            'UPDATE proyectos SET codigo = :codigo, nit = :nit, nombre = :nombre, motor_id = :motor_id WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'codigo' => $codigo, 'nit' => $nit, 'nombre' => $nombre, 'motor_id' => $motorId]);
    }

    public function cambiarEstado(int $id): bool
    {
        $consulta = $this->db->prepare(
            "UPDATE proyectos SET estado = IF(estado = 'activo', 'inactivo', 'activo') WHERE id = :id"
        );

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM proyectos WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Mismo patrón que Linea/Motor (crear/actualizar/desactivar, nunca borra), resolviendo el
     * motor por (linea_codigo, motor_codigo) — ninguno de los dos es único por sí solo — igual
     * que Motor resuelve su línea. Filas cuyo motor no se pueda resolver se ignoran.
     */
    public function sincronizarDesdeCsv(array $filas): array
    {
        $modeloLinea = new Linea();
        $modeloMotor = new Motor();
        $codigosCsv = [];
        $creados = 0;
        $actualizados = 0;
        $ignorados = 0;

        foreach ($filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $lineaCodigo = trim($fila['linea_codigo'] ?? '');
            $motorCodigo = trim($fila['motor_codigo'] ?? '');

            if ($codigo === '' || $nombre === '' || $lineaCodigo === '' || $motorCodigo === '') {
                continue;
            }

            $linea = $modeloLinea->obtenerPorCodigo($lineaCodigo);
            $motor = $linea !== null ? $modeloMotor->obtenerPorCodigoYLinea($motorCodigo, (int) $linea['id']) : null;

            if ($motor === null) {
                $ignorados++;
                continue;
            }

            $motorId = (int) $motor['id'];
            $estadoDeseado = strtolower(trim($fila['estado'] ?? '')) === 'inactivo' ? 'inactivo' : 'activo';

            $existente = $this->obtenerPorMotorYCodigo($motorId, $codigo);
            $codigosCsv[] = $motorId . '|' . $codigo;

            if ($existente === null) {
                $this->crear($codigo, $nombre, $motorId);
                $nuevoId = (int) $this->db->lastInsertId();

                if ($estadoDeseado === 'inactivo') {
                    $this->cambiarEstado($nuevoId);
                }

                $creados++;
            } else {
                $cambio = false;

                if ($existente['nombre'] !== $nombre) {
                    $this->actualizar((int) $existente['id'], $codigo, $nombre, $motorId);
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
            if (in_array((int) $existente['motor_id'] . '|' . $existente['codigo'], $codigosCsv, true)) {
                continue;
            }

            if ($existente['estado'] === 'activo') {
                $this->cambiarEstado((int) $existente['id']);
                $desactivados++;
            }
        }

        return ['creados' => $creados, 'actualizados' => $actualizados, 'desactivados' => $desactivados, 'ignorados' => $ignorados];
    }

    private function calcularNitDesdeMotor(int $motorId, string $codigo): ?string
    {
        $motor = $this->db->prepare(
            'SELECT m.codigo AS motor_codigo, l.codigo AS linea_codigo FROM motores m JOIN lineas l ON l.id = m.linea_id WHERE m.id = :motor_id'
        );
        $motor->execute(['motor_id' => $motorId]);
        $filaMotor = $motor->fetch();

        return $filaMotor !== false
            ? $this->calcularNit($filaMotor['linea_codigo'], $filaMotor['motor_codigo'], $codigo)
            : null;
    }

    private function calcularNit(string $lineaCodigo, string $motorCodigo, string $proyectoCodigo): string
    {
        $numero = static fn (string $codigo): string => str_pad((string) preg_replace('/\D/', '', $codigo), 2, '0', STR_PAD_LEFT);

        return $numero($lineaCodigo) . $numero($motorCodigo) . $numero($proyectoCodigo);
    }
}
