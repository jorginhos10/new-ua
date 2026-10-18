<?php
// Instalador de base de datos: muestra qué tablas/columnas que usa el código faltan en la base
// real y, con el botón "Instalar", ejecuta solo lo que falta (cada paso revisa antes si ya está).
// Uso: instalar.php?clave=instalar-ua — BORRAR del servidor cuando termine la instalación.
// Servidor MySQL 5.7: sin IF NOT EXISTS en ALTER; cada paso se protege con information_schema.

const CLAVE_INSTALAR = 'instalar-ua';

if (($_GET['clave'] ?? '') !== CLAVE_INSTALAR) {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '1');
error_reporting(E_ALL);
set_time_limit(300);

require_once __DIR__ . '/config/conexion.php';

try {
    $pdo = Conexion::obtener();
    $baseDatos = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $versionServidor = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Sin conexión a la base de datos: ' . htmlspecialchars($e->getMessage()));
}

// --- Comprobaciones contra information_schema -------------------------------------------------

function tablaExiste(PDO $pdo, string $tabla): bool
{
    $consulta = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
    $consulta->execute(['t' => $tabla]);

    return (bool) $consulta->fetchColumn();
}

function columnaExiste(PDO $pdo, string $tabla, string $columna): bool
{
    $consulta = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
    $consulta->execute(['t' => $tabla, 'c' => $columna]);

    return (bool) $consulta->fetchColumn();
}

