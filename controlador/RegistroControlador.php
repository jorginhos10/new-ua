<?php

require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Estamento.php';
require_once __DIR__ . '/../modelo/Rol.php';

class RegistroControlador
{
    private Usuario $modeloUsuario;
    private Dependencia $modeloDependencia;
    private Estamento $modeloEstamento;
    private Rol $modeloRol;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
        $this->modeloDependencia = new Dependencia();
        $this->modeloEstamento = new Estamento();
        $this->modeloRol = new Rol();
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

        // Todo invitado autoregistrado es de rol "Formulador". La dependencia_id apunta a la
        // Facultad real que eligió (no a un cajón genérico) porque el envío de su necesidad debe
        // poder enrutarse a un administrador Gestor de esa misma Facultad.
        $rolFormulador = $this->modeloRol->obtenerPorNombre('Formulador');
        $dependenciaFacultad = $this->modeloDependencia->obtenerPorNombre($facultad);

        $this->modeloUsuario->crear(
            $nombre,
            $correo,
            $password,
            'invitado',
            $facultad,
            $rolFormulador !== null ? (int) $rolFormulador['id'] : null,
            $dependenciaFacultad !== null ? (int) $dependenciaFacultad['id'] : null,
            $estamentoId
        );

        return ['', 'Registro exitoso. Ya puedes iniciar sesión.'];
    }
}
