<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Estado por defecto (desplegado/recogido) de las listas de cada pestaña árbol de ?ruta=analisis
 * ('pdi', 'programacion', 'proyectos'). Sin fila guardada = desplegado (comportamiento de siempre).
 * También guarda la fecha de corte de la columna "Año anterior (a corte)" que elige el admin.
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

    /**
     * Fecha de corte (Y-m-d) elegida por el admin para la columna "Año anterior (a corte)" de la
     * pestaña, o null si nadie la ha elegido (o si la columna aún no existe: instalar.php).
     */
    public function obtenerFechaCorte(string $pestana): ?string
    {
        try {
            $consulta = $this->db->prepare('SELECT fecha_corte FROM analisis_arbol_configuracion WHERE pestana = :pestana');
            $consulta->execute(['pestana' => $pestana]);
            $fila = $consulta->fetch();
        } catch (Throwable $excepcion) {
            return null;
        }

        return ($fila !== false && !empty($fila['fecha_corte'])) ? (string) $fila['fecha_corte'] : null;
    }

    public function guardarFechaCorte(string $pestana, string $fechaCorte, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO analisis_arbol_configuracion (pestana, fecha_corte, actualizado_por)
             VALUES (:pestana, :fecha_corte, :usuario_id)
             ON DUPLICATE KEY UPDATE fecha_corte = VALUES(fecha_corte), actualizado_por = VALUES(actualizado_por)'
        );

        return $consulta->execute([
            'pestana' => $pestana,
            'fecha_corte' => $fechaCorte,
            'usuario_id' => $usuarioId,
        ]);
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
