<?php

require_once __DIR__ . '/EnvioLote.php';
require_once __DIR__ . '/Gasto.php';
require_once __DIR__ . '/GastoExtension.php';
require_once __DIR__ . '/GastoPostgrado.php';
require_once __DIR__ . '/GastoUnisalud.php';
require_once __DIR__ . '/GastoSinExcedentes.php';
require_once __DIR__ . '/IngresoExtension.php';
require_once __DIR__ . '/IngresoPostgrado.php';
require_once __DIR__ . '/IngresoUnisalud.php';
require_once __DIR__ . '/IngresoSinExcedentes.php';
require_once __DIR__ . '/Sede.php';
require_once __DIR__ . '/Linea.php';
require_once __DIR__ . '/Motor.php';
require_once __DIR__ . '/Proyecto.php';
require_once __DIR__ . '/Rubro.php';
require_once __DIR__ . '/Usuario.php';
require_once __DIR__ . '/Rol.php';

/**
 * Convierte lotes de envío (EnvioLote) en datos listos para pintar: cada fila de la foto congelada
 * resuelta a las mismas columnas legibles que la tabla del módulo (sede, línea, motor... en
 * texto, no ids), comparada contra la fila en vivo para marcar lo que cambió, más el total actual
 * del lote y el techo congelado con su saldo recalculado. Compartido entre el landing del módulo
 * (lote completo) y Historial (solo el cambio de un ítem), para no duplicar esta lógica.
 */
class VistaLoteEnvio
{
    private const NOMBRES_MESES = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    private EnvioLote $modeloEnvioLote;
    private ?array $mapas = null;

    public function __construct()
    {
        $this->modeloEnvioLote = new EnvioLote();
    }

    /**
     * Para el landing: cada lote con todas sus filas, sus cambios, total y techo.
     */
    public function construirLotes(string $origen, array $lotes): array
    {
        $definicion = $this->definicion($origen);
        $vistas = [];

        foreach ($lotes as $lote) {
            $filasVista = [];
            $cambios = [];
            $totalActual = 0.0;

            foreach ($lote['filas'] as $indiceFila => $filaLote) {
                $resuelta = $this->resolverFila($definicion, $filaLote, $indiceFila);
                $totalActual += $resuelta['valorActual'];
                $filasVista[] = $resuelta['filaVista'];

                if ($resuelta['cambio'] !== null) {
                    $cambios[] = $resuelta['cambio'];
                }
            }

            $vistas[] = $this->cabeceraVista($lote, $totalActual) + [
                'cantidadItems' => count($lote['filas']),
                'editado' => !empty($cambios),
                'headers' => $definicion['headers'],
                'filas' => $filasVista,
                'cambios' => $cambios,
            ];
        }

        return $vistas;
    }

    /**
     * Para Historial: solo la fila de ese ítem (su comparación, si cambió), más el total actual y
     * el techo del lote — sin las demás filas.
     */
    public function construirParaHistorial(string $origen, int $origenFilaId): ?array
    {
        if (!$this->soportaOrigen($origen)) {
            return null;
        }

        $loteDeFila = $this->modeloEnvioLote->obtenerLotePorFila($origen, $origenFilaId);

        if ($loteDeFila === null) {
            return null;
        }

        $lote = $this->modeloEnvioLote->obtenerPorId((int) $loteDeFila['id']);
        $definicion = $this->definicion($origen);
        $totalActual = 0.0;
        $resueltaItem = null;

        foreach ($lote['filas'] as $indiceFila => $filaLote) {
            $resuelta = $this->resolverFila($definicion, $filaLote, $indiceFila);
            $totalActual += $resuelta['valorActual'];

            if ((int) $filaLote['origen_fila_id'] === $origenFilaId) {
                $resueltaItem = $resuelta;
            }
        }

        return $this->cabeceraVista($lote, $totalActual) + [
            'cantidadItems' => count($lote['filas']),
            'eliminada' => $resueltaItem !== null && $resueltaItem['filaVista']['eliminada'],
            'cambioFila' => $resueltaItem['cambio'] ?? null,
        ];
    }

