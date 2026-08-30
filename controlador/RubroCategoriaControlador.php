<?php

require_once __DIR__ . '/../modelo/RubroCategoria.php';

class RubroCategoriaControlador
{
    private RubroCategoria $modeloRubroCategoria;

    public function __construct()
    {
        $this->modeloRubroCategoria = new RubroCategoria();
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
            [$error, $exito] = $this->guardar();
        }

        $this->modeloRubroCategoria->sincronizarPrefijos();
        $categorias = $this->modeloRubroCategoria->obtenerTodos();

        require __DIR__ . '/../vista/rubro-categorias/index.php';
    }

    private function guardar(): array
    {
        $marcadosAutogestion = array_map('intval', $_POST['autogestion'] ?? []);
        $marcadosEgresos = array_map('intval', $_POST['egresos'] ?? []);
        $marcadosProyectos = array_map('intval', $_POST['proyectos'] ?? []);

        foreach ($this->modeloRubroCategoria->obtenerTodos() as $categoria) {
            $id = (int) $categoria['id'];

            $this->modeloRubroCategoria->guardar(
                $id,
                in_array($id, $marcadosAutogestion, true),
                in_array($id, $marcadosEgresos, true),
                in_array($id, $marcadosProyectos, true)
            );
        }

        return ['', 'Categorías de rubros actualizadas correctamente.'];
    }
}
