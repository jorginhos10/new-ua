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
require_once __DIR__ . '/../modelo/PeticionArchivada.php';
require_once __DIR__ . '/../modelo/Convocatoria.php';
require_once __DIR__ . '/../modelo/FuenteFinanciacion.php';
require_once __DIR__ . '/../modelo/CuentaRegresiva.php';

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
    private Convocatoria $modeloConvocatoria;
    private FuenteFinanciacion $modeloFuente;

    private const CAMPOS_REQUERIDOS_PROYECTO = [
        'vigencia',
        'nombre_necesidad',
        'estamento_solicitante_id',
        'linea_inversion',
        'sublinea_inversion',
        'sede_id',
        'valor',
        'fuente_financiacion_id',
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
        $this->modeloConvocatoria = new Convocatoria();
        $this->modeloFuente = new FuenteFinanciacion();
    }

    public function index(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?ruta=login');
            exit;
        }

        if (!in_array($_SESSION['usuario_rol'], ['administrador', 'invitado'], true)) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $esInvitado = $_SESSION['usuario_rol'] === 'invitado';
        $error = '';
        $exito = '';

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $dependenciaUsuarioId = !empty($usuarioActual['dependencia_id']) ? (int) $usuarioActual['dependencia_id'] : null;
        $convocatoriasVisibles = $this->modeloConvocatoria->visiblesPara($_SESSION['usuario_rol'], $dependenciaUsuarioId);

        // Un proyecto en edición pertenece a su convocatoria; si no, la de la pestaña elegida o la primera.
        $proyectoParaEditar = null;
        if (isset($_GET['editar_id']) && ctype_digit((string) $_GET['editar_id'])) {
            $proyectoParaEditar = $this->modeloNecesidad->obtenerPorId((int) $_GET['editar_id']);

            // Nadie, sin importar el rol, puede entrar a editar un proyecto que no es suyo, ni uno ya enviado.
            if ($proyectoParaEditar !== null && (int) $proyectoParaEditar['usuario_id'] !== (int) $_SESSION['usuario_id']) {
                $proyectoParaEditar = null;
            }

            if ($proyectoParaEditar !== null && ($proyectoParaEditar['estado'] ?? 'borrador') === 'enviado') {
                $proyectoParaEditar = null;
            }
        }

        $convocatoriaActual = $this->resolverConvocatoriaActual($convocatoriasVisibles, $proyectoParaEditar);
        $dentroDeVentana = $convocatoriaActual !== null
            && ($esInvitado ? Convocatoria::dentroDeVentana($convocatoriaActual) : (int) $convocatoriaActual['activa'] === 1);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'enviar_todo') {
            [$error, $exito] = $this->enviarTodo($esInvitado, $convocatoriaActual, $dentroDeVentana);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_proyecto') {
            [$error, $exito] = $this->crearProyecto($esInvitado, $convocatoriaActual, $dentroDeVentana, $usuarioActual);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar_proyecto') {
            [$error, $exito] = $this->actualizarProyecto($esInvitado, $usuarioActual);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_seleccionados') {
            [$error, $exito] = $this->eliminarSeleccionados($esInvitado);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'duplicar_seleccionados') {
            [$error, $exito] = $this->duplicarSeleccionados($esInvitado);
        }

        $necesidadesTodas = $this->filtrarPorPropietarioODestinatario($this->modeloNecesidad->obtenerTodas(), $usuarioActual);
        $necesidades = $convocatoriaActual === null ? [] : array_values(array_filter(
            $necesidadesTodas,
            static fn (array $n): bool => (int) ($n['convocatoria_id'] ?? 0) === (int) $convocatoriaActual['id']
        ));

        $roles = $this->modeloRol->obtenerTodos();
        $dependenciasPrograma = $this->modeloDependencia->obtenerPorTipos(self::PROGRAMA_ACADEMICO_TIPOS);
        $lineasInversion = $this->modeloLineaInversion->obtenerActivas();
        $sublineasInversion = $this->modeloSublineaInversion->obtenerActivas();
        $sedes = $this->modeloSede->obtenerTodas();
        $proyectos = $this->modeloProyecto->obtenerTodos();
        $estamentos = $this->modeloEstamento->obtenerTodos();
        $aniosVigencia = $this->obtenerAniosVigencia();
        $fuentesHabilitadas = $convocatoriaActual === null ? [] : $this->modeloConvocatoria->fuentesHabilitadas((int) $convocatoriaActual['id']);
        $relojConvocatoria = $convocatoriaActual === null ? null : CuentaRegresiva::calcular($convocatoriaActual['fecha_inicio'], $convocatoriaActual['fecha_cierre']);

        $necesidadesBorrador = array_values(array_filter($necesidades, static fn (array $n): bool => $n['estado'] === 'borrador'));
        $puedeEnviarTodo = $dentroDeVentana && !empty($necesidadesBorrador);

        // Dependencias que se pueden elegir: solo las del alcance de la convocatoria (si tiene alcance).
        $alcanceDependencias = $convocatoriaActual === null ? null : $this->modeloConvocatoria->alcanceDependenciaIds((int) $convocatoriaActual['id']);

        // Para invitado (Formulador), "Responsable"/destinatario se acota siempre a los Gestores de
        // su propia Facultad — nunca ve ni puede elegir el mapa completo dependencia→rol→usuario de
        // la universidad, que solo se calcula/inyecta para administrador.
        // El invitado solo ve su propia dependencia (el servidor la fuerza igual en prepararDatosProyecto()).
        $dependenciaPropiaNombre = $dependenciaUsuarioId !== null
            ? ($this->modeloDependencia->obtenerPorId($dependenciaUsuarioId)['nombre'] ?? null)
            : null;

        if ($esInvitado) {
            $avaladores = $this->obtenerGestoresDeMiFacultad();
            $etiquetaResponsable = 'gestor';
            $dependenciasTodas = [];
            $usuariosPorDependenciaYRol = [];
            $dependenciasSugeridas = $dependenciaPropiaNombre !== null ? [$dependenciaPropiaNombre] : [];
        } else {
            $avaladores = $this->obtenerAvaladores();
            $etiquetaResponsable = 'avalador';
            $dependenciasTodas = $this->modeloDependencia->obtenerActivasParaEnvio();
            $usuariosPorDependenciaYRol = $this->modeloUsuario->obtenerMapaPorDependenciaYRol();
            $dependenciasSugeridas = array_column($this->filtrarPorAlcance($this->modeloDependencia->obtenerActivas(), $alcanceDependencias), 'nombre');
        }

        $volverEdicion = $_GET['volver'] ?? '';

        require __DIR__ . '/../vista/perfil-proyectos/index.php';
    }

    /**
     * Convocatoria que se muestra: la del proyecto que se edita, o la elegida (POST o GET) entre las
     * visibles para el usuario; si no hay elección válida, la primera visible.
     */
    private function resolverConvocatoriaActual(array $visibles, ?array $proyectoParaEditar): ?array
    {
        if ($proyectoParaEditar !== null) {
            $idPreferido = (int) $proyectoParaEditar['convocatoria_id'];
        } else {
            $idPreferido = (int) ($_POST['convocatoria_id'] ?? $_GET['convocatoria_id'] ?? 0);
        }

        if ($idPreferido <= 0) {
            return $visibles[0] ?? null;
        }

        foreach ($visibles as $convocatoria) {
            if ((int) $convocatoria['id'] === $idPreferido) {
                return $convocatoria;
            }
        }

        return null;
    }

    /** Filtra filas de dependencias (con 'id') al alcance dado; null = sin restricción. */
    private function filtrarPorAlcance(array $dependencias, ?array $alcance): array
    {
        if ($alcance === null) {
            return $dependencias;
        }

        return array_values(array_filter($dependencias, static fn (array $d): bool => in_array((int) $d['id'], $alcance, true)));
    }

    /**
     * Un proyecto solo debe ser visible, en este listado, para quien lo creó o para quien
     * coincide exactamente con la dependencia y el rol al que fue enviado — mismo criterio que
     * ya usan Gastos/Extensión/Postgrado/etc. en su propio listado.
     */
    private function filtrarPorPropietarioODestinatario(array $items, ?array $usuarioActual): array
    {
        $usuarioActualId = (int) ($usuarioActual['id'] ?? 0);
        $dependenciaUsuarioNombre = null;
        $dependenciaFila = null;

        if (!empty($usuarioActual['dependencia_id'])) {
            $dependenciaFila = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);
            $dependenciaUsuarioNombre = $dependenciaFila['nombre'] ?? null;
        }

        // "Auditar" (toggle global de la headerbar, solo para la dependencia raíz): en vez de
        // exigir ser dueño o destinatario exacto de cada necesidad, se ve todo lo que cae en el
        // árbol de dependencias — mismo bypass que ya usa Peticiones en modo jerarquía.
        if (!empty($_SESSION['modo_auditoria']) && $dependenciaFila !== null && !empty($dependenciaFila['es_raiz_superadmin'])) {
            $dependenciasPermitidas = [$dependenciaUsuarioNombre];
            foreach ($this->modeloDependencia->obtenerDescendientesPlano((int) $usuarioActual['dependencia_id']) as $descendiente) {
                $dependenciasPermitidas[] = $descendiente['nombre'];
            }

            return array_values(array_filter($items, static fn (array $item): bool =>
                in_array($item['dependencia'] ?? null, $dependenciasPermitidas, true)
                || in_array($item['dependencia_destino'] ?? null, $dependenciasPermitidas, true)
            ));
        }

        $rolUsuarioId = !empty($usuarioActual['rol_id']) ? (int) $usuarioActual['rol_id'] : null;

        return array_values(array_filter($items, static function (array $item) use ($usuarioActualId, $dependenciaUsuarioNombre, $rolUsuarioId): bool {
            if ($usuarioActualId > 0 && (int) $item['usuario_id'] === $usuarioActualId) {
                return true;
            }

            // Si se guardó un destinatario específico (porque había más de uno con ese rol en la
            // dependencia), solo esa persona lo ve — si no (envíos antiguos, o cuando había un
            // único destinatario), se mantiene la visibilidad por rol+dependencia de siempre.
            return ($item['estado'] ?? 'borrador') === 'enviado'
                && $rolUsuarioId !== null
                && (int) ($item['rol_destinatario_id'] ?? 0) === $rolUsuarioId
                && ($item['dependencia_destino'] ?? null) === $dependenciaUsuarioNombre
                && (empty($item['usuario_destinatario_id']) || (int) $item['usuario_destinatario_id'] === $usuarioActualId);
        }));
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

    /**
     * El invitado (Formulador) envía su proyecto al Gestor de su propia Facultad — no a cualquier
     * Avalador. Se busca por el rol "Gestor" del catálogo y la dependencia_id del propio invitado
     * (su Facultad real). Si no tiene dependencia_id asignada, no hay a quién enviarle.
     */
    private function obtenerGestoresDeMiFacultad(): array
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);

        if ($usuarioActual === null || empty($usuarioActual['dependencia_id'])) {
            return [];
        }

        $rolGestor = $this->modeloRol->obtenerPorNombre('Gestor');

        if ($rolGestor === null) {
            return [];
        }

        return $this->modeloUsuario->obtenerPorDependenciaYRol((int) $usuarioActual['dependencia_id'], (int) $rolGestor['id']);
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

        if (!in_array($_SESSION['usuario_rol'], ['administrador', 'invitado'], true)) {
            header('Location: index.php?ruta=dashboard');
            exit;
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $necesidades = $this->modeloNecesidad->obtenerTodas();
        $necesidades = $this->filtrarPorPropietarioODestinatario($necesidades, $usuarioActual);

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
                number_format((float) $necesidad['valor'], 2, ',', '.'),
                $necesidad['fuente_financiacion'],
                $necesidad['responsable_nombre'] ?? '',
                $necesidad['observaciones'] ?? '',
                $necesidad['creado_en'],
            ], ';');
        }

        fclose($salida);
        exit;
    }

    /**
     * Valida y normaliza el formulario de un proyecto dentro de su convocatoria. Devuelve [error, datos].
     * La fuente debe estar habilitada en la convocatoria; el invitado formula siempre para su propia
     * dependencia, y el administrador elige una dentro del alcance de la convocatoria.
     */
    private function prepararDatosProyecto(array $convocatoria, bool $esInvitado, ?array $usuarioActual): array
    {
        $datos = [];

        foreach ($_POST as $campo => $valor) {
            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        foreach (self::CAMPOS_REQUERIDOS_PROYECTO as $campo) {
            if (($datos[$campo] ?? '') === '') {
                return ['Todos los campos obligatorios deben diligenciarse.', []];
            }
        }

        if (!is_numeric($datos['valor']) || (float) $datos['valor'] < 0) {
            return ['El valor debe ser un número válido.', []];
        }

        // Con tope por proyecto, ningún proyecto de la convocatoria puede superarlo.
        if ($convocatoria['tope_por_proyecto'] !== null && (float) $datos['valor'] > (float) $convocatoria['tope_por_proyecto']) {
            return ['El valor no puede superar el tope por proyecto de esta convocatoria ($ ' . number_format((float) $convocatoria['tope_por_proyecto'], 2, ',', '.') . ').', []];
        }

        if (!is_numeric($datos['vigencia'])) {
            return ['Selecciona una vigencia válida.', []];
        }

        if (isset($datos['beneficiarios_cantidad']) && $datos['beneficiarios_cantidad'] !== '') {
            if (!is_numeric($datos['beneficiarios_cantidad']) || (int) $datos['beneficiarios_cantidad'] < 0) {
                return ['Los beneficiarios deben ser un número entero válido.', []];
            }
            $datos['beneficiarios_cantidad'] = (int) $datos['beneficiarios_cantidad'];
        } else {
            $datos['beneficiarios_cantidad'] = '';
        }

        $lineaElegida = $this->modeloLineaInversion->obtenerPorCodigo($datos['linea_inversion']);

        if ($lineaElegida === null) {
            return ['Selecciona una línea de inversión válida.', []];
        }

        $sublinea = $this->modeloSublineaInversion->obtenerPorCodigo($datos['sublinea_inversion']);

        if ($sublinea === null || (int) $sublinea['linea_inversion_id'] !== (int) $lineaElegida['id']) {
            return ['La sublínea de inversión seleccionada no pertenece a la línea elegida.', []];
        }

        $fuenteId = (int) $datos['fuente_financiacion_id'];
        $fuente = null;
        foreach ($this->modeloConvocatoria->fuentesHabilitadas((int) $convocatoria['id']) as $fuenteHabilitada) {
            if ((int) $fuenteHabilitada['id'] === $fuenteId) {
                $fuente = $fuenteHabilitada;
                break;
            }
        }

        if ($fuente === null) {
            return ['Selecciona una fuente de financiación habilitada en la convocatoria.', []];
        }

        if ($esInvitado) {
            $dependencia = !empty($usuarioActual['dependencia_id'])
                ? $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id'])
                : null;
        } else {
            $dependencia = $this->modeloDependencia->obtenerPorNombre($datos['dependencia'] ?? '');
        }

        if ($dependencia === null) {
            return ['Selecciona una dependencia válida.', []];
        }

        if (!$this->modeloConvocatoria->permiteDependencia((int) $convocatoria['id'], (int) $dependencia['id'])) {
            return ['La dependencia seleccionada no puede formular en esta convocatoria.', []];
        }

        $datos['convocatoria_id'] = (int) $convocatoria['id'];
        $datos['dependencia'] = $dependencia['nombre'];
        $datos['dependencia_id'] = (int) $dependencia['id'];
        $datos['fuente_financiacion_id'] = $fuenteId;
        $datos['fuente_financiacion'] = $fuente['nombre'];
        $datos['sede_id'] = (int) $datos['sede_id'];
        $datos['estamento_solicitante_id'] = (int) $datos['estamento_solicitante_id'];
        $datos['responsable_usuario_id'] = (int) $datos['responsable_usuario_id'];
        $datos['proyecto_pdi_id'] = !empty($datos['proyecto_id']) ? (int) $datos['proyecto_id'] : null;
        $datos['vigencia'] = (int) $datos['vigencia'];
        $datos['beneficiarios_estamentos'] = array_map('intval', $_POST['beneficiarios_estamentos'] ?? []);

        return ['', $datos];
    }

    /** Dentro de la ventana para el invitado; el administrador solo necesita que la convocatoria esté activa. */
    private function puedeModificarPorVentana(bool $esInvitado, array $proyecto): bool
    {
        $convocatoria = $this->modeloConvocatoria->obtenerPorId((int) ($proyecto['convocatoria_id'] ?? 0));

        if ($convocatoria === null) {
            return false;
        }

        return $esInvitado ? Convocatoria::dentroDeVentana($convocatoria) : (int) $convocatoria['activa'] === 1;
    }

    private function crearProyecto(bool $esInvitado, ?array $convocatoria, bool $dentroDeVentana, ?array $usuarioActual): array
    {
        if ($convocatoria === null) {
            return ['Selecciona una convocatoria disponible para tu usuario.', ''];
        }

        if (!$dentroDeVentana) {
            return ['La convocatoria "' . $convocatoria['nombre'] . '" no está abierta para formular proyectos.', ''];
        }

        [$error, $datos] = $this->prepararDatosProyecto($convocatoria, $esInvitado, $usuarioActual);

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloNecesidad->crear($datos, (int) ($_SESSION['usuario_id'] ?? 0));

        return ['', 'Proyecto registrado correctamente.'];
    }

    private function actualizarProyecto(bool $esInvitado, ?array $usuarioActual): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloNecesidad->obtenerPorId($id) : null;

        // Nadie, sin importar el rol, puede actualizar un proyecto que no es suyo.
        if ($existente === null || (int) $existente['usuario_id'] !== (int) $_SESSION['usuario_id']) {
            return ['El proyecto que intentas editar no existe.', ''];
        }

        if (($existente['estado'] ?? 'borrador') === 'enviado') {
            return ['Un proyecto enviado ya no se puede editar.', ''];
        }

        if (!$this->puedeModificarPorVentana($esInvitado, $existente)) {
            return ['La convocatoria de este proyecto no está abierta; no se puede editar.', ''];
        }

        $convocatoria = $this->modeloConvocatoria->obtenerPorId((int) $existente['convocatoria_id']);
        [$error, $datos] = $this->prepararDatosProyecto($convocatoria, $esInvitado, $usuarioActual);

        if ($error !== '') {
            return [$error, ''];
        }

        $this->modeloNecesidad->actualizar($id, $datos);

        (new PeticionArchivada())->sincronizarDesdeOrigen('necesidad', $id, (float) $datos['valor'], $datos['dependencia']);

        $destino = !empty($_POST['volver']) ? $_POST['volver'] : 'index.php?ruta=perfil-proyectos&convocatoria_id=' . (int) $convocatoria['id'];
        header('Location: ' . $destino);
        exit;
    }

    private function eliminarSeleccionados(bool $esInvitado): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $eliminados = 0;

        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }

            $existente = $this->modeloNecesidad->obtenerPorId($id);

            // Nadie, sin importar el rol, puede eliminar un proyecto que no es suyo.
            if ($existente !== null
                && (int) $existente['usuario_id'] === (int) $_SESSION['usuario_id']
                && ($existente['estado'] ?? 'borrador') === 'borrador'
                && $this->puedeModificarPorVentana($esInvitado, $existente)
            ) {
                $this->modeloNecesidad->eliminar($id);
                $eliminados++;
            }
        }

        if ($eliminados === 0) {
            return ['No se eliminó ningún proyecto.', ''];
        }

        return ['', 'Se eliminaron ' . $eliminados . ' proyecto(s).'];
    }

    private function duplicarSeleccionados(bool $esInvitado): array
    {
        $ids = array_map('intval', $_POST['id'] ?? []);
        $db = Conexion::obtener();
        $duplicados = 0;

        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }

            // Nadie, sin importar el rol, puede duplicar un proyecto que no es suyo.
            $existente = $this->modeloNecesidad->obtenerPorId($id);

            if ($existente === null
                || (int) $existente['usuario_id'] !== (int) $_SESSION['usuario_id']
                || !$this->puedeModificarPorVentana($esInvitado, $existente)
            ) {
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

    private function enviarTodo(bool $esInvitado, ?array $convocatoria, bool $dentroDeVentana): array
    {
        if ($convocatoria === null) {
            return ['Selecciona una convocatoria disponible para tu usuario.', ''];
        }

        if (!$dentroDeVentana) {
            return ['La convocatoria "' . $convocatoria['nombre'] . '" no está abierta; no se pueden enviar sus proyectos.', ''];
        }

        if ($esInvitado) {
            // El invitado no elige dependencia/rol libremente — se derivan siempre de su propia
            // Facultad y del rol "Gestor"; solo se confía en qué Gestor puntual eligió del POST.
            $usuarioActual = $this->modeloUsuario->obtenerPorId((int) $_SESSION['usuario_id']);

            if ($usuarioActual === null || empty($usuarioActual['dependencia_id'])) {
                return ['Tu cuenta no tiene una Facultad asignada todavía.', ''];
            }

            $rol = $this->modeloRol->obtenerPorNombre('Gestor');

            if ($rol === null) {
                return ['El rol "Gestor" no existe en el catálogo.', ''];
            }

            $rolDestinatarioId = (int) $rol['id'];
            $dependenciaDestino = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);

            if ($dependenciaDestino === null) {
                return ['Tu Facultad asignada ya no existe.', ''];
            }

            $dependenciaDestinoNombre = $dependenciaDestino['nombre'];
            // Solo para mostrar en los mensajes de abajo — $dependenciaDestinoNombre sigue siendo
            // el nombre real (necesario para enviarTodosBorrador()).
            $dependenciaDestinoVisible = Dependencia::nombreVisible($dependenciaDestinoNombre);
            $gestoresDisponibles = $this->modeloUsuario->obtenerPorDependenciaYRol((int) $dependenciaDestino['id'], $rolDestinatarioId);
            $usuarioDestinatarioId = (int) ($_POST['usuario_destinatario_id'] ?? 0);
            $destinatarios = array_values(array_filter($gestoresDisponibles, static fn (array $u): bool => (int) $u['id'] === $usuarioDestinatarioId));

            if (empty($destinatarios)) {
                return ['Selecciona un Gestor válido de tu Facultad.', ''];
            }
        } else {
            $dependenciaDestinoNombre = trim($_POST['dependencia_destino'] ?? '');
            // Solo para mostrar en los mensajes de abajo — $dependenciaDestinoNombre sigue siendo
            // el nombre real (necesario para obtenerPorNombre()/enviarTodosBorrador()).
            $dependenciaDestinoVisible = Dependencia::nombreVisible($dependenciaDestinoNombre);
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
                    return ['Hay más de un usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoVisible . '". Selecciona a quién remitir la petición.', ''];
                }
            }
        }

        // Una vez resuelto (único con ese rol, o desambiguado arriba), se guarda quién es
        // exactamente el destinatario — si no, cualquiera con ese rol en la dependencia vería la
        // petición en Pendientes, no solo la persona elegida.
        $usuarioDestinatarioResuelto = isset($destinatarios[0]) ? (int) $destinatarios[0]['id'] : null;

        // Solo se envían los borradores de la convocatoria que se está viendo.
        $enviados = $this->modeloNecesidad->enviarTodosBorrador(
            $dependenciaDestinoNombre,
            $rolDestinatarioId,
            $usuarioDestinatarioResuelto,
            (int) $_SESSION['usuario_id'],
            (int) $convocatoria['id']
        );

        if ($enviados === 0) {
            return ['No hay proyectos en borrador para enviar en esta convocatoria.', ''];
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
            return ['', 'Se enviaron ' . $enviados . ' proyecto(s), pero no se encontró ningún usuario con el rol "' . $rol['nombre'] . '" en "' . $dependenciaDestinoVisible . '" para notificar.'];
        }

        return ['', 'Se enviaron ' . $enviados . ' proyecto(s) a ' . $destinatarios[0]['nombre'] . ' (' . $rol['nombre'] . ' en "' . $dependenciaDestinoVisible . '").'];
    }
}
