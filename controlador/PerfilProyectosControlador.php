<?php

require_once __DIR__ . '/../modelo/Necesidad.php';
require_once __DIR__ . '/../modelo/DuplicadorFilas.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/LineaInversion.php';
require_once __DIR__ . '/../modelo/SublineaInversion.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Estamento.php';
require_once __DIR__ . '/../modelo/AnioPresupuestal.php';

class PerfilProyectosControlador
{
    private Necesidad $modeloNecesidad;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;
    private LineaInversion $modeloLineaInversion;
    private SublineaInversion $modeloSublineaInversion;
    private Sede $modeloSede;
    private Proyecto $modeloProyecto;
    private Estamento $modeloEstamento;
    private AnioPresupuestal $modeloAnio;

    private const CAMPOS_REQUERIDOS_PROYECTO = [
        'vigencia',
        'nombre_necesidad',
        'estamento_solicitante_id',
        'linea_inversion',
        'sublinea_inversion',
        'sede_id',
        'dependencia',
        'valor',
        'fuente_financiacion',
        'responsable_usuario_id',
    ];

    private const PROGRAMA_ACADEMICO_TIPOS = ['pregrado', 'postgrado'];

    private const MAX_ANIOS_VIGENCIA_ADICIONALES = 5;

    public function __construct()
    {
        $this->modeloNecesidad = new Necesidad();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
        $this->modeloLineaInversion = new LineaInversion();
        $this->modeloSublineaInversion = new SublineaInversion();
        $this->modeloSede = new Sede();
        $this->modeloProyecto = new Proyecto();
        $this->modeloEstamento = new Estamento();
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

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'enviar_todo') {
            [$error, $exito] = $this->enviarTodo();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_proyecto') {
            [$error, $exito] = $this->crearProyecto();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_seleccionados') {
            [$error, $exito] = $this->eliminarSeleccionados();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'duplicar_seleccionados') {
            [$error, $exito] = $this->duplicarSeleccionados();
        }

        $necesidades = $this->modeloNecesidad->obtenerTodas();
        $roles = $this->modeloRol->obtenerTodos();
        $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();
        $dependenciasSugeridas = array_column($this->modeloDependencia->obtenerActivas(), 'nombre');
        $dependenciasTodas = $this->modeloDependencia->obtenerActivasParaEnvio();
        $dependenciasPrograma = $this->modeloDependencia->obtenerPorTipos(self::PROGRAMA_ACADEMICO_TIPOS);
        $lineasInversion = $this->modeloLineaInversion->obtenerActivas();
        $sublineasInversion = $this->modeloSublineaInversion->obtenerActivas();
        $sedes = $this->modeloSede->obtenerTodas();
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $estamentos = $this->modeloEstamento->obtenerTodos();
        $avaladores = $this->obtenerAvaladores();
        $aniosVigencia = $this->obtenerAniosVigencia();
        $puedeEnviarTodo = !empty(array_filter($necesidades, static fn (array $n): bool => ($n['estado'] ?? 'borrador') === 'borrador'));

