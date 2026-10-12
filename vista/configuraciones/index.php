<?php
/**
 * Configuraciones: pantalla principal. El antiguo submenú "Listas"
 * (index.php?ruta=configuraciones&grupo=listas) ya no existe — los 10 catálogos que agrupaba
 * (Línea/Motor/Proyecto del PDI + Estamentos/Rubros/Sedes/Contratos comunes/Líneas y sublíneas de
 * inversión/Fuentes de financiación) ahora se navegan entre sí con la barra de pestañas de
 * vista/parciales/landing-lista.php, así que el botón "Listas" entra directo al primer catálogo.
 */
$tituloPagina = 'Configuraciones';
$flechaModulo = '<svg class="tarjeta-modulo-flecha" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
require __DIR__ . '/../parciales/encabezado.php';
?>

    <div class="grid-modulos">

        <section class="grupo-configuraciones">
            <h2 class="grupo-configuraciones-titulo grupo-listas">Catálogos</h2>
            <div class="box-items-config">
                <a href="index.php?ruta=lineas" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Listas</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Línea, motor y proyecto del PDI, y los catálogos de estamentos, rubros, sedes, contratos comunes y líneas de inversión y fuentes de financiación.</p>
                </a>
                <a href="index.php?ruta=presupuesto-final" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Exportar presupuesto final</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Descarga en Excel el presupuesto final de gastos principales y de autogestión, por rubro, centro de costo y proyecto.</p>
                </a>
                <a href="index.php?ruta=convocatorias" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Convocatorias de proyectos</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Crea convocatorias de Perfil de proyectos, con su reloj, fuentes, dependencias y el consolidado de cada una.</p>
                </a>
                <a href="index.php?ruta=categorias-gasto" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Categoría de gasto</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Clasifica los gastos en las 36 categorías de actividad: calcular vacíos o recalcular todo.</p>
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
                <?php if ($esSuperAdmin): ?>
                <a href="index.php?ruta=mensaje-global" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Mensaje global</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Edita el mensaje que ven en el Dashboard los administradores que no son superadmin.</p>
                </a>
                <?php endif; ?>
                <a href="index.php?ruta=jerarquias" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Jerarquías</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Gestiona el árbol de centros de costo y los techos presupuestales por nivel.</p>
                </a>
                <?php if ($esSuperAdmin): ?>
                <a href="index.php?ruta=repositorios" class="tarjeta-modulo">
                    <div class="tarjeta-modulo-cabecera">
                        <h2>Repositorios</h2>
                        <?= $flechaModulo ?>
                    </div>
                    <p class="texto-atenuado">Crea y administra snapshots (copias completas de los datos) para uso futuro en herramientas de visualización.</p>
                </a>
                <?php endif; ?>
            </div>
        </section>

    </div>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
