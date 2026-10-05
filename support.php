<?php
// Diagnóstico de errores 500: prepara (sin ejecutar) cada consulta SQL que hay en modelo/ y
// controlador/ contra la base real y muestra qué sección y qué tabla/columna falla.
// Uso: support.php?clave=soporte-ua   — BORRAR del servidor cuando termine el diagnóstico.

const CLAVE_SOPORTE = 'soporte-ua';

if (($_GET['clave'] ?? '') !== CLAVE_SOPORTE) {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '1');
error_reporting(E_ALL);
set_time_limit(120);

$fatal = null;
register_shutdown_function(function () use (&$fatal) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR], true)) {
        echo '<div style="background:#fee;border:2px solid #c00;padding:12px;margin:12px;font-family:sans-serif">'
            . '<b>Error fatal de PHP:</b> ' . htmlspecialchars($e['message'])
            . '<br><small>' . htmlspecialchars($e['file']) . ':' . $e['line'] . '</small></div>';
    }
});

$secciones = [
    'Actas' => 'Actas', 'Analisis' => 'Análisis', 'AnioPresupuestal' => 'Configuraciones > Años presupuestales',
    'Autogestion' => 'Autogestión', 'CategoriaGasto' => 'Configuraciones > Categorías de gasto',
    'CategoriasGasto' => 'Configuraciones > Categorías de gasto', 'ContratoComun' => 'Configuraciones > Contratos comunes',
    'Convocatoria' => 'Configuraciones > Convocatorias', 'Dashboard' => 'Dashboard (inicio)',
    'Dependencia' => 'Configuraciones > Dependencias', 'Estamento' => 'Configuraciones > Estamentos',
    'Extension' => 'Extensión', 'FuenteFinanciacion' => 'Configuraciones > Fuentes de financiación',
    'Gasto' => 'Gastos', 'Historial' => 'Historial', 'Jerarquia' => 'Configuraciones > Jerarquías',
    'Linea' => 'Configuraciones > Líneas', 'LineaInversion' => 'Configuraciones > Líneas de inversión',
    'Login' => 'Login', 'Mensaje' => 'Mensajes', 'MensajeGlobal' => 'Configuraciones > Mensaje global',
    'MenuPermiso' => 'Menú / permisos (todas las páginas)', 'Motor' => 'Configuraciones > Motores',
    'Perfil' => 'Perfil', 'PerfilProyectos' => 'Perfil de proyectos', 'Peticiones' => 'Peticiones',
    'Postgrado' => 'Postgrado', 'PresupuestoFinal' => 'Configuraciones > Presupuesto final',
    'Proyecto' => 'Configuraciones > Proyectos', 'RelojArena' => 'Configuraciones > Reloj de arena',
    'RelojArenaConsejo' => 'Reloj Consejo Superior', 'Rol' => 'Configuraciones > Roles',
    'Rubro' => 'Configuraciones > Rubros', 'RubroCategoria' => 'Configuraciones > Categorías de rubro',
    'Sede' => 'Configuraciones > Sedes', 'SinExcedentes' => 'Sin excedentes', 'Solicitud' => 'Solicitudes',
    'SublineaInversion' => 'Configuraciones > Sublíneas', 'Techos' => 'Techos', 'Unisalud' => 'Unidad de Salud',
    'Usuario' => 'Configuraciones > Usuarios', 'VariableMacroeconomica' => 'Configuraciones > Variables macro',
];

function seccionDeArchivo(string $ruta, array $secciones): string
{
    $base = preg_replace('/(Controlador)?\.php$/', '', basename($ruta));
    if (isset($secciones[$base])) {
        return $secciones[$base];
    }
    foreach ($secciones as $prefijo => $nombre) {
        if (str_starts_with($base, $prefijo) && strlen($prefijo) >= 4) {
            $mejor = $nombre;
        }
    }
    return $mejor ?? $base;
}

