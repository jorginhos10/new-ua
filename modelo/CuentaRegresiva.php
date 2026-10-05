<?php

/**
 * Cuenta regresiva de un periodo (reloj de arena). Cuenta desde las 00:00 del día de inicio hasta las
 * 23:59:59 del día de cierre (es decir, hasta las 00:00 del día siguiente). Con menos de dos días
 * faltantes, la cuenta pasa a horas. Los días son los de Colombia, sin depender de la zona horaria del
 * servidor.
 */
class CuentaRegresiva
{
    public static function calcular(string $fechaInicio, string $fechaCierre): array
    {
        $zona = new DateTimeZone('America/Bogota');
        $inicio = new DateTimeImmutable($fechaInicio . ' 00:00:00', $zona);
        $fin = (new DateTimeImmutable($fechaCierre . ' 00:00:00', $zona))->modify('+1 day');
        $ahora = new DateTimeImmutable('now', $zona);

        $segundosTotales = max(1, $fin->getTimestamp() - $inicio->getTimestamp());

        if ($ahora < $inicio) {
            $estado = 'pendiente';
            $segundosTranscurridos = 0;
            $segundosFaltantes = $segundosTotales;
            $segundosParaAbrir = $inicio->getTimestamp() - $ahora->getTimestamp();
        } elseif ($ahora >= $fin) {
            $estado = 'cerrado';
            $segundosTranscurridos = $segundosTotales;
            $segundosFaltantes = 0;
            $segundosParaAbrir = 0;
        } else {
            $estado = 'abierto';
            $segundosTranscurridos = $ahora->getTimestamp() - $inicio->getTimestamp();
            $segundosFaltantes = $fin->getTimestamp() - $ahora->getTimestamp();
            $segundosParaAbrir = 0;
        }

        [$faltante, $unidad] = self::formatear($segundosFaltantes);
        [$faltanteParaAbrir, $unidadParaAbrir] = self::formatear($segundosParaAbrir);

        return [
            'configurado' => true,
            'fecha_inicio' => $inicio->format('d/m/Y'),
            'fecha_cierre' => (new DateTimeImmutable($fechaCierre))->format('d/m/Y'),
            'faltante' => $faltante,
            'unidad' => $unidad,
            'faltante_para_abrir' => $faltanteParaAbrir,
            'unidad_para_abrir' => $unidadParaAbrir,
            'porcentaje_transcurrido' => min(100, ($segundosTranscurridos / $segundosTotales) * 100),
            'estado' => $estado,
        ];
    }

    /** @return array{0: int, 1: string} Cantidad y unidad (horas si faltan menos de dos días, si no días). */
    private static function formatear(int $segundos): array
    {
        if ($segundos < 2 * 86400) {
            $cantidad = intdiv($segundos, 3600);

            return [$cantidad, $cantidad === 1 ? 'hora' : 'horas'];
        }

        $cantidad = intdiv($segundos, 86400);

        return [$cantidad, $cantidad === 1 ? 'día' : 'días'];
    }
}
