<?php

require_once __DIR__ . '/../modelo/Acta.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';

class ActaControlador
{
    private const TIPOS_DEPENDENCIA_PERMITIDOS = ['Facultad', 'Vicerrectoria'];
    private const TAMANO_MAXIMO_BYTES = 3 * 1024 * 1024;
    private const CARPETA_ALMACENAMIENTO = __DIR__ . '/../almacenamiento/actas/';

    private Acta $modeloActa;
    private Usuario $modeloUsuario;
    private Dependencia $modeloDependencia;
    private AnioPresupuestal $modeloAnio;

    public function __construct()
    {
        $this->modeloActa = new Acta();
        $this->modeloUsuario = new Usuario();
        $this->modeloDependencia = new Dependencia();
        $this->modeloAnio = new AnioPresupuestal();
    }

    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $usuarioActual = $this->modeloUsuario->obtenerPorId($usuarioId);
        // Nombre distinto a $dependenciaActual a propósito: encabezado.php (incluido más abajo
        // por la vista) reutiliza esa variable para mostrar el nombre en la barra superior.
        $dependenciaFacultad = !empty($usuarioActual['dependencia_id'])
            ? $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id'])
            : null;

        if ($dependenciaFacultad === null || !in_array($dependenciaFacultad['tipo'] ?? '', self::TIPOS_DEPENDENCIA_PERMITIDOS, true)) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'eliminar') {
                $this->eliminar($usuarioId);
            } else {
                [$error, $exito] = $this->subir($usuarioId, $dependenciaFacultad);
            }
        }

        $tab = ($_GET['tab'] ?? 'recibidas') === 'enviadas' ? 'enviadas' : 'recibidas';

        $recibidas = $this->modeloActa->obtenerRecibidas($usuarioId);
        $enviadas = $this->modeloActa->obtenerEnviadas($usuarioId);
        $destinatarios = $this->modeloUsuario->obtenerTodosExcepto($usuarioId);
        $aniosActivos = $this->modeloAnio->obtenerActivos();

        require __DIR__ . '/../vista/actas/index.php';
    }

    public function descargar(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $id = (int) ($_GET['id'] ?? 0);
        $acta = $id > 0 ? $this->modeloActa->obtenerPorId($id) : null;

        if ($acta === null || ((int) $acta['remitente_id'] !== $usuarioId && (int) $acta['destinatario_id'] !== $usuarioId)) {
            http_response_code(404);
            exit('Acta no encontrada.');
        }

        $rutaArchivo = self::CARPETA_ALMACENAMIENTO . $acta['nombre_almacenado'];

        if (!is_file($rutaArchivo)) {
            http_response_code(404);
            exit('El archivo ya no está disponible.');
        }

        if ((int) $acta['destinatario_id'] === $usuarioId) {
            $this->modeloActa->marcarLeido($id, $usuarioId);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($acta['nombre_archivo']) . '"');
        header('Content-Length: ' . filesize($rutaArchivo));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($rutaArchivo);
        exit;
    }

    /**
     * @return array{0: string, 1: string} [error, éxito]
     */
    private function subir(int $usuarioId, array $dependenciaActual): array
    {
        $anioPresupuestalId = (int) ($_POST['anio_presupuestal_id'] ?? 0);
        $destinatarioId = (int) ($_POST['destinatario_id'] ?? 0);

        if ($anioPresupuestalId <= 0) {
            return ['Selecciona el año presupuestal.', ''];
        }

        if ($destinatarioId <= 0) {
            return ['Selecciona a quién le vas a enviar el acta.', ''];
        }

        if ($destinatarioId === $usuarioId) {
            return ['No puedes enviarte un acta a ti mismo.', ''];
        }

        $destinatario = $this->modeloUsuario->obtenerPorId($destinatarioId);
        if ($destinatario === null) {
            return ['El destinatario seleccionado no existe.', ''];
        }

        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            return ['Selecciona un archivo PDF para subir.', ''];
        }

        $archivoTemporal = $_FILES['archivo']['tmp_name'];
        $nombreOriginal = (string) $_FILES['archivo']['name'];
        $tamanoBytes = (int) $_FILES['archivo']['size'];

        if ($tamanoBytes > self::TAMANO_MAXIMO_BYTES) {
            return ['El archivo no puede pesar más de 3 MB.', ''];
        }

        $extension = strtolower((string) pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return ['Solo se permiten archivos en formato PDF.', ''];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipoMime = $finfo !== false ? finfo_file($finfo, $archivoTemporal) : false;
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if ($tipoMime !== 'application/pdf') {
            return ['El archivo no es un PDF válido.', ''];
        }

        if (!is_dir(self::CARPETA_ALMACENAMIENTO) && !mkdir(self::CARPETA_ALMACENAMIENTO, 0755, true) && !is_dir(self::CARPETA_ALMACENAMIENTO)) {
            return ['No se pudo guardar el archivo. Intenta de nuevo.', ''];
        }

        $nombreAlmacenado = bin2hex(random_bytes(16)) . '.pdf';
        $rutaDestino = self::CARPETA_ALMACENAMIENTO . $nombreAlmacenado;

        if (!move_uploaded_file($archivoTemporal, $rutaDestino)) {
            return ['No se pudo guardar el archivo. Intenta de nuevo.', ''];
        }

        $this->modeloActa->crear([
            'anio_presupuestal_id' => $anioPresupuestalId,
            'dependencia_id' => (int) $dependenciaActual['id'],
            'remitente_id' => $usuarioId,
            'destinatario_id' => $destinatarioId,
            'nombre_archivo' => $nombreOriginal !== '' ? $nombreOriginal : 'acta.pdf',
            'nombre_almacenado' => $nombreAlmacenado,
            'tamano_bytes' => $tamanoBytes,
        ]);

        return ['', 'Acta enviada correctamente a ' . $destinatario['nombre'] . '.'];
    }

    private function eliminar(int $usuarioId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $tab = ($_POST['tab'] ?? 'recibidas') === 'enviadas' ? 'enviadas' : 'recibidas';

        if ($id > 0) {
            $acta = $this->modeloActa->obtenerPorId($id);

            if ($acta !== null && ((int) $acta['remitente_id'] === $usuarioId || (int) $acta['destinatario_id'] === $usuarioId)) {
                $rutaArchivo = self::CARPETA_ALMACENAMIENTO . $acta['nombre_almacenado'];
                $this->modeloActa->eliminar($id, $usuarioId);

                if (is_file($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
            }
        }

        header('Location: index.php?ruta=actas&tab=' . $tab);
        exit;
    }
}
