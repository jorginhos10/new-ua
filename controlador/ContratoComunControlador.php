<?php

require_once __DIR__ . '/../modelo/ContratoComun.php';

class ContratoComunControlador
{
    private ContratoComun $modeloContratoComun;

    public function __construct()
    {
        $this->modeloContratoComun = new ContratoComun();
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

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['accion'] ?? '') === 'cambiar_estado') {
                $this->cambiarEstado();
            }

            [$error, $exito] = $this->guardar();
        }

        $contratosComunes = $this->modeloContratoComun->obtenerTodos();

        require __DIR__ . '/../vista/contratos-comunes/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloContratoComun->cambiarEstado($id);
        }

        header('Location: index.php?ruta=contratos-comunes');
        exit;
    }

    private function guardar(): array
    {
        $codigo = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($codigo === '' || $descripcion === '') {
            return ['El código y la descripción son obligatorios.', ''];
        }

        if ($this->modeloContratoComun->existeCodigo($codigo)) {
            return ['Ese código ya existe.', ''];
        }

        $this->modeloContratoComun->crear($codigo, $descripcion);

        return ['', 'Contrato común agregado correctamente.'];
    }
}