    /**
     * Una hoja de Excel por lote (para GeneradorXlsx::descargarHojas()), en orden cronológico:
     * cabecera del lote, cambios detectados (fila completa "Al enviar"/"Actual", en color), una fila
     * en blanco, los encabezados otra vez, la tabla completa (celdas cambiadas en color) y al final
     * el total actual del lote y el techo congelado con su saldo — mismo orden que el landing.
     * Recibe lo que devuelve construirLotes().
     */
    public function hojasExcel(array $lotesVista, array $nombresReservados = []): array
    {
        $hojas = [];
        $nombresUsados = array_fill_keys(array_map('mb_strtolower', $nombresReservados), true);
        $celdaResaltable = static fn (array $celda) => $celda['clase'] !== '' ? ['valor' => $celda['valor'], 'estilo' => 2] : $celda['valor'];

        foreach (array_reverse($lotesVista) as $lote) {
            $headers = $lote['headers'];
            $previas = [
                [['valor' => 'Enviado v' . $lote['version'] . ' — ' . $lote['dependencia'], 'estilo' => 4]],
                ['Enviado el ' . date('d/m/Y H:i', strtotime($lote['enviadoEn'])) . ' → ' . $lote['destinatarioTexto']],
                [],
                [['valor' => 'CAMBIOS DETECTADOS DESPUÉS DE ENVIAR', 'estilo' => 2]],
            ];

            if (empty($lote['cambios'])) {
                $previas[] = ['Sin cambios desde el envío.'];
            }

            foreach ($lote['cambios'] as $cambio) {
                $previas[] = [['valor' => $cambio['titulo'], 'estilo' => 4]];
                $previas[] = array_map(static fn (string $encabezado): array => ['valor' => $encabezado, 'estilo' => 1], array_merge(['Versión'], $headers));
                $previas[] = array_merge(['Al enviar'], $cambio['alEnviar']);
                $previas[] = $cambio['actual'] === null
                    ? ['Actual', ['valor' => 'Eliminada', 'estilo' => 2]]
                    : array_merge(['Actual'], array_map($celdaResaltable, $cambio['actual']));
            }

            $previas[] = [];

            $filas = [];
            foreach ($lote['filas'] as $fila) {
                $estado = $fila['eliminada'] ? 'Eliminada' : ($fila['editada'] ? 'Editado' : 'Sin cambios');
                $filas[] = array_merge(
                    [$fila['eliminada'] || $fila['editada'] ? ['valor' => $estado, 'estilo' => 2] : $estado],
                    array_map($celdaResaltable, $fila['celdas'])
                );
            }

            $columnas = count($headers) + 1;
            $indiceValorTotal = array_search('Valor total', $headers, true);
            $columnaValor = $indiceValorTotal !== false ? $indiceValorTotal + 1 : $columnas - 1;

            $filaTotal = array_fill(0, $columnas, ['valor' => '', 'estilo' => 3]);
            $filaTotal[$columnaValor - 1] = ['valor' => 'TOTAL LOTE (actual)', 'estilo' => 3];
            $filaTotal[$columnaValor] = ['valor' => $lote['totalLoteFormateado'], 'estilo' => 3];

            $filaTecho = array_fill(0, $columnas, '');
            $filaTecho[$columnaValor - 1] = ['valor' => 'Techo asignado en ese momento (saldo actual)', 'estilo' => 4];
            $filaTecho[$columnaValor] = $lote['techoFormateado'] ?? 'Sin techo asignado';

            $filas[] = $filaTotal;
            $filas[] = $filaTecho;

            $hojas[] = [
                'nombre' => $this->nombreHojaUnico('Enviado v' . $lote['version'] . ' ' . $lote['dependencia'], $nombresUsados),
                'encabezados' => array_merge(['Estado'], $headers),
                'filasPrevias' => $previas,
                'filas' => $filas,
            ];
        }

        return $hojas;
    }

    /**
     * Excel exige nombres de hoja únicos, de máximo 31 caracteres y sin : \ / ? * [ ].
     */
    private function nombreHojaUnico(string $nombre, array &$usados): string
    {
        $base = mb_substr(trim(preg_replace('/[:\\\\\/?*\[\]]/u', ' ', $nombre)), 0, 31);
        $candidato = $base;
        $sufijo = 2;

        while (isset($usados[mb_strtolower($candidato)])) {
            $extra = ' (' . $sufijo++ . ')';
            $candidato = mb_substr($base, 0, 31 - mb_strlen($extra)) . $extra;
        }

        $usados[mb_strtolower($candidato)] = true;

        return $candidato;
    }

    private const MODELOS_GASTO = [
        'gasto_extension' => GastoExtension::class,
        'gasto_postgrado' => GastoPostgrado::class,
        'gasto_unisalud' => GastoUnisalud::class,
        'gasto_sin_excedentes' => GastoSinExcedentes::class,
    ];

