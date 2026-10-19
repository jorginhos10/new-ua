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

        // Rubros 2.05 (gastos de comercialización, autogestión) = los mismos rubros de 2.02 (adquisición de
        // bienes y servicios): 2.05.X.Y.A.B equivale a 2.02.02.Y.A.B y, para maquinaria y equipo, a
        // 2.02.01.Y.A.B. Solo se usa si el código 2.05 no tiene regla propia (una regla de 2.05 siempre gana).
        function reglasDelRubro(codigo) {
            var propias = reglasDelCodigo(codigo);
            if (propias.length > 0 || !codigo || codigo.indexOf('2.05.') !== 0) {
                return propias;
            }

            var segmentos = codigo.split('.');
            if (segmentos.length < 4) {
                return propias;
            }
            var cola = segmentos.slice(3).join('.');
            var equivalentes = ['2.02.02.' + cola, '2.02.01.' + cola];
            for (var i = 0; i < equivalentes.length; i++) {
                var reglasEquivalente = reglasDelCodigo(equivalentes[i]);
                if (reglasEquivalente.length > 0) {
                    return reglasEquivalente;
                }
            }

            return propias;
        }

        // La regla de prefijo más largo que coincida con el código del rubro gana (por segmentos).
        function reglasDelCodigo(codigo) {
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

        // Para filas SIN código de rubro (p. ej. los Excedentes automáticos de autogestión, que solo
        // guardan rubro_texto): puntúa todas las categorías del catálogo con sus tokens contra
        // actividad + insumo + rubro_texto. Asigna la mejor solo con al menos MINIMO_COINCIDENCIAS_TEXTO
        // coincidencias y sin empate; la confianza es media fija para que se note que salió del texto.
        var MINIMO_COINCIDENCIAS_TEXTO = 2;

        function clasificarPorTexto(gasto) {
            var texto = normalizar([gasto.actividad, gasto.insumo, gasto.rubro_texto].join(' '));
            var mejor = null;
            var empate = false;

            Object.keys(categorias).forEach(function (id) {
                var coincidencias = contarCoincidencias(texto, categorias[id].tokens);
                if (coincidencias === 0) {
                    return;
                }

                if (mejor === null || coincidencias > mejor.coincidencias) {
                    mejor = { categoriaId: id, coincidencias: coincidencias };
                    empate = false;
                } else if (coincidencias === mejor.coincidencias) {
                    empate = true;
                }
            });

            if (mejor === null || empate || mejor.coincidencias < MINIMO_COINCIDENCIAS_TEXTO) {
                return { categoriaId: null, origen: null, confianza: null, razon: 'sin_texto' };
            }

            return { categoriaId: mejor.categoriaId, origen: 'automatico', confianza: 0.6, razon: 'texto_sin_rubro' };
        }

        return { clasificar: clasificar, clasificarPorTexto: clasificarPorTexto, normalizar: normalizar };
    }

    global.CategorizadorGastos = { crearMotor: crearMotor, normalizar: normalizar };
})(window);
