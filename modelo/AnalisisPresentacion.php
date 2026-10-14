<?php

require_once __DIR__ . '/../config/conexion.php';

/**
 * Enlaces de la pestaña "Presentación" de ?ruta=analisis: una lista (no solo uno) de documentos de
 * OneDrive/SharePoint o Google Drive — presentación, Word, Excel o PDF — con nombre visible y orden
 * propio, que el superadmin administra. Cada uno se ve incrustado en un visor (ver urlIncrustada()).
 */
class AnalisisPresentacion
{
    /** Hosts aceptados: OneDrive personal, OneDrive/SharePoint de Microsoft 365, el visor de Office y Google Drive. */
    private const HOSTS_PERMITIDOS = ['onedrive.live.com', '1drv.ms', 'view.officeapps.live.com', 'sharepoint.com', 'drive.google.com'];

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
     * URL que va en el <img> de la miniatura. Un enlace de compartir de Google Drive
     * (drive.google.com/file/d/<id>/view, open?id=<id>, uc?id=<id>) lleva a la página de Drive, no a
     * la imagen, y el <img> queda roto — se pasa al endpoint de miniaturas de Drive, que sí devuelve
     * la imagen (el archivo debe estar compartido con "Cualquier persona con el enlace"). Cualquier
     * otra URL va tal cual. Se aplica al mostrar, así que sirve también para lo ya guardado.
     */
    public static function urlImagenMiniatura(string $url): string
    {
        $id = self::idArchivoDrive($url);

        return $id !== null ? 'https://drive.google.com/thumbnail?id=' . $id . '&sz=w640' : $url;
    }

    /**
     * Id del archivo en un enlace de Google Drive (drive.google.com/file/d/<id>/..., open?id=<id>,
     * uc?id=<id>); null si no es de Drive o no trae id.
     */
    private static function idArchivoDrive(string $url): ?string
    {
        $partes = parse_url($url);
        if (strtolower($partes['host'] ?? '') !== 'drive.google.com') {
            return null;
        }

        if (preg_match('#/file/d/([A-Za-z0-9_-]+)#', $partes['path'] ?? '', $coincidencia)) {
            return $coincidencia[1];
        }

        parse_str($partes['query'] ?? '', $parametros);
        if (isset($parametros['id']) && is_string($parametros['id']) && preg_match('/^[A-Za-z0-9_-]+$/', $parametros['id'])) {
            return $parametros['id'];
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
     * URL para el <iframe>. Sirve igual para una presentación, un Word, un Excel o un PDF: el visor
     * de Office de Microsoft los incrusta a todos de la misma forma.
     * - Ya es de incrustar (onedrive.live.com/embed o el visor de Office): tal cual.
     * - SharePoint / OneDrive de Microsoft 365: el mismo enlace con action=embedview.
     * - OneDrive personal (1drv.ms o enlace de compartir): visor de Office sobre la descarga
     *   directa del archivo compartido (API de shares de OneDrive).
     * - Google Drive: la vista /preview del archivo, que Google sí deja incrustar en otro dominio
     *   (el archivo debe estar compartido con "Cualquier persona con el enlace").
     *
     * Ojo con los PDF de SharePoint/OneDrive: no los abre Office Online sino el visor propio de
     * SharePoint, que ignora action=embedview y no se deja incrustar desde otro dominio (Chrome
     * muestra "rechazó la conexión"). Igual se mandan al visor — la ventana trae un enlace "Abrir en
     * otra pestaña" para ese caso —; para verlos incrustados, subirlos a Google Drive o pegar el
     * código "Insertar" de SharePoint (embed.aspx).
     */
    public static function urlIncrustada(string $url): string
    {
        $partes = parse_url($url);
        $host = strtolower($partes['host'] ?? '');
        $ruta = $partes['path'] ?? '';
        $yaEsDeIncrustar = ($host === 'onedrive.live.com' && str_starts_with($ruta, '/embed'))
            || (str_ends_with($host, 'sharepoint.com') && str_contains(strtolower($ruta), '/_layouts/15/embed.aspx'));

        if ($yaEsDeIncrustar) {
            return $url;
        }

        $idDrive = self::idArchivoDrive($url);
        if ($idDrive !== null) {
            return 'https://drive.google.com/file/d/' . $idDrive . '/preview';
        }

        if ($host === 'view.officeapps.live.com') {
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

    /**
     * URL de descarga directa del archivo (botón "Descargar" de cada tarjeta): el servidor de
     * Microsoft responde con Content-Disposition: attachment, así que funciona aunque el atributo
     * HTML `download` no se respete entre dominios distintos (solo aplica mismo origen).
     * - SharePoint / OneDrive de Microsoft 365: el mismo enlace con download=1 en vez de embedview.
     * - Google Drive: uc?export=download con el id del archivo.
     * - OneDrive personal (1drv.ms o enlace de compartir): la misma API de descarga directa que usa
     *   urlIncrustada() para alimentar el visor, pero expuesta tal cual, no envuelta en el visor.
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

        $idDrive = self::idArchivoDrive($url);
        if ($idDrive !== null) {
            return 'https://drive.google.com/uc?export=download&id=' . $idDrive;
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