        require __DIR__ . '/../vista/perfil-proyectos/index.php';
    }

    private function obtenerAvaladores(): array
    {
        $roles = $this->modeloRol->obtenerTodos();
        $rolAvalador = null;

        foreach ($roles as $rol) {
            if ($rol['nombre'] === 'Avalador') {
                $rolAvalador = $rol;
                break;
            }
        }

        if ($rolAvalador === null) {
            return [];
        }

        return $this->modeloUsuario->obtenerPorRolId((int) $rolAvalador['id']);
    }

    private function obtenerAniosVigencia(): array
    {
        $aniosActivos = $this->modeloAnio->obtenerActivos();
        $anioBase = !empty($aniosActivos) ? (int) $aniosActivos[0]['anio'] : (int) date('Y');

        $anios = [];
        for ($i = 0; $i <= self::MAX_ANIOS_VIGENCIA_ADICIONALES; $i++) {
            $anios[] = $anioBase + $i;
        }

        return $anios;
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
            'Solicitante', 'Vigencia', 'Nombre de la necesidad', 'Descripción', 'Justificación', 'Estamento solicitante',
            'Beneficiarios', 'Beneficiarios por estamento', 'Línea', 'Sublínea', 'Inversión (detalle)', 'Sede', 'Dependencia',
            'Programa académico', 'Proyecto PDI', 'Articulación con Plan/planes', 'Espacio a intervenir',
            'Requisitos normativos', 'Valor', 'Fuente de financiación', 'Responsable', 'Observaciones', 'Registrado',
        ], ';');

        foreach ($necesidades as $necesidad) {
            $beneficiariosEstamentos = array_map(
                static fn (array $e): string => $e['nombre'],
                $necesidad['beneficiarios_estamentos'] ?? []
            );

            fputcsv($salida, [
                $necesidad['nombre_solicitante'],
                $necesidad['vigencia'] ?? '',
                $necesidad['nombre_necesidad'] ?? '',
                $necesidad['descripcion'] ?? '',
                $necesidad['justificacion'] ?? '',
                $necesidad['estamento_solicitante_nombre'] ?? '',
                $necesidad['beneficiarios_cantidad'] ?? '',
                implode(', ', $beneficiariosEstamentos),
                $necesidad['linea_inversion_nombre'] ?? $necesidad['linea_inversion'],
                $necesidad['sublinea_inversion_nombre'] ?? $necesidad['sublinea_inversion'],
                $necesidad['detalle_inversion'] ?? '',
                $necesidad['sede_nombre'] ?? '',
                $necesidad['dependencia'],
                $necesidad['programa_academico'] ?? '',
                $necesidad['proyecto_nombre'] ?? '',
                $necesidad['articulacion_plan'] ?? '',
                $necesidad['espacio_intervenir'] ?? '',
                $necesidad['requisitos_normativos'] ?? '',
                number_format((float) $necesidad['valor'], 2, '.', ''),
                $necesidad['fuente_financiacion'],
                $necesidad['responsable_nombre'] ?? '',
                $necesidad['observaciones'] ?? '',
                $necesidad['creado_en'],
            ], ';');
        }

        fclose($salida);
        exit;
    }

    private function crearProyecto(): array
    {
        $datos = [];

        foreach ($_POST as $campo => $valor) {
            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        foreach (self::CAMPOS_REQUERIDOS_PROYECTO as $campo) {
            if (($datos[$campo] ?? '') === '') {
                return ['Todos los campos obligatorios deben diligenciarse.', ''];
            }
        }

        if (!is_numeric($datos['valor']) || (float) $datos['valor'] < 0) {
            return ['El valor debe ser un número válido.', ''];
        }

        if (!is_numeric($datos['vigencia'])) {
            return ['Selecciona una vigencia válida.', ''];
        }

        if (isset($datos['beneficiarios_cantidad']) && $datos['beneficiarios_cantidad'] !== '') {
            if (!is_numeric($datos['beneficiarios_cantidad']) || (int) $datos['beneficiarios_cantidad'] < 0) {
                return ['Los beneficiarios deben ser un número entero válido.', ''];
            }
            $datos['beneficiarios_cantidad'] = (int) $datos['beneficiarios_cantidad'];
        } else {
            $datos['beneficiarios_cantidad'] = '';
        }

        $lineaElegida = $this->modeloLineaInversion->obtenerPorCodigo($datos['linea_inversion']);

        if ($lineaElegida === null) {
            return ['Selecciona una línea de inversión válida.', ''];
        }

        $sublinea = $this->modeloSublineaInversion->obtenerPorCodigo($datos['sublinea_inversion']);

        if ($sublinea === null || (int) $sublinea['linea_inversion_id'] !== (int) $lineaElegida['id']) {
            return ['La sublínea de inversión seleccionada no pertenece a la línea elegida.', ''];
        }

        $datos['sede_id'] = (int) $datos['sede_id'];
        $datos['estamento_solicitante_id'] = (int) $datos['estamento_solicitante_id'];
        $datos['responsable_usuario_id'] = (int) $datos['responsable_usuario_id'];
        $datos['proyecto_pdi_id'] = !empty($datos['proyecto_id']) ? (int) $datos['proyecto_id'] : null;
        $datos['vigencia'] = (int) $datos['vigencia'];
        $datos['beneficiarios_estamentos'] = array_map('intval', $_POST['beneficiarios_estamentos'] ?? []);

        $this->modeloNecesidad->crear($datos, (int) ($_SESSION['usuario_id'] ?? 0));

        return ['', 'Proyecto registrado correctamente.'];
    }

    private function eliminarSeleccionados(): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $eliminados = 0;

        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }

            $existente = $this->modeloNecesidad->obtenerPorId($id);

            if ($existente !== null && ($existente['estado'] ?? 'borrador') === 'borrador') {
                $this->modeloNecesidad->eliminar($id);
                $eliminados++;
            }
        }

        if ($eliminados === 0) {
            return ['No se eliminó ningún proyecto.', ''];
        }

        return ['', 'Se eliminaron ' . $eliminados . ' proyecto(s).'];
    }

    private function duplicarSeleccionados(): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $db = Conexion::obtener();
        $duplicados = 0;

        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }

            $nuevoId = DuplicadorFilas::duplicarFila($db, 'necesidades_academicas', $id);

            if ($nuevoId !== null) {
                DuplicadorFilas::duplicarFilasHijo($db, 'necesidad_beneficiarios_estamentos', 'necesidad_id', $id, $nuevoId);
                $duplicados++;
            }
        }

        if ($duplicados === 0) {
            return ['No se duplicó ningún proyecto.', ''];
        }

        return ['', 'Se duplicaron ' . $duplicados . ' proyecto(s).'];
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

        $destinatarios = $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependenciaDestino['id'], $rolDestinatarioId);

        if (count($destinatarios) > 1) {
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($destinatarios, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoNombre . '". Selecciona a quién remitir la petición.', ''];
            }
        }

        $enviados = $this->modeloNecesidad->enviarTodosBorrador($dependenciaDestinoNombre, $rolDestinatarioId);

        if ($enviados === 0) {
            return ['No hay proyectos en borrador para enviar.', ''];
        }

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

        return ['', 'Se enviaron ' . $enviados . ' proyecto(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaDestinoNombre . '").'];
    }
}
