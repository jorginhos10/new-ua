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
            'SELECT modulo, costos, inversiones, excedentes, contribucion_postgrado FROM autogestion_porcentajes WHERE modulo = :modulo'
        );
        $consulta->execute(['modulo' => $modulo]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : ['modulo' => $modulo, 'costos' => 0, 'inversiones' => 0, 'excedentes' => 0, 'contribucion_postgrado' => 0];
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
