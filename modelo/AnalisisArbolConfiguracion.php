<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Estado por defecto (desplegado/recogido) de las listas de cada pestaña árbol de ?ruta=analisis
 * ('pdi', 'programacion', 'proyectos'). Sin fila guardada = desplegado (comportamiento de siempre).
 */
class AnalisisArbolConfiguracion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerExpandido(string $pestana): bool
    {
        $consulta = $this->db->prepare('SELECT expandido FROM analisis_arbol_configuracion WHERE pestana = :pestana');
        $consulta->execute(['pestana' => $pestana]);
        $fila = $consulta->fetch();

        return $fila === false || (int) $fila['expandido'] === 1;
    }

    public function guardarExpandido(string $pestana, bool $expandido, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO analisis_arbol_configuracion (pestana, expandido, actualizado_por)
             VALUES (:pestana, :expandido, :usuario_id)
             ON DUPLICATE KEY UPDATE expandido = VALUES(expandido), actualizado_por = VALUES(actualizado_por)'
        );

        return $consulta->execute([
            'pestana' => $pestana,
            'expandido' => $expandido ? 1 : 0,
            'usuario_id' => $usuarioId,
        ]);
    }
}
