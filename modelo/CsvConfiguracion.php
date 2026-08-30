<?php

/**
 * Helper compartido de exportar/importar CSV para los catálogos de Configuraciones. UTF-8 con BOM
 * y separador ";" (soporta tildes/ñ correctamente en Excel), igual al patrón que ya usaba
 * PerfilProyectosControlador::exportar().
 */
class CsvConfiguracion
{
    public static function exportar(string $nombreArchivo, array $columnas, array $filas): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, $columnas, ';');

        foreach ($filas as $fila) {
            fputcsv($salida, $fila, ';');
        }

        fclose($salida);
        exit;
    }

    /**
     * Lee un CSV subido (UTF-8 con o sin BOM, separado por ";") y devuelve un array de filas
     * asociativas usando la primera línea como encabezados. Devuelve null si no se pudo leer.
     */
    public static function leerArchivoSubido(array $archivo): ?array
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $contenido = file_get_contents($archivo['tmp_name']);

        if ($contenido === false) {
            return null;
        }

        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $lineas = preg_split('/\r\n|\n|\r/', (string) $contenido);
        $lineas = array_values(array_filter($lineas, static fn (string $l): bool => trim($l) !== ''));

        if (empty($lineas)) {
            return null;
        }

        $encabezados = array_map('trim', str_getcsv(array_shift($lineas), ';'));

        $filas = [];
        foreach ($lineas as $linea) {
            $valores = str_getcsv($linea, ';');
            $fila = [];
            foreach ($encabezados as $indice => $encabezado) {
                $fila[$encabezado] = trim((string) ($valores[$indice] ?? ''));
            }
            $filas[] = $fila;
        }

        return $filas;
    }

    /**
     * Comprueba, vía information_schema, si algún registro en cualquier tabla de la base referencia
     * la fila $id de $tabla por llave foránea. Se usa antes de borrar filas de un catálogo al
     * sincronizar un CSV, en vez de confiar en que el DELETE lance una excepción: algunas FKs (ej.
     * usuarios.estamento_id) usan ON DELETE SET NULL, que no lanza nada y dejaría la referencia
     * huérfana en silencio si no se valida antes.
     */
    public static function tieneReferencias(PDO $db, string $tabla, int $id): bool
    {
        static $cachePorTabla = [];

        if (!isset($cachePorTabla[$tabla])) {
            $consulta = $db->prepare(
                'SELECT TABLE_NAME, COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                   AND REFERENCED_TABLE_NAME = :tabla'
            );
            $consulta->execute(['tabla' => $tabla]);
            $cachePorTabla[$tabla] = $consulta->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($cachePorTabla[$tabla] as $referencia) {
            $tablaRef = $referencia['TABLE_NAME'];
            $columnaRef = $referencia['COLUMN_NAME'];

            $consulta = $db->prepare(
                "SELECT 1 FROM `{$tablaRef}` WHERE `{$columnaRef}` = :id LIMIT 1"
            );
            $consulta->execute(['id' => $id]);

            if ($consulta->fetch() !== false) {
                return true;
            }
        }

        return false;
    }
}
