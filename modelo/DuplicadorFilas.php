<?php

/**
 * Helper compartido para duplicar filas por introspección de esquema, usado por los controladores
 * que agregan "Duplicar" en la barra de acciones de su propio listado (no confundir con la
 * duplicación de PeticionesControlador::duplicarConsolidadoGrupo(), que opera sobre items ya
 * consolidados/archivados y se deja intacta).
 */
class DuplicadorFilas
{
    public static function duplicarFila(PDO $db, string $tabla, int $id, array $excluir = ['id', 'creado_en', 'actualizado_en']): ?int
    {
        $columnas = array_values(array_diff(self::obtenerColumnas($db, $tabla), $excluir));

        if (empty($columnas)) {
            return null;
        }

        $listaColumnas = implode(', ', $columnas);

        $consulta = $db->prepare(
            "INSERT INTO {$tabla} ({$listaColumnas}) SELECT {$listaColumnas} FROM {$tabla} WHERE id = :id"
        );
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0 ? (int) $db->lastInsertId() : null;
    }

    public static function duplicarFilasHijo(PDO $db, string $tablaHijo, string $campoFk, int $idViejo, int $idNuevo, array $excluir = ['id']): void
    {
        $excluirFk = array_merge($excluir, [$campoFk]);
        $columnas = array_values(array_diff(self::obtenerColumnas($db, $tablaHijo), $excluirFk));

        $consultaHijos = $db->prepare("SELECT * FROM {$tablaHijo} WHERE {$campoFk} = :id");
        $consultaHijos->execute(['id' => $idViejo]);

        $listaColumnas = implode(', ', array_merge([$campoFk], $columnas));
        $listaMarcadores = implode(', ', array_map(static fn (string $c): string => ':' . $c, array_merge([$campoFk], $columnas)));
        $insertar = $db->prepare("INSERT INTO {$tablaHijo} ({$listaColumnas}) VALUES ({$listaMarcadores})");

        foreach ($consultaHijos->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $valores = [$campoFk => $idNuevo];

            foreach ($columnas as $columna) {
                $valores[$columna] = $fila[$columna];
            }

            $insertar->execute($valores);
        }
    }

    private static function obtenerColumnas(PDO $db, string $tabla): array
    {
        $consulta = $db->query("SHOW COLUMNS FROM {$tabla}");

        return array_column($consulta->fetchAll(), 'Field');
    }
}