// Extrae las consultas SQL de un archivo PHP. Une literales concatenados ('a' . $x . 'b'); lo que
// no es literal se reemplaza por un espacio, así que algunas consultas dinámicas no se pueden validar.
function extraerConsultas(string $archivo): array
{
    $tokens = token_get_all(file_get_contents($archivo));
    $n = count($tokens);
    $consultas = [];
    $literal = function ($t): ?string {
        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
            return stripcslashes(substr($t[1], 1, -1));
        }
        return null;
    };
    $saltar = fn($t) => is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);

    for ($i = 0; $i < $n; $i++) {
        $texto = $literal($tokens[$i]);
        if ($texto === null || !preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE|REPLACE|WITH)\b/i', $texto)) {
            continue;
        }
        $linea = $tokens[$i][2];
        $sql = $texto;
        $j = $i + 1;
        while (true) {
            while ($j < $n && $saltar($tokens[$j])) $j++;
            if ($j >= $n || $tokens[$j] !== '.') break;
            $j++;
            while ($j < $n && $saltar($tokens[$j])) $j++;
            $sig = $j < $n ? $literal($tokens[$j]) : null;
            if ($sig !== null) {
                $sql .= $sig;
                $j++;
                continue;
            }
            // Expresión no literal: avanzar hasta el próximo '.' / fin de expresión a profundidad 0.
            $prof = 0;
            for (; $j < $n; $j++) {
                $t = $tokens[$j];
                if (in_array($t, ['(', '[', '{'], true)) $prof++;
                elseif (in_array($t, [')', ']', '}'], true)) { if ($prof === 0) break; $prof--; }
                elseif ($prof === 0 && in_array($t, ['.', ',', ';'], true)) break;
            }
            $sql .= ' ';
        }
        $i = $j - 1;
        $consultas[] = ['linea' => $linea, 'sql' => $sql];
    }
    return $consultas;
}

// 1) Conexión
$filas = [];
$errorConexion = null;
try {
    require_once __DIR__ . '/config/conexion.php';
    $pdo = Conexion::obtener();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $baseDatos = $pdo->query('SELECT DATABASE()')->fetchColumn();
} catch (Throwable $e) {
    $errorConexion = $e->getMessage();
}

// 2) Consultas
$total = 0;
$noVerificables = 0;
if ($errorConexion === null) {
    $archivos = array_merge(glob(__DIR__ . '/modelo/*.php') ?: [], glob(__DIR__ . '/controlador/*.php') ?: []);
    foreach ($archivos as $archivo) {
        foreach (extraerConsultas($archivo) as $c) {
            $total++;
            try {
                $pdo->prepare($c['sql'])->closeCursor();
            } catch (PDOException $e) {
                $codigo = (int) ($e->errorInfo[1] ?? 0);
                if ($codigo === 1064) { // consulta armada dinámicamente: no se puede validar aquí
                    $noVerificables++;
                    continue;
                }
                $mensaje = $e->errorInfo[2] ?? $e->getMessage();
                $tabla = '';
                if (preg_match("/Table '[^.']+\.([^']+)'/", $mensaje, $m)) {
                    $tabla = $m[1];
                } elseif (preg_match('/\b(?:FROM|INTO|UPDATE)\s+`?(\w+)/i', $c['sql'], $m)) {
                    $tabla = $m[1];
                }
                $filas[] = [
                    'seccion' => seccionDeArchivo($archivo, $secciones),
                    'tabla' => $tabla,
                    'tipo' => match ($codigo) { 1146 => 'Tabla no existe', 1054 => 'Columna no existe', default => 'Error ' . $codigo },
                    'mensaje' => $mensaje,
                    'archivo' => str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $archivo) . ':' . $c['linea'],
                    'sql' => trim(preg_replace('/\s+/', ' ', $c['sql'])),
                ];
            }
        }
    }
    usort($filas, fn($a, $b) => [$a['seccion'], $a['tabla']] <=> [$b['seccion'], $b['tabla']]);
}