    private const MODELOS_INGRESO = [
        'ingreso_extension' => IngresoExtension::class,
        'ingreso_postgrado' => IngresoPostgrado::class,
        'ingreso_unisalud' => IngresoUnisalud::class,
        'ingreso_sin_excedentes' => IngresoSinExcedentes::class,
    ];

    public function soportaOrigen(string $origen): bool
    {
        return $origen === 'gasto_principal' || isset(self::MODELOS_GASTO[$origen]) || isset(self::MODELOS_INGRESO[$origen]);
    }

    private function resolverFila(array $definicion, array $filaLote, int $indiceFila): array
    {
        $datosCongelados = $filaLote['datos'];
        $filaActual = ($definicion['filaActual'])((int) $filaLote['origen_fila_id']);
        $comparacion = $this->modeloEnvioLote->compararFila($datosCongelados, $filaActual);
        $celdasAlEnviar = ($definicion['etiquetas'])($datosCongelados);
        $titulo = 'Fila ' . ($indiceFila + 1) . ' — ' . ($datosCongelados[$definicion['campoDescripcion']] ?? '');

        if ($comparacion['eliminada']) {
            return [
                'valorActual' => 0.0,
                'filaVista' => [
                    'celdas' => array_map(static fn (string $valor): array => ['valor' => $valor, 'clase' => ''], $celdasAlEnviar),
                    'editada' => false,
                    'eliminada' => true,
                    'origenId' => (int) $filaLote['origen_fila_id'],
                ],
                'cambio' => ['titulo' => $titulo, 'headers' => $definicion['headers'], 'alEnviar' => $celdasAlEnviar, 'actual' => null],
            ];
        }

        $celdasVista = [];
        foreach (($definicion['etiquetas'])($filaActual) as $indice => $valor) {
            $celdasVista[] = ['valor' => $valor, 'clase' => $valor !== $celdasAlEnviar[$indice] ? 'celda-cambiada' : ''];
        }

        return [
            'valorActual' => (float) ($filaActual[$definicion['campoValor']] ?? 0),
            'filaVista' => [
                'celdas' => $celdasVista,
                'editada' => $comparacion['editada'],
                'eliminada' => false,
                'origenId' => (int) $filaLote['origen_fila_id'],
            ],
            'cambio' => $comparacion['editada']
                ? ['titulo' => $titulo, 'headers' => $definicion['headers'], 'alEnviar' => $celdasAlEnviar, 'actual' => $celdasVista]
                : null,
        ];
    }

    private function cabeceraVista(array $lote, float $totalActual): array
    {
        $techoFormateado = null;
        if ($lote['techo_numero'] !== null) {
            $saldo = (float) $lote['techo_numero'] - $totalActual;
            $techoFormateado = $this->moneda((float) $lote['techo_numero']) . ' (saldo ' . $this->moneda($saldo) . ')';
        }

        $rol = !empty($lote['rol_destinatario_id']) ? (new Rol())->obtenerPorId((int) $lote['rol_destinatario_id']) : null;
        $usuario = !empty($lote['usuario_destinatario_id']) ? (new Usuario())->obtenerPorId((int) $lote['usuario_destinatario_id']) : null;
        $destinatario = trim(($rol['nombre'] ?? '') . ($usuario !== null ? ' · ' . $usuario['nombre'] : ''));

        return [
            'id' => (int) $lote['id'],
            'version' => (int) $lote['version'],
            'dependencia' => $lote['dependencia'],
            'enviadoEn' => $lote['enviado_en'],
            'destinatarioTexto' => $destinatario !== '' ? $destinatario : '—',
            'totalLoteFormateado' => $this->moneda($totalActual),
            'techoFormateado' => $techoFormateado,
        ];
    }

    private function definicion(string $origen): array
    {
        if ($origen === 'gasto_principal') {
            return $this->definicionGasto(new Gasto(), false);
        }

        if (isset(self::MODELOS_GASTO[$origen])) {
            $clase = self::MODELOS_GASTO[$origen];

            return $this->definicionGasto(new $clase(), true);
        }

        if (isset(self::MODELOS_INGRESO[$origen])) {
            $clase = self::MODELOS_INGRESO[$origen];

            return $this->definicionIngreso(new $clase());
        }

        throw new InvalidArgumentException('Origen sin vista de lote: ' . $origen);
    }

