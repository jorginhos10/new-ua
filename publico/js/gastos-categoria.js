(function () {
    'use strict';

    var datosEl = document.getElementById('datos-categorias-gasto');
    if (!datosEl || !window.CategorizadorGastos) {
        return;
    }

    var datos = JSON.parse(datosEl.textContent);
    var motor = CategorizadorGastos.crearMotor(datos.catalogo, datos.mapeo);

    // Se engancha a los dos formularios (alta sin prefijo, edición con prefijo "editar-").
    ['', 'editar-'].forEach(function (prefijo) {
        var select = document.getElementById(prefijo + 'categoria_gasto_id');
        if (!select) {
            return;
        }

        var campoOrigen = document.getElementById(prefijo + 'categoria_origen');
        var campoConfianza = document.getElementById(prefijo + 'categoria_confianza');
        var sugerencia = document.getElementById(prefijo + 'categoria_sugerencia');
        var campoRubro = document.getElementById(prefijo + 'rubro_id');
        var listaRubros = document.getElementById(prefijo + 'rubro_lista');
        var campoActividad = document.getElementById(prefijo + 'actividad');
        var campoInsumo = document.getElementById(prefijo + 'insumo');

        function textoRubro() {
            if (!campoRubro || !campoRubro.value || !listaRubros) {
                return '';
            }

            var opcion = listaRubros.querySelector('.selector-buscable-opcion[data-id="' + campoRubro.value + '"]');
            return opcion ? opcion.dataset.texto || '' : '';
        }

        function codigoRubro() {
            var texto = textoRubro();
            return texto.indexOf(' - ') !== -1 ? texto.split(' - ')[0].trim() : null;
        }

        function nombreCategoria(id) {
            var categoria = datos.catalogo.filter(function (c) { return c.id === id; })[0];
            return categoria ? categoria.id + ' · ' + categoria.subcategoria : id;
        }

        function sugerir() {
            // Lo que eligió una persona a mano no se reemplaza.
            if (campoOrigen && campoOrigen.value === 'manual') {
                return;
            }

            var resultado = motor.clasificar({
                rubro_codigo: codigoRubro(),
                actividad: campoActividad ? campoActividad.value : '',
                insumo: campoInsumo ? campoInsumo.value : '',
                rubro_texto: '',
                tipo_automatico: null
            });

            if (resultado.origen !== 'automatico' || resultado.confianza < 0.35) {
                if (sugerencia) {
                    sugerencia.textContent = '';
                }
                return;
            }

            select.value = resultado.categoriaId;
            if (campoOrigen) { campoOrigen.value = 'automatico'; }
            if (campoConfianza) { campoConfianza.value = String(resultado.confianza); }

            if (sugerencia) {
                var porcentaje = Math.round(resultado.confianza * 100);
                sugerencia.textContent = resultado.confianza >= 0.65
                    ? 'Categoría sugerida: ' + nombreCategoria(resultado.categoriaId) + ' (confianza ' + porcentaje + ' %).'
                    : '[Sugerido] ' + nombreCategoria(resultado.categoriaId) + ' (confianza media ' + porcentaje + ' %). Revísala.';
            }
        }

        select.addEventListener('change', function () {
            if (campoOrigen) { campoOrigen.value = select.value ? 'manual' : ''; }
            if (campoConfianza) { campoConfianza.value = ''; }
            if (sugerencia) {
                sugerencia.textContent = select.value ? 'Categoría elegida a mano.' : '';
            }
        });

        if (campoRubro) {
            campoRubro.addEventListener('change', sugerir);
        }
        [campoActividad, campoInsumo].forEach(function (campo) {
            if (campo) {
                campo.addEventListener('blur', sugerir);
            }
        });
    });
})();
