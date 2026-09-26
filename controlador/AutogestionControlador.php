<?php

require_once __DIR__ . '/../modelo/AutogestionItem.php';
require_once __DIR__ . '/../modelo/AutogestionPorcentaje.php';
require_once __DIR__ . '/../modelo/AutogestionAutomaticoDefinicion.php';
require_once __DIR__ . '/../modelo/AutogestionAutomaticoPermiso.php';
require_once __DIR__ . '/../modelo/Sede.php';
require_once __DIR__ . '/../modelo/Proyecto.php';
require_once __DIR__ . '/../modelo/Rubro.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';

class AutogestionControlador
{
    private AutogestionItem $modeloAutogestion;
    private AutogestionPorcentaje $modeloPorcentaje;
    private AutogestionAutomaticoDefinicion $modeloDefinicionAutomatico;
    private AutogestionAutomaticoPermiso $modeloPermisoAutomatico;
    private Sede $modeloSede;
    private Proyecto $modeloProyecto;
    private Rubro $modeloRubro;
    private Dependencia $modeloDependencia;
    private Usuario $modeloUsuario;

    private const MODULOS = [
        'extension' => 'Extensión',
        'postgrado' => 'Postgrado',
        'unisalud' => 'Unidad de Salud',
        'sin-excedentes' => 'Convenios',
    ];

    /**
     * Módulos donde tiene sentido "Definir" parámetros de fila automática (sede/proyecto/rubro/
     * actividad/insumo/meses) — Convenios/SinExcedentes queda fuera a pedido explícito del usuario.
     */
    private const MODULOS_CON_DEFINICION_AUTOMATICO = ['extension', 'postgrado', 'unisalud'];

    public function __construct()
    {
        $this->modeloAutogestion = new AutogestionItem();
        $this->modeloPorcentaje = new AutogestionPorcentaje();
        $this->modeloDefinicionAutomatico = new AutogestionAutomaticoDefinicion();
        $this->modeloPermisoAutomatico = new AutogestionAutomaticoPermiso();
        $this->modeloSede = new Sede();
        $this->modeloProyecto = new Proyecto();
        $this->modeloRubro = new Rubro();
        $this->modeloDependencia = new Dependencia();
        $this->modeloUsuario = new Usuario();
    }

