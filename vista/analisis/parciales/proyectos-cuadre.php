<?php
/**
 * Cuadre por código raíz entre "Proyectos" y Programación presupuestal Egresos — solo
 * informativo (nunca bloquea guardar ni importar), solo en Tiempo real. Variable esperada:
 * $cuadreProyectos (array de ['codigo','descripcion','totalProyectos','totalPresupuesto'
 * (?float, null si el código no existe en Egresos),'diferencia' (?float)]).
 */
if (empty($cuadreProyectos)) {
    return;
}
?>
<div class="tarjeta" style="margin: 0.5rem 0.5rem 0; flex-shrink: 0;">
    <h1 style="font-size: 1rem;">Cuadre contra Programación presupuestal (Egresos)</h1>
    <table class="tabla-usuarios">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Total en Proyectos</th>
                <th>Total en Progr. presupuestal (Egresos)</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cuadreProyectos as $fila): ?>
            <tr>
                <td><?= htmlspecialchars($fila['codigo']) ?></td>
                <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                <td>$<?= number_format($fila['totalProyectos'], 2, ',', '.') ?></td>
                <td><?= $fila['totalPresupuesto'] !== null ? '$' . number_format($fila['totalPresupuesto'], 2, ',', '.') : '—' ?></td>
                <td>
                    <?php if ($fila['totalPresupuesto'] === null): ?>
                        <span class="texto-atenuado">Sin código coincidente</span>
                    <?php elseif (abs($fila['diferencia']) < 0.01): ?>
                        <span style="color:#1e6b3c;">✓ Cuadra</span>
                    <?php else: ?>
                        <span style="color:#a83232;">⚠ Diferencia: $<?= number_format($fila['diferencia'], 2, ',', '.') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