    /**
     * Gastos/egresos: misma forma en los 5 módulos (gasto_principal + los 4 de Autogestión), salvo
     * que estos últimos agregan la columna "Categoría" (Excedentes/Gastos/Inversiones).
     */
    private function definicionGasto(object $modelo, bool $conCategoria): array
    {
        $mapas = $this->mapas();
        $headersBase = ['Sede', 'Dependencia', 'Línea estratégica', 'Motor de desarrollo', 'Proyecto PDI', 'Contratos comunes', 'Actividad', 'Rubro', 'Insumo', 'Cantidad', 'Costo unitario', 'Valor total', 'Meses'];

        return [
            'headers' => $conCategoria ? array_merge(['Categoría'], $headersBase) : $headersBase,
            'campoValor' => 'valor_total',
            'campoDescripcion' => 'insumo',
            'filaActual' => static fn (int $id): ?array => $modelo->obtenerPorId($id),
            'etiquetas' => function (array $datos) use ($mapas, $conCategoria): array {
                $celdas = [
                    $this->codigoNombre($mapas['sedes'], $datos['sede_id'] ?? null, 'nombre'),
                    $datos['dependencia'] ?? '',
                    $this->codigoNombre($mapas['lineas'], $datos['linea_id'] ?? null, 'nombre'),
                    $this->codigoNombre($mapas['motores'], $datos['motor_id'] ?? null, 'nombre'),
                    $this->codigoNombre($mapas['proyectos'], $datos['proyecto_id'] ?? null, 'nombre'),
                    $datos['objeto_proyecto_paa'] ?? '',
                    $datos['actividad'] ?? '',
                    !empty($datos['rubro_id'])
                        ? $this->codigoNombre($mapas['rubros'], $datos['rubro_id'], 'descripcion')
                        : ($datos['rubro_texto'] ?? '—'),
                    $datos['insumo'] ?? '',
                    (string) ($datos['cantidad'] ?? ''),
                    $this->moneda((float) ($datos['costo_unitario'] ?? 0)),
                    $this->moneda((float) ($datos['valor_total'] ?? 0)),
                    $this->meses($datos['meses'] ?? ''),
                ];

                return $conCategoria ? array_merge([$datos['categoria'] ?? '—'], $celdas) : $celdas;
            },
        ];
    }

    /**
     * Ingresos de Autogestión (Extensión/Postgrado/Unisalud/SinExcedentes): forma mucho más simple
     * que un gasto — sin catálogos que resolver. La foto congelada (SELECT * de la tabla) no incluye
     * el desglose de "conceptos" (viene de una tabla hija vía JOIN en las consultas normales del
     * módulo) — el snapshot y su comparación solo cubren estas 4 columnas planas.
     */
    private function definicionIngreso(object $modelo): array
    {
        return [
            'headers' => ['Dependencia', 'Concepto adicional', 'Valor adicional', 'Valor total'],
            'campoValor' => 'valor_total',
            'campoDescripcion' => 'dependencia',
            'filaActual' => static fn (int $id): ?array => $modelo->obtenerPorId($id),
            'etiquetas' => function (array $datos): array {
                return [
                    $datos['dependencia'] ?? '',
                    ($datos['concepto_adicional'] ?? '') !== '' ? $datos['concepto_adicional'] : '—',
                    $this->moneda((float) ($datos['valor_adicional'] ?? 0)),
                    $this->moneda((float) ($datos['valor_total'] ?? 0)),
                ];
            },
        ];
    }

    private function mapas(): array
    {
        if ($this->mapas === null) {
            $this->mapas = [
                'sedes' => array_column((new Sede())->obtenerTodas(), null, 'id'),
                'lineas' => array_column((new Linea())->obtenerTodas(), null, 'id'),
                'motores' => array_column((new Motor())->obtenerTodos(), null, 'id'),
                'proyectos' => array_column((new Proyecto())->obtenerTodos(), null, 'id'),
                'rubros' => array_column((new Rubro())->obtenerTodos(), null, 'id'),
            ];
        }

        return $this->mapas;
    }

    private function codigoNombre(array $mapa, $id, string $campoNombre): string
    {
        $fila = $id !== null ? ($mapa[(int) $id] ?? null) : null;

        return $fila !== null ? $fila['codigo'] . ' - ' . $fila[$campoNombre] : '—';
    }

    private function meses(?string $meses): string
    {
        if ($meses === null || $meses === '') {
            return '';
        }

        return implode(', ', array_map(static fn ($mes) => self::NOMBRES_MESES[(int) $mes] ?? $mes, explode(',', $meses)));
    }

    private function moneda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
