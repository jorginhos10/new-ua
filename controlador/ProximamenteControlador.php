<?php

class ProximamenteControlador
{
    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        $tituloModulo = $_GET['titulo'] ?? 'Módulo';

        require __DIR__ . '/../vista/comun/proximamente.php';
    }
}
