<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Foto congelada de un "Enviar" (Gastos o Autogestión): cada clic de Enviar crea un lote versionado
 * (v1, v2... por origen/dependencia/año) con la foto de las filas enviadas en ese momento y el techo
 * presupuestal vigente entonces. Las ediciones posteriores de esas filas NO modifican esta foto —
 * ver `compararFila()` para la comparación en vivo al momento de mostrarla.
 */
class EnvioLote
{
    /**
     * Columnas que cambian por el simple hecho de enviarse (borrador→enviado, se asigna
     * destinatario) — no representan una edición humana y se excluyen de compararFila() para que
     * "Editado" solo se marque cuando alguien realmente cambió un valor de negocio.
     */
    private const CAMPOS_TRANSICION_ENVIO = ['estado', 'rol_destinatario_id', 'usuario_destinatario_id', 'dependencia_destino'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * $cabecera: anio_presupuestal_id, dependencia, ambito (string|null, ver sql/envios_lote.sql),
     * enviado_por, rol_destinatario_id, usuario_destinatario_id, total_lote, techo_numero
     * (float|null), techo_flexible_activo (bool), techo_categorias (array|null). $filas: filas crudas
     * de la tabla de origen (cada una debe traer 'id'); se guarda tal cual, como la foto "al enviar".
     * Devuelve ['id' => id del lote, 'version' => número de versión asignado], para que el llamador
     * pueda armar el detalle del historial ("Enviado como lote vN") sin otra consulta.
     */
    public function crear(string $origen, array $cabecera, array $filas): array
    {
        $this->db->beginTransaction();

        $ambito = $cabecera['ambito'] ?? null;
        $version = $this->siguienteVersion($origen, $cabecera['dependencia'], (int) $cabecera['anio_presupuestal_id'], $ambito);

        $consulta = $this->db->prepare(
            'INSERT INTO envios_lote
                (origen, anio_presupuestal_id, dependencia, ambito, version, enviado_por, rol_destinatario_id,
                 usuario_destinatario_id, total_lote, techo_numero, techo_flexible_activo, techo_categorias)
             VALUES
                (:origen, :anio_presupuestal_id, :dependencia, :ambito, :version, :enviado_por, :rol_destinatario_id,
                 :usuario_destinatario_id, :total_lote, :techo_numero, :techo_flexible_activo, :techo_categorias)'
        );
        $consulta->execute([
            'origen' => $origen,
            'anio_presupuestal_id' => $cabecera['anio_presupuestal_id'],
            'dependencia' => $cabecera['dependencia'],
            'ambito' => $ambito,
            'version' => $version,
            'enviado_por' => $cabecera['enviado_por'] ?? null,
            'rol_destinatario_id' => $cabecera['rol_destinatario_id'] ?? null,
            'usuario_destinatario_id' => $cabecera['usuario_destinatario_id'] ?? null,
            'total_lote' => $cabecera['total_lote'] ?? 0,
            'techo_numero' => $cabecera['techo_numero'] ?? null,
            'techo_flexible_activo' => !empty($cabecera['techo_flexible_activo']) ? 1 : 0,
            'techo_categorias' => isset($cabecera['techo_categorias']) ? json_encode($cabecera['techo_categorias']) : null,
        ]);
        $loteId = (int) $this->db->lastInsertId();

        $consultaFila = $this->db->prepare(
            'INSERT INTO envios_lote_filas (lote_id, origen_fila_id, datos_json) VALUES (:lote_id, :origen_fila_id, :datos_json)'
        );
        foreach ($filas as $fila) {
            $consultaFila->execute([
                'lote_id' => $loteId,
                'origen_fila_id' => (int) $fila['id'],
                'datos_json' => json_encode($fila),
            ]);
        }

        $this->db->commit();

        return ['id' => $loteId, 'version' => $version];
    }

    private function siguienteVersion(string $origen, string $dependencia, int $anioId, ?string $ambito): int
    {
        $consulta = $this->db->prepare(
            'SELECT COALESCE(MAX(version), 0) FROM envios_lote
             WHERE origen = :origen AND dependencia = :dependencia AND anio_presupuestal_id = :anio_presupuestal_id
                AND ambito <=> :ambito'
        );
        $consulta->execute(['origen' => $origen, 'dependencia' => $dependencia, 'anio_presupuestal_id' => $anioId, 'ambito' => $ambito]);

        return ((int) $consulta->fetchColumn()) + 1;
    }

    /**
     * Lotes activos que contienen alguna de estas filas de la tabla de origen — la forma de
     * encontrar "los lotes que este usuario puede ver" sin duplicar la lógica de visibilidad de
     * cada módulo: se le pasan los ids de las filas que ya tiene visibles. Más recientes primero.
     */
    public function obtenerActivosPorFilas(string $origen, array $idsFilas): array
    {
        $idsFilas = array_values(array_unique(array_map('intval', $idsFilas)));

        if (empty($idsFilas)) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($idsFilas), '?'));
        $consulta = $this->db->prepare(
            "SELECT DISTINCT l.* FROM envios_lote l
             JOIN envios_lote_filas f ON f.lote_id = l.id
             WHERE l.origen = ? AND l.estado = 'activo' AND f.origen_fila_id IN ($marcadores)
             ORDER BY l.enviado_en DESC, l.id DESC"
        );
        $consulta->execute(array_merge([$origen], $idsFilas));
        $lotes = $consulta->fetchAll();

