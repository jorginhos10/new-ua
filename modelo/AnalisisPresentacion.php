<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Presentación (.ppsx en OneDrive) de la pestaña "Presentación" de ?ruta=analisis. Una sola fila
 * (id = 1) con el enlace que pegó el superadmin; se muestra incrustada con el visor de Office
 * (ver urlIncrustada()).
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

    public function obtener(): ?array
    {
        $consulta = $this->db->query(
            'SELECT p.url, p.actualizado_en, u.nombre AS actualizado_por_nombre
             FROM analisis_presentacion p
             LEFT JOIN usuarios u ON u.id = p.actualizado_por
             WHERE p.id = 1'
        );
        $fila = $consulta->fetch();

        return $fila !== false && $fila['url'] !== '' ? $fila : null;
    }

    public function guardar(string $url, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'INSERT INTO analisis_presentacion (id, url, actualizado_por)
             VALUES (1, :url, :usuario_id)
             ON DUPLICATE KEY UPDATE url = VALUES(url), actualizado_por = VALUES(actualizado_por)'
        );

        return $consulta->execute(['url' => $url, 'usuario_id' => $usuarioId]);
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
     * URL para el <iframe>:
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
