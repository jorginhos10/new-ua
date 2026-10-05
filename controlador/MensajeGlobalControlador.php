<?php

require_once __DIR__ . '/../modelo/MensajeGlobal.php';
require_once __DIR__ . '/../modelo/Usuario.php';

class MensajeGlobalControlador
{
    private MensajeGlobal $modeloMensaje;

    public function __construct()
    {
        $this->modeloMensaje = new MensajeGlobal();
    }

    public function index(): void
    {
        $this->requerirSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarAccion();
        }

        // Después de guardar se redirige (patrón PRG): el aviso viaja en la sesión.
        $aviso = $_SESSION['mensaje_global_aviso'] ?? null;
        unset($_SESSION['mensaje_global_aviso']);

        $audiencia = $this->audienciaSolicitada($_GET['audiencia'] ?? null);
        $mensajes = $this->modeloMensaje->listar($audiencia);

        require __DIR__ . '/../vista/mensaje-global/index.php';
    }

    private function procesarAccion(): void
    {
        $usuarioId = (int) $_SESSION['usuario_id'];
        $accion = $_POST['accion'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        $contenido = trim((string) ($_POST['contenido'] ?? ''));
        $audiencia = $this->audienciaSolicitada($_POST['audiencia'] ?? null);

        if ($accion === 'eliminar') {
            if ($id > 0 && $this->modeloMensaje->obtenerPorId($id) !== null) {
                $this->modeloMensaje->eliminar($id);
                $this->avisar('exito', 'Mensaje eliminado.');
            } else {
                $this->avisar('error', 'No se encontró el mensaje.');
            }
        } elseif (in_array($accion, ['crear', 'editar'], true)) {
            if ($contenido === '') {
                $this->avisar('error', 'El mensaje no puede estar vacío.');
            } elseif (mb_strlen($contenido) > MensajeGlobal::LIMITE_CONTENIDO) {
                $this->avisar('error', 'El mensaje no puede superar los ' . MensajeGlobal::LIMITE_CONTENIDO . ' caracteres.');
            } elseif ($accion === 'crear') {
                $this->modeloMensaje->crear($contenido, $usuarioId, $audiencia);
                $this->avisar('exito', 'Mensaje agregado.');
            } elseif ($id > 0 && $this->modeloMensaje->obtenerPorId($id) !== null) {
                $this->modeloMensaje->actualizar($id, $contenido, $usuarioId);
                $this->avisar('exito', 'Mensaje actualizado.');
            } else {
                $this->avisar('error', 'No se encontró el mensaje.');
            }
        } else {
            $this->avisar('error', 'Acción no válida.');
        }

        header('Location: index.php?ruta=mensaje-global&audiencia=' . urlencode($audiencia));
        exit;
    }

    /** Solo se aceptan las audiencias conocidas; cualquier otra cae en administradores. */
    private function audienciaSolicitada(?string $valor): string
    {
        return in_array($valor, MensajeGlobal::AUDIENCIAS, true) ? $valor : 'administrador';
    }

    private function avisar(string $tipo, string $texto): void
    {
        $_SESSION['mensaje_global_aviso'] = ['tipo' => $tipo, 'texto' => $texto];
    }

    private function requerirSuperAdmin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioActual = (new Usuario())->obtenerPorId((int) $_SESSION['usuario_id']);
        $esSuperAdmin = $usuarioActual !== null && (int) ($usuarioActual['es_super_admin'] ?? 0) === 1;

        if (!$esSuperAdmin) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }
    }
}
