-- Faltante en producción (jorginho_presupuestos) respecto al código actual, comparado con sql/actual.sql
-- (dump del 2026-10-05). Sin esto, las páginas que usan estas tablas o columnas devuelven 500.
-- EJECUTAR UNA SOLA VEZ en phpMyAdmin. Servidor MySQL 5.7: no usa IF NOT EXISTS en ALTER.

-- 1) Árbol de Análisis: estado desplegado/recogido por pestaña (?ruta=analisis).
CREATE TABLE IF NOT EXISTS analisis_arbol_configuracion (
    pestana VARCHAR(20) NOT NULL PRIMARY KEY,
    expandido TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_por INT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Mensajes globales (varias diapositivas en el inicio), con las audiencias de Consejo Superior
--    y de la pestaña "Presentación" de Análisis (mensaje editable que explica esa sección).
--    El primer mensaje toma el contenido actual de mensaje_global.
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mensajes_globales (contenido, orden, actualizado_por)
SELECT mg.contenido, 1, mg.actualizado_por
FROM mensaje_global mg
WHERE mg.id = 1
  AND mg.contenido IS NOT NULL
  AND mg.contenido <> ''
  AND NOT EXISTS (SELECT 1 FROM mensajes_globales);

-- 3) Reloj del Consejo Superior.
CREATE TABLE IF NOT EXISTS reloj_arena_consejo (
    id INT PRIMARY KEY DEFAULT 1,
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) NIT de sede (dos dígitos, 00 a 09, único).
ALTER TABLE sedes ADD COLUMN nit CHAR(2) NULL AFTER codigo;

UPDATE sedes SET nit = '01' WHERE id = 1;
UPDATE sedes SET nit = '02' WHERE id = 2;
UPDATE sedes SET nit = '03' WHERE id = 4;
UPDATE sedes SET nit = '04' WHERE id = 5;
UPDATE sedes SET nit = '05' WHERE id = 6;
UPDATE sedes SET nit = '06' WHERE id = 8;

ALTER TABLE sedes
    MODIFY COLUMN nit CHAR(2) NOT NULL,
    ADD UNIQUE KEY uq_sedes_nit (nit);

-- 5) Categoría de gasto: catálogo, mapeo rubro → categoría y 3 columnas en gastos.
CREATE TABLE IF NOT EXISTS categorias_gasto (
    id VARCHAR(10) NOT NULL PRIMARY KEY,
    macro VARCHAR(80) NOT NULL,
    subcategoria VARCHAR(150) NOT NULL,
    tokens TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rubro_categoria_gasto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prefijo_codigo VARCHAR(30) NOT NULL,
    accion ENUM('categoria', 'no_aplica') NOT NULL DEFAULT 'categoria',
    categoria_id VARCHAR(10) NULL,
    es_defecto TINYINT(1) NOT NULL DEFAULT 0,
    peso DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    UNIQUE KEY uniq_prefijo_accion_categoria (prefijo_codigo, accion, categoria_id),
    CONSTRAINT fk_rubro_categoria_gasto_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_gasto (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('FIS-05', 'Tributario, Legal y Financiero', 'Asesoría jurídica, sentencias y conciliaciones', 'juridico,abogado,sentencia,conciliacion,litigio,honorario,judicial,judiciales', 36);

-- Prefijo CPC del rubro → candidatas. Gana el prefijo más largo que coincida con el código del rubro.
-- es_defecto = 1 indica la candidata que se usa cuando el texto no desempata (nunca queda vacío).
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
('2.11', 'categoria', 'FIS-03', 1, 1.00);

-- Gastos: categoría (nullable), origen de la asignación y confianza (0–1) que el motor guardó.
ALTER TABLE gastos
    ADD COLUMN categoria_gasto_id VARCHAR(10) NULL,
    ADD COLUMN categoria_origen ENUM('manual', 'automatico', 'no_aplica') NULL,
    ADD COLUMN categoria_confianza DECIMAL(4, 3) NULL,
    ADD KEY idx_gastos_categoria_gasto (categoria_gasto_id),
    ADD CONSTRAINT fk_gastos_categoria_gasto FOREIGN KEY (categoria_gasto_id) REFERENCES categorias_gasto (id);

-- 6) Pestaña "Presentación" de Análisis: lista de enlaces de OneDrive/SharePoint (presentación,
--    Word, Excel o PDF) con nombre y orden propio — ya no es un solo enlace sin nombre.
CREATE TABLE IF NOT EXISTS analisis_presentacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    url VARCHAR(2000) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    actualizado_por INT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_analisis_presentacion_orden (orden),
    CONSTRAINT fk_analisis_presentacion_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) Indexación por línea de Programación presupuestal (solo hojas): el criterio con el que se
--    proyecta esa línea (ej. "IPC", "SMMLV", "ICES"), diligenciado desde la plantilla Excel y
--    mostrado como un punto de color (con el texto como tooltip) junto al valor del año vigente.
ALTER TABLE presupuesto_institucional_lineas
    ADD COLUMN indexacion VARCHAR(50) NULL AFTER descripcion;