// 3) Carga de los controladores (lo mismo que hace index.php en cada página)
$erroresCarga = [];
foreach (glob(__DIR__ . '/controlador/*.php') ?: [] as $archivo) {
    try {
        require_once $archivo;
    } catch (Throwable $e) {
        $erroresCarga[] = [basename($archivo), get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'];
    }
}

$tablasFaltantes = array_values(array_unique(array_column(array_filter($filas, fn($f) => $f['tipo'] === 'Tabla no existe'), 'tabla')));
sort($tablasFaltantes);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Soporte - diagnóstico</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 0; padding: 16px; background: #f5f6f8; color: #222; }
    h1 { margin: 0 0 4px; font-size: 22px; }
    .sub { color: #666; margin-bottom: 16px; font-size: 14px; }
    .tarjetas { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
    .tarjeta { background: #fff; border-radius: 8px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,.08); min-width: 160px; }
    .tarjeta b { display: block; font-size: 24px; }
    .ok { color: #1a7f37; } .mal { color: #c62828; }
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.08); font-size: 13px; }
    th, td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
    th { background: #263238; color: #fff; position: sticky; top: 0; }
    .tag { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; background: #fdecea; color: #c62828; white-space: nowrap; }
    code, details { font-size: 12px; color: #555; word-break: break-word; }
    .aviso { background: #fff8e1; border: 1px solid #f0c000; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
    h2 { font-size: 17px; margin: 24px 0 8px; }
</style>
</head>
<body>
<h1>Diagnóstico de soporte</h1>
<div class="sub">Consultas SQL del código preparadas contra la base real, sin ejecutarlas. No modifica datos.</div>
<div class="aviso">Borra <b>support.php</b> del servidor cuando termines.</div>

<?php if ($errorConexion !== null): ?>
    <div class="tarjeta mal"><b>Sin conexión</b><?= htmlspecialchars($errorConexion) ?></div>
<?php else: ?>
    <div class="tarjetas">
        <div class="tarjeta">Base de datos<b><?= htmlspecialchars($baseDatos) ?></b><small><?= htmlspecialchars($version) ?> · PHP <?= PHP_VERSION ?></small></div>
        <div class="tarjeta">Consultas revisadas<b><?= $total ?></b><small><?= $noVerificables ?> dinámicas sin verificar</small></div>
        <div class="tarjeta">Con error<b class="<?= $filas ? 'mal' : 'ok' ?>"><?= count($filas) ?></b></div>
        <div class="tarjeta">Controladores con error<b class="<?= $erroresCarga ? 'mal' : 'ok' ?>"><?= count($erroresCarga) ?></b></div>
    </div>

    <?php if ($tablasFaltantes): ?>
        <div class="aviso"><b>Tablas que no existen:</b> <?= htmlspecialchars(implode(', ', $tablasFaltantes)) ?></div>
    <?php endif; ?>

    <h2>Consultas que fallan</h2>
    <?php if (!$filas): ?>
        <p class="ok"><b>Todas las consultas verificables funcionan.</b></p>
    <?php else: ?>
        <table>
            <thead><tr><th>Sección</th><th>Tabla</th><th>Problema</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['seccion']) ?></td>
                    <td><b><?= htmlspecialchars($f['tabla']) ?></b></td>
                    <td><span class="tag"><?= htmlspecialchars($f['tipo']) ?></span></td>
                    <td><?= htmlspecialchars($f['mensaje']) ?><br><code><?= htmlspecialchars($f['archivo']) ?></code>
                        <details><summary>SQL</summary><code><?= htmlspecialchars($f['sql']) ?></code></details></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<h2>Carga de controladores</h2>
<?php if (!$erroresCarga): ?>
    <p class="ok"><b>Todos los controladores cargan sin error.</b></p>
<?php else: ?>
    <table>
        <thead><tr><th>Archivo</th><th>Error</th></tr></thead>
        <tbody>
        <?php foreach ($erroresCarga as [$archivo, $error]): ?>
            <tr><td><?= htmlspecialchars($archivo) ?></td><td><?= htmlspecialchars($error) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