        foreach ($lotes as &$lote) {
            $lote['filas'] = $this->obtenerFilas((int) $lote['id']);
        }
        unset($lote);

        return $lotes;
    }



    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM envios_lote WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $lote = $consulta->fetch();

        if ($lote === false) {
            return null;
        }

        $lote['filas'] = $this->obtenerFilas($id);

        return $lote;
    }

    private function obtenerFilas(int $loteId): array
    {
        $consulta = $this->db->prepare('SELECT * FROM envios_lote_filas WHERE lote_id = :lote_id ORDER BY id ASC');
        $consulta->execute(['lote_id' => $loteId]);
        $filas = $consulta->fetchAll();

        foreach ($filas as &$fila) {
            $fila['datos'] = json_decode($fila['datos_json'], true) ?? [];
        }
        unset($fila);

        return $filas;
    }

    /**
     * El lote (si existe) que contiene una fila puntual de la tabla de origen — usado desde
     * Historial para mostrar solo el cambio de ESE ítem, sin traer las demás filas del lote.
     */
    public function obtenerLotePorFila(string $origen, int $origenFilaId): ?array
    {
        $consulta = $this->db->prepare(
            "SELECT l.*, f.datos_json
             FROM envios_lote_filas f
             JOIN envios_lote l ON l.id = f.lote_id
             WHERE l.origen = :origen AND f.origen_fila_id = :origen_fila_id AND l.estado = 'activo'
             ORDER BY l.version DESC
             LIMIT 1"
        );
        $consulta->execute(['origen' => $origen, 'origen_fila_id' => $origenFilaId]);
        $lote = $consulta->fetch();

        if ($lote === false) {
            return null;
        }

        $lote['datos'] = json_decode($lote['datos_json'], true) ?? [];

        return $lote;
    }

    public function ocultar(int $id): bool
    {
        $consulta = $this->db->prepare("UPDATE envios_lote SET estado = 'inactivo' WHERE id = :id");

        return $consulta->execute(['id' => $id]);
    }

    public function mostrar(int $id): bool
    {
        $consulta = $this->db->prepare("UPDATE envios_lote SET estado = 'activo' WHERE id = :id");

        return $consulta->execute(['id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM envios_lote WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Compara una fila congelada (al enviar) contra su estado actual (en vivo, o null si ya no
     * existe). Devuelve, campo por campo, si cambió — para resaltar solo lo distinto sin importar
     * cuántas columnas tenga la tabla de origen.
     */
    public function compararFila(array $filaCongelada, ?array $filaActual): array
    {
        if ($filaActual === null) {
            return ['editada' => false, 'eliminada' => true, 'campos' => []];
        }

        $campos = [];
        $editada = false;

        foreach ($filaCongelada as $clave => $valorAlEnviar) {
            if (in_array($clave, self::CAMPOS_TRANSICION_ENVIO, true)) {
                continue;
            }

            $valorActual = $filaActual[$clave] ?? null;
            $cambiado = (string) $valorAlEnviar !== (string) $valorActual;

            if ($cambiado) {
                $editada = true;
            }

            $campos[] = [
                'clave' => $clave,
                'alEnviar' => $valorAlEnviar,
                'actual' => $valorActual,
                'cambiado' => $cambiado,
            ];
        }

        return ['editada' => $editada, 'eliminada' => false, 'campos' => $campos];
    }
}