    /**
     * SA = quien pertenece a la dependencia raíz (`es_raiz_superadmin`) — mismo criterio ya usado
     * en toda la sesión para "Auditar"/visibilidad en Peticiones y en los 4 controladores de
     * autogestión.
     */
    private function esUsuarioActualSuperAdminRaiz(): bool
    {
        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));

        if ($usuarioActual === null || empty($usuarioActual['dependencia_id'])) {
            return false;
        }

        $dependencia = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);

        return $dependencia !== null && !empty($dependencia['es_raiz_superadmin']);
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

        $modulos = self::MODULOS;
        $moduloActivo = isset($_GET['tab']) && array_key_exists($_GET['tab'], $modulos) ? $_GET['tab'] : array_key_first($modulos);

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $moduloPost = $_POST['modulo'] ?? $moduloActivo;

            if (array_key_exists($moduloPost, $modulos)) {
                $moduloActivo = $moduloPost;
            }

            if ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'guardar_porcentaje') {
                [$error, $exito] = $this->guardarPorcentaje($moduloActivo);
            } elseif ($accion === 'guardar_tope') {
                [$error, $exito] = $this->guardarTope($moduloActivo);
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar($moduloActivo);
            } elseif ($accion === 'actualizar_porcentaje_item') {
                [$error, $exito] = $this->actualizarPorcentajeItem();
            } elseif ($accion === 'guardar_definicion_automatico') {
                [$error, $exito] = $this->guardarDefinicionAutomatico($moduloActivo);
            } elseif ($accion === 'eliminar_definicion_automatico') {
                [$error, $exito] = $this->eliminarDefinicionAutomatico($moduloActivo);
            } elseif ($accion === 'otorgar_permiso_automatico') {
                [$error, $exito] = $this->otorgarPermisoAutomatico($moduloActivo);
            } elseif ($accion === 'revocar_permiso_automatico') {
                [$error, $exito] = $this->revocarPermisoAutomatico($moduloActivo);
            } else {
                [$error, $exito] = $this->guardar($moduloActivo);
            }
        }

        $items = $this->modeloAutogestion->obtenerTodos($moduloActivo);
        $porcentajes = $this->modeloPorcentaje->obtenerPorModulo($moduloActivo);
        $esSuperAdminRaiz = $this->esUsuarioActualSuperAdminRaiz();
        $tieneDefinicionAutomatico = in_array($moduloActivo, self::MODULOS_CON_DEFINICION_AUTOMATICO, true);

        $sedes = [];
        $proyectos = [];
        $rubros = [];
        $dependenciasTodas = [];
        $usuariosConRol = [];
        $tiposAutomaticoPorModulo = ['excedentes' => 'Excedentes nivel central'];

        if ($tieneDefinicionAutomatico) {
            $sedes = $this->modeloSede->obtenerTodas();
            $proyectos = $this->modeloProyecto->obtenerTodos();
            $rubros = $this->modeloRubro->obtenerActivosPorCategoria('autogestion');

            if ($moduloActivo === 'postgrado') {
                $tiposAutomaticoPorModulo['contrib_postgrado'] = 'Contribución a posgrado';
            }

            foreach ($items as &$itemFila) {
                $itemFila['definiciones'] = [];

                foreach (array_keys($tiposAutomaticoPorModulo) as $tipoAutomatico) {
                    $itemFila['definiciones'][$tipoAutomatico] = $this->modeloDefinicionAutomatico->listarPorItem($moduloActivo, (int) $itemFila['id'], $tipoAutomatico);
                }
            }
            unset($itemFila);
        }

        $definicionesModulo = [];

        if (!$tieneDefinicionAutomatico) {
            // Convenios/SinExcedentes: sin "Definir" (fuera de alcance a pedido del usuario).
            $tiposAutomaticoPorModulo = [];
        } elseif (!in_array($moduloActivo, ['extension', 'postgrado'], true)) {
            // Unisalud: sin ítems propios — una sola definición por (módulo, tipo, dependencia).
            foreach (array_keys($tiposAutomaticoPorModulo) as $tipoAutomatico) {
                $definicionesModulo[$tipoAutomatico] = $this->modeloDefinicionAutomatico->listarPorItem($moduloActivo, null, $tipoAutomatico);
            }
        }

        if ($esSuperAdminRaiz) {
            $dependenciasTodas = $this->modeloDependencia->obtenerActivas();
            $usuariosConRol = $this->modeloUsuario->obtenerActivosConRol();
        }

        $permisosAutomatico = $esSuperAdminRaiz ? $this->modeloPermisoAutomatico->listar($moduloActivo) : [];

        require __DIR__ . '/../vista/autogestion/index.php';
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloAutogestion->cambiarEstado($id);
        }

        $tab = $_POST['modulo'] ?? '';
        $destino = array_key_exists($tab, self::MODULOS) ? 'index.php?ruta=autogestion&tab=' . $tab : 'index.php?ruta=autogestion';

        header('Location: ' . $destino);
        exit;
    }

    private function guardar(string $modulo): array
    {
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        if ($this->modeloAutogestion->existeNombre($nombre, $modulo)) {
            return ['Ese ítem ya existe.', ''];
        }

        $this->modeloAutogestion->crear($nombre, $modulo);

        return ['', 'Ítem de autogestión agregado correctamente.'];
    }

    private function actualizar(string $modulo): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $this->modeloAutogestion->obtenerPorId($id) === null) {
            return ['El ítem que intentas editar no existe.', ''];
        }

        if ($nombre === '') {
            return ['El nombre es obligatorio.', ''];
        }

        if ($this->modeloAutogestion->existeNombre($nombre, $modulo, $id)) {
            return ['Ese ítem ya existe.', ''];
        }

        $this->modeloAutogestion->actualizar($id, $nombre);

        return ['', 'Ítem de autogestión actualizado correctamente.'];
    }

    /**
     * Tope único del módulo (Extensión, Postgrado): un solo número que sirve de denominador para
     * la tarjeta correspondiente del Dashboard — a diferencia de Costos/Inversiones/Excedentes,
     * que sí se configuran por ítem (ver actualizarPorcentajeItem()), el tope NO es por ítem ni se
     * suma entre ítems.
     */
    private function guardarTope(string $modulo): array
    {
        $tope = trim($_POST['tope'] ?? '');

        if ($tope === '') {
            $this->modeloPorcentaje->guardarTope($modulo, null);

            return ['', 'Tope actualizado correctamente.'];
        }

        if (!is_numeric($tope) || (float) $tope < 0) {
            return ['El tope debe ser un número mayor o igual a 0, o dejarse vacío ("Sin tope").', ''];
        }

        $this->modeloPorcentaje->guardarTope($modulo, (float) $tope);

        return ['', 'Tope actualizado correctamente.'];
    }

    /**
     * Guarda el Costos/Inversiones/Excedentes (y, si el ítem es de Postgrado, también Contribución
     * a posgrado) de UN ítem — a diferencia de guardarPorcentaje(), que sigue siendo el % global
     * por módulo, usado por Unidad de Salud/Convenios, que no tienen ítems propios.
     */
    private function actualizarPorcentajeItem(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $item = $id > 0 ? $this->modeloAutogestion->obtenerPorId($id) : null;

        if ($item === null) {
            return ['El ítem no existe.', ''];
        }

        $campos = ['costos', 'inversiones', 'excedentes'];

        if ($item['modulo'] === 'postgrado') {
            $campos[] = 'contribucion_postgrado';
        }

        $valores = [];

        foreach ($campos as $campo) {
            [$errorCampo, $valor] = $this->leerPorcentajeCampo($campo);

            if ($errorCampo !== '') {
                return [$errorCampo, ''];
            }

            $valores[$campo] = $valor;
        }

        $suma = array_sum(array_filter($valores, static fn ($valor) => $valor !== null));

        if ($suma > 100) {
            $nombreCampos = $item['modulo'] === 'postgrado'
                ? 'Costos, Inversiones, Excedentes y Contribución a posgrado'
                : 'Costos, Inversiones y Excedentes';

            return ['La suma de ' . $nombreCampos . ' de este ítem no puede superar el 100%.', ''];
        }

        $this->modeloAutogestion->actualizarPorcentajes(
            $id,
            $valores['costos'],
            $valores['inversiones'],
            $valores['excedentes'],
            $valores['contribucion_postgrado'] ?? null
        );

        return ['', 'Porcentajes del ítem actualizados correctamente.'];
    }

    /**
     * El % es opcional por campo: vacío significa "No aplica" (igual que el tope). Si viene
     * diligenciado, debe ser un número entre 0 y 100.
     *
     * @return array{0: string, 1: ?float} [mensaje de error, valor o null]
     */
    private function leerPorcentajeCampo(string $campo): array
    {
        $valor = trim($_POST[$campo] ?? '');

        if ($valor === '') {
            return ['', null];
        }

        if (!is_numeric($valor) || (float) $valor < 0 || (float) $valor > 100) {
            return ['Cada porcentaje debe ser un número entre 0 y 100, o dejarse vacío ("No aplica").', null];
        }

        return ['', (float) $valor];
    }

    /**
     * % global por módulo — ya solo aplica a Unidad de Salud/Convenios (Extensión y Postgrado
     * tienen ítems propios, ver actualizarPorcentajeItem()).
     */
    private function guardarPorcentaje(string $modulo): array
    {
        $campos = ['costos', 'inversiones', 'excedentes'];
        $valores = [];

        foreach ($campos as $campo) {
            if (($_POST['no_aplica_' . $campo] ?? '') === '1') {
                $valores[$campo] = null;
                continue;
            }

            $valor = $_POST[$campo] ?? '';

            if (!is_numeric($valor) || (float) $valor < 0 || (float) $valor > 100) {
                return ['Cada porcentaje debe ser un número entre 0 y 100, o marcarse como No aplica.', ''];
            }

            $valores[$campo] = (float) $valor;
        }

        $suma = array_sum(array_filter($valores, static fn ($valor) => $valor !== null));

        if ($suma > 100) {
            return ['La suma de Costos, Inversiones y Excedentes no puede superar el 100%.', ''];
        }

        $this->modeloPorcentaje->guardar($modulo, $valores['costos'], $valores['inversiones'], $valores['excedentes']);

        return ['', 'Porcentajes actualizados correctamente.'];
    }

    /**
     * Guarda (o edita, si viene "id") la plantilla de campos que se usa al generar la fila
     * automática de Excedentes/Contribución a posgrado de un ítem+dependencia, en vez de las
     * constantes fijas AUTOMATICO_* de cada controlador (ver
     * ExtensionControlador::regenerarAutomaticosDeIngreso()). Solo aplica a Extensión/Postgrado/
     * Unisalud (self::MODULOS_CON_DEFINICION_AUTOMATICO) — Convenios queda fuera.
     */
    private function guardarDefinicionAutomatico(string $modulo): array
    {
        if (!in_array($modulo, self::MODULOS_CON_DEFINICION_AUTOMATICO, true)) {
            return ['Este módulo no admite definir parámetros de fila automática.', ''];
        }

        $esSuperAdminRaiz = $this->esUsuarioActualSuperAdminRaiz();
        $tipo = trim($_POST['tipo'] ?? '');
        $tiposValidos = $modulo === 'postgrado' ? ['excedentes', 'contrib_postgrado'] : ['excedentes'];

        if (!in_array($tipo, $tiposValidos, true)) {
            return ['El tipo de fila automática no es válido.', ''];
        }

        $autogestionId = null;

        if (in_array($modulo, ['extension', 'postgrado'], true)) {
            $autogestionId = (int) ($_POST['autogestion_id'] ?? 0);
            $item = $autogestionId > 0 ? $this->modeloAutogestion->obtenerPorId($autogestionId) : null;

            if ($item === null || $item['modulo'] !== $modulo) {
                return ['El ítem de autogestión no existe.', ''];
            }
        }

        $usuarioActual = $this->modeloUsuario->obtenerPorId((int) ($_SESSION['usuario_id'] ?? 0));
        $dependenciaPropia = null;

        if ($usuarioActual !== null && !empty($usuarioActual['dependencia_id'])) {
            $dependenciaUsuario = $this->modeloDependencia->obtenerPorId((int) $usuarioActual['dependencia_id']);
            $dependenciaPropia = $dependenciaUsuario['nombre'] ?? null;
        }

        $dependenciaPost = trim($_POST['dependencia'] ?? '');

        if ($esSuperAdminRaiz) {
            // Solo el SA puede dejarla en blanco (default global) o elegir una dependencia distinta
            // a la suya.
            if ($dependenciaPost === '') {
                $dependenciaFinal = null;
            } elseif ($this->modeloDependencia->obtenerPorNombre($dependenciaPost) === null) {
                return ['La dependencia elegida no existe.', ''];
            } else {
                $dependenciaFinal = $dependenciaPost;
            }
        } else {
            // Un usuario normal siempre queda fijado a su propia dependencia, sin importar lo que
            // haya llegado en el campo (que en su formulario ni siquiera es editable) — seguridad
            // del lado del servidor, no solo ocultar el campo en el HTML.
            if ($dependenciaPropia === null) {
                return ['Tu usuario no tiene una dependencia asignada — no se puede definir sin ella.', ''];
            }

            $dependenciaFinal = $dependenciaPropia;
        }

        $sedeId = (int) ($_POST['sede_id'] ?? 0);
        $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
        $rubroId = (int) ($_POST['rubro_id'] ?? 0);
        $actividad = trim($_POST['actividad'] ?? '');
        $insumo = trim($_POST['insumo'] ?? '');

        $sedesValidas = array_column($this->modeloSede->obtenerTodas(), 'id');
        $rubrosValidos = array_column($this->modeloRubro->obtenerActivosPorCategoria('autogestion'), 'id');

        if ($sedeId <= 0 || !in_array($sedeId, $sedesValidas, true)) {
            return ['Selecciona una sede válida.', ''];
        }

        if ($proyectoId <= 0 || $this->modeloProyecto->obtenerPorId($proyectoId) === null) {
            return ['Selecciona un proyecto PDI válido.', ''];
        }

        if ($rubroId <= 0 || !in_array($rubroId, $rubrosValidos, true)) {
            return ['Selecciona un rubro válido.', ''];
        }

        if ($actividad === '' || $insumo === '') {
            return ['La actividad y el insumo son obligatorios.', ''];
        }

        $meses = array_unique(array_filter(
            array_map('intval', $_POST['meses'] ?? []),
            static fn (int $mes): bool => $mes >= 1 && $mes <= 12
        ));

        if (empty($meses)) {
            return ['Selecciona al menos un mes de ejecución.', ''];
        }

        sort($meses);

        $datos = [
            'modulo' => $modulo,
            'autogestion_id' => $autogestionId,
            'tipo' => $tipo,
            'dependencia' => $dependenciaFinal,
            'sede_id' => $sedeId,
            'proyecto_id' => $proyectoId,
            'rubro_id' => $rubroId,
            'actividad' => $actividad,
            'insumo' => $insumo,
            'meses' => implode(',', $meses),
            'creado_por' => (int) ($_SESSION['usuario_id'] ?? 0),
        ];

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $existente = $this->modeloDefinicionAutomatico->obtenerPorId($id);

            if ($existente === null || $existente['modulo'] !== $modulo) {
                return ['La definición que intentas editar no existe.', ''];
            }

            // Solo el SA puede reasignarle la dependencia a una definición ya creada — un usuario
            // normal solo puede editar el resto de campos de la suya propia.
            if (!$esSuperAdminRaiz && $existente['dependencia'] !== $dependenciaFinal) {
                return ['No puedes cambiar la dependencia de esta definición.', ''];
            }

            $this->modeloDefinicionAutomatico->actualizar($id, $datos);

            return ['', 'Definición actualizada correctamente.'];
        }

        if ($this->modeloDefinicionAutomatico->buscarExacta($modulo, $autogestionId, $tipo, $dependenciaFinal) !== null) {
            return ['Ya existe una definición para este ítem, tipo y dependencia — edítala en vez de crear otra.', ''];
        }

        $this->modeloDefinicionAutomatico->crear($datos);

        return ['', 'Definición creada correctamente.'];
    }

    private function eliminarDefinicionAutomatico(string $modulo): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existente = $id > 0 ? $this->modeloDefinicionAutomatico->obtenerPorId($id) : null;

        if ($existente === null || $existente['modulo'] !== $modulo) {
            return ['La definición que intentas eliminar no existe.', ''];
        }

        $esSuperAdminRaiz = $this->esUsuarioActualSuperAdminRaiz();

        if (!$esSuperAdminRaiz && (int) $existente['creado_por'] !== (int) ($_SESSION['usuario_id'] ?? 0)) {
            return ['No puedes eliminar una definición creada por otro usuario.', ''];
        }

        $this->modeloDefinicionAutomatico->eliminar($id);

        return ['', 'Definición eliminada correctamente.'];
    }

    /**
     * Otorga a un usuario puntual (elegido por selector "Rol · Nombre (correo)") el mismo poder que
     * ya tiene el SA para editar/borrar filas automáticas de un (módulo, tipo) — ver
     * AutogestionAutomaticoPermiso. Solo el SA puede otorgar/revocar.
     */
    private function otorgarPermisoAutomatico(string $modulo): array
    {
        if (!$this->esUsuarioActualSuperAdminRaiz()) {
            return ['Solo el superadmin puede otorgar este permiso.', ''];
        }

        $tipo = trim($_POST['tipo'] ?? '');
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);

        if ($tipo === '' || $usuarioId <= 0) {
            return ['Selecciona un tipo y un usuario válidos.', ''];
        }

        $this->modeloPermisoAutomatico->otorgar($modulo, $tipo, $usuarioId, (int) ($_SESSION['usuario_id'] ?? 0));

        return ['', 'Permiso otorgado correctamente.'];
    }

    private function revocarPermisoAutomatico(string $modulo): array
    {
        if (!$this->esUsuarioActualSuperAdminRaiz()) {
            return ['Solo el superadmin puede revocar este permiso.', ''];
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            return ['Permiso no válido.', ''];
        }

        $this->modeloPermisoAutomatico->revocar($id);

        return ['', 'Permiso revocado correctamente.'];
    }
}
