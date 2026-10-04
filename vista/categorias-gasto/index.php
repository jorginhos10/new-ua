<?php $tituloPagina = 'Categoría de gasto'; require __DIR__ . '/../parciales/encabezado.php'; ?>

    <div class="tarjeta">
        <div class="cabecera-modulo">
            <div>
                <h1>Categoría de gasto</h1>
                <p class="texto-atenuado">
                    Asigna a cada gasto una de las 36 categorías de actividad a partir de su rubro (y, si el rubro admite
                    varias, del texto de actividad e insumo). Las asignaciones automáticas guardan su confianza.
                </p>
            </div>
            <a href="index.php?ruta=configuraciones" class="boton-accion boton-accion-ver">&larr; Volver a Configuraciones</a>
        </div>

        <div class="grupo-acciones-encabezado">
            <button type="button" class="boton-accion boton-accion-enviar" id="boton-calcular-vacios">Calcular vacíos</button>
            <button type="button" class="boton-accion boton-accion-eliminar" id="boton-recalcular-todo">Recalcular todo</button>
        </div>

        <p class="texto-atenuado" id="estado-categorias" role="status"></p>

        <table class="tabla-usuarios" id="tabla-resumen-categorias" hidden>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Gastos</th>
                </tr>
            </thead>
            <tbody id="cuerpo-resumen-categorias"></tbody>
        </table>
    </div>

    <script src="publico/js/categorizador-gastos.js?v=<?= @filemtime(__DIR__ . '/../../publico/js/categorizador-gastos.js') ?: 1 ?>"></script>
    <script>
    (function () {
        var URL_BASE = 'index.php?ruta=categorias-gasto';
        var TAMANO_LOTE = 300;
        var botones = [document.getElementById('boton-calcular-vacios'), document.getElementById('boton-recalcular-todo')];
        var estado = document.getElementById('estado-categorias');
        var tabla = document.getElementById('tabla-resumen-categorias');
        var cuerpo = document.getElementById('cuerpo-resumen-categorias');

        function fila(concepto, valor) {
            var tr = document.createElement('tr');
            var a = document.createElement('td');
            a.textContent = concepto;
            var b = document.createElement('td');
            b.style.textAlign = 'right';
            b.textContent = valor;
            tr.appendChild(a);
            tr.appendChild(b);
            return tr;
        }

        function mostrarResumen(filas) {
            cuerpo.innerHTML = '';
            filas.forEach(function (par) { cuerpo.appendChild(fila(par[0], par[1])); });
            tabla.hidden = false;
        }

        function bloquear(activo) {
            botones.forEach(function (boton) { boton.disabled = activo; });
        }

        function guardarLote(items) {
            return fetch(URL_BASE + '&accion=guardar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: items })
            }).then(function (respuesta) {
                return respuesta.json().then(function (cuerpoRespuesta) {
                    if (!respuesta.ok || !cuerpoRespuesta.ok) {
                        throw new Error(cuerpoRespuesta.error || 'Error al guardar el lote.');
                    }
                    return cuerpoRespuesta.guardados;
                });
            });
        }

        async function correrLote(modo) {
            bloquear(true);
            tabla.hidden = true;
            estado.textContent = 'Cargando gastos…';

            try {
                var respuesta = await fetch(URL_BASE + '&accion=datos&modo=' + modo);
                var datos = await respuesta.json();
                var motor = CategorizadorGastos.crearMotor(datos.catalogo, datos.mapeo);

                var conteo = { total: datos.gastos.length, asignados: 0, noAplica: 0, sinAsignar: 0, confianzaAlta: 0, confianzaMedia: 0, confianzaBaja: 0 };
                var lote = [];

                datos.gastos.forEach(function (gasto) {
                    var r = motor.clasificar(gasto);
                    if (r.origen === null) {
                        conteo.sinAsignar++;
                        return;
                    }
                    if (r.origen === 'no_aplica') {
                        conteo.noAplica++;
                    } else {
                        conteo.asignados++;
                        if (r.confianza >= 0.65) { conteo.confianzaAlta++; }
                        else if (r.confianza >= 0.4) { conteo.confianzaMedia++; }
                        else { conteo.confianzaBaja++; }
                    }
                    lote.push({ id: gasto.id, categoria_id: r.categoriaId, origen: r.origen, confianza: r.confianza });
                });

                var guardados = 0;
                for (var inicio = 0; inicio < lote.length; inicio += TAMANO_LOTE) {
                    estado.textContent = 'Guardando ' + Math.min(inicio + TAMANO_LOTE, lote.length) + ' de ' + lote.length + '…';
                    guardados += await guardarLote(lote.slice(inicio, inicio + TAMANO_LOTE));
                }

                estado.textContent = modo === 'todo'
                    ? 'Recalculo completo: ' + guardados + ' gastos actualizados.'
                    : 'Vacíos calculados: ' + guardados + ' gastos actualizados.';
                mostrarResumen([
                    ['Gastos procesados', conteo.total],
                    ['Asignados por el motor', conteo.asignados],
                    ['   confianza alta (≥ 65 %)', conteo.confianzaAlta],
                    ['   confianza media (40–64 %)', conteo.confianzaMedia],
                    ['   confianza baja (< 40 %)', conteo.confianzaBaja],
                    ['No aplican (asignación de techo o nómina)', conteo.noAplica],
                    ['Sin asignar (rubro sin mapeo)', conteo.sinAsignar],
                    ['Manuales sobrescritos', modo === 'todo' ? datos.resumen.manuales : 0]
                ]);
            } catch (error) {
                estado.textContent = 'No se completó el proceso: ' + error.message;
            } finally {
                bloquear(false);
            }
        }

        document.getElementById('boton-calcular-vacios').addEventListener('click', function () {
            correrLote('vacios');
        });

        document.getElementById('boton-recalcular-todo').addEventListener('click', function () {
            if (!confirm('Recalcular todo vuelve a clasificar TODOS los gastos y sobrescribe también las categorías elegidas a mano. ¿Continuar?')) {
                return;
            }
            correrLote('todo');
        });
    })();
    </script>

<?php require __DIR__ . '/../parciales/pie.php'; ?>
