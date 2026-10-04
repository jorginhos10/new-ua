(function (global) {
    'use strict';

    // Normaliza texto para comparar: minúsculas, sin tildes ni signos, espacios colapsados.
    function normalizar(texto) {
        if (!texto) {
            return '';
        }

        return String(texto)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    // Cuenta cuántas palabras clave (o frases) del catálogo aparecen completas en el texto.
    function contarCoincidencias(textoNormalizado, tokens) {
        var rodeado = ' ' + textoNormalizado + ' ';
        var coincidencias = 0;
        tokens.forEach(function (token) {
            if (token && rodeado.indexOf(' ' + token + ' ') !== -1) {
                coincidencias++;
            }
        });

        return coincidencias;
    }

    // catalogo: [{id, subcategoria, macro, tokens: ['...']}]
    // mapeo: [{prefijo_codigo, accion: 'categoria'|'no_aplica', categoria_id, es_defecto, peso}]
    function crearMotor(catalogo, mapeo) {
        var categorias = {};
        catalogo.forEach(function (categoria) {
            categorias[categoria.id] = {
                id: categoria.id,
                macro: categoria.macro,
                subcategoria: categoria.subcategoria,
                tokens: (categoria.tokens || []).map(normalizar).filter(Boolean)
            };
        });

        var reglas = mapeo.map(function (regla) {
            return {
                prefijo: regla.prefijo_codigo,
                accion: regla.accion,
                categoriaId: regla.categoria_id,
                defecto: Number(regla.es_defecto) === 1,
                peso: Number(regla.peso) || 1
            };
        });

        // La regla de prefijo más largo que coincida con el código del rubro gana (por segmentos).
        function reglasDelRubro(codigo) {
            if (!codigo) {
                return [];
            }

            var prefijoGanador = null;
            reglas.forEach(function (regla) {
                var coincide = codigo === regla.prefijo || codigo.indexOf(regla.prefijo + '.') === 0;
                if (coincide && (prefijoGanador === null || regla.prefijo.length > prefijoGanador.length)) {
                    prefijoGanador = regla.prefijo;
                }
            });

            if (prefijoGanador === null) {
                return [];
            }

            return reglas.filter(function (regla) {
                return regla.prefijo === prefijoGanador;
            });
        }

        // Devuelve {categoriaId, origen, confianza, razon}. origen null = sin asignar.
        function clasificar(gasto) {
            if (gasto.tipo_automatico === 'techo_hijo') {
                return { categoriaId: null, origen: 'no_aplica', confianza: null, razon: 'asignacion_de_techo' };
            }

            var candidatas = reglasDelRubro(gasto.rubro_codigo);
            if (candidatas.length === 0) {
                return { categoriaId: null, origen: null, confianza: null, razon: 'sin_mapeo' };
            }

            if (candidatas[0].accion === 'no_aplica') {
                return { categoriaId: null, origen: 'no_aplica', confianza: null, razon: 'fuera_de_actividades' };
            }

            if (candidatas.length === 1) {
                return { categoriaId: candidatas[0].categoriaId, origen: 'automatico', confianza: 0.85, razon: 'rubro' };
            }

            var texto = normalizar([gasto.actividad, gasto.insumo, gasto.rubro_texto].join(' '));
            var mejor = null;

            candidatas.forEach(function (regla) {
                var categoria = categorias[regla.categoriaId];
                if (!categoria) {
                    return;
                }

                var coincidencias = contarCoincidencias(texto, categoria.tokens);
                var puntaje;
                if (coincidencias > 0) {
                    puntaje = Math.min(0.95, 0.55 + 0.15 * coincidencias) * regla.peso;
                } else {
                    puntaje = (regla.defecto ? 0.45 : 0.30) * regla.peso;
                }

                var gana = mejor === null
                    || puntaje > mejor.puntaje
                    || (puntaje === mejor.puntaje && regla.defecto && !mejor.defecto);

                if (gana) {
                    mejor = { categoriaId: regla.categoriaId, puntaje: puntaje, coincidencias: coincidencias, defecto: regla.defecto };
                }
            });

            if (mejor === null) {
                return { categoriaId: null, origen: null, confianza: null, razon: 'sin_mapeo' };
            }

            var confianza = mejor.coincidencias > 0 ? Math.min(0.95, mejor.puntaje) : 0.45;

            return {
                categoriaId: mejor.categoriaId,
                origen: 'automatico',
                confianza: Math.round(confianza * 1000) / 1000,
                razon: mejor.coincidencias > 0 ? 'texto' : 'defecto_rubro'
            };
        }

        return { clasificar: clasificar, normalizar: normalizar };
    }

    global.CategorizadorGastos = { crearMotor: crearMotor, normalizar: normalizar };
})(window);
