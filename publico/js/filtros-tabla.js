// Filtro por columna, siempre visible (sin botón "Filtrar" que los oculte) — mismo lenguaje visual
// y mecánica de combobox de Dev > Tabla (predicción en gris dentro del input + tarjeta desplegable
// con los valores únicos de esa columna, para evitar errores de tipeo), para cualquier tabla que
// traiga una fila con class="fila-filtros-siempre" y celdas .combo-filtro-siempre con data-indice.
document.addEventListener('DOMContentLoaded', function () {
    function crearCombobox(envoltorio, valores) {
        var input = envoltorio.querySelector('.combo-input');
        var fantasma = envoltorio.querySelector('.combo-fantasma');
        var tarjeta = envoltorio.querySelector('.combo-tarjeta');
        var tecleado = document.createElement('span');
        var sugerenciaSpan = document.createElement('span');
        tecleado.className = 'combo-fantasma-tecleado';
        sugerenciaSpan.className = 'combo-fantasma-sugerencia';
        fantasma.appendChild(tecleado);
        fantasma.appendChild(sugerenciaSpan);

        function sugerenciaPara(texto) {
            if (texto === '') {
                return '';
            }
            var textoMin = texto.toLowerCase();
            var encontrado = valores.find(function (v) {
                return v.length > texto.length && v.toLowerCase().indexOf(textoMin) === 0;
            });
            return encontrado ? encontrado.slice(texto.length) : '';
        }

        function actualizarFantasma() {
            tecleado.textContent = input.value;
            sugerenciaSpan.textContent = sugerenciaPara(input.value);
        }

        function cerrarTarjeta() {
            tarjeta.classList.remove('abierta');
        }

        function renderTarjeta() {
            var textoMin = input.value.toLowerCase();
            var coincidencias = valores.filter(function (v) { return v.toLowerCase().indexOf(textoMin) !== -1; });

            tarjeta.innerHTML = '';

            if (coincidencias.length === 0) {
                var vacio = document.createElement('div');
                vacio.className = 'combo-tarjeta-vacio';
                vacio.textContent = 'Sin coincidencias';
                tarjeta.appendChild(vacio);
                return;
            }

            coincidencias.slice(0, 50).forEach(function (valor) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'combo-tarjeta-item';
                item.textContent = valor;
                item.addEventListener('mousedown', function (evento) {
                    evento.preventDefault();
                    input.value = valor;
                    actualizarFantasma();
                    cerrarTarjeta();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
                tarjeta.appendChild(item);
            });
        }

        input.addEventListener('input', function () {
            actualizarFantasma();
            tarjeta.classList.add('abierta');
            renderTarjeta();
        });

        input.addEventListener('keydown', function (evento) {
            var sugerencia = sugerenciaSpan.textContent;
            if (sugerencia && (evento.key === 'Tab' || evento.key === 'ArrowRight') && input.selectionStart === input.value.length) {
                evento.preventDefault();
                input.value += sugerencia;
                actualizarFantasma();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            } else if (evento.key === 'Escape') {
                cerrarTarjeta();
            }
        });

        input.addEventListener('click', function () { renderTarjeta(); tarjeta.classList.add('abierta'); });
        input.addEventListener('focus', function () { renderTarjeta(); tarjeta.classList.add('abierta'); });
        document.addEventListener('click', function (evento) {
            if (!envoltorio.contains(evento.target)) {
                cerrarTarjeta();
            }
        });

        actualizarFantasma();
    }

    document.querySelectorAll('table').forEach(function (tabla) {
        var envoltorios = tabla.querySelectorAll('.combo-filtro-siempre');
        if (envoltorios.length === 0) {
            return;
        }

        var cuerpo = tabla.querySelector('tbody');
        if (!cuerpo) {
            return;
        }

        var filasOriginales = Array.prototype.slice.call(cuerpo.querySelectorAll('tr'));

        // El texto de una celda para filtrar/sugerir descarta íconos/insignias decorativas (la
        // letra del ícono de rol, la estrellita de superadmin) — si no, "Gestor" aparecía en la
        // tarjeta como "GGestor" (la letra del ícono pegada al nombre).
        function textoCeldaLimpio(celda) {
            if (!celda) {
                return '';
            }
            var clon = celda.cloneNode(true);
            clon.querySelectorAll('.icono-rol, .estrella-super-admin').forEach(function (el) { el.remove(); });
            return clon.textContent.replace(/\s+/g, ' ').trim();
        }

        function valoresUnicosColumna(indice) {
            var valores = {};
            filasOriginales.forEach(function (fila) {
                var texto = textoCeldaLimpio(fila.children[indice]);
                if (texto !== '') {
                    valores[texto] = true;
                }
            });
            return Object.keys(valores).sort(function (a, b) { return a.localeCompare(b, 'es'); });
        }

        var filtros = [];

        envoltorios.forEach(function (envoltorio) {
            var indice = parseInt(envoltorio.dataset.indice, 10);
            crearCombobox(envoltorio, valoresUnicosColumna(indice));
            filtros.push({ indice: indice, input: envoltorio.querySelector('.combo-input') });
        });

        function aplicarFiltros() {
            var activos = filtros
                .map(function (f) { return { indice: f.indice, valor: f.input.value.trim().toLowerCase() }; })
                .filter(function (f) { return f.valor !== ''; });

            filasOriginales.forEach(function (fila) {
                var visible = activos.every(function (filtro) {
                    return textoCeldaLimpio(fila.children[filtro.indice]).toLowerCase().indexOf(filtro.valor) !== -1;
                });
                fila.style.display = visible ? '' : 'none';
            });
        }

        filtros.forEach(function (f) {
            f.input.addEventListener('input', aplicarFiltros);
        });
    });
});
