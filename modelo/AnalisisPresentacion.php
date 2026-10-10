<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Enlaces de la pestaña "Presentación" de ?ruta=analisis: una lista (no solo uno) de documentos de
 * OneDrive/SharePoint — presentación, Word, Excel o PDF — con nombre visible y orden propio, que el
 * superadmin administra. Cada uno se ve incrustado con el visor de Office (ver urlIncrustada()).
 */
class AnalisisPresentacion
{
    /** Hosts aceptados: OneDrive personal, OneDrive/SharePoint de Microsoft 365 y el visor de Office. */
    private const HOSTS_PERMITIDOS = ['onedrive.live.com', '1drv.ms', 'view.officeapps.live.com', 'sharepoint.com'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** @return array<int, array{id: int, nombre: string, url: string, orden: int, actualizado_en: string, actualizado_por_nombre: ?string}> */
    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT p.id, p.nombre, p.url, p.orden, p.actualizado_en, u.nombre AS actualizado_por_nombre
             FROM analisis_presentacion p
             LEFT JOIN usuarios u ON u.id = p.actualizado_por
             ORDER BY p.orden, p.id'
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, url, orden FROM analisis_presentacion WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public function crear(string $nombre, string $url, int $usuarioId): int
    {
        $ordenConsulta = $this->db->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM analisis_presentacion');
        $orden = (int) $ordenConsulta->fetchColumn();

        $consulta = $this->db->prepare(
            'INSERT INTO analisis_presentacion (nombre, url, orden, actualizado_por)
             VALUES (:nombre, :url, :orden, :usuario_id)'
        );
        $consulta->execute(['nombre' => $nombre, 'url' => $url, 'orden' => $orden, 'usuario_id' => $usuarioId]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, string $url, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE analisis_presentacion SET nombre = :nombre, url = :url, actualizado_por = :usuario_id WHERE id = :id'
        );

        return $consulta->execute(['id' => $id, 'nombre' => $nombre, 'url' => $url, 'usuario_id' => $usuarioId]);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM analisis_presentacion WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    /**
     * Sube o baja un enlace una posición, intercambiando su "orden" con el del vecino inmediato.
     * Sin vecino en esa dirección (ya está en un extremo), no hace nada.
     */
    public function mover(int $id, string $direccion): bool
    {
        $actual = $this->obtenerPorId($id);

        if ($actual === null) {
            return false;
        }

        $comparador = $direccion === 'arriba' ? '<' : '>';
        $orden = $direccion === 'arriba' ? 'DESC' : 'ASC';

        $consulta = $this->db->prepare(
            "SELECT id, orden FROM analisis_presentacion WHERE orden $comparador :orden ORDER BY orden $orden LIMIT 1"
        );
        $consulta->execute(['orden' => $actual['orden']]);
        $vecino = $consulta->fetch();

        if ($vecino === false) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $this->db->prepare('UPDATE analisis_presentacion SET orden = :orden WHERE id = :id')
                ->execute(['orden' => $vecino['orden'], 'id' => $actual['id']]);
            $this->db->prepare('UPDATE analisis_presentacion SET orden = :orden WHERE id = :id')
                ->execute(['orden' => $actual['orden'], 'id' => $vecino['id']]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Limpia lo que pegó el admin: acepta el enlace de compartir o el código <iframe> que da
     * OneDrive en "Insertar". Devuelve null si no es un enlace https de OneDrive/SharePoint.
     */
    public static function normalizarEnlace(string $entrada): ?string
    {
        $entrada = trim($entrada);

        if (preg_match('/<iframe[^>]+src\s*=\s*["\']([^"\']+)["\']/i', $entrada, $coincidencia)) {
            $entrada = html_entity_decode($coincidencia[1], ENT_QUOTES | ENT_HTML5);
        }

        $partes = parse_url($entrada);
        if (!$partes || ($partes['scheme'] ?? '') !== 'https' || empty($partes['host'])) {
            return null;
        }

        $host = strtolower($partes['host']);
        foreach (self::HOSTS_PERMITIDOS as $permitido) {
            if ($host === $permitido || str_ends_with($host, '.' . $permitido)) {
                return $entrada;
            }
        }

        return null;
    }

    /**
     * URL para el <iframe>. Sirve igual para una presentación, un Word, un Excel o un PDF: el visor
     * de Office de Microsoft los incrusta a todos de la misma forma.
     * - Ya es de incrustar (onedrive.live.com/embed o el visor de Office): tal cual.
     * - SharePoint / OneDrive de Microsoft 365: el mismo enlace con action=embedview.
     * - OneDrive personal (1drv.ms o enlace de compartir): visor de Office sobre la descarga
     *   directa del archivo compartido (API de shares de OneDrive).
     */
    public static function urlIncrustada(string $url): string
    {
        $partes = parse_url($url);
        $host = strtolower($partes['host'] ?? '');
        $ruta = $partes['path'] ?? '';

        if ($host === 'view.officeapps.live.com' || ($host === 'onedrive.live.com' && str_starts_with($ruta, '/embed'))) {
            return $url;
        }

        if (str_ends_with($host, 'sharepoint.com')) {
            parse_str($partes['query'] ?? '', $parametros);
            $parametros['action'] = 'embedview';

            return 'https://' . $partes['host'] . $ruta . '?' . http_build_query($parametros);
        }

        $idCompartido = 'u!' . rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
        $descargaDirecta = 'https://api.onedrive.com/v1.0/shares/' . $idCompartido . '/root/content';

        return 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($descargaDirecta);
    }
}
