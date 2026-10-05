<?php

/**
 * Filas de las tablas de Análisis y de Peticiones (detalle por tipo) como datos, para que
 * publico/js/tabla-real.js dibuje solo las visibles. Antes el servidor escribía cada <tr> en el HTML
 * (miles de nodos); ahora entrega un JSON con el mismo contenido: cada celda es el texto que se
 * mostraba, sin HTML (las celdas nunca tuvieron markup).
 */
class FilasTabla
{
    /**
     * @param array<int, array<string, mixed>> $filasCompletas filas ya armadas por el controlador
     * @param array<int, string>               $clavesFila     clave de cada columna de datos
     * @return array<int, array{id: int, editar: string, puede: bool, resaltada: bool, celdas: string[]}>
     */
    public static function paraJson(array $filasCompletas, array $clavesFila, ?int $resaltarId = null): array
    {
        $filas = [];

        foreach ($filasCompletas as $filaCompleta) {
            $celdas = [];
            foreach ($clavesFila as $clave) {
                $valor = $filaCompleta[$clave] ?? '—';
                if (is_float($valor)) {
                    $valor = number_format($valor, 2, ',', '.');
                }
                $celdas[] = (string) $valor;
            }

            $id = (int) $filaCompleta['origen_id'];
            $filas[] = [
                'id' => $id,
                'editar' => (string) ($filaCompleta['ruta_editar'] ?? ''),
                'puede' => !empty($filaCompleta['puede_editar']),
                'resaltada' => $resaltarId !== null && $id === $resaltarId,
                'celdas' => $celdas,
            ];
        }

        return $filas;
    }
}
