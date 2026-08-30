<?php

require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';

class PerfilProyectosControlador
{
    private Necesidad $modeloNecesidad;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;

    public function __construct()
    {
        $this->modeloNecesidad = new Necesidad();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'enviar_todo') {
            [$error, $exito] = $this->enviarTodo();
        }

        $necesidades = $this->modeloNecesidad->obtenerTodas();
        $roles = $this->modeloRol->obtenerTodos();
        $dependenciasTodas = $this->modeloDependencia->obtenerActivas();
        $puedeEnviarTodo = !empty(array_filter($necesidades, static fn (array $n): bool => ($n['estado'] ?? 'borrador') === 'borrador'));

        require __DIR__ . '/../vista/perfil-proyectos/index.php';
    }

    public function exportar(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if ($_SESSION['usuario_rol'] !== 'administrador') {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $necesidades = $this->modeloNecesidad->obtenerTodas();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="perfil-proyectos-' . date('Y-m-d') . '.csv"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, [
            'Solicitante', 'Línea', 'Sublínea', 'Inversión (detalle)', 'Sede', 'Dependencia',
            'Programa académico', 'Proyecto PDI', 'Articulación con Plan/planes', 'Espacio a intervenir',
            'Requisitos normativos', 'Valor', 'Fuente de financiación', 'Responsable', 'Observaciones', 'Registrado',
        ], ';');

        foreach ($necesidades as $necesidad) {
            fputcsv($salida, [
                $necesidad['nombre_solicitante'],
                $necesidad['linea_inversion'],
                $necesidad['sublinea_inversion'],
                $necesidad['detalle_inversion'] ?? '',
                $necesidad['sede'],
                $necesidad['dependencia'],
                $necesidad['programa_academico'] ?? '',
                $necesidad['proyecto_pdi'] ?? '',
                $necesidad['articulacion_plan'] ?? '',
                $necesidad['espacio_intervenir'] ?? '',
                $necesidad['requisitos_normativos'] ?? '',
                number_format((float) $necesidad['valor'], 2, '.', ''),
                $necesidad['fuente_financiacion'],
                $necesidad['responsable'],
                $necesidad['observaciones'] ?? '',
                $necesidad['creado_en'],
            ], ';');
        }

        fclose($salida);
        exit;
    }

    private function enviarTodo(): array
    {
        $dependenciaDestinoNombre = trim($_POST['dependencia_destino'] ?? '');
        $rolDestinatarioId = (int) ($_POST['rol_destinatario_id'] ?? 0);

        if ($dependenciaDestinoNombre === '' || $rolDestinatarioId <= 0) {
            return ['Selecciona a quién se enviará y el rol al que se enviarán los proyectos.', ''];
        }

        $rol = $this->modeloRol->obtenerPorId($rolDestinatarioId);

        if ($rol === null) {
            return ['El rol seleccionado no existe.', ''];
        }

        $dependenciaDestino = $this->modeloDependencia->obtenerPorNombre($dependenciaDestinoNombre);

        if ($dependenciaDestino === null) {
            return ['La dependencia destino seleccionada no existe.', ''];
        }

        $enviados = $this->modeloNecesidad->enviarTodosBorrador($rolDestinatarioId);

        if ($enviados === 0) {
            return ['No hay proyectos en borrador para enviar.', ''];
        }

        $destinatarios = $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependenciaDestino['id'], $rolDestinatarioId);

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);

        foreach ($destinatarios as $destinatario) {
            $this->modeloMensaje->crear(
                $remitenteId,
                (int) $destinatario['id'],
                'Perfil de proyectos enviado',
                'Se enviaron ' . $enviados . ' proyecto(s) de Perfil de proyectos para tu revisión.'
            );
        }

        if (empty($destinatarios)) {
            return ['', 'Se enviaron ' . $enviados . ' proyecto(s), pero no se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoNombre . '" para notificar.'];
        }

        return ['', 'Se enviaron ' . $enviados . ' proyecto(s) a ' . count($destinatarios) . ' usuario(s) con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoNombre . '".'];
    }
}
