<?php

require_once __DIR__ . '/../modelo/AnioPresupuestal.php';
require_once __DIR__ . '/../modelo/Dependencia.php';
require_once __DIR__ . '/../modelo/PresupuestoDependencia.php';
require_once __DIR__ . '/../modelo/Usuario.php';
require_once __DIR__ . '/../modelo/Rol.php';
require_once __DIR__ . '/../modelo/Mensaje.php';
require_once __DIR__ . '/../modelo/PresupuestoVersion.php';

class AnioPresupuestalControlador
{
    private AnioPresupuestal $modeloAnio;
    private Dependencia $modeloDependencia;
    private PresupuestoDependencia $modeloPresupuestoDependencia;
    private Usuario $modeloUsuario;
    private Rol $modeloRol;
    private Mensaje $modeloMensaje;
    private PresupuestoVersion $modeloVersion;

    public function __construct()
    {
        $this->modeloAnio = new AnioPresupuestal();
        $this->modeloDependencia = new Dependencia();
        $this->modeloPresupuestoDependencia = new PresupuestoDependencia();
        $this->modeloUsuario = new Usuario();
        $this->modeloRol = new Rol();
        $this->modeloMensaje = new Mensaje();
        $this->modeloVersion = new PresupuestoVersion();
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
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'cambiar_estado') {
                $this->cambiarEstado();
            } elseif ($accion === 'eliminar') {
                [$error, $exito] = $this->eliminar();
            } elseif ($accion === 'actualizar') {
                [$error, $exito] = $this->actualizar();
            } else {
                [$error, $exito] = $this->guardar();
            }
        }

        $anios = $this->modeloAnio->obtenerTodos();

        require __DIR__ . '/../vista/anios-presupuestales/index.php';
    }

    public function configurarPresupuestos(): void
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

        $anioId = isset($_GET['anio_id']) ? (int) $_GET['anio_id'] : 0;
        $anio = $anioId > 0 ? $this->modeloAnio->obtenerPorId($anioId) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $anio !== null) {
            $accion = $_POST['accion'] ?? '';
            [$error, $exito] = $this->guardarPresupuestos($accion === 'notificar');
        }

        $todosAnios = $this->modeloAnio->obtenerTodos();
        $dependencias = array_values(array_filter(
            $this->modeloDependencia->obtenerActivas(),
            static fn (array $dependencia): bool => (int) ($dependencia['no_monetizable'] ?? 0) !== 1
        ));
        $gruposDependencia = $this->agruparPorFamilia($dependencias);
        $presupuestosActuales = $anio !== null ? $this->modeloPresupuestoDependencia->obtenerPorAnio($anioId) : [];

        require __DIR__ . '/../vista/anios-presupuestales/configurar.php';
    }

    /**
     * Agrupa las dependencias por familia (2 primeros dígitos del código jerárquico),
     * usando como título del grupo el nombre de la cabeza de esa familia (código familia+"0101").
     * Las dependencias sin código de 6 dígitos (ej. ADMIN) caen en un grupo "General".
     */
    private function agruparPorFamilia(array $dependencias): array
    {
        $porCodigo = [];
        foreach ($dependencias as $dependencia) {
            $porCodigo[$dependencia['codigo']] = $dependencia;
        }

        $grupos = [];
        foreach ($dependencias as $dependencia) {
            $codigo = $dependencia['codigo'];

            if ($codigo === '0' || strlen($codigo) !== 6) {
                $familia = 'general';
                $titulo = 'General';
                $dependencia['es_cabeza_familia'] = false;
            } else {
                $familia = substr($codigo, 0, 2);
                $codigoFamiliaRaiz = $familia . '0101';
                $titulo = $porCodigo[$codigoFamiliaRaiz]['nombre'] ?? ('Familia ' . $familia);
                $dependencia['es_cabeza_familia'] = $codigo === $codigoFamiliaRaiz;
            }

            if (!isset($grupos[$familia])) {
                $grupos[$familia] = ['titulo' => $titulo, 'items' => []];
            }

            $grupos[$familia]['items'][] = $dependencia;
        }

        uasort($grupos, static fn (array $a, array $b) => $a['titulo'] <=> $b['titulo']);

        return $grupos;
    }

    private function guardarPresupuestos(bool $notificar): array
    {
        $anioId = (int) ($_POST['anio_id'] ?? 0);
        $anio = $this->modeloAnio->obtenerPorId($anioId);

        if ($anioId <= 0 || $anio === null) {
            return ['El año presupuestal no existe.', ''];
        }

        $minimos = $_POST['minimo'] ?? [];
        $techos = $_POST['techo'] ?? [];

        $techosAnteriores = $this->modeloPresupuestoDependencia->obtenerMapaPorAnio()[$anioId] ?? [];
        $dependenciasConTechoNuevo = [];
        $cambiosVersion = [];

        foreach ($this->modeloDependencia->obtenerActivas() as $dependencia) {
            if ((int) ($dependencia['no_monetizable'] ?? 0) === 1) {
                continue;
            }

            $dependenciaId = (int) $dependencia['id'];
            $minimoTexto = trim((string) ($minimos[$dependenciaId] ?? ''));
            $techoTexto = trim((string) ($techos[$dependenciaId] ?? ''));

            $minimo = $minimoTexto !== '' && is_numeric($minimoTexto) ? (float) $minimoTexto : null;
            $techo = $techoTexto !== '' && is_numeric($techoTexto) ? (float) $techoTexto : null;

            if ($minimo === null && $techo === null) {
                continue;
            }

            $minimoAnterior = isset($techosAnteriores[$dependenciaId]['minimo']) ? (float) $techosAnteriores[$dependenciaId]['minimo'] : null;
            $techoAnterior = isset($techosAnteriores[$dependenciaId]['techo']) ? (float) $techosAnteriores[$dependenciaId]['techo'] : null;

            if ($minimo === $minimoAnterior && $techo === $techoAnterior) {
                continue;
            }

            if ($techo !== null && $techo !== $techoAnterior) {
                $dependenciasConTechoNuevo[] = ['dependencia' => $dependencia, 'techo' => $techo];
            }

            $this->modeloPresupuestoDependencia->guardar($anioId, $dependenciaId, $minimo, $techo);

            $cambiosVersion[] = [
                'dependencia_id' => $dependenciaId,
                'minimo_anterior' => $minimoAnterior,
                'minimo_nuevo' => $minimo,
                'techo_anterior' => $techoAnterior,
                'techo_nuevo' => $techo,
            ];
        }

        if (!empty($cambiosVersion)) {
            $this->modeloVersion->crearVersion($anioId, (int) ($_SESSION['usuario_id'] ?? 0), $cambiosVersion);
        }

        if (!$notificar) {
            return ['', 'Presupuestos por dependencia actualizados correctamente.'];
        }

        if (empty($dependenciasConTechoNuevo)) {
            return ['', 'Presupuestos actualizados. No había techos nuevos o modificados para notificar.'];
        }

        $notificados = $this->notificarAvaladores($anio, $dependenciasConTechoNuevo);

        if ($notificados === 0) {
            return ['', 'Presupuestos actualizados, pero no se encontró ningún avalador asignado a las dependencias modificadas.'];
        }

        return ['', 'Presupuestos actualizados y se notificó a ' . $notificados . ' avalador(es).'];
    }

    private function notificarAvaladores(array $anio, array $dependenciasConTechoNuevo): int
    {
        $rolAvaladorId = null;
        foreach ($this->modeloRol->obtenerTodos() as $rol) {
            if ($rol['nombre'] === 'Avalador') {
                $rolAvaladorId = (int) $rol['id'];
                break;
            }
        }

        if ($rolAvaladorId === null) {
            return 0;
        }

        $remitenteId = (int) ($_SESSION['usuario_id'] ?? 0);
        $notificados = 0;

        foreach ($dependenciasConTechoNuevo as $item) {
            $avaladores = $this->modeloUsuario->obtenerPorDependenciaYRol((int) $item['dependencia']['id'], $rolAvaladorId);

            foreach ($avaladores as $avalador) {
                $asunto = 'Techo presupuestal actualizado — ' . $item['dependencia']['nombre'];
                $cuerpo = 'Se definió un nuevo techo presupuestal para "' . $item['dependencia']['nombre']
                    . '" en el año ' . $anio['anio'] . ': $' . number_format($item['techo'], 2) . '.';

                $this->modeloMensaje->crear($remitenteId, (int) $avalador['id'], $asunto, $cuerpo);
                $notificados++;
            }
        }

        return $notificados;
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->modeloAnio->cambiarEstado($id);
        }

        header('Location: index.php?ruta=anios-presupuestales');
        exit;
    }

    private function guardar(): array
    {
        $anio = trim($_POST['anio'] ?? '');
        $presupuesto = trim($_POST['presupuesto'] ?? '');

        if ($anio === '' || !ctype_digit($anio)) {
            return ['El año es obligatorio y debe ser un número válido.', ''];
        }

        if ($presupuesto === '' || !is_numeric($presupuesto) || (float) $presupuesto < 0) {
            return ['El presupuesto es obligatorio y debe ser un número válido.', ''];
        }

        $anio = (int) $anio;

        if ($anio < 2000 || $anio > 2100) {
            return ['Ingresa un año dentro de un rango válido.', ''];
        }

        if ($this->modeloAnio->existeAnio($anio)) {
            return ['Ese año ya existe.', ''];
        }

        $this->modeloAnio->crear($anio, (float) $presupuesto);

        return ['', 'Año presupuestal agregado correctamente.'];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        $anio = trim($_POST['anio'] ?? '');
        $presupuesto = trim($_POST['presupuesto'] ?? '');

        if ($id <= 0 || $this->modeloAnio->obtenerPorId($id) === null) {
            return ['El año presupuestal que intentas editar no existe.', ''];
        }

        if ($anio === '' || !ctype_digit($anio)) {
            return ['El año es obligatorio y debe ser un número válido.', ''];
        }

        if ($presupuesto === '' || !is_numeric($presupuesto) || (float) $presupuesto < 0) {
            return ['El presupuesto es obligatorio y debe ser un número válido.', ''];
        }

        $anio = (int) $anio;

        if ($anio < 2000 || $anio > 2100) {
            return ['Ingresa un año dentro de un rango válido.', ''];
        }

        if ($this->modeloAnio->existeAnio($anio, $id)) {
            return ['Ese año ya existe.', ''];
        }

        $this->modeloAnio->actualizar($id, $anio, (float) $presupuesto);

        return ['', 'Año presupuestal actualizado correctamente.'];
    }

    private function eliminar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $this->modeloAnio->obtenerPorId($id) === null) {
            return ['El año presupuestal que intentas eliminar no existe.', ''];
        }

        if (!$this->modeloAnio->eliminar($id)) {
            return ['No puedes eliminar este año porque tiene datos asociados (gastos, solicitudes u otros registros).', ''];
        }

        return ['', 'Año presupuestal eliminado correctamente.'];
    }
}