function enumContiene(PDO $pdo, string $tabla, string $columna, string $valor): bool
{
    $consulta = $pdo->prepare('SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
    $consulta->execute(['t' => $tabla, 'c' => $columna]);
    $tipo = (string) $consulta->fetchColumn();

    return str_contains($tipo, "'" . $valor . "'");
}

function indiceExiste(PDO $pdo, string $tabla, string $indice): bool
{
    $consulta = $pdo->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND INDEX_NAME = :i');
    $consulta->execute(['t' => $tabla, 'i' => $indice]);

    return (bool) $consulta->fetchColumn();
}

// --- Pasos de instalación ----------------------------------------------------------------------
// Cada paso: sección, qué crea, 'falta' (closure: true si hay que ejecutarlo) y 'sql' (sentencias
// en orden). Los pasos se ejecutan en este orden y cada uno se vuelve a comprobar justo antes, así
// que un paso que depende de otro anterior ve el estado ya actualizado.

$pasos = [];

$pasos[] = [
    'seccion' => 'Análisis',
    'que' => 'Tabla analisis_arbol_configuracion',
    'falta' => fn() => !tablaExiste($pdo, 'analisis_arbol_configuracion'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS analisis_arbol_configuracion (
    pestana VARCHAR(20) NOT NULL PRIMARY KEY,
    expandido TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_por INT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

$pasos[] = [
    'seccion' => 'Mensajes globales',
    'que' => 'Tabla mensajes_globales (con el mensaje actual de mensaje_global)',
    'falta' => fn() => !tablaExiste($pdo, 'mensajes_globales'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS mensajes_globales (
    id INT NOT NULL AUTO_INCREMENT,
    contenido TEXT NOT NULL,
    audiencia ENUM('administrador', 'consejo_superior', 'analisis_presentacion') NOT NULL DEFAULT 'administrador',
    orden INT NOT NULL DEFAULT 0,
    actualizado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mensajes_globales_orden (orden),
    CONSTRAINT fk_mensajes_globales_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, <<<'SQL'
INSERT INTO mensajes_globales (contenido, orden, actualizado_por)
SELECT mg.contenido, 1, mg.actualizado_por
FROM mensaje_global mg
WHERE mg.id = 1 AND mg.contenido IS NOT NULL AND mg.contenido <> ''
  AND NOT EXISTS (SELECT 1 FROM mensajes_globales)
SQL],
];

$pasos[] = [
    'seccion' => 'Mensajes globales',
    'que' => 'Columna mensajes_globales.audiencia',
    'falta' => fn() => tablaExiste($pdo, 'mensajes_globales') && !columnaExiste($pdo, 'mensajes_globales', 'audiencia'),
    'sql' => ["ALTER TABLE mensajes_globales ADD COLUMN audiencia ENUM('administrador', 'consejo_superior', 'analisis_presentacion') NOT NULL DEFAULT 'administrador' AFTER contenido"],
];

$pasos[] = [
    'seccion' => 'Mensajes globales',
    'que' => "Audiencia 'analisis_presentacion' en mensajes_globales",
    'falta' => fn() => columnaExiste($pdo, 'mensajes_globales', 'audiencia') && !enumContiene($pdo, 'mensajes_globales', 'audiencia', 'analisis_presentacion'),
    'sql' => ["ALTER TABLE mensajes_globales MODIFY COLUMN audiencia ENUM('administrador', 'consejo_superior', 'analisis_presentacion') NOT NULL DEFAULT 'administrador'"],
];

$pasos[] = [
    'seccion' => 'Consejo Superior',
    'que' => 'Tabla reloj_arena_consejo',
    'falta' => fn() => !tablaExiste($pdo, 'reloj_arena_consejo'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS reloj_arena_consejo (
    id INT PRIMARY KEY DEFAULT 1,
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

$pasos[] = [
    'seccion' => 'Consejo Superior',
    'que' => 'Columna reloj_arena_consejo.etiqueta (texto bajo el anillo, por defecto "días")',
    'falta' => fn() => tablaExiste($pdo, 'reloj_arena_consejo') && !columnaExiste($pdo, 'reloj_arena_consejo', 'etiqueta'),
    'sql' => ["ALTER TABLE reloj_arena_consejo ADD COLUMN etiqueta VARCHAR(60) NOT NULL DEFAULT 'días' AFTER fecha_cierre"],
];

$pasos[] = [
    'seccion' => 'Configuraciones > Sedes',
    'que' => 'Columna sedes.nit (01 a 06 para las sedes iniciales)',
    'falta' => fn() => !columnaExiste($pdo, 'sedes', 'nit'),
    'sql' => [
        'ALTER TABLE sedes ADD COLUMN nit CHAR(2) NULL AFTER codigo',
        "UPDATE sedes SET nit = '01' WHERE id = 1",
        "UPDATE sedes SET nit = '02' WHERE id = 2",
        "UPDATE sedes SET nit = '03' WHERE id = 4",
        "UPDATE sedes SET nit = '04' WHERE id = 5",
        "UPDATE sedes SET nit = '05' WHERE id = 6",
        "UPDATE sedes SET nit = '06' WHERE id = 8",
        'ALTER TABLE sedes ADD UNIQUE KEY uq_sedes_nit (nit)',
    ],
];

$pasos[] = [
    'seccion' => 'Gastos > Categoría de gasto',
    'que' => 'Tablas categorias_gasto y rubro_categoria_gasto (con su catálogo inicial)',
    'falta' => fn() => !tablaExiste($pdo, 'categorias_gasto') || !tablaExiste($pdo, 'rubro_categoria_gasto'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS categorias_gasto (
    id VARCHAR(10) NOT NULL PRIMARY KEY,
    macro VARCHAR(80) NOT NULL,
    subcategoria VARCHAR(150) NOT NULL,
    tokens TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, <<<'SQL'
CREATE TABLE IF NOT EXISTS rubro_categoria_gasto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prefijo_codigo VARCHAR(30) NOT NULL,
    accion ENUM('categoria', 'no_aplica') NOT NULL DEFAULT 'categoria',
    categoria_id VARCHAR(10) NULL,
    es_defecto TINYINT(1) NOT NULL DEFAULT 0,
    peso DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    UNIQUE KEY uniq_prefijo_accion_categoria (prefijo_codigo, accion, categoria_id),
    CONSTRAINT fk_rubro_categoria_gasto_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_gasto (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, <<<'SQL'
INSERT IGNORE INTO categorias_gasto (id, macro, subcategoria, tokens, orden) VALUES
('EST-01', 'Estudiantes y Bienestar', 'Apoyos económicos y subsidios de manutención', 'subsidio,auxilio,manutencion,bono,almuerzo,refrigerio,beca,vulnerabilidad,permanencia,apoyo,socioeconomico', 1),
('EST-02', 'Estudiantes y Bienestar', 'Movilidad y salidas de campo estudiantiles', 'salida,campo,practica,visita,estudiante,asignatura,academica,bus,charter,pasaje,transporte', 2),
('EST-03', 'Estudiantes y Bienestar', 'Monitorías académicas y estímulos', 'monitor,monitoria,estimulo,excelencia,tutoria', 3),
('EST-04', 'Estudiantes y Bienestar', 'Deporte, arte y recreación estudiantil', 'deporte,deportivo,balon,uniforme,musical,instrumento,escenografia,cultural,arte,recreacion', 4),
('EST-05', 'Estudiantes y Bienestar', 'Salud, apoyo psicosocial y primeros auxilios', 'botiquin,medicamento,tamizaje,psicologica,psicosocial,salud,primeros auxilios', 5),
('EST-06', 'Estudiantes y Bienestar', 'Material didáctico y guías de práctica', 'guia,fotocopia,kit,maqueta,practica,material didactico,estudiante,laboratorio', 6),
('EST-07', 'Estudiantes y Bienestar', 'Grados y eventos estudiantiles', 'grado,graduacion,toga,birrete,diploma,orfebreria,medalla,evento,auditorio,alimentacion', 7),
('EST-08', 'Estudiantes y Bienestar', 'Apoyo a semilleros y ponencias estudiantiles', 'semillero,ponencia,poster,encuentro,estudiante,inscripcion,redcolsi,formacion', 8),
('DOC-01', 'Docentes e Investigación', 'Movilidad y viáticos docentes (comisiones)', 'viatico,tiquete,alojamiento,pasaje,congreso,comision,docente,profesor,pasantia,ponencia', 9),
('DOC-02', 'Docentes e Investigación', 'Reactivos químicos y materiales biológicos', 'acido,reactivo,cepa,enzima,solvente,agar,medio de cultivo,etanol,quimico,muestra,analisis', 10),
('DOC-03', 'Docentes e Investigación', 'Vidriería y material fungible de laboratorio', 'pipeta,tubo de ensayo,caja petri,guante,jeringa,filtro,matraz,crisol,vidrieria,beaker', 11),
('DOC-04', 'Docentes e Investigación', 'Calibración y mantenimiento de equipos de laboratorio', 'calibracion,cromatografo,balanza,gas especial,metrologia,equipo de laboratorio', 12),
('DOC-05', 'Docentes e Investigación', 'Publicación científica y patentes (APC)', 'publicacion,articulo,revista,indexada,apc,traduccion,patente,patentes,signos distintivos,diseno industrial', 13),
('DOC-06', 'Docentes e Investigación', 'Membresías a redes y sociedades académicas', 'membresia,afiliacion,red,sociedad,ieee,acm,cuota', 14),
('DOC-07', 'Docentes e Investigación', 'Bases de datos y software científico', 'licencia,suscripcion,base de datos,scopus,matlab,spss,software,atlas,chemdraw', 15),
('DOC-08', 'Docentes e Investigación', 'Capacitación y cualificación docente', 'diplomado,doctorado,curso,capacitacion,actualizacion,formacion,pedagogico,educacion', 16),
('DOC-09', 'Docentes e Investigación', 'Servicios técnicos de ensayo y pruebas externas', 'ensayo,espectrometria,microscopia,externo,tercerizado,prueba,investigacion', 17),
('ADM-01', 'Personal Administrativo', 'Viáticos y desplazamientos administrativos', 'viatico,conductor,auditoria,tramite,rectoria,desplazamiento,administrativo,funcionario,comision,peaje,gasolina', 18),
('ADM-02', 'Personal Administrativo', 'Capacitación y desarrollo administrativo', 'curso,gestion publica,tributaria,gestion documental,capacitacion,administrativo,funcionario', 19),
('ADM-03', 'Personal Administrativo', 'Dotación institucional y EPP', 'overol,calzado,uniforme,bata,epp,dotacion,casco,seguridad', 20),
('ADM-04', 'Personal Administrativo', 'Papelería y suministros de oficina', 'papel,resma,toner,tinta,boligrafo,archivador,carpeta,yute,papeleria,oficina,impresion', 21),
('ADM-05', 'Personal Administrativo', 'Membresías y cuotas de afiliación institucional', 'afiliacion,asociacion,camara de comercio,ascun,cuota,membresia', 22),
('ADM-06', 'Personal Administrativo', 'Mensajería, envíos y valija institucional', 'mensajeria,envio,valija,correo,certificado,encomienda,guia', 23),
('CMP-01', 'Servicios y Campus', 'Servicios públicos domiciliarios', 'energia,electricidad,agua,acueducto,alcantarillado,gas natural,factura,servicio publico', 24),
('CMP-02', 'Servicios y Campus', 'Conectividad, enlaces y telecomunicaciones', 'internet,fibra,intercampus,telefonia,enlace,red,conectividad,telecomunicacion', 25),
('CMP-03', 'Servicios y Campus', 'Aseo, limpieza y gestión de residuos', 'aseo,limpieza,desinfectante,residuo,basura,bolsa roja,bioinfeccioso,recoleccion,fumigacion,plagas,poda,riego,jardineria', 26),
('CMP-04', 'Servicios y Campus', 'Seguridad y vigilancia del campus', 'vigilancia,seguridad,ronda,monitoreo,camara,custodia', 27),
('CMP-05', 'Servicios y Campus', 'Mantenimiento y adecuaciones de infraestructura', 'mantenimiento,pintura,luminaria,reparacion,hidrosanitario,teja,adecuacion,obra,senalizacion,construccion', 28),
('CMP-06', 'Servicios y Campus', 'Mantenimiento TIC y servidores generales', 'computador,sistema,servidor,cableado,rack,soporte,informatico', 29),
('CMP-07', 'Servicios y Campus', 'Pólizas y seguros universitarios', 'poliza,seguro,multirriesgo,accidente', 30),
('CMP-08', 'Servicios y Campus', 'Arrendamientos y alquiler de bienes', 'arriendo,arrendamiento,alquiler,renta,montacargas', 31),
('FIS-01', 'Tributario, Legal y Financiero', 'Impuestos nacionales, departamentales y locales', 'impuesto,predial,ica,retencion,declaracion,gravamen,vehiculo,tributo', 32),
('FIS-02', 'Tributario, Legal y Financiero', 'Tasas, contribuciones y peajes', 'tasa,sobretasa,contribucion,derecho administrativo,fiscalizacion,auditaje', 33),
('FIS-03', 'Tributario, Legal y Financiero', 'Transferencias de ley y estampillas universitarias', 'transferencia,estampilla,fondo,ley,interna', 34),
('FIS-04', 'Tributario, Legal y Financiero', 'Costos y comisiones financieras', 'comision bancaria,cuota de manejo,interes,financiero,gmf,bancaria', 35),
('FIS-05', 'Tributario, Legal y Financiero', 'Asesoría jurídica, sentencias y conciliaciones', 'juridico,abogado,sentencia,conciliacion,litigio,honorario,judicial,judiciales', 36)
SQL, <<<'SQL'
INSERT IGNORE INTO rubro_categoria_gasto (prefijo_codigo, accion, categoria_id, es_defecto, peso) VALUES
('2.01', 'no_aplica', NULL, 1, 1.00),
('2.01.01.01.01.11', 'categoria', 'ADM-01', 1, 1.00),
('2.02.01.01.01.02', 'categoria', 'CMP-05', 1, 1.00),
('2.02.01.01.03.08', 'categoria', 'EST-04', 1, 1.00),
('2.02.01.01.03.08', 'categoria', 'CMP-05', 0, 0.80),
('2.02.01.01.04.03', 'categoria', 'CMP-05', 1, 1.00),
('2.02.01.01.04.03', 'categoria', 'DOC-03', 0, 0.80),
('2.02.01.01.04.04', 'categoria', 'CMP-05', 1, 1.00),
('2.02.01.01.04.05', 'categoria', 'CMP-06', 1, 1.00),
('2.02.01.01.04.05', 'categoria', 'ADM-04', 0, 0.80),
('2.02.01.01.04.06', 'categoria', 'CMP-05', 1, 1.00),
('2.02.01.01.04.06', 'categoria', 'DOC-03', 0, 0.80),
('2.02.01.01.04.07', 'categoria', 'CMP-02', 1, 1.00),
('2.02.01.01.04.07', 'categoria', 'CMP-06', 0, 0.80),
('2.02.01.01.04.08', 'categoria', 'DOC-03', 1, 1.00),
('2.02.01.01.04.08', 'categoria', 'DOC-04', 0, 0.80),
('2.02.01.01.04.08', 'categoria', 'EST-05', 0, 0.70),
('2.02.01.01.04.08', 'categoria', 'EST-06', 0, 0.70),
('2.02.02.01.00.01', 'categoria', 'EST-06', 1, 1.00),
('2.02.02.01.00.01', 'categoria', 'DOC-02', 0, 1.00),
('2.02.02.01.01.06', 'categoria', 'DOC-02', 1, 1.00),
('2.02.02.01.02', 'categoria', 'EST-07', 1, 1.00),
('2.02.02.01.02', 'categoria', 'EST-08', 0, 1.00),
('2.02.02.01.02', 'categoria', 'EST-02', 0, 1.00),
('2.02.02.01.02.06', 'categoria', 'EST-04', 1, 1.00),
('2.02.02.01.02.06', 'categoria', 'ADM-03', 0, 1.00),
('2.02.02.01.02.07', 'categoria', 'EST-04', 1, 1.00),
('2.02.02.01.02.07', 'categoria', 'ADM-03', 0, 1.00),
('2.02.02.01.02.08', 'categoria', 'ADM-03', 1, 1.00),
('2.02.02.01.02.09', 'categoria', 'ADM-03', 1, 1.00),
('2.02.02.01.03.01', 'categoria', 'CMP-05', 1, 1.00),
('2.02.02.01.03.02', 'categoria', 'ADM-04', 1, 1.00),
('2.02.02.01.03.03', 'categoria', 'DOC-02', 1, 1.00),
('2.02.02.01.03.04', 'categoria', 'DOC-02', 1, 1.00),
('2.02.02.01.03.04', 'categoria', 'EST-06', 0, 0.70),
('2.02.02.01.03.05', 'categoria', 'DOC-02', 1, 1.00),
('2.02.02.01.03.05', 'categoria', 'EST-06', 0, 0.70),
('2.02.02.01.03.06', 'categoria', 'DOC-03', 1, 1.00),
('2.02.02.01.03.06', 'categoria', 'EST-06', 0, 0.80),
('2.02.02.01.03.06', 'categoria', 'CMP-03', 0, 0.80),
('2.02.02.01.03.07', 'categoria', 'DOC-03', 1, 1.00),
('2.02.02.01.03.08', 'categoria', 'ADM-03', 1, 1.00),
('2.02.02.01.03.08', 'categoria', 'ADM-04', 0, 1.00),
('2.02.02.01.03.08', 'categoria', 'CMP-03', 0, 1.00),
('2.02.02.01.03.08', 'categoria', 'EST-06', 0, 1.00),
('2.02.02.01.03.08', 'categoria', 'DOC-03', 0, 1.00),
('2.02.02.01.04.01', 'categoria', 'CMP-05', 1, 1.00),
('2.02.02.01.04.01', 'categoria', 'DOC-02', 0, 1.00),
('2.02.02.01.04.02', 'categoria', 'CMP-05', 1, 1.00),
('2.02.02.01.04.04', 'categoria', 'CMP-05', 1, 1.00),
('2.02.02.01.04.04', 'categoria', 'DOC-03', 0, 0.80),
('2.02.02.01.04.05', 'categoria', 'CMP-06', 1, 1.00),
('2.02.02.01.04.05', 'categoria', 'ADM-04', 0, 0.80),
('2.02.02.01.04.07', 'categoria', 'CMP-02', 1, 1.00),
('2.02.02.01.04.07', 'categoria', 'CMP-06', 0, 0.80),
('2.02.02.01.04.07', 'categoria', 'FIS-05', 0, 1.00),
('2.02.02.02.06.03', 'categoria', 'DOC-01', 1, 1.00),
('2.02.02.02.06.03', 'categoria', 'EST-02', 0, 1.00),
('2.02.02.02.06.03', 'categoria', 'EST-08', 0, 1.00),
('2.02.02.02.06.03', 'categoria', 'ADM-01', 0, 1.00),
('2.02.02.02.06.03', 'categoria', 'EST-07', 0, 1.00),
('2.02.02.02.06.04', 'categoria', 'EST-02', 1, 1.00),
('2.02.02.02.06.04', 'categoria', 'DOC-01', 0, 1.00),
('2.02.02.02.06.04', 'categoria', 'ADM-01', 0, 1.00),
('2.02.02.02.06.04', 'categoria', 'EST-08', 0, 1.00),
('2.02.02.02.06.07', 'categoria', 'ADM-01', 1, 1.00),
('2.02.02.02.06.08', 'categoria', 'ADM-06', 1, 1.00),
('2.02.02.02.06.09', 'categoria', 'CMP-01', 1, 1.00),
('2.02.02.02.07.01', 'categoria', 'FIS-04', 1, 1.00),
('2.02.02.02.07.03', 'categoria', 'CMP-08', 1, 1.00),
('2.02.02.02.08.01', 'categoria', 'DOC-09', 1, 1.00),
('2.02.02.02.08.01', 'categoria', 'DOC-02', 0, 1.00),
('2.02.02.02.08.03', 'categoria', 'DOC-09', 1, 1.00),
('2.02.02.02.08.03', 'categoria', 'FIS-05', 0, 1.00),
('2.02.02.02.08.03', 'categoria', 'ADM-02', 0, 1.00),
('2.02.02.02.08.04', 'categoria', 'CMP-02', 1, 1.00),
('2.02.02.02.08.05', 'categoria', 'CMP-06', 1, 1.00),
('2.02.02.02.08.05', 'categoria', 'CMP-04', 0, 1.00),
('2.02.02.02.08.05', 'categoria', 'CMP-03', 0, 1.00),
('2.02.02.02.08.05', 'categoria', 'DOC-05', 0, 1.00),
('2.02.02.02.08.07', 'categoria', 'CMP-05', 1, 1.00),
('2.02.02.02.08.07', 'categoria', 'CMP-06', 0, 1.00),
('2.02.02.02.08.09', 'categoria', 'DOC-05', 1, 1.00),
('2.02.02.02.08.09', 'categoria', 'ADM-04', 0, 1.00),
('2.02.02.02.08.09', 'categoria', 'EST-06', 0, 1.00),
('2.02.02.02.09.02', 'categoria', 'DOC-08', 1, 1.00),
('2.02.02.02.09.02', 'categoria', 'ADM-02', 0, 1.00),
('2.02.02.02.09.02', 'categoria', 'EST-08', 0, 1.00),
('2.02.02.02.09.03', 'categoria', 'EST-05', 1, 1.00),
('2.02.02.02.09.04', 'categoria', 'CMP-03', 1, 1.00),
('2.02.02.02.09.06', 'categoria', 'EST-04', 1, 1.00),
('2.02.02.02.09.07', 'categoria', 'CMP-01', 1, 1.00),
('2.02.02.02.09.07', 'categoria', 'ADM-02', 0, 1.00),
('2.02.02.02.09.10', 'categoria', 'ADM-01', 1, 1.00),
('2.02.02.02.09.10', 'categoria', 'DOC-01', 0, 1.00),
('2.03.01', 'categoria', 'DOC-06', 1, 1.00),
('2.03.01', 'categoria', 'ADM-05', 0, 1.00),
('2.03.02', 'categoria', 'DOC-06', 1, 1.00),
('2.03.02', 'categoria', 'ADM-05', 0, 1.00),
('2.03.12', 'categoria', 'EST-01', 1, 1.00),
('2.03.13', 'categoria', 'EST-05', 1, 1.00),
('2.08', 'categoria', 'FIS-01', 1, 1.00),
('2.08.03', 'categoria', 'FIS-02', 1, 1.00),
('2.08.04', 'categoria', 'FIS-02', 1, 1.00),
('2.11', 'categoria', 'FIS-03', 1, 1.00)
SQL],
];

$pasos[] = [
    'seccion' => 'Gastos > Categoría de gasto',
    'que' => 'Columna gastos.categoria_gasto_id',
    'falta' => fn() => !columnaExiste($pdo, 'gastos', 'categoria_gasto_id'),
    'sql' => [
        'ALTER TABLE gastos ADD COLUMN categoria_gasto_id VARCHAR(10) NULL, ADD KEY idx_gastos_categoria_gasto (categoria_gasto_id), ADD CONSTRAINT fk_gastos_categoria_gasto FOREIGN KEY (categoria_gasto_id) REFERENCES categorias_gasto (id)',
    ],
];

$pasos[] = [
    'seccion' => 'Gastos > Categoría de gasto',
    'que' => 'Columna gastos.categoria_origen',
    'falta' => fn() => !columnaExiste($pdo, 'gastos', 'categoria_origen'),
    'sql' => ["ALTER TABLE gastos ADD COLUMN categoria_origen ENUM('manual', 'automatico', 'no_aplica') NULL"],
];

$pasos[] = [
    'seccion' => 'Gastos > Categoría de gasto',
    'que' => 'Columna gastos.categoria_confianza',
    'falta' => fn() => !columnaExiste($pdo, 'gastos', 'categoria_confianza'),
    'sql' => ['ALTER TABLE gastos ADD COLUMN categoria_confianza DECIMAL(4, 3) NULL'],
];

$pasos[] = [
    'seccion' => 'Análisis > Documentos',
    'que' => 'Tabla analisis_presentacion (lista de documentos)',
    'falta' => fn() => !tablaExiste($pdo, 'analisis_presentacion'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS analisis_presentacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    url VARCHAR(2000) NOT NULL,
    miniatura_url VARCHAR(2000) NULL,
    orden INT NOT NULL DEFAULT 0,
    actualizado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_analisis_presentacion_orden (orden),
    CONSTRAINT fk_analisis_presentacion_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

// Versión anterior de analisis_presentacion (una sola fila, sin nombre): se pasa a la nueva
// estructura conservando el enlace que tuviera guardado.
$pasos[] = [
    'seccion' => 'Análisis > Documentos',
    'que' => 'Actualizar analisis_presentacion de la versión de un solo enlace a lista (conserva el enlace)',
    'falta' => fn() => tablaExiste($pdo, 'analisis_presentacion') && !columnaExiste($pdo, 'analisis_presentacion', 'nombre'),
    'sql' => [
        'DROP TABLE IF EXISTS analisis_presentacion_v1',
        'RENAME TABLE analisis_presentacion TO analisis_presentacion_v1',
        <<<'SQL'
CREATE TABLE analisis_presentacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    url VARCHAR(2000) NOT NULL,
    miniatura_url VARCHAR(2000) NULL,
    orden INT NOT NULL DEFAULT 0,
    actualizado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_analisis_presentacion_orden (orden),
    CONSTRAINT fk_analisis_presentacion_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        "INSERT INTO analisis_presentacion (nombre, url, orden, actualizado_por) SELECT 'Presentación', url, 1, actualizado_por FROM analisis_presentacion_v1 WHERE url <> ''",
        'DROP TABLE analisis_presentacion_v1',
    ],
];

$pasos[] = [
    'seccion' => 'Análisis > Documentos',
    'que' => 'Columna analisis_presentacion.miniatura_url',
    'falta' => fn() => columnaExiste($pdo, 'analisis_presentacion', 'nombre') && !columnaExiste($pdo, 'analisis_presentacion', 'miniatura_url'),
    'sql' => ['ALTER TABLE analisis_presentacion ADD COLUMN miniatura_url VARCHAR(2000) NULL AFTER url'],
];

$pasos[] = [
    'seccion' => 'Análisis > Documentos',
    'que' => 'Fila "Actas" dentro de analisis_presentacion',
    'falta' => fn() => !columnaExiste($pdo, 'analisis_presentacion', 'nombre')
        || !(bool) $pdo->query("SELECT 1 FROM analisis_presentacion WHERE url = 'actas:interno' LIMIT 1")->fetchColumn(),
    'sql' => [<<<'SQL'
INSERT INTO analisis_presentacion (nombre, url, orden, actualizado_por)
SELECT 'Actas', 'actas:interno', (SELECT COALESCE(MAX(orden), 0) + 1 FROM analisis_presentacion), NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM analisis_presentacion WHERE url = 'actas:interno')
SQL],
];

foreach ([['lineas', 'nombre'], ['motores', 'linea_id'], ['proyectos', 'motor_id'], ['estamentos', 'nombre'], ['sedes', 'nombre']] as [$tablaEstado, $despuesDe]) {
    $pasos[] = [
        'seccion' => 'Configuraciones > Listas',
        'que' => 'Columna ' . $tablaEstado . '.estado',
        'falta' => fn() => !columnaExiste($pdo, $tablaEstado, 'estado'),
        'sql' => ['ALTER TABLE ' . $tablaEstado . " ADD COLUMN estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo' AFTER " . $despuesDe],
    ];
}

$pasos[] = [
    'seccion' => 'Análisis > Programación presupuestal',
    'que' => 'Columna presupuesto_institucional_lineas.indexacion',
    'falta' => fn() => !columnaExiste($pdo, 'presupuesto_institucional_lineas', 'indexacion'),
    'sql' => ['ALTER TABLE presupuesto_institucional_lineas ADD COLUMN indexacion VARCHAR(50) NULL AFTER descripcion'],
];

$pasos[] = [
    'seccion' => 'Análisis > Proyectos de Inversión',
    'que' => 'Columna presupuesto_institucional_lineas.codigo_pp',
    'falta' => fn() => !columnaExiste($pdo, 'presupuesto_institucional_lineas', 'codigo_pp'),
    'sql' => ['ALTER TABLE presupuesto_institucional_lineas ADD COLUMN codigo_pp VARCHAR(50) NULL AFTER indexacion'],
];

$pasos[] = [
    'seccion' => 'Análisis > Proyectos de Inversión',
    'que' => 'Columna presupuesto_institucional_lineas.detalle',
    'falta' => fn() => !columnaExiste($pdo, 'presupuesto_institucional_lineas', 'detalle'),
    'sql' => ['ALTER TABLE presupuesto_institucional_lineas ADD COLUMN detalle TEXT NULL AFTER codigo_pp'],
];

$pasos[] = [
    'seccion' => 'Análisis > Proyectos de Inversión',
    'que' => 'Columna presupuesto_institucional_lineas.fuentes_financiacion',
    'falta' => fn() => !columnaExiste($pdo, 'presupuesto_institucional_lineas', 'fuentes_financiacion'),
    'sql' => ['ALTER TABLE presupuesto_institucional_lineas ADD COLUMN fuentes_financiacion VARCHAR(500) NULL AFTER detalle'],
];

$pasos[] = [
    'seccion' => 'Análisis > Análisis de distribución',
    'que' => 'Tabla edicion_masiva_proyecto_respaldos (respaldo previo de la edición masiva de Proyecto PDI)',
    'falta' => fn() => !tablaExiste($pdo, 'edicion_masiva_proyecto_respaldos'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS edicion_masiva_proyecto_respaldos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(30) NOT NULL,
    proyecto_nuevo_id INT NOT NULL,
    proyecto_nuevo_texto VARCHAR(255) NOT NULL,
    total_filas INT NOT NULL DEFAULT 0,
    filas LONGTEXT NOT NULL,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    restaurado_en DATETIME DEFAULT NULL,
    restaurado_por INT DEFAULT NULL,
    KEY idx_edicion_masiva_respaldos_origen (origen, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

$pasos[] = [
    'seccion' => 'Análisis > Análisis de distribución',
    'que' => 'Columna snapshots.activa',
    'falta' => fn() => !columnaExiste($pdo, 'snapshots', 'activa'),
    'sql' => ['ALTER TABLE snapshots ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 0 AFTER nombre'],
];

$pasos[] = [
    'seccion' => 'Análisis > Techos y Metas',
    'que' => 'Tablas techos_versiones y techos_versiones_datos',
    'falta' => fn() => !tablaExiste($pdo, 'techos_versiones') || !tablaExiste($pdo, 'techos_versiones_datos'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS techos_versiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, <<<'SQL'
CREATE TABLE IF NOT EXISTS techos_versiones_datos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    bloque VARCHAR(20) NOT NULL,
    datos LONGTEXT NOT NULL,
    KEY idx_techos_version_bloque (version_id, bloque),
    CONSTRAINT fk_techos_versiones_datos_version FOREIGN KEY (version_id) REFERENCES techos_versiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

$pasos[] = [
    'seccion' => 'Análisis > Documentos',
    'que' => 'Tablas documentos_versiones y documentos_versiones_datos',
    'falta' => fn() => !tablaExiste($pdo, 'documentos_versiones') || !tablaExiste($pdo, 'documentos_versiones_datos'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS documentos_versiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, <<<'SQL'
CREATE TABLE IF NOT EXISTS documentos_versiones_datos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    datos LONGTEXT NOT NULL,
    CONSTRAINT fk_documentos_versiones_datos_version FOREIGN KEY (version_id) REFERENCES documentos_versiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

$pasos[] = [
    'seccion' => 'Egresos > Gastos',
    'que' => 'Tabla codigos_verificacion_envio (código de 6 dígitos al enviar gastos desde la dependencia superadmin)',
    'falta' => fn() => !tablaExiste($pdo, 'codigos_verificacion_envio'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS codigos_verificacion_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo CHAR(6) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    intentos TINYINT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_codigos_verificacion_envio_usuario (usuario_id, usado),
    CONSTRAINT fk_codigos_verificacion_envio_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

$pasos[] = [
    'seccion' => 'Configuraciones > Gastos fijos',
    'que' => 'Tabla gastos_fijos (solo se reflejan en Análisis de distribución)',
    'falta' => fn() => !tablaExiste($pdo, 'gastos_fijos'),
    'sql' => [<<<'SQL'
CREATE TABLE IF NOT EXISTS gastos_fijos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio_presupuestal_id INT NOT NULL,
    sede_id INT NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    linea_id INT NOT NULL,
    motor_id INT NOT NULL,
    proyecto_id INT NOT NULL,
    objeto_proyecto_paa VARCHAR(200) NOT NULL DEFAULT '',
    actividad VARCHAR(255) NOT NULL,
    rubro_id INT NULL,
    insumo VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(15,2) NOT NULL,
    valor_total DECIMAL(15,2) NOT NULL,
    meses VARCHAR(150) NOT NULL DEFAULT '',
    categoria_gasto_id VARCHAR(10) NULL,
    categoria_origen ENUM('manual','automatico','no_aplica') NULL,
    categoria_confianza DECIMAL(4,3) NULL,
    usuario_id INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_gastos_fijos_anio (anio_presupuestal_id),
    CONSTRAINT fk_gastos_fijos_anio FOREIGN KEY (anio_presupuestal_id) REFERENCES anios_presupuestales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL],
];

$pasos[] = [
    'seccion' => 'Gastos > Categoría de gasto',
    'que' => 'Categoría PER-01 "Servicios personales" y regla del rubro 2.01 hacia ella (antes "no aplica")',
    'falta' => fn() => tablaExiste($pdo, 'categorias_gasto')
        && !(bool) $pdo->query("SELECT 1 FROM categorias_gasto WHERE id = 'PER-01'")->fetchColumn(),
    'sql' => [
        "INSERT IGNORE INTO categorias_gasto (id, macro, subcategoria, tokens, orden) VALUES ('PER-01', 'Servicios Personales', 'Servicios personales (nómina, prestaciones y contribuciones)', 'nomina,salario,sueldo,prima,cesantias,vacaciones,bonificacion,horas extra,prestaciones,seguridad social,parafiscales,aportes,personal,planta,catedra,ocasional,supernumerario', 37)",
        "UPDATE rubro_categoria_gasto SET accion = 'categoria', categoria_id = 'PER-01' WHERE prefijo_codigo = '2.01' AND accion = 'no_aplica'",
    ],
];

// --- Instalar -----------------------------------------------------------------------------------

$resultados = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'instalar') {
    $resultados = [];
    foreach ($pasos as $paso) {
        if (!($paso['falta'])()) {
            continue;
        }
        try {
            foreach ($paso['sql'] as $sentencia) {
                $pdo->exec($sentencia);
            }
            $resultados[] = ['que' => $paso['que'], 'ok' => true, 'mensaje' => 'Instalado'];
        } catch (Throwable $e) {
            $resultados[] = ['que' => $paso['que'], 'ok' => false, 'mensaje' => $e->getMessage()];
        }
    }
}

$estado = [];
foreach ($pasos as $paso) {
    try {
        $falta = ($paso['falta'])();
    } catch (Throwable $e) {
        $falta = true;
    }
    $estado[] = ['seccion' => $paso['seccion'], 'que' => $paso['que'], 'falta' => $falta];
}
$pendientes = count(array_filter($estado, fn($fila) => $fila['falta']));

// --- Otras faltantes: consultas del código que fallan por tabla/columna inexistente -------------
// Mismo método de support.php: se prepara cada consulta (sin ejecutarla). Lo que aparezca aquí no
// está en la lista de pasos de arriba, así que no se puede instalar con el botón.

function extraerConsultasInstalador(string $archivo): array
{
    $tokens = token_get_all(file_get_contents($archivo));
    $n = count($tokens);
    $consultas = [];
    $literal = fn($t): ?string => is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING ? stripcslashes(substr($t[1], 1, -1)) : null;
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

$otrasFaltantes = [];
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
foreach (array_merge(glob(__DIR__ . '/modelo/*.php') ?: [], glob(__DIR__ . '/controlador/*.php') ?: []) as $archivo) {
    foreach (extraerConsultasInstalador($archivo) as $c) {
        try {
            $pdo->prepare($c['sql'])->closeCursor();
        } catch (PDOException $e) {
            $codigo = (int) ($e->errorInfo[1] ?? 0);
            if (!in_array($codigo, [1146, 1054], true)) {
                continue;
            }
            $mensaje = (string) ($e->errorInfo[2] ?? $e->getMessage());
            $clave = $mensaje;
            $otrasFaltantes[$clave] ??= ['mensaje' => $mensaje, 'tipo' => $codigo === 1146 ? 'Tabla no existe' : 'Columna no existe', 'archivos' => []];
            $otrasFaltantes[$clave]['archivos'][] = basename($archivo) . ':' . $c['linea'];
        }
    }
}
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

$urlPagina = 'instalar.php?clave=' . urlencode(CLAVE_INSTALAR);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalador de base de datos</title>
<style>
    :root { --fondo: #f5f6f8; --superficie: #fff; --texto: #1f2328; --tenue: #6b7280; --borde: #e5e7eb; --ok: #1a7f37; --ok-fondo: #e8f5ec; --mal: #c62828; --mal-fondo: #fdecea; --primario: #0b6bcb; }
    body { font-family: system-ui, -apple-system, Segoe UI, sans-serif; margin: 0; padding: 24px 16px; background: var(--fondo); color: var(--texto); }
    .contenedor { max-width: 1000px; margin: 0 auto; }
    h1 { margin: 0 0 4px; font-size: 24px; }
    h2 { font-size: 17px; margin: 28px 0 10px; }
    .sub { color: var(--tenue); margin: 0 0 20px; font-size: 14px; }
    .aviso { background: #fff8e1; border: 1px solid #f0c000; padding: 10px 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .resumen { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; background: var(--superficie); border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 2px rgba(0,0,0,.08); margin-bottom: 20px; }
    .resumen b { font-size: 28px; display: block; }
    .resumen small { color: var(--tenue); }
    .ok { color: var(--ok); } .mal { color: var(--mal); }
    .boton { border: 0; border-radius: 8px; padding: 12px 28px; font-size: 16px; font-weight: 700; color: #fff; background: var(--primario); cursor: pointer; }
    .boton:disabled { background: #9ca3af; cursor: not-allowed; }
    table { width: 100%; border-collapse: collapse; background: var(--superficie); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.08); font-size: 14px; }
    th, td { padding: 10px 12px; border-bottom: 1px solid var(--borde); text-align: left; vertical-align: top; }
    th { background: #263238; color: #fff; font-size: 13px; }
    tr:last-child td { border-bottom: 0; }
    .tag { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .tag-ok { background: var(--ok-fondo); color: var(--ok); }
    .tag-mal { background: var(--mal-fondo); color: var(--mal); }
    .seccion { color: var(--tenue); font-size: 13px; }
    code { font-size: 12px; color: var(--tenue); word-break: break-word; }
</style>
</head>
<body>
<div class="contenedor">
    <h1>Instalador de base de datos</h1>
    <p class="sub">Base <b><?= htmlspecialchars($baseDatos) ?></b> · MySQL <?= htmlspecialchars($versionServidor) ?> · PHP <?= PHP_VERSION ?></p>
    <div class="aviso">Haz un respaldo de la base en phpMyAdmin (Exportar) antes de instalar. Borra <b>instalar.php</b> del servidor cuando termines.</div>

    <?php if ($resultados !== null): ?>
    <h2>Resultado de la instalación</h2>
    <?php if ($resultados === []): ?>
    <p class="ok"><b>No había nada pendiente.</b></p>
    <?php else: ?>
    <table>
        <thead><tr><th>Paso</th><th style="width:120px">Resultado</th></tr></thead>
        <tbody>
        <?php foreach ($resultados as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['que']) ?><?php if (!$r['ok']): ?><br><code><?= htmlspecialchars($r['mensaje']) ?></code><?php endif; ?></td>
                <td><span class="tag <?= $r['ok'] ? 'tag-ok' : 'tag-mal' ?>"><?= $r['ok'] ? 'Instalado' : 'Error' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php endif; ?>

    <div class="resumen" style="margin-top:20px">
        <div>
            <b class="<?= $pendientes > 0 ? 'mal' : 'ok' ?>"><?= $pendientes > 0 ? $pendientes . ' pendiente' . ($pendientes === 1 ? '' : 's') : 'Todo instalado' ?></b>
            <small><?= count($pasos) - $pendientes ?> de <?= count($pasos) ?> tablas/columnas ya están en la base</small>
        </div>
        <form method="POST" action="<?= htmlspecialchars($urlPagina) ?>" onsubmit="return confirm('¿Instalar las <?= $pendientes ?> tablas/columnas que faltan en <?= htmlspecialchars($baseDatos, ENT_QUOTES) ?>?');">
            <input type="hidden" name="accion" value="instalar">
            <button type="submit" class="boton" <?= $pendientes === 0 ? 'disabled' : '' ?>>Instalar</button>
        </form>
    </div>

    <h2>Tablas y columnas</h2>
    <table>
        <thead><tr><th style="width:230px">Sección</th><th>Tabla / columna</th><th style="width:110px">Estado</th></tr></thead>
        <tbody>
        <?php foreach ($estado as $fila): ?>
            <tr>
                <td class="seccion"><?= htmlspecialchars($fila['seccion']) ?></td>
                <td><?= htmlspecialchars($fila['que']) ?></td>
                <td><span class="tag <?= $fila['falta'] ? 'tag-mal' : 'tag-ok' ?>"><?= $fila['falta'] ? 'Falta' : '✔ OK' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Otras faltantes detectadas en el código</h2>
    <?php if ($otrasFaltantes === []): ?>
    <p class="ok"><b>Ninguna: todas las consultas del código encuentran sus tablas y columnas.</b></p>
    <?php else: ?>
    <p class="sub" style="margin-bottom:10px">No están en la lista de arriba, así que el botón no las instala. Pásale esta lista al desarrollador.</p>
    <table>
        <thead><tr><th style="width:150px">Problema</th><th>Detalle</th></tr></thead>
        <tbody>
        <?php foreach ($otrasFaltantes as $f): ?>
            <tr>
                <td><span class="tag tag-mal"><?= htmlspecialchars($f['tipo']) ?></span></td>
                <td><?= htmlspecialchars($f['mensaje']) ?><br><code><?= htmlspecialchars(implode(', ', array_unique($f['archivos']))) ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
