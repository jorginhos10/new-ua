<?php

require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/Usuario.php';

class MensajeControlador
{
    private Mensaje $modeloMensaje;
    private Usuario $modeloUsuario;

    public function __construct()
    {
        $this->modeloMensaje = new Mensaje();
        $this->modeloUsuario = new Usuario();
    }

    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario_id'];
        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'eliminar') {
                $this->eliminar($usuarioId);
            } else {
                [$error, $exito] = $this->enviar($usuarioId);
            }
        }

        $tab = ($_GET['tab'] ?? 'recibidos') === 'enviados' ? 'enviados' : 'recibidos';

        $recibidos = $this->modeloMensaje->obtenerRecibidos($usuarioId);
        $enviados = $this->modeloMensaje->obtenerEnviados($usuarioId);
        $destinatarios = $this->modeloUsuario->obtenerTodosExcepto($usuarioId);

        require __DIR__ . '/../vista/mensajes/index.php';
    }

    public function marcarLeido(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['usuario_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false]);
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloMensaje->marcarLeido($id, (int) $_SESSION['usuario_id']);
        }

        echo json_encode(['ok' => true]);
    }

    private function enviar(int $usuarioId): array
    {
        $destinatarioId = (int) ($_POST['destinatario_id'] ?? 0);
        $asunto = trim($_POST['asunto'] ?? '');
        $cuerpo = trim($_POST['cuerpo'] ?? '');

        if ($destinatarioId <= 0 || $asunto === '' || $cuerpo === '') {
            return ['Todos los campos son obligatorios.', ''];
        }

        if ($destinatarioId === $usuarioId) {
            return ['No puedes enviarte un mensaje a ti mismo.', ''];
        }

        $this->modeloMensaje->crear($usuarioId, $destinatarioId, $asunto, $cuerpo);

        return ['', 'Mensaje enviado correctamente.'];
    }

    private function eliminar(int $usuarioId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $tab = ($_POST['tab'] ?? 'recibidos') === 'enviados' ? 'enviados' : 'recibidos';

        if ($id > 0) {
            $this->modeloMensaje->eliminar($id, $usuarioId);
        }

        header('Location: index.php?ruta=mensajes&tab=' . $tab);
        exit;
    }
}
