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
        } elseif ($ahora >= $fin) {
            $estado = 'cerrado';
            $segundosTranscurridos = $segundosTotales;
            $segundosFaltantes = 0;
        } else {
            $estado = 'abierto';
            $segundosTranscurridos = $ahora->getTimestamp() - $inicio->getTimestamp();
            $segundosFaltantes = $fin->getTimestamp() - $ahora->getTimestamp();
        }

        if ($segundosFaltantes < 2 * 86400) {
            $faltante = intdiv($segundosFaltantes, 3600);
            $unidad = $faltante === 1 ? 'hora' : 'horas';
        } else {
            $faltante = intdiv($segundosFaltantes, 86400);
            $unidad = $faltante === 1 ? 'día' : 'días';
        }

        return [
            'configurado' => true,
            'fecha_inicio' => $inicio->format('d/m/Y'),
            'fecha_cierre' => (new DateTimeImmutable($fechaCierre))->format('d/m/Y'),
            'faltante' => $faltante,
            'unidad' => $unidad,
            'porcentaje_transcurrido' => min(100, ($segundosTranscurridos / $segundosTotales) * 100),
            'estado' => $estado,
        ];
    }
}
