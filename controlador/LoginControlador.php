<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/RecuperacionPassword.php';
require_once __DIR__ . '/../modelo/Correo.php';

class LoginControlador
{
    private Usuario $modeloUsuario;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
    }

    public function index(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->procesar();
        }

        require __DIR__ . '/../vista/login/login.php';
    }

    private function procesar(): string
    {
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($correo === '' || $password === '') {
            return 'Debes completar correo electrónico y contraseña.';
        }

        $datos = $this->modeloUsuario->verificarCredenciales($correo, $password);

        if ($datos === false) {
            return 'Correo o contraseña incorrectos.';
        }

        $this->modeloUsuario->registrarAcceso((int) $datos['id']);

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $datos['id'];
        $_SESSION['usuario_nombre'] = $datos['nombre'];
        $_SESSION['usuario_rol'] = $datos['rol'];
        $_SESSION['usuario_rol_nombre'] = $datos['rol_nombre'] ?? '—';
        $_SESSION['usuario_estamento'] = $datos['estamento_nombre'] ?? '—';
        $_SESSION['usuario_dependencia'] = $datos['dependencia_nombre'] ?? '—';
        $_SESSION['usuario_super_admin'] = (int) ($datos['es_super_admin'] ?? 0) === 1;

        header('Location: index.php?ruta=dashboard');
        exit;
    }

    /**
     * Paso 1 del popup "Recuperar contraseña": genera un código de 9 dígitos, invalida cualquier
     * código pendiente anterior del mismo usuario y lo envía por correo. Responde JSON porque lo
     * consume el fetch() del popup, no una navegación normal.
     */
    public function recuperarSolicitar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $correo = trim($_POST['correo'] ?? '');

        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Ingresa un correo electrónico válido.']);
            return;
        }

        $usuario = $this->modeloUsuario->buscarPorCorreo($correo);

        if ($usuario === false) {
            echo json_encode(['exito' => false, 'mensaje' => 'No encontramos una cuenta con ese correo electrónico.']);
            return;
        }

        $modeloRecuperacion = new RecuperacionPassword();
        $codigo = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $expiraEn = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $modeloRecuperacion->invalidarPendientes((int) $usuario['id']);
        $modeloRecuperacion->crear((int) $usuario['id'], $codigo, $expiraEn);

        $enviado = (new Correo())->enviar(
            $correo,
            'Código de recuperación de contraseña',
            'Tu código de recuperación de contraseña es: ' . $codigo
                . "\n\nEste código vence en 15 minutos. Si no solicitaste este cambio, ignora este mensaje."
        );

        if (!$enviado) {
            echo json_encode(['exito' => false, 'mensaje' => 'No pudimos enviar el correo con el código. Intenta de nuevo en unos minutos.']);
            return;
        }

        echo json_encode(['exito' => true, 'mensaje' => 'Te enviamos un código de 9 dígitos a tu correo.']);
    }

    /**
     * Paso 2 del popup "Recuperar contraseña": valida el código de 9 dígitos contra el correo y
     * actualiza la contraseña si coincide, no expiró y no fue usado antes.
     */
    public function recuperarConfirmar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $correo = trim($_POST['correo'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if ($correo === '' || $codigo === '' || $password === '' || $confirmar === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'Completa todos los campos.']);
            return;
        }

        if ($password !== $confirmar) {
            echo json_encode(['exito' => false, 'mensaje' => 'Las contraseñas no coinciden.']);
            return;
        }

        if (mb_strlen($password) < 6) {
            echo json_encode(['exito' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres.']);
            return;
        }

        $usuario = $this->modeloUsuario->buscarPorCorreo($correo);

        if ($usuario === false) {
            echo json_encode(['exito' => false, 'mensaje' => 'No encontramos una cuenta con ese correo electrónico.']);
            return;
        }

        $modeloRecuperacion = new RecuperacionPassword();
        $codigoVigente = $modeloRecuperacion->obtenerVigente((int) $usuario['id'], $codigo);

        if ($codigoVigente === null) {
            echo json_encode(['exito' => false, 'mensaje' => 'El código es inválido o ya expiró.']);
            return;
        }

        $this->modeloUsuario->actualizarPassword((int) $usuario['id'], $password);
        $modeloRecuperacion->marcarUsado((int) $codigoVigente['id']);

        echo json_encode(['exito' => true, 'mensaje' => 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.']);
    }
}
