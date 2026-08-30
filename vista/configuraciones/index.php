<?php
$tituloPagina = 'Configuraciones';
$flechaModulo = '<svg class="tarjeta-modulo-flecha" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="grid-modulos">

        <section class="grupo-configuraciones">
            <h2 class="grupo-configuraciones-titulo grupo-pdi">PDI</h2>
            <div class="box-items-config">
                <a href="index.php?ruta=lineas" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Línea</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona las líneas de inversión (ej. L1, L2, L3).</p>
                </a>
                <a href="index.php?ruta=motores" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Motor</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona los motores, asociados a una línea (ej. M1, M2, M3).</p>
                </a>
                <a href="index.php?ruta=proyectos" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Proyecto</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona los proyectos, asociados a un motor (ej. P1, P2, P3).</p>
                </a>
            </div>
        </section>

        <section class="grupo-configuraciones">
            <h2 class="grupo-configuraciones-titulo grupo-listas">Listas</h2>
            <div class="box-items-config">
                <a href="index.php?ruta=estamentos" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Estamentos</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el catálogo de estamentos.</p>
                </a>
                <a href="index.php?ruta=rubros" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Rubros</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el catálogo de rubros presupuestales.</p>
                </a>
                <a href="index.php?ruta=sedes" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Sedes</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona las sedes de la universidad.</p>
                </a>
                <a href="index.php?ruta=facultades" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Facultades</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el catálogo de facultades para el registro de usuarios.</p>
                </a>
                <a href="index.php?ruta=contratos-comunes" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Contratos comunes</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el catálogo de contratos comunes (BCC) para el campo "Contratos comunes" de los gastos.</p>
                </a>
                <a href="index.php?ruta=lineas-inversion" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Líneas de inversión</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el catálogo de líneas de inversión para Perfil de proyectos.</p>
                </a>
                <a href="index.php?ruta=sublineas-inversion" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Sublíneas de inversión</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el catálogo de sublíneas de inversión, condicionadas a una línea, para Perfil de proyectos.</p>
                </a>
                <a href="index.php?ruta=rubro-categorias" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Categorías de rubros</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Marca en qué secciones (Autogestión, Egresos, Proyectos) debe aparecer cada capítulo.sección de rubro.</p>
                </a>
            </div>
        </section>

        <section class="grupo-configuraciones">
            <h2 class="grupo-configuraciones-titulo grupo-variables">Variables presupuestales</h2>
            <div class="box-items-config">
                <a href="index.php?ruta=anios-presupuestales" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Año presupuestal</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona los años presupuestales y su estado.</p>
                </a>
                <a href="index.php?ruta=variables-macroeconomicas" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Variables macroeconómicas</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona indicadores de referencia (IPC, SMLV, UVT, etc.) por año presupuestal.</p>
                </a>
                <a href="index.php?ruta=autogestion" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Autogestión</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona los porcentajes a gastos, excedentes e inversiones.</p>
                </a>
            </div>
        </section>

        <section class="grupo-configuraciones">
            <h2 class="grupo-configuraciones-titulo grupo-usuarios">Usuarios y responsabilidades</h2>
            <div class="box-items-config">
                <a href="index.php?ruta=usuarios" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Usuarios</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona administradores e invitados del sistema.</p>
                </a>
                <a href="index.php?ruta=roles" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Roles</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona los roles y sus permisos.</p>
                </a>
                <a href="index.php?ruta=dependencias" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Dependencias</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el catálogo de dependencias.</p>
                </a>
                <a href="index.php?ruta=reloj-arena" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Reloj de arena</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Define las fechas de inicio y cierre del reloj de arena del dashboard.</p>
                </a>
                <a href="index.php?ruta=jerarquias" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Jerarquías</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el árbol de centros de costo y los techos presupuestales por nivel.</p>
                </a>
            </div>
        </section>

    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
