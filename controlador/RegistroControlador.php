<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Estamento.php';

class RegistroControlador
{
    private Usuario $modeloUsuario;
    private Dependencia $modeloDependencia;
    private Estamento $modeloEstamento;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
        $this->modeloDependencia = new Dependencia();
        $this->modeloEstamento = new Estamento();
    }

    public function index(): void
    {
        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$error, $exito] = $this->procesar();
        }

        $facultades = $this->modeloDependencia->obtenerPorTipo('Facultad');
        $estamentos = $this->modeloEstamento->obtenerTodos();

        require __DIR__ . '/../vista/login/registro.php';
    }

    private function procesar(): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $facultad = trim($_POST['facultad'] ?? '');
        $estamentoId = (int) ($_POST['estamento_id'] ?? 0);
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if ($nombre === '' || $facultad === '' || $estamentoId <= 0 || $correo === '' || $password === '') {
            return ['Todos los campos son obligatorios.', ''];
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['Ingresa un correo electrónico válido.', ''];
        }

        $facultadesActivas = array_column($this->modeloDependencia->obtenerPorTipo('Facultad'), 'nombre');
        if (!in_array($facultad, $facultadesActivas, true)) {
            return ['Selecciona una facultad válida.', ''];
        }

        if ($this->modeloEstamento->obtenerPorId($estamentoId) === null) {
            return ['Selecciona un estamento válido.', ''];
        }

        if ($password !== $confirmar) {
            return ['Las contraseñas no coinciden.', ''];
        }

        if ($this->modeloUsuario->existeCorreo($correo)) {
            return ['Ese correo ya está registrado.', ''];
        }

        $this->modeloUsuario->crear($nombre, $correo, $password, 'invitado', $facultad, null, null, $estamentoId);

        return ['', 'Registro exitoso. Ya puedes iniciar sesión.'];
    }
}
