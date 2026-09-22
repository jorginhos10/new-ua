<?php

require_once __DIR__ . '/../config/conexion.php';

class AutogestionPorcentaje
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerPorModulo(string $modulo): array
    {
        $consulta = $this->db->prepare(
            'SELECT modulo, tope, costos, inversiones, excedentes, contribucion_postgrado FROM autogestion_porcentajes WHERE modulo = :modulo'
        );
        $consulta->execute(['modulo' => $modulo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : ['modulo' => $modulo, 'tope' => null, 'costos' => 0, 'inversiones' => 0, 'excedentes' => 0, 'contribucion_postgrado' => 0];
    }

    /**
     * Tope único del módulo (Extensión, Postgrado) — a diferencia de costos/inversiones/excedentes,
     * que Extensión y Postgrado configuran por ítem, el tope es un solo número por módulo: es el
     * denominador de la tarjeta de Autogestión/Postgrado en el Dashboard (ver
     * DashboardControlador::obtenerResumenIngresos()).
     */
    public function obtenerTopePorModulo(string $modulo): float
    {
        $consulta = $this->db->prepare('SELECT tope FROM autogestion_porcentajes WHERE modulo = :modulo');
        $consulta->execute(['modulo' => $modulo]);
        $tope = $consulta->fetchColumn();

        return $tope !== false && $tope !== null ? (float) $tope : 0.0;
    }

    public function guardarTope(string $modulo, ?float $tope): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO autogestion_porcentajes (modulo, tope) VALUES (:modulo, :tope)
             ON DUPLICATE KEY UPDATE tope = :tope2'
        );

        return $consulta->execute(['modulo' => $modulo, 'tope' => $tope, 'tope2' => $tope]);
    }

    public function guardar(string $modulo, ?float $costos, ?float $inversiones, ?float $excedentes, ?float $contribucionPostgrado = null): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO autogestion_porcentajes (modulo, costos, inversiones, excedentes, contribucion_postgrado)
             VALUES (:modulo, :costos, :inversiones, :excedentes, :contribucion_postgrado)
             ON DUPLICATE KEY UPDATE costos = :costos2, inversiones = :inversiones2, excedentes = :excedentes2, contribucion_postgrado = :contribucion_postgrado2'
        );

        return $consulta->execute([
            'modulo' => $modulo,
            'costos' => $costos,
            'inversiones' => $inversiones,
            'excedentes' => $excedentes,
            'contribucion_postgrado' => $contribucionPostgrado,
            'costos2' => $costos,
            'inversiones2' => $inversiones,
            'excedentes2' => $excedentes,
            'contribucion_postgrado2' => $contribucionPostgrado,
        ]);
    }
}
