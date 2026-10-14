<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Enlaces de la pestaña "Presentación" de ?ruta=analisis: una lista (no solo uno) de documentos de
 * OneDrive/SharePoint — presentación, Word, Excel o PDF — con nombre visible y orden propio, que el
 * superadmin administra. Cada uno se abre en otra pestaña (SharePoint no deja incrustarlos).
 */
class AnalisisPresentacion
{
    /** Hosts aceptados: OneDrive personal, OneDrive/SharePoint de Microsoft 365 y el visor de Office. */
    private const HOSTS_PERMITIDOS = ['onedrive.live.com', '1drv.ms', 'view.officeapps.live.com', 'sharepoint.com'];

    /**
     * Valor reservado de `url` para la fila de "Actas": vive en esta misma tabla (una fila más,
     * sembrada una sola vez — ver sql/presentacion_actas_fila.sql) para que entre al mismo orden y
     * se pueda mover con las mismas flechas ▲▼ que un documento — pero no es un documento real, así
     * que el controlador/vista la tratan distinto (ícono de carpeta, manda a la pestaña Actas, sin
     * campos de nombre/enlace editables ni botón Eliminar).
     */
    public const URL_ACTAS = 'actas:interno';

    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /** @return array<int, array{id: int, nombre: string, url: string, miniatura_url: ?string, orden: int, actualizado_en: string, actualizado_por_nombre: ?string}> */
    public function obtenerTodos(): array
    {
        $consulta = $this->db->query(
            'SELECT p.id, p.nombre, p.url, p.miniatura_url, p.orden, p.actualizado_en, u.nombre AS actualizado_por_nombre
             FROM analisis_presentacion p
             LEFT JOIN usuarios u ON u.id = p.actualizado_por
             ORDER BY p.orden, p.id'
        );

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare('SELECT id, nombre, url, miniatura_url, orden FROM analisis_presentacion WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        return $fila !== false ? $fila : null;
    }

    public static function esActas(array $enlace): bool
    {
        return ($enlace['url'] ?? '') === self::URL_ACTAS;
    }

    public function crear(string $nombre, string $url, ?string $miniaturaUrl, int $usuarioId): int
    {
        $ordenConsulta = $this->db->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM analisis_presentacion');
        $orden = (int) $ordenConsulta->fetchColumn();

        $consulta = $this->db->prepare(
            'INSERT INTO analisis_presentacion (nombre, url, miniatura_url, orden, actualizado_por)
             VALUES (:nombre, :url, :miniatura_url, :orden, :usuario_id)'
        );
        $consulta->execute([
            'nombre' => $nombre,
            'url' => $url,
            'miniatura_url' => $miniaturaUrl,
            'orden' => $orden,
            'usuario_id' => $usuarioId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, string $url, ?string $miniaturaUrl, int $usuarioId): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE analisis_presentacion SET nombre = :nombre, url = :url, miniatura_url = :miniatura_url, actualizado_por = :usuario_id WHERE id = :id'
        );

        return $consulta->execute([
            'id' => $id,
            'nombre' => $nombre,
            'url' => $url,
            'miniatura_url' => $miniaturaUrl,
            'usuario_id' => $usuarioId,
        ]);
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
     * Tipo de documento detectado del enlace (para la miniatura y la descripción automática de
     * cada tarjeta — nunca lo escribe el admin a mano). Primero intenta con la convención de
     * SharePoint/OneDrive 365 (".../:w:/g/..." = Word, ":x:" Excel, ":p:" PowerPoint, ":b:" PDF);
     * si el enlace no trae esa marca (ej. OneDrive personal/1drv.ms), cae a la extensión del
     * archivo en cualquier parte de la URL. Sin ninguna pista, 'documento' genérico.
     *
     * @return array{tipo: string, etiqueta: string, descripcion: string, color: string, glifo: string}
     */
    public static function tipoDocumento(string $url): array
    {
        $tipos = [
            'word' => ['etiqueta' => 'Word', 'descripcion' => 'Documento de Word', 'color' => '#185ABD', 'glifo' => 'W'],
            'excel' => ['etiqueta' => 'Excel', 'descripcion' => 'Hoja de cálculo de Excel', 'color' => '#107C41', 'glifo' => 'X'],
            'powerpoint' => ['etiqueta' => 'PowerPoint', 'descripcion' => 'Presentación de PowerPoint', 'color' => '#C43E1C', 'glifo' => 'P'],
            'pdf' => ['etiqueta' => 'PDF', 'descripcion' => 'Documento PDF', 'color' => '#C8102E', 'glifo' => 'PDF'],
            'documento' => ['etiqueta' => 'Documento', 'descripcion' => 'Documento compartido', 'color' => '#5B5FC7', 'glifo' => 'DOC'],
        ];

        $ruta = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        if (preg_match('#^/:([a-z]):/#', $ruta, $coincidencia)) {
            $clave = match ($coincidencia[1]) {
                'w' => 'word',
                'x' => 'excel',
                'p' => 'powerpoint',
                'b' => 'pdf',
                default => null,
            };
            if ($clave !== null) {
                return ['tipo' => $clave] + $tipos[$clave];
            }
        }

        $extensionesPorTipo = [
            'word' => ['docx', 'doc'],
            'excel' => ['xlsx', 'xls', 'csv'],
            'powerpoint' => ['pptx', 'ppt', 'ppsx', 'pps'],
            'pdf' => ['pdf'],
        ];
        $urlMinuscula = strtolower($url);
        foreach ($extensionesPorTipo as $clave => $extensiones) {
            foreach ($extensiones as $extension) {
                if (preg_match('/\.' . $extension . '(\?|&|$)/', $urlMinuscula)) {
                    return ['tipo' => $clave] + $tipos[$clave];
                }
            }
        }

        return ['tipo' => 'documento'] + $tipos['documento'];
    }

    /**
     * URL de descarga directa del archivo (botón "Descargar" de cada tarjeta): el servidor de
     * Microsoft responde con Content-Disposition: attachment, así que funciona aunque el atributo
     * HTML `download` no se respete entre dominios distintos (solo aplica mismo origen).
     * - SharePoint / OneDrive de Microsoft 365: el mismo enlace con download=1 en vez de embedview.
     * - OneDrive personal (1drv.ms o enlace de compartir): descarga directa por la API
     *   de shares de OneDrive.
     * - Ya es un enlace de incrustar (caso raro, el admin pegó eso directamente): no hay forma simple
     *   de derivar la descarga, se deja tal cual.
     */
    public static function urlDescarga(string $url): string
    {
        $partes = parse_url($url);
        $host = strtolower($partes['host'] ?? '');
        $ruta = $partes['path'] ?? '';

        if ($host === 'view.officeapps.live.com' || ($host === 'onedrive.live.com' && str_starts_with($ruta, '/embed'))) {
            return $url;
        }

        if (str_ends_with($host, 'sharepoint.com')) {
            parse_str($partes['query'] ?? '', $parametros);
            unset($parametros['action']);
            $parametros['download'] = '1';

            return 'https://' . $partes['host'] . $ruta . '?' . http_build_query($parametros);
        }

        $idCompartido = 'u!' . rtrim(strtr(base64_encode($url), '+/', '-_'), '=');

        return 'https://api.onedrive.com/v1.0/shares/' . $idCompartido . '/root/content';
    }
}
