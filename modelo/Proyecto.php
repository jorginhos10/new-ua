<?php

require_once __DIR__ . '/../config/conexion.php';

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
            'SELECT p.id, p.codigo, p.nit, p.nombre, p.motor_id, p.creado_en,
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
            'SELECT p.id, p.codigo, p.nit, p.nombre, p.motor_id, p.creado_en,
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
     * El NIT es el código numérico de 6 dígitos del PDI (línea+motor+proyecto, 2 dígitos cada
     * uno), calculado a partir de los códigos "L1"/"M1"/"P1" ya existentes de cada nivel.
     */
    public function crear(string $codigo, string $nombre, int $motorId): bool
    {
        $motor = $this->db->prepare(
            'SELECT m.codigo AS motor_codigo, l.codigo AS linea_codigo FROM motores m JOIN lineas l ON l.id = m.linea_id WHERE m.id = :motor_id'
        );
        $motor->execute(['motor_id' => $motorId]);
        $filaMotor = $motor->fetch();

        $nit = $filaMotor !== false
            ? $this->calcularNit($filaMotor['linea_codigo'], $filaMotor['motor_codigo'], $codigo)
            : null;

        $consulta = $this->db->prepare(
            'INSERT INTO proyectos (codigo, nit, nombre, motor_id) VALUES (:codigo, :nit, :nombre, :motor_id)'
        );

        return $consulta->execute(['codigo' => $codigo, 'nit' => $nit, 'nombre' => $nombre, 'motor_id' => $motorId]);
    }

    private function calcularNit(string $lineaCodigo, string $motorCodigo, string $proyectoCodigo): string
    {
        $numero = static fn (string $codigo): string => str_pad((string) preg_replace('/\D/', '', $codigo), 2, '0', STR_PAD_LEFT);

        return $numero($lineaCodigo) . $numero($motorCodigo) . $numero($proyectoCodigo);
    }
}
